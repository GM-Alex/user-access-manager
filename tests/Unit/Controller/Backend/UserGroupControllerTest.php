<?php
/**
 * UserGroupControllerTest.php
 *
 * The UserGroupControllerTest unit test class file.
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

use UserAccessManager\Controller\Backend\UserGroupController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class UserGroupControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\UserGroupController
 */
class UserGroupControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertInstanceOf(UserGroupController::class, $userGroupController);
    }

    /**
     * @group  unit
     * @covers ::getTabGroups()
     */
    public function testGetTabGroups()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => null,
                ObjectHandler::POST_OBJECT_TYPE => null,
                ObjectHandler::PAGE_OBJECT_TYPE => null
            ]));

        $wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => null
            ]));

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals(
            [
                UserGroupController::GROUP_USER_GROUPS => ['user_groups'],
                UserGroupController::GROUP_DEFAULT_USER_GROUPS => [
                    'attachment',
                    'post',
                    'page',
                    'category',
                    ObjectHandler::GENERAL_USER_OBJECT_TYPE
                ]
            ],
            $userGroupController->getTabGroups()
        );
    }

    /**
     * @group  unit
     * @covers ::getGroupText()
     */
    public function testGetGroupText()
    {
        $formHelper = $this->getFormHelper();

        $formHelper->expects($this->exactly(2))
            ->method('getText')
            ->withConsecutive(['keyOne'], ['keyTwo'])
            ->will($this->onConsecutiveCalls('valueOne', 'valueTwo'));

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory(),
            $formHelper
        );

        self::assertEquals('valueOne', $userGroupController->getGroupText('keyOne'));
        self::assertEquals('valueTwo', $userGroupController->getGroupText('keyTwo'));
    }

    /**
     * @group  unit
     * @covers ::getGroupSectionText()
     */
    public function testGetGroupSectionText()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(5))
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => $this->createTypeObject('attachmentName'),
                ObjectHandler::POST_OBJECT_TYPE => $this->createTypeObject('postName', '\WP_Post_Type'),
                ObjectHandler::PAGE_OBJECT_TYPE => $this->createTypeObject('pageName')
            ]));

        $wordpress->expects($this->exactly(5))
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => $this->createTypeObject('categoryName', '\WP_Taxonomy'),
                ObjectHandler::POST_FORMAT_TYPE => $this->createTypeObject('postFormat'),
            ]));

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals('someKey', $userGroupController->getGroupSectionText('someKey'));
        self::assertEquals(
            'attachmentName',
            $userGroupController->getGroupSectionText(ObjectHandler::ATTACHMENT_OBJECT_TYPE)
        );
        self::assertEquals(
            'postName ('.TXT_UAM_POST_TYPE.')',
            $userGroupController->getGroupSectionText(ObjectHandler::POST_OBJECT_TYPE)
        );
        self::assertEquals(
            'categoryName ('.TXT_UAM_TAXONOMY_TYPE.')',
            $userGroupController->getGroupSectionText('category')
        );
        self::assertEquals(
            TXT_UAM_USER,
            $userGroupController->getGroupSectionText(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );
    }

    /**
     * @group  unit
     * @covers ::getUserGroup()
     */
    public function testGetUserGroup()
    {
        $userGroup = $this->getUserGroup(1);

        $userGroupFactory = $this->getUserGroupFactory();
        $userGroupFactory->expects($this->once())
            ->method('createUserGroup')
            ->with(1)
            ->will($this->returnValue($userGroup));

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getUserGroupHandler(),
            $userGroupFactory,
            $this->getFormHelper()
        );

        $_GET['userGroupId'] = 1;
        self::assertEquals($userGroup, $userGroupController->getUserGroup());
        self::assertEquals($userGroup, $userGroupController->getUserGroup());
    }

    /**
     * @group  unit
     * @covers ::getUserGroups()
     */
    public function testGetUserGroups()
    {
        $userGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2)
        ];

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($userGroups));

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $userGroupHandler,
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals($userGroups, $userGroupController->getUserGroups());
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

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals('roleNames', $userGroupController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers ::insertUpdateUserGroupAction()
     */
    public function testInsertUpdateUserGroupAction()
    {
        $_GET[UserGroupController::INSERT_UPDATE_GROUP_NONCE.'Nonce'] = 'insertUpdateNonce';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('verifyNonce')
            ->with('insertUpdateNonce')
            ->will($this->returnValue(true));

        $userGroup = $this->getUserGroup(1);

        $userGroup->expects($this->exactly(3))
            ->method('setName')
            ->with('userGroupNameValue');

        $userGroup->expects($this->exactly(3))
            ->method('setDescription')
            ->with('userGroupDescriptionValue');

        $userGroup->expects($this->exactly(3))
            ->method('setReadAccess')
            ->with('readAccessValue');

        $userGroup->expects($this->exactly(3))
            ->method('setWriteAccess')
            ->with('writeAccessValue');

        $userGroup->expects($this->exactly(3))
            ->method('setIpRange')
            ->with('ipRangeValue');

        $userGroup->expects($this->exactly(2))
            ->method('removeObject')
            ->with(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        $userGroup->expects($this->exactly(4))
            ->method('addObject')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo']
            );

        $userGroup->expects($this->exactly(3))
            ->method('save')
            ->will($this->onConsecutiveCalls(false, true, true));

        $userGroupFactory = $this->getUserGroupFactory();
        $userGroupFactory->expects($this->exactly(4))
            ->method('createUserGroup')
            ->withConsecutive([null], [null], [null], [1])
            ->will($this->returnValue($userGroup));

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->exactly(2))
            ->method('addUserGroup')
            ->with($userGroup);

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $userGroupHandler,
            $userGroupFactory,
            $this->getFormHelper()
        );

        $userGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'updateMessage', $userGroupController);

        $_POST['userGroupName'] = 'userGroupNameValue';
        $_POST['userGroupDescription'] = 'userGroupDescriptionValue';
        $_POST['readAccess'] = 'readAccessValue';
        $_POST['writeAccess'] = 'writeAccessValue';
        $_POST['ipRange'] = 'ipRangeValue';
        $_POST['roles'] = ['roleOne', 'roleTwo'];

        $userGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'updateMessage', $userGroupController);

        $userGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_ADDED, 'updateMessage', $userGroupController);

        $_POST['userGroupId'] = 1;

        $userGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_USER_GROUP_EDIT_SUCCESS, 'updateMessage', $userGroupController);
    }

    /**
     * @group  unit
     * @covers ::deleteUserGroupAction()
     */
    public function testDeleteUserGroupAction()
    {
        $_GET[UserGroupController::DELETE_GROUP_NONCE.'Nonce'] = UserGroupController::DELETE_GROUP_NONCE;
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('verifyNonce')
            ->with(UserGroupController::DELETE_GROUP_NONCE)
            ->will($this->returnValue(true));

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->exactly(2))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [2]);

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $userGroupHandler,
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        $_POST['delete'] = [1, 2];
        $userGroupController->deleteUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_DELETE_GROUP, 'updateMessage', $userGroupController);
    }

    /**
     * @group  unit
     * @covers ::setDefaultUserGroupsAction()
     * @covers ::isDefaultTypeAdd()
     */
    public function testSetDefaultUserGroupsAction()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('verifyNonce')
            ->with(UserGroupController::SET_DEFAULT_USER_GROUPS_NONCE)
            ->will($this->returnValue(true));

        $wordpress->expects($this->exactly(2))
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => $this->createTypeObject('attachmentName'),
                ObjectHandler::POST_OBJECT_TYPE => $this->createTypeObject('postName', '\WP_Post_Type'),
                ObjectHandler::PAGE_OBJECT_TYPE => $this->createTypeObject('pageName')
            ]));

        $wordpress->expects($this->exactly(2))
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => $this->createTypeObject('categoryName', '\WP_Taxonomy'),
                ObjectHandler::POST_FORMAT_TYPE => $this->createTypeObject('postFormat'),
            ]));


        $firstUserGroup = $this->getUserGroup(1);

        $firstUserGroup->expects($this->once())
            ->method('addDefaultType')
            ->with('objectType', 'fromTimeValue', null);

        $firstUserGroup->expects($this->once())
            ->method('removeDefaultType')
            ->with('objectType');

        $secondUserGroup = $this->getUserGroup(2);

        $secondUserGroup->expects($this->once())
            ->method('addDefaultType')
            ->with('objectType', null, 'toTimeValue');

        $secondUserGroup->expects($this->once())
            ->method('removeDefaultType')
            ->with('objectType');

        $thirdUserGroup = $this->getUserGroup(3);

        $thirdUserGroup->expects($this->never())
            ->method('addDefaultType');

        $thirdUserGroup->expects($this->once())
            ->method('removeDefaultType')
            ->with('objectType');

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroupHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue([
                $firstUserGroup,
                $secondUserGroup,
                $thirdUserGroup
            ]));

        $userGroupController = new UserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $userGroupHandler,
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        $_POST = [
            UserGroupController::SET_DEFAULT_USER_GROUPS_NONCE.'Nonce' =>
                UserGroupController::SET_DEFAULT_USER_GROUPS_NONCE,
            'tab_group_section' => 'objectType',
            UserGroupController::DEFAULT_USER_GROUPS_FORM_FIELD => [
                1 => ['id' => 1, 'fromTime' => 'fromTimeValue'],
                2 => ['id' => 2, 'toTime' => 'toTimeValue'],
                3 => ['id' => 4]
            ]
        ];
        $userGroupController->setDefaultUserGroupsAction();

        self::assertAttributeEquals(
            TXT_UAM_SET_DEFAULT_USER_GROUP_SUCCESS,
            'updateMessage',
            $userGroupController
        );
    }
}
