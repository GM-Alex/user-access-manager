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
namespace UserAccessManager\FileHandler;

use UserAccessManager\Config\MainConfig;
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
     * @var MainConfig
     */
    protected $config;

    /**
     * @var Util
     */
    protected $util;

    /**
     * ApacheFileProtection constructor.
     *
     * @param Php        $php
     * @param Wordpress  $wordpress
     * @param MainConfig $config
     * @param Util       $util
     */
    public function __construct(Php $php, Wordpress $wordpress, MainConfig $config, Util $util)
    {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->config = $config;
        $this->util = $util;
    }

    /**
     * Cleans up the file types.
     *
     * @param string $fileTypes The file types which should be cleaned up.
     *
     * @return string
     */
    protected function cleanUpFileTypes($fileTypes)
    {
        $validFileTypes = [];
        $fileTypes = explode(',', $fileTypes);
        $mimeTypes = $this->config->getMimeTypes();

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
     *
     * @param string $dir
     *
     * @return null|string
     */
    private function getDefaultPasswordFileWithPath($dir)
    {
        if ($dir === null) {
            $wordpressUploadDir = $this->wordpress->getUploadDir();

            if (empty($wordpressUploadDir['error']) === true) {
                $dir = $wordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
            }
        }

        return ($dir !== null) ? $dir.self::PASSWORD_FILE_NAME : null;
    }

    /**
     * Creates a htpasswd file.
     *
     * @param boolean $createNew Force to create new file.
     * @param string  $dir        The destination directory.
     */
    public function createPasswordFile($createNew = false, $dir = null)
    {
        $file = $this->getDefaultPasswordFileWithPath($dir);

        if ($file !== null && (file_exists($file) === false || $createNew)) {
            $currentUser = $this->wordpress->getCurrentUser();

            $user = $currentUser->user_login;
            $password = $currentUser->user_pass;

            if ($this->config->getFilePassType() === 'random') {
                try {
                    $randomPassword = $this->util->getRandomPassword();
                    $password = md5($randomPassword);
                } catch (\Exception $exception) {
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
}
