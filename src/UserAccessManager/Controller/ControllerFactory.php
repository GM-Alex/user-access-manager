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
namespace UserAccessManager\Controller;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileObjectFactory;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
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
     * @var Config
     */
    private $config;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

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
     * ControllerFactory constructor.
     *
     * @param Php               $php
     * @param Wordpress         $wordpress
     * @param Database          $database
     * @param Config            $config
     * @param Util              $util
     * @param Cache             $cache
     * @param ObjectHandler     $objectHandler
     * @param AccessHandler     $accessHandler
     * @param UserGroupFactory  $userGroupFactory
     * @param FileHandler       $fileHandler
     * @param FileObjectFactory $fileObjectFactory
     * @param SetupHandler      $setupHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        Config $config,
        Util $util,
        Cache $cache,
        ObjectHandler $objectHandler,
        AccessHandler $accessHandler,
        UserGroupFactory $userGroupFactory,
        FileHandler $fileHandler,
        FileObjectFactory $fileObjectFactory,
        SetupHandler $setupHandler
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->config = $config;
        $this->util = $util;
        $this->cache = $cache;
        $this->objectHandler = $objectHandler;
        $this->accessHandler = $accessHandler;
        $this->userGroupFactory = $userGroupFactory;
        $this->fileHandler = $fileHandler;
        $this->fileObjectFactory = $fileObjectFactory;
        $this->setupHandler = $setupHandler;
    }

    /**
     * Creates and returns a new admin controller.
     *
     * @return AdminController
     */
    public function createAdminController()
    {
        return new AdminController(
            $this->php,
            $this->wordpress,
            $this->config,
            $this->accessHandler,
            $this->fileHandler
        );
    }

    /**
     * Creates and returns a new admin about controller.
     *
     * @return AdminAboutController
     */
    public function createAdminAboutController()
    {
        return new AdminAboutController(
            $this->php,
            $this->wordpress,
            $this->config
        );
    }

    /**
     * Creates and returns a new admin about controller.
     *
     * @return AdminObjectController
     */
    public function createAdminObjectController()
    {
        return new AdminObjectController(
            $this->php,
            $this->wordpress,
            $this->config,
            $this->database,
            $this->objectHandler,
            $this->accessHandler
        );
    }

    /**
     * Creates and returns a new admin setup controller.
     *
     * @return AdminSettingsController
     */
    public function createAdminSettingsController()
    {
        return new AdminSettingsController(
            $this->php,
            $this->wordpress,
            $this->config,
            $this->objectHandler,
            $this->fileHandler
        );
    }

    /**
     * Creates and returns a new admin setup controller.
     *
     * @return AdminSetupController
     */
    public function createAdminSetupController()
    {
        return new AdminSetupController(
            $this->php,
            $this->wordpress,
            $this->config,
            $this->database,
            $this->setupHandler
        );
    }

    /**
     * Creates and returns a new admin user group controller.
     *
     * @return AdminUserGroupController
     */
    public function createAdminUserGroupController()
    {
        return new AdminUserGroupController(
            $this->php,
            $this->wordpress,
            $this->config,
            $this->accessHandler,
            $this->userGroupFactory
        );
    }

    /**
     * Creates and returns a new frontend controller.
     *
     * @return FrontendController
     */
    public function createFrontendController()
    {
        return new FrontendController(
            $this->php,
            $this->wordpress,
            $this->config,
            $this->database,
            $this->util,
            $this->cache,
            $this->objectHandler,
            $this->accessHandler,
            $this->fileHandler,
            $this->fileObjectFactory
        );
    }
}
