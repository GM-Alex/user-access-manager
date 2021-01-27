<?php
/**
 * CacheProviderInterface.php
 *
 * The CacheProviderInterface interface file.
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

namespace UserAccessManager\Cache;

use UserAccessManager\Config\Config;

/**
 * Interface CacheProviderInterface
 *
 * @package UserAccessManager\Cache
 */
interface CacheProviderInterface
{
    /**
     * Returns the id of the cache provider.
     * @return string
     */
    public function getId(): string;

    /**
     * Initialises the cache provider object.
     */
    public function init();

    /**
     * Returns the cache provider configuration.
     * @return Config
     */
    public function getConfig(): Config;

    /**
     * Adds a value to the cache.
     * @param string $key
     * @param mixed $value
     */
    public function add(string $key, $value);

    /**
     * Returns a value from the cache.
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * Invalidates the cache.
     * @param string $key
     */
    public function invalidate(string $key);
}
