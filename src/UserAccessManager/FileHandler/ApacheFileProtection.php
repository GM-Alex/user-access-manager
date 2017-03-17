<?php
/**
 * ApacheFileProtection.php
 *
 * The ApacheFileProtection class file.
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

use UserAccessManager\ObjectHandler\ObjectHandler;

/**
 * Class ApacheFileProtection
 *
 * @package UserAccessManager\FileHandler
 */
class ApacheFileProtection extends FileProtection implements FileProtectionInterface
{
    const FILE_NAME = '.htaccess';

    /**
     * Generates the htaccess file.
     *
     * @param string $sDir
     * @param string $sObjectType
     *
     * @return bool
     */
    public function create($sDir, $sObjectType = null)
    {
        $sDir = rtrim($sDir, '/').'/';
        $sContent = '';
        $sAreaName = 'WP-Files';

        if ($this->_oConfig->isPermalinksActive() === false) {
            $sFileTypes = null;
            $sLockFileTypes = $this->_oConfig->getLockFileTypes();

            if ($sLockFileTypes === 'selected') {
                $sFileTypes = $this->_cleanUpFileTypes($this->_oConfig->getLockedFileTypes());
                $sFileTypes = "\.({$sFileTypes})";
            } elseif ($sLockFileTypes === 'not_selected') {
                $sFileTypes = $this->_cleanUpFileTypes($this->_oConfig->getLockedFileTypes());
                $sFileTypes = "^\.({$sFileTypes})";
            }

            // make .htaccess and .htpasswd
            $sContent .= "AuthType Basic"."\n";
            $sContent .= "AuthName \"{$sAreaName}\""."\n";
            $sContent .= "AuthUserFile {$sDir}.htpasswd"."\n";
            $sContent .= "require valid-user"."\n";

            if ($sFileTypes !== null) {
                /** @noinspection */
                $sContent = "<FilesMatch '{$sFileTypes}'>\n{$sContent}</FilesMatch>\n";
            }

            $this->createPasswordFile(true, $sDir);
        } else {
            if ($sObjectType === null) {
                $sObjectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
            }

            $aHomeRoot = parse_url($this->_oWrapper->getHomeUrl());
            $sHomeRoot = (isset($aHomeRoot['path'])) ? trim($aHomeRoot['path'], '/\\').'/' : '/';

            $sContent = "<IfModule mod_rewrite.c>\n";
            $sContent .= "RewriteEngine On\n";
            $sContent .= "RewriteBase {$sHomeRoot}\n";
            $sContent .= "RewriteRule ^index\.php$ - [L]\n";
            $sContent .= "RewriteRule (.*) ";
            $sContent .= "{$sHomeRoot}index.php?uamfiletype={$sObjectType}&uamgetfile=$1 [L]\n";
            $sContent .= "</IfModule>\n";
        }

        // save files
        $sFileWithPath = $sDir.self::FILE_NAME;
        return (file_put_contents($sFileWithPath, $sContent) !== false);
    }

    /**
     * Deletes the htaccess files.
     *
     * @param string $sDir
     *
     * @return bool
     */
    public function delete($sDir)
    {
        $blSuccess = true;
        $sDir = rtrim($sDir, '/').'/';
        $sFileName = $sDir.self::FILE_NAME;

        if (file_exists($sFileName)) {
            $blSuccess = unlink($sFileName) && $blSuccess;
        }

        $sPasswordFile = $sDir.self::PASSWORD_FILE_NAME;

        if (file_exists($sPasswordFile)) {
            $blSuccess = unlink($sPasswordFile) && $blSuccess;
        }

        return $blSuccess;
    }
}