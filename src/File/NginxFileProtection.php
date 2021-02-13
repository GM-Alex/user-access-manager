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

declare(strict_types=1);

namespace UserAccessManager\File;

use Exception;
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
     * Returns the location.
     * @param string $directory
     * @return string
     */
    protected function getLocation(string $directory): string
    {
        if ($this->mainConfig->getLockedDirectoryType() === 'wordpress') {
            return "^{$directory}" . $this->getDirectoryMatch();
        }

        $directoryMatch = $this->getDirectoryMatch();
        return $directoryMatch === null ? $directory : $directoryMatch;
    }

    /**
     * Creates the file content if permalinks are active.
     * @param string $absolutePath
     * @param string $directory
     * @param string|null $objectType
     * @return string
     */
    private function getFileContent(string $absolutePath, string $directory, ?string $objectType): string
    {
        if ($objectType === null) {
            $objectType = ObjectHandler::ATTACHMENT_OBJECT_TYPE;
        }

        $location = $this->getLocation(str_replace($absolutePath, '/', $directory));

        $content = "location {$location} {\n";
        $content .= "rewrite ^([^?]*)$ /index.php?uamfiletype={$objectType}&uamgetfile=$1 last;\n";
        $content .= "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ ";
        $content .= "/index.php?uamfiletype={$objectType}&uamgetfile=$1&$2 last;\n";
        $content .= "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n";
        $content .= "}\n";

        return $content;
    }

    /**
     * Returns the nginx config file name with path.
     * @param string null|$directory
     * @return string
     */
    public function getFileNameWithPath($directory = null): string
    {
        return ABSPATH . self::FILE_NAME;
    }

    /**
     * Generates the conf file.
     * @param string $directory
     * @param string|null $objectType
     * @param string|null $absolutePath
     * @return bool
     */
    public function create(string $directory, ?string $objectType = null, ?string $absolutePath = ABSPATH): bool
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $content = $this->getFileContent($absolutePath, $directory, $objectType);

        // save files
        $fileWithPath = $absolutePath . self::FILE_NAME;

        try {
            file_put_contents($fileWithPath, $content);
            return true;
        } catch (Exception $exception) {
            // Because file_put_contents can throw exceptions we use this try catch block
            // to return the success result instead of an exception
        }

        return false;
    }

    /**
     * Deletes the conf file.
     * @param string $directory
     * @return bool
     */
    public function delete(string $directory): bool
    {
        return $this->deleteFiles($directory);
    }
}
