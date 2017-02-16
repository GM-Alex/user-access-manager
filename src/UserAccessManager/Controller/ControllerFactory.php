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
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ControllerFactory
 *
 * @package UserAccessManager\Controller
 */
class ControllerFactory
{
    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * @var Cache
     */
    protected $_oCache;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $_oUserGroupFactory;

    /**
     * @var FileHandler
     */
    protected $_oFileHandler;

    /**
     * @var SetupHandler
     */
    protected $_oSetupHandler;

    /**
     * ControllerFactory constructor.
     *
     * @param Wordpress        $oWrapper
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
        Wordpress $oWrapper,
        Database $oDatabase,
        Config $oConfig,
        Util $oUtil,
        Cache $oCache,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        UserGroupFactory $oUserGroupFactory,
        FileHandler $oFileHandler,
        SetupHandler $oSetupHandler
    )
    {
        $this->_oWrapper = $oWrapper;
        $this->_oDatabase = $oDatabase;
        $this->_oConfig = $oConfig;
        $this->_oUtil = $oUtil;
        $this->_oCache = $oCache;
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oUserGroupFactory = $oUserGroupFactory;
        $this->_oFileHandler = $oFileHandler;
        $this->_oSetupHandler = $oSetupHandler;
    }

    /**
     * Creates and returns a new admin controller.
     *
     * @return AdminController
     */
    public function createAdminController()
    {
        return new AdminController($this->_oWrapper, $this->_oConfig, $this->_oAccessHandler, $this->_oFileHandler);
    }

    /**
     * Creates and returns a new admin about controller.
     *
     * @return AdminAboutController
     */
    public function createAdminAboutController()
    {
        return new AdminAboutController($this->_oWrapper, $this->_oConfig);
    }

    /**
     * Creates and returns a new admin about controller.
     *
     * @return AdminObjectController
     */
    public function createAdminObjectController()
    {
        return new AdminObjectController(
            $this->_oWrapper,
            $this->_oConfig,
            $this->_oDatabase,
            $this->_oObjectHandler,
            $this->_oAccessHandler
        );
    }

    /**
     * Creates and returns a new admin setup controller.
     *
     * @return AdminSettingsController
     */
    public function createAdminSettingController()
    {
        return new AdminSettingsController($this->_oWrapper, $this->_oConfig, $this->_oFileHandler);
    }

    /**
     * Creates and returns a new admin setup controller.
     *
     * @return AdminSetupController
     */
    public function createAdminSetupController()
    {
        return new AdminSetupController($this->_oWrapper, $this->_oConfig, $this->_oDatabase, $this->_oSetupHandler);
    }

    /**
     * Creates and returns a new admin user group controller.
     *
     * @return AdminUserGroupController
     */
    public function createAdminUserGroupController()
    {
        return new AdminUserGroupController($this->_oWrapper, $this->_oConfig, $this->_oAccessHandler, $this->_oUserGroupFactory);
    }

    /**
     * Creates and returns a new frontend controller.
     *
     * @return FrontendController
     */
    public function createFrontendController()
    {
        return new FrontendController(
            $this->_oWrapper,
            $this->_oConfig,
            $this->_oDatabase,
            $this->_oUtil,
            $this->_oCache,
            $this->_oObjectHandler,
            $this->_oAccessHandler,
            $this->_oFileHandler
        );
    }
}