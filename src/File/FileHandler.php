<?php

declare(strict_types=1);

namespace UserAccessManager\File;

use JetBrains\PhpStorm\NoReturn;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class FileHandler
{
    public const X_SEND_FILE_TEST_FILE = 'xSendFileTestFile';

    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private WordpressConfig $wordpressConfig,
        private MainConfig $mainConfig,
        private FileProtectionFactory $fileProtectionFactory
    ) {}

    private function clearBuffer(): void
    {
        //prevent '\n' / '0A'
        if ((int) $this->php->iniGet('output_buffering') === 0
            && is_numeric(ob_get_length()) === true
        ) {
            ob_clean();
        }

        flush();
    }

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

    private function addDefaultHeader(string $file, bool $isInline): void
    {
        $fileMimeType = $this->getFileMineType($file);
        $contentDisposition = ($isInline === true) ? 'inline' : 'attachment';
        $baseName = str_replace(' ', '_', basename($file));

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $fileMimeType);
        header("Content-Disposition: $contentDisposition; filename=\"$baseName\"");
    }

    private function deliverFileViaFopen(string $file): void
    {
        $handler = fopen($file, 'r');

        while (feof($handler) === false) {
            if ($this->php->iniGet('safe_mode') !== '') {
                $this->php->setTimeLimit(30);
            }

            echo $this->php->fread($handler, 1024);
        }
    }

    private function deliverFile(string $file, bool $isInline): void
    {
        header("HTTP/1.1 200 OK");
        $downloadType = $this->mainConfig->getDownloadType();

        if ($downloadType === 'xsendfile') {
            header("X-Sendfile: $file");
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

    private function readFilePartly($fileHandler, int $bytes): void
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

    private function getExtraContents(string $file, array $ranges, ?int &$contentLength, ?string &$boundary): array
    {
        $contentLength = 0;
        $extraContents = [];

        //More than one range is requested?
        if (count($ranges) > 1) {
            $boundary = 'g45d64df96bmdf4sdgh45hf5';
            $fullBoundary = "\r\n--$boundary--\r\n";
            $fileSize = filesize($file);
            $mineType = $this->getFileMineType($file);

            //compute content length
            foreach ($ranges as $index => $range) {
                [$seekStart, $seekEnd] = $range;
                $extraContent = $fullBoundary;
                $extraContent .= "Content-Type: $mineType\r\n";
                $extraContent .= "Content-Range: bytes $seekStart-$seekEnd/$fileSize\r\n\r\n";
                $extraContents[$index] = $extraContent;
                $contentLength += strlen($extraContent) + ($seekEnd - $seekStart + 1);
            }

            $contentLength += strlen($fullBoundary);
            $extraContents[] = $fullBoundary;
        }

        return $extraContents;
    }

    private function deliverFilePartial(string $file, bool $isInline): void
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
                [$seekStart, $seekEnd] = $ranges[0];
                $contentLength = ($seekEnd - $seekStart + 1);
                header("Content-Range: bytes $seekStart-$seekEnd/$fileSize");
            } else {
                header("Content-Type: multipart/x-byteranges; boundary=$boundary");
            }

            header("Content-Length: $contentLength");
            $fileHandler = fopen($file, 'r');

            foreach ($ranges as $index => $range) {
                if (isset($extraContents[$index]) === true) {
                    echo $extraContents[$index];
                }

                [$seekStart, $seekEnd] = $ranges[0];
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

    private function isInlineFile(string $file): bool
    {
        $inlineFiles = array_map('trim', explode(',', (string) $this->mainConfig->getInlineFiles()));
        $map = array_flip($inlineFiles);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return isset($map[$extension]);
    }

    #[NoReturn]
    public function getFile(string $file, bool $isImage): void
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

    private function getCurrentFileProtectionHandler(): FileProtectionInterface
    {
        if ($this->wordpress->isNginx() === true) {
            return $this->fileProtectionFactory->createNginxFileProtection();
        }

        return $this->fileProtectionFactory->createApacheFileProtection();
    }

    public function getFileProtectionFileName(): string
    {
        return $this->getCurrentFileProtectionHandler()->getFileNameWithPath(
            $this->wordpressConfig->getUploadDirectory()
        );
    }

    public function createFileProtection(string $dir = null, string $objectType = null): bool
    {
        $dir = ($dir === null) ? $this->wordpressConfig->getUploadDirectory() : $dir;

        if ($dir !== null) {
            return $this->getCurrentFileProtectionHandler()->create($dir, $objectType);
        }

        return false;
    }

    public function deleteFileProtection(string $dir = null): bool
    {
        $dir = ($dir === null) ? $this->wordpressConfig->getUploadDirectory() : $dir;

        if ($dir !== null) {
            return $this->getCurrentFileProtectionHandler()->delete($dir);
        }

        return false;
    }

    #[NoReturn]
    public function deliverXSendFileTestFile(): void
    {
        $file = $this->wordpressConfig->getUploadDirectory() . DIRECTORY_SEPARATOR . self::X_SEND_FILE_TEST_FILE;
        file_put_contents($file, 'success');

        header("X-Sendfile: $file");
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        $this->php->callExit();
    }

    public function removeXSendFileTestFile(): void
    {
        $file = $this->wordpressConfig->getUploadDirectory() . DIRECTORY_SEPARATOR . self::X_SEND_FILE_TEST_FILE;

        if (file_exists($file) === true) {
            unlink($file);
        }
    }
}
