<?php
/**
 * PostMembershipHandlerTest.php
 *
 * The PostMembershipHandlerTest unit test class file.
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
use UserAccessManager\UserGroup\ObjectMembership\PostMembershipHandler;

/**
 * Class PostMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\UserGroup\ObjectMembership
 * @coversDefaultClass \UserAccessManager\UserGroup\ObjectMembership\PostMembershipHandler
 */
class PostObjectMembershipHandlerTest extends ObjectMembershipHandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $postMembershipHandler = new PostMembershipHandler(
            $this->getObjectHandler(),
            $this->getAssignmentInformationFactory(),
            $this->getUserGroup(1)
        );

        self::assertInstanceOf(PostMembershipHandler::class, $postMembershipHandler);
    }

    /**
     * @group  unit
     * @covers ::isMember()
     * @covers ::getMap()
     */
    public function testIsMember()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractUserGroup $userGroup
         */
        $userGroup = $this->getMembershipUserGroup(
            [
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 1],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 3],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 2],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 3],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 1],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 4],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 5],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE , 10]
            ],
            [4, 5, 10]
        );

        $userGroup->expects($this->exactly(3))
            ->method('isTermMember')
            ->withConsecutive([3], [9], [3])
            ->will($this->returnCallback(function ($termId, &$assignmentInformation = null) {
                if (in_array($termId, [9]) === true) {
                    $assignmentInformation = null;
                    return false;
                }

                $assignmentInformation = $this->getAssignmentInformation('term');
                return true;
            }));

        $postMembershipHandler = new PostMembershipHandler(
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory(),
            $userGroup
        );

        // post tests
        $return = $postMembershipHandler->isMember(false, 1, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('post'), $assignmentInformation);

        $return = $postMembershipHandler->isMember(true, 2, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                'post',
                null,
                null,
                [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => $this->getAssignmentInformation('post')],
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => $this->getAssignmentInformation('term')]
                ]
            ),
            $assignmentInformation
        );

        $return = $postMembershipHandler->isMember(true, 3, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('post'), $assignmentInformation);

        $return = $postMembershipHandler->isMember(true, 4, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                null,
                null,
                null,
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => $this->getAssignmentInformation('post')]]
            ),
            $assignmentInformation
        );

        $return = $postMembershipHandler->isMember(true, 5, $assignmentInformation);
        self::assertFalse($return);
        self::assertEquals($this->getAssignmentInformation(null), $assignmentInformation);

        $return = $postMembershipHandler->isMember(true, 10, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                null,
                null,
                null,
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => $this->getAssignmentInformation('term')]]
            ),
            $assignmentInformation
        );
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
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE],
                ['page']
            )
            ->will($this->returnCallback(function ($objectType) {
                $elements = [];

                if ($objectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
                    || $objectType === 'post'
                ) {
                    $elements[1] = $this->getAssignmentInformation('post');
                    $elements[2] = $this->getAssignmentInformation('post');
                    $elements[3] = $this->getAssignmentInformation('post');
                }

                if ($objectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
                    || $objectType === 'page'
                ) {
                    $elements[100] = $this->getAssignmentInformation('page');
                }

                return $elements;
            }));

        $test = null;
        $userGroup->expects($this->exactly(3))
            ->method('isObjectMember')
            ->withConsecutive(
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1, &$test],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 2, &$test],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 4, &$test]
            )
            ->will($this->onConsecutiveCalls(true, false, true));

        $userGroup->expects($this->exactly(2))
            ->method('getFullTerms')
            ->will($this->returnValue([
                1 => $this->getAssignmentInformation(ObjectHandler::GENERAL_TERM_OBJECT_TYPE),
                2 => $this->getAssignmentInformation(ObjectHandler::GENERAL_TERM_OBJECT_TYPE),
                3 => $this->getAssignmentInformation(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
            ]));

        $postMembershipHandler = new PostMembershipHandler(
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory(),
            $userGroup
        );

        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 100 => 'page'],
            $postMembershipHandler->getFullObjects(false)
        );

        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 100 => 'page', 4 => 'post', 9 => 'post', 10 => 'page'],
            $postMembershipHandler->getFullObjects(true)
        );

        self::assertEquals(
            [100 => 'page', 10 => 'page'],
            $postMembershipHandler->getFullObjects(true, 'page')
        );
    }
}
