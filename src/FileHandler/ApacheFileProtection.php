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
 * @version   SVN: $id$
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
     * @param string $dir
     * @param string $objectType
     *
     * @return bool
     */
    public function create($dir, $objectType = null)
    {
        $dir = rtrim($dir, '/').'/';
        $content = '';
        $areaName = 'WP-Files';
        $fileTypes = null;
        $lockFileTypes = $this->config->getLockFileTypes();

        if ($lockFileTypes === 'selected') {
            $fileTypes = $this->cleanUpFileTypes($this->config->getLockedFileTypes());
            $fileTypes = ($fileTypes !== '') ? "\.({$fileTypes})" : null;
        } elseif ($lockFileTypes === 'not_selected') {
            $fileTypes = $this->cleanUpFileTypes($this->config->getNotLockedFileTypes());
            $fileTypes = ($fileTypes !== '') ? "^\.({$fileTypes})" : null;
        }

        if ($this->config->isPermalinksActive() === false) {
            // make .htaccess and .htpasswd
            $content .= "AuthType Basic"."\n";
            $content .= "AuthName \"{$areaName}\""."\n";
            $content .= "AuthUserFile {$dir}.htpasswd"."\n";
            $content .= "require valid-user"."\n";

            if ($fileTypes !== null) {
                /** @noinspection */
                $content = "<FilesMatch '{$fileTypes}'>\n{$content}</FilesMatch>\n";
            }

            $this->createPasswordFile(true, $dir);
        } else {
            if ($objectType === null) {
                $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
            }

            $homeRoot = parse_url($this->wordpress->getHomeUrl());
            $homeRoot = (isset($homeRoot['path']) === true) ? '/'.trim($homeRoot['path'], '/\\').'/' : '/';

            $content = "RewriteEngine On\n";
            $content .= "RewriteBase {$homeRoot}\n";
            $content .= "RewriteRule ^index\\.php$ - [L]\n";
            $content .= "RewriteRule ^([^?]*)$ {$homeRoot}index.php?uamfiletype={$objectType}&uamgetfile=$1 [QSA,L]\n";
            $content .= "RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ ";
            $content .= "{$homeRoot}index.php?uamfiletype={$objectType}&uamgetfile=$1&$2 [QSA,L]\n";
            $content .= "RewriteRule ^(.*)\\?(.*)$ {$homeRoot}index.php?uamgetfile=$1&$2 [QSA,L]\n";

            if ($fileTypes !== null) {
                /** @noinspection */
                $content = "<FilesMatch '{$fileTypes}'>\n{$content}</FilesMatch>\n";
            }

            $content = "<IfModule mod_rewrite.c>\n$content</IfModule>\n";
        }

        // save files
        $fileWithPath = $dir.self::FILE_NAME;

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
     * Deletes the htaccess files.
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
