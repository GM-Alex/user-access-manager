<?php

declare(strict_types=1);

namespace UserAccessManager\File\Protection;

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
        return match ($this->mainConfig->getLockedDirectoryType()) {
            'wordpress' => '\d{4}' . DIRECTORY_SEPARATOR . '\d{2}',
            'custom' => $this->mainConfig->getCustomLockedDirectories(),
            default => null
        };
    }

    protected function cleanUpFileTypes(string $fileTypes): string
    {
        $validFileTypes = [];
        $mimeTypes = $this->wordpressConfig->getMimeTypes();

        foreach (explode(',', $fileTypes) as $fileType) {
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

    public function createPasswordFile(bool $createNew = false, ?string $dir = null): void
    {
        $file = $this->getDefaultPasswordFileWithPath($dir);

        if ($file === null || (file_exists($file) === true && $createNew === false)) {
            return;
        }

        $currentUser = $this->wordpress->getCurrentUser();
        $user = $currentUser->user_login;
        $password = $currentUser->user_pass;

        if ($this->mainConfig->getFilePassType() === 'random') {
            try {
                $password = md5($this->util->getRandomPassword());
            } catch (Exception) {
                // Keep the current user's password hash when no random one can be generated.
            }
        }

        $fileHandler = fopen($file, 'w');
        fwrite($fileHandler, "$user:$password\n");
        fclose($fileHandler);
    }

    public function deleteFiles(string $directory): bool
    {
        $success = true;
        $directory = rtrim($directory, '/') . '/';

        foreach ([static::FILE_NAME, static::PASSWORD_FILE_NAME] as $fileName) {
            $file = $directory . $fileName;

            if (file_exists($file) === true) {
                $success = ($this->php->unlink($file) === true) && $success;
            }
        }

        return $success;
    }
}
