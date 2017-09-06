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
namespace UserAccessManager\File;

use UserAccessManager\Object\ObjectHandler;

/**
 * Class NginxFileProtection
 *
 * @package UserAccessManager\FileHandler
 */
class NginxFileProtection extends FileProtection implements FileProtectionInterface
{
    const FILE_NAME = 'uam.conf';

    /**
     * Creates the file content if permalinks are active.
     *
     * @param string $absolutePath
     * @param string $directory
     *
     * @return string
     */
    private function getPermalinkFileContent($absolutePath, $directory)
    {
        $areaName = 'WP-Files';
        $fileTypes = null;

        if ($this->mainConfig->getLockFileTypes() === 'selected') {
            $fileTypes = $this->cleanUpFileTypes($this->mainConfig->getLockedFileTypes());
            $fileTypes = "\\.({$fileTypes})";
        }

        $content = "location ".str_replace($absolutePath, '/', $directory)." {\n";

        if ($fileTypes !== null) {
            $content .= "location ~ {$fileTypes} {\n";
        }

        $content .= "auth_basic \"{$areaName}\";\n";
        $content .= "auth_basic_user_file {$directory}.htpasswd;\n";
        $content .= "}\n";

        if ($fileTypes !== null) {
            $content .= "}\n";
        }

        return $content;
    }

    /**
     * Creates the file content if no permalinks are active.
     *
     * @param string $absolutePath
     * @param string $directory
     * @param string $objectType
     *
     * @return string
     */
    private function getFileContent($absolutePath, $directory, $objectType)
    {
        if ($objectType === null) {
            $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
        }

        $content = "location ".str_replace($absolutePath, '/', $directory)." {\n";
        $content .= "rewrite ^([^?]*)$ /index.php?uamfiletype={$objectType}&uamgetfile=$1 last;\n";
        $content .= "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ ";
        $content .= "/index.php?uamfiletype={$objectType}&uamgetfile=$1&$2 last;\n";
        $content .= "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n";
        $content .= "}\n";

        return $content;
    }

    /**
     * Generates the conf file.
     *
     * @param string $directory
     * @param string $objectType
     * @param string $absolutePath
     *
     * @return bool
     */
    public function create($directory, $objectType = null, $absolutePath = ABSPATH)
    {
        $directory = rtrim($directory, '/').'/';
        $absolutePath = rtrim($absolutePath, '/').'/';

        if ($this->wordpressConfig->isPermalinksActive() === false) {
            $content = $this->getPermalinkFileContent($absolutePath, $directory);
            $this->createPasswordFile(true, $directory);
        } else {
            $content = $this->getFileContent($absolutePath, $directory, $objectType);
        }

        // save files
        $fileWithPath = $absolutePath.self::FILE_NAME;

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
     * @param string $directory
     *
     * @return bool
     */
    public function delete($directory)
    {
        return $this->deleteFiles($directory);
    }
}
