<?php

declare(strict_types=1);

namespace UserAccessManager\File\Protection;

use Exception;
use UserAccessManager\Object\ObjectHandler;

class NginxFileProtection extends FileProtection implements FileProtectionInterface
{
    public const FILE_NAME = 'uam.conf';

    protected function getLocation(string $directory): string
    {
        if ($this->mainConfig->getLockedDirectoryType() === 'wordpress') {
            return "^$directory" . $this->getDirectoryMatch();
        }

        return $this->getDirectoryMatch() ?? $directory;
    }

    private function getFileContent(string $absolutePath, string $directory, ?string $objectType): string
    {
        if ($objectType === null) {
            $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
        }

        $location = $this->getLocation(str_replace($absolutePath, '/', $directory));

        return "location ~ \"$location\" {\n"
            . "rewrite ^([^?]*)$ /index.php?uamfiletype=$objectType&uamgetfile=$1 last;\n"
            . "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ "
            . "/index.php?uamfiletype=$objectType&uamgetfile=$1&$2 last;\n"
            . "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n"
            . "}\n";
    }

    public function getFileNameWithPath(?string $directory = null): string
    {
        return ABSPATH . self::FILE_NAME;
    }

    public function create(string $directory, ?string $objectType = null, ?string $absolutePath = ABSPATH): bool
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $content = $this->getFileContent($absolutePath, $directory, $objectType);

        try {
            return file_put_contents($absolutePath . self::FILE_NAME, $content) !== false;
        } catch (Exception) {
            // file_put_contents may throw, but callers expect a boolean success result.
        }

        return false;
    }

    public function delete(string $directory): bool
    {
        return $this->deleteFiles($directory);
    }
}
