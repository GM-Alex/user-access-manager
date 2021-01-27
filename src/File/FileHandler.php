<?php
/**
 * FileHandler.php
 *
 * The FileHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\File;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FileHandler
 *
 * @package UserAccessManager\FileHandler
 */
class FileHandler
{
    const X_SEND_FILE_TEST_FILE = 'xSendFileTestFile';

    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var WordpressConfig
     */
    private $wordpressConfig;

    /**
     * @var MainConfig
     */
    private $mainConfig;

    /**
     * @var FileProtectionFactory
     */
    private $fileProtectionFactory;

    /**
     * FileHandler constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig $mainConfig
     * @param FileProtectionFactory $fileProtectionFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        FileProtectionFactory $fileProtectionFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->wordpressConfig = $wordpressConfig;
        $this->mainConfig = $mainConfig;
        $this->fileProtectionFactory = $fileProtectionFactory;
    }

    /**
     * Clears the buffer.
     */
    private function clearBuffer()
    {
        //prevent '\n' / '0A'
        if ((int) $this->php->iniGet('output_buffering') === 0
            && is_numeric(ob_get_length()) === true
        ) {
            ob_clean();
        }

        flush();
    }

    /**
     * Returns the file mine type.
     * @param string $file
     * @return string
     */
    private function getFileMineType(string $file): string
    {
        $fileName = basename($file);

        /*
         * This only for compatibility
         * mime_content_type has been deprecated as the PECL extension file info
         * provides the same functionality (and more) in a much cleaner way.
         */
        $explodedFileName = explode('.', $fileName);
        $lastElement = array_pop($explodedFileName);
        $fileExt = strtolower($lastElement);

        $mimeTypes = $this->wordpressConfig->getMimeTypes();

        if ($this->php->functionExists('finfo_open') === true) {
            $fileInfo = finfo_open(FILEINFO_MIME);
            $fileMimeType = finfo_file($fileInfo, $file);
            finfo_close($fileInfo);
        } elseif ($this->php->functionExists('mime_content_type')) {
            $fileMimeType = mime_content_type($file);
        } elseif (isset($mimeTypes[$fileExt]) === true) {
            $fileMimeType = $mimeTypes[$fileExt];
        } else {
            $fileMimeType = 'application/octet-stream';
        }

        return (string) $fileMimeType;
    }

