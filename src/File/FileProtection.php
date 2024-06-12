<?php

declare(strict_types=1);

namespace UserAccessManager\File;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

abstract class FileProtection
{
    public const FILE_NAME = null;
    public const PASSWORD_FILE_NAME = '.htpasswd';

    public function __construct(
        protected Php $php,
        protected Wordpress $wordpress,
        protected WordpressConfig $wordpressConfig,
        protected MainConfig $mainConfig,
        protected Util $util
    ) {
    }

    protected function getDirectoryMatch(): ?string
    {
        $directoryMatch = null;
        $lockedDirectoryType = $this->mainConfig->getLockedDirectoryType();

        if ($lockedDirectoryType === 'wordpress') {
            $directoryMatch = '[0-9]{4}' . DIRECTORY_SEPARATOR . '[0-9]{2}';
        } elseif ($lockedDirectoryType === 'custom') {
            $directoryMatch = $this->mainConfig->getCustomLockedDirectories();
        }

        return $directoryMatch;
    }

    protected function cleanUpFileTypes(string $fileTypes): string
    {
        $validFileTypes = [];
        $fileTypes = explode(',', $fileTypes);
        $mimeTypes = $this->wordpressConfig->getMimeTypes();

        foreach ($fileTypes as $fileType) {
            $cleanFileType = trim($fileType);

            if (isset($mimeTypes[$cleanFileType]) === true) {
                $validFileTypes[$cleanFileType] = $cleanFileType;
            }
        }

        return implode('|', $validFileTypes);
    }

    private function getDefaultPasswordFileWithPath(?string $dir): ?string
    {
        if ($dir === null) {
            $wordpressUploadDir = $this->wordpress->getUploadDir();

            if (empty($wordpressUploadDir['error']) === true) {
                $dir = $wordpressUploadDir['basedir'] . DIRECTORY_SEPARATOR;
            }
        }

        return ($dir !== null) ? $dir . static::PASSWORD_FILE_NAME : null;
    }

    public function createPasswordFile(bool $createNew = false, string $dir = null): void
    {
        $file = $this->getDefaultPasswordFileWithPath($dir);

        if ($file !== null && (file_exists($file) === false || $createNew)) {
            $currentUser = $this->wordpress->getCurrentUser();

            $user = $currentUser->user_login;
            $password = $currentUser->user_pass;

            if ($this->mainConfig->getFilePassType() === 'random') {
                try {
                    $randomPassword = $this->util->getRandomPassword();
                    $password = md5($randomPassword);
                } catch (Exception) {
                    // Do nothing
                }
            }

            // make .htpasswd
            $content = "$user:$password\n";

            // save file
            $fileHandler = fopen($file, 'w');
            fwrite($fileHandler, $content);
            fclose($fileHandler);
        }
    }

    public function deleteFiles(string $directory): bool
    {
        $success = true;
        $directory = rtrim($directory, '/') . '/';
        $fileName = $directory . static::FILE_NAME;

        if (file_exists($fileName) === true) {
            $success = ($this->php->unlink($fileName) === true);
        }

        $passwordFile = $directory . static::PASSWORD_FILE_NAME;

        if (file_exists($passwordFile) === true) {
            $success = ($this->php->unlink($passwordFile) === true) && $success;
        }

        return $success;
    }
}
