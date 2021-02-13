<?php
/**
 * PostObjectControllerTest.php
 *
 * The PostObjectControllerTest class file.
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

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;
use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Controller\Backend\PostObjectController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupTypeException;

/**
 * Class PostObjectControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\PostObjectController
 */
class PostObjectControllerTest extends ObjectControllerTestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $postObjectController = new PostObjectController(
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

        self::assertInstanceOf(PostObjectController::class, $postObjectController);
    }

    /**
     * @group  unit
     * @covers ::addPostColumnsHeader()
     */
    public function testAddPostColumnsHeader()
    {
        $postObjectController = new PostObjectController(
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

        self::assertEquals(
            ['a' => 'a', ObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $postObjectController->addPostColumnsHeader(['a' => 'a'])
        );
    }

    /**
     * @group  unit
     * @covers ::addPostColumn()
     * @throws UserGroupTypeException
     */
    public function testAddPostColumn()
    {
        $postObjectController = new PostObjectController(
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

        $postObjectController->addPostColumn(ObjectController::COLUMN_NAME, 1);
    }

    /**
     * @group  unit
     * @covers ::addPostColumn()
     * @covers ::editPostContent()
     * @covers ::addBulkAction()
     * @covers ::showMediaFile()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testEditForm()
    {
        /**
         * @var PostObjectController $postObjectController
         */
        $postObjectController = $this->getTestEditFormPrototype(
            PostObjectController::class,
            [
                'vfs://root/src/View/ObjectColumn.php',
                'vfs://root/src/View/PostEditForm.php',
                'vfs://root/src/View/PostEditForm.php',
                'vfs://root/src/View/BulkEditForm.php',
                'vfs://root/src/View/MediaAjaxEditForm.php',
                'vfs://root/src/View/MediaAjaxEditForm.php',
                'vfs://root/src/View/MediaAjaxEditForm.php'
            ],
            [
                ['post', 1],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
            ]
        );

        $postObjectController->addPostColumn('invalid', 1);
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addPostColumn('invalid', 1);
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addPostColumn(ObjectController::COLUMN_NAME, 1);
        self::assertEquals('post', $postObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(1, $postObjectController->getObjectInformation()->getObjectId());
        $expectedOutput = '!UserAccessManager\Controller\Backend\PostObjectController|'
            . 'vfs://root/src/View/ObjectColumn.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->editPostContent(null);
        self::assertEquals(null, $postObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(null, $postObjectController->getObjectInformation()->getObjectId());
        $expectedOutput .= '!UserAccessManager\Controller\Backend\PostObjectController|'
            . 'vfs://root/src/View/PostEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        /**
         * @var MockObject|stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        $postObjectController->editPostContent($post);
        self::assertEquals('post', $postObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(1, $postObjectController->getObjectInformation()->getObjectId());
        $expectedOutput .= '!UserAccessManager\Controller\Backend\PostObjectController|'
            . 'vfs://root/src/View/PostEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addBulkAction('invalid');
        $expectedOutput .= '';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addBulkAction('invalid');
        $expectedOutput .= '';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addBulkAction(ObjectController::COLUMN_NAME);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\PostObjectController|'
            . 'vfs://root/src/View/BulkEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        $return = $postObjectController->showMediaFile(['a' => 'b']);
        self::assertEquals(null, $postObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(null, $postObjectController->getObjectInformation()->getObjectId());
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\PostObjectController|'
                        . 'vfs://root/src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );
        $this->resetControllerObjectInformation($postObjectController);

        $return = $postObjectController->showMediaFile(['a' => 'b'], $post);
        self::assertEquals('post', $postObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(1, $postObjectController->getObjectInformation()->getObjectId());
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\PostObjectController|'
                        . 'vfs://root/src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );
        $this->resetControllerObjectInformation($postObjectController);

        $_GET['attachment_id'] = 3;
        $return = $postObjectController->showMediaFile(['a' => 'b'], $post);
        self::assertEquals('attachment', $postObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(3, $postObjectController->getObjectInformation()->getObjectId());
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\PostObjectController|'
                        . 'vfs://root/src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );
        $this->resetControllerObjectInformation($postObjectController);

        $_GET['action'] = 'edit';
        $return = $postObjectController->showMediaFile(['a' => 'b'], $post);
        self::assertEquals(['a' => 'b'], $return);

        self::expectOutputString($expectedOutput);
    }

    /**
     * @group  unit
     * @covers ::savePostData()
     * @covers ::saveAttachmentData()
     * @covers ::saveAjaxAttachmentData()
     * @throws UserGroupTypeException
     */
    public function testSaveObjectData()
    {
        /**
         * @var PostObjectController $postObjectController
         */
        $postObjectController = $this->getTestSaveObjectDataPrototype(
            PostObjectController::class,
            [
                ['post', 1],
                ['post', 1],
                ['post', 1],
                ['attachment', 3],
                ['attachment', 3],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, null]
            ]
        );

        $postObjectController->savePostData(['ID' => 1]);
        $postObjectController->savePostData(['ID' => 2]);
        $postObjectController->savePostData(2);
        self::assertEquals(['ID' => 3, 'title' => 'T'], $postObjectController->saveAttachmentData(['ID' => 3, 'title' => 'T']));
        $postObjectController->saveAttachmentData(['ID' => 3]);
        $postObjectController->saveAjaxAttachmentData();
    }

    /**
     * @param string $id
     * @param bool $isDefault
     * @return MockObject|UserGroup
     */
    private function getUserGroupWithDefault(string $id, bool $isDefault)
    {
        $userGroups = $this->getUserGroup($id);
        $userGroups->expects($this->any())
            ->method('isDefaultGroupForObjectType')
            ->will($this->returnValue($isDefault));

        return $userGroups;
    }

    /**
     * @group  unit
     * @covers ::addAttachment()
     * @throws UserGroupTypeException
     */
    public function testAddAttachment()
    {
        $userHandler = $this->getUserHandler();
        $userHandler->expects($this->any())
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->returnValue(false));

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->any())
            ->method('getFilteredUserGroups')
            ->will($this->returnValue([]));

        $userGroupHandler->expects($this->once())
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(
                []
            )
            ->will($this->returnValue([]));

        $userGroupHandler->expects($this->once())
            ->method('getFullUserGroups')
            ->will($this->returnValue([
                $this->getUserGroupWithDefault(1, false),
                $this->getUserGroupWithDefault(2, true),
                $this->getUserGroupWithDefault(3, false),
                $this->getUserGroupWithDefault(4, true)
            ]));

        $userGroupAssignmentHandler = $this->getUserGroupAssignmentHandler();
        $userGroupAssignmentHandler->expects($this->once())
            ->method('assignObjectToUserGroups')
            ->with(
                ObjectHandler::POST_OBJECT_TYPE,
                1,
                [
                    2 => ['id' => 2],
                    4 => ['id' => 4]
                ]
            );

        $postObjectController = new PostObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getExtendedObjectHandler(),
            $userHandler,
            $userGroupHandler,
            $userGroupAssignmentHandler,
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        unset($_GET);
        unset($_POST);
        $postObjectController->addAttachment(1);
    }

    /**
     * @group  unit
     * @covers ::removePostData()
     */
    public function testRemovePostData()
    {
        /**
         * @var PostObjectController $postObjectController
         */
        $postObjectController = $this->getTestRemoveObjectDataPrototype(
            PostObjectController::class,
            1,
            'post'
        );
        $postObjectController->removePostData(1);
    }
}
