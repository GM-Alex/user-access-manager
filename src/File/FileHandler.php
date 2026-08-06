<?php

declare(strict_types=1);

namespace UserAccessManager\File;

use JetBrains\PhpStorm\NoReturn;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\File\Protection\FileProtectionFactory;
use UserAccessManager\File\Protection\FileProtectionInterface;
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
    ) {
    }

    private function clearBuffer(): void
    {
        //prevent '\n' / '0A'
        if ((int) $this->php->iniGet('output_buffering') === 0
            && is_numeric(ob_get_length()) === true
        ) {
            ob_clean();
        }

        $this->php->flush();
    }

    private function getFileMimeType(string $file): string
    {
        $explodedFileName = explode('.', basename($file));
        $fileExtension = strtolower(array_pop($explodedFileName));
        $mimeTypes = $this->wordpressConfig->getMimeTypes();

        // The deprecated mime_content_type() is only kept as a fallback for installations without fileinfo.
        if ($this->php->functionExists('finfo_open') === true) {
            $fileInfo = $this->php->fInfoOpen(FILEINFO_MIME);
            $fileMimeType = $this->php->fInfoFile($fileInfo, $file);
            $this->php->fInfoClose($fileInfo);
        } elseif ($this->php->functionExists('mime_content_type')) {
            $fileMimeType = $this->php->mimeContentType($file);
        } else {
            $fileMimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
        }

        return (string) $fileMimeType;
    }

    private function addDefaultHeader(string $file, bool $isInline): void
    {
        $fileMimeType = $this->getFileMimeType($file);
        $contentDisposition = ($isInline === true) ? 'inline' : 'attachment';
        $baseName = str_replace(' ', '_', basename($file));

        $this->php->header('Content-Description: File Transfer');
        $this->php->header('Content-Type: ' . $fileMimeType);
        $this->php->header("Content-Disposition: $contentDisposition; filename=\"$baseName\"");
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

    private function addXSendFileHeader(string $file): bool
    {
        if ($this->wordpress->isNginx()) {
            // The /uam-files prefix targets a dedicated internal location that bypasses
            // UAM's rewrite rules, which would otherwise make the redirect loop.
            $uri = '/uam-files' . str_replace(rtrim(ABSPATH, '/'), '', $file);
            $this->php->header("X-Accel-Redirect: $uri");

            return true;
        }

        if ($this->wordpress->isApacheModuleLoaded('mod_xsendfile')) {
            $this->php->header("X-Sendfile: $file");

            return true;
        }

        return false;
    }

    private function deliverFile(string $file, bool $isInline): void
    {
        $this->php->header("HTTP/1.1 200 OK");
        $downloadType = $this->mainConfig->getDownloadType();

        if ($downloadType === 'xsendfile' && $this->addXSendFileHeader($file) === false) {
            // Without server-side sending support the file still has to be delivered by PHP.
            $downloadType = 'fopen';
        }

        $this->addDefaultHeader($file, $isInline);

        if ($downloadType === 'xsendfile') {
            return;
        }

        $this->php->header('Content-Transfer-Encoding: binary');
        $this->php->header('Content-Length: ' . filesize($file));
        $this->clearBuffer();

        if ($downloadType === 'fopen') {
            $this->deliverFileViaFopen($file);
        } else {
            readfile($file);
        }
    }

    /**
     * Returns the [start, end] byte offsets of a single HTTP range, or null if the range is invalid.
     */
    private function getSeekStartEnd(string $range, int $fileSize): ?array
    {
        $seek = explode('-', $range);
        $seekStart = ($seek[0] !== '') ? abs((int) $seek[0]) : null;
        $seekEnd = (isset($seek[1]) === true && $seek[1] !== '') ? abs((int) $seek[1]) : null;
        $maxSize = $fileSize - 1;

        if ($seekStart === null) {
            $seekStart = $fileSize - $seekEnd;
            $seekEnd = $maxSize;
        }

        $seekEnd = min($seekEnd ?? $maxSize, $maxSize);

        return ($seekStart < $seekEnd) ? [$seekStart, $seekEnd] : null;
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

        if ($httpRange[0] !== 'bytes') {
            return [];
        }

        $ranges = [];

        foreach (explode(',', $httpRange[1] ?? '') as $originRange) {
            $range = $this->getSeekStartEnd($originRange, $fileSize);

            if ($range === null) {
                return [];
            }

            $ranges[] = $range;
        }

        return $ranges;
    }

    private function getExtraContents(string $file, array $ranges, ?int &$contentLength, ?string &$boundary): array
    {
        $contentLength = 0;
        $extraContents = [];

        if (count($ranges) <= 1) {
            return $extraContents;
        }

        $boundary = 'g45d64df96bmdf4sdgh45hf5';
        $fullBoundary = "\r\n--$boundary--\r\n";
        $fileSize = filesize($file);
        $mimeType = $this->getFileMimeType($file);

        foreach ($ranges as $index => $range) {
            [$seekStart, $seekEnd] = $range;
            $extraContent = $fullBoundary
                . "Content-Type: $mimeType\r\n"
                . "Content-Range: bytes $seekStart-$seekEnd/$fileSize\r\n\r\n";
            $extraContents[$index] = $extraContent;
            $contentLength += strlen($extraContent) + ($seekEnd - $seekStart + 1);
        }

        $contentLength += strlen($fullBoundary);
        $extraContents[] = $fullBoundary;

        return $extraContents;
    }

    private function deliverFilePartial(string $file, bool $isInline): void
    {
        $fileSize = filesize($file);
        $ranges = $this->getRanges($fileSize);

        if ($ranges === []) {
            $this->php->header('HTTP/1.1 416 Requested Range Not Satisfiable');
            $this->php->header("Content-Range: */$fileSize");

            return;
        }

        $extraContents = $this->getExtraContents($file, $ranges, $contentLength, $boundary);

        $this->php->header('HTTP/1.1 206 Partial Content');
        $this->php->header('Content-Transfer-Encoding: binary');
        $this->php->header('Accept-Ranges: bytes');

        if ($extraContents === []) {
            $this->addDefaultHeader($file, $isInline);
            [$seekStart, $seekEnd] = $ranges[0];
            $contentLength = ($seekEnd - $seekStart + 1);
            $this->php->header("Content-Range: bytes $seekStart-$seekEnd/$fileSize");
        } else {
            $this->php->header("Content-Type: multipart/x-byteranges; boundary=$boundary");
        }

        $this->php->header("Content-Length: $contentLength");
        $fileHandler = fopen($file, 'r');

        foreach ($ranges as $index => $range) {
            if (isset($extraContents[$index]) === true) {
                echo $extraContents[$index];
            }

            [$seekStart, $seekEnd] = $range;
            $this->php->fseek($fileHandler, $seekStart);
            $this->readFilePartly($fileHandler, $seekEnd - $seekStart + 1);
        }

        if ($extraContents !== []) {
            echo end($extraContents);
            $this->clearBuffer();
        }
    }

    private function isInlineFile(string $file): bool
    {
        $inlineFiles = array_map('trim', explode(',', (string) $this->mainConfig->getInlineFiles()));
        $map = array_flip($inlineFiles);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        return isset($map[$extension]);
    }

    private function isRangeRequest(): bool
    {
        return isset($_SERVER['HTTP_RANGE']) === true
            && isset($_SERVER['REQUEST_METHOD']) === true
            && $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    #[NoReturn]
    public function getFile(string $file, bool $isImage): void
    {
        if (file_exists($file) === false) {
            $this->wordpress->wpDie(
                TXT_UAM_FILE_NOT_FOUND_ERROR_MESSAGE,
                TXT_UAM_FILE_NOT_FOUND_ERROR_TITLE,
                ['response' => 404]
            );

            return;
        }

        $isInline = $isImage === true || $this->isInlineFile($file) === true;

        if ($this->isRangeRequest() === true) {
            $this->deliverFilePartial($file, $isInline);
        } else {
            $this->deliverFile($file, $isInline);
        }

        $this->php->callExit();
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

    public function createFileProtection(?string $dir = null, ?string $objectType = null): bool
    {
        $directory = $dir ?? $this->wordpressConfig->getUploadDirectory();

        return $directory !== null
            && $this->getCurrentFileProtectionHandler()->create($directory, $objectType);
    }

    public function deleteFileProtection(?string $dir = null): bool
    {
        $directory = $dir ?? $this->wordpressConfig->getUploadDirectory();

        return $directory !== null
            && $this->getCurrentFileProtectionHandler()->delete($directory);
    }

    private function getXSendFileTestFilePath(): string
    {
        return $this->wordpressConfig->getUploadDirectory() . DIRECTORY_SEPARATOR . self::X_SEND_FILE_TEST_FILE;
    }

    #[NoReturn]
    public function deliverXSendFileTestFile(): void
    {
        $file = $this->getXSendFileTestFilePath();
        file_put_contents($file, 'success');

        $this->php->header("X-Sendfile: $file");
        $this->php->header('Content-Type: application/octet-stream');
        $this->php->header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        $this->php->callExit();
    }

    public function removeXSendFileTestFile(): void
    {
        $file = $this->getXSendFileTestFilePath();

        if ($this->php->isFile($file) === true) {
            $this->php->unlink($file);
        }
    }
}
