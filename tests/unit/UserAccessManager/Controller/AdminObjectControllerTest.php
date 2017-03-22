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
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class AdminObjectControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminObjectControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $oRoot;

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
        $oUserGroup = $this->getUserGroup($iId);

        if (count($aWithAdd) > 0) {
            $oUserGroup->expects($this->exactly(count($aWithAdd)))
                ->method('addObject')
                ->withConsecutive(...$aWithAdd);
        }

        if (count($aWithRemove) > 0) {
            $oUserGroup->expects($this->exactly(count($aWithRemove)))
                ->method('removeObject')
                ->withConsecutive(...$aWithRemove);
        }

        return $oUserGroup;
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
        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminObjectController', $oAdminObjectController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::_setObjectInformation()
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

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('objectType', 'objectId')
            ->will($this->returnValue($aFullGroups));

        $oAccessHandler->expects($this->once())
            ->method('getFilteredUserGroupsForObject')
            ->with('objectType', 'objectId')
            ->will($this->returnValue($aFilteredGroups));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::callMethod($oAdminObjectController, '_setObjectInformation', ['objectType', 'objectId']);

        self::assertAttributeEquals('objectType', '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals('objectId', '_sObjectId', $oAdminObjectController);
        self::assertAttributeEquals($aFullGroups, '_aObjectUserGroups', $oAdminObjectController);
        self::assertAttributeEquals($aFilteredGroups, '_aFilteredObjectUserGroups', $oAdminObjectController);
        self::assertAttributeEquals(1, '_iUserGroupDiff', $oAdminObjectController);

        return $oAdminObjectController;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectType()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetObjectType(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals('objectType', $oAdminObjectController->getObjectType());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectId()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetObjectId(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals('objectId', $oAdminObjectController->getObjectId());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetFullObjectUserGroups(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals(
            [1 => $this->getUserGroup(1), 2 => $this->getUserGroup(2)],
            $oAdminObjectController->getObjectUserGroups()
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getFilteredObjectUserGroups()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetObjectUserGroups(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals([1 => $this->getUserGroup(1)], $oAdminObjectController->getFilteredObjectUserGroups());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminObjectController::getUserGroupDiff()
     * @depends testSetObjectInformation
     *
     * @param AdminObjectController $oAdminObjectController
     */
    public function testGetUserGroupDiff(AdminObjectController $oAdminObjectController)
    {
        self::assertEquals(1, $oAdminObjectController->getUserGroupDiff());
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

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($aUserGroups));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertEquals($aUserGroups, $oAdminObjectController->getUserGroups());
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

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->once())
            ->method('getFilteredUserGroups')
            ->will($this->returnValue($aUserGroups));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertEquals($aUserGroups, $oAdminObjectController->getFilteredUserGroups());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::isCurrentUserAdmin()
     */
    public function testIsCurrentUserAdmin()
    {
        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(2))
            ->method('userIsAdmin')
            ->with('objectId')
            ->will($this->onConsecutiveCalls(false, true));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertFalse($oAdminObjectController->isCurrentUserAdmin());

        self::setValue($oAdminObjectController, '_sObjectType', ObjectHandler::GENERAL_USER_OBJECT_TYPE);
        self::assertFalse($oAdminObjectController->isCurrentUserAdmin());

        self::setValue($oAdminObjectController, '_sObjectId', 'objectId');
        self::assertFalse($oAdminObjectController->isCurrentUserAdmin());
        self::assertTrue($oAdminObjectController->isCurrentUserAdmin());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $oRoles = new \stdClass();
        $oRoles->role_names = 'roleNames';

        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($oRoles));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals('roleNames', $oAdminObjectController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getAllObjectTypes()
     */
    public function testGetAllObjectTypes()
    {
        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue([1 => 1, 2 => 2]));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $oObjectHandler,
            $this->getAccessHandler()
        );

        self:self::assertEquals([1 => 1, 2 => 2], $oAdminObjectController->getAllObjectTypes());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkUserAccess()
     */
    public function testCheckUserAccess()
    {
        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $oAccessHandler
        );

        self::assertFalse($oAdminObjectController->checkUserAccess());
        self::assertTrue($oAdminObjectController->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::getRecursiveMembership()
     */
    public function testGetRecursiveMembership()
    {
        $oRoles = new \stdClass();
        $oRoles->role_names = [1 => 'roleOne'];

        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($oRoles));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oTaxonomy
         */
        $oTaxonomy = $this->getMockBuilder('\WP_Taxonomy')->getMock();
        $oTaxonomy->labels = new \stdClass();
        $oTaxonomy->labels->name = 'category';

        $oWordpress->expects($this->once())
            ->method('getTaxonomy')
            ->with('termTaxonomy')
            ->will($this->returnValue($oTaxonomy));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oPostType
         */
        $oPostType = $this->getMockBuilder('\WP_Post_Type')->getMock();
        $oPostType->labels = new \stdClass();
        $oPostType->labels->name = 'post';

        $oWordpress->expects($this->once())
            ->method('getPostTypeObject')
            ->with('postType')
            ->will($this->returnValue($oPostType));


        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->exactly(9))
            ->method('getGeneralObjectType')
            ->withConsecutive(
                ['role'],
                ['user'], ['user'],
                ['term'], ['term'],
                ['post'], ['post'],
                ['pluggableObject']
            )
            ->will($this->onConsecutiveCalls(
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                ObjectHandler::GENERAL_USER_OBJECT_TYPE, ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE, ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                ObjectHandler::GENERAL_POST_OBJECT_TYPE, ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                null,
                null
            ));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->display_name = 'userTwo';

        $oObjectHandler->expects($this->exactly(2))
            ->method('getUser')
            ->withConsecutive([-1], [2])
            ->will($this->onConsecutiveCalls(
                false, $oUser
            ));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oTerm
         */
        $oTerm = $this->getMockBuilder('\WP_Term')->getMock();
        $oTerm->name = 'categoryThree';
        $oTerm->taxonomy = 'termTaxonomy';

        $oObjectHandler->expects($this->exactly(2))
            ->method('getTerm')
            ->withConsecutive([-1], [3])
            ->will($this->onConsecutiveCalls(
                false, $oTerm
            ));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oPost
         */
        $oPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oPost->post_title = 'postFour';
        $oPost->post_type = 'postType';

        $oObjectHandler->expects($this->exactly(2))
            ->method('getPost')
            ->withConsecutive([-1], [4])
            ->will($this->onConsecutiveCalls(
                false, $oPost
            ));

        $oObjectHandler->expects($this->exactly(2))
            ->method('isPluggableObject')
            ->withConsecutive(['pluggableObject'], ['invalid'])
            ->will($this->onConsecutiveCalls(true, false));


        $oPluggableObject = $this->createMock('\UserAccessManager\ObjectHandler\PluggableObject');
        $oPluggableObject->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pluggableObjectTypeName'));
        $oPluggableObject->expects($this->once())
            ->method('getObjectName')
            ->with(5)
            ->will($this->returnValue('pluggableObjectFive'));

        $oObjectHandler->expects($this->once())
            ->method('getPluggableObject')
            ->with('pluggableObject')
            ->will($this->returnValue($oPluggableObject));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $oObjectHandler,
            $this->getAccessHandler()
        );

        self::setValue($oAdminObjectController, '_sObjectId', 'objectId');
        self::setValue($oAdminObjectController, '_sObjectType', 'objectType');

        $oUserGroup = $this->getUserGroup(1);
        $oUserGroup->expects($this->once())
            ->method('getRecursiveMembershipForObject')
            ->with('objectType', 'objectId')
            ->willReturn([
                'role' => [1],
                'user' => [-1, 2],
                'term' => [-1, 3],
                'post' => [-1, 4],
                'pluggableObject' => [5],
                'invalid' => [-1]
            ]);

        $aExpected = [
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [1 => 'roleOne'],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [-1 => -1, 2 => 'userTwo'],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [-1 => -1],
            'category' => [3 => 'categoryThree'],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE => [-1 => -1],
            'post' => [4 => 'postFour'],
            'pluggableObjectTypeName' => [5 => 'pluggableObjectFive']
        ];

        self::assertEquals($aExpected, $oAdminObjectController->getRecursiveMembership($oUserGroup));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::checkRightsToEditContent()
     */
    public function testCheckRightsToEditContent()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->exactly(3))
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oPost
         */
        $oPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oPost->ID = 1;
        $oPost->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oNoAccessPost
         */
        $oNoAccessPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oNoAccessPost->ID = 2;
        $oNoAccessPost->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oAttachment
         */
        $oAttachment = $this->getMockBuilder('\WP_Post')->getMock();
        $oAttachment->ID = 3;
        $oAttachment->post_type = 'attachment';

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->exactly(3))
            ->method('getPost')
            ->withConsecutive([1], [2], [3])
            ->will($this->onConsecutiveCalls($oPost, $oNoAccessPost, $oAttachment));

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(5))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2],
                ['attachment', 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5]
            )
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $oObjectHandler,
            $oAccessHandler
        );

        $oAdminObjectController->checkRightsToEditContent();

        $_GET['post'] = 1;
        $oAdminObjectController->checkRightsToEditContent();

        $_GET['post'] = 2;
        $oAdminObjectController->checkRightsToEditContent();

        unset($_GET['post']);
        $_GET['attachment_id'] = 3;
        $oAdminObjectController->checkRightsToEditContent();

        unset($_GET['attachment_id']);
        $_GET['tag_ID'] = 4;
        $oAdminObjectController->checkRightsToEditContent();

        $_GET['tag_ID'] = 5;
        $oAdminObjectController->checkRightsToEditContent();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    private function getObjectHandlerWithPosts()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oPost
         */
        $oPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oPost->ID = 1;
        $oPost->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oRevisionPost
         */
        $oRevisionPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oRevisionPost->ID = 2;
        $oRevisionPost->post_type = 'revision';
        $oRevisionPost->post_parent = 1;

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oAttachment
         */
        $oAttachment = $this->getMockBuilder('\WP_Post')->getMock();
        $oAttachment->ID = 3;
        $oAttachment->post_type = 'attachment';

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->any())
            ->method('getPost')
            ->will($this->returnCallback(function ($iPostId) use ($oPost, $oRevisionPost, $oAttachment) {
                if ($iPostId === 1) {
                    return $oPost;
                } elseif ($iPostId === 2) {
                    return $oRevisionPost;
                } elseif ($iPostId === 3) {
                    return $oAttachment;
                }

                return false;
            }));

        return $oObjectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::_saveObjectData()
     * @covers \UserAccessManager\Controller\AdminObjectController::savePostData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveAttachmentData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveUserData()
     * @covers \UserAccessManager\Controller\AdminObjectController::saveTermData()
     * @covers \UserAccessManager\Controller\AdminObjectController::savePluggableObjectData()
     */
    public function testSaveObjectData()
    {
        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(2))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(
                false, true
            ));

        $oObjectHandler = $this->getObjectHandlerWithPosts();

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(9))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(
                false, false, true, true, true, true, true, true, true
            ));

        $oAccessHandler->expects($this->exactly(8))
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

        $oAccessHandler->expects($this->exactly(8))
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

        $oAccessHandler->expects($this->exactly(8))
            ->method('unsetUserGroupsForObject');

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $oConfig,
            $this->getDatabase(),
            $oObjectHandler,
            $oAccessHandler
        );

        $oAdminObjectController->savePostData(['ID' => 1]);

        $_POST['uam_update_groups'] = [1, 3];
        $oAdminObjectController->savePostData(['ID' => 1]);
        $_POST['uam_update_groups'] = [2, 4];
        $oAdminObjectController->savePostData(['ID' => 2]);
        $_POST['uam_update_groups'] = [1, 2];
        $oAdminObjectController->savePostData(2);
        $_POST['uam_update_groups'] = [3, 4];
        $oAdminObjectController->saveAttachmentData(['ID' => 3]);

        $_POST['uam_bulk_type'] = AdminObjectController::BULK_REMOVE;
        $_POST['uam_update_groups'] = [2, 3];
        $oAdminObjectController->saveAttachmentData(['ID' => 3]);

        unset($_POST['uam_bulk_type']);
        $_POST['uam_update_groups'] = [2];
        $oAdminObjectController->saveUserData(1);
        $_POST['uam_update_groups'] = [3];
        $oAdminObjectController->saveTermData(1);
        $_POST['uam_update_groups'] = [4];
        $oAdminObjectController->savePluggableObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::_removeObjectData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removePostData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removeUserData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removeTermData()
     * @covers \UserAccessManager\Controller\AdminObjectController::removePluggableObjectData()
     */
    public function testRemoveObjectData()
    {
        $oDatabase = $this->getDatabase();
        $oDatabase->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $oDatabase->expects($this->exactly(4))
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

        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $oDatabase,
            $this->getObjectHandlerWithPosts(),
            $this->getAccessHandler()
        );

        $oAdminObjectController->removePostData(1);
        $oAdminObjectController->removeUserData(2);
        $oAdminObjectController->removeTermData(3);
        $oAdminObjectController->removePluggableObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::addPostColumnsHeader()
     * @covers \UserAccessManager\Controller\AdminObjectController::addUserColumnsHeader()
     * @covers \UserAccessManager\Controller\AdminObjectController::addTermColumnsHeader()
     */
    public function testAddColumnsHeader()
    {
        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals(
            ['a' => 'a', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $oAdminObjectController->addPostColumnsHeader(['a' => 'a'])
        );
        self::assertEquals(
            ['b' => 'b', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_USER_GROUPS],
            $oAdminObjectController->addUserColumnsHeader(['b' => 'b'])
        );
        self::assertEquals(
            ['c' => 'c', AdminObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $oAdminObjectController->addTermColumnsHeader(['c' => 'c'])
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
        $oAdminObjectController = new AdminObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandlerWithPosts(),
            $this->getAccessHandler()
        );

        $oAdminObjectController->addPostColumn(AdminObjectController::COLUMN_NAME, 1);
        $oAdminObjectController->addUserColumn('return', AdminObjectController::COLUMN_NAME, 1);
        $oAdminObjectController->addTermColumn('content', AdminObjectController::COLUMN_NAME, 1);
        $oAdminObjectController->getPluggableColumn('objectType', 'objectId');
    }

    /**
     * @group  unit
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
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('src', new Directory([
            'UserAccessManager'  => new Directory([
                'View'  => new Directory([
                    'PostEditForm.php' => new File('<?php echo \'PostEditForm\';'),
                    'BulkEditForm.php' => new File('<?php echo \'BulkEditForm\';'),
                    'UserProfileEditForm.php' => new File('<?php echo \'UserProfileEditForm\';'),
                    'TermEditForm.php' => new File('<?php echo \'TermEditForm\';'),
                    'GroupSelectionForm.php' => new File('<?php echo \'GroupSelectionForm\';')
                ])
            ])
        ]));

        $oPhp = $this->getPhp();
        $oPhp->expects($this->exactly(11))
            ->method('includeFile')
            ->withConsecutive(
                ['vfs://src/UserAccessManager/View/PostEditForm.php'],
                ['vfs://src/UserAccessManager/View/PostEditForm.php'],
                ['vfs://src/UserAccessManager/View/BulkEditForm.php'],
                ['vfs://src/UserAccessManager/View/PostEditForm.php'],
                ['vfs://src/UserAccessManager/View/PostEditForm.php'],
                ['vfs://src/UserAccessManager/View/PostEditForm.php'],
                ['vfs://src/UserAccessManager/View/UserProfileEditForm.php'],
                ['vfs://src/UserAccessManager/View/UserProfileEditForm.php'],
                ['vfs://src/UserAccessManager/View/TermEditForm.php'],
                ['vfs://src/UserAccessManager/View/TermEditForm.php'],
                ['vfs://src/UserAccessManager/View/GroupSelectionForm.php']
            )
            ->will($this->returnCallback(function ($sFile) {
                echo '!'.$sFile.'!';
            }));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(11))
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(6))
            ->method('getUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4],
                ['category', 5],
                ['objectType', 'objectId']
            )
            ->will($this->returnValue([]));

        $oAccessHandler->expects($this->exactly(6))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4],
                ['category', 5],
                ['objectType', 'objectId']
            )
            ->will($this->returnValue([]));

        $oAdminObjectController = new AdminObjectController(
            $oPhp,
            $this->getWordpress(),
            $oConfig,
            $this->getDatabase(),
            $this->getObjectHandlerWithPosts(),
            $oAccessHandler
        );

        $oAdminObjectController->editPostContent(null);
        self::assertAttributeEquals(null, '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(null, '_sObjectId', $oAdminObjectController);
        $sExpectedOutput = '!vfs://src/UserAccessManager/View/PostEditForm.php!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oPost
         */
        $oPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oPost->ID = 1;
        $oPost->post_type = 'post';

        $oAdminObjectController->editPostContent($oPost);
        self::assertAttributeEquals('post', '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(1, '_sObjectId', $oAdminObjectController);
        $sExpectedOutput .= '!vfs://src/UserAccessManager/View/PostEditForm.php!';
        self::setValue($oAdminObjectController, '_sObjectType', null);
        self::setValue($oAdminObjectController, '_sObjectId', null);

        $oAdminObjectController->addBulkAction('invalid');
        $sExpectedOutput .= '';

        $oAdminObjectController->addBulkAction(AdminObjectController::COLUMN_NAME);
        $sExpectedOutput .= '!vfs://src/UserAccessManager/View/BulkEditForm.php!';

        $sExpected = 'meta</td></tr><tr><th class="label"><label>'.TXT_UAM_SET_UP_USER_GROUPS.
            '</label></th><td class="field">!vfs://src/UserAccessManager/View/PostEditForm.php!';

        $sReturn = $oAdminObjectController->showMediaFile('meta');
        self::assertAttributeEquals(null, '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(null, '_sObjectId', $oAdminObjectController);
        self::assertEquals($sExpected, $sReturn);
        $sExpectedOutput .= '';

        $sReturn = $oAdminObjectController->showMediaFile('meta', $oPost);
        self::assertAttributeEquals('post', '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(1, '_sObjectId', $oAdminObjectController);
        self::assertEquals($sExpected, $sReturn);
        $sExpectedOutput .= '';
        self::setValue($oAdminObjectController, '_sObjectType', null);
        self::setValue($oAdminObjectController, '_sObjectId', null);

        $_GET['attachment_id'] = 3;
        $sReturn = $oAdminObjectController->showMediaFile('meta', $oPost);
        self::assertAttributeEquals('attachment', '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(3, '_sObjectId', $oAdminObjectController);
        self::assertEquals($sExpected, $sReturn);
        $sExpectedOutput .= '';
        self::setValue($oAdminObjectController, '_sObjectType', null);
        self::setValue($oAdminObjectController, '_sObjectId', null);

        $oAdminObjectController->showUserProfile();
        self::assertAttributeEquals(null, '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(null, '_sObjectId', $oAdminObjectController);
        $sExpectedOutput .= '!vfs://src/UserAccessManager/View/UserProfileEditForm.php!';

        $_GET['user_id'] = 4;
        $oAdminObjectController->showUserProfile();
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(4, '_sObjectId', $oAdminObjectController);
        $sExpectedOutput .= '!vfs://src/UserAccessManager/View/UserProfileEditForm.php!';
        self::setValue($oAdminObjectController, '_sObjectType', null);
        self::setValue($oAdminObjectController, '_sObjectId', null);
        unset($_GET['user_id']);

        $oAdminObjectController->showTermEditForm(null);
        self::assertAttributeEquals(null, '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(null, '_sObjectId', $oAdminObjectController);
        $sExpectedOutput .= '!vfs://src/UserAccessManager/View/TermEditForm.php!';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass|\WP_Term $oTerm
         */
        $oTerm = $this->getMockBuilder('\WP_Term')->getMock();
        $oTerm->term_id = 5;
        $oTerm->taxonomy = 'category';
        $oAdminObjectController->showTermEditForm($oTerm);

        self::assertAttributeEquals('category', '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals(5, '_sObjectId', $oAdminObjectController);
        $sExpectedOutput .= '!vfs://src/UserAccessManager/View/TermEditForm.php!';
        self::setValue($oAdminObjectController, '_sObjectType', null);
        self::setValue($oAdminObjectController, '_sObjectId', null);

        $sReturn = $oAdminObjectController->showPluggableGroupSelectionForm('objectType', 'objectId');
        self::assertEquals('!vfs://src/UserAccessManager/View/GroupSelectionForm.php!', $sReturn);
        self::assertAttributeEquals('objectType', '_sObjectType', $oAdminObjectController);
        self::assertAttributeEquals('objectId', '_sObjectId', $oAdminObjectController);
        $sExpectedOutput .= '';

        self::expectOutputString($sExpectedOutput);
    }
}
