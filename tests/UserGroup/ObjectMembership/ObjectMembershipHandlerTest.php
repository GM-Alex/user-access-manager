<?php
/**
 * ObjectMembershipHandlerTest.php
 *
 * The ObjectMembershipHandlerTest unit test class file.
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

use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\UserGroup\ObjectMembership\MissingObjectTypeException;
use UserAccessManager\UserGroup\ObjectMembership\ObjectMembershipHandler;

/**
 * Class ObjectMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\UserGroup\ObjectMembership
 * @coversDefaultClass \UserAccessManager\UserGroup\ObjectMembership\ObjectMembershipHandler
 */
class ObjectMembershipHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param AbstractUserGroup            $abstractUserGroup
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectMembershipHandler
     */
    private function getStub(
        AssignmentInformationFactory $assignmentInformationFactory,
        AbstractUserGroup $abstractUserGroup
    ) {
        $stub = $this->getMockForAbstractClass(
            ObjectMembershipHandler::class,
            [],
            '',
            false
        );

        self::setValue($stub, 'assignmentInformationFactory', $assignmentInformationFactory);
        self::setValue($stub, 'userGroup', $abstractUserGroup);

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectMembershipHandler = $this->getStub(
            $this->getAssignmentInformationFactory(),
            $this->getUserGroup(1)
        );

        self::setValue($objectMembershipHandler, 'objectType', 'type');
        $objectMembershipHandler->__construct(
            $this->getAssignmentInformationFactory(),
            $this->getUserGroup(1)
        );

        self::assertInstanceOf(ObjectMembershipHandler::class, $objectMembershipHandler);
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testUserGroupTypeException()
    {
        self::expectException(MissingObjectTypeException::class);

        $this->getMockForAbstractClass(
            ObjectMembershipHandler::class,
            [
                $this->getAssignmentInformationFactory(),
                $this->getUserGroup(1)
            ]
        );
    }

    /**
     * @group  unit
     * @covers ::assignRecursiveMembership()
     */
    public function testAssignRecursiveMembership()
    {
        $assignmentInformationOne = $this->getAssignmentInformation('typeOne');
        $assignmentInformationFactory = $this->getAssignmentInformationFactory();
        $assignmentInformationFactory->expects($this->once())
            ->method('createAssignmentInformation')
            ->will($this->returnValue($assignmentInformationOne));

        $objectMembershipHandler = $this->getStub(
            $assignmentInformationFactory,
            $this->getUserGroup(1)
        );

        /**
         * @var null|AssignmentInformation $resultAssignmentInformationOne
         */
        $resultAssignmentInformationOne = null;
        self::callMethod(
            $objectMembershipHandler,
            'assignRecursiveMembership',
            [&$resultAssignmentInformationOne, ['recursiveMembershipOne']]
        );
        self::assertEquals($assignmentInformationOne, $resultAssignmentInformationOne);
        self::assertEquals(['recursiveMembershipOne'], $resultAssignmentInformationOne->getRecursiveMembership());

        $assignmentInformationTwo = $this->getAssignmentInformation('typeTwo');
        $assignmentInformationTwo->expects($this->once())
            ->method('setRecursiveMembership')
            ->with(['recursiveMembershipTwo']);
        self::callMethod(
            $objectMembershipHandler,
            'assignRecursiveMembership',
            [&$assignmentInformationTwo, ['recursiveMembershipTwo']]
        );
        self::assertEquals(['recursiveMembershipTwo'], $assignmentInformationTwo->getRecursiveMembership());
    }

    /**
     * @group  unit
     * @covers ::getSimpleAssignedObjects()
     */
    public function testGetSimpleAssignedObjects()
    {
        $userGroup = $this->getUserGroup(1);
        $userGroup->expects($this->once())
            ->method('getAssignedObjects')
            ->with('objectType')
            ->will($this->returnValue([
                1 => $this->getAssignmentInformation('objectType'),
                2 => $this->getAssignmentInformation('objectType'),
                3 => $this->getAssignmentInformation('objectType')
            ]));

        $objectMembershipHandler = $this->getStub(
            $this->getAssignmentInformationFactory(),
            $userGroup
        );

        $result = self::callMethod($objectMembershipHandler, 'getSimpleAssignedObjects', ['objectType']);
        self::assertEquals(
            [
                1 => 'objectType',
                2 => 'objectType',
                3 => 'objectType'
            ],
            $result
        );
    }
}
