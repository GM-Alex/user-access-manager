<?php
/**
 * AdminObjectControllerTest.php
 *
 * The AdminObjectControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class AdminObjectControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminObjectControllerTest extends UserAccessManagerTestCase
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
     *
     * @return array
     */
    private function getUserGroupArray(array $addIds, array $removeIds = [], array $with = [])
    {
        $groups = [];

        $both = array_intersect($addIds, $removeIds);

        foreach ($both as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, $with, $with);
        }

        $add = array_diff($addIds, $both);

        foreach ($add as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, $with, []);
        }

        $remove = array_diff($removeIds, $both);

        foreach ($remove as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, [], $with);
        }

        return $groups;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::__construct()
     */
    public function testCanCreateInstance()
    {
        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminObjectController', $adminObjectController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::setObjectInformation()
     *
     * @return AdminObjectController
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
            ->with('objectType', 'objectId')
            ->will($this->returnValue($fullGroups));

        $accessHandler->expects($this->once())
            ->method('getFilteredUserGroupsForObject')
            ->with('objectType', 'objectId')
            ->will($this->returnValue($filteredGroups));

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $accessHandler
        );

        $userGroups = [
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        self::callMethod($adminObjectController, 'setObjectInformation', ['objectType', 'objectId', $userGroups]);

        self::assertAttributeEquals('objectType', 'objectType', $adminObjectController);
        self::assertAttributeEquals('objectId', 'objectId', $adminObjectController);
        self::assertAttributeEquals($userGroups, 'objectUserGroups', $adminObjectController);
        self::assertAttributeEquals(0, 'userGroupDiff', $adminObjectController);

        self::callMethod($adminObjectController, 'setObjectInformation', ['objectType', 'objectId']);

        self::assertAttributeEquals('objectType', 'objectType', $adminObjectController);
        self::assertAttributeEquals('objectId', 'objectId', $adminObjectController);
        self::assertAttributeEquals($filteredGroups, 'objectUserGroups', $adminObjectController);
        self::assertAttributeEquals(1, 'userGroupDiff', $adminObjectController);

        return $adminObjectController;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectType()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $adminObjectController
     */
    public function testGetObjectType(AdminObjectController $adminObjectController)
    {
        self::assertEquals('objectType', $adminObjectController->getObjectType());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectId()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $adminObjectController
     */
    public function testGetObjectId(AdminObjectController $adminObjectController)
    {
        self::assertEquals('objectId', $adminObjectController->getObjectId());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $adminObjectController
     */
    public function testGetObjectUserGroups(AdminObjectController $adminObjectController)
    {
        self::assertEquals([1 => $this->getUserGroup(1)], $adminObjectController->getObjectUserGroups());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getUserGroupDiff()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $adminObjectController
     */
    public function testGetUserGroupDiff(AdminObjectController $adminObjectController)
    {
        self::assertEquals(1, $adminObjectController->getUserGroupDiff());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getUserGroups()
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
            ->method('getUserGroups')
            ->will($this->returnValue($userGroups));

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $accessHandler
        );

        self::assertEquals($userGroups, $adminObjectController->getUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getFilteredUserGroups()
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

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $accessHandler
        );

        self::assertEquals($userGroups, $adminObjectController->getFilteredUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::isCurrentUserAdmin()
     */
    public function testIsCurrentUserAdmin()
    {
        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('userIsAdmin')
            ->with('objectId')
            ->will($this->onConsecutiveCalls(false, true));

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $accessHandler
        );

        self::assertFalse($adminObjectController->isCurrentUserAdmin());

        self::setValue($adminObjectController, 'objectType', ObjectHandler::GENERAL_USER_OBJECT_TYPE);
        self::assertFalse($adminObjectController->isCurrentUserAdmin());

        self::setValue($adminObjectController, 'objectId', 'objectId');
        self::assertFalse($adminObjectController->isCurrentUserAdmin());
        self::assertTrue($adminObjectController->isCurrentUserAdmin());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $roles = new \stdClass();
        $roles->role_names = 'roleNames';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals('roleNames', $adminObjectController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getAllObjectTypes()
     */
    public function testGetAllObjectTypes()
    {
        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue([1 => 1, 2 => 2]));

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $objectHandler,
            $this->getAccessHandler()
        );

        self:self::assertEquals([1 => 1, 2 => 2], $adminObjectController->getAllObjectTypes());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkUserAccess()
     */
    public function testCheckUserAccess()
    {
        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $accessHandler
        );

        self::assertFalse($adminObjectController->checkUserAccess());
        self::assertTrue($adminObjectController->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRecursiveMembership()
     */
    public function testGetRecursiveMembership()
    {
        $roles = new \stdClass();
        $roles->role_names = [1 => 'roleOne'];

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $taxonomy
         */
        $taxonomy = $this->getMockBuilder('\WP_Taxonomy')->getMock();
        $taxonomy->labels = new \stdClass();
        $taxonomy->labels->name = 'category';

        $wordpress->expects($this->exactly(2))
            ->method('getTaxonomy')
            ->with('termTaxonomy')
            ->will($this->onConsecutiveCalls(false, $taxonomy));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $postType
         */
        $postType = $this->getMockBuilder('\WP_Post_Type')->getMock();
        $postType->labels = new \stdClass();
        $postType->labels->name = 'post';

        $wordpress->expects($this->once())
            ->method('getPostTypeObject')
            ->with('postType')
            ->will($this->returnValue($postType));


        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(10))
            ->method('getGeneralObjectType')
            ->withConsecutive(
                ['role'],
                ['user'],
                ['user'],
                ['term'],
                ['term'],
                ['term'],
                ['post'],
                ['post'],
                ['pluggableObject']
            )
            ->will($this->onConsecutiveCalls(
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                null,
                null
            ));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->display_name = 'userTwo';

        $objectHandler->expects($this->exactly(2))
            ->method('getUser')
            ->withConsecutive([-1], [2])
            ->will($this->onConsecutiveCalls(false, $user));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $term
         */
        $term = $this->getMockBuilder('\WP_Term')->getMock();
        $term->name = 'categoryThree';
        $term->taxonomy = 'termTaxonomy';

        $objectHandler->expects($this->exactly(3))
            ->method('getTerm')
            ->withConsecutive([-1], [1], [3])
            ->will($this->onConsecutiveCalls(false, $term, $term));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->post_title = 'postFour';
        $post->post_type = 'postType';

        $objectHandler->expects($this->exactly(2))
            ->method('getPost')
            ->withConsecutive([-1], [4])
            ->will($this->onConsecutiveCalls(false, $post));

        $objectHandler->expects($this->exactly(2))
            ->method('isPluggableObject')
            ->withConsecutive(['pluggableObject'], ['invalid'])
            ->will($this->onConsecutiveCalls(true, false));


        $pluggableObject = $this->createMock('\UserAccessManager\ObjectHandler\PluggableObject');
        $pluggableObject->expects($this->once())
            ->method('getObjectType')
            ->will($this->returnValue('pluggableObjectTypeName'));
        $pluggableObject->expects($this->once())
            ->method('getObjectName')
            ->with(5)
            ->will($this->returnValue('pluggableObjectFive'));

        $objectHandler->expects($this->once())
            ->method('getPluggableObject')
            ->with('pluggableObject')
            ->will($this->returnValue($pluggableObject));

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $objectHandler,
            $this->getAccessHandler()
        );

        self::setValue($adminObjectController, 'objectId', 'objectId');
        self::setValue($adminObjectController, 'objectType', 'objectType');

        $userGroup = $this->getUserGroup(1);
        $userGroup->expects($this->once())
            ->method('getRecursiveMembershipForObject')
            ->with('objectType', 'objectId')
            ->willReturn([
                'role' => [1],
                'user' => [-1, 2],
                'term' => [-1, 1, 3],
                'post' => [-1, 4],
                'pluggableObject' => [5],
                'invalid' => [-1]
            ]);

        $expected = [
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [1 => 'roleOne'],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [-1 => -1, 2 => 'userTwo'],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [-1 => -1, 1 => 'categoryThree'],
            'category' => [3 => 'categoryThree'],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE => [-1 => -1],
            'post' => [4 => 'postFour'],
            'pluggableObjectTypeName' => [5 => 'pluggableObjectFive']
        ];

        self::assertEquals($expected, $adminObjectController->getRecursiveMembership($userGroup));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkRightsToEditContent()
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

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $objectHandler,
            $accessHandler
        );

        $adminObjectController->checkRightsToEditContent();

        $_GET['post'] = -1;
        $adminObjectController->checkRightsToEditContent();

        $_GET['post'] = 1;
        $adminObjectController->checkRightsToEditContent();

        $_GET['post'] = 2;
        $adminObjectController->checkRightsToEditContent();

        unset($_GET['post']);
        $_GET['attachment_id'] = 3;
        $adminObjectController->checkRightsToEditContent();

        unset($_GET['attachment_id']);
        $_GET['tag_ID'] = 4;
        $adminObjectController->checkRightsToEditContent();

        $_GET['tag_ID'] = 5;
        $adminObjectController->checkRightsToEditContent();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    private function getObjectHandlerWithPosts()
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

        return $objectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::saveObjectData()
     * @covers \UserAccessManager\Controller\AdminObjectController::savePostData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveAttachmentData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveAjaxAttachmentData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveUserData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveTermData()
     * @covers \UserAccessManager\Controller\AdminObjectController::savePluggableObjectData()
     */
    public function testSaveObjectData()
    {
        $config = $this->getConfig();
        $config->expects($this->exactly(2))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(false, true));

        $objectHandler = $this->getObjectHandlerWithPosts();

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(11))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(false, false, true, true, true, true, true, true, true, true, true));

        $accessHandler->expects($this->exactly(9))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                ['attachment', 3],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1],
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
                $this->getUserGroupArray([2, 3])
            ));

        $accessHandler->expects($this->exactly(9))
            ->method('getFilteredUserGroups')
            ->will($this->onConsecutiveCalls(
                $this->getUserGroupArray([1, 3], [1, 2, 3], [['post', 1]]),
                $this->getUserGroupArray([2, 4], [1, 2, 4], [['post', 1]]),
                $this->getUserGroupArray([1, 2], [2, 3, 4], [['post', 1]]),
                $this->getUserGroupArray([3, 4], [1, 3, 4], [['attachment', 3]]),
                $this->getUserGroupArray([], [2, 3], [['attachment', 3]]),
                $this->getUserGroupArray([3, 4], [1, 3, 4], [[ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3]]),
                $this->getUserGroupArray([2], [3, 4], [[ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1]]),
                $this->getUserGroupArray([3], [1, 4], [[ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1]]),
                $this->getUserGroupArray([4], [2, 3], [['objectType', 'objectId']])
            ));

        $accessHandler->expects($this->exactly(9))
            ->method('unsetUserGroupsForObject');

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $config,
            $this->getDatabase(),
            $objectHandler,
            $accessHandler
        );

        $_POST[AdminObjectController::UPDATE_GROUPS_FORM_NAME] = 1;
        $adminObjectController->savePostData(['ID' => 1]);

        $_POST['uam_user_groups'] = [1, 3];
        $adminObjectController->savePostData(['ID' => 1]);
        $_POST['uam_user_groups'] = [2, 4];
        $adminObjectController->savePostData(['ID' => 2]);
        $_POST['uam_user_groups'] = [1, 2];
        $adminObjectController->savePostData(2);
        $_POST['uam_user_groups'] = [3, 4];
        $adminObjectController->saveAttachmentData(['ID' => 3]);

        //unset($_POST[AdminObjectController::UPDATE_GROUPS_FORM_NAME]);
        $_POST['uam_bulk_type'] = AdminObjectController::BULK_REMOVE;
        $_POST['uam_user_groups'] = [2, 3];
        $adminObjectController->saveAttachmentData(['ID' => 3]);

        $_POST = [
            AdminObjectController::UPDATE_GROUPS_FORM_NAME => 1,
            'id' => 3,
            'uam_user_groups' => [3, 4]
        ];
        $adminObjectController->saveAjaxAttachmentData();

        $_POST['uam_user_groups'] = [2];
        $adminObjectController->saveUserData(1);
        $_POST['uam_user_groups'] = [3];
        $adminObjectController->saveTermData(1);
        $_POST['uam_user_groups'] = [4];
        $adminObjectController->savePluggableObjectData('objectType', 'objectId');

        $_POST = [];
        $adminObjectController->savePluggableObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::removeObjectData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removePostData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removeUserData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removeTermData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removePluggableObjectData()
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

        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $database,
            $this->getObjectHandlerWithPosts(),
            $this->getAccessHandler()
        );

        $adminObjectController->removePostData(1);
        $adminObjectController->removeUserData(2);
        $adminObjectController->removeTermData(3);
        $adminObjectController->removePluggableObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::addPostColumnsHeader()
     * @covers \UserAccessManager\Controller\AdminObjectController::addUserColumnsHeader()
     * @covers \UserAccessManager\Controller\AdminObjectController::addTermColumnsHeader()
     */
    public function testAddColumnsHeader()
    {
        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals(
            ['a' => 'a', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $adminObjectController->addPostColumnsHeader(['a' => 'a'])
        );
        self::assertEquals(
            ['b' => 'b', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_USER_GROUPS],
            $adminObjectController->addUserColumnsHeader(['b' => 'b'])
        );
        self::assertEquals(
            ['c' => 'c', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $adminObjectController->addTermColumnsHeader(['c' => 'c'])
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::addPostColumn()
     * @covers \UserAccessManager\Controller\AdminObjectController::addUserColumn()
     * @covers \UserAccessManager\Controller\AdminObjectController::addTermColumn()
     * @covers \UserAccessManager\Controller\AdminObjectController::getPluggableColumn()
     */
    public function testAddColumn()
    {
        $adminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandlerWithPosts(),
            $this->getAccessHandler()
        );

        $adminObjectController->addPostColumn(AdminObjectController::COLUMN_NAME, 1);
        $adminObjectController->addUserColumn('return', AdminObjectController::COLUMN_NAME, 1);
        $adminObjectController->addTermColumn('content', AdminObjectController::COLUMN_NAME, 1);
        $adminObjectController->getPluggableColumn('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::addPostColumn()
     * @covers \UserAccessManager\Controller\AdminObjectController::addUserColumn()
     * @covers \UserAccessManager\Controller\AdminObjectController::addTermColumn()
     * @covers \UserAccessManager\Controller\AdminObjectController::getPluggableColumn()
     * @covers \UserAccessManager\Controller\AdminObjectController::editPostContent()
     * @covers \UserAccessManager\Controller\AdminObjectController::addBulkAction()
     * @covers \UserAccessManager\Controller\AdminObjectController::showMediaFile()
     * @covers \UserAccessManager\Controller\AdminObjectController::showUserProfile()
     * @covers \UserAccessManager\Controller\AdminObjectController::showTermEditForm()
     * @covers \UserAccessManager\Controller\AdminObjectController::showPluggableGroupSelectionForm()
     * @covers \UserAccessManager\Controller\AdminObjectController::getGroupsFromName()
     */
    public function testEditForm()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('src', new Directory([
            'UserAccessManager'  => new Directory([
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
            ])
        ]));

        $php = $this->getPhp();

        $config = $this->getConfig();
        $config->expects($this->exactly(16))
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(10))
            ->method('getUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1],
                ['objectType', 'objectId'],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4],
                ['category', 5],
                ['objectType', 'objectId']
            )
            ->will($this->returnValue([]));

        $accessHandler->expects($this->exactly(10))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1],
                ['objectType', 'objectId'],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4],
                ['category', 5],
                ['objectType', 'objectId']
            )
            ->will($this->returnValue([]));

        $adminObjectController = new AdminObjectController(
            $php,
            $this->getWordpress(),
            $config,
            $this->getDatabase(),
            $this->getObjectHandlerWithPosts(),
            $accessHandler
        );

        $php->expects($this->exactly(16))
            ->method('includeFile')
            ->withConsecutive(
                [$adminObjectController, 'vfs://src/UserAccessManager/View/ObjectColumn.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/UserColumn.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/ObjectColumn.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/ObjectColumn.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/PostEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/PostEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/BulkEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/MediaAjaxEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/MediaAjaxEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/MediaAjaxEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/UserProfileEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/UserProfileEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/TermEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/TermEditForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/GroupSelectionForm.php'],
                [$adminObjectController, 'vfs://src/UserAccessManager/View/GroupSelectionForm.php']
            )
            ->will($this->returnCallback(function (AdminObjectController $controller, $file) {
                echo '!'.get_class($controller).'|'.$file.'|'.$controller->getGroupsFromName().'!';
            }));

        $adminObjectController->addPostColumn('invalid', 1);
        $adminObjectController->addPostColumn('invalid', 1);
        $adminObjectController->addPostColumn(AdminObjectController::COLUMN_NAME, 1);
        self::assertAttributeEquals('post', 'objectType', $adminObjectController);
        self::assertAttributeEquals(1, 'objectId', $adminObjectController);
        $expectedOutput = '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals('return', $adminObjectController->addUserColumn('return', 'invalid', 1));
        self::assertEquals('return', $adminObjectController->addUserColumn('return', 'invalid', 1));

        $expected = 'return!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/UserColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $adminObjectController->addUserColumn('return', AdminObjectController::COLUMN_NAME, 1)
        );
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $adminObjectController);
        self::assertAttributeEquals(1, 'objectId', $adminObjectController);

        self::assertEquals('content', $adminObjectController->addTermColumn('content', 'invalid', 1));
        self::assertEquals('content', $adminObjectController->addTermColumn('content', 'invalid', 1));

        $expected = 'content!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $adminObjectController->addTermColumn('content', AdminObjectController::COLUMN_NAME, 1)
        );
        self::assertAttributeEquals(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'objectType', $adminObjectController);
        self::assertAttributeEquals(1, 'objectId', $adminObjectController);

        $expected = '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $adminObjectController->getPluggableColumn('objectType', 'objectId')
        );
        self::assertAttributeEquals('objectType', 'objectType', $adminObjectController);
        self::assertAttributeEquals('objectId', 'objectId', $adminObjectController);

        self::setValue($adminObjectController, 'objectType', null);
        self::setValue($adminObjectController, 'objectId', null);

        $adminObjectController->editPostContent(null);
        self::assertAttributeEquals(null, 'objectType', $adminObjectController);
        self::assertAttributeEquals(null, 'objectId', $adminObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/PostEditForm.php|uam_user_groups!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        $adminObjectController->editPostContent($post);
        self::assertAttributeEquals('post', 'objectType', $adminObjectController);
        self::assertAttributeEquals(1, 'objectId', $adminObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/PostEditForm.php|uam_user_groups!';
        self::setValue($adminObjectController, 'objectType', null);
        self::setValue($adminObjectController, 'objectId', null);

        $adminObjectController->addBulkAction('invalid');
        $expectedOutput .= '';

        $adminObjectController->addBulkAction('invalid');
        $expectedOutput .= '';

        $adminObjectController->addBulkAction(AdminObjectController::COLUMN_NAME);
        $expectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/BulkEditForm.php|uam_user_groups!';


        $return = $adminObjectController->showMediaFile(['a' => 'b']);
        self::assertAttributeEquals(null, 'objectType', $adminObjectController);
        self::assertAttributeEquals(null, 'objectId', $adminObjectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\AdminObjectController|'
                        .'vfs://src/UserAccessManager/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );

        $return = $adminObjectController->showMediaFile(['a' => 'b'], $post);
        self::assertAttributeEquals('post', 'objectType', $adminObjectController);
        self::assertAttributeEquals(1, 'objectId', $adminObjectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\AdminObjectController|'
                        .'vfs://src/UserAccessManager/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );

        self::setValue($adminObjectController, 'objectType', null);
        self::setValue($adminObjectController, 'objectId', null);

        $_GET['attachment_id'] = 3;
        $return = $adminObjectController->showMediaFile(['a' => 'b'], $post);
        self::assertAttributeEquals('attachment', 'objectType', $adminObjectController);
        self::assertAttributeEquals(3, 'objectId', $adminObjectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\AdminObjectController|'
                        .'vfs://src/UserAccessManager/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );

        self::setValue($adminObjectController, 'objectType', null);
        self::setValue($adminObjectController, 'objectId', null);

        $adminObjectController->showUserProfile();
        self::assertAttributeEquals(null, 'objectType', $adminObjectController);
        self::assertAttributeEquals(null, 'objectId', $adminObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/UserProfileEditForm.php|uam_user_groups!';

        $_GET['user_id'] = 4;
        $adminObjectController->showUserProfile();
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $adminObjectController);
        self::assertAttributeEquals(4, 'objectId', $adminObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/UserProfileEditForm.php|uam_user_groups!';
        self::setValue($adminObjectController, 'objectType', null);
        self::setValue($adminObjectController, 'objectId', null);
        unset($_GET['user_id']);

        $adminObjectController->showTermEditForm(null);
        self::assertAttributeEquals(null, 'objectType', $adminObjectController);
        self::assertAttributeEquals(null, 'objectId', $adminObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/TermEditForm.php|uam_user_groups!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass|\WP_Term $term
         */
        $term = $this->getMockBuilder('\WP_Term')->getMock();
        $term->term_id = 5;
        $term->taxonomy = 'category';
        $adminObjectController->showTermEditForm($term);

        self::assertAttributeEquals('category', 'objectType', $adminObjectController);
        self::assertAttributeEquals(5, 'objectId', $adminObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/TermEditForm.php|uam_user_groups!';
        self::setValue($adminObjectController, 'objectType', null);
        self::setValue($adminObjectController, 'objectId', null);

        $return = $adminObjectController->showPluggableGroupSelectionForm('objectType', 'objectId', 'otherForm');
        self::assertEquals(
            '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/GroupSelectionForm.php|otherForm!',
            $return
        );
        self::assertAttributeEquals('objectType', 'objectType', $adminObjectController);
        self::assertAttributeEquals('objectId', 'objectId', $adminObjectController);
        self::assertEquals(
            AdminObjectController::DEFAULT_GROUPS_FORM_NAME,
            $adminObjectController->getGroupsFromName()
        );
        $expectedOutput .= '';

        $userGroups = [
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        $return = $adminObjectController->showPluggableGroupSelectionForm('objectType', 'objectId', null, $userGroups);
        self::assertEquals(
            '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/GroupSelectionForm.php|uam_user_groups!',
            $return
        );
        self::assertAttributeEquals('objectType', 'objectType', $adminObjectController);
        self::assertAttributeEquals('objectId', 'objectId', $adminObjectController);
        self::assertAttributeEquals($userGroups, 'objectUserGroups', $adminObjectController);
        self::assertEquals(
            AdminObjectController::DEFAULT_GROUPS_FORM_NAME,
            $adminObjectController->getGroupsFromName()
        );
        $expectedOutput .= '';

        self::expectOutputString($expectedOutput);
    }
}
