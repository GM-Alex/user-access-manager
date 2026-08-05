<?php

declare(strict_types=1);

namespace UserAccessManager\Cache;

use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\Parameter\ConfigParameterFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class CacheProviderFactory
{
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private Util $util,
        private ConfigFactory $configFactory,
        private ConfigParameterFactory $configParameterFactory
    ) {
    }

    public function createFileSystemCacheProvider(): FileSystemCacheProvider
    {
        return new FileSystemCacheProvider(
            $this->php,
            $this->wordpress,
            $this->util,
            $this->configFactory,
            $this->configParameterFactory
        );
    }

    public function createRedisCacheProvider(): RedisCacheProvider
    {
        return new RedisCacheProvider(
            $this->wordpress,
            $this->configFactory,
            $this->configParameterFactory
        );
    }
}
