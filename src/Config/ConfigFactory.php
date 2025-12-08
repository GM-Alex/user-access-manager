<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use UserAccessManager\Wrapper\Wordpress;


class ConfigFactory
{
    public function __construct(
        private Wordpress $wordpress
    ) {}

    public function createConfig(string $key): Config
    {
        return new Config($this->wordpress, $key);
    }
}
