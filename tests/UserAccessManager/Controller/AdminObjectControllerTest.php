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
 * @version   SVN: $Id$
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
    private $Root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->oRoot = FileSystem::factory('vfs://');
        $this->oRoot->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->oRoot->unmount();
    }
    /**
     * @param int   $iId
     * @param array $aWithAdd
     * @param array $aWithRemove
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroup
     */
    protected function getUserGroupWithAddDelete($iId, array $aWithAdd = [], array $aWithRemove = [])
    {
        $UserGroup = $this->getUserGroup($iId);

        if (count($aWithAdd) > 0) {
            $UserGroup->expects($this->exactly(count($aWithAdd)))
                ->method('addObject')
                ->withConsecutive(...$aWithAdd);
        }

        if (count($aWithRemove) > 0) {
            $UserGroup->expects($this->exactly(count($aWithRemove)))
                ->method('removeObject')
                ->withConsecutive(...$aWithRemove);
        }

        return $UserGroup;
    }

    /**
     * @param array $aAddIds
     * @param array $aRemoveIds
     * @param array $aWith
     *
     * @return array
     */
    private function getUserGroupArray(array $aAddIds, array $aRemoveIds = [], array $aWith = [])
    {
        $aGroups = [];

        $aBoth = array_intersect($aAddIds, $aRemoveIds);

        foreach ($aBoth as $sId) {
            $aGroups[$sId] = $this->getUserGroupWithAddDelete($sId, $aWith, $aWith);
        }

        $aAdd = array_diff($aAddIds, $aBoth);

        foreach ($aAdd as $sId) {
            $aGroups[$sId] = $this->getUserGroupWithAddDelete($sId, $aWith, []);
        }

        $aRemove = array_diff($aRemoveIds, $aBoth);

        foreach ($aRemove as $sId) {
            $aGroups[$sId] = $this->getUserGroupWithAddDelete($sId, [], $aWith);
        }

        return $aGroups;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::__construct()
     */
    public function testCanCreateInstance()
    {
        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminObjectController', $AdminObjectController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::setObjectInformation()
     *
     * @return AdminObjectController
     */
    public function testSetObjectInformation()
    {
        $aFullGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $aFilteredGroups = [
            1 => $this->getUserGroup(1)
        ];

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('objectType', 'objectId')
            ->will($this->returnValue($aFullGroups));

        $AccessHandler->expects($this->once())
            ->method('getFilteredUserGroupsForObject')
            ->with('objectType', 'objectId')
            ->will($this->returnValue($aFilteredGroups));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $AccessHandler
        );

        self::callMethod($AdminObjectController, 'setObjectInformation', ['objectType', 'objectId']);

        self::assertAttributeEquals('objectType', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals('objectId', 'sObjectId', $AdminObjectController);
        self::assertAttributeEquals($aFullGroups, 'aObjectUserGroups', $AdminObjectController);
        self::assertAttributeEquals($aFilteredGroups, 'aFilteredObjectUserGroups', $AdminObjectController);
        self::assertAttributeEquals(1, 'iUserGroupDiff', $AdminObjectController);

        return $AdminObjectController;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectType()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $AdminObjectController
     */
    public function testGetObjectType(AdminObjectController $AdminObjectController)
    {
        self::assertEquals('objectType', $AdminObjectController->getObjectType());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectId()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $AdminObjectController
     */
    public function testGetObjectId(AdminObjectController $AdminObjectController)
    {
        self::assertEquals('objectId', $AdminObjectController->getObjectId());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $AdminObjectController
     */
    public function testGetFullObjectUserGroups(AdminObjectController $AdminObjectController)
    {
        self::assertEquals(
            [1 => $this->getUserGroup(1), 2 => $this->getUserGroup(2)],
            $AdminObjectController->getObjectUserGroups()
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getFilteredObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $AdminObjectController
     */
    public function testGetObjectUserGroups(AdminObjectController $AdminObjectController)
    {
        self::assertEquals([1 => $this->getUserGroup(1)], $AdminObjectController->getFilteredObjectUserGroups());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getUserGroupDiff()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $AdminObjectController
     */
    public function testGetUserGroupDiff(AdminObjectController $AdminObjectController)
    {
        self::assertEquals(1, $AdminObjectController->getUserGroupDiff());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getUserGroups()
     */
    public function testGetUserGroups()
    {
        $aUserGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3)
        ];

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($aUserGroups));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $AccessHandler
        );

        self::assertEquals($aUserGroups, $AdminObjectController->getUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $aUserGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->once())
            ->method('getFilteredUserGroups')
            ->will($this->returnValue($aUserGroups));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $AccessHandler
        );

        self::assertEquals($aUserGroups, $AdminObjectController->getFilteredUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::isCurrentUserAdmin()
     */
    public function testIsCurrentUserAdmin()
    {
        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(2))
            ->method('userIsAdmin')
            ->with('objectId')
            ->will($this->onConsecutiveCalls(false, true));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $AccessHandler
        );

        self::assertFalse($AdminObjectController->isCurrentUserAdmin());

        self::setValue($AdminObjectController, 'sObjectType', ObjectHandler::GENERAL_USER_OBJECT_TYPE);
        self::assertFalse($AdminObjectController->isCurrentUserAdmin());

        self::setValue($AdminObjectController, 'sObjectId', 'objectId');
        self::assertFalse($AdminObjectController->isCurrentUserAdmin());
        self::assertTrue($AdminObjectController->isCurrentUserAdmin());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $Roles = new \stdClass();
        $Roles->role_names = 'roleNames';

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($Roles));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals('roleNames', $AdminObjectController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getAllObjectTypes()
     */
    public function testGetAllObjectTypes()
    {
        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue([1 => 1, 2 => 2]));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $ObjectHandler,
            $this->getAccessHandler()
        );

        self:self::assertEquals([1 => 1, 2 => 2], $AdminObjectController->getAllObjectTypes());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkUserAccess()
     */
    public function testCheckUserAccess()
    {
        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $AccessHandler
        );

        self::assertFalse($AdminObjectController->checkUserAccess());
        self::assertTrue($AdminObjectController->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRecursiveMembership()
     */
    public function testGetRecursiveMembership()
    {
        $Roles = new \stdClass();
        $Roles->role_names = [1 => 'roleOne'];

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($Roles));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Taxonomy
         */
        $Taxonomy = $this->getMockBuilder('\WP_Taxonomy')->getMock();
        $Taxonomy->labels = new \stdClass();
        $Taxonomy->labels->name = 'category';

        $Wordpress->expects($this->exactly(2))
            ->method('getTaxonomy')
            ->with('termTaxonomy')
            ->will($this->onConsecutiveCalls(false, $Taxonomy));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $PostType
         */
        $PostType = $this->getMockBuilder('\WP_Post_Type')->getMock();
        $PostType->labels = new \stdClass();
        $PostType->labels->name = 'post';

        $Wordpress->expects($this->once())
            ->method('getPostTypeObject')
            ->with('postType')
            ->will($this->returnValue($PostType));


        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->exactly(10))
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
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->display_name = 'userTwo';

        $ObjectHandler->expects($this->exactly(2))
            ->method('getUser')
            ->withConsecutive([-1], [2])
            ->will($this->onConsecutiveCalls(false, $User));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Term
         */
        $Term = $this->getMockBuilder('\WP_Term')->getMock();
        $Term->name = 'categoryThree';
        $Term->taxonomy = 'termTaxonomy';

        $ObjectHandler->expects($this->exactly(3))
            ->method('getTerm')
            ->withConsecutive([-1], [1], [3])
            ->will($this->onConsecutiveCalls(false, $Term, $Term));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Post
         */
        $Post = $this->getMockBuilder('\WP_Post')->getMock();
        $Post->post_title = 'postFour';
        $Post->post_type = 'postType';

        $ObjectHandler->expects($this->exactly(2))
            ->method('getPost')
            ->withConsecutive([-1], [4])
            ->will($this->onConsecutiveCalls(false, $Post));

        $ObjectHandler->expects($this->exactly(2))
            ->method('isPluggableObject')
            ->withConsecutive(['pluggableObject'], ['invalid'])
            ->will($this->onConsecutiveCalls(true, false));


        $PluggableObject = $this->createMock('\UserAccessManager\ObjectHandler\PluggableObject');
        $PluggableObject->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pluggableObjectTypeName'));
        $PluggableObject->expects($this->once())
            ->method('getObjectName')
            ->with(5)
            ->will($this->returnValue('pluggableObjectFive'));

        $ObjectHandler->expects($this->once())
            ->method('getPluggableObject')
            ->with('pluggableObject')
            ->will($this->returnValue($PluggableObject));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $ObjectHandler,
            $this->getAccessHandler()
        );

        self::setValue($AdminObjectController, 'sObjectId', 'objectId');
        self::setValue($AdminObjectController, 'sObjectType', 'objectType');

        $UserGroup = $this->getUserGroup(1);
        $UserGroup->expects($this->once())
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

        $aExpected = [
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [1 => 'roleOne'],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [-1 => -1, 2 => 'userTwo'],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [-1 => -1, 1 => 'categoryThree'],
            'category' => [3 => 'categoryThree'],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE => [-1 => -1],
            'post' => [4 => 'postFour'],
            'pluggableObjectTypeName' => [5 => 'pluggableObjectFive']
        ];

        self::assertEquals($aExpected, $AdminObjectController->getRecursiveMembership($UserGroup));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkRightsToEditContent()
     */
    public function testCheckRightsToEditContent()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(3))
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Post
         */
        $Post = $this->getMockBuilder('\WP_Post')->getMock();
        $Post->ID = 1;
        $Post->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $NoAccessPost
         */
        $NoAccessPost = $this->getMockBuilder('\WP_Post')->getMock();
        $NoAccessPost->ID = 2;
        $NoAccessPost->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Attachment
         */
        $Attachment = $this->getMockBuilder('\WP_Post')->getMock();
        $Attachment->ID = 3;
        $Attachment->post_type = 'attachment';

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive([-1], [1], [2], [3])
            ->will($this->onConsecutiveCalls(false, $Post, $NoAccessPost, $Attachment));

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(5))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2],
                ['attachment', 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5]
            )
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $ObjectHandler,
            $AccessHandler
        );

        $AdminObjectController->checkRightsToEditContent();

        $_GET['post'] = -1;
        $AdminObjectController->checkRightsToEditContent();

        $_GET['post'] = 1;
        $AdminObjectController->checkRightsToEditContent();

        $_GET['post'] = 2;
        $AdminObjectController->checkRightsToEditContent();

        unset($_GET['post']);
        $_GET['attachment_id'] = 3;
        $AdminObjectController->checkRightsToEditContent();

        unset($_GET['attachment_id']);
        $_GET['tag_ID'] = 4;
        $AdminObjectController->checkRightsToEditContent();

        $_GET['tag_ID'] = 5;
        $AdminObjectController->checkRightsToEditContent();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    private function getObjectHandlerWithPosts()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Post
         */
        $Post = $this->getMockBuilder('\WP_Post')->getMock();
        $Post->ID = 1;
        $Post->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $RevisionPost
         */
        $RevisionPost = $this->getMockBuilder('\WP_Post')->getMock();
        $RevisionPost->ID = 2;
        $RevisionPost->post_type = 'revision';
        $RevisionPost->post_parent = 1;

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Attachment
         */
        $Attachment = $this->getMockBuilder('\WP_Post')->getMock();
        $Attachment->ID = 3;
        $Attachment->post_type = 'attachment';

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->any())
            ->method('getPost')
            ->will($this->returnCallback(function ($iPostId) use ($Post, $RevisionPost, $Attachment) {
                if ($iPostId === 1) {
                    return $Post;
                } elseif ($iPostId === 2) {
                    return $RevisionPost;
                } elseif ($iPostId === 3) {
                    return $Attachment;
                }

                return false;
            }));

        return $ObjectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::saveObjectData()
     * @covers \UserAccessManager\Controller\AdminObjectController::savePostData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveAttachmentData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveUserData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveTermData()
     * @covers \UserAccessManager\Controller\AdminObjectController::savePluggableObjectData()
     */
    public function testSaveObjectData()
    {
        $Config = $this->getConfig();
        $Config->expects($this->exactly(2))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(false, true));

        $ObjectHandler = $this->getObjectHandlerWithPosts();

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(9))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(false, false, true, true, true, true, true, true, true));

        $AccessHandler->expects($this->exactly(8))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                ['attachment', 3],
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
                $this->getUserGroupArray([3, 4]),
                $this->getUserGroupArray([1, 4]),
                $this->getUserGroupArray([2, 3])
            ));

        $AccessHandler->expects($this->exactly(8))
            ->method('getFilteredUserGroups')
            ->will($this->onConsecutiveCalls(
                $this->getUserGroupArray([1, 3], [1, 2, 3], [['post', 1]]),
                $this->getUserGroupArray([2, 4], [1, 2, 4], [['post', 1]]),
                $this->getUserGroupArray([1, 2], [2, 3, 4], [['post', 1]]),
                $this->getUserGroupArray([3, 4], [1, 3, 4], [['attachment', 3]]),
                $this->getUserGroupArray([], [2, 3], [['attachment', 3]]),
                $this->getUserGroupArray([2], [3, 4], [[ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1]]),
                $this->getUserGroupArray([3], [1, 4], [[ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1]]),
                $this->getUserGroupArray([4], [2, 3], [['objectType', 'objectId']])
            ));

        $AccessHandler->expects($this->exactly(8))
            ->method('unsetUserGroupsForObject');

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $Config,
            $this->getDatabase(),
            $ObjectHandler,
            $AccessHandler
        );

        $AdminObjectController->savePostData(['ID' => 1]);

        $_POST['uam_update_groups'] = [1, 3];
        $AdminObjectController->savePostData(['ID' => 1]);
        $_POST['uam_update_groups'] = [2, 4];
        $AdminObjectController->savePostData(['ID' => 2]);
        $_POST['uam_update_groups'] = [1, 2];
        $AdminObjectController->savePostData(2);
        $_POST['uam_update_groups'] = [3, 4];
        $AdminObjectController->saveAttachmentData(['ID' => 3]);

        $_POST['uam_bulk_type'] = AdminObjectController::BULK_REMOVE;
        $_POST['uam_update_groups'] = [2, 3];
        $AdminObjectController->saveAttachmentData(['ID' => 3]);

        unset($_POST['uam_bulk_type']);
        $_POST['uam_update_groups'] = [2];
        $AdminObjectController->saveUserData(1);
        $_POST['uam_update_groups'] = [3];
        $AdminObjectController->saveTermData(1);
        $_POST['uam_update_groups'] = [4];
        $AdminObjectController->savePluggableObjectData('objectType', 'objectId');
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
        $Database = $this->getDatabase();
        $Database->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $Database->expects($this->exactly(4))
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

        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $Database,
            $this->getObjectHandlerWithPosts(),
            $this->getAccessHandler()
        );

        $AdminObjectController->removePostData(1);
        $AdminObjectController->removeUserData(2);
        $AdminObjectController->removeTermData(3);
        $AdminObjectController->removePluggableObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::addPostColumnsHeader()
     * @covers \UserAccessManager\Controller\AdminObjectController::addUserColumnsHeader()
     * @covers \UserAccessManager\Controller\AdminObjectController::addTermColumnsHeader()
     */
    public function testAddColumnsHeader()
    {
        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals(
            ['a' => 'a', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $AdminObjectController->addPostColumnsHeader(['a' => 'a'])
        );
        self::assertEquals(
            ['b' => 'b', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_USER_GROUPS],
            $AdminObjectController->addUserColumnsHeader(['b' => 'b'])
        );
        self::assertEquals(
            ['c' => 'c', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $AdminObjectController->addTermColumnsHeader(['c' => 'c'])
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
        $AdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandlerWithPosts(),
            $this->getAccessHandler()
        );

        $AdminObjectController->addPostColumn(AdminObjectController::COLUMN_NAME, 1);
        $AdminObjectController->addUserColumn('return', AdminObjectController::COLUMN_NAME, 1);
        $AdminObjectController->addTermColumn('content', AdminObjectController::COLUMN_NAME, 1);
        $AdminObjectController->getPluggableColumn('objectType', 'objectId');
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
     */
    public function testEditForm()
    {
        /**
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('src', new Directory([
            'UserAccessManager'  => new Directory([
                'View'  => new Directory([
                    'ObjectColumn.php' => new File('<?php echo \'ObjectColumn\';'),
                    'UserColumn.php' => new File('<?php echo \'UserColumn\';'),
                    'PostEditForm.php' => new File('<?php echo \'PostEditForm\';'),
                    'BulkEditForm.php' => new File('<?php echo \'BulkEditForm\';'),
                    'UserProfileEditForm.php' => new File('<?php echo \'UserProfileEditForm\';'),
                    'TermEditForm.php' => new File('<?php echo \'TermEditForm\';'),
                    'GroupSelectionForm.php' => new File('<?php echo \'GroupSelectionForm\';')
                ])
            ])
        ]));

        $Php = $this->getPhp();

        $Config = $this->getConfig();
        $Config->expects($this->exactly(15))
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(10))
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

        $AccessHandler->expects($this->exactly(10))
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

        $AdminObjectController = new AdminObjectController(
            $Php,
            $this->getWordpress(),
            $Config,
            $this->getDatabase(),
            $this->getObjectHandlerWithPosts(),
            $AccessHandler
        );

        $Php->expects($this->exactly(15))
            ->method('includeFile')
            ->withConsecutive(
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/ObjectColumn.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/UserColumn.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/ObjectColumn.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/ObjectColumn.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/PostEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/PostEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/BulkEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/PostEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/PostEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/PostEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/UserProfileEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/UserProfileEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/TermEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/TermEditForm.php'],
                [$AdminObjectController, 'vfs://src/UserAccessManager/View/GroupSelectionForm.php']
            )
            ->will($this->returnCallback(function (Controller $Controller, $sFile) {
                echo '!'.get_class($Controller).'|'.$sFile.'!';
            }));

        $AdminObjectController->addPostColumn('invalid', 1);
        $AdminObjectController->addPostColumn('invalid', 1);
        $AdminObjectController->addPostColumn(AdminObjectController::COLUMN_NAME, 1);
        self::assertAttributeEquals('post', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(1, 'sObjectId', $AdminObjectController);
        $sExpectedOutput = '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/ObjectColumn.php!';

        self::assertEquals('return', $AdminObjectController->addUserColumn('return', 'invalid', 1));
        self::assertEquals('return', $AdminObjectController->addUserColumn('return', 'invalid', 1));

        $sExpected = 'return!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/UserColumn.php!';

        self::assertEquals(
            $sExpected,
            $AdminObjectController->addUserColumn('return', AdminObjectController::COLUMN_NAME, 1)
        );
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(1, 'sObjectId', $AdminObjectController);

        self::assertEquals('content', $AdminObjectController->addTermColumn('content', 'invalid', 1));
        self::assertEquals('content', $AdminObjectController->addTermColumn('content', 'invalid', 1));

        $sExpected = 'content!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/ObjectColumn.php!';

        self::assertEquals(
            $sExpected,
            $AdminObjectController->addTermColumn('content', AdminObjectController::COLUMN_NAME, 1)
        );
        self::assertAttributeEquals(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(1, 'sObjectId', $AdminObjectController);

        $sExpected = '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/ObjectColumn.php!';

        self::assertEquals(
            $sExpected,
            $AdminObjectController->getPluggableColumn('objectType', 'objectId')
        );
        self::assertAttributeEquals('objectType', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals('objectId', 'sObjectId', $AdminObjectController);

        self::setValue($AdminObjectController, 'sObjectType', null);
        self::setValue($AdminObjectController, 'sObjectId', null);

        $AdminObjectController->editPostContent(null);
        self::assertAttributeEquals(null, 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(null, 'sObjectId', $AdminObjectController);
        $sExpectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/PostEditForm.php!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $Post
         */
        $Post = $this->getMockBuilder('\WP_Post')->getMock();
        $Post->ID = 1;
        $Post->post_type = 'post';

        $AdminObjectController->editPostContent($Post);
        self::assertAttributeEquals('post', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(1, 'sObjectId', $AdminObjectController);
        $sExpectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/PostEditForm.php!';
        self::setValue($AdminObjectController, 'sObjectType', null);
        self::setValue($AdminObjectController, 'sObjectId', null);

        $AdminObjectController->addBulkAction('invalid');
        $sExpectedOutput .= '';

        $AdminObjectController->addBulkAction('invalid');
        $sExpectedOutput .= '';

        $AdminObjectController->addBulkAction(AdminObjectController::COLUMN_NAME);
        $sExpectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/BulkEditForm.php!';

        $sExpected = 'meta</td></tr><tr><th class="label"><label>'.TXT_UAM_SET_UP_USER_GROUPS
            .'</label></th><td class="field">!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/PostEditForm.php!';

        $sReturn = $AdminObjectController->showMediaFile('meta');
        self::assertAttributeEquals(null, 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(null, 'sObjectId', $AdminObjectController);
        self::assertEquals($sExpected, $sReturn);
        $sExpectedOutput .= '';

        $sReturn = $AdminObjectController->showMediaFile('meta', $Post);
        self::assertAttributeEquals('post', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(1, 'sObjectId', $AdminObjectController);
        self::assertEquals($sExpected, $sReturn);
        $sExpectedOutput .= '';
        self::setValue($AdminObjectController, 'sObjectType', null);
        self::setValue($AdminObjectController, 'sObjectId', null);

        $_GET['attachment_id'] = 3;
        $sReturn = $AdminObjectController->showMediaFile('meta', $Post);
        self::assertAttributeEquals('attachment', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(3, 'sObjectId', $AdminObjectController);
        self::assertEquals($sExpected, $sReturn);
        $sExpectedOutput .= '';
        self::setValue($AdminObjectController, 'sObjectType', null);
        self::setValue($AdminObjectController, 'sObjectId', null);

        $AdminObjectController->showUserProfile();
        self::assertAttributeEquals(null, 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(null, 'sObjectId', $AdminObjectController);
        $sExpectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/UserProfileEditForm.php!';

        $_GET['user_id'] = 4;
        $AdminObjectController->showUserProfile();
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(4, 'sObjectId', $AdminObjectController);
        $sExpectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/UserProfileEditForm.php!';
        self::setValue($AdminObjectController, 'sObjectType', null);
        self::setValue($AdminObjectController, 'sObjectId', null);
        unset($_GET['user_id']);

        $AdminObjectController->showTermEditForm(null);
        self::assertAttributeEquals(null, 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(null, 'sObjectId', $AdminObjectController);
        $sExpectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/TermEditForm.php!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass|\WP_Term $Term
         */
        $Term = $this->getMockBuilder('\WP_Term')->getMock();
        $Term->term_id = 5;
        $Term->taxonomy = 'category';
        $AdminObjectController->showTermEditForm($Term);

        self::assertAttributeEquals('category', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals(5, 'sObjectId', $AdminObjectController);
        $sExpectedOutput .= '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/TermEditForm.php!';
        self::setValue($AdminObjectController, 'sObjectType', null);
        self::setValue($AdminObjectController, 'sObjectId', null);

        $sReturn = $AdminObjectController->showPluggableGroupSelectionForm('objectType', 'objectId');
        self::assertEquals(
            '!UserAccessManager\Controller\AdminObjectController|'
            .'vfs://src/UserAccessManager/View/GroupSelectionForm.php!',
            $sReturn
        );
        self::assertAttributeEquals('objectType', 'sObjectType', $AdminObjectController);
        self::assertAttributeEquals('objectId', 'sObjectId', $AdminObjectController);
        $sExpectedOutput .= '';

        self::expectOutputString($sExpectedOutput);
    }
}
