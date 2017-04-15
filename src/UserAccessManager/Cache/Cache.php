<?php
/**
 * Cache.php
 *
 * The Cache class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Cache;

/**
 * Class Cache
 *
 * @package UserAccessManager\Cache
 */
class Cache
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * Returns a generated cache key.
     *
     * @return string
     */
    public function generateCacheKey()
    {
        $arguments = func_get_args();

        return implode('|', $arguments);
    }

    /**
     * Adds the variable to the cache.
     *
     * @param string $key   The cache key
     * @param mixed  $value The value.
     */
    public function addToCache($key, $value)
    {
        $this->cache[$key] = $value;
    }

    /**
     * Returns a value from the cache by the given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getFromCache($key)
    {
        if (isset($this->cache[$key]) === true) {
            return $this->cache[$key];
        }

        return null;
    }

    /**
     * Flushes the cache.
     */
    public function flushCache()
    {
        $this->cache = [];
    }
}
