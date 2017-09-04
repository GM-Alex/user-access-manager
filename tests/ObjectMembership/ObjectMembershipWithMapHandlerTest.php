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
namespace UserAccessManager\Tests\ObjectMembership;

use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\ObjectMembership\ObjectMembershipWithMapHandler;

/**
 * Class ObjectMembershipWithMapHandlerTest
 *
 * @package UserAccessManager\Tests\ObjectMembership
 * @coversDefaultClass \UserAccessManager\ObjectMembership\ObjectMembershipWithMapHandler
 */
class ObjectMembershipWithMapHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @param AssignmentInformationFactory $assignmentInformationFactory
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectMembershipWithMapHandler
     */
    private function getStub(AssignmentInformationFactory $assignmentInformationFactory)
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
                        'otherParentObjectId'  => ['otherObjectId' => 'generalObjectType'],
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
                ['generalObjectType', 'objectIdFalse'],
                ['generalObjectType', 'objectId'],
                ['generalObjectType', 'parentObjectId'],
                ['generalObjectType', 'parentObjectIdFalse'],
                ['generalObjectType', 'objectIdFalse'],
                ['someObjectType', 'objectId']
            )
            ->will($this->returnCallback(
                function ($generalObjectType, $objectId, &$assignmentInformation) {
                    $assignmentInformation = ($objectId === 'parentObjectId') ? 'rmInfo' : null;
                    return ($objectId === 'objectIdFalse' || $objectId === 'parentObjectIdFalse') ? false : true;
                }
            ));

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
            ['generalObjectType' => ['parentObjectId' => 'rmInfo']],
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
