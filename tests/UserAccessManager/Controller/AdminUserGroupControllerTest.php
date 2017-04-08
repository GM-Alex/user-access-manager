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
use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class AdminUserGroupControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminUserGroupControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::__construct()
     */
    public function testCanCreateInstance()
    {
        $AdminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminUserGroupController', $AdminUserGroupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getUserGroup()
     */
    public function testGetUserGroup()
    {
        $UserGroup = $this->getUserGroup(1);

        $UserGroupFactory = $this->getUserGroupFactory();
        $UserGroupFactory->expects($this->once())
            ->method('createUserGroup')
            ->with(1)
            ->will($this->returnValue($UserGroup));

        $AdminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $UserGroupFactory
        );

        $_GET['userGroupId'] = 1;
        self::assertEquals($UserGroup, $AdminUserGroupController->getUserGroup());
        self::assertEquals($UserGroup, $AdminUserGroupController->getUserGroup());
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

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($aUserGroups));

        $AdminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $AccessHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals($aUserGroups, $AdminUserGroupController->getUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $Roles = new \stdClass();
        $Roles->role_names = 'roleNames';

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($Roles));

        $AdminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals('roleNames', $AdminUserGroupController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::insertUpdateUserGroupAction()
     */
    public function testInsertUpdateUserGroupAction()
    {
        $_GET[AdminUserGroupController::INSERT_UPDATE_GROUP_NONCE.'Nonce'] = 'insertUpdateNonce';

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(4))
            ->method('verifyNonce')
            ->with('insertUpdateNonce')
            ->will($this->returnValue(true));

        $UserGroup = $this->getUserGroup(1);

        $UserGroup->expects($this->exactly(3))
            ->method('setName')
            ->with('userGroupNameValue');

        $UserGroup->expects($this->exactly(3))
            ->method('setDescription')
            ->with('userGroupDescriptionValue');

        $UserGroup->expects($this->exactly(3))
            ->method('setReadAccess')
            ->with('readAccessValue');

        $UserGroup->expects($this->exactly(3))
            ->method('setWriteAccess')
            ->with('writeAccessValue');

        $UserGroup->expects($this->exactly(3))
            ->method('setIpRange')
            ->with('ipRangeValue');

        $UserGroup->expects($this->exactly(3))
            ->method('removeObject')
            ->with(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        $UserGroup->expects($this->exactly(6))
            ->method('addObject')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo']
            );

        $UserGroup->expects($this->exactly(3))
            ->method('save')
            ->will($this->onConsecutiveCalls(false, true, true));

        $UserGroupFactory = $this->getUserGroupFactory();
        $UserGroupFactory->expects($this->exactly(4))
            ->method('createUserGroup')
            ->withConsecutive([null], [null], [null], [1])
            ->will($this->returnValue($UserGroup));

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(2))
            ->method('addUserGroup')
            ->with($UserGroup);

        $AdminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $AccessHandler,
            $UserGroupFactory
        );

        $AdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'sUpdateMessage', $AdminUserGroupController);

        $_POST['userGroupName'] = 'userGroupNameValue';
        $_POST['userGroupDescription'] = 'userGroupDescriptionValue';
        $_POST['readAccess'] = 'readAccessValue';
        $_POST['writeAccess'] = 'writeAccessValue';
        $_POST['ipRange'] = 'ipRangeValue';
        $_POST['roles'] = ['roleOne', 'roleTwo'];

        $AdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'sUpdateMessage', $AdminUserGroupController);

        $AdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_ADDED, 'sUpdateMessage', $AdminUserGroupController);

        $_POST['userGroupId'] = 1;

        $AdminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_ACCESS_GROUP_EDIT_SUCCESS, 'sUpdateMessage', $AdminUserGroupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::deleteUserGroupAction()
     */
    public function testDeleteUserGroupAction()
    {
        $_GET[AdminUserGroupController::DELETE_GROUP_NONCE.'Nonce'] = 'deleteNonce';
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('verifyNonce')
            ->with('deleteNonce')
            ->will($this->returnValue(true));

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(2))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [2]);

        $AdminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $AccessHandler,
            $this->getUserGroupFactory()
        );

        $_POST['delete'] = [1, 2];
        $AdminUserGroupController->deleteUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_DELETE_GROUP, 'sUpdateMessage', $AdminUserGroupController);
    }
}
