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

namespace UserAccessManager\Tests\Unit\Controller\Backend;

use Exception;
use ReflectionException;
use UserAccessManager\Controller\Backend\BackendController;
use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Controller\Backend\ObjectInformation;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\PostMembershipHandler;
use UserAccessManager\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\TermMembershipHandler;
use UserAccessManager\ObjectMembership\UserMembershipHandler;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroupAssignmentException;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_Roles;

/**
 * Class ObjectControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
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
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertInstanceOf(ObjectController::class, $objectController);
    }

    /**
     * @group  unit
     * @covers ::setObjectInformation()
     * @return ObjectController
     * @throws ReflectionException
     */
    public function testSetObjectInformation(): ObjectController
    {
        $fullGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $filteredGroups = [
            1 => $this->getUserGroup(1)
        ];

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroupHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('objectType', 'objectId', true)
            ->will($this->returnValue($fullGroups));

        $userGroupHandler->expects($this->once())
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
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $userGroupHandler,
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $userGroups = [
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        self::callMethod($objectController, 'setObjectInformation', ['objectType', 'objectId', $userGroups]);

        self::assertEquals('objectType', $objectController->getObjectInformation()->getObjectType());
        self::assertEquals('objectId', $objectController->getObjectInformation()->getObjectId());
        self::assertEquals($userGroups, $objectController->getObjectInformation()->getObjectUserGroups());
        self::assertEquals(0, $objectController->getObjectInformation()->getUserGroupDiff());

        self::callMethod($objectController, 'setObjectInformation', ['objectType', 'objectId']);

        self::assertEquals('objectType', $objectController->getObjectInformation()->getObjectType());
        self::assertEquals('objectId', $objectController->getObjectInformation()->getObjectId());
        self::assertEquals($filteredGroups, $objectController->getObjectInformation()->getObjectUserGroups());
        self::assertEquals(1, $objectController->getObjectInformation()->getUserGroupDiff());

        return $objectController;
    }

    /**
     * @group   unit
     * @covers  ::getObjectInformation()
     * @depends testSetObjectInformation
     * @param ObjectController $objectController
     */
    public function testGetObjectInformation(ObjectController $objectController)
    {
        self::assertInstanceOf(ObjectInformation::class, $objectController->getObjectInformation());
        self::assertEquals('objectType', $objectController->getObjectInformation()->getObjectType());
        self::assertEquals('objectId', $objectController->getObjectInformation()->getObjectId());
        self::assertEquals(1, $objectController->getObjectInformation()->getUserGroupDiff());
        self::assertEquals(
            [1 => $this->getUserGroup(1)],
            $objectController->getObjectInformation()->getObjectUserGroups()
        );
    }

    /**
     * @group  unit
     * @covers ::getFilteredUserGroups()
     * @throws UserGroupTypeException
     */
    public function testGetFilteredUserGroups()
    {
        $userGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->once())
            ->method('getFilteredUserGroups')
            ->will($this->returnValue($userGroups));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $userGroupHandler,
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertEquals($userGroups, $objectController->getFilteredUserGroups());
    }

    /**
     * @group  unit
     * @covers ::sortUserGroups()
     */
    public function testSortUserGroups()
    {
        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $notLoggedInUserGroupId = DynamicUserGroup::USER_TYPE . '|' . DynamicUserGroup::NOT_LOGGED_IN_USER_ID;
        $userGroups = [
            1 => $this->getUserGroup(1, true, false, [''], 'none', 'none', [], [], 'A'),
            $notLoggedInUserGroupId => $this->getUserGroup(
                $notLoggedInUserGroupId,
                true,
                false,
                [''],
                'none',
                'none',
                [],
                [],
                'NotLoggedIn'
            ),
            2 => $this->getUserGroup(2, true, false, [''], 'none', 'none', [], [], 'Z'),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'none', [], [], 'B')
        ];
        $objectController->sortUserGroups($userGroups);

        self::assertSame(
            [
                1 => $userGroups[1],
                3 => $userGroups[3],
                2 => $userGroups[2],
                $notLoggedInUserGroupId => $userGroups[$notLoggedInUserGroupId]
            ],
            $userGroups
        );
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
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertEquals($this->getDateUtil(), $objectController->getDateUtil());
    }

    /**
     * @group  unit
     * @covers ::isCurrentUserAdmin()
     * @throws ReflectionException
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
            $this->getObjectHandler(),
            $userHandler,
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertFalse($objectController->isCurrentUserAdmin());

        $objectInformation = $this->createMock(ObjectInformation::class);
        $objectInformation->expects($this->exactly(3))
            ->method('getObjectType')
            ->will($this->returnValue(ObjectHandler::GENERAL_USER_OBJECT_TYPE));
        $objectInformation->expects($this->exactly(5))
            ->method('getObjectId')
            ->will($this->onConsecutiveCalls(null, 'objectId', 'objectId', 'objectId', 'objectId'));

        self::setValue($objectController, 'objectInformation', $objectInformation);
        self::assertFalse($objectController->isCurrentUserAdmin());

        self::assertFalse($objectController->isCurrentUserAdmin());
        self::assertTrue($objectController->isCurrentUserAdmin());
    }

    /**
     * @group  unit
     * @covers ::getRoleNames()
     */
    public function testGetRoleNames()
    {
        $roles = $this->getMockBuilder(WP_Roles::class)->allowMockingUnknownTypes()->getMock();
        $roles->role_names = ['roleNames'];

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
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertEquals(['roleNames'], $objectController->getRoleNames());
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
            $this->getObjectHandler(),
            $userHandler,
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertFalse($objectController->checkUserAccess());
        self::assertTrue($objectController->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers ::getRecursiveMembership()
     * @throws Exception
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
            $objectHandler,
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $objectInformation = $this->createMock(ObjectInformation::class);
        $objectInformation->expects($this->once())
            ->method('getObjectType')
            ->will($this->returnValue('objectType'));
        $objectInformation->expects($this->once())
            ->method('getObjectId')
            ->will($this->returnValue('objectId'));

        self::setValue($objectController, 'objectInformation', $objectInformation);

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
     * @covers ::dieOnNoAccess()
     * @throws UserGroupTypeException
     */
    public function testCheckRightsToEditContent()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(5))
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(8))
            ->method('checkObjectAccess')
            ->withConsecutive(
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, -1],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 2],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 6]
            )
            ->will($this->onConsecutiveCalls(false, false, false, true, false, true, true, false));

        $objectController = new ObjectController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $accessHandler,
            $this->getObjectInformationFactory()
        );

        $objectController->checkRightsToEditContent();

        $_GET['post'] = -1;
        $objectController->checkRightsToEditContent();

        $_GET['post'] = 1;
        $objectController->checkRightsToEditContent();

        $_GET['post'] = [1, 2];
        $objectController->checkRightsToEditContent();

        unset($_GET['post']);
        $_GET['attachment_id'] = 3;
        $objectController->checkRightsToEditContent();

        unset($_GET['attachment_id']);
        $_GET['tag_ID'] = 4;
        $objectController->checkRightsToEditContent();

        $_GET['tag_ID'] = 5;
        $objectController->checkRightsToEditContent();

        $_GET['tag_ID'] = 6;
        $objectController->checkRightsToEditContent();
    }

    /**
     * @group  unit
     * @covers ::saveObjectData()
     * @covers ::getAddRemoveGroups()
     * @throws UserGroupTypeException
     */
    public function testSaveObjectData()
    {
        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(3))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(false, true, true));

        $objectHandler = $this->getExtendedObjectHandler();

        $userHandler = $this->getUserHandler();
        $userHandler->expects($this->exactly(7))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(
                false,
                false,
                true,
                true,
                true,
                false,
                true
            ));

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroupHandler->expects($this->exactly(5))
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

        $throwException = false;
        $userGroupAssignmentHandler = $this->getUserGroupAssignmentHandler();
        $userGroupAssignmentHandler->expects($this->exactly(5))
            ->method('assignObjectToUserGroups')
            ->withConsecutive(
                ['objectType', 1],
                ['objectType', 1],
                ['objectType', 1],
                ['objectType', 'objectId', [4 => ['id' => 4]], [2 => 0, 3 => 1]],
                ['objectType', 1, [], [1 => ['id' => 1], 2 => ['id' => 2]], []]
            )
            ->will($this->returnCallback(function () use (&$throwException) {
                if ($throwException === true) {
                    throw new UserGroupAssignmentException('User group assignment exception');
                }
            }));

        $objectController = new ObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $mainConfig,
            $this->getDatabase(),
            $this->getDateUtil(),
            $objectHandler,
            $userHandler,
            $userGroupHandler,
            $userGroupAssignmentHandler,
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $_POST[ObjectController::UPDATE_GROUPS_FORM_NAME] = 1;
        $objectController->saveObjectData('objectType', 1);

        $_POST[ObjectController::DEFAULT_DYNAMIC_GROUPS_FORM_NAME] = [
            DynamicUserGroup::USER_TYPE . '|1' => [
                'id' => DynamicUserGroup::USER_TYPE . '|1',
                'fromDate' => ['date' => 'from', 'time' => 'Date'],
                'toDate' => ['date' => 'to', 'time' => 'Date']
            ],
            DynamicUserGroup::ROLE_TYPE . '|admin' => ['id' => DynamicUserGroup::ROLE_TYPE . '|admin'],
            DynamicUserGroup::ROLE_TYPE . '|some' => ['id' => DynamicUserGroup::ROLE_TYPE . '|some'],
            'A|B' => ['id' => 'B|A'],
        ];
        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            1 => ['id' => 1, 'fromDate' => ['date' => 1, 'time' => 2], 'toDate' => ['date' => 'to', 'time' => 'Date']],
            3 => ['id' => 3, 'fromDate' => ['date' => 1, 'time' => 2], 'toDate' => ['date' => 'to', 'time' => 'Date']],
            100 => [],
            101 => ['id' => 100]
        ];

        if (defined('_SESSION')) {
            unset($_SESSION[BackendController::UAM_ERRORS]);
        }

        $throwException = true;
        $objectController->saveObjectData('objectType', 1);

        self::assertEquals(
            ['The following error occurred: User group assignment exception|user-access-manager'],
            $_SESSION[BackendController::UAM_ERRORS]
        );
        $throwException = false;

        unset($_POST[ObjectController::DEFAULT_DYNAMIC_GROUPS_FORM_NAME]);
        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            2 => ['id' => 2],
            4 => ['id' => 4]
        ];
        $objectController->saveObjectData('objectType', 1);

        $_POST[ObjectController::DEFAULT_GROUPS_FORM_NAME] = [
            1 => ['id' => 1, 'formDate' => ['date' => '', 'time' => ''], 'toDate' => ['date' => 23, 'time' => 4]],
            2 => ['id' => 2, 'formDate' => ['date' => '', 'time' => ''], 'toDate' => ['date' => 23, 'time' => 4]]
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
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $objectController->removeObjectData('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers ::getGroupColumn()
     * @throws UserGroupTypeException
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
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $objectController->getGroupColumn('objectType', 'objectId');
    }

    /**
     * @group  unit
     * @covers ::getGroupColumn()
     * @covers ::showGroupSelectionForm()
     * @covers ::getGroupsFormName()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testEditForm()
    {
        /**
         * @var ObjectController $objectController
         */
        $objectController = $this->getTestEditFormPrototype(
            ObjectController::class,
            [
                'vfs://root/src/View/ObjectColumn.php',
                'vfs://root/src/View/GroupSelectionForm.php',
                'vfs://root/src/View/GroupSelectionForm.php'
            ],
            [
                ['objectType', 'objectId'],
                ['objectType', 'objectId']
            ]
        );

        $expected = '!UserAccessManager\Controller\Backend\ObjectController|'
            . 'vfs://root/src/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $objectController->getGroupColumn('objectType', 'objectId')
        );
        self::assertEquals('objectType', $objectController->getObjectInformation()->getObjectType());
        self::assertEquals('objectId', $objectController->getObjectInformation()->getObjectId());
        self::setValue($objectController, 'objectInformation', $this->getObjectInformation());

        $return = $objectController->showGroupSelectionForm('objectType', 'objectId', 'otherForm');
        self::assertEquals(
            '!UserAccessManager\Controller\Backend\ObjectController|'
            . 'vfs://root/src/View/GroupSelectionForm.php|otherForm!',
            $return
        );
        self::assertEquals('objectType', $objectController->getObjectInformation()->getObjectType());
        self::assertEquals('objectId', $objectController->getObjectInformation()->getObjectId());
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
            . 'vfs://root/src/View/GroupSelectionForm.php|uam_user_groups!',
            $return
        );
        self::assertEquals('objectType', $objectController->getObjectInformation()->getObjectType());
        self::assertEquals('objectId', $objectController->getObjectInformation()->getObjectId());
        self::assertEquals($userGroups, $objectController->getObjectInformation()->getObjectUserGroups());
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
     * @throws Exception
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
            $objectHandler,
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $objectInformation = $this->createMock(ObjectInformation::class);

        $objectInformation->expects($this->exactly(5))
            ->method('getObjectType')
            ->will($this->onConsecutiveCalls(
                null,
                'objectTypeValue',
                'objectTypeValue',
                'otherObjectTypeValue',
                'otherObjectTypeValue'
            ));

        $objectInformation->expects($this->exactly(4))
            ->method('getObjectId')
            ->will($this->onConsecutiveCalls(null, 1, 1, 1));

        self::setValue($objectController, 'objectInformation', $objectInformation);

        self::assertFalse($objectController->isNewObject());
        self::assertTrue($objectController->isNewObject());
        self::assertFalse($objectController->isNewObject());

        $_GET['action'] = 'edit';
        self::assertFalse($objectController->isNewObject());

        $_GET['action'] = 'new';
        self::assertTrue($objectController->isNewObject());
    }
}
