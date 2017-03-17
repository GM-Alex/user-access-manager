<?php
/**
 * AdminUserGroupControllerTest.php
 *
 * The AdminUserGroupControllerTest unit test class file.
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

/**
 * Class AdminUserGroupControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminUserGroupControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::__construct()
     */
    public function testCanCreateInstance()
    {
        $oAdminUserGroupController = new AdminUserGroupController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminUserGroupController', $oAdminUserGroupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getUserGroup()
     */
    public function testGetUserGroup()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getUserGroups()
     */
    public function testGetUserGroups()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getRoleNames()
     */
    public function testGetRoleNames()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::insertUpdateUserGroupAction()
     */
    public function testInsertUpdateUserGroupAction()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::deleteUserGroupAction()
     */
    public function testDeleteUserGroupAction()
    {

    }
}
