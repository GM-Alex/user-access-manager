<?php

declare(strict_types=1);

namespace UserAccessManager\Widget;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class WidgetFactory
{
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private WordpressConfig $wordpressConfig
    ) {
    }

    public function createLoginWidget(): LoginWidget
    {
        return new LoginWidget($this->php, $this->wordpress, $this->wordpressConfig);
    }
}
