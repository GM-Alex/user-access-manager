<?php
/**
 * WordpressConfig.php
 *
 * The WordpressConfig class file.
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

namespace UserAccessManager\Config;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Class WordpressConfig
 *
 * @package UserAccessManager\Config
 */
class WordpressConfig
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var string
     */
    private $baseFile;

    /**
     * @var null|bool
     */
    private $isPermalinksActive = null;

    /**
     * @var null|array
     */
    private $mimeTypes = null;

    /**
     * WordpressClass constructor.
     * @param Wordpress $wordpress
     * @param string $baseFile
     */
    public function __construct(Wordpress $wordpress, string $baseFile)
    {
        $this->wordpress = $wordpress;
        $this->baseFile = $baseFile;
    }

    /**
     * Returns true if a user is at the admin panel.
     * @return bool
     */
    public function atAdminPanel(): bool
    {
        return $this->wordpress->isAdmin();
    }

    /**
     * Returns true if permalinks are active otherwise false.
     * @return bool
     */
    public function isPermalinksActive(): ?bool
    {
        if ($this->isPermalinksActive === null) {
            $permalinkStructure = $this->wordpress->getOption('permalink_structure');
            $this->isPermalinksActive = (empty($permalinkStructure) === false);
        }

        return $this->isPermalinksActive;
    }

    /**
     * Returns the upload directory.
     * @return null|string
     */
    public function getUploadDirectory(): ?string
    {
        $wordpressUploadDir = $this->wordpress->getUploadDir();

        if (empty($wordpressUploadDir['error'])) {
            return $wordpressUploadDir['basedir'] . DIRECTORY_SEPARATOR;
        }

        return null;
    }

    /**
     * Returns the full supported mine types.
     * @return array
     */
    public function getMimeTypes(): ?array
    {
        if ($this->mimeTypes === null) {
            $mimeTypes = $this->wordpress->getAllowedMimeTypes();
            $fullMimeTypes = [];

            foreach ($mimeTypes as $extensions => $mineType) {
                $extensions = explode('|', $extensions);

                foreach ($extensions as $extension) {
                    $fullMimeTypes[$extension] = $mineType;
                }
            }

            $this->mimeTypes = $fullMimeTypes;
        }

        return $this->mimeTypes;
    }

    /**
     * Returns the module url path.
     * @return string
     */
    public function getUrlPath(): string
    {
        return $this->wordpress->pluginsUrl('', $this->baseFile) . '/';
    }

    /**
     * Returns the module real path.
     * @return string
     */
    public function getRealPath(): string
    {
        $dirName = dirname($this->baseFile);

        return $this->wordpress->getPluginDir() . DIRECTORY_SEPARATOR
            . $this->wordpress->pluginBasename($dirName) . DIRECTORY_SEPARATOR;
    }
}
