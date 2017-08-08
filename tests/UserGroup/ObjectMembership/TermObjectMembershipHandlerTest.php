<?php
/**
 * TermMembershipHandlerTest.php
 *
 * The TermMembershipHandlerTest unit test class file.
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
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\ObjectMembership\TermMembershipHandler;

/**
 * Class TermMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\UserGroup\ObjectMembership
 * @coversDefaultClass \UserAccessManager\UserGroup\ObjectMembership\TermMembershipHandler
 */
class TermObjectMembershipHandlerTest extends ObjectMembershipHandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $termMembershipHandler = new TermMembershipHandler(
            $this->getObjectHandler(),
            $this->getAssignmentInformationFactory(),
            $this->getUserGroup(1)
        );

        self::assertInstanceOf(TermMembershipHandler::class, $termMembershipHandler);
    }

    /**
     * @group  unit
     * @covers ::isMember()
     * @covers ::getMap()
     */
    public function testIsMember()
    {
        $userGroup = $this->getMembershipUserGroup(
            [
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 2],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 4],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 5]
            ],
            [4, 5]
        );

        $termMembershipHandler = new TermMembershipHandler(
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory(),
            $userGroup
        );

        // term tests
        $return = $termMembershipHandler->isMember(false, 1, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('term'), $assignmentInformation);

        $return = $termMembershipHandler->isMember(true, 2, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                'term',
                null,
                null,
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => $this->getAssignmentInformation('term')]]
            ),
            $assignmentInformation
        );

        $return = $termMembershipHandler->isMember(true, 3, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('term'), $assignmentInformation);

        $return = $termMembershipHandler->isMember(true, 4, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                null,
                null,
                null,
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => $this->getAssignmentInformation('term')]]
            ),
            $assignmentInformation
        );

        $return = $termMembershipHandler->isMember(true, 5, $assignmentInformation);
        self::assertFalse($return);
        self::assertEquals(null, $assignmentInformation);
    }

    /**
     * @group  unit
     * @covers ::getFullObjects()
     * @covers ::getMap()
     */
    public function testGetFullObjects()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractUserGroup $userGroup
         */
        $userGroup = $this->createMock(AbstractUserGroup::class);

        $userGroup->expects($this->exactly(3))
            ->method('getAssignedObjects')
            ->withConsecutive(
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                ['category']
            )
            ->will($this->returnCallback(function ($objectType) {
                $elements = [];

                if ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
                    || $objectType === 'term'
                ) {
                    $elements[1] = $this->getAssignmentInformation('term');
                    $elements[2] = $this->getAssignmentInformation('term');
                    $elements[3] = $this->getAssignmentInformation('term');
                }

                if ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
                    || $objectType === 'category'
                ) {
                    $elements[100] = $this->getAssignmentInformation('category');
                }

                return $elements;
            }));

        $test = null;
        $userGroup->expects($this->exactly(3))
            ->method('isObjectMember')
            ->withConsecutive(
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1, &$test],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 2, &$test],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4, &$test]
            )
            ->will($this->onConsecutiveCalls(true, false, true));


        $termMembershipHandler = new TermMembershipHandler(
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory(),
            $userGroup
        );

        self::assertEquals(
            [1 => 'term', 2 => 'term', 3 => 'term', 100 => 'category'],
            $termMembershipHandler->getFullObjects(false)
        );

        self::assertEquals(
            [1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term', 100 => 'category'],
            $termMembershipHandler->getFullObjects(true)
        );

        self::assertEquals(
            [100 => 'category'],
            $termMembershipHandler->getFullObjects(true, 'category')
        );
    }
}
