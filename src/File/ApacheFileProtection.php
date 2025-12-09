<?php

declare(strict_types=1);

namespace UserAccessManager\File;

use Exception;
use UserAccessManager\Object\ObjectHandler;

class ApacheFileProtection extends FileProtection implements FileProtectionInterface
{
    public const FILE_NAME = '.htaccess';

    private function getFileTypes(): ?string
    {
        $fileTypes = null;
        $lockedFileTypes = $this->mainConfig->getLockedFileType();

        if ($lockedFileTypes === 'selected') {
            $fileTypes = $this->cleanUpFileTypes($this->mainConfig->getLockedFiles());
            $fileTypes = ($fileTypes !== '') ? "\.($fileTypes)" : null;
        } elseif ($lockedFileTypes === 'not_selected') {
            $fileTypes = $this->cleanUpFileTypes($this->mainConfig->getNotLockedFiles());
            $fileTypes = ($fileTypes !== '') ? "^\.($fileTypes)" : null;
        }

        return $fileTypes;
    }

    protected function getDirectoryMatch(): ?string
    {
        if ($this->mainConfig->getLockedDirectoryType() === 'wordpress') {
            return '^.*' . DIRECTORY_SEPARATOR . parent::getDirectoryMatch() . '.*$';
        }

        return parent::getDirectoryMatch();
    }

    private function applyFilters(string $content): string
    {
        $fileTypes = $this->getFileTypes();

        if ($fileTypes !== null) {
            /** @noinspection */
            $content = "<FilesMatch '$fileTypes'>\n$content</FilesMatch>\n";
        }

        return $content;
    }

    private function getFileContent(string $directory): string
    {
        $areaName = 'WP-Files';
        // make .htaccess and .htpasswd
        $content = "AuthType Basic" . "\n";
        $content .= "AuthName \"$areaName\"" . "\n";
        $content .= "AuthUserFile $directory.htpasswd" . "\n";
        $content .= "require valid-user" . "\n";

        return $this->applyFilters($content);
    }

    private function getPermalinkFileContent(?string $objectType, ?bool $isSubSite = false): string
    {
        if ($objectType === null) {
            $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
        }

        $homeRoot = parse_url($this->wordpress->getSiteUrl());
        $homeRoot = (isset($homeRoot['path']) === true) ? '/' . trim($homeRoot['path'], '/\\') . '/' : '/';

        $content = "RewriteEngine On\n";
        $content .= "RewriteBase $homeRoot\n";
        $content .= "RewriteRule ^index\\.php$ - [L]\n";

        if ($isSubSite === false) {
            $content .= "RewriteCond %{REQUEST_URI} !.*\/sites\/[0-9]+\/.*\n";
        }

        $directoryMatch = $this->getDirectoryMatch();

        if ($directoryMatch !== null) {
            $content .= "RewriteCond %{REQUEST_URI} $directoryMatch\n";
        }

        $content .= "RewriteRule ^([^?]*)$ {$homeRoot}index.php?uamfiletype=$objectType&uamgetfile=$1 [QSA,L]\n";
        $content .= "RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ ";
        $content .= "{$homeRoot}index.php?uamfiletype=$objectType&uamgetfile=$1&$2 [QSA,L]\n";
        $content .= "RewriteRule ^(.*)\\?(.*)$ {$homeRoot}index.php?uamgetfile=$1&$2 [QSA,L]\n";
        $content = $this->applyFilters($content);

        return "<IfModule mod_rewrite.c>\n$content</IfModule>\n";
    }

    public function getFileNameWithPath(string $directory = null): string
    {
        return $directory . self::FILE_NAME;
    }

    public function create(string $directory, ?string $objectType = null, ?string $absolutePath = null): bool
    {
        $directory = rtrim($directory, '/') . '/';

        if ($this->wordpress->gotModRewrite() === false) {
            $content = $this->getFileContent($directory);
            $this->createPasswordFile(true, $directory);
        } else {
            $content = $this->getPermalinkFileContent(
                $objectType,
                preg_match('/.*\/sites\/[0-9]+\/$/', $directory) !== 0
            );
        }

        // save files
        $fileWithPath = $this->getFileNameWithPath($directory);

        try {
            return file_put_contents($fileWithPath, $content) !== false;
        } catch (Exception) {
            // Because file_put_contents can throw exceptions we use this try catch block
            // to return the success result instead of an exception
        }

        return false;
    }

    public function delete(string $directory): bool
    {
        return $this->deleteFiles($directory);
    }
}
