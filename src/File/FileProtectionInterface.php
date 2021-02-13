<?php
/**
 * FileProtectionInterface.php
 *
 * The FileProtectionInterface interface file.
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

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Interface FileProtectionInterface
 *
 * @package UserAccessManager\FileProtection
 */
interface FileProtectionInterface
{
    /**
     * FileProtectionInterface constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig $mainConfig
     * @param Util $util
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util
    );

    /**
     * Returns the file with path.
     * @param null|string $directory
     * @return string
     */
    public function getFileNameWithPath($directory = null): string;

    /**
     * Creates the file protection.
     * @param string $directory
     * @param string|null $objectType
     * @param string|null $absolutePath
     * @return bool
     */
    public function create(string $directory, ?string $objectType = null, ?string $absolutePath = null): bool;

    /**
     * Deletes the file protection.
     * @param string $directory
     * @return bool
     */
    public function delete(string $directory): bool;
}
