<?php

declare(strict_types=1);

namespace UserAccessManager\File;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

interface FileProtectionInterface
{
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util
    );

    public function getFileNameWithPath(string $directory = null): string;
    public function create(string $directory, ?string $objectType = null, ?string $absolutePath = null): bool;
    public function delete(string $directory): bool;
}
