<?php

declare(strict_types=1);

namespace UserAccessManager\Cache;

use Exception;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;

class RedisCacheProvider implements CacheProviderInterface
{
    const ID = 'RedisCacheProvider';
    const CONFIG_KEY = 'uam_redis_cache_provider';
    const CONFIG_PREFIX = 'redis_prefix';
    const CONFIG_TTL = 'redis_ttl';
    const DEFAULT_PREFIX = 'uam_cache';
    const DEFAULT_TTL = 0;

    private ?Config $config = null;

    public function __construct(
        private ConfigFactory $configFactory,
        private ConfigParameterFactory $configParameterFactory
    ) {
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function init(): void
    {
        // WordPress object cache is already set up — nothing to initialize here.
    }

    /**
     * @throws Exception
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

    private function getPrefix(): string
    {
        return (string) ($this->config?->getParameterValue(self::CONFIG_PREFIX) ?? self::DEFAULT_PREFIX);
    }

    private function getTtl(): int
    {
        return (int) ($this->config?->getParameterValue(self::CONFIG_TTL) ?? self::DEFAULT_TTL);
    }

    private function buildKey(string $key): string
    {
        return $this->getPrefix() . '|' . $key;
    }

    public function add(string $key, mixed $value): void
    {
        wp_cache_set($this->buildKey($key), $value, self::ID, $this->getTtl());
    }

    public function get(string $key): mixed
    {
        $value = wp_cache_get($this->buildKey($key), self::ID);

        // wp_cache_get returns false on a miss; normalize to null per interface contract.
        return ($value === false) ? null : $value;
    }

    public function invalidate(string $key): void
    {
        wp_cache_delete($this->buildKey($key), self::ID);
    }
}
