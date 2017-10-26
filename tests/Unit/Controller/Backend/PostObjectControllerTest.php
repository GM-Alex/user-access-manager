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

use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Controller\Backend\PostObjectController;
use UserAccessManager\Object\ObjectHandler;

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
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
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
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals(
            ['a' => 'a', ObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $postObjectController->addPostColumnsHeader(['a' => 'a'])
        );
    }

    /**
     * @group  unit
     * @covers ::addPostColumn()
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
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        $postObjectController->addPostColumn(ObjectController::COLUMN_NAME, 1);
    }

    /**
     * @group  unit
     * @covers ::addPostColumn()
     * @covers ::editPostContent()
     * @covers ::addBulkAction()
     * @covers ::showMediaFile()
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
        self::assertAttributeEquals('post', 'objectType', $postObjectController);
        self::assertAttributeEquals(1, 'objectId', $postObjectController);
        $expectedOutput = '!UserAccessManager\Controller\Backend\PostObjectController|'
            .'vfs://root/src/View/ObjectColumn.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->editPostContent(null);
        self::assertAttributeEquals(null, 'objectType', $postObjectController);
        self::assertAttributeEquals(null, 'objectId', $postObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\PostObjectController|'
            .'vfs://root/src/View/PostEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        $postObjectController->editPostContent($post);
        self::assertAttributeEquals('post', 'objectType', $postObjectController);
        self::assertAttributeEquals(1, 'objectId', $postObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\PostObjectController|'
            .'vfs://root/src/View/PostEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addBulkAction('invalid');
        $expectedOutput .= '';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addBulkAction('invalid');
        $expectedOutput .= '';
        $this->resetControllerObjectInformation($postObjectController);

        $postObjectController->addBulkAction(ObjectController::COLUMN_NAME);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\PostObjectController|'
            .'vfs://root/src/View/BulkEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($postObjectController);

        $return = $postObjectController->showMediaFile(['a' => 'b']);
        self::assertAttributeEquals(null, 'objectType', $postObjectController);
        self::assertAttributeEquals(null, 'objectId', $postObjectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\PostObjectController|'
                        .'vfs://root/src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );
        $this->resetControllerObjectInformation($postObjectController);

        $return = $postObjectController->showMediaFile(['a' => 'b'], $post);
        self::assertAttributeEquals('post', 'objectType', $postObjectController);
        self::assertAttributeEquals(1, 'objectId', $postObjectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\PostObjectController|'
                        .'vfs://root/src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );
        $this->resetControllerObjectInformation($postObjectController);

        $_GET['attachment_id'] = 3;
        $return = $postObjectController->showMediaFile(['a' => 'b'], $post);
        self::assertAttributeEquals('attachment', 'objectType', $postObjectController);
        self::assertAttributeEquals(3, 'objectId', $postObjectController);
        self::assertEquals(
            [
                'a' => 'b',
                'uam_user_groups' => [
                    'label' => 'Set up user groups|user-access-manager',
                    'input' => 'editFrom',
                    'editFrom' => '!UserAccessManager\Controller\Backend\PostObjectController|'
                        .'vfs://root/src/View/MediaAjaxEditForm.php|uam_user_groups!'
                ]
            ],
            $return
        );
        $this->resetControllerObjectInformation($postObjectController);

        self::expectOutputString($expectedOutput);
    }

    /**
     * @group  unit
     * @covers ::savePostData()
     * @covers ::saveAttachmentData()
     * @covers ::saveAjaxAttachmentData()
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
        $postObjectController->saveAttachmentData(['ID' => 3]);
        $postObjectController->saveAttachmentData(['ID' => 3]);
        $postObjectController->saveAjaxAttachmentData();
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
