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
namespace UserAccessManager\File;

use UserAccessManager\Object\ObjectHandler;

/**
 * Class ApacheFileProtection
 *
 * @package UserAccessManager\FileHandler
 */
class ApacheFileProtection extends FileProtection implements FileProtectionInterface
{
    const FILE_NAME = '.htaccess';

    /**
     * Returns the file types.
     *
     * @return null|string
     */
    private function getFileTypes()
    {
        $fileTypes = null;
        $lockedFileTypes = $this->mainConfig->getLockedFileType();

        if ($lockedFileTypes === 'selected') {
            $fileTypes = $this->cleanUpFileTypes($this->mainConfig->getLockedFiles());
            $fileTypes = ($fileTypes !== '') ? "\.({$fileTypes})" : null;
        } elseif ($lockedFileTypes === 'not_selected') {
            $fileTypes = $this->cleanUpFileTypes($this->mainConfig->getNotLockedFiles());
            $fileTypes = ($fileTypes !== '') ? "^\.({$fileTypes})" : null;
        }

        return $fileTypes;
    }

    /**
     * Returns the directory match.
     *
     * @return null|string
     */
    protected function getDirectoryMatch()
    {
        if ($this->mainConfig->getLockedDirectoryType() === 'wordpress') {
            return '^.*'.DIRECTORY_SEPARATOR.parent::getDirectoryMatch().'.*$';
        }

        return parent::getDirectoryMatch();
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function applyFilters($content)
    {
        $fileTypes = $this->getFileTypes();

        if ($fileTypes !== null) {
            /** @noinspection */
            $content = "<FilesMatch '{$fileTypes}'>\n{$content}</FilesMatch>\n";
        }

        return $content;
    }

    /**
     * Creates the file content if no permalinks are active.
     *
     * @param string $directory
     *
     * @return string
     */
    private function getFileContent($directory)
    {
        $areaName = 'WP-Files';
        // make .htaccess and .htpasswd
        $content = "AuthType Basic"."\n";
        $content .= "AuthName \"{$areaName}\""."\n";
        $content .= "AuthUserFile {$directory}.htpasswd"."\n";
        $content .= "require valid-user"."\n";

        return $this->applyFilters($content);
    }

    /**
     * Creates the file content if permalinks are active.
     *
     * @param string $objectType
     * @param bool   $isSubSite
     *
     * @return string
     */
    private function getPermalinkFileContent($objectType, $isSubSite = false)
    {
        if ($objectType === null) {
            $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
        }

        $homeRoot = parse_url($this->wordpress->getSiteUrl());
        $homeRoot = (isset($homeRoot['path']) === true) ? '/'.trim($homeRoot['path'], '/\\').'/' : '/';

        $content = "RewriteEngine On\n";
        $content .= "RewriteBase {$homeRoot}\n";
        $content .= "RewriteRule ^index\\.php$ - [L]\n";

        if ($isSubSite === false) {
            $content .= "RewriteCond %{REQUEST_URI} !.*\/sites\/[0-9]+\/.*\n";
        }

        $directoryMatch = $this->getDirectoryMatch();

        if ($directoryMatch !== null) {
            $content .= "RewriteCond %{REQUEST_URI} {$directoryMatch}\n";
        }

        $content .= "RewriteRule ^([^?]*)$ {$homeRoot}index.php?uamfiletype={$objectType}&uamgetfile=$1 [QSA,L]\n";
        $content .= "RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ ";
        $content .= "{$homeRoot}index.php?uamfiletype={$objectType}&uamgetfile=$1&$2 [QSA,L]\n";
        $content .= "RewriteRule ^(.*)\\?(.*)$ {$homeRoot}index.php?uamgetfile=$1&$2 [QSA,L]\n";
        $content = $this->applyFilters($content);

        $content = "<IfModule mod_rewrite.c>\n$content</IfModule>\n";

        return $content;
    }

    /**
     * Returns the htaccess file name with path.
     *
     * @param null|string $directory
     *
     * @return string
     */
    public function getFileNameWithPath($directory = null)
    {
        return $directory.self::FILE_NAME;
    }

    /**
     * Generates the htaccess file.
     *
     * @param string $directory
     * @param string $objectType
     *
     * @return bool
     */
    public function create($directory, $objectType = null)
    {
        $directory = rtrim($directory, '/').'/';

        if ($this->wordpress->gotModRewrite() === false) {
            $content = $this->getFileContent($directory);
            $this->createPasswordFile(true, $directory);
        } else {
            $content = $this->getPermalinkFileContent(
                $objectType,
                preg_match('/.*\/sites\/[0-9]+\/$/', $directory) !== 0
            );
        }

        // save files
        $fileWithPath = $this->getFileNameWithPath($directory);

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
     * @param string $directory
     *
     * @return bool
     */
    public function delete($directory)
    {
        return $this->deleteFiles($directory);
    }
}
