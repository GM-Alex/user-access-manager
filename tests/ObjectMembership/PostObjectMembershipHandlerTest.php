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
namespace UserAccessManager\Tests\ObjectMembership;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\ObjectMembership\PostMembershipHandler;

/**
 * Class PostMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\ObjectMembership
 * @coversDefaultClass \UserAccessManager\ObjectMembership\PostMembershipHandler
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
            $this->getAssignmentInformationFactory(),
            $this->getWordpress(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(PostMembershipHandler::class, $postMembershipHandler);
    }

    /**
     * @group  unit
     * @covers ::getObjectName()
     */
    public function testGetObjectName()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $postType
         */
        $postType = $this->getMockBuilder('\WP_Post_Type')->getMock();
        $postType->labels = new \stdClass();
        $postType->labels->name = 'post';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getPostTypeObject')
            ->with('postType')
            ->will($this->returnValue($postType));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->post_title = 'postFour';
        $post->post_type = 'postType';

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(2))
            ->method('getPost')
            ->withConsecutive([-1], [4])
            ->will($this->onConsecutiveCalls(false, $post));

        $postMembershipHandler = new PostMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $wordpress,
            $objectHandler
        );

        $typeName = 'someType';
        self::assertEquals(-1, $postMembershipHandler->getObjectName(-1, $typeName));
        self::assertEquals('someType', $typeName);

        $typeName = 'someType';
        self::assertEquals('postFour', $postMembershipHandler->getObjectName(4, $typeName));
        self::assertEquals('post', $typeName);
    }

    /**
     * @group  unit
     * @covers ::getHandledObjects()
     */
    public function testGetHandledObjects()
    {
        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'page' => 'page']));

        $postMembershipHandler = new PostMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $this->getWordpress(),
            $objectHandler
        );

        self::assertEquals(
            [
                '_post_' => '_post_',
                'post' => 'post',
                'page' => 'page'
            ],
            $postMembershipHandler->getHandledObjects()
        );
    }

    /**
     * @group  unit
     * @covers ::isMember()
     * @covers ::assignRecursiveMembershipByTerm()
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
            $this->getExtendedAssignmentInformationFactory(),
            $this->getWordpress(),
            $this->getObjectHandler()
        );

        // post tests
        $return = $postMembershipHandler->isMember($userGroup, false, 1, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('post'), $assignmentInformation);

        $return = $postMembershipHandler->isMember($userGroup, true, 2, $assignmentInformation);
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

        $return = $postMembershipHandler->isMember($userGroup, true, 3, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('post'), $assignmentInformation);

        $return = $postMembershipHandler->isMember($userGroup, true, 4, $assignmentInformation);
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

        $return = $postMembershipHandler->isMember($userGroup, true, 5, $assignmentInformation);
        self::assertFalse($return);
        self::assertEquals($this->getAssignmentInformation(null), $assignmentInformation);

        $return = $postMembershipHandler->isMember($userGroup, true, 10, $assignmentInformation);
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
            $this->getExtendedAssignmentInformationFactory(),
            $this->getWordpress(),
            $this->getObjectHandler()
        );

        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 100 => 'page'],
            $postMembershipHandler->getFullObjects($userGroup, false)
        );

        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 100 => 'page', 4 => 'post', 9 => 'post', 10 => 'page'],
            $postMembershipHandler->getFullObjects($userGroup, true)
        );

        self::assertEquals(
            [100 => 'page', 10 => 'page'],
            $postMembershipHandler->getFullObjects($userGroup, true, 'page')
        );
    }
}
