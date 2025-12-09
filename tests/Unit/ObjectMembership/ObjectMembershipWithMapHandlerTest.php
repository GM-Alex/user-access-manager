<?php
/**
 * ObjectMembershipWithMapHandlerTest.php
 *
 * The ObjectMembershipWithMapHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\ObjectMembership;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipWithMapHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class ObjectMembershipWithMapHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\ObjectMembership
 * @coversDefaultClass \UserAccessManager\ObjectMembership\ObjectMembershipWithMapHandler
 */
class ObjectMembershipWithMapHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @return MockObject|ObjectMembershipWithMapHandler
     * @throws ReflectionException
     */
    private function getStub(AssignmentInformationFactory $assignmentInformationFactory): MockObject|ObjectMembershipWithMapHandler
    {
        $stub = $this->getMockForAbstractClass(
            ObjectMembershipWithMapHandler::class,
            [],
            '',
            false
        );

        self::setValue($stub, 'generalObjectType', 'generalObjectType');
        self::setValue($stub, 'assignmentInformationFactory', $assignmentInformationFactory);

        $stub->expects($this->any())
            ->method('getMap')
            ->willReturn([
                ObjectMapHandler::TREE_MAP_PARENTS => [
                    'generalObjectType' => [
                        'objectIdFalse' => [
                            'parentObjectId' => 'parentObjectType',
                            'parentObjectIdFalse' => 'parentObjectType'
                        ]
                    ]
                ],
                ObjectMapHandler::TREE_MAP_CHILDREN => [
                    'generalObjectType' => [
                        'parentObjectId' => [
                            'firstObjectId' => 'generalObjectType',
                            'secondObjectId' => 'generalObjectType'
                        ],
                        'otherParentObjectId' => ['otherObjectId' => 'generalObjectType'],
                    ]
                ]
            ]);

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::getMembershipByMap()
     * @throws ReflectionException
     */
    public function testGetMembershipByMap()
    {
        $userGroup = $this->createMock(UserGroup::class);

        $userGroup->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $assignmentInformationMock = $this->createMock(AssignmentInformation::class);
        $returnCallback = $this->returnCallback(
            function ($generalObjectType, $objectId, &$assignmentInformation) use ($assignmentInformationMock) {
                $assignmentInformation = ($objectId === 'parentObjectId')
                    ? $assignmentInformationMock
                    : null;
                return !($objectId === 'objectIdFalse' || $objectId === 'parentObjectIdFalse');
            }
        );

        $userGroup->expects($this->exactly(2))
            ->method('isObjectMember')
            ->withConsecutive(
                ['generalObjectType', 'parentObjectId'],
                ['generalObjectType', 'parentObjectIdFalse']
            )
            ->will($returnCallback);

        $userGroup->expects($this->exactly(4))
            ->method('isObjectAssignedToGroup')
            ->withConsecutive(
                ['generalObjectType', 'objectIdFalse'],
                ['generalObjectType', 'objectId'],
                ['generalObjectType', 'objectIdFalse'],
                ['someObjectType', 'objectId']
            )
            ->will($returnCallback);

        $objectMembershipWithMapHandler = $this->getStub(
            $this->getExtendedAssignmentInformationFactory()
        );

        /**
         * @var AssignmentInformation $assignmentInformation
         */
        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [$userGroup, false, 'objectIdFalse', &$assignmentInformation]
        );
        self::assertFalse($result);
        self::assertNull($assignmentInformation);

        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [$userGroup, false, 'objectId', &$assignmentInformation]
        );
        self::assertTrue($result);
        self::assertEquals([], $assignmentInformation->getRecursiveMembership());

        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [$userGroup, true, 'objectIdFalse', &$assignmentInformation]
        );
        self::assertTrue($result);
        self::assertEquals(
            ['generalObjectType' => ['parentObjectId' => $assignmentInformationMock]],
            $assignmentInformation->getRecursiveMembership()
        );

        self::setValue($objectMembershipWithMapHandler, 'generalObjectType', 'someObjectType');
        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [$userGroup, true, 'objectId', &$assignmentInformation]
        );
        self::assertTrue($result);
        self::assertEquals([], $assignmentInformation->getRecursiveMembership());
    }

    /**
     * @group  unit
     * @covers ::getFullObjectsByMap()
     * @throws ReflectionException
     */
    public function testGetFullObjectsByMap()
    {
        /**
         * @var MockObject|AbstractUserGroup $userGroup
         */
        $userGroup = $this->createMock(AbstractUserGroup::class);

        $userGroup->expects($this->exactly(3))
            ->method('getAssignedObjects')
            ->withConsecutive(
                ['generalObjectType'],
                ['generalObjectType'],
                ['someObjectType']
            )
            ->will($this->returnValue(['parentObjectId' => $this->getAssignmentInformation('generalObjectType')]));

        $test = null;
        $userGroup->expects($this->exactly(2))
            ->method('isObjectMember')
            ->withConsecutive(
                ['generalObjectType', 'firstObjectId', &$test],
                ['generalObjectType', 'secondObjectId', &$test]
            )
            ->will($this->onConsecutiveCalls(true, false));

        $objectMembershipWithMapHandler = $this->getStub(
            $this->getExtendedAssignmentInformationFactory()
        );

        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getFullObjectsByMap',
            [$userGroup, false, 'generalObjectType']
        );
        self::assertEquals(['parentObjectId' => 'generalObjectType'], $result);

        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getFullObjectsByMap',
            [$userGroup, true, 'generalObjectType']
        );
        self::assertEquals(
            [
                'parentObjectId' => 'generalObjectType',
                'firstObjectId' => 'generalObjectType'
            ],
            $result
        );

        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getFullObjectsByMap',
            [$userGroup, true, 'someObjectType']
        );
        self::assertEquals(['parentObjectId' => 'generalObjectType'], $result);
    }
}
