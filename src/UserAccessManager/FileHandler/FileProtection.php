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

use UserAccessManager\Config\Config;
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
     * @var Config
     */
    protected $config;

    /**
     * @var Util
     */
    protected $util;

    /**
     * ApacheFileProtection constructor.
     *
     * @param Php         $php
     * @param Wordpress   $wordpress
     * @param Config      $config
     * @param Util        $util
     */
    public function __construct(Php $php, Wordpress $wordpress, Config $config, Util $util)
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
     * Creates a htpasswd file.
     *
     * @param boolean $createNew Force to create new file.
     * @param string  $dir        The destination directory.
     */
    public function createPasswordFile($createNew = false, $dir = null)
    {
        // get url
        if ($dir === null) {
            $wordpressUploadDir = $this->wordpress->getUploadDir();

            if (empty($wordpressUploadDir['error'])) {
                $dir = $wordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
            }
        }

        $file = $dir.self::PASSWORD_FILE_NAME;

        if ($dir !== null
            && (file_exists($file) === false || $createNew)
        ) {
            $currentUser = $this->wordpress->getCurrentUser();

            if ($this->config->getFilePassType() === 'random') {
                try {
                    $randomPassword = $this->util->getRandomPassword();
                    $password = md5($randomPassword);
                } catch (\Exception $exception) {
                    $password = $currentUser->user_pass;
                }
            } else {
                $password = $currentUser->user_pass;
            }

            $user = $currentUser->user_login;

            // make .htpasswd
            $content = "{$user}:{$password}\n";

            // save file
            $fileHandler = fopen($file, 'w');
            fwrite($fileHandler, $content);
            fclose($fileHandler);
        }
    }
}
