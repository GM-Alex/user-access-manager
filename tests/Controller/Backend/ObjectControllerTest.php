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

use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\PostMembershipHandler;
use UserAccessManager\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\TermMembershipHandler;
use UserAccessManager\ObjectMembership\UserMembershipHandler;
use UserAccessManager\UserGroup\DynamicUserGroup;

/**
 * Class ObjectControllerTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\Backend\ObjectController
 */
class ObjectControllerTest extends ObjectControllerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals($userGroups, $objectController->getFilteredUserGroups());
    }

    /**
     * @group  unit
     * @covers ::getDateUtil()
     */
    public function testGetDateUtil()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals($this->getDateUtil(), $objectController->getDateUtil());
    }

    /**
     * @group  unit
     * @covers ::isCurrentUserAdmin()
     */
    public function testIsCurrentUserAdmin()
    {
        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(2))
            ->method('userIsAdmin')
            ->with('objectId')
            ->will($this->onConsecutiveCalls(false, true));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $userHandler,
            $this->getAccessHandler(),
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $objectHandler,
            $this->getUserHandler(),
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
        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $userHandler,
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertFalse($objectController->checkUserAccess());
        self::assertTrue($objectController->checkUserAccess());
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $objectHandler,
            $this->getUserHandler(),
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $objectHandler,
            $this->getUserHandler(),
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
     * @group  unit
     * @covers ::saveObjectData()
     * @covers ::getAddRemoveGroups()
     * @covers ::setUserGroups()
     * @covers ::setDynamicGroups()
     * @covers ::setDefaultGroups()
     * @covers ::getDateParameter()
     */
    public function testSaveObjectData()
    {
        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(3))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(false, true, true));

        $dateUtil = $this->getDateUtil();
        $dateUtil->expects($this->exactly(2))
            ->method('getDateFromTime')
            ->withConsecutive(
                [1],
                [2]
            )
            ->will($this->onConsecutiveCalls(
                '1970-01-01 00:01:41',
                '1970-01-01 00:01:42'
            ));

        $objectHandler = $this->getExtendedObjectHandler();

        $userHandler = $this->getUserHandler();
        $userHandler->expects($this->exactly(12))
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
                false,
                false,
                true,
                true
            ));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(5))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                ['objectType', 1],
                ['objectType', 1],
                ['objectType', 1],
                ['objectType', 'objectId'],
                ['objectType', 1]
            )
            ->will($this->onConsecutiveCalls(
                $this->getUserGroupArray([1, 2, 3]),
                $this->getUserGroupArray([1, 2, 4]),
                $this->getUserGroupArray([2, 3, 4]),
                $this->getUserGroupArray([2, 3]),
                $this->getUserGroupArray([1, 2, 3])
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

        $accessHandler->expects($this->exactly(5))
            ->method('getFilteredUserGroups')
            ->will($this->onConsecutiveCalls(
                $this->getUserGroupArray([1, 3], [1, 2, 3], [['objectType', 1, '1', 'toDate']], [100, 101]),
                $this->getUserGroupArray([2, 4], [1, 2, 4], [['objectType', 1, null, null]]),
                $this->getUserGroupArray([1, 2], [2, 3, 4], [['objectType', 1, null, '234']]),
                $this->getUserGroupArray([4], [2, 3], [['objectType', 'objectId', null, null]]),
                $this->getUserGroupArray([], [1, 2], [['objectType', 1, null, null]])
            ));

        $accessHandler->expects($this->exactly(5))
            ->method('unsetUserGroupsForObject');

        $userGroupFactory = $this->getUserGroupFactory();

        $userGroupFactory->expects($this->exactly(2))
            ->method('createDynamicUserGroup')
            ->withConsecutive(
                [DynamicUserGroup::USER_TYPE, '1'],
                [DynamicUserGroup::ROLE_TYPE, 'admin']
            )->will($this->onConsecutiveCalls(
                $this->getDynamicUserGroupWithAdd(
                    DynamicUserGroup::USER_TYPE,
                    '1',
                    ['objectType', 1, 'fromDate', 'toDate']
                ),
                $this->getDynamicUserGroupWithAdd(DynamicUserGroup::ROLE_TYPE, 'admin', ['objectType', 1, null, null])
            ));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $mainConfig,
            $this->getDatabase(),
            $dateUtil,
            $this->getCache(),
            $objectHandler,
            $userHandler,
            $accessHandler,
            $userGroupFactory
        );

        $_POST[ObjectController::UPDATE_GROUPS_FORM_NAME] = 1;
        $objectController->saveObjectData('objectType', 1);

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
        $objectController->saveObjectData('objectType', 1);

        unset($_POST[ObjectController::DEFAULT_DYNAMIC_GROUPS_FORM_NAME]);
        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            2 => ['id' => 2],
            4 => ['id' => 4]
        ];
        $objectController->saveObjectData('objectType', 1);

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            1 => ['id' => 1, 'formDate' => '', 'toDate' => 234],
            2 => ['id' => 2, 'formDate' => '', 'toDate' => 234]
        ];
        $objectController->saveObjectData('objectType', 1);

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            4 => ['id' => 4]
        ];
        $objectController->saveObjectData('objectType', 'objectId');
        $_POST = [];
        $objectController->saveObjectData('objectType', 'objectId');

        $_POST = [
            ObjectController::UPDATE_GROUPS_FORM_NAME => 1,
            'uam_bulk_type' => ObjectController::BULK_REMOVE,
            ObjectController::DEFAULT_GROUPS_FORM_NAME => [
                1 => ['id' => 1],
                2 => ['id' => 2]
            ]
        ];
        $objectController->saveObjectData('objectType', 1);
    }

    /**
     * @group  unit
     * @covers ::removeObjectData()
     */
    public function testRemoveObjectData()
    {
        $database = $this->getDatabase();
        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('delete')
            ->with(
                'userGroupToObjectTable',
                ['object_id' => 'objectId', 'object_type' => 'objectType'],
                ['%d', '%s']
            );

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $database,
            $this->getDateUtil(),
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        $objectController->removeObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers ::getGroupColumn()
     */
    public function testAddColumn()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        $objectController->getGroupColumn('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers ::getGroupColumn()
     * @covers ::showGroupSelectionForm()
     * @covers ::getGroupsFormName()
     */
    public function testEditForm()
    {
        /**
         * @var ObjectController $objectController
         */
        $objectController = $this->getTestEditFormPrototype(
            ObjectController::class,
            [
                'vfs://src/View/ObjectColumn.php',
                'vfs://src/View/GroupSelectionForm.php',
                'vfs://src/View/GroupSelectionForm.php'
            ],
            [
                ['objectType', 'objectId'],
                ['objectType', 'objectId']
            ]
        );

        $expected = '!UserAccessManager\Controller\Backend\ObjectController|'
            .'vfs://src/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $objectController->getGroupColumn('objectType', 'objectId')
        );
        self::assertAttributeEquals('objectType', 'objectType', $objectController);
        self::assertAttributeEquals('objectId', 'objectId', $objectController);

        self::setValue($objectController, 'objectType', null);
        self::setValue($objectController, 'objectId', null);

        $return = $objectController->showGroupSelectionForm('objectType', 'objectId', 'otherForm');
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
        $expectedOutput = '';

        $userGroups = [
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        $return = $objectController->showGroupSelectionForm('objectType', 'objectId', null, $userGroups);
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getCache(),
            $objectHandler,
            $this->getUserHandler(),
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
}
