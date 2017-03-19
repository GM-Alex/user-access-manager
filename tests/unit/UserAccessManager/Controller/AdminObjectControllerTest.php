<?php
/**
 * AdminObjectControllerTest.php
 *
 * The AdminObjectControllerTest unit test class file.
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
 * Class AdminObjectControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminObjectControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::__construct()
     */
    public function testCanCreateInstance()
    {
        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminObjectController', $oAdminObjectController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::_setObjectInformation()
     *
     * @return AdminObjectController
     */
    public function testSetObjectInformation()
    {
        $aFullGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $aFilteredGroups = [
            1 => $this->getUserGroup(1)
        ];

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('objectType', 'objectId')
            ->will($this->returnValue($aFullGroups));

        $oAccessHandler->expects($this->once())
            ->method('getFilteredUserGroupsForObject')
            ->with('objectType', 'objectId')
            ->will($this->returnValue($aFilteredGroups));

        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::callMethod($oAdminObjectController, '_setObjectInformation', ['objectType', 'objectId']);

        self::assertAttributeEquals('objectType', '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals('objectId', '_sObjectId', $oAdminObjectController);
        self::assertAttributeEquals($aFullGroups, '_aObjectUserGroups', $oAdminObjectController);
        self::assertAttributeEquals($aFilteredGroups, '_aFilteredObjectUserGroups', $oAdminObjectController);
        self::assertAttributeEquals(1, '_iUserGroupDiff', $oAdminObjectController);

        return $oAdminObjectController;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectType()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetObjectType(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals('objectType', $oAdminObjectController->getObjectType());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectId()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetObjectId(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals('objectId', $oAdminObjectController->getObjectId());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetFullObjectUserGroups(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals(
            [1 => $this->getUserGroup(1), 2 => $this->getUserGroup(2)],
            $oAdminObjectController->getObjectUserGroups()
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getFilteredObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetObjectUserGroups(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals([1 => $this->getUserGroup(1)], $oAdminObjectController->getFilteredObjectUserGroups());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getUserGroupDiff()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetUserGroupDiff(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals(1, $oAdminObjectController->getUserGroupDiff());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getUserGroups()
     */
    public function testGetUserGroups()
    {
        $aUserGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3)
        ];

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($aUserGroups));

        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertEquals($aUserGroups, $oAdminObjectController->getUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $aUserGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->once())
            ->method('getFilteredUserGroups')
            ->will($this->returnValue($aUserGroups));

        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertEquals($aUserGroups, $oAdminObjectController->getFilteredUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::isCurrentUserAdmin()
     */
    public function testIsCurrentUserAdmin()
    {
        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(2))
            ->method('userIsAdmin')
            ->with('objectId')
            ->will($this->onConsecutiveCalls(false, true));

        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertFalse($oAdminObjectController->isCurrentUserAdmin());

        self::setValue($oAdminObjectController, '_sObjectType', ObjectHandler::GENERAL_USER_OBJECT_TYPE);
        self::assertFalse($oAdminObjectController->isCurrentUserAdmin());

        self::setValue($oAdminObjectController, '_sObjectId', 'objectId');
        self::assertFalse($oAdminObjectController->isCurrentUserAdmin());
        self::assertTrue($oAdminObjectController->isCurrentUserAdmin());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $oRoles = new \stdClass();
        $oRoles->role_names = 'roleNames';

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($oRoles));

        $oAdminObjectController = new AdminObjectController(
            $oWrapper,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals('roleNames', $oAdminObjectController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getAllObjectTypes()
     */
    public function testGetAllObjectTypes()
    {
        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue([1 => 1, 2 => 2]));

        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $oObjectHandler,
            $this->getAccessHandler()
        );

        self:self::assertEquals([1 => 1, 2 => 2], $oAdminObjectController->getAllObjectTypes());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkUserAccess()
     */
    public function testCheckUserAccess()
    {
        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertFalse($oAdminObjectController->checkUserAccess());
        self::assertTrue($oAdminObjectController->checkUserAccess());
    }
}
