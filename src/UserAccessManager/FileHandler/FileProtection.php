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
     * @var Wordpress
     */
    protected $_oWordpress;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * ApacheFileProtection constructor.
     *
     * @param Wordpress   $oWordpress
     * @param Config      $oConfig
     * @param Util        $oUtil
     */
    public function __construct(Wordpress $oWordpress, Config $oConfig, Util $oUtil)
    {
        $this->_oWordpress = $oWordpress;
        $this->_oConfig = $oConfig;
        $this->_oUtil = $oUtil;
    }

    /**
     * Cleans up the file types.
     *
     * @param string $sFileTypes The file types which should be cleaned up.
     *
     * @return string
     */
    protected function _cleanUpFileTypes($sFileTypes)
    {
        $aValidFileTypes = array();
        $aFileTypes = explode(',', $sFileTypes);
        $aMimeTypes = $this->_oConfig->getMimeTypes();

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
            $aWordpressUploadDir = $this->_oWordpress->getUploadDir();

            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
            }
        }

        $sFile = $sDir.self::PASSWORD_FILE_NAME;

        if ($sDir !== null
            && (!file_exists($sFile) || $blCreateNew)
        ) {
            $oCurrentUser = $this->_oWordpress->getCurrentUser();

            if ($this->_oConfig->getFilePassType() === 'random') {
                $sPassword = md5($this->_oUtil->getRandomPassword());
            } else {
                $sPassword = $oCurrentUser->user_pass;
            }

            $sUser = $oCurrentUser->user_login;

            // make .htpasswd
            $sContent = "{$sUser}:{$sPassword}\n";

            // save file
            $oFileHandler = fopen($sFile, 'w');
            fwrite($oFileHandler, $sContent);
            fclose($oFileHandler);
        }
    }
}