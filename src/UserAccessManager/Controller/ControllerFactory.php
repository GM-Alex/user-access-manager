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
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ControllerFactory
 *
 * @package UserAccessManager\Controller
 */
class ControllerFactory
{
    public function __construct(
        Wordpress $oWrapper,
        Database $oDatabase,
        Config $oConfig,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        UserGroupFactory $oUserGroupFactory,
        FileHandler $oFileHandler
    )
    {
        $this->_oWrapper = $oWrapper;
        $this->_oDatabase = $oDatabase;
        $this->_oConfig = $oConfig;
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oUserGroupFactory = $oUserGroupFactory;
        $this->_oFileHandler = $oFileHandler;
    }

    /**
     * Creates and returns a new admin about controller.
     *
     * @return AdminAboutController
     */
    public function createAdminAboutController()
    {
        return new AdminAboutController($this->_oWrapper);
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
            $this->_oDatabase,
            $this->_oConfig,
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
     * @param UserAccessManager $oUserAccessManager
     *
     * @return AdminSetupController
     */
    public function createAdminSetupController(UserAccessManager $oUserAccessManager)
    {
        return new AdminSetupController($this->_oWrapper, $oUserAccessManager);
    }

    /**
     * Creates and returns a new admin user group controller.
     *
     * @return AdminUserGroupController
     */
    public function createAdminUserGroupController()
    {
        return new AdminUserGroupController($this->_oWrapper, $this->_oAccessHandler, $this->_oUserGroupFactory);
    }
}