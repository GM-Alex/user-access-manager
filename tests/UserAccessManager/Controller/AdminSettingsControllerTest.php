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

use UserAccessManager\Config\BooleanConfigParameter;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\Config\SelectionConfigParameter;
use UserAccessManager\Config\StringConfigParameter;
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
            $this->getMainConfig(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertInstanceOf(AdminSettingsController::class, $adminSettingController);
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
            $this->getMainConfig(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
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
            $this->getMainConfig(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertEquals([], self::callMethod($adminSettingController, 'getPages'));
        self::assertEquals(['a' => 'a'], self::callMethod($adminSettingController, 'getPages'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSettingsController::getText()
     * @covers \UserAccessManager\Controller\AdminSettingsController::getGroupText()
     */
    public function testGetText()
    {
        $formHelper = $this->getFormHelper();
        $formHelper->expects($this->exactly(3))
            ->method('getText')
            ->withConsecutive(
                ['firstKey', false],
                ['secondKey', true],
                ['firstKey', false]
            )
            ->will($this->returnValue('text'));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $formHelper
        );

        self::assertEquals('text', $adminSettingController->getText('firstKey'));
        self::assertEquals('text', $adminSettingController->getText('secondKey', true));
        self::assertEquals('text', $adminSettingController->getGroupText('firstKey'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSettingsController::getObjectName()
     * @covers \UserAccessManager\Controller\AdminSettingsController::getGroupSectionText()
     */
    public function testGetObjectName()
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

        $wordpress->expects($this->exactly(4))
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => $this->createTypeObject('category')
            ]));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertEquals('attachment', $adminSettingController->getObjectName(ObjectHandler::ATTACHMENT_OBJECT_TYPE));
        self::assertEquals('post', $adminSettingController->getObjectName(ObjectHandler::POST_OBJECT_TYPE));
        self::assertEquals('something', $adminSettingController->getObjectName('something'));
        self::assertEquals(
            TXT_UAM_SETTINGS_GROUP_SECTION_DEFAULT,
            $adminSettingController->getGroupSectionText(MainConfig::DEFAULT_TYPE)
        );
        self::assertEquals('something', $adminSettingController->getGroupSectionText('something'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSettingsController::getTabGroups()
     */
    public function testGetSettingsGroups()
    {
        $expected = [
            AdminSettingsController::GROUP_POST_TYPES => [MainConfig::DEFAULT_TYPE, 'attachment', 'post', 'page'],
            AdminSettingsController::GROUP_TAXONOMIES => [MainConfig::DEFAULT_TYPE, 'category', 'post_format'],
            AdminSettingsController::GROUP_FILES => [AdminSettingsController::GROUP_FILES],
            AdminSettingsController::GROUP_AUTHOR => [AdminSettingsController::GROUP_AUTHOR],
            AdminSettingsController::GROUP_CACHE => ['activeCacheProvider', 'cacheProviderId'],
            AdminSettingsController::GROUP_OTHER => [AdminSettingsController::GROUP_OTHER]
        ];

        $wordpress = $this->getWordpressWithPostTypesAndTaxonomies(1, 1);

        $config = $this->getMainConfig();

        $config->expects($this->once())
            ->method('getActiveCacheProvider')
            ->will($this->returnValue('activeCacheProvider'));

        $cacheProvider = $this->createMock('\UserAccessManager\Cache\CacheProviderInterface');
        $cacheProvider->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('cacheProviderId'));

        $activeCacheProvider = $this->createMock('\UserAccessManager\Cache\CacheProviderInterface');
        $activeCacheProvider->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('activeCacheProvider'));

        $cache = $this->getCache();
        $cache->expects($this->once())
            ->method('getRegisteredCacheProviders')
            ->will($this->returnValue([$activeCacheProvider, $cacheProvider]));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $config,
            $cache,
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertEquals($expected, $adminSettingController->getTabGroups());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMultipleFormElementValue()
    {
        return $this->createMock('\UserAccessManager\Form\MultipleFormElementValue');
    }

    /**
     * @param $expectedPostTypes
     * @param $expectedTaxonomies
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWordpressWithPostTypesAndTaxonomies($expectedPostTypes, $expectedTaxonomies)
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly($expectedPostTypes))
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => $this->createTypeObject('attachment'),
                ObjectHandler::POST_OBJECT_TYPE => $this->createTypeObject('post'),
                ObjectHandler::PAGE_OBJECT_TYPE => $this->createTypeObject('page')
            ]));

        $wordpress->expects($this->exactly($expectedTaxonomies))
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => $this->createTypeObject('category'),
                ObjectHandler::POST_FORMAT_TYPE => $this->createTypeObject('postFormat'),
            ]));

        return $wordpress;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getCurrentGroupForms()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getPostTypes()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getTaxonomies()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getPostSettingsForm()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getTaxonomySettingsForm()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getFilesSettingsForm()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getAuthorSettingsForm()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getOtherSettingsForm()
     * @covers  \UserAccessManager\Controller\AdminSettingsController::getPages()
     */
    public function testGetCurrentGroupForms()
    {
        $wordpress = $this->getWordpressWithPostTypesAndTaxonomies(8, 8);

        $wordpress->expects($this->exactly(2))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, true));

        $pages = [];

        $firstPage = new \stdClass();
        $firstPage->ID = 1;
        $firstPage->post_title = 'firstPage';
        $pages[] = $firstPage;

        $secondPage = new \stdClass();
        $secondPage->ID = 2;
        $secondPage->post_title = 'secondPage';
        $pages[] = $secondPage;

        $wordpress->expects($this->once())
            ->method('getPages')
            ->with('sort_column=menu_order')
            ->will($this->returnValue($pages));

        $configValues = [
            'hide_post' => $this->getConfigParameter('boolean'),
            'hide_post_title' => $this->getConfigParameter('boolean'),
            'post_title' => $this->getConfigParameter('string'),
            'show_post_content_before_more' => $this->getConfigParameter('boolean'),
            'post_content' => $this->getConfigParameter('string'),
            'hide_post_comment' => $this->getConfigParameter('boolean'),
            'post_comment_content' => $this->getConfigParameter('string'),
            'post_comments_locked' => $this->getConfigParameter('boolean'),
            'hide_page' => $this->getConfigParameter('boolean'),
            'hide_page_title' => $this->getConfigParameter('boolean'),
            'page_title' => $this->getConfigParameter('string'),
            'page_content' => $this->getConfigParameter('string'),
            'hide_page_comment' => $this->getConfigParameter('boolean'),
            'page_comment_content' => $this->getConfigParameter('string'),
            'page_comments_locked' => $this->getConfigParameter('boolean'),
            'hide_empty_category' => $this->getConfigParameter('boolean'),
            'lock_file' => $this->getConfigParameter('boolean'),
            'download_type' => $this->getConfigParameter('selection'),
            'lock_file_types' => $this->getConfigParameter('selection'),
            'locked_file_types' => $this->getConfigParameter('string'),
            'not_locked_file_types' => $this->getConfigParameter('string'),
            'file_pass_type' => $this->getConfigParameter('selection'),
            'authors_has_access_to_own' => $this->getConfigParameter('boolean'),
            'authors_can_add_posts_to_groups' => $this->getConfigParameter('boolean'),
            'full_access_role' => $this->getConfigParameter('selection'),
            'lock_recursive' => $this->getConfigParameter('boolean'),
            'protect_feed' => $this->getConfigParameter('boolean'),
            'redirect' => $this->getConfigParameter('selection'),
            'redirect_custom_page' => $this->getConfigParameter('string'),
            'redirect_custom_url' => $this->getConfigParameter('string'),
            'blog_admin_hint' => $this->getConfigParameter('boolean'),
            'blog_admin_hint_text' => $this->getConfigParameter('string')
        ];

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(6))
            ->method('getConfigParameters')
            ->will($this->returnValue($configValues));

        $config = $this->getConfig();

        $cacheProvider = $this->createMock('\UserAccessManager\Cache\CacheProviderInterface');
        $cacheProvider->expects($this->exactly(15))
            ->method('getId')
            ->will($this->returnValue('cacheProviderId'));
        $cacheProvider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $cache = $this->getCache();
        $cache->expects($this->exactly(8))
            ->method('getRegisteredCacheProviders')
            ->will($this->returnValue([$cacheProvider]));

        $formFactory = $this->getFormFactory();

        $formFactory->expects($this->exactly(2))
            ->method('createTextarea')
            ->withConsecutive(
                ['stringId', 'stringValue', 'stringIdPost', 'stringIdPostDesc'],
                ['stringId', 'stringValue', 'stringIdPage', 'stringIdPageDesc']
            )
            ->will($this->onConsecutiveCalls('postTextarea', 'pageTextarea'));

        $formFactory->expects($this->exactly(9))
            ->method('createMultipleFormElementValue')
            ->withConsecutive(
                ['all', TXT_UAM_ALL],
                ['selected', TXT_UAM_LOCKED_FILE_TYPES],
                ['not_selected', TXT_UAM_NOT_LOCKED_FILE_TYPES],
                ['all', TXT_UAM_ALL],
                ['selected', TXT_UAM_LOCKED_FILE_TYPES],
                ['false', TXT_UAM_NO],
                ['blog', TXT_UAM_REDIRECT_TO_BLOG],
                ['selected', TXT_UAM_REDIRECT_TO_PAGE],
                ['custom_url', TXT_UAM_REDIRECT_TO_URL]
            )
            ->will($this->returnValue($this->createMultipleFormElementValue()));

        $formFactory->expects($this->exactly(3))
            ->method('createRadio')
            ->withConsecutive(
                [
                    'selectionId',
                    [
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue()
                    ],
                    'selectionValue',
                    TXT_UAM_LOCK_FILE_TYPES,
                    TXT_UAM_LOCK_FILE_TYPES_DESC
                ],
                [
                    'selectionId',
                    [
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue()
                    ],
                    'selectionValue',
                    TXT_UAM_LOCK_FILE_TYPES,
                    TXT_UAM_LOCK_FILE_TYPES_DESC
                ],
                [
                    'selectionId',
                    [
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue()
                    ],
                    'selectionValue',
                    TXT_UAM_REDIRECT,
                    TXT_UAM_REDIRECT_DESC
                ]
            )
            ->will($this->onConsecutiveCalls('fileRadio', 'fileRadio', 'redirectRadio'));

        $formFactory->expects($this->exactly(2))
            ->method('createValueSetFromElementValue')
            ->withConsecutive(
                [1, 'firstPage'],
                [2, 'secondPage']
            )
            ->will($this->returnValue(
                $this->createMock('\UserAccessManager\Form\MultipleFormElementValue')
            ));

        $formFactory->expects($this->once())
            ->method('createSelect')
            ->withConsecutive(
                [
                    'stringId',
                    [
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue()
                    ],
                    0
                ]
            )
            ->will($this->returnValue(
                $this->createMock('\UserAccessManager\Form\Select')
            ));

        $formHelper = $this->getFormHelper();
        $formHelper->expects($this->exactly(9))
            ->method('getSettingsForm')
            ->withConsecutive(
                [
                    [
                        'hide_default',
                        'hide_default_title',
                        'default_title',
                        null,
                        'hide_default_comment',
                        'default_comment_content',
                        'default_comments_locked'
                    ],
                    MainConfig::DEFAULT_TYPE
                ],
                [
                    [
                        'post_use_default',
                        'hide_post',
                        'hide_post_title',
                        'post_title',
                        'postTextarea',
                        'hide_post_comment',
                        'post_comment_content',
                        'post_comments_locked',
                        'show_post_content_before_more'
                    ],
                    'post'
                ],
                [
                    [
                        'page_use_default',
                        'hide_page',
                        'hide_page_title',
                        'page_title',
                        'pageTextarea',
                        'hide_page_comment',
                        'page_comment_content',
                        'page_comments_locked'
                    ],
                    'page'
                ],
                [
                    ['hide_empty_default'],
                    MainConfig::DEFAULT_TYPE
                ],
                [
                    ['category_use_default', 'hide_empty_category'],
                    'category'
                ],
                [
                    [
                        'lock_file',
                        'download_type',
                        'fileRadio',
                        'file_pass_type'
                    ]
                ],
                [
                    [
                        'lock_file',
                        'download_type',
                        'fileRadio',
                        'file_pass_type'
                    ]
                ],
                [
                    [
                        'authors_has_access_to_own',
                        'authors_can_add_posts_to_groups',
                        'full_access_role'
                    ]
                ],
                [
                    [
                        'lock_recursive',
                        'protect_feed',
                        'redirectRadio',
                        'blog_admin_hint',
                        'blog_admin_hint_text'
                    ]
                ]
            )
            ->will($this->onConsecutiveCalls(
                'defaultPostTypeForm',
                'postForm',
                'pageForm',
                'defaultTaxonomyForm',
                'categoryForm',
                'fileForm',
                'fileForm',
                'authorForm',
                'otherForm'
            ));

        $formHelper->expects($this->exactly(4))
            ->method('getParameterText')
            ->will($this->returnCallback(
                function (ConfigParameter $configParameter, $description, $postType) {
                    $text = $configParameter->getId().ucfirst($postType);
                    $text .= ($description === true) ? 'Desc' : '';
                    return $text;
                }
            ));

        $formHelper->expects($this->exactly(4))
            ->method('convertConfigParameter')
            ->withConsecutive(
                [],
                [],
                [],
                []
            )
            ->will($this->returnCallback(function ($configParameter) {
                if (($configParameter instanceof StringConfigParameter) === true) {
                    return $this->createMock('\UserAccessManager\Form\Input');
                } elseif (($configParameter instanceof BooleanConfigParameter) === true) {
                    return $this->createMock('\UserAccessManager\Form\Radio');
                } elseif (($configParameter instanceof SelectionConfigParameter) === true) {
                    return $this->createMock('\UserAccessManager\Form\Select');
                }

                return null;
            }));

        $formHelper->expects($this->once())
            ->method('getSettingsFormByConfig')
            ->with($config)
            ->will($this->returnValue('configForm'));

        $adminSettingController = new AdminSettingsController(
            $this->getPhp(),
            $wordpress,
            $mainConfig,
            $cache,
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $formFactory,
            $formHelper
        );

        self::assertEquals(
            [
                'default' => 'defaultPostTypeForm',
                'post' => 'postForm',
                'page' => 'pageForm'
            ],
            $adminSettingController->getCurrentGroupForms()
        );

        $_GET['tab_group'] = AdminSettingsController::GROUP_TAXONOMIES;
        self::assertEquals(
            [
                'default' => 'defaultTaxonomyForm',
                'category' => 'categoryForm'
            ],
            $adminSettingController->getCurrentGroupForms()
        );

        $_GET['tab_group'] = AdminSettingsController::GROUP_FILES;
        self::assertEquals(['file' => 'fileForm'], $adminSettingController->getCurrentGroupForms());

        $_GET['tab_group'] = AdminSettingsController::GROUP_FILES;
        self::assertEquals(['file' => 'fileForm'], $adminSettingController->getCurrentGroupForms());

        $_GET['tab_group'] = AdminSettingsController::GROUP_AUTHOR;
        self::assertEquals(['author' => 'authorForm'], $adminSettingController->getCurrentGroupForms());

        $_GET['tab_group'] = AdminSettingsController::GROUP_CACHE;
        self::assertEquals(
            ['none' => null, 'cacheProviderId' => 'configForm'],
            $adminSettingController->getCurrentGroupForms()
        );

        $_GET['tab_group'] = AdminSettingsController::GROUP_OTHER;
        self::assertEquals(['other' => 'otherForm'], $adminSettingController->getCurrentGroupForms());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::updateSettingsAction()
     */
    public function testUpdateSettingsAction()
    {
        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(5))
            ->method('setConfigParameters')
            ->withConsecutive(
                [['b' => '&lt;b&gt;b&lt;/b&gt;', 'i' => '&lt;i&gt;i&lt;/i&gt;']],
                [['b' => '&lt;b&gt;b&lt;/b&gt;', 'i' => '&lt;i&gt;i&lt;/i&gt;']],
                [['b' => '&lt;b&gt;b&lt;/b&gt;', 'i' => '&lt;i&gt;i&lt;/i&gt;']],
                [['active_cache_provider' => MainConfig::CACHE_PROVIDER_NONE]],
                [['active_cache_provider' => 'cacheProviderId']]
            );

        $mainConfig->expects($this->exactly(5))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('setConfigParameters')
            ->with(['b' => '&lt;b&gt;b&lt;/b&gt;', 'i' => '&lt;i&gt;i&lt;/i&gt;']);

        $cacheProvider = $this->createMock('\UserAccessManager\Cache\CacheProviderInterface');
        $cacheProvider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $cache = $this->getCache();
        $cache->expects($this->exactly(11))
            ->method('getRegisteredCacheProviders')
            ->will($this->returnValue(['cacheProviderId' => $cacheProvider]));

        $wordpress = $this->getWordpressWithPostTypesAndTaxonomies(9, 9);
        $wordpress->expects($this->exactly(5))
            ->method('verifyNonce')
            ->will($this->returnValue(true));

        $wordpress->expects($this->exactly(5))
            ->method('doAction')
            ->with('uam_update_options', $mainConfig);

        $fileHandler = $this->getFileHandler();

        $fileHandler->expects($this->exactly(4))
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
            $mainConfig,
            $cache,
            $this->getObjectHandler(),
            $fileHandler,
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        $adminSettingController->updateSettingsAction();
        $adminSettingController->updateSettingsAction();
        $adminSettingController->updateSettingsAction();

        $_GET['tab_group'] = AdminSettingsController::GROUP_CACHE;
        $_GET['tab_group_section'] = MainConfig::CACHE_PROVIDER_NONE;
        $adminSettingController->updateSettingsAction();

        $_GET['tab_group'] = AdminSettingsController::GROUP_CACHE;
        $_GET['tab_group_section'] = 'cacheProviderId';
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
            $this->getMainConfig(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertTrue($adminSettingController->isPostTypeGroup(ObjectHandler::ATTACHMENT_OBJECT_TYPE));
        self::assertTrue($adminSettingController->isPostTypeGroup(ObjectHandler::POST_OBJECT_TYPE));
        self::assertTrue($adminSettingController->isPostTypeGroup(ObjectHandler::PAGE_OBJECT_TYPE));
        self::assertFalse($adminSettingController->isPostTypeGroup('something'));
    }
}
