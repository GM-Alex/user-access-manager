<?php

declare(strict_types=1);

namespace UserAccessManager\Cache;

use UserAccessManager\Config\Config;

interface CacheProviderInterface
{
    public function getId(): string;

    public function init(): void;

    public function getConfig(): Config;

    public function add(string $key, mixed $value);

    public function get(string $key): mixed;

    public function invalidate(string $key);
}
