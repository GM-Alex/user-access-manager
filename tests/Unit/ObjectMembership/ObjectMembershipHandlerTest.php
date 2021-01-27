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

namespace UserAccessManager\Tests\Unit\ObjectMembership;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\ObjectMembership\MissingObjectTypeException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;

/**
 * Class ObjectMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\ObjectMembership
 * @coversDefaultClass \UserAccessManager\ObjectMembership\ObjectMembershipHandler
 */
class ObjectMembershipHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @return MockObject|ObjectMembershipHandler
     * @throws ReflectionException
     */
    private function getStub(
        AssignmentInformationFactory $assignmentInformationFactory
    )
    {
        $stub = $this->getMockForAbstractClass(
            ObjectMembershipHandler::class,
            [],
            '',
            false
        );

        self::setValue($stub, 'assignmentInformationFactory', $assignmentInformationFactory);

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     * @throws Exception
     */
    public function testCanCreateInstance()
    {
        $objectMembershipHandler = $this->getStub(
            $this->getAssignmentInformationFactory()
        );

        self::setValue($objectMembershipHandler, 'generalObjectType', 'type');
        $objectMembershipHandler->__construct(
            $this->getAssignmentInformationFactory()
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
                $this->getAssignmentInformationFactory()
            ]
        );
    }

    /**
     * @group  unit
     * @covers ::getGeneralObjectType()
     * @throws ReflectionException
     */
    public function testGetGeneralObjectType()
    {
        $objectMembershipHandler = $this->getStub(
            $this->getAssignmentInformationFactory()
        );
        self::setValue($objectMembershipHandler, 'generalObjectType', 'type');

        self::assertEquals('type', $objectMembershipHandler->getGeneralObjectType());
    }

    /**
     * @group  unit
     * @covers ::getHandledObjects()
     * @throws ReflectionException
     */
    public function testGetHandledObjects()
    {
        $objectMembershipHandler = $this->getStub(
            $this->getAssignmentInformationFactory()
        );
        self::setValue($objectMembershipHandler, 'generalObjectType', 'type');

        self::assertEquals(['type' => 'type'], $objectMembershipHandler->getHandledObjects());
    }

    /**
     * @group  unit
     * @covers ::handlesObject()
     * @throws ReflectionException
     */
    public function testHandlesObject()
    {
        $objectMembershipHandler = $this->getStub(
            $this->getAssignmentInformationFactory()
        );
        self::setValue($objectMembershipHandler, 'generalObjectType', 'type');

        self::assertTrue($objectMembershipHandler->handlesObject('type'));
        self::assertFalse($objectMembershipHandler->handlesObject('invalid'));
    }

    /**
     * @group  unit
     * @covers ::assignRecursiveMembership()
     * @throws ReflectionException
     */
    public function testAssignRecursiveMembership()
    {
        $assignmentInformationOne = $this->getAssignmentInformation('typeOne');
        $assignmentInformationFactory = $this->getAssignmentInformationFactory();
        $assignmentInformationFactory->expects($this->once())
            ->method('createAssignmentInformation')
            ->will($this->returnValue($assignmentInformationOne));

        $objectMembershipHandler = $this->getStub(
            $assignmentInformationFactory
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
            ->with(['recursiveMembershipTwo'])
            ->will($this->returnValue($assignmentInformationTwo));
        self::callMethod(
            $objectMembershipHandler,
            'assignRecursiveMembership',
            [&$assignmentInformationTwo, ['recursiveMembershipTwo']]
        );
        self::assertEquals(['recursiveMembershipTwo'], $assignmentInformationTwo->getRecursiveMembership());
    }


    /**
     * @group  unit
     * @covers ::checkAccessWithRecursiveMembership()
     * @throws ReflectionException
     */
    public function testCheckAccessWithRecursiveMembership()
    {
        $objectMembershipHandler = $this->getStub(
            $this->getAssignmentInformationFactory()
        );

        /**
         * @var null|AssignmentInformation $resultAssignmentInformationOne
         */
        $assignmentInformationOne = null;
        $result = self::callMethod(
            $objectMembershipHandler,
            'checkAccessWithRecursiveMembership',
            [false, [], &$assignmentInformationOne]
        );
        self::assertFalse($result);
        self::assertNull($assignmentInformationOne);

        $assignmentInformationTwo = $this->getAssignmentInformation('typeTwo');
        $assignmentInformationTwo->expects($this->once())
            ->method('setRecursiveMembership')
            ->with([])
            ->will($this->returnValue($assignmentInformationTwo));
        $resultAssignmentInformationOne = null;
        $result = self::callMethod(
            $objectMembershipHandler,
            'checkAccessWithRecursiveMembership',
            [true, [], &$assignmentInformationTwo]
        );
        self::assertTrue($result);
        self::assertNotNull($assignmentInformationTwo);
        self::assertEquals([], $assignmentInformationTwo->getRecursiveMembership());

        $assignmentInformationThree = $this->getAssignmentInformation('typeThree');
        $assignmentInformationThree->expects($this->once())
            ->method('setRecursiveMembership')
            ->with(['membership'])
            ->will($this->returnValue($assignmentInformationThree));
        $resultAssignmentInformationOne = null;
        $result = self::callMethod(
            $objectMembershipHandler,
            'checkAccessWithRecursiveMembership',
            [false, ['membership'], &$assignmentInformationThree]
        );
        self::assertTrue($result);
        self::assertNotNull($assignmentInformationThree);
        self::assertEquals(['membership'], $assignmentInformationThree->getRecursiveMembership());
    }

    /**
     * @group  unit
     * @covers ::getSimpleAssignedObjects()
     * @throws ReflectionException
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
            $this->getAssignmentInformationFactory()
        );

        $result = self::callMethod($objectMembershipHandler, 'getSimpleAssignedObjects', [$userGroup, 'objectType']);
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
