<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

abstract class ContentController extends Controller
{
    use AdminOutputControllerTrait;

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        protected MainConfig $mainConfig,
        protected Util $util,
        protected ObjectHandler $objectHandler,
        protected UserHandler $userHandler,
        protected UserGroupHandler $userGroupHandler,
        protected AccessHandler $accessHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
    }

    protected function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

    protected function getMainConfig(): MainConfig
    {
        return $this->mainConfig;
    }

    protected function getUtil(): Util
    {
        return $this->util;
    }

    protected function getUserHandler(): UserHandler
    {
        return $this->userHandler;
    }

    protected function getUserGroupHandler(): UserGroupHandler
    {
        return $this->userGroupHandler;
    }

    protected function removePostFromList($postType): bool
    {
        return $this->mainConfig->hidePostType($postType) === true
            || $this->wordpressConfig->atAdminPanel() === true;
    }
}
