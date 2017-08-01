<?php
/**
 * AdminUserGroupControllerTest.php
 *
 * The AdminUserGroupControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller;

use UserAccessManager\Controller\AdminUserGroupController;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class AdminUserGroupControllerTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\AdminUserGroupController
 */
class AdminUserGroupControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertInstanceOf(AdminUserGroupController::class, $adminUserGroupController);
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

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals(
            [
                AdminUserGroupController::GROUP_USER_GROUPS => ['user_groups'],
                AdminUserGroupController::GROUP_DEFAULT_USER_GROUPS => [
                    'attachment',
                    'post',
                    'page',
                    'category',
                    ObjectHandler::GENERAL_USER_OBJECT_TYPE
                ]
            ],
            $adminUserGroupController->getTabGroups()
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

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $formHelper
        );

        self::assertEquals('valueOne', $adminUserGroupController->getGroupText('keyOne'));
        self::assertEquals('valueTwo', $adminUserGroupController->getGroupText('keyTwo'));
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

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals('someKey', $adminUserGroupController->getGroupSectionText('someKey'));
        self::assertEquals(
            'attachmentName',
            $adminUserGroupController->getGroupSectionText(ObjectHandler::ATTACHMENT_OBJECT_TYPE)
        );
        self::assertEquals(
            'postName ('.TXT_UAM_POST_TYPE.')',
            $adminUserGroupController->getGroupSectionText(ObjectHandler::POST_OBJECT_TYPE)
        );
        self::assertEquals(
            'categoryName ('.TXT_UAM_TAXONOMY_TYPE.')',
            $adminUserGroupController->getGroupSectionText('category')
        );
        self::assertEquals(
            TXT_UAM_USER,
            $adminUserGroupController->getGroupSectionText(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
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

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $userGroupFactory,
            $this->getFormHelper()
        );

        $_GET['userGroupId'] = 1;
        self::assertEquals($userGroup, $adminUserGroupController->getUserGroup());
        self::assertEquals($userGroup, $adminUserGroupController->getUserGroup());
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

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue($userGroups));

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $accessHandler,
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals($userGroups, $adminUserGroupController->getUserGroups());
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

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        self::assertEquals('roleNames', $adminUserGroupController->getRoleNames());
    }

    /**
     * @group  unit
     * @covers ::insertUpdateUserGroupAction()
     */
    public function testInsertUpdateUserGroupAction()
    {
        $_GET[AdminUserGroupController::INSERT_UPDATE_GROUP_NONCE.'Nonce'] = 'insertUpdateNonce';

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

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(2))
            ->method('addUserGroup')
            ->with($userGroup);

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $accessHandler,
            $userGroupFactory,
            $this->getFormHelper()
        );

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'updateMessage', $adminUserGroupController);

        $_POST['userGroupName'] = 'userGroupNameValue';
        $_POST['userGroupDescription'] = 'userGroupDescriptionValue';
        $_POST['readAccess'] = 'readAccessValue';
        $_POST['writeAccess'] = 'writeAccessValue';
        $_POST['ipRange'] = 'ipRangeValue';
        $_POST['roles'] = ['roleOne', 'roleTwo'];

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_NAME_ERROR, 'updateMessage', $adminUserGroupController);

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_GROUP_ADDED, 'updateMessage', $adminUserGroupController);

        $_POST['userGroupId'] = 1;

        $adminUserGroupController->insertUpdateUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_USER_GROUP_EDIT_SUCCESS, 'updateMessage', $adminUserGroupController);
    }

    /**
     * @group  unit
     * @covers ::deleteUserGroupAction()
     */
    public function testDeleteUserGroupAction()
    {
        $_GET[AdminUserGroupController::DELETE_GROUP_NONCE.'Nonce'] = AdminUserGroupController::DELETE_GROUP_NONCE;
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('verifyNonce')
            ->with(AdminUserGroupController::DELETE_GROUP_NONCE)
            ->will($this->returnValue(true));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(2))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [2]);

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $accessHandler,
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        $_POST['delete'] = [1, 2];
        $adminUserGroupController->deleteUserGroupAction();
        self::assertAttributeEquals(TXT_UAM_DELETE_GROUP, 'updateMessage', $adminUserGroupController);
    }

    /**
     * @group  unit
     * @covers ::setDefaultUserGroupsAction()
     */
    public function testSetDefaultUserGroupsAction()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('verifyNonce')
            ->with(AdminUserGroupController::SET_DEFAULT_USER_GROUPS_NONCE)
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

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->once())
            ->method('getUserGroups')
            ->will($this->returnValue([
                $firstUserGroup,
                $secondUserGroup,
                $thirdUserGroup
            ]));

        $adminUserGroupController = new AdminUserGroupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $accessHandler,
            $this->getUserGroupFactory(),
            $this->getFormHelper()
        );

        $_POST = [
            AdminUserGroupController::SET_DEFAULT_USER_GROUPS_NONCE.'Nonce' =>
                AdminUserGroupController::SET_DEFAULT_USER_GROUPS_NONCE,
            'tab_group_section' => 'objectType',
            AdminUserGroupController::DEFAULT_USER_GROUPS_FORM_FIELD => [
                1 => ['id' => 1, 'fromTime' => 'fromTimeValue'],
                2 => ['id' => 2, 'toTime' => 'toTimeValue'],
                3 => ['id' => 4]
            ]
        ];
        $adminUserGroupController->setDefaultUserGroupsAction();

        self::assertAttributeEquals(
            TXT_UAM_SET_DEFAULT_USER_GROUP_SUCCESS,
            'updateMessage',
            $adminUserGroupController
        );
    }
}
