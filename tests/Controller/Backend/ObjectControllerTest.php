<?php
/**
 * ObjectControllerTest.php
 *
 * The ObjectControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller\Backend;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\PostMembershipHandler;
use UserAccessManager\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\TermMembershipHandler;
use UserAccessManager\ObjectMembership\UserMembershipHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\DynamicUserGroup;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ObjectControllerTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\Backend\ObjectController
 */
class ObjectControllerTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->root->unmount();
    }
    /**
     * @param int   $id
     * @param array $withAdd
     * @param array $withRemove
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroup
     */
    protected function getUserGroupWithAddDelete($id, array $withAdd = [], array $withRemove = [])
    {
        $userGroup = $this->getUserGroup($id);

        if (count($withAdd) > 0) {
            $userGroup->expects($this->exactly(count($withAdd)))
                ->method('addObject')
                ->withConsecutive(...$withAdd);
        }

        if (count($withRemove) > 0) {
            $userGroup->expects($this->exactly(count($withRemove)))
                ->method('removeObject')
                ->withConsecutive(...$withRemove);
        }

        return $userGroup;
    }

    /**
     * @param array $addIds
     * @param array $removeIds
     * @param array $with
     * @param array $additional
     *
     * @return array
     */
    private function getUserGroupArray(array $addIds, array $removeIds = [], array $with = [], array $additional = [])
    {
        $groups = [];

        $both = array_intersect($addIds, $removeIds);
        $withRemove = array_map(
            function ($element) {
                return array_slice($element, 0, 2);
            },
            $with
        );

        foreach ($both as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, $with, $withRemove);
        }

        $add = array_diff($addIds, $both);

        foreach ($add as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, $with, []);
        }

        $remove = array_diff($removeIds, $both);

        foreach ($remove as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, [], $withRemove);
        }

        foreach ($additional as $id) {
            $group = $this->getUserGroup($id);
            $group->expects($this->never())
                ->method('addObject');

            $groups[$id] = $group;
        }

        return $groups;
    }

    /**
     * @param string $type
     * @param string $id
     * @param array  $with
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroup
     */
    protected function getDynamicUserGroupWithAdd(
        $type,
        $id,
        array $with
    ) {
        $dynamicUserGroup = parent::getDynamicUserGroup(
            $type,
            $id
        );

        $dynamicUserGroup->expects($this->once())
            ->method('addObject')
            ->with(...$with);

        return $dynamicUserGroup;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf(ObjectController::class, $objectController);
    }

    /**
     * @group  unit
     * @covers ::setObjectInformation()
     *
     * @return ObjectController
     */
    public function testSetObjectInformation()
    {
        $fullGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $filteredGroups = [
            1 => $this->getUserGroup(1)
        ];

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('objectType', 'objectId', true)
            ->will($this->returnValue($fullGroups));

        $accessHandler->expects($this->once())
            ->method('getFilteredUserGroupsForObject')
            ->with('objectType', 'objectId', true)
            ->will($this->returnValue($filteredGroups));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        $userGroups = [
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        self::callMethod($objectController, 'setObjectInformation', ['objectType', 'objectId', $userGroups]);

        self::assertAttributeEquals('objectType', 'objectType', $objectController);
        self::assertAttributeEquals('objectId', 'objectId', $objectController);
        self::assertAttributeEquals($userGroups, 'objectUserGroups', $objectController);
        self::assertAttributeEquals(0, 'userGroupDiff', $objectController);

        self::callMethod($objectController, 'setObjectInformation', ['objectType', 'objectId']);

        self::assertAttributeEquals('objectType', 'objectType', $objectController);
        self::assertAttributeEquals('objectId', 'objectId', $objectController);
        self::assertAttributeEquals($filteredGroups, 'objectUserGroups', $objectController);
        self::assertAttributeEquals(1, 'userGroupDiff', $objectController);

        return $objectController;
    }

    /**
     * @group   unit
     * @covers  ::getObjectType()
     * @depends testSetObjectInformation
     *
     * @param ObjectController $objectController
     */
    public function testGetObjectType(ObjectController $objectController)
    {
        self::assertEquals('objectType', $objectController->getObjectType());
    }

    /**
     * @group   unit
     * @covers  ::getObjectId()
     * @depends testSetObjectInformation
     *
     * @param ObjectController $objectController
     */
    public function testGetObjectId(ObjectController $objectController)
    {
        self::assertEquals('objectId', $objectController->getObjectId());
    }

    /**
     * @group   unit
     * @covers  ::getObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param ObjectController $objectController
     */
    public function testGetObjectUserGroups(ObjectController $objectController)
    {
        self::assertEquals([1 => $this->getUserGroup(1)], $objectController->getObjectUserGroups());
    }

    /**
     * @group   unit
     * @covers  ::getUserGroupDiff()
     * @depends testSetObjectInformation
     *
     * @param ObjectController $objectController
     */
    public function testGetUserGroupDiff(ObjectController $objectController)
    {
        self::assertEquals(1, $objectController->getUserGroupDiff());
    }

    /**
     * @group  unit
     * @covers ::getUserGroups()
     */
    public function testGetUserGroups()
    {
        $userGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3)
        ];

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->once())
            ->method('getFullUserGroups')
            ->will($this->returnValue($userGroups));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals($userGroups, $objectController->getUserGroups());
    }

    /**
     * @group  unit
     * @covers ::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $userGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->once())
            ->method('getFilteredUserGroups')
            ->will($this->returnValue($userGroups));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals($userGroups, $objectController->getFilteredUserGroups());
    }

    /**
     * @group  unit
     * @covers ::isCurrentUserAdmin()
     */
    public function testIsCurrentUserAdmin()
    {
        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('userIsAdmin')
            ->with('objectId')
            ->will($this->onConsecutiveCalls(false, true));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        self::assertFalse($objectController->isCurrentUserAdmin());

        self::setValue($objectController, 'objectType', ObjectHandler::GENERAL_USER_OBJECT_TYPE);
        self::assertFalse($objectController->isCurrentUserAdmin());

        self::setValue($objectController, 'objectId', 'objectId');
        self::assertFalse($objectController->isCurrentUserAdmin());
        self::assertTrue($objectController->isCurrentUserAdmin());
    }

    /**
     * @group  unit
     * @covers ::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $roles = new \stdClass();
        $roles->role_names = 'roleNames';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        $objectController = new ObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals('roleNames', $objectController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers ::getAllObjectTypes()
     */
    public function testGetAllObjectTypes()
    {
        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue([1 => 1, 2 => 2]));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $objectHandler,
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self:self::assertEquals([1 => 1, 2 => 2], $objectController->getAllObjectTypes());
    }

    /**
     * @group  unit
     * @covers ::checkUserAccess()
     */
    public function testCheckUserAccess()
    {
        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        self::assertFalse($objectController->checkUserAccess());
        self::assertTrue($objectController->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers ::formatDate()
     */
    public function testFormatDate()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('formatDate')
            ->with('date')
            ->will($this->returnValue('formattedDate'));

        $objectController = new ObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals('formattedDate', $objectController->formatDate('date'));
    }

    /**
     * @group  unit
     * @covers ::formatDateForDatetimeInput()
     */
    public function testFormatDateForDatetimeInput()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals(null, $objectController->formatDateForDatetimeInput(null));
        self::assertEquals('1970-01-01T00:00:00', $objectController->formatDateForDatetimeInput(0));
    }

    /**
     * @group  unit
     * @covers ::getDateFromTime()
     */
    public function testGetDateFromTime()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('currentTime')
            ->with('timestamp')
            ->will($this->returnValue(100));

        $objectController = new ObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals(null, $objectController->getDateFromTime(null));
        self::assertEquals(null, $objectController->getDateFromTime(0));
        self::assertEquals('1970-01-01 00:01:41', $objectController->getDateFromTime(1));
    }

    /**
     * @group  unit
     * @covers ::getRecursiveMembership()
     */
    public function testGetRecursiveMembership()
    {
        $objectHandler = $this->getObjectHandler();

        $postMembershipHandler = $this->getMembershipHandler(PostMembershipHandler::class, 'post', [-1]);
        $roleMembershipHandler = $this->getMembershipHandler(RoleMembershipHandler::class, 'role', [-1]);
        $termMembershipHandler = $this->getMembershipHandler(TermMembershipHandler::class, 'term', [-1]);
        $userMembershipHandler = $this->getMembershipHandler(UserMembershipHandler::class, 'user', [-1]);

        $objectHandler->expects($this->exactly(9))
            ->method('getObjectMembershipHandler')
            ->withConsecutive(
                ['role'],
                ['user'],
                ['user'],
                ['term'],
                ['term'],
                ['term'],
                ['post'],
                ['post'],
                ['invalid']
            )->will($this->returnCallback(
                function ($type) use (
                    $postMembershipHandler,
                    $roleMembershipHandler,
                    $termMembershipHandler,
                    $userMembershipHandler
                ) {
                    if ($type === 'role') {
                        return $roleMembershipHandler;
                    } elseif ($type === 'user') {
                        return $userMembershipHandler;
                    } elseif ($type === 'term') {
                        return $termMembershipHandler;
                    } elseif ($type === 'post') {
                        return $postMembershipHandler;
                    }

                    throw new MissingObjectMembershipHandlerException();
                }
            ));


        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $objectHandler,
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::setValue($objectController, 'objectId', 'objectId');
        self::setValue($objectController, 'objectType', 'objectType');

        $userGroup = $this->getUserGroup(1);
        $userGroup->expects($this->once())
            ->method('getRecursiveMembershipForObject')
            ->with('objectType', 'objectId')
            ->willReturn([
                'role' => [1 => $this->getAssignmentInformation('role')],
                'user' => [
                    -1 => $this->getAssignmentInformation('user'),
                    2 => $this->getAssignmentInformation('user')
                ],
                'term' => [
                    -1 => $this->getAssignmentInformation('term'),
                    1 => $this->getAssignmentInformation('term'),
                    3 => $this->getAssignmentInformation('term')
                ],
                'post' => [
                    -1 => $this->getAssignmentInformation('post'),
                    4 => $this->getAssignmentInformation('post')
                ],
                'invalid' => [-1 => $this->getAssignmentInformation('invalid')]
            ]);

        $expected = [
            'roleTypeName' => [1 => 'roleName'],
            'userTypeName' => [2 => 'userName'],
            'user' => [-1 => -1],
            'termTypeName' => [1 => 'termName', 3 => 'termName'],
            'term' => [-1 => -1],
            'postTypeName' => [4 => 'postName'],
            'post' => [-1 => -1]
        ];

        self::assertEquals($expected, $objectController->getRecursiveMembership($userGroup));
    }

    /**
     * @group  unit
     * @covers ::checkRightsToEditContent()
     */
    public function testCheckRightsToEditContent()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $noAccessPost
         */
        $noAccessPost = $this->getMockBuilder('\WP_Post')->getMock();
        $noAccessPost->ID = 2;
        $noAccessPost->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $attachment
         */
        $attachment = $this->getMockBuilder('\WP_Post')->getMock();
        $attachment->ID = 3;
        $attachment->post_type = 'attachment';

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive([-1], [1], [2], [3])
            ->will($this->onConsecutiveCalls(false, $post, $noAccessPost, $attachment));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(5))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2],
                ['attachment', 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5]
            )
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $objectController = new ObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getUserGroupFactory()
        );

        $objectController->checkRightsToEditContent();

        $_GET['post'] = -1;
        $objectController->checkRightsToEditContent();

        $_GET['post'] = 1;
        $objectController->checkRightsToEditContent();

        $_GET['post'] = 2;
        $objectController->checkRightsToEditContent();

        unset($_GET['post']);
        $_GET['attachment_id'] = 3;
        $objectController->checkRightsToEditContent();

        unset($_GET['attachment_id']);
        $_GET['tag_ID'] = 4;
        $objectController->checkRightsToEditContent();

        $_GET['tag_ID'] = 5;
        $objectController->checkRightsToEditContent();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    private function getExtendedObjectHandler()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $revisionPost
         */
        $revisionPost = $this->getMockBuilder('\WP_Post')->getMock();
        $revisionPost->ID = 2;
        $revisionPost->post_type = 'revision';
        $revisionPost->post_parent = 1;

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $attachment
         */
        $attachment = $this->getMockBuilder('\WP_Post')->getMock();
        $attachment->ID = 3;
        $attachment->post_type = 'attachment';

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getPost')
            ->will($this->returnCallback(function ($postId) use ($post, $revisionPost, $attachment) {
                if ($postId === 1) {
                    return $post;
                } elseif ($postId === 2) {
                    return $revisionPost;
                } elseif ($postId === 3) {
                    return $attachment;
                }

                return false;
            }));

        $objectHandler->expects($this->any())
            ->method('getTerm')
            ->will($this->returnCallback(function ($termId) {
                if ($termId === 0) {
                    return false;
                }

                /**
                 * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $term
                 */
                $term = $this->getMockBuilder('\WP_Term')->getMock();
                $term->term_id = $termId;
                $term->taxonomy = 'taxonomy_'.$termId;

                return $term;
            }));

        return $objectHandler;
    }

    /**
     * @group  unit
     * @covers ::saveObjectData()
     * @covers ::getAddRemoveGroups()
     * @covers ::setUserGroups()
     * @covers ::setDynamicGroups()
     * @covers ::setDefaultGroups()
     * @covers ::savePostData()
     * @covers ::saveAttachmentData()
     * @covers ::saveAjaxAttachmentData()
     * @covers ::saveUserData()
     * @covers ::saveTermData()
     * @covers ::savePluggableObjectData()
     * @covers ::getDateParameter()
     */
    public function testSaveObjectData()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('currentTime')
            ->with('timestamp')
            ->will($this->returnValue(100));

        $config = $this->getMainConfig();
        $config->expects($this->exactly(3))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(false, true, true));

        $objectHandler = $this->getExtendedObjectHandler();

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(22))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(
                false,
                false,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                false,
                false
            ));

        $accessHandler->expects($this->exactly(10))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                ['attachment', 3],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 0],
                ['taxonomy_1', 1],
                ['objectType', 'objectId']
            )
            ->will($this->onConsecutiveCalls(
                $this->getUserGroupArray([1, 2, 3]),
                $this->getUserGroupArray([1, 2, 4]),
                $this->getUserGroupArray([2, 3, 4]),
                $this->getUserGroupArray([1, 3, 4]),
                $this->getUserGroupArray([1, 2]),
                $this->getUserGroupArray([1, 3, 4]),
                $this->getUserGroupArray([3, 4]),
                $this->getUserGroupArray([1, 4]),
                $this->getUserGroupArray([1, 4]),
                $this->getUserGroupArray([2, 3])
            ));

        $fullGroupOne = $this->getUserGroupWithAddDelete(
            1000,
            [['objectType', 'objectId', '1970-01-01 00:01:41', '1970-01-01 00:01:42']]
        );

        $fullGroupOne->expects($this->once())
            ->method('isDefaultGroupForObjectType')
            ->with('objectType', null, null)
            ->will($this->returnCallback(function ($objectType, &$fromTime, &$toTime) {
                $fromTime = 1;
                $toTime = 2;
                return true;
            }));

        $fullGroupTwo = $this->getUserGroupWithAddDelete(1001);

        $fullGroupTwo->expects($this->once())
            ->method('isDefaultGroupForObjectType')
            ->with('objectType', 1, 2)
            ->will($this->returnValue(false));

        $accessHandler->expects($this->once())
            ->method('getFullUserGroups')
            ->will($this->returnValue([$fullGroupOne, $fullGroupTwo]));

        $accessHandler->expects($this->exactly(10))
            ->method('getFilteredUserGroups')
            ->will($this->onConsecutiveCalls(
                $this->getUserGroupArray([1, 3], [1, 2, 3], [['post', 1, '1', 'toDate']], [100, 101]),
                $this->getUserGroupArray([2, 4], [1, 2, 4], [['post', 1, null, null]]),
                $this->getUserGroupArray([1, 2], [2, 3, 4], [['post', 1, null, '234']]),
                $this->getUserGroupArray([3, 4], [1, 3, 4], [['attachment', 3, null, null]]),
                $this->getUserGroupArray([], [2, 3], [['attachment', 3, null, null]]),
                $this->getUserGroupArray([3, 4], [1, 3, 4], [[ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3, null, null]]),
                $this->getUserGroupArray([2], [3, 4], [[ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1, null, null]]),
                $this->getUserGroupArray([3], [1, 4], [[ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 0, null, null]]),
                $this->getUserGroupArray([3], [1, 4], [['taxonomy_1', 1, null, null]]),
                $this->getUserGroupArray([4], [2, 3], [['objectType', 'objectId', null, null]])
            ));

        $accessHandler->expects($this->exactly(10))
            ->method('unsetUserGroupsForObject');

        $userGroupFactory = $this->getUserGroupFactory();

        $userGroupFactory->expects($this->exactly(2))
            ->method('createDynamicUserGroup')
            ->withConsecutive(
                [DynamicUserGroup::USER_TYPE, '1'],
                [DynamicUserGroup::ROLE_TYPE, 'admin']
            )->will($this->onConsecutiveCalls(
                $this->getDynamicUserGroupWithAdd(DynamicUserGroup::USER_TYPE, '1', ['post', 1, 'fromDate', 'toDate']),
                $this->getDynamicUserGroupWithAdd(DynamicUserGroup::ROLE_TYPE, 'admin', ['post', 1, null, null])
            ));

        $objectController = new ObjectController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $userGroupFactory
        );

        $_POST[ObjectController::UPDATE_GROUPS_FORM_NAME] = 1;
        $objectController->savePostData(['ID' => 1]);

        $_POST[ObjectController::DEFAULT_DYNAMIC_GROUPS_FORM_NAME] = [
            DynamicUserGroup::USER_TYPE.'|1' => [
                'id' => DynamicUserGroup::USER_TYPE.'|1',
                'fromDate' => 'fromDate',
                'toDate' => 'toDate'
            ],
            DynamicUserGroup::ROLE_TYPE.'|admin' => ['id' => DynamicUserGroup::ROLE_TYPE.'|admin'],
            'A|B' => ['id' => 'B|A'],
        ];
        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            1 => ['id' => 1, 'fromDate' => 1, 'toDate' => 'toDate'],
            3 => ['id' => 3, 'fromDate' => 1, 'toDate' => 'toDate'],
            100 => [],
            101 => ['id' => 100]
        ];
        $objectController->savePostData(['ID' => 1]);

        unset($_POST[ObjectController::DEFAULT_DYNAMIC_GROUPS_FORM_NAME]);
        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            2 => ['id' => 2],
            4 => ['id' => 4]
        ];
        $objectController->savePostData(['ID' => 2]);

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            1 => ['id' => 1, 'formDate' => '', 'toDate' => 234],
            2 => ['id' => 2, 'formDate' => '', 'toDate' => 234]
        ];
        $objectController->savePostData(2);

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            3 => ['id' => 3],
            4 => ['id' => 4]
        ];
        $objectController->saveAttachmentData(['ID' => 3]);

        $_POST['uam_bulk_type'] = ObjectController::BULK_REMOVE;
        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            2 => ['id' => 2],
            3 => ['id' => 3]
        ];
        $objectController->saveAttachmentData(['ID' => 3]);

        $_POST = [
            ObjectController::UPDATE_GROUPS_FORM_NAME => 1,
            'id' => 3,
            ObjectController::DEFAULT_GROUPS_FORM_NAME => [
                3 => ['id' => 3],
                4 => ['id' => 4]
            ]
        ];
        $objectController->saveAjaxAttachmentData();

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            2 => ['id' => 2]
        ];
        $objectController->saveUserData(1);

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            3 => ['id' => 3]
        ];
        $objectController->saveTermData(0);
        $objectController->saveTermData(1);

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            4 => ['id' => 4]
        ];
        $objectController->savePluggableObjectData('objectType', 'objectId');

        $_POST = [];
        $objectController->savePluggableObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers ::removeObjectData()
     * @covers ::removePostData()
     * @covers ::removeUserData()
     * @covers ::removeTermData()
     * @covers ::removePluggableObjectData()
     */
    public function testRemoveObjectData()
    {
        $database = $this->getDatabase();
        $database->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(4))
            ->method('delete')
            ->withConsecutive(
                [
                    'userGroupToObjectTable',
                    ['object_id' => 1, 'object_type' => 'post'],
                    ['%d', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    ['object_id' => 2, 'object_type' => ObjectHandler::GENERAL_USER_OBJECT_TYPE],
                    ['%d', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    ['object_id' => 3, 'object_type' => ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                    ['%d', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    ['object_id' => 'objectId', 'object_type' => 'objectType'],
                    ['%d', '%s']
                ]
            );

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $database,
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        $objectController->removePostData(1);
        $objectController->removeUserData(2);
        $objectController->removeTermData(3);
        $objectController->removePluggableObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers ::addPostColumnsHeader()
     * @covers ::addUserColumnsHeader()
     * @covers ::addTermColumnsHeader()
     */
    public function testAddColumnsHeader()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals(
            ['a' => 'a', ObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $objectController->addPostColumnsHeader(['a' => 'a'])
        );
        self::assertEquals(
            ['b' => 'b', ObjectController::COLUMN_NAME => TXT_UAM_COLUMN_USER_GROUPS],
            $objectController->addUserColumnsHeader(['b' => 'b'])
        );
        self::assertEquals(
            ['c' => 'c', ObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $objectController->addTermColumnsHeader(['c' => 'c'])
        );
    }

    /**
     * @group  unit
     * @covers ::addPostColumn()
     * @covers ::addUserColumn()
     * @covers ::addTermColumn()
     * @covers ::getPluggableColumn()
     */
    public function testAddColumn()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        $objectController->addPostColumn(ObjectController::COLUMN_NAME, 1);
        $objectController->addUserColumn('return', ObjectController::COLUMN_NAME, 1);
        $objectController->addTermColumn('content', ObjectController::COLUMN_NAME, 1);
        $objectController->getPluggableColumn('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers ::addPostColumn()
     * @covers ::addUserColumn()
     * @covers ::addTermColumn()
     * @covers ::getPluggableColumn()
     * @covers ::editPostContent()
     * @covers ::addBulkAction()
     * @covers ::showMediaFile()
     * @covers ::showUserProfile()
     * @covers ::showTermEditForm()
     * @covers ::showPluggableGroupSelectionForm()
     * @covers ::getGroupsFormName()
     */
    public function testEditForm()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('src', new Directory([
            'View'  => new Directory([
                'ObjectColumn.php' => new File('<?php echo \'ObjectColumn\';'),
                'UserColumn.php' => new File('<?php echo \'UserColumn\';'),
                'PostEditForm.php' => new File('<?php echo \'PostEditForm\';'),
                'BulkEditForm.php' => new File('<?php echo \'BulkEditForm\';'),
                'MediaAjaxEditForm.php' => new File('<?php echo \'MediaAjaxEditForm\';'),
                'UserProfileEditForm.php' => new File('<?php echo \'UserProfileEditForm\';'),
                'TermEditForm.php' => new File('<?php echo \'TermEditForm\';'),
                'GroupSelectionForm.php' => new File('<?php echo \'GroupSelectionForm\';')
            ])
        ]));

        $php = $this->getPhp();

        $config = $this->getMainConfig();
        $config->expects($this->exactly(17))
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(11))
            ->method('getUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 0],
                ['taxonomy_1', 1],
                ['objectType', 'objectId'],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4],
                ['category', 5],
                ['objectType', 'objectId']
            )
            ->will($this->returnValue([]));

        $accessHandler->expects($this->exactly(11))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 0],
                ['taxonomy_1', 1],
                ['objectType', 'objectId'],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4],
                ['category', 5],
                ['objectType', 'objectId']
            )
            ->will($this->returnValue([]));

        $objectController = new ObjectController(
            $php,
            $this->getWordpress(),
            $config,
            $this->getDatabase(),
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        $php->expects($this->exactly(17))
            ->method('includeFile')
            ->withConsecutive(
                [$objectController, 'vfs://src/View/ObjectColumn.php'],
                [$objectController, 'vfs://src/View/UserColumn.php'],
                [$objectController, 'vfs://src/View/ObjectColumn.php'],
                [$objectController, 'vfs://src/View/ObjectColumn.php'],
                [$objectController, 'vfs://src/View/ObjectColumn.php'],
                [$objectController, 'vfs://src/View/PostEditForm.php'],
                [$objectController, 'vfs://src/View/PostEditForm.php'],
                [$objectController, 'vfs://src/View/BulkEditForm.php'],
                [$objectController, 'vfs://src/View/MediaAjaxEditForm.php'],
                [$objectController, 'vfs://src/View/MediaAjaxEditForm.php'],
                [$objectController, 'vfs://src/View/MediaAjaxEditForm.php'],
                [$objectController, 'vfs://src/View/UserProfileEditForm.php'],
                [$objectController, 'vfs://src/View/UserProfileEditForm.php'],
                [$objectController, 'vfs://src/View/TermEditForm.php'],
                [$objectController, 'vfs://src/View/TermEditForm.php'],
                [$objectController, 'vfs://src/View/GroupSelectionForm.php'],
                [$objectController, 'vfs://src/View/GroupSelectionForm.php']
            )
            ->will($this->returnCallback(function (ObjectController $controller, $file) {
                echo '!'.get_class($controller).'|'.$file.'|'.$controller->getGroupsFormName().'!';
            }));

        $objectController->addPostColumn('invalid', 1);
        $objectController->addPostColumn('invalid', 1);
        $objectController->addPostColumn(ObjectController::COLUMN_NAME, 1);
        self::assertAttributeEquals('post', 'objectType', $objectController);
        self::assertAttributeEquals(1, 'objectId', $objectController);
        $expectedOutput = '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals('return', $objectController->addUserColumn('return', 'invalid', 1));
        self::assertEquals('return', $objectController->addUserColumn('return', 'invalid', 1));

        $expected = 'return!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/UserColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $objectController->addUserColumn('return', ObjectController::COLUMN_NAME, 1)
        );
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $objectController);
        self::assertAttributeEquals(1, 'objectId', $objectController);

        self::assertEquals('content', $objectController->addTermColumn('content', 'invalid', 1));
        self::assertEquals('content', $objectController->addTermColumn('content', 'invalid', 1));

        $expected = 'content!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $objectController->addTermColumn('content', ObjectController::COLUMN_NAME, 0)
        );
        self::assertAttributeEquals(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'objectType', $objectController);
        self::assertAttributeEquals(0, 'objectId', $objectController);

        self::assertEquals(
            $expected,
            $objectController->addTermColumn('content', ObjectController::COLUMN_NAME, 1)
        );
        self::assertAttributeEquals('taxonomy_1', 'objectType', $objectController);
        self::assertAttributeEquals(1, 'objectId', $objectController);

        $expected = '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $objectController->getPluggableColumn('objectType', 'objectId')
        );
        self::assertAttributeEquals('objectType', 'objectType', $objectController);
        self::assertAttributeEquals('objectId', 'objectId', $objectController);

        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);

        $objectController->editPostContent(null);
        self::assertAttributeEquals(null, 'objectType', $objectController);
        self::assertAttributeEquals(null, 'objectId', $objectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/PostEditForm.php|uam_user_groups!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        $objectController->editPostContent($post);
        self::assertAttributeEquals('post', 'objectType', $objectController);
        self::assertAttributeEquals(1, 'objectId', $objectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/PostEditForm.php|uam_user_groups!';
        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);

        $objectController->addBulkAction('invalid');
        $expectedOutput .= '';

        $objectController->addBulkAction('invalid');
        $expectedOutput .= '';

        $objectController->addBulkAction(ObjectController::COLUMN_NAME);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/BulkEditForm.php|uam_user_groups!';


        $return = $objectController->showMediaFile(['a' => 'b']);
        self::assertAttributeEquals(null, 'objectType', $objectController);
        self::assertAttributeEquals(null, 'objectId', $objectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\ObjectController|'
                        .'vfs://src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );

        $return = $objectController->showMediaFile(['a' => 'b'], $post);
        self::assertAttributeEquals('post', 'objectType', $objectController);
        self::assertAttributeEquals(1, 'objectId', $objectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\ObjectController|'
                        .'vfs://src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );

        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);

        $_GET['attachment_id'] = 3;
        $return = $objectController->showMediaFile(['a' => 'b'], $post);
        self::assertAttributeEquals('attachment', 'objectType', $objectController);
        self::assertAttributeEquals(3, 'objectId', $objectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\ObjectController|'
                        .'vfs://src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );

        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);

        $objectController->showUserProfile();
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $objectController);
        self::assertAttributeEquals(null, 'objectId', $objectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/UserProfileEditForm.php|uam_user_groups!';

        $_GET['user_id'] = 4;
        $objectController->showUserProfile();
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $objectController);
        self::assertAttributeEquals(4, 'objectId', $objectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/UserProfileEditForm.php|uam_user_groups!';
        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);
        unset($_GET['user_id']);

        $objectController->showTermEditForm('category');
        self::assertAttributeEquals('category', 'objectType', $objectController);
        self::assertAttributeEquals(null, 'objectId', $objectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/TermEditForm.php|uam_user_groups!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass|\WP_Term $term
         */
        $term = $this->getMockBuilder('\WP_Term')->getMock();
        $term->term_id = 5;
        $term->taxonomy = 'category';
        $objectController->showTermEditForm($term);

        self::assertAttributeEquals('category', 'objectType', $objectController);
        self::assertAttributeEquals(5, 'objectId', $objectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/TermEditForm.php|uam_user_groups!';
        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);

        $return = $objectController->showPluggableGroupSelectionForm('objectType', 'objectId', 'otherForm');
        self::assertEquals(
            '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/GroupSelectionForm.php|otherForm!',
            $return
        );
        self::assertAttributeEquals('objectType', 'objectType', $objectController);
        self::assertAttributeEquals('objectId', 'objectId', $objectController);
        self::assertEquals(
            ObjectController::DEFAULT_GROUPS_FORM_NAME,
            $objectController->getGroupsFormName()
        );
        $expectedOutput .= '';

        $userGroups = [
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        $return = $objectController->showPluggableGroupSelectionForm('objectType', 'objectId', null, $userGroups);
        self::assertEquals(
            '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/GroupSelectionForm.php|uam_user_groups!',
            $return
        );
        self::assertAttributeEquals('objectType', 'objectType', $objectController);
        self::assertAttributeEquals('objectId', 'objectId', $objectController);
        self::assertAttributeEquals($userGroups, 'objectUserGroups', $objectController);
        self::assertEquals(
            ObjectController::DEFAULT_GROUPS_FORM_NAME,
            $objectController->getGroupsFormName()
        );
        $expectedOutput .= '';

        self::expectOutputString($expectedOutput);
    }

    /**
     * @group  unit
     * @covers ::invalidateTermCache()
     * @covers ::invalidatePostCache()
     */
    public function testInvalidateCache()
    {
        $cache = $this->getCache();
        $cache->expects($this->exactly(6))
            ->method('invalidate')
            ->withConsecutive(
                [ObjectHandler::POST_TERM_MAP_CACHE_KEY],
                [ObjectHandler::TERM_POST_MAP_CACHE_KEY],
                [ObjectHandler::TERM_TREE_MAP_CACHE_KEY],
                [ObjectHandler::TERM_POST_MAP_CACHE_KEY],
                [ObjectHandler::POST_TERM_MAP_CACHE_KEY],
                [ObjectHandler::POST_TREE_MAP_CACHE_KEY]
            );

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $cache,
            $this->getExtendedObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        $objectController->invalidateTermCache();
        $objectController->invalidatePostCache();
    }

    /**
     * @param int    $id
     * @param string $displayName
     * @param string $userLogin
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\stdClass
     */
    private function getUser($id, $displayName, $userLogin)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = $id;
        $user->display_name = $displayName;
        $user->user_login = $userLogin;

        return $user;
    }

    /**
     * @group  unit
     * @covers ::isNewObject()
     */
    public function testIsNewObject()
    {
        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(4))
            ->method('getGeneralObjectType')
            ->withConsecutive(
                ['objectTypeValue'],
                ['objectTypeValue'],
                ['otherObjectTypeValue'],
                ['otherObjectTypeValue']
            )
            ->will($this->onConsecutiveCalls(
                'generalObjectType',
                'generalObjectType',
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                ObjectHandler::GENERAL_POST_OBJECT_TYPE
            ));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $objectHandler,
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);
        self::assertFalse($objectController->isNewObject());

        self::setValue($objectController, 'objectType', 'objectTypeValue');
        self::setValue($objectController, 'objectId', null);
        self::assertTrue($objectController->isNewObject());

        self::setValue($objectController, 'objectType', 'objectTypeValue');
        self::setValue($objectController, 'objectId', 1);
        self::assertFalse($objectController->isNewObject());

        $_GET['action'] = 'edit';
        self::setValue($objectController, 'objectType', 'otherObjectTypeValue');
        self::assertFalse($objectController->isNewObject());

        $_GET['action'] = 'new';
        self::setValue($objectController, 'objectType', 'otherObjectTypeValue');
        self::assertTrue($objectController->isNewObject());
    }

    /**
     * @group  unit
     * @covers ::getDynamicGroupsForAjax()
     */
    public function testGetDynamicGroupsForAjax()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(2))
            ->method('callExit');

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getUsers')
            ->with([
                'search' => '*sea*',
                'fields' => ['ID', 'display_name', 'user_login', 'user_email']
            ])
            ->will($this->returnValue([
                $this->getUser(1, 'firstUser', 'firstUserLogin'),
                $this->getUser(2, 'secondUser', 'secondUserLogin')
            ]));

        $roles = new \stdClass();
        $roles->roles = [
            'admin' => ['name' => 'Administrator'],
            'editor' => ['name' => 'Editor'],
            'search' => ['name' => 'Search']
        ];

        $wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        $_GET['q'] = 'firstSearch, sea';

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->with(AccessHandler::MANAGE_USER_GROUPS_CAPABILITY)
            ->will($this->onConsecutiveCalls(true, false));

        $objectController = new ObjectController(
            $php,
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        $objectController->getDynamicGroupsForAjax();
        $objectController->getDynamicGroupsForAjax();

        self::expectOutputString(
            '['
            .'{"id":1,"name":"User|user-access-manager: firstUser (firstUserLogin)","type":"user"},'
            .'{"id":2,"name":"User|user-access-manager: secondUser (secondUserLogin)","type":"user"},'
            .'{"id":"search","name":"Role|user-access-manager: Search","type":"role"}'
            .'][]'
        );
    }
}
