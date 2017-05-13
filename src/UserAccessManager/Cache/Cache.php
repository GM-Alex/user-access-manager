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
     * @var null|CacheProviderInterface
     */
    private $cacheProvider = null;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var array
     */
    private $runtimeCache = [];

    /**
     * Sets a cache provider
     *
     * @param CacheProviderInterface $cacheProvider
     */
    public function setCacheProvider(CacheProviderInterface $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

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
    public function add($key, $value)
    {
        if ($this->cacheProvider !== null) {
            $this->cacheProvider->add($key, $value);
        }

        $this->cache[$key] = $value;
    }

    /**
     * Returns a value from the cache by the given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->cache[$key]) === false) {
            $this->cache[$key] = ($this->cacheProvider !== null) ? $this->cacheProvider->get($key) : null;
        }

        return $this->cache[$key];
    }

    /**
     * Invalidates the cached object.
     *
     * @param string $key
     */
    public function invalidate($key)
    {
        if ($this->cacheProvider !== null) {
            $this->cacheProvider->invalidate($key);
        }

        unset($this->cache[$key]);
    }

    /**
     * Adds the variable to the runtime cache.
     *
     * @param string $key   The cache key
     * @param mixed  $value The value.
     */
    public function addToRuntimeCache($key, $value)
    {
        $this->runtimeCache[$key] = $value;
    }

    /**
     * Returns a value from the runtime cache by the given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getFromRuntimeCache($key)
    {
        if (isset($this->runtimeCache[$key]) === true) {
            return $this->runtimeCache[$key];
        }

        return null;
    }

    /**
     * Flushes the cache.
     */
    public function flushCache()
    {
        $this->cache = [];
        $this->runtimeCache = [];
    }
}
