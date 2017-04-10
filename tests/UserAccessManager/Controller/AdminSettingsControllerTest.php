<?php
/**
 * AdminSettingsControllerTest.php
 *
 * The AdminSettingsControllerTest unit test class file.
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

/**
 * Class AdminSettingsControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminSettingsControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSettingsController::__construct()
     */
    public function testCanCreateInstance()
    {
        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminSettingsController', $adminSettingController);
    }

    /**
     * @param string $name
     *
     * @return \stdClass
     */
    private function createTypeObject($name)
    {
        $type = new \stdClass();
        $type->labels = new \stdClass();
        $type->labels->name = $name;

        return $type;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::isNginx()
     */
    public function testIsNginx()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, true));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertFalse($adminSettingController->isNginx());
        self::assertTrue($adminSettingController->isNginx());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getPages()
     */
    public function testGetPages()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getPages')
            ->with('sort_column=menu_order')
            ->will($this->onConsecutiveCalls(false, ['a' => 'a']));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertEquals([], $adminSettingController->getPages());
        self::assertEquals(['a' => 'a'], $adminSettingController->getPages());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getConfigParameters()
     */
    public function testGetConfigParameters()
    {
        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('getConfigParameters')
            ->will($this->returnValue(['a' => 'a']));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $this->getWordpress(),
            $config,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertEquals(['a' => 'a'], $adminSettingController->getConfigParameters());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getGroupedConfigParameters()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getPostTypes()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getTaxonomies()
     */
    public function testGetGroupedConfigParameters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => $this->createTypeObject('attachment'),
                ObjectHandler::POST_OBJECT_TYPE => $this->createTypeObject('post'),
                ObjectHandler::PAGE_OBJECT_TYPE => $this->createTypeObject('page')
            ]));

        $wordpress->expects($this->exactly(2))
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => $this->createTypeObject('category')
            ]));
        
        $configValues = [
            'hide_post' => 'hide_post',
            'hide_post_title' => 'hide_post_title',
            'post_title' => 'post_title',
            'show_post_content_before_more' => 'show_post_content_before_more',
            'post_content' => 'post_content',
            'hide_post_comment' => 'hide_post_comment',
            'post_comment_content' => 'post_comment_content',
            'post_comments_locked' => 'post_comments_locked',
            'hide_page' => 'hide_page',
            'hide_page_title' => 'hide_page_title',
            'page_title' => 'page_title',
            'page_content' => 'page_content',
            'hide_page_comment' => 'hide_page_comment',
            'page_comment_content' => 'page_comment_content',
            'page_comments_locked' => 'page_comments_locked',
            'redirect' => 'redirect',
            'redirect_custom_page' => 'redirect_custom_page',
            'redirect_custom_url' => 'redirect_custom_url',
            'lock_recursive' => 'lock_recursive',
            'authors_has_access_to_own' => 'authors_has_access_to_own',
            'authors_can_add_posts_to_groups' => 'authors_can_add_posts_to_groups',
            'lock_file' => 'lock_file',
            'file_pass_type' => 'file_pass_type',
            'download_type' => 'download_type',
            'lock_file_types' => 'lock_file_types',
            'locked_file_types' => 'locked_file_types',
            'not_locked_file_types' => 'not_locked_file_types',
            'blog_admin_hint' => 'blog_admin_hint',
            'blog_admin_hint_text' => 'blog_admin_hint_text',
            'hide_empty_category' => 'hide_empty_category',
            'protect_feed' => 'protect_feed',
            'full_access_role' => 'full_access_role'
        ];

        $config = $this->getConfig();
        $config->expects($this->exactly(2))
            ->method('getConfigParameters')
            ->will($this->returnValue($configValues));
        
        $config->expects($this->exactly(2))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, true));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        $expected = [
            'post' => [
                0 => 'hide_post',
                1 => 'hide_post_title',
                2 => 'post_title',
                3 => 'post_content',
                4 => 'hide_post_comment',
                5 => 'post_comment_content',
                6 => 'post_comments_locked',
                7 => 'show_post_content_before_more'
                
            ],
            'page' => [
                0 => 'hide_page',
                1 => 'hide_page_title',
                2 => 'page_title',
                3 => 'page_content',
                4 => 'hide_page_comment',
                5 => 'page_comment_content',
                6 => 'page_comments_locked'

            ],
            'category' => [
                0 => 'hide_empty_category'
            ],
            'file' => [
                0 => 'lock_file',
                1 => 'download_type'
            ],
            'author' => [
                0 => 'authors_has_access_to_own',
                1 => 'authors_can_add_posts_to_groups',
                2 => 'full_access_role'
            ],
            'other' => [
                0 => 'lock_recursive',
                1 => 'protect_feed',
                2 => 'redirect',
                3 => 'blog_admin_hint',
                4 => 'blog_admin_hint_text'
            ]
        ];
        
        self::assertEquals($expected, $adminSettingController->getGroupedConfigParameters());

        $expected['file'][2] = 'lock_file_types';
        $expected['file'][3] = 'file_pass_type';

        self::assertEquals($expected, $adminSettingController->getGroupedConfigParameters());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::updateSettingsAction()
     */
    public function testUpdateSettingsAction()
    {
        $config = $this->getConfig();
        $config->expects($this->exactly(3))
            ->method('setConfigParameters')
            ->with([
                'b' => '&lt;b&gt;b&lt;/b&gt;',
                'i' => '&lt;i&gt;i&lt;/i&gt;',
            ]);

        $config->expects($this->exactly(3))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('verifyNonce')
            ->will($this->returnValue(true));

        $wordpress->expects($this->exactly(3))
            ->method('doAction')
            ->with('uam_update_options', $config);

        $fileHandler = $this->getFileHandler();

        $fileHandler->expects($this->exactly(2))
            ->method('createFileProtection');

        $fileHandler->expects($this->once())
            ->method('deleteFileProtection');

        $_POST['config_parameters'] = [
            'b' => '<b>b</b>',
            'i' => '<i>i</i>'
        ];

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getObjectHandler(),
            $fileHandler
        );

        $adminSettingController->updateSettingsAction();
        $adminSettingController->updateSettingsAction();
        $adminSettingController->updateSettingsAction();

        self::assertAttributeEquals(TXT_UAM_UPDATE_SETTINGS, 'updateMessage', $adminSettingController);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::isPostTypeGroup()
     */
    public function testIsPostTypeGroup()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => $this->createTypeObject('attachment'),
                ObjectHandler::POST_OBJECT_TYPE => $this->createTypeObject('post'),
                ObjectHandler::PAGE_OBJECT_TYPE => $this->createTypeObject('page')
            ]));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertTrue($adminSettingController->isPostTypeGroup(ObjectHandler::ATTACHMENT_OBJECT_TYPE));
        self::assertTrue($adminSettingController->isPostTypeGroup(ObjectHandler::POST_OBJECT_TYPE));
        self::assertTrue($adminSettingController->isPostTypeGroup(ObjectHandler::PAGE_OBJECT_TYPE));
        self::assertFalse($adminSettingController->isPostTypeGroup('something'));
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getSectionText()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getParameterText()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getObjectText()
     */
    public function testGetText()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(5))
            ->method('arrayFill')
            ->withConsecutive(
                [0, 1, 'category'],
                [0, 1, 'attachment'],
                [0, 0, 'post'],
                [0, 0, 'post'],
                [0, 2, 'post']
            )->will($this->returnCallback(function ($startIndex, $numberOfElements, $value) {
                return array_fill($startIndex, $numberOfElements, $value);
            }));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(9))
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => $this->createTypeObject('attachment'),
                ObjectHandler::POST_OBJECT_TYPE => $this->createTypeObject('post'),
                ObjectHandler::PAGE_OBJECT_TYPE => $this->createTypeObject('page')
            ]));

        $wordpress->expects($this->exactly(9))
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => $this->createTypeObject('category')
            ]));

        $adminSettingController = new AdminSettingsController(
            $php,
            $wordpress,
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameter $parameter
         */
        $parameter = self::getMockForAbstractClass(
            '\UserAccessManager\Config\ConfigParameter',
            [],
            '',
            false,
            true,
            true,
            ['getId']
        );

        $parameter->expects(self::any())
            ->method('getId')
            ->will($this->returnValue('test_id'));

        define('TXT_UAM_GROUP_KEY_SETTING', 'TEST');
        define('TXT_UAM_GROUP_KEY_SETTING_DESC', 'TEST_DESC');
        define('TXT_UAM_TEST_ID', 'TEST_ID');
        define('TXT_UAM_TEST_ID_DESC', 'TEST_ID_DESC');

        self::assertEquals('TEST', $adminSettingController->getSectionText('group_key'));
        self::assertEquals('TEST_DESC', $adminSettingController->getSectionText('group_key', true));

        self::assertEquals('TEST_ID', $adminSettingController->getParameterText('group_key', $parameter));
        self::assertEquals('TEST_ID_DESC', $adminSettingController->getParameterText('group_key', $parameter, true));

        self::assertEquals(
            'category settings|user-access-manager',
            $adminSettingController->getSectionText('category')
        );
        self::assertEquals(
            'Set up the behaviour if the attachment is locked|user-access-manager',
            $adminSettingController->getSectionText(ObjectHandler::ATTACHMENT_OBJECT_TYPE, true)
        );
        self::assertEquals(
            'TEST_ID',
            $adminSettingController->getParameterText(ObjectHandler::POST_OBJECT_TYPE, $parameter)
        );
        self::assertEquals(
            'TEST_ID_DESC',
            $adminSettingController->getParameterText(ObjectHandler::POST_OBJECT_TYPE, $parameter, true)
        );

        define('TXT_UAM_TEST', '%s %s');
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameter $parameter
         */
        $parameter = self::getMockForAbstractClass(
            '\UserAccessManager\Config\ConfigParameter',
            [],
            '',
            false,
            true,
            true,
            ['getId']
        );

        $parameter->expects(self::any())
            ->method('getId')
            ->will($this->returnValue('test'));

        self::assertEquals(
            'post post',
            $adminSettingController->getParameterText(ObjectHandler::POST_OBJECT_TYPE, $parameter)
        );
    }
}
