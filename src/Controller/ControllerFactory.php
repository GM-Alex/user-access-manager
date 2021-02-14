<?php
/**
 * ControllerFactory.php
 *
 * The ControllerFactory class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

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
use UserAccessManager\UserGroup\UserGroupAssignmentHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\DateUtil;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ControllerFactory
 *
 * @package UserAccessManager\Controller
 */
class ControllerFactory
{
    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var WordpressConfig
     */
    private $wordpressConfig;

    /**
     * @var MainConfig
     */
    private $mainConfig;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var DateUtil
     */
    private $dateUtil;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var ObjectMapHandler
     */
    private $objectMapHandler;

    /**
     * @var UserHandler
     */
    private $userHandler;

    /**
     * @var UserGroupHandler
     */
    private $userGroupHandler;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

    /**
     * @var UserGroupAssignmentHandler
     */
    private $userGroupAssignmentHandler;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var FileObjectFactory
     */
    private $fileObjectFactory;

    /**
     * @var SetupHandler
     */
    private $setupHandler;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var FormHelper
     */
    private $formHelper;

    /**
     * @var ObjectInformationFactory
     */
    private $objectInformationFactory;

    /**
     * ControllerFactory constructor.
     * @param Php                        $php
     * @param Wordpress                  $wordpress
     * @param Database                   $database
     * @param WordpressConfig            $wordpressConfig
     * @param MainConfig                 $mainConfig
     * @param Util                       $util
     * @param DateUtil                   $dateUtil
     * @param Cache                      $cache
     * @param ObjectHandler              $objectHandler
     * @param ObjectMapHandler           $objectMapHandler
     * @param UserHandler                $userHandler
     * @param UserGroupHandler           $userGroupHandler
     * @param UserGroupFactory           $userGroupFactory
     * @param UserGroupAssignmentHandler $userGroupAssignmentHandler
     * @param AccessHandler              $accessHandler
     * @param FileHandler                $fileHandler
     * @param FileObjectFactory          $fileObjectFactory
     * @param SetupHandler               $setupHandler
     * @param FormFactory                $formFactory
     * @param FormHelper                 $formHelper
     * @param ObjectInformationFactory   $objectInformationFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util,
        DateUtil $dateUtil,
        Cache $cache,
        ObjectHandler $objectHandler,
        ObjectMapHandler $objectMapHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        UserGroupFactory $userGroupFactory,
        UserGroupAssignmentHandler $userGroupAssignmentHandler,
        AccessHandler $accessHandler,
        FileHandler $fileHandler,
        FileObjectFactory $fileObjectFactory,
        SetupHandler $setupHandler,
        FormFactory $formFactory,
        FormHelper $formHelper,
        ObjectInformationFactory $objectInformationFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->wordpressConfig = $wordpressConfig;
        $this->mainConfig = $mainConfig;
        $this->util = $util;
        $this->dateUtil = $dateUtil;
        $this->cache = $cache;
        $this->objectHandler = $objectHandler;
        $this->objectMapHandler = $objectMapHandler;
        $this->userHandler = $userHandler;
        $this->userGroupHandler = $userGroupHandler;
        $this->userGroupFactory = $userGroupFactory;
        $this->userGroupAssignmentHandler = $userGroupAssignmentHandler;
        $this->accessHandler = $accessHandler;
        $this->fileHandler = $fileHandler;
        $this->fileObjectFactory = $fileObjectFactory;
        $this->setupHandler = $setupHandler;
        $this->formFactory = $formFactory;
        $this->formHelper = $formHelper;
        $this->objectInformationFactory = $objectInformationFactory;
    }

    /**
     * Creates and returns a new backend controller.
     * @return BackendController
     */
    public function createBackendController(): BackendController
    {
        return new BackendController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->userHandler,
            $this->fileHandler,
            $this->setupHandler
        );
    }

    /**
     * Creates and returns a new backend about controller.
     * @return AboutController
     */
    public function createBackendAboutController(): AboutController
    {
        return new AboutController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig
        );
    }

    /**
     * Creates and returns a new backend object controller.
     * @return ObjectController
     */
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

    /**
     * Creates and returns a new backend cache controller.
     * @return CacheController
     */
    public function createBackendCacheController(): CacheController
    {
        return new CacheController(
            $this->cache
        );
    }

    /**
     * Creates and returns a new backend post object controller.
     * @return PostObjectController
     */
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

    /**
     * Creates and returns a new backend term object controller.
     * @return TermObjectController
     */
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

    /**
     * Creates and returns a new backend user object controller.
     * @return UserObjectController
     */
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

    /**
     * Creates and returns a new backend dynamic group controller.
     * @return DynamicGroupsController
     */
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

    /**
     * Creates and returns a new backend setup controller.
     * @return SettingsController
     */
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

    /**
     * Creates and returns a new backend setup controller.
     * @return SetupController
     */
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

    /**
     * Creates and returns a new backend user group controller.
     * @return UserGroupController
     */
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

    /**
     * Creates and returns a new frontend controller.
     * @return FrontendController
     */
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

    /**
     * Creates and returns a new frontend post controller.
     * @return PostController
     */
    public function createFrontendPostController(): PostController
    {
        return new PostController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->database,
            $this->util,
            $this->objectHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->accessHandler
        );
    }

    /**
     * Creates and returns a new frontend redirect controller.
     * @return RedirectController
     */
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

    /**
     * Creates and returns a new frontend short code controller.
     * @return ShortCodeController
     */
    public function createFrontendShortCodeController(): ShortCodeController
    {
        return new ShortCodeController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->userGroupHandler
        );
    }

    /**
     * Creates and returns a new frontend term controller.
     * @return TermController
     */
    public function createFrontendTermController(): TermController
    {
        return new TermController(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->util,
            $this->objectHandler,
            $this->objectMapHandler,
            $this->userHandler,
            $this->userGroupHandler,
            $this->accessHandler
        );
    }
}
