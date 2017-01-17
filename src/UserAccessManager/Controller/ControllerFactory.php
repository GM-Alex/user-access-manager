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
    public function __construct(Wordpress $oWrapper, AccessHandler $oAccessHandler, UserGroupFactory $oUserGroupFactory)
    {
        $this->_oWrapper = $oWrapper;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oUserGroupFactory = $oUserGroupFactory;
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