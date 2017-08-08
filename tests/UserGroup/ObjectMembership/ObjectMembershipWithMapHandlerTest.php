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
namespace UserAccessManager\Tests\UserGroup\ObjectMembership;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\UserGroup\ObjectMembership\ObjectMembershipWithMapHandler;

/**
 * Class ObjectMembershipWithMapHandlerTest
 *
 * @package UserAccessManager\Tests\UserGroup\ObjectMembership
 * @coversDefaultClass \UserAccessManager\UserGroup\ObjectMembership\ObjectMembershipWithMapHandler
 */
class ObjectMembershipWithMapHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param AbstractUserGroup            $abstractUserGroup
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectMembershipWithMapHandler
     */
    private function getStub(
        AssignmentInformationFactory $assignmentInformationFactory,
        AbstractUserGroup $abstractUserGroup
    ) {
        $stub = $this->getMockForAbstractClass(
            ObjectMembershipWithMapHandler::class,
            [],
            '',
            false
        );

        self::setValue($stub, 'objectType', 'objectType');
        self::setValue($stub, 'assignmentInformationFactory', $assignmentInformationFactory);
        self::setValue($stub, 'userGroup', $abstractUserGroup);

        $stub->expects($this->any())
            ->method('getMap')
            ->willReturn([
                ObjectHandler::TREE_MAP_PARENTS => [
                    'objectType' => [
                        'objectIdFalse' => [
                            'parentObjectId' => 'parentObjectType',
                            'parentObjectIdFalse' => 'parentObjectType'
                        ]
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    'objectType' => [
                        'parentObjectId' => [
                            'firstObjectId' => 'objectType',
                            'secondObjectId' => 'objectType'
                        ],
                        'otherParentObjectId'  => ['otherObjectId' => 'objectType'],
                    ]
                ]
            ]);

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::getMembershipByMap()
     */
    public function testGetMembershipByMap()
    {
        $userGroup = $this->getUserGroup(1);
        $userGroup->expects($this->exactly(6))
            ->method('isObjectAssignedToGroup')
            ->withConsecutive(
                ['objectType', 'objectIdFalse'],
                ['objectType', 'objectId'],
                ['objectType', 'parentObjectId'],
                ['objectType', 'parentObjectIdFalse'],
                ['objectType', 'objectIdFalse'],
                ['someObjectType', 'objectId']
            )
            ->will($this->returnCallback(
                function ($objectType, $objectId, &$assignmentInformation) {
                    $assignmentInformation = ($objectId === 'parentObjectId') ? 'rmInfo' : null;
                    return ($objectId === 'objectIdFalse' || $objectId === 'parentObjectIdFalse') ? false : true;
                }
            ));

        $objectMembershipWithMapHandler = $this->getStub(
            $this->getExtendedAssignmentInformationFactory(),
            $userGroup
        );

        /**
         * @var AssignmentInformation $assignmentInformation
         */
        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [false, 'objectIdFalse', &$assignmentInformation]
        );
        self::assertFalse($result);
        self::assertNull($assignmentInformation);

        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [false, 'objectId', &$assignmentInformation]
        );
        self::assertTrue($result);
        self::assertEquals([], $assignmentInformation->getRecursiveMembership());

        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [true, 'objectIdFalse', &$assignmentInformation]
        );
        self::assertTrue($result);
        self::assertEquals(
            ['objectType' => ['parentObjectId' => 'rmInfo']],
            $assignmentInformation->getRecursiveMembership()
        );

        self::setValue($objectMembershipWithMapHandler, 'objectType', 'someObjectType');
        $assignmentInformation = null;
        $result = self::callMethod(
            $objectMembershipWithMapHandler,
            'getMembershipByMap',
            [true, 'objectId', &$assignmentInformation]
        );
        self::assertTrue($result);
        self::assertEquals([], $assignmentInformation->getRecursiveMembership());
    }

    /**
     * @group  unit
     * @covers ::getFullObjectsByMap()
     */
    public function testGetFullObjectsByMap()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractUserGroup $userGroup
         */
        $userGroup = $this->createMock(AbstractUserGroup::class);

        $userGroup->expects($this->exactly(3))
            ->method('getAssignedObjects')
            ->withConsecutive(
                ['objectType'],
                ['objectType'],
                ['someObjectType']
            )
            ->will($this->returnValue(['parentObjectId' => $this->getAssignmentInformation('objectType')]));

        $test = null;
        $userGroup->expects($this->exactly(2))
            ->method('isObjectMember')
            ->withConsecutive(
                ['objectType', 'firstObjectId', &$test],
                ['objectType', 'secondObjectId', &$test]
            )
            ->will($this->onConsecutiveCalls(true, false));

        $objectMembershipWithMapHandler = $this->getStub(
            $this->getExtendedAssignmentInformationFactory(),
            $userGroup
        );

        $result = self::callMethod($objectMembershipWithMapHandler, 'getFullObjectsByMap', [false, 'objectType']);
        self::assertEquals(['parentObjectId' => 'objectType'], $result);

        $result = self::callMethod($objectMembershipWithMapHandler, 'getFullObjectsByMap', [true, 'objectType']);
        self::assertEquals(
            [
                'parentObjectId' => 'objectType',
                'firstObjectId' => 'objectType'
            ],
            $result
        );

        $result = self::callMethod($objectMembershipWithMapHandler, 'getFullObjectsByMap', [true, 'someObjectType']);
        self::assertEquals(['parentObjectId' => 'objectType'], $result);
    }
}
