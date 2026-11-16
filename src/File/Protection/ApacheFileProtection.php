<?php

declare(strict_types=1);

namespace UserAccessManager\File\Protection;

use Exception;
use UserAccessManager\Object\ObjectHandler;

class ApacheFileProtection extends FileProtection implements FileProtectionInterface
{
    public const FILE_NAME = '.htaccess';
    private const MARKER_START = '# BEGIN User Access Manager';
    private const MARKER_END = '# END User Access Manager';
    private const AUTH_NAME_LINE = 'AuthName "WP-Files"';

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
            . self::AUTH_NAME_LINE . "\n"
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

    // Versions before the markers wrote the whole file, recognisable by these two rules.
    private function isOwnLegacyContent(string $content): bool
    {
        return str_contains($content, 'uamgetfile=')
            || str_contains($content, self::AUTH_NAME_LINE);
    }

    private function getMarkedBlock(string $content): string
    {
        return self::MARKER_START . "\n" . $content . self::MARKER_END . "\n";
    }

    // Everything but the own blocks, so rules of other plugins survive a rewrite.
    private function getForeignContent(string $file): string
    {
        if (is_file($file) === false) {
            return '';
        }

        $content = (string) file_get_contents($file);
        $start = strpos($content, self::MARKER_START);

        if ($start === false) {
            return ($this->isOwnLegacyContent($content) === true) ? '' : $content;
        }

        while ($start !== false) {
            $end = strpos($content, self::MARKER_END, $start);
            $behind = ($end === false) ? '' : ltrim(substr($content, $end + strlen(self::MARKER_END)), "\n");
            $content = substr($content, 0, $start) . $behind;
            $start = strpos($content, self::MARKER_START);
        }

        return $content;
    }

    public function create(string $directory, ?string $objectType = null, ?string $absolutePath = null): bool
    {
        $directory = rtrim($directory, '/') . '/';
        $file = $this->getFileNameWithPath($directory);

        if ($this->wordpress->gotModRewrite() === false) {
            // A foreign "Require all granted" would join the own one in an implicit
            // RequireAny and grant access, so this mode keeps the file to itself.
            $this->createPasswordFile(true, $directory);
            $content = $this->getMarkedBlock($this->getFileContent($directory));
        } else {
            // The own rules end in [L] and have to be reached before a foreign rule
            // delivers the file, while RewriteEngine is a per directory switch whose
            // last occurrence wins, so a foreign "Off" is answered by the closing block.
            $content = $this->getMarkedBlock($this->getPermalinkFileContent(
                $objectType,
                preg_match('/.*\/sites\/[0-9]+\/$/', $directory) !== 0
            ))
                . $this->getForeignContent($file)
                . $this->getMarkedBlock("<IfModule mod_rewrite.c>\nRewriteEngine On\n</IfModule>\n");
        }

        try {
            return file_put_contents($file, $content) !== false;
        } catch (Exception) {
            // file_put_contents may throw, but callers expect a boolean success result.
        }

        return false;
    }

    public function delete(string $directory): bool
    {
        $directory = rtrim($directory, '/') . '/';
        $file = $this->getFileNameWithPath($directory);
        $foreignContent = $this->getForeignContent($file);

        if (trim($foreignContent) === '') {
            return $this->deleteFiles($directory);
        }

        $success = $this->php->filePutContents($file, $foreignContent) !== false;
        $passwordFile = $directory . self::PASSWORD_FILE_NAME;

        if (is_file($passwordFile) === true) {
            $success = ($this->php->unlink($passwordFile) === true) && $success;
        }

        return $success;
    }
}
