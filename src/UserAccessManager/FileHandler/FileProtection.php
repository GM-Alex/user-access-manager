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
 * @version   SVN: $Id$
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
    protected $Php;

    /**
     * @var Wordpress
     */
    protected $Wordpress;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var Util
     */
    protected $Util;

    /**
     * ApacheFileProtection constructor.
     *
     * @param Php         $Php
     * @param Wordpress   $Wordpress
     * @param Config      $Config
     * @param Util        $Util
     */
    public function __construct(Php $Php, Wordpress $Wordpress, Config $Config, Util $Util)
    {
        $this->Php = $Php;
        $this->Wordpress = $Wordpress;
        $this->Config = $Config;
        $this->Util = $Util;
    }

    /**
     * Cleans up the file types.
     *
     * @param string $sFileTypes The file types which should be cleaned up.
     *
     * @return string
     */
    protected function cleanUpFileTypes($sFileTypes)
    {
        $aValidFileTypes = [];
        $aFileTypes = explode(',', $sFileTypes);
        $aMimeTypes = $this->Config->getMimeTypes();

        foreach ($aFileTypes as $sFileType) {
            $sCleanFileType = trim($sFileType);

            if (isset($aMimeTypes[$sCleanFileType])) {
                $aValidFileTypes[$sCleanFileType] = $sCleanFileType;
            }
        }

        return implode('|', $aValidFileTypes);
    }

    /**
     * Creates a htpasswd file.
     *
     * @param boolean $blCreateNew Force to create new file.
     * @param string  $sDir        The destination directory.
     */
    public function createPasswordFile($blCreateNew = false, $sDir = null)
    {
        // get url
        if ($sDir === null) {
            $aWordpressUploadDir = $this->Wordpress->getUploadDir();

            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
            }
        }

        $sFile = $sDir.self::PASSWORD_FILE_NAME;

        if ($sDir !== null
            && (file_exists($sFile) === false || $blCreateNew)
        ) {
            $CurrentUser = $this->Wordpress->getCurrentUser();

            if ($this->Config->getFilePassType() === 'random') {
                try {
                    $sRandomPassword = $this->Util->getRandomPassword();
                    $sPassword = md5($sRandomPassword);
                } catch (\Exception $Exception) {
                    $sPassword = $CurrentUser->user_pass;
                }
            } else {
                $sPassword = $CurrentUser->user_pass;
            }

            $sUser = $CurrentUser->user_login;

            // make .htpasswd
            $sContent = "{$sUser}:{$sPassword}\n";

            // save file
            $FileHandler = fopen($sFile, 'w');
            fwrite($FileHandler, $sContent);
            fclose($FileHandler);
        }
    }
}
