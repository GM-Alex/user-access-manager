<?php

declare(strict_types=1);

namespace UserAccessManager\Widget;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\BaseControllerTrait;
use UserAccessManager\Controller\Frontend\LoginControllerTrait;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use WP_Widget;

class LoginWidget extends WP_Widget
{
    use BaseControllerTrait;
    use LoginControllerTrait;

    const WIDGET_ID = 'uam_login_widget';

    /**
     * LoginWidget constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     */
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private WordpressConfig $wordpressConfig
    ) {
        $this->template = 'LoginWidget.php';

        parent::__construct(
            self::WIDGET_ID,
            TXT_UAM_LOGIN_WIDGET_TITLE,
            ['description' => TXT_UAM_LOGIN_WIDGET_DESC]
        );
    }

    protected function getPhp(): Php
    {
        return $this->php;
    }

    protected function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

    protected function getWordpressConfig(): WordpressConfig
    {
        return $this->wordpressConfig;
    }

    public function widget($args, $instance): void
    {
        $this->render();
    }
}
