<?php
/**
 * FileProtection.php
 *
 * The FileProtection class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\File;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FileProtection
 *
 * @package UserAccessManager\FileHandler
 */
abstract class FileProtection
{
    const FILE_NAME = null;
    const PASSWORD_FILE_NAME = '.htpasswd';

    /**
     * @var Php
     */
    protected $php;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var WordpressConfig
     */
    protected $wordpressConfig;

    /**
     * @var MainConfig
     */
    protected $mainConfig;

    /**
     * @var Util
     */
    protected $util;

    /**
     * FileProtection constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig $mainConfig
     * @param Util $util
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->wordpressConfig = $wordpressConfig;
        $this->mainConfig = $mainConfig;
        $this->util = $util;
    }

    /**
     * Returns the directory match.
     * @return null|string
     */
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

    /**
     * Cleans up the file types.
     * @param string $fileTypes The file types which should be cleaned up.
     * @return string
     */
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

    /**
     * Returns the password file name with the path.
     * @param string|null $dir
     * @return null|string
     */
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

    /**
     * Creates a htpasswd file.
     * @param boolean $createNew Force to create new file.
     * @param string $dir The destination directory.
     */
    public function createPasswordFile($createNew = false, $dir = null)
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
                } catch (Exception $exception) {
                    // Do nothing
                }
            }

            // make .htpasswd
            $content = "{$user}:{$password}\n";

            // save file
            $fileHandler = fopen($file, 'w');
            fwrite($fileHandler, $content);
            fclose($fileHandler);
        }
    }

    /**
     * Deletes the htaccess files.
     * @param string $directory
     * @return bool
     */
    public function deleteFiles(string $directory): bool
    {
        $success = true;
        $directory = rtrim($directory, '/') . '/';
        $fileName = $directory . static::FILE_NAME;

        if (file_exists($fileName) === true) {
            $success = ($this->php->unlink($fileName) === true) && $success;
        }

        $passwordFile = $directory . static::PASSWORD_FILE_NAME;

        if (file_exists($passwordFile) === true) {
            $success = ($this->php->unlink($passwordFile) === true) && $success;
        }

        return $success;
    }
}
