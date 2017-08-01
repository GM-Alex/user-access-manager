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
 * @version   SVN: $id$
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
     * @param string $dir
     * @param string $objectType
     * @param string $absPath
     *
     * @return bool
     */
    public function create($dir, $objectType = null, $absPath = ABSPATH)
    {
        $dir = rtrim($dir, '/').'/';
        $absPath = rtrim($absPath, '/').'/';
        $areaName = 'WP-Files';

        if ($this->config->isPermalinksActive() === false) {
            $fileTypes = null;

            if ($this->config->getLockFileTypes() === 'selected') {
                $fileTypes = $this->cleanUpFileTypes($this->config->getLockedFileTypes());
                $fileTypes = "\\.({$fileTypes})";
            }

            $content = "location ".str_replace($absPath, '/', $dir)." {\n";

            if ($fileTypes !== null) {
                $content .= "location ~ {$fileTypes} {\n";
            }

            $content .= "auth_basic \"{$areaName}\";\n";
            $content .= "auth_basic_user_file {$dir}.htpasswd;\n";
            $content .= "}\n";

            if ($fileTypes !== null) {
                $content .= "}\n";
            }

            $this->createPasswordFile(true, $dir);
        } else {
            if ($objectType === null) {
                $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
            }

            $content = "location ".str_replace($absPath, '/', $dir)." {\n";
            $content .= "rewrite ^([^?]*)$ /index.php?uamfiletype={$objectType}&uamgetfile=$1 last;\n";
            $content .= "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ ";
            $content .= "/index.php?uamfiletype={$objectType}&uamgetfile=$1&$2 last;\n";
            $content .= "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n";
            $content .= "}\n";
        }

        // save files
        $fileWithPath = $absPath.self::FILE_NAME;

        try {
            file_put_contents($fileWithPath, $content);
            return true;
        } catch (\Exception $exception) {
            // Because file_put_contents can throw exceptions we use this try catch block
            // to return the success result instead of an exception
        }

        return false;
    }

    /**
     * Deletes the conf file.
     *
     * @param string $dir
     *
     * @return bool
     */
    public function delete($dir)
    {
        $success = true;
        $dir = rtrim($dir, '/').'/';
        $fileName = $dir.self::FILE_NAME;

        if (file_exists($fileName) === true) {
            $success = ($this->php->unlink($fileName) === true) && $success;
        }

        $passwordFile = $dir.self::PASSWORD_FILE_NAME;

        if (file_exists($passwordFile) === true) {
            $success = ($this->php->unlink($passwordFile) === true) && $success;
        }

        return $success;
    }
}
