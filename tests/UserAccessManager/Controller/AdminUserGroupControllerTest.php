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
 * @version   SVN: $id$
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
        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminUserGroupController', $adminUserGroupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getUserGroup()
     */
    public function testGetUserGroup()
    {
        $userGroup = $this->getUserGroup(1);

        $userGroupFactory = $this->getUserGroupFactory();
        $userGroupFactory->expects($this->once())
            ->method('createUserGroup')
            ->with(1)
            ->will($this->returnValue($userGroup));

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $userGroupFactory
        );

        $_GET['userGroupId'] = 1;
        self::assertEquals($userGroup, $adminUserGroupController->getUserGroup());
        self::assertEquals($userGroup, $adminUserGroupController->getUserGroup());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getUserGroups()
     */
    public function testGetUserGroups()
    {
        $userGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($userGroups));

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals($userGroups, $adminUserGroupController->getUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $roles = new \stdClass();
        $roles->role_names = 'roleNames';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals('roleNames', $adminUserGroupController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::insertUpdateUserGroupAction()
     */
    public function testInsertUpdateUserGroupAction()
    {
        $_GET[AdminUserGroupController::INSERT_UPDATE_GROUP_NONCE.'Nonce'] = 'insertUpdateNonce';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('verifyNonce')
            ->with('insertUpdateNonce')
            ->will($this->returnValue(true));

        $userGroup = $this->getUserGroup(1);

        $userGroup->expects($this->exactly(3))
            ->method('setName')
            ->with('userGroupNameValue');

        $userGroup->expects($this->exactly(3))
            ->method('setDescription')
            ->with('userGroupDescriptionValue');

        $userGroup->expects($this->exactly(3))
            ->method('setReadAccess')
            ->with('readAccessValue');

        $userGroup->expects($this->exactly(3))
            ->method('setWriteAccess')
            ->with('writeAccessValue');

        $userGroup->expects($this->exactly(3))
            ->method('setIpRange')
            ->with('ipRangeValue');

        $userGroup->expects($this->exactly(2))
            ->method('removeObject')
            ->with(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        $userGroup->expects($this->exactly(4))
            ->method('addObject')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo']
            );

        $userGroup->expects($this->exactly(3))
            ->method('save')
            ->will($this->onConsecutiveCalls(false, true, true));

        $userGroupFactory = $this->getUserGroupFactory();
        $userGroupFactory->expects($this->exactly(4))
            ->method('createUserGroup')
            ->withConsecutive([null], [null], [null], [1])
            ->will($this->returnValue($userGroup));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(2))
            ->method('addUserGroup')
            ->with($userGroup);

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $accessHandler,
            $userGroupFactory
        );

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'updateMessage', $adminUserGroupController);

        $_POST['userGroupName'] = 'userGroupNameValue';
        $_POST['userGroupDescription'] = 'userGroupDescriptionValue';
        $_POST['readAccess'] = 'readAccessValue';
        $_POST['writeAccess'] = 'writeAccessValue';
        $_POST['ipRange'] = 'ipRangeValue';
        $_POST['roles'] = ['roleOne', 'roleTwo'];

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'updateMessage', $adminUserGroupController);

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_ADDED, 'updateMessage', $adminUserGroupController);

        $_POST['userGroupId'] = 1;

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_ACCESS_GROUP_EDIT_SUCCESS, 'updateMessage', $adminUserGroupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminUserGroupController::deleteUserGroupAction()
     */
    public function testDeleteUserGroupAction()
    {
        $_GET[AdminUserGroupController::DELETE_GROUP_NONCE.'Nonce'] = 'deleteNonce';
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('verifyNonce')
            ->with('deleteNonce')
            ->will($this->returnValue(true));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(2))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [2]);

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        $_POST['delete'] = [1, 2];
        $adminUserGroupController->deleteUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_DELETE_GROUP, 'updateMessage', $adminUserGroupController);
    }
}
