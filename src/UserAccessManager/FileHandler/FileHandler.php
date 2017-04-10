<?php
/**
 * FileHandler.php
 *
 * The FileHandler class file.
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

use UserAccessManager\Config\Config;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FileHandler
 *
 * @package UserAccessManager\FileHandler
 */
class FileHandler
{
    /**
     * @var Php
     */
    protected $php;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var FileProtectionFactory
     */
    protected $fileProtectionFactory;

    /**
     * FileHandler constructor.
     *
     * @param Php                   $php
     * @param Wordpress             $wordpress
     * @param Config                $config
     * @param FileProtectionFactory $fileProtectionFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Config $config,
        FileProtectionFactory $fileProtectionFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->config = $config;
        $this->fileProtectionFactory = $fileProtectionFactory;
    }

    /**
     * Delivers the content of the requested file.
     *
     * @param string $file
     * @param bool   $isImage
     *
     * @return null
     */
    public function getFile($file, $isImage)
    {
        //Deliver content
        if (file_exists($file) === true) {
            $fileName = basename($file);

            /*
             * This only for compatibility
             * mime_content_type has been deprecated as the PECL extension file info
             * provides the same functionality (and more) in a much cleaner way.
             */
            $explodedFileName = explode('.', $fileName);
            $lastElement = array_pop($explodedFileName);
            $fileExt = strtolower($lastElement);

            $mimeTypes = $this->config->getMimeTypes();

            if ($this->php->functionExists('finfo_open')) {
                $fileInfo = finfo_open(FILEINFO_MIME);
                $fileMimeType = finfo_file($fileInfo, $file);
                finfo_close($fileInfo);
            } elseif ($this->php->functionExists('mime_content_type')) {
                $fileMimeType = mime_content_type($file);
            } elseif (isset($mimeTypes[$fileExt])) {
                $fileMimeType = $mimeTypes[$fileExt];
            } else {
                $fileMimeType = 'application/octet-stream';
            }

            header('Content-Description: File Transfer');
            header('Content-Type: '.$fileMimeType);

            if ($isImage === false) {
                $baseName = str_replace(' ', '_', basename($file));
                header('Content-Disposition: attachment; filename="'.$baseName.'"');
            }

            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($file));

            if ($this->config->getDownloadType() === 'fopen'
                && $isImage === false
            ) {
                $handler = fopen($file, 'r');

                //TODO find better solution (prevent '\n' / '0A')
                ob_clean();
                flush();

                while (feof($handler) === false) {
                    if ($this->php->iniGet('safe_mode') !== '') {
                        $this->php->setTimeLimit(30);
                    }

                    echo $this->php->fread($handler, 1024);
                }
            } else {
                ob_clean();
                flush();
                readfile($file);
            }
        } else {
            $this->wordpress->wpDie(TXT_UAM_FILE_NOT_FOUND_ERROR);
        }

        return null;
    }

    /**
     * Creates a protection file.
     *
     * @param string $dir        The destination directory.
     * @param string $objectType The object type.
     *
     * @return false
     */
    public function createFileProtection($dir = null, $objectType = null)
    {
        $dir = ($dir === null) ? $this->config->getUploadDirectory() : $dir;

        if ($dir !== null) {
            if ($this->wordpress->isNginx() === true) {
                return $this->fileProtectionFactory->createNginxFileProtection()->create($dir, $objectType);
            } else {
                return $this->fileProtectionFactory->createApacheFileProtection()->create($dir, $objectType);
            }
        }

        return false;
    }


    /**
     * Deletes the protection files.
     *
     * @param string $dir The destination directory.
     *
     * @return false
     */
    public function deleteFileProtection($dir = null)
    {
        $dir = ($dir === null) ? $this->config->getUploadDirectory() : $dir;

        if ($dir !== null) {
            if ($this->wordpress->isNginx() === true) {
                return $this->fileProtectionFactory->createNginxFileProtection()->delete($dir);
            } else {
                return $this->fileProtectionFactory->createApacheFileProtection()->delete($dir);
            }
        }

        return false;
    }
}
