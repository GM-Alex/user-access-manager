<?php

declare(strict_types=1);

namespace UserAccessManager\Cache;

use UserAccessManager\Wrapper\Wordpress;

class Cache
{
    private ?CacheProviderInterface $cacheProvider = null;
    private array $cache = [];
    private array $runtimeCache = [];

    public function __construct(
        private Wordpress $wordpress,
        private CacheProviderFactory $cacheProviderFactory
    ) {
    }

    public function getCacheProvider(): ?CacheProviderInterface
    {
        return $this->cacheProvider;
    }

    public function setActiveCacheProvider(?string $key): void
    {
        $this->cacheProvider = $this->getRegisteredCacheProviders()[$key] ?? null;
        $this->cacheProvider?->init();
    }

    public function generateCacheKey(): string
    {
        $arguments = func_get_args();

        return implode('|', $arguments);
    }

    public function add(string $key, mixed $value): void
    {
        $this->cacheProvider?->add($key, $value);
        $this->cache[$key] = $value;
    }

    public function get(string $key): mixed
    {
        if (isset($this->cache[$key]) === false) {
            $this->cache[$key] = ($this->cacheProvider !== null) ? $this->cacheProvider->get($key) : null;
        }

        return $this->cache[$key];
    }

    public function invalidate(string $key): void
    {
        $this->cacheProvider?->invalidate($key);
        unset($this->cache[$key]);
    }

    public function addToRuntimeCache(string $key, mixed $value): void
    {
        $this->runtimeCache[$key] = $value;
    }

    public function getFromRuntimeCache(string $key): mixed
    {
        if (isset($this->runtimeCache[$key]) === true) {
            return $this->runtimeCache[$key];
        }

        return null;
    }


    public function flushCache(): void
    {
        $this->cache = [];
        $this->runtimeCache = [];
    }

    /**
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
