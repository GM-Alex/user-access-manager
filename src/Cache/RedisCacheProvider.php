<?php

declare(strict_types=1);

namespace UserAccessManager\Cache;

use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;

/**
 * Cache provider that stores UAM cache data via the WordPress object cache API.
 *
 * When a persistent object cache backend (e.g. Redis via the Redis Object Cache
 * drop-in) is active, cache entries survive across requests. Without a persistent
 * backend WordPress falls back to an in-memory store that is discarded at the end
 * of each request — in that case the FileSystemCacheProvider is a better choice.
 *
 * This provider is only registered when wp_using_ext_object_cache() returns true,
 * so it will never appear as an option unless a persistent backend is installed.
 */
class RedisCacheProvider implements CacheProviderInterface
{
    /** Unique identifier used to select this provider in UAM settings. */
    const ID = 'RedisCacheProvider';

    /** WordPress option key under which this provider's configuration is stored. */
    const CONFIG_KEY = 'uam_redis_cache_provider';

    /** Configuration parameter: key prefix added to every cache entry. */
    const CONFIG_PREFIX = 'redis_prefix';

    /** Configuration parameter: TTL in seconds (0 = no expiration). */
    const CONFIG_TTL = 'redis_ttl';

    /** Default cache key prefix — avoids collisions with other plugins. */
    const DEFAULT_PREFIX = 'uam_cache';

    /** Default TTL: entries never expire unless explicitly invalidated. */
    const DEFAULT_TTL = 0;

    /** @var Config|null Lazily initialised configuration object. */
    private ?Config $config = null;

    /**
     * @param ConfigFactory          $configFactory          Creates Config instances from WP options.
     * @param ConfigParameterFactory $configParameterFactory Creates individual config parameter objects.
     */
    public function __construct(
        private ConfigFactory $configFactory,
        private ConfigParameterFactory $configParameterFactory
    ) {
    }

    /**
     * Returns the unique identifier for this cache provider.
     *
     * @return string Provider ID used in UAM settings and provider registry.
     */
    public function getId(): string
    {
        return self::ID;
    }

    /**
     * Initialises the cache provider.
     *
     * No setup is required — the WordPress object cache is already bootstrapped
     * before plugins are loaded.
     */
    public function init(): void
    {
        // WordPress object cache is already set up — nothing to initialise here.
    }

    /**
     * Returns the configuration object for this provider, creating it on first call.
     *
     * Exposes two settings in the UAM cache settings panel:
     *   - redis_prefix : string prefix prepended to every cache key
     *   - redis_ttl    : expiration time in seconds (0 = never expire)
     *
     * @return Config Provider configuration.
     */
    public function getConfig(): Config
    {
        if ($this->config === null) {
            $this->config = $this->configFactory->createConfig(self::CONFIG_KEY);

            $configParameters = [
                self::CONFIG_PREFIX => $this->configParameterFactory->createStringConfigParameter(
                    self::CONFIG_PREFIX,
                    self::DEFAULT_PREFIX
                ),
                self::CONFIG_TTL => $this->configParameterFactory->createStringConfigParameter(
                    self::CONFIG_TTL,
                    (string) self::DEFAULT_TTL
                ),
            ];

            $this->config->setDefaultConfigParameters($configParameters);
        }

        return $this->config;
    }

    /**
     * Returns the configured key prefix.
     *
     * Falls back to DEFAULT_PREFIX if config has not been initialised yet.
     *
     * @return string Cache key prefix.
     */
    private function getPrefix(): string
    {
        return (string) ($this->config?->getParameterValue(self::CONFIG_PREFIX) ?? self::DEFAULT_PREFIX);
    }

    /**
     * Returns the configured TTL in seconds.
     *
     * Falls back to DEFAULT_TTL (no expiration) if config has not been initialised yet.
     *
     * @return int TTL in seconds; 0 means no expiration.
     */
    private function getTtl(): int
    {
        return (int) ($this->config?->getParameterValue(self::CONFIG_TTL) ?? self::DEFAULT_TTL);
    }

    /**
     * Builds a namespaced cache key by combining the prefix and the raw key.
     *
     * Using a prefix prevents collisions with other plugins that share the same
     * WordPress object cache group or global key namespace.
     *
     * @param  string $key Raw cache key supplied by UAM.
     * @return string      Namespaced key passed to the object cache API.
     */
    private function buildKey(string $key): string
    {
        return $this->getPrefix() . '|' . $key;
    }

    /**
     * Stores a value in the cache.
     *
     * If the entry already exists it will be overwritten. Uses the provider ID
     * as the object cache group so entries are logically isolated from other data.
     *
     * @param string $key   Cache key.
     * @param mixed  $value Value to store; must be serialisable.
     */
    public function add(string $key, mixed $value): void
    {
        wp_cache_set($this->buildKey($key), $value, self::ID, $this->getTtl());
    }

    /**
     * Retrieves a value from the cache.
     *
     * Returns null when the key does not exist. wp_cache_get() returns false on
     * a cache miss, which is normalised to null to match the interface contract.
     *
     * @param  string $key Cache key.
     * @return mixed       Cached value, or null if the key is not found.
     */
    public function get(string $key): mixed
    {
        $value = wp_cache_get($this->buildKey($key), self::ID);

        // wp_cache_get returns false on a miss; normalise to null per interface contract.
        return ($value === false) ? null : $value;
    }

    /**
     * Removes a single entry from the cache.
     *
     * @param string $key Cache key to delete.
     */
    public function invalidate(string $key): void
    {
        wp_cache_delete($this->buildKey($key), self::ID);
    }
}
