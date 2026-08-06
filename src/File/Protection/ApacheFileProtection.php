<?php

declare(strict_types=1);

namespace UserAccessManager\File\Protection;

use Exception;
use UserAccessManager\Object\ObjectHandler;

class ApacheFileProtection extends FileProtection implements FileProtectionInterface
{
    public const FILE_NAME = '.htaccess';

    private function getFileTypes(): ?string
    {
        $lockedFileTypes = $this->mainConfig->getLockedFileType();

        if ($lockedFileTypes === 'selected') {
            $fileTypes = $this->cleanUpFileTypes($this->mainConfig->getLockedFiles());

            return ($fileTypes !== '') ? "\.($fileTypes)" : null;
        }

        if ($lockedFileTypes === 'not_selected') {
            $fileTypes = $this->cleanUpFileTypes($this->mainConfig->getNotLockedFiles());

            return ($fileTypes !== '') ? "^\.($fileTypes)" : null;
        }

        return null;
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

        if ($fileTypes === null) {
            return $content;
        }

        return "<FilesMatch '$fileTypes'>\n$content</FilesMatch>\n";
    }

    private function getFileContent(string $directory): string
    {
        return $this->applyFilters(
            "AuthType Basic\n"
            . "AuthName \"WP-Files\"\n"
            . "AuthUserFile {$directory}.htpasswd\n"
            . "require valid-user\n"
        );
    }

    private function getPermalinkFileContent(?string $objectType, ?bool $isSubSite): string
    {
        if ($objectType === null) {
            $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
        }

        $siteUrlParts = parse_url($this->wordpress->getSiteUrl());
        $homeRoot = (isset($siteUrlParts['path']) === true) ? '/' . trim($siteUrlParts['path'], '/\\') . '/' : '/';

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

    public function getFileNameWithPath(?string $directory = null): string
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

        try {
            return file_put_contents($this->getFileNameWithPath($directory), $content) !== false;
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
