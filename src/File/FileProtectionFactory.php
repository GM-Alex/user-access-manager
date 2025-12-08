<?php

declare(strict_types=1);

namespace UserAccessManager\File;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class FileProtectionFactory
{
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private WordpressConfig $wordpressConfig,
        private MainConfig $mainConfig,
        private Util $util
    ) {}

    public function createApacheFileProtection(): ApacheFileProtection
    {
        return new ApacheFileProtection(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->util
        );
    }

    public function createNginxFileProtection(): NginxFileProtection
    {
        return new NginxFileProtection(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->util
        );
    }
}
