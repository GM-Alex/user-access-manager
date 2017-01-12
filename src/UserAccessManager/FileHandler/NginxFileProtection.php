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

use UserAccessManager\Service\UserAccessManager;

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
     * @param string   $sObjectType
     *
     * @return bool
     */
    public function create($sDir, $sObjectType = null)
    {
        $sAreaName = 'WP-Files';

        if ($this->_oConfig->isPermalinksActive() === false) {
            $sFileTypes = null;

            if ($this->_oConfig->getLockedFileTypes() == 'selected') {
                $sFileTypes = $this->_cleanUpFileTypes($this->_oConfig->getLockedFileTypes());
                $sFileTypes = "\.({$sFileTypes})";
            }

            $sFile = "location ".str_replace(ABSPATH, '/', $sDir)." {\n";

            if ($sFileTypes !== null) {
                $sFile .= "location ~ {$sFileTypes} {\n";
            }

            $sFile .= "auth_basic \"{$sAreaName}\";\n";
            $sFile .= "auth_basic_user_file {$sDir}.htpasswd;\n";
            $sFile .= "}\n";

            if ($sFileTypes !== null) {
                $sFile .= "}\n";
            }

            $this->createPasswordFile(true);
        } else {
            if ($sObjectType === null) {
                $sObjectType = UserAccessManager::ATTACHMENT_OBJECT_TYPE;
            }

            $sFile = "location ".str_replace(ABSPATH, '/', $sDir)." {\n";
            $sFile .= "rewrite ^(.*)$ /index.php?uamfiletype={$sObjectType}&uamgetfile=$1 last;\n";
            $sFile .= "}\n";
        }

        // save files
        $sFileWithPath = ABSPATH.self::FILE_NAME;

        $oFileHandler = fopen($sFileWithPath, 'w');
        fwrite($oFileHandler, $sFile);
        fclose($oFileHandler);

        return true;
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
        $sFileName = $sDir.self::FILE_NAME;

        if (file_exists($sFileName)) {
            unlink($sFileName);
        }

        $sPasswordFile = $sDir.self::PASSWORD_FILE_NAME;

        if (file_exists($sPasswordFile)) {
            unlink($sPasswordFile);
        }

        return true;
    }
}