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
        self::assertAttributeEquals($aFullGroups, '_aFullObjectUserGroups', $oAdminObjectController);
        self::assertAttributeEquals($aFilteredGroups, '_aObjectUserGroups', $oAdminObjectController);
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

    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getFullObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetFullObjectUserGroups(AdminObjectController $oAdminObjectController)
    {

    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetObjectUserGroups(AdminObjectController $oAdminObjectController)
    {

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

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getUserGroups()
     */
    public function testGetUserGroups()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::isCurrentUserAdmin()
     */
    public function testIsCurrentUserAdmin()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRoleNames()
     */
    public function testGetRoleNames()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getAllObjectTypes()
     */
    public function testGetAllObjectTypes()
    {

    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkUserAccess()
     */
    public function testCheckUserAccess()
    {

    }
}
