<?php

declare(strict_types=1);

namespace UserAccessManager\Controller;

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Backend\AboutController;
use UserAccessManager\Controller\Backend\BackendController;
use UserAccessManager\Controller\Backend\CacheController;
use UserAccessManager\Controller\Backend\DynamicGroupsController;
use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Controller\Backend\ObjectInformationFactory;
use UserAccessManager\Controller\Backend\PostObjectController;
use UserAccessManager\Controller\Backend\SettingsController;
use UserAccessManager\Controller\Backend\SetupController;
use UserAccessManager\Controller\Backend\TermObjectController;
use UserAccessManager\Controller\Backend\UserGroupController;
use UserAccessManager\Controller\Backend\UserObjectController;
use UserAccessManager\Controller\Frontend\FrontendController;
use UserAccessManager\Controller\Frontend\PostController;
use UserAccessManager\Controller\Frontend\RedirectController;
use UserAccessManager\Controller\Frontend\ShortCodeController;
use UserAccessManager\Controller\Frontend\TermController;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\File\FileObjectFactory;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupAssignmentHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\DateUtil;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class ControllerFactory
{
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private Database $database,
        private WordpressConfig $wordpressConfig,
        private MainConfig $mainConfig,
        private Util $util,
        private DateUtil $dateUtil,
        private Cache $cache,
        private ObjectHandler $objectHandler,
        private ObjectMapHandler $objectMapHandler,
        private UserHandler $userHandler,
        private UserGroupHandler $userGroupHandler,
        private UserGroupFactory $userGroupFactory,
        private UserGroupAssignmentHandler $userGroupAssignmentHandler,
        private AccessHandler $accessHandler,
        private FileHandler $fileHandler,
        private FileObjectFactory $fileObjectFactory,
        private SetupHandler $setupHandler,
        private FormFactory $formFactory,
        private FormHelper $formHelper,
        private ObjectInformationFactory $objectInformationFactory
    ) {
    }

    public function createBackendController(): BackendController
    {
        return new BackendController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->userHandler,
            $this->setupHandler
        );
    }

    public function createBackendAboutController(): AboutController
    {
        return new AboutController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig
        );
    }

    public function createBackendObjectController(): ObjectController
    {
        return new ObjectController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->database,
            $this->dateUtil,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->userGroupAssignmentHandler,
            $this->accessHandler,
            $this->objectInformationFactory
        );
    }

    public function createBackendCacheController(): CacheController
    {
        return new CacheController(
            $this->cache
        );
    }

    public function createBackendPostObjectController(): PostObjectController
    {
        return new PostObjectController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->database,
            $this->dateUtil,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->userGroupAssignmentHandler,
            $this->accessHandler,
            $this->objectInformationFactory
        );
    }

    public function createBackendTermObjectController(): TermObjectController
    {
        return new TermObjectController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->database,
            $this->dateUtil,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->userGroupAssignmentHandler,
            $this->accessHandler,
            $this->objectInformationFactory
        );
    }

    public function createBackendUserObjectController(): UserObjectController
    {
        return new UserObjectController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->database,
            $this->dateUtil,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->userGroupAssignmentHandler,
            $this->accessHandler,
            $this->objectInformationFactory
        );
    }

    public function createBackendDynamicGroupsController(): DynamicGroupsController
    {
        return new DynamicGroupsController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->database,
            $this->dateUtil,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->userGroupAssignmentHandler,
            $this->accessHandler,
            $this->objectInformationFactory
        );
    }

    public function createBackendSettingsController(): SettingsController
    {
        return new SettingsController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->cache,
            $this->fileHandler,
            $this->formFactory,
            $this->formHelper
        );
    }

    public function createBackendSetupController(): SetupController
    {
        return new SetupController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->database,
            $this->setupHandler
        );
    }

    public function createBackendUserGroupController(): UserGroupController
    {
        return new UserGroupController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->userGroupHandler,
            $this->userGroupFactory,
            $this->formHelper
        );
    }

    public function createFrontendController(): FrontendController
    {
        return new FrontendController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->accessHandler
        );
    }

    public function createFrontendPostController(): PostController
    {
        return new PostController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->util,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->accessHandler,
            $this->database
        );
    }

    public function createFrontendRedirectController(): RedirectController
    {
        return new RedirectController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->database,
            $this->util,
            $this->cache,
            $this->objectHandler,
            $this->accessHandler,
            $this->fileHandler,
            $this->fileObjectFactory
        );
    }

    public function createFrontendShortCodeController(): ShortCodeController
    {
        return new ShortCodeController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->userGroupHandler
        );
    }

    public function createFrontendTermController(): TermController
    {
        return new TermController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->util,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->accessHandler,
            $this->objectMapHandler
        );
    }
}
