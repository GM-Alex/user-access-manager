<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ContentController
 *
 * @package UserAccessManager\Controller\Frontend
 */
abstract class ContentController extends Controller
{
    use AdminOutputControllerTrait;

    /**
     * @var MainConfig
     */
    protected $mainConfig;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var ObjectHandler
     */
    protected $objectHandler;

    /**
     * @var ObjectMapHandler
     */
    protected $objectMapHandler;

    /**
     * @var UserHandler
     */
    protected $userHandler;

    /**
     * @var UserGroupHandler
     */
    protected $userGroupHandler;

    /**
     * @var AccessHandler
     */
    protected $accessHandler;

    /**
     * ContentController constructor.
      * @param Php              $php
     * @param Wordpress        $wordpress
     * @param WordpressConfig  $wordpressConfig
     * @param MainConfig       $mainConfig
     * @param Util             $util
     * @param ObjectHandler    $objectHandler
     * @param UserHandler      $userHandler
     * @param UserGroupHandler $userGroupHandler
     * @param AccessHandler    $accessHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        AccessHandler $accessHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->mainConfig = $mainConfig;
        $this->util = $util;
        $this->objectHandler = $objectHandler;
        $this->userHandler = $userHandler;
        $this->userGroupHandler = $userGroupHandler;
        $this->accessHandler = $accessHandler;
    }

    /**
     * @return Wordpress
     */
    protected function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

    /**
     * @return MainConfig
     */
    protected function getMainConfig(): MainConfig
    {
        return $this->mainConfig;
    }

    /**
     * @return Util
     */
    protected function getUtil(): Util
    {
        return $this->util;
    }

    /**
     * @return UserHandler
     */
    protected function getUserHandler(): UserHandler
    {
        return $this->userHandler;
    }

    /**
     * @return UserGroupHandler
     */
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
