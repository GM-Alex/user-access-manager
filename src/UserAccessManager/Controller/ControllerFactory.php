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
    protected $php;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var ObjectHandler
     */
    protected $objectHandler;

    /**
     * @var AccessHandler
     */
    protected $accessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $userGroupFactory;

    /**
     * @var FileHandler
     */
    protected $fileHandler;

    /**
     * @var SetupHandler
     */
    protected $setupHandler;

    /**
     * ControllerFactory constructor.
     *
     * @param Php              $php
     * @param Wordpress        $wordpress
     * @param Database         $database
     * @param Config           $config
     * @param Util             $util
     * @param Cache            $cache
     * @param ObjectHandler    $objectHandler
     * @param AccessHandler    $accessHandler
     * @param UserGroupFactory $userGroupFactory
     * @param FileHandler      $fileHandler
     * @param SetupHandler     $setupHandler
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
            $this->fileHandler
        );
    }
}
