<?php
/**
 * RoleObjectMembershipHandlerTest.php
 *
 * The RoleObjectMembershipHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\UserGroup\ObjectMembership;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\ObjectMembership\RoleMembershipHandler;

/**
 * Class RoleObjectMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\UserGroup\ObjectMembership
 * @coversDefaultClass \UserAccessManager\UserGroup\ObjectMembership\RoleMembershipHandler
 */
class RoleMembershipHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $roleMembershipHandler = new RoleMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $this->getUserGroup(1)
        );

        self::assertInstanceOf(RoleMembershipHandler::class, $roleMembershipHandler);
    }

    /**
     * @group  unit
     * @covers ::isMember()
     */
    public function testIsMember()
    {
        $userGroup = $this->getUserGroup(1);
        $userGroup->expects($this->exactly(2))
            ->method('isObjectAssignedToGroup')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'firstObjectId'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'secondObjectId']
            )
            ->will($this->returnCallback(function ($objectType, $objectId, &$assignmentInformation = null) {
                $assignmentInformation = $this->getAssignmentInformation($objectType.'|'.$objectId);
                return ($objectId === 'secondObjectId');
            }));

        $roleMembershipHandler = new RoleMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $userGroup
        );

        $assignmentInformation = null;
        self::assertFalse($roleMembershipHandler->isMember(false, 'firstObjectId', $assignmentInformation));
        self::assertNull($assignmentInformation);

        self::assertTrue($roleMembershipHandler->isMember(true, 'secondObjectId', $assignmentInformation));
        self::assertEquals(
            $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE.'|'.'secondObjectId'),
            $assignmentInformation
        );
    }

    /**
     * @group  unit
     * @covers ::getFullObjects()
     */
    public function testGetFullObjects()
    {
        $userGroup = $this->getUserGroup(1);
        $userGroup->expects($this->exactly(2))
            ->method('getAssignedObjects')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE],
                ['type']
            )
            ->will($this->onConsecutiveCalls(
                [
                    1 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE),
                    2 => $this->getAssignmentInformation('type')
                ],
                [
                    2 => $this->getAssignmentInformation('type')
                ]
            ));

        $roleMembershipHandler = new RoleMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $userGroup
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                2 => 'type'
            ],
            $roleMembershipHandler->getFullObjects(false)
        );
        self::assertEquals(
            [2 => 'type'],
            $roleMembershipHandler->getFullObjects(true, 'type')
        );
    }
}