    /**
     * Adds the default header.
     * @param string $file
     * @param bool $isInline
     */
    private function addDefaultHeader(string $file, bool $isInline)
    {
        $fileMimeType = $this->getFileMineType($file);
        $contentDisposition = ($isInline === true) ? 'inline' : 'attachment';
        $baseName = str_replace(' ', '_', basename($file));

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $fileMimeType);
        header("Content-Disposition: {$contentDisposition}; filename=\"{$baseName}\"");
    }

    /**
     * Delivers the file via fopen.
     * @param string $file
     */
    private function deliverFileViaFopen(string $file)
    {
        $handler = fopen($file, 'r');

        while (feof($handler) === false) {
            if ($this->php->iniGet('safe_mode') !== '') {
                $this->php->setTimeLimit(30);
            }

            echo $this->php->fread($handler, 1024);
        }
    }

    /**
     * Delivers the file.
     * @param string $file
     * @param bool $isInline
     */
    private function deliverFile(string $file, bool $isInline)
    {
        $downloadType = $this->mainConfig->getDownloadType();

        if ($downloadType === 'xsendfile') {
            header("X-Sendfile: {$file}");
        }

        $this->addDefaultHeader($file, $isInline);

        if ($downloadType !== 'xsendfile') {
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($file));
            $this->clearBuffer();

            if ($downloadType === 'fopen') {
                $this->deliverFileViaFopen($file);
            } else {
                readfile($file);
            }
        }
    }

    /**
     * Sets the seek start and end.
     * @param string $range
     * @param int $fileSize
     * @param int|null $seekStart
     * @param int|null $seekEnd
     * @return bool
     */
    private function getSeekStartEnd(string $range, int $fileSize, ?int &$seekStart, ?int &$seekEnd): bool
    {
        //Figure out download piece from range (if set)
        $seek = explode('-', $range);
        $seekStart = ($seek[0] !== '') ? abs((int) $seek[0]) : null;
        $seekEnd = (isset($seek[1]) === true && $seek[1] !== '') ? abs((int) $seek[1]) : null;
        $maxSize = $fileSize - 1;

        if ($seekStart === null) {
            $seekStart = $fileSize - $seekEnd;
            $seekEnd = $maxSize;
        } elseif ($seekEnd === null) {
            $seekEnd = $maxSize;
        }

        //Start and end based on range (if set), else set defaults also check for invalid ranges.
        $seekEnd = min($seekEnd, $maxSize);

        return $seekStart < $seekEnd;
    }

    /**
     * Reads the file partly.
     * @param resource $fileHandler
     * @param int $bytes
     */
    private function readFilePartly($fileHandler, int $bytes)
    {
        $bytesLeft = $bytes;
        $bufferSize = 1024;

        while ($bytesLeft > 0 && feof($fileHandler) === false) {
            $bytesToRead = min($bytesLeft, $bufferSize);
            $bytesLeft -= $bytesToRead;
            echo $this->php->fread($fileHandler, $bytesToRead);
            $this->clearBuffer();

            if ($this->php->connectionStatus() !== 0) {
                $this->php->fClose($fileHandler);
                break;
            }
        }
    }

    /**
     * Returns the http ranges.
     * @param int $fileSize
     * @return array
     */
    private function getRanges(int $fileSize): array
    {
        $httpRange = explode('=', $_SERVER['HTTP_RANGE']);
        $originRanges = isset($httpRange[1]) === true ? $httpRange[1] : '';
        $originRanges = explode(',', $originRanges);
        $sizeUnit = $httpRange[0];
        $ranges = [];

        if ($sizeUnit === 'bytes') {
            foreach ($originRanges as $originRange) {
                if ($this->getSeekStartEnd($originRange, $fileSize, $seekStart, $seekEnd) === false) {
                    $ranges = [];
                    break;
                }

                $ranges[] = [$seekStart, $seekEnd];
            }
        }

        return $ranges;
    }

    /**
     * Returns the extra contents.
     * @param string $file
     * @param array $ranges
     * @param int|null $contentLength
     * @param string|null $boundary
     * @return array
     */
    private function getExtraContents(string $file, array $ranges, ?int &$contentLength, ?string &$boundary): array
    {
        $contentLength = 0;
        $extraContents = [];

        //More than one range is requested?
        if (count($ranges) > 1) {
            $boundary = 'g45d64df96bmdf4sdgh45hf5';
            $fullBoundary = "\r\n--{$boundary}--\r\n";
            $fileSize = filesize($file);
            $mineType = $this->getFileMineType($file);

            //compute content length
            foreach ($ranges as $index => $range) {
                list($seekStart, $seekEnd) = $range;
                $extraContent = $fullBoundary;
                $extraContent .= "Content-Type: {$mineType}\r\n";
                $extraContent .= "Content-Range: bytes $seekStart-$seekEnd/$fileSize\r\n\r\n";
                $extraContents[$index] = $extraContent;
                $contentLength += strlen($extraContent) + ($seekEnd - $seekStart + 1);
            }

            $contentLength += strlen($fullBoundary);
            $extraContents[] = $fullBoundary;
        }

        return $extraContents;
    }

    /**
     * Delivers the file partial.
     * @param string $file
     * @param bool $isInline
     */
    private function deliverFilePartial(string $file, bool $isInline)
    {
        $fileSize = filesize($file);
        $ranges = $this->getRanges($fileSize);

        if ($ranges !== []) {
            $extraContents = $this->getExtraContents($file, $ranges, $contentLength, $boundary);

            header('HTTP/1.1 206 Partial Content');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');

            if ($extraContents === []) {
                $this->addDefaultHeader($file, $isInline);
                list($seekStart, $seekEnd) = $ranges[0];
                $contentLength = ($seekEnd - $seekStart + 1);
                header("Content-Range: bytes {$seekStart}-{$seekEnd}/{$fileSize}");
            } else {
                header("Content-Type: multipart/x-byteranges; boundary={$boundary}");
            }

            header("Content-Length: {$contentLength}");
            $fileHandler = fopen($file, 'r');

            foreach ($ranges as $index => $range) {
                if (isset($extraContents[$index]) === true) {
                    echo $extraContents[$index];
                }

                list($seekStart, $seekEnd) = $ranges[0];
                fseek($fileHandler, $seekStart);
                $this->readFilePartly($fileHandler, $seekEnd - $seekStart + 1);
            }

            if ($extraContents !== []) {
                echo end($extraContents);
                $this->clearBuffer();
            }
        } else {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: */$fileSize");
        }
    }

    /**
     * Checks if the file is an inline file
     * @param string $file
     * @return bool
     */
    private function isInlineFile(string $file): bool
    {
        $inlineFiles = array_map('trim', explode(',', (string) $this->mainConfig->getInlineFiles()));
        $map = array_flip($inlineFiles);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return isset($map[$extension]);
    }

    /**
     * Delivers the content of the requested file.
     * @param string $file
     * @param bool $isImage
     */
    public function getFile(string $file, bool $isImage)
    {
        //Deliver content
        if (file_exists($file) === true) {
            $isInline = $isImage === true || $this->isInlineFile($file) === true;

            if (isset($_SERVER['HTTP_RANGE']) === true
                && isset($_SERVER['REQUEST_METHOD']) === true
                && $_SERVER['REQUEST_METHOD'] === 'GET'
            ) {
                $this->deliverFilePartial($file, $isInline);
            } else {
                $this->deliverFile($file, $isInline);
            }

            $this->php->callExit();
        } else {
            $this->wordpress->wpDie(
                TXT_UAM_FILE_NOT_FOUND_ERROR_MESSAGE,
                TXT_UAM_FILE_NOT_FOUND_ERROR_TITLE,
                ['response' => 404]
            );
        }
    }

    /**
     * Returns the current file protection handler.
     * @return FileProtectionInterface
     */
    private function getCurrentFileProtectionHandler(): FileProtectionInterface
    {
        if ($this->wordpress->isNginx() === true) {
            return $this->fileProtectionFactory->createNginxFileProtection();
        }

        return $this->fileProtectionFactory->createApacheFileProtection();
    }

    /**
     * Returns the file protection file.
     * @return string
     */
    public function getFileProtectionFileName(): string
    {
        return $this->getCurrentFileProtectionHandler()->getFileNameWithPath(
            $this->wordpressConfig->getUploadDirectory()
        );
    }

    /**
     * Creates a protection file.
     * @param string $dir The destination directory.
     * @param string $objectType The object type.
     * @return false
     */
    public function createFileProtection($dir = null, $objectType = null): bool
    {
        $dir = ($dir === null) ? $this->wordpressConfig->getUploadDirectory() : $dir;

        if ($dir !== null) {
            return $this->getCurrentFileProtectionHandler()->create($dir, $objectType);
        }

        return false;
    }

    /**
     * Deletes the protection files.
     * @param string $dir The destination directory.
     * @return false
     */
    public function deleteFileProtection($dir = null): bool
    {
        $dir = ($dir === null) ? $this->wordpressConfig->getUploadDirectory() : $dir;

        if ($dir !== null) {
            return $this->getCurrentFileProtectionHandler()->delete($dir);
        }

        return false;
    }

    /**
     * Delivers a xsendfile test file.
     */
    public function deliverXSendFileTestFile()
    {
        $file = $this->wordpressConfig->getUploadDirectory() . DIRECTORY_SEPARATOR . self::X_SEND_FILE_TEST_FILE;
        file_put_contents($file, 'success');

        header("X-Sendfile: {$file}");
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        $this->php->callExit();
    }

    /**
     * Removes the xsendfile test file if exists.
     */
    public function removeXSendFileTestFile()
    {
        $file = $this->wordpressConfig->getUploadDirectory() . DIRECTORY_SEPARATOR . self::X_SEND_FILE_TEST_FILE;

        if (file_exists($file) === true) {
            unlink($file);
        }
    }
}
