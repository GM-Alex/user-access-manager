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
 * @version   SVN: $Id$
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
    protected $oPhp;

    /**
     * @var Wordpress
     */
    protected $oWordpress;

    /**
     * @var Database
     */
    protected $oDatabase;

    /**
     * @var Config
     */
    protected $oConfig;

    /**
     * @var Util
     */
    protected $oUtil;

    /**
     * @var Cache
     */
    protected $oCache;

    /**
     * @var ObjectHandler
     */
    protected $oObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $oAccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $oUserGroupFactory;

    /**
     * @var FileHandler
     */
    protected $oFileHandler;

    /**
     * @var SetupHandler
     */
    protected $oSetupHandler;

    /**
     * ControllerFactory constructor.
     *
     * @param Php              $oPhp
     * @param Wordpress        $oWordpress
     * @param Database         $oDatabase
     * @param Config           $oConfig
     * @param Util             $oUtil
     * @param Cache            $oCache
     * @param ObjectHandler    $oObjectHandler
     * @param AccessHandler    $oAccessHandler
     * @param UserGroupFactory $oUserGroupFactory
     * @param FileHandler      $oFileHandler
     * @param SetupHandler     $oSetupHandler
     */
    public function __construct(
        Php $oPhp,
        Wordpress $oWordpress,
        Database $oDatabase,
        Config $oConfig,
        Util $oUtil,
        Cache $oCache,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        UserGroupFactory $oUserGroupFactory,
        FileHandler $oFileHandler,
        SetupHandler $oSetupHandler
    ) {
        $this->oPhp = $oPhp;
        $this->oWordpress = $oWordpress;
        $this->oDatabase = $oDatabase;
        $this->oConfig = $oConfig;
        $this->oUtil = $oUtil;
        $this->oCache = $oCache;
        $this->oObjectHandler = $oObjectHandler;
        $this->oAccessHandler = $oAccessHandler;
        $this->oUserGroupFactory = $oUserGroupFactory;
        $this->oFileHandler = $oFileHandler;
        $this->oSetupHandler = $oSetupHandler;
    }

    /**
     * Creates and returns a new admin controller.
     *
     * @return AdminController
     */
    public function createAdminController()
    {
        return new AdminController(
            $this->oPhp,
            $this->oWordpress,
            $this->oConfig,
            $this->oAccessHandler,
            $this->oFileHandler
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
            $this->oPhp,
            $this->oWordpress,
            $this->oConfig
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
            $this->oPhp,
            $this->oWordpress,
            $this->oConfig,
            $this->oDatabase,
            $this->oObjectHandler,
            $this->oAccessHandler
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
            $this->oPhp,
            $this->oWordpress,
            $this->oConfig,
            $this->oObjectHandler,
            $this->oFileHandler
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
            $this->oPhp,
            $this->oWordpress,
            $this->oConfig,
            $this->oDatabase,
            $this->oSetupHandler
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
            $this->oPhp,
            $this->oWordpress,
            $this->oConfig,
            $this->oAccessHandler,
            $this->oUserGroupFactory
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
            $this->oPhp,
            $this->oWordpress,
            $this->oConfig,
            $this->oDatabase,
            $this->oUtil,
            $this->oCache,
            $this->oObjectHandler,
            $this->oAccessHandler,
            $this->oFileHandler
        );
    }
}
