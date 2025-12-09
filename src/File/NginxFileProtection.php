<?php

declare(strict_types=1);

namespace UserAccessManager\File;

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

        $directoryMatch = $this->getDirectoryMatch();
        return $directoryMatch === null ? $directory : $directoryMatch;
    }

    private function getFileContent(string $absolutePath, string $directory, ?string $objectType): string
    {
        if ($objectType === null) {
            $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
        }

        $location = $this->getLocation(str_replace($absolutePath, '/', $directory));

        $content = "location $location {\n";
        $content .= "rewrite ^([^?]*)$ /index.php?uamfiletype=$objectType&uamgetfile=$1 last;\n";
        $content .= "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ ";
        $content .= "/index.php?uamfiletype=$objectType&uamgetfile=$1&$2 last;\n";
        $content .= "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n";
        $content .= "}\n";

        return $content;
    }

    public function getFileNameWithPath(string $directory = null): string
    {
        return ABSPATH . self::FILE_NAME;
    }

    public function create(string $directory, ?string $objectType = null, ?string $absolutePath = ABSPATH): bool
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $content = $this->getFileContent($absolutePath, $directory, $objectType);

        // save files
        $fileWithPath = $absolutePath . self::FILE_NAME;

        try {
            return file_put_contents($fileWithPath, $content) !== false;
        } catch (Exception) {
            // Because file_put_contents can throw exceptions, we use this try catch block
            // to return the success result instead of an exception
        }

        return false;
    }

    public function delete(string $directory): bool
    {
        return $this->deleteFiles($directory);
    }
}
