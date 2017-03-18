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

use UserAccessManager\ObjectHandler\ObjectHandler;

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
        $oUserGroup = $this->getUserGroup(1);

        $oUserGroupFactory = $this->getUserGroupFactory();
        $oUserGroupFactory->expects($this->once())
            ->method('createUserGroup')
            ->with(1)
            ->will($this->returnValue($oUserGroup));

        $oAdminUserGroupController = new AdminUserGroupController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $oUserGroupFactory
        );

        $_GET['userGroupId'] = 1;
        self::assertEquals($oUserGroup, $oAdminUserGroupController->getUserGroup());
        self::assertEquals($oUserGroup, $oAdminUserGroupController->getUserGroup());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getUserGroups()
     */
    public function testGetUserGroups()
    {
        $aUserGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($aUserGroups));

        $oAdminUserGroupController = new AdminUserGroupController(
            $this->getWrapper(),
            $this->getConfig(),
            $oAccessHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals($aUserGroups, $oAdminUserGroupController->getUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $oRoles = new \stdClass();
        $oRoles->role_names = 'roleNames';

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($oRoles));

        $oAdminUserGroupController = new AdminUserGroupController(
            $oWrapper,
            $this->getConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals('roleNames', $oAdminUserGroupController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::insertUpdateUserGroupAction()
     */
    public function testInsertUpdateUserGroupAction()
    {
        $_GET[AdminUserGroupController::INSERT_UPDATE_GROUP_NONCE.'Nonce'] = 'insertUpdateNonce';

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(4))
            ->method('verifyNonce')
            ->with('insertUpdateNonce')
            ->will($this->returnValue(true));

        $oUserGroup = $this->getUserGroup(1);

        $oUserGroup->expects($this->exactly(3))
            ->method('setGroupName')
            ->with('userGroupNameValue');

        $oUserGroup->expects($this->exactly(3))
            ->method('setGroupDesc')
            ->with('userGroupDescriptionValue');

        $oUserGroup->expects($this->exactly(3))
            ->method('setReadAccess')
            ->with('readAccessValue');

        $oUserGroup->expects($this->exactly(3))
            ->method('setWriteAccess')
            ->with('writeAccessValue');

        $oUserGroup->expects($this->exactly(3))
            ->method('setIpRange')
            ->with('ipRangeValue');

        $oUserGroup->expects($this->exactly(3))
            ->method('removeObject')
            ->with(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        $oUserGroup->expects($this->exactly(6))
            ->method('addObject')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo']
            );

        $oUserGroup->expects($this->exactly(3))
            ->method('save')
            ->will($this->onConsecutiveCalls(false, true, true));

        $oUserGroupFactory = $this->getUserGroupFactory();
        $oUserGroupFactory->expects($this->exactly(4))
            ->method('createUserGroup')
            ->withConsecutive([null], [null], [null], [1])
            ->will($this->returnValue($oUserGroup));

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(2))
            ->method('addUserGroup')
            ->with($oUserGroup);

        $oAdminUserGroupController = new AdminUserGroupController(
            $oWrapper,
            $this->getConfig(),
            $oAccessHandler,
            $oUserGroupFactory
        );

        $oAdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, '_sUpdateMessage', $oAdminUserGroupController);

        $_POST['userGroupName'] = 'userGroupNameValue';
        $_POST['userGroupDescription'] = 'userGroupDescriptionValue';
        $_POST['readAccess'] = 'readAccessValue';
        $_POST['writeAccess'] = 'writeAccessValue';
        $_POST['ipRange'] = 'ipRangeValue';
        $_POST['roles'] = ['roleOne', 'roleTwo'];

        $oAdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, '_sUpdateMessage', $oAdminUserGroupController);

        $oAdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_ADDED, '_sUpdateMessage', $oAdminUserGroupController);

        $_POST['userGroupId'] = 1;

        $oAdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_ACCESS_GROUP_EDIT_SUCCESS, '_sUpdateMessage', $oAdminUserGroupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::deleteUserGroupAction()
     */
    public function testDeleteUserGroupAction()
    {
        $_GET[AdminUserGroupController::DELETE_GROUP_NONCE.'Nonce'] = 'deleteNonce';
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('verifyNonce')
            ->with('deleteNonce')
            ->will($this->returnValue(true));

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(2))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [2]);

        $oAdminUserGroupController = new AdminUserGroupController(
            $oWrapper,
            $this->getConfig(),
            $oAccessHandler,
            $this->getUserGroupFactory()
        );

        $_POST['delete'] = [1, 2];
        $oAdminUserGroupController->deleteUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_DELETE_GROUP, '_sUpdateMessage', $oAdminUserGroupController);
    }
}
