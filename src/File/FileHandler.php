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
namespace UserAccessManager\File;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Util\Util;
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
     *
     * @param Php                   $php
     * @param Wordpress             $wordpress
     * @param WordpressConfig       $wordpressConfig
     * @param MainConfig            $mainConfig
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
        if (is_numeric(ob_get_length()) === true) {
            ob_clean();
        }

        flush();
    }

    /**
     * Returns the file mine type.
     *
     * @param string $file
     *
     * @return string
     */
    private function getFileMineType($file)
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

        return (string)$fileMimeType;
    }

    /**
     * Adds the default header.
     *
     * @param string $file
     * @param bool   $isInline
     */
    private function addDefaultHeader($file, $isInline)
    {
        $fileMimeType = $this->getFileMineType($file);
        $contentDisposition = ($isInline === true) ? 'inline' : 'attachment';
        $baseName = str_replace(' ', '_', basename($file));

        header('Content-Description: File Transfer');
        header('Content-Type: '.$fileMimeType);
        header("Content-Disposition: {$contentDisposition}; filename=\"{$baseName}\"");
    }

    /**
     * Delivers the file via fopen.
     *
     * @param string $file
     */
    private function deliverFileViaFopen($file)
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
     *
     * @param string $file
     * @param bool   $isInline
     */
    private function deliverFile($file, $isInline)
    {
        $downloadType = $this->mainConfig->getDownloadType();

        if ($downloadType === 'xsendfile') {
            header("X-Sendfile: {$file}");
        }

        $this->addDefaultHeader($file, $isInline);

        if ($downloadType !== 'xsendfile') {
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($file));
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
     *
     * @param string $rangeOrigin
     * @param int    $fileSize
     * @param int    $seekStart
     * @param int    $seekEnd
     */
    private function getSeekStartEnd($rangeOrigin, $fileSize, &$seekStart, &$seekEnd)
    {
        //just serve the first range
        $range = explode(',', $rangeOrigin)[0];
        //figure out download piece from range (if set)
        $seek = explode('-', $range);
        $seekStart = abs((int)$seek[0]);
        $seekEnd = isset($seek[1]) === true ? abs((int)$seek[1]) : 0;
        //start and end based on range (if set), else set defaults also check for invalid ranges.
        $seekEnd = ($seekEnd === 0) ? ($fileSize - 1) : min($seekEnd, ($fileSize - 1));
        $seekStart = ($seekEnd < $seekStart) ? 0 : max($seekStart, 0);
    }

    /**
     * Delivers the file partial.
     *
     * @param string $file
     * @param bool   $isInline
     */
    private function deliverFilePartial($file, $isInline)
    {
        $httpRange = explode('=', $_SERVER['HTTP_RANGE']);
        $sizeUnit = $httpRange[0];
        $rangeOrigin = isset($httpRange[1]) === true ? $httpRange[1] : '';

        if ($sizeUnit === 'bytes') {
            $fileSize = filesize($file);
            $this->getSeekStartEnd($rangeOrigin, $fileSize, $seekStart, $seekEnd);

            $this->addDefaultHeader($file, $isInline);
            header('HTTP/1.1 206 Partial Content');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            header('Content-Range: bytes '.$seekStart.'-'.$seekEnd.'/'.$fileSize);
            header('Content-Length: '.($seekEnd - $seekStart + 1));

            $handler = fopen($file, 'r');
            fseek($handler, $seekStart);

            while (feof($handler) === false) {
                echo $this->php->fread($handler, 1024 * 8);
                $this->clearBuffer();

                if ($this->php->connectionStatus() !== 0) {
                    $this->php->fClose($handler);
                    break;
                }
            }
        } else {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
        }
    }

    /**
     * Checks if the file is an inline file
     *
     * @param string $file
     *
     * @return bool
     */
    private function isInlineFile($file)
    {
        $inlineFiles = array_map('trim', explode(',', $this->mainConfig->getInlineFiles()));
        $map = array_flip($inlineFiles);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return isset($map[$extension]);
    }

    /**
     * Delivers the content of the requested file.
     *
     * @param string $file
     * @param bool   $isImage
     */
    public function getFile($file, $isImage)
    {
        //Deliver content
        if (file_exists($file) === true) {
            $isInline = $isImage  === true || $this->isInlineFile($file) === true;

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
     *
     * @return FileProtectionInterface
     */
    private function getCurrentFileProtectionHandler()
    {
        if ($this->wordpress->isNginx() === true) {
            return $this->fileProtectionFactory->createNginxFileProtection();
        }

        return $this->fileProtectionFactory->createApacheFileProtection();
    }

    /**
     * Returns the file protection file.
     *
     * @return string
     */
    public function getFileProtectionFileName()
    {
        return $this->getCurrentFileProtectionHandler()->getFileNameWithPath(
            $this->wordpressConfig->getUploadDirectory()
        );
    }

    /**
     * Creates a protection file.
     *
     * @param string $dir        The destination directory.
     * @param string $objectType The object type.
     *
     * @return false
     */
    public function createFileProtection($dir = null, $objectType = null)
    {
        $dir = ($dir === null) ? $this->wordpressConfig->getUploadDirectory() : $dir;

        if ($dir !== null) {
            return $this->getCurrentFileProtectionHandler()->create($dir, $objectType);
        }

        return false;
    }

    /**
     * Deletes the protection files.
     *
     * @param string $dir The destination directory.
     *
     * @return false
     */
    public function deleteFileProtection($dir = null)
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
        $file = $this->wordpressConfig->getUploadDirectory().DIRECTORY_SEPARATOR.self::X_SEND_FILE_TEST_FILE;
        file_put_contents($file, 'success');

        header("X-Sendfile: {$file}");
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        $this->php->callExit();
    }

    /**
     * Removes the xsendfile test file if exists.
     */
    public function removeXSendFileTestFile()
    {
        $file = $this->wordpressConfig->getUploadDirectory().DIRECTORY_SEPARATOR.self::X_SEND_FILE_TEST_FILE;

        if (file_exists($file) === true) {
            unlink($file);
        }
    }
}
