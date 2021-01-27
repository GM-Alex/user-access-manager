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

declare(strict_types=1);

namespace UserAccessManager\Cache;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Cache
 *
 * @package UserAccessManager\Cache
 */
class Cache
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var CacheProviderFactory
     */
    private $cacheProviderFactory;

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
     * Cache constructor.
     * @param Wordpress $wordpress
     * @param CacheProviderFactory $cacheProviderFactory
     */
    public function __construct(Wordpress $wordpress, CacheProviderFactory $cacheProviderFactory)
    {
        $this->wordpress = $wordpress;
        $this->cacheProviderFactory = $cacheProviderFactory;
    }

    /**
     * Return the cache provider.
     * @return CacheProviderInterface|null
     */
    public function getCacheProvider(): ?CacheProviderInterface
    {
        return $this->cacheProvider;
    }

    /**
     * Sets a cache provider
     * @param null|string $key
     */
    public function setActiveCacheProvider(?string $key)
    {
        $cacheProviders = $this->getRegisteredCacheProviders();

        $this->cacheProvider = (isset($cacheProviders[$key]) === true) ? $cacheProviders[$key] : null;

        if ($this->cacheProvider !== null) {
            $this->cacheProvider->init();
        }
    }

    /**
     * Returns a generated cache key.
     * @return string
     */
    public function generateCacheKey(): string
    {
        $arguments = func_get_args();

        return implode('|', $arguments);
    }

    /**
     * Adds the variable to the cache.
     * @param string $key The cache key
     * @param mixed $value The value.
     */
    public function add(string $key, $value)
    {
        if ($this->cacheProvider !== null) {
            $this->cacheProvider->add($key, $value);
        }

        $this->cache[$key] = $value;
    }

    /**
     * Returns a value from the cache by the given key.
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if (isset($this->cache[$key]) === false) {
            $this->cache[$key] = ($this->cacheProvider !== null) ? $this->cacheProvider->get($key) : null;
        }

        return $this->cache[$key];
    }

    /**
     * Invalidates the cached object.
     * @param string $key
     */
    public function invalidate(string $key)
    {
        if ($this->cacheProvider !== null) {
            $this->cacheProvider->invalidate($key);
        }

        unset($this->cache[$key]);
    }

    /**
     * Adds the variable to the runtime cache.
     * @param string $key The cache key
     * @param mixed $value The value.
     */
    public function addToRuntimeCache(string $key, $value)
    {
        $this->runtimeCache[$key] = $value;
    }

    /**
     * Returns a value from the runtime cache by the given key.
     * @param string $key
     * @return mixed
     */
    public function getFromRuntimeCache(string $key)
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

    /**
     * Returns a list of the registered cache handlers.
     * @return CacheProviderInterface[]
     */
    public function getRegisteredCacheProviders(): array
    {
        $fileSystemCacheProvider = $this->cacheProviderFactory->createFileSystemCacheProvider();

        return $this->wordpress->applyFilters(
            'uam_registered_cache_handlers',
            [$fileSystemCacheProvider->getId() => $fileSystemCacheProvider]
        );
    }
}
