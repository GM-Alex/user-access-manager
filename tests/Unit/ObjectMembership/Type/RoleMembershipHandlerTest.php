<?php

namespace UserAccessManager\Tests\Unit\ObjectMembership\Type;

use Exception;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\Type\RoleMembershipHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use WP_Roles;

/**
 * @coversDefaultClass \UserAccessManager\ObjectMembership\Type\RoleMembershipHandler
 */
class RoleMembershipHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @throws Exception
     */
    public function testCanCreateInstance()
    {
        $assignmentInformationFactory = $this->getAssignmentInformationFactory();
        $roleMembershipHandler = new RoleMembershipHandler(
            $assignmentInformationFactory,
            $this->getWordpress()
        );

        self::assertInstanceOf(RoleMembershipHandler::class, $roleMembershipHandler);
        // The parent constructor must initialise the inherited factory property.
        self::assertSame(
            $assignmentInformationFactory,
            self::getValue($roleMembershipHandler, 'assignmentInformationFactory')
        );
    }

    /**
     * @group  unit
     * @covers ::getObjectName()
     * @throws Exception
     */
    public function testGetObjectName()
    {
        $roles = $this->getMockBuilder(WP_Roles::class)->allowMockingUnknownTypes()->getMock();
        $roles->role_names = [1 => 'roleOne'];

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getRoles')
            ->will($this->returnValue($roles));

        $roleMembershipHandler = new RoleMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $wordpress
        );

        $typeName = 'someType';
        self::assertEquals(-1, $roleMembershipHandler->getObjectName(-1, $typeName));
        self::assertEquals('_role_', $typeName);

        $typeName = 'someType';
        self::assertEquals('roleOne', $roleMembershipHandler->getObjectName(1, $typeName));
        self::assertEquals('_role_', $typeName);
    }

    /**
     * @group  unit
     * @covers ::isMember()
     * @throws Exception
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
                $assignmentInformation = $this->getAssignmentInformation($objectType . '|' . $objectId);
                return ($objectId === 'secondObjectId');
            }));

        $roleMembershipHandler = new RoleMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $this->getWordpress()
        );

        $assignmentInformation = null;
        self::assertFalse($roleMembershipHandler->isMember($userGroup, false, 'firstObjectId', $assignmentInformation));
        self::assertNull($assignmentInformation);

        self::assertTrue($roleMembershipHandler->isMember($userGroup, true, 'secondObjectId', $assignmentInformation));
        self::assertEquals(
            $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE . '|' . 'secondObjectId'),
            $assignmentInformation
        );
    }

    /**
     * @group  unit
     * @covers ::getFullObjects()
     * @throws Exception
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
            $this->getWordpress()
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                2 => 'type'
            ],
            $roleMembershipHandler->getFullObjects($userGroup, false)
        );
        self::assertEquals(
            [2 => 'type'],
            $roleMembershipHandler->getFullObjects($userGroup, true, 'type')
        );
    }
}
