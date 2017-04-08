<?php
/**
 * NginxFileProtection.php
 *
 * The NginxFileProtection class file.
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
 * Class NginxFileProtection
 *
 * @package UserAccessManager\FileHandler
 */
class NginxFileProtection extends FileProtection implements FileProtectionInterface
{
    const FILE_NAME = 'uam.conf';

    /**
     * Generates the conf file.
     *
     * @param string $sDir
     * @param string $sObjectType
     * @param string $sAbsPath
     *
     * @return bool
     */
    public function create($sDir, $sObjectType = null, $sAbsPath = ABSPATH)
    {
        $sDir = rtrim($sDir, '/').'/';
        $sAbsPath = rtrim($sAbsPath, '/').'/';
        $sAreaName = 'WP-Files';

        if ($this->Config->isPermalinksActive() === false) {
            $sFileTypes = null;

            if ($this->Config->getLockFileTypes() === 'selected') {
                $sFileTypes = $this->cleanUpFileTypes($this->Config->getLockedFileTypes());
                $sFileTypes = "\.({$sFileTypes})";
            }

            $sContent = "location ".str_replace($sAbsPath, '/', $sDir)." {\n";

            if ($sFileTypes !== null) {
                $sContent .= "location ~ {$sFileTypes} {\n";
            }

            $sContent .= "auth_basic \"{$sAreaName}\";\n";
            $sContent .= "auth_basic_user_file {$sDir}.htpasswd;\n";
            $sContent .= "}\n";

            if ($sFileTypes !== null) {
                $sContent .= "}\n";
            }

            $this->createPasswordFile(true, $sDir);
        } else {
            if ($sObjectType === null) {
                $sObjectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
            }

            $sContent = "location ".str_replace($sAbsPath, '/', $sDir)." {\n";
            $sContent .= "rewrite ^(.*)$ /index.php?uamfiletype={$sObjectType}&uamgetfile=$1 last;\n";
            $sContent .= "}\n";
        }

        // save files
        $sFileWithPath = $sAbsPath.self::FILE_NAME;

        try {
            file_put_contents($sFileWithPath, $sContent);
            return true;
        } catch (\Exception $Exception) {
        }

        return false;
    }

    /**
     * Deletes the conf file.
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

        if (file_exists($sFileName) === true) {
            $blSuccess = ($this->Php->unlink($sFileName) === true) && $blSuccess;
        }

        $sPasswordFile = $sDir.self::PASSWORD_FILE_NAME;

        if (file_exists($sPasswordFile) === true) {
            $blSuccess = ($this->Php->unlink($sPasswordFile) === true) && $blSuccess;
        }

        return $blSuccess;
    }
}
