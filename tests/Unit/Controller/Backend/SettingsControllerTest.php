<?php
/**
 * SettingsControllerTest.php
 *
 * The SettingsControllerTest unit test class file.
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
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;
use UserAccessManager\Cache\CacheProviderInterface;
use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Controller\Backend\BackendController;
use UserAccessManager\Controller\Backend\SettingsController;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\MultipleFormElementValue;
use UserAccessManager\Form\Radio;
use UserAccessManager\Form\Select;
use UserAccessManager\Form\Textarea;
use UserAccessManager\Form\ValueSetFormElement;
use UserAccessManager\Form\ValueSetFormElementValue;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Wrapper\Wordpress;
use VCR\VCR;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class SettingsControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\SettingsController
 */
class SettingsControllerTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    protected function setUp(): void
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
        parent::setUp();
    }

    /**
     * Tear down virtual file system.
     */
    protected function tearDown(): void
    {
        $this->root->unmount();
        parent::tearDown();
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $settingController = new SettingsController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getCache(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertInstanceOf(SettingsController::class, $settingController);
    }

    /**
     * @group  unit
     * @covers ::getPages()
     * @throws ReflectionException
     */
    public function testGetPages()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getPages')
            ->with('sort_column=menu_order')
            ->will($this->onConsecutiveCalls(false, ['a' => 'a']));

        $settingController = new SettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getCache(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertEquals([], self::callMethod($settingController, 'getPages'));
        self::assertEquals(['a' => 'a'], self::callMethod($settingController, 'getPages'));
    }

    /**
     * @group  unit
     * @covers ::getText()
     * @covers ::getGroupText()
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

        $settingController = new SettingsController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getCache(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $formHelper
        );

        self::assertEquals('text', $settingController->getText('firstKey'));
        self::assertEquals('text', $settingController->getText('secondKey', true));
        self::assertEquals('text', $settingController->getGroupText('firstKey'));
    }

    /**
     * @group  unit
     * @covers ::getObjectName()
     * @covers ::getGroupSectionText()
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

        $settingController = new SettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getCache(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertEquals('attachment', $settingController->getObjectName(ObjectHandler::ATTACHMENT_OBJECT_TYPE));
        self::assertEquals('post', $settingController->getObjectName(ObjectHandler::POST_OBJECT_TYPE));
        self::assertEquals('something', $settingController->getObjectName('something'));
        self::assertEquals(
            TXT_UAM_SETTINGS_GROUP_SECTION_DEFAULT,
            $settingController->getGroupSectionText(MainConfig::DEFAULT_TYPE)
        );
        self::assertEquals('something', $settingController->getGroupSectionText('something'));
    }

    /**
     * @group  unit
     * @covers ::getTabGroups()
     */
    public function testGetSettingsGroups()
    {
        $expected = [
            SettingsController::GROUP_POST_TYPES => [MainConfig::DEFAULT_TYPE, 'attachment', 'post', 'page'],
            SettingsController::GROUP_TAXONOMIES => [MainConfig::DEFAULT_TYPE, 'category', 'post_format'],
            SettingsController::GROUP_FILES => [SettingsController::GROUP_FILES],
            SettingsController::GROUP_AUTHOR => [SettingsController::GROUP_AUTHOR],
            SettingsController::GROUP_CACHE => ['activeCacheProvider', 'cacheProviderId', 'none'],
            SettingsController::GROUP_OTHER => [SettingsController::GROUP_OTHER]
        ];

        $wordpress = $this->getWordpressWithPostTypesAndTaxonomies(1, 1);

        $config = $this->getMainConfig();

        $config->expects($this->once())
            ->method('getActiveCacheProvider')
            ->will($this->returnValue('activeCacheProvider'));

        $cacheProvider = $this->createMock(CacheProviderInterface::class);
        $cacheProvider->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('cacheProviderId'));

        $activeCacheProvider = $this->createMock(CacheProviderInterface::class);
        $activeCacheProvider->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('activeCacheProvider'));

        $cache = $this->getCache();
        $cache->expects($this->once())
            ->method('getRegisteredCacheProviders')
            ->will($this->returnValue([$activeCacheProvider, $cacheProvider]));

        $settingController = new SettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $config,
            $cache,
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertEquals($expected, $settingController->getTabGroups());
    }

    /**
     * @return MockObject
     */
    private function createMultipleFormElementValue(): MockObject
    {
        return $this->createMock(MultipleFormElementValue::class);
    }

    /**
     * @param $expectedPostTypes
     * @param $expectedTaxonomies
     * @return MockObject|Wordpress
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
     * @group  unit
     * @covers ::getCurrentGroupForms()
     * @covers ::getFullPostSettingsForm()
     * @covers ::getFullTaxonomySettingsForm()
     * @covers ::getFullCacheProvidersForm()
     * @covers ::getFullSettingsFrom()
     * @covers ::getPostTypes()
     * @covers ::getTaxonomies()
     * @covers ::getPostSettingsForm()
     * @covers ::getTaxonomySettingsForm()
     * @covers ::getFilesSettingsForm()
     * @covers ::addLockFileTypes()
     * @covers ::isXSendFileAvailable()
     * @covers ::disableXSendFileOption()
     * @covers ::getAuthorSettingsForm()
     * @covers ::getOtherSettingsForm()
     * @covers ::addCustomPageRedirectFormElement()
     * @covers ::getPages()
     */
    public function testGetCurrentGroupForms()
    {
        $wordpress = $this->getWordpressWithPostTypesAndTaxonomies(12, 12);

        $wordpress->expects($this->exactly(4))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(true, false, false, false));

        $pages = [];

        $firstPage = new stdClass();
        $firstPage->ID = 1;
        $firstPage->post_title = 'firstPage';
        $pages[] = $firstPage;

        $secondPage = new stdClass();
        $secondPage->ID = 2;
        $secondPage->post_title = 'secondPage';
        $pages[] = $secondPage;

        $wordpress->expects($this->exactly(2))
            ->method('getPages')
            ->with('sort_column=menu_order')
            ->will($this->returnValue($pages));

        $wordpress->expects($this->exactly(5))
            ->method('getSiteUrl')
            ->will($this->returnValue('https://localhost'));

        $wordpress->expects($this->exactly(3))
            ->method('gotModRewrite')
            ->will($this->onConsecutiveCalls(true, true, false));

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
            'inline_files' => $this->getConfigParameter('string'),
            'no_access_image_type' => $this->getConfigParameter('selection'),
            'custom_no_access_image' => $this->getConfigParameter('string'),
            'use_custom_file_handling_file' => $this->getConfigParameter('boolean'),
            'locked_directory_type' => $this->getConfigParameter('selection'),
            'custom_locked_directories' => $this->getConfigParameter('string'),
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
        $mainConfig->expects($this->exactly(10))
            ->method('getConfigParameters')
            ->will($this->returnCallback(function () use (&$configValues) {
                return $configValues;
            }));

        $config = $this->getConfig();

        $cacheProvider = $this->createMock(CacheProviderInterface::class);
        $cacheProvider->expects($this->exactly(23))
            ->method('getId')
            ->will($this->returnValue('cacheProviderId'));
        $cacheProvider->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $cache = $this->getCache();
        $cache->expects($this->exactly(12))
            ->method('getRegisteredCacheProviders')
            ->will($this->returnValue([$cacheProvider]));

        $formFactory = $this->getFormFactory();

        $postTextarea = $this->createMock(Textarea::class);
        $pageTextarea = $this->createMock(Textarea::class);
        $customFileHandlingFile = $this->createMock(Textarea::class);

        $customFileHandlingFileTextarea = [
            'custom_file_handling_file',
            'fileProtectionFileContent',
            TXT_UAM_CUSTOM_FILE_HANDLING_FILE,
            TXT_UAM_CUSTOM_FILE_HANDLING_FILE_DESC
        ];

        $formFactory->expects($this->exactly(7))
            ->method('createTextarea')
            ->withConsecutive(
                ['stringId', 'stringValue', 'stringIdPost', 'stringIdPostDesc'],
                ['stringId', 'stringValue', 'stringIdPage', 'stringIdPageDesc'],
                $customFileHandlingFileTextarea,
                $customFileHandlingFileTextarea,
                $customFileHandlingFileTextarea,
                $customFileHandlingFileTextarea,
                $customFileHandlingFileTextarea
            )
            ->will($this->onConsecutiveCalls(
                $postTextarea,
                $pageTextarea,
                $customFileHandlingFile,
                $customFileHandlingFile,
                $customFileHandlingFile,
                $customFileHandlingFile,
                $customFileHandlingFile
            ));

        $createMultipleFormElementValueReturn = array_fill(0, 7, $this->createMultipleFormElementValue());
        $exceptionElement = $this->createMultipleFormElementValue();
        $exceptionElement->expects($this->once())
            ->method('setSubElement')
            ->will($this->returnCallback(function () {
                throw new Exception('error');
            }));
        $createMultipleFormElementValueReturn[] = $exceptionElement;

        $formFactory->expects($this->exactly(8))
            ->method('createMultipleFormElementValue')
            ->withConsecutive(
                ['false', TXT_UAM_NO],
                ['blog', TXT_UAM_REDIRECT_TO_BLOG],
                ['login', TXT_UAM_REDIRECT_TO_LOGIN],
                ['custom_page', TXT_UAM_REDIRECT_TO_PAGE],
                ['false', TXT_UAM_NO],
                ['blog', TXT_UAM_REDIRECT_TO_BLOG],
                ['login', TXT_UAM_REDIRECT_TO_LOGIN],
                ['custom_page', TXT_UAM_REDIRECT_TO_PAGE]
            )
            ->will($this->onConsecutiveCalls(...$createMultipleFormElementValueReturn));

        $redirectOne = [
            'selectionId',
            [
                $this->createMultipleFormElementValue(),
                $this->createMultipleFormElementValue(),
                $this->createMultipleFormElementValue(),
                $this->createMultipleFormElementValue(),
                $this->createMultipleFormElementValue()
            ],
            'selectionValue',
            TXT_UAM_REDIRECT,
            TXT_UAM_REDIRECT_DESC
        ];

        $redirectTwo = [
            'selectionId',
            [
                $this->createMultipleFormElementValue(),
                $this->createMultipleFormElementValue(),
                $this->createMultipleFormElementValue()
            ],
            'selectionValue',
            TXT_UAM_REDIRECT,
            TXT_UAM_REDIRECT_DESC
        ];

        $redirectRadio = $this->createMock(Radio::class);
        $formFactory->expects($this->exactly(2))
            ->method('createRadio')
            ->withConsecutive(
                $redirectOne,
                $redirectTwo
            )
            ->will($this->onConsecutiveCalls(
                $redirectRadio,
                $redirectRadio
            ));

        $formFactory->expects($this->exactly(4))
            ->method('createValueSetFromElementValue')
            ->withConsecutive(
                [1, 'firstPage'],
                [2, 'secondPage'],
                [1, 'firstPage'],
                [2, 'secondPage']
            )
            ->will($this->returnValue(
                $this->createMock(MultipleFormElementValue::class)
            ));

        $formFactory->expects($this->exactly(2))
            ->method('createSelect')
            ->withConsecutive(
                [
                    'stringId',
                    [
                        $this->createMultipleFormElementValue(),
                        $this->createMultipleFormElementValue()
                    ],
                    0
                ],
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
                $this->createMock(Select::class)
            ));

        $xSendFileValue = $this->createMock(ValueSetFormElementValue::class);
        $xSendFileValue->expects($this->exactly(4))
            ->method('markDisabled');

        $downloadTypeElement = $this->createMock(ValueSetFormElement::class);
        $downloadTypeElement->expects($this->exactly(4))
            ->method('getPossibleValues')
            ->will($this->returnValue(['xsendfile' => $xSendFileValue]));

        $fileFrom = $this->createMock(Form::class);
        $fileFrom->expects($this->exactly(4))
            ->method('getElements')
            ->will($this->returnValue(['download_type' => $downloadTypeElement]));

        $formHelper = $this->getFormHelper();

        $defaultPostTypeForm = $this->createMock(Form::class);
        $postForm = $this->createMock(Form::class);
        $pageForm = $this->createMock(Form::class);
        $defaultTaxonomyForm = $this->createMock(Form::class);
        $categoryForm = $this->createMock(Form::class);
        $authorForm = $this->createMock(Form::class);
        $otherForm = $this->createMock(Form::class);

        $settingsFormReturns = [
            $defaultPostTypeForm,
            $postForm,
            $pageForm,
            $defaultTaxonomyForm,
            $categoryForm,
            $fileFrom,
            $fileFrom,
            $fileFrom,
            $fileFrom,
            $fileFrom,
            $authorForm,
            $otherForm
        ];

        $formHelper->expects($this->exactly(13))
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
                        'default_comments_locked',
                        'show_default_content_before_more'
                    ],
                    MainConfig::DEFAULT_TYPE
                ],
                [
                    [
                        'post_use_default',
                        'hide_post',
                        'hide_post_title',
                        'post_title',
                        $postTextarea,
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
                        $pageTextarea,
                        'hide_page_comment',
                        'page_comment_content',
                        'page_comments_locked',
                        'show_page_content_before_more'
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
                        'inline_files',
                        'no_access_image_type' => ['custom' => 'custom_no_access_image'],
                        'use_custom_file_handling_file',
                        $customFileHandlingFile,
                        'locked_directory_type' => ['custom' => 'custom_locked_directories']
                    ]
                ],
                [
                    [
                        'lock_file',
                        'download_type',
                        'inline_files',
                        'no_access_image_type' => ['custom' => 'custom_no_access_image'],
                        'use_custom_file_handling_file',
                        $customFileHandlingFile,
                        'locked_directory_type' => ['custom' => 'custom_locked_directories'],
                        'lock_file_types' => [
                            'selected' => 'locked_file_types',
                            'not_selected' => 'not_locked_file_types'
                        ]
                    ]
                ],
                [
                    [
                        'lock_file',
                        'download_type',
                        'inline_files',
                        'no_access_image_type' => ['custom' => 'custom_no_access_image'],
                        'use_custom_file_handling_file',
                        $customFileHandlingFile,
                        'locked_directory_type' => ['custom' => 'custom_locked_directories'],
                        'lock_file_types' => [
                            'selected' => 'locked_file_types',
                            'not_selected' => 'not_locked_file_types'
                        ]
                    ]
                ],
                [
                    [

                        'lock_file',
                        'download_type',
                        'inline_files',
                        'no_access_image_type' => ['custom' => 'custom_no_access_image'],
                        'use_custom_file_handling_file',
                        $customFileHandlingFile,
                        'locked_directory_type' => ['custom' => 'custom_locked_directories'],
                        'lock_file_types' => [
                            'selected' => 'locked_file_types',
                            'not_selected' => 'not_locked_file_types'
                        ],
                        'file_pass_type'
                    ]
                ],
                [
                    [

                        'lock_file',
                        'download_type',
                        'inline_files',
                        'no_access_image_type' => ['custom' => 'custom_no_access_image'],
                        'use_custom_file_handling_file',
                        $customFileHandlingFile,
                        'locked_directory_type' => ['custom' => 'custom_locked_directories']
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
                        $redirectRadio,
                        'blog_admin_hint',
                        'blog_admin_hint_text',
                        'show_assigned_groups',
                        'hide_edit_link_on_no_access',
                        'extra_ip_header'
                    ]
                ],
                [
                    [
                        'lock_recursive',
                        'protect_feed',
                        $redirectRadio,
                        'blog_admin_hint',
                        'blog_admin_hint_text',
                        'show_assigned_groups',
                        'hide_edit_link_on_no_access',
                        'extra_ip_header'
                    ]
                ]
            )
            ->will($this->returnCallback(function () use (&$settingsFormReturns) {
                if (count($settingsFormReturns) <= 0) {
                    throw new Exception('error');
                }

                return array_shift($settingsFormReturns);
            }));

        $formHelper->expects($this->exactly(4))
            ->method('getParameterText')
            ->will($this->returnCallback(
                function (ConfigParameter $configParameter, $description, $postType) {
                    $text = $configParameter->getId() . ucfirst($postType);
                    $text .= ($description === true) ? 'Desc' : '';
                    return $text;
                }
            ));

        $configForm = $this->createMock(Form::class);
        $formHelper->expects($this->once())
            ->method('getSettingsFormByConfig')
            ->with($config)
            ->will($this->returnValue($configForm));

        $throwException = false;

        $formHelper->expects($this->exactly(2))
            ->method('createMultipleFromElement')
            ->withConsecutive(
                ['custom_url', TXT_UAM_REDIRECT_TO_URL],
                ['custom_url', TXT_UAM_REDIRECT_TO_URL]
            )
            ->will($this->returnCallback(function () use (&$throwException) {
                if ($throwException === true) {
                    throw new Exception('error');
                }

                return $this->createMock(MultipleFormElementValue::class);
            }));

        $fileHandler = $this->getFileHandler();

        $fileHandler->expects($this->exactly(5))
            ->method('removeXSendFileTestFile');

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory([
            'fileProtectionFileName' => new File('fileProtectionFileContent')
        ]));
        $testFileWithDir = 'vfs://testDir/fileProtectionFileName';

        $fileHandler->expects($this->exactly(5))
            ->method('getFileProtectionFileName')
            ->will($this->returnValue($testFileWithDir));

        if (defined('_SESSION')) {
            unset($_SESSION[BackendController::UAM_ERRORS]);
        }

        $settingController = new SettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $mainConfig,
            $cache,
            $fileHandler,
            $formFactory,
            $formHelper
        );

        self::assertEquals(
            [
                'default' => $defaultPostTypeForm,
                'post' => $postForm,
                'page' => $pageForm
            ],
            $settingController->getCurrentGroupForms()
        );

        $_GET['tab_group'] = SettingsController::GROUP_TAXONOMIES;
        self::assertEquals(
            [
                'default' => $defaultTaxonomyForm,
                'category' => $categoryForm
            ],
            $settingController->getCurrentGroupForms()
        );

        VCR::turnOn();
        VCR::insertCassette('testXSendFileSuccess');
        $_GET['tab_group'] = SettingsController::GROUP_FILES;
        self::assertEquals(['file' => $fileFrom], $settingController->getCurrentGroupForms());
        VCR::eject();

        VCR::insertCassette('testXSendFileFailure');
        self::assertEquals(['file' => $fileFrom], $settingController->getCurrentGroupForms());
        self::assertEquals(['file' => $fileFrom], $settingController->getCurrentGroupForms());
        self::assertEquals(['file' => $fileFrom], $settingController->getCurrentGroupForms());

        unset($configValues['lock_file_types']);
        self::assertEquals(['file' => $fileFrom], $settingController->getCurrentGroupForms());
        VCR::eject();
        VCR::turnOff();

        $_GET['tab_group'] = SettingsController::GROUP_AUTHOR;
        self::assertEquals(['author' => $authorForm], $settingController->getCurrentGroupForms());

        $_GET['tab_group'] = SettingsController::GROUP_CACHE;
        self::assertEquals(
            ['none' => null, 'cacheProviderId' => $configForm],
            $settingController->getCurrentGroupForms()
        );

        $_GET['tab_group'] = SettingsController::GROUP_OTHER;
        self::assertEquals(['other' => $otherForm], $settingController->getCurrentGroupForms());

        $throwException = true;
        self::assertEquals([], $settingController->getCurrentGroupForms());
        self::assertEquals(
            ['The following error occurred: error|user-access-manager'],
            $_SESSION[BackendController::UAM_ERRORS]
        );
    }

    /**
     * @group  unit
     * @covers ::updateSettingsAction()
     * @covers ::updateFileProtectionFile()
     */
    public function testUpdateSettingsAction()
    {
        $php = $this->getPhp();
        $php->expects($this->once())
            ->method('filePutContents')
            ->with('fileProtectionFileName', 'newFileContent');

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

        $mainConfig->expects($this->exactly(4))
            ->method('useCustomFileHandlingFile')
            ->will($this->onConsecutiveCalls(true, true, false, false));

        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('setConfigParameters')
            ->with(['b' => '&lt;b&gt;b&lt;/b&gt;', 'i' => '&lt;i&gt;i&lt;/i&gt;']);

        $cacheProvider = $this->createMock(CacheProviderInterface::class);
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

        $fileHandler->expects($this->exactly(2))
            ->method('createFileProtection');

        $fileHandler->expects($this->once())
            ->method('deleteFileProtection');

        $fileHandler->expects($this->once())
            ->method('getFileProtectionFileName')
            ->will($this->returnValue('fileProtectionFileName'));

        $_POST['config_parameters'] = [
            'b' => '<b>b</b>',
            'i' => '<i>i</i>',
            'custom_file_handling_file' => 'newFileContent'
        ];

        $settingController = new SettingsController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $mainConfig,
            $cache,
            $fileHandler,
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        $settingController->updateSettingsAction();
        $settingController->updateSettingsAction();

        unset($_POST['config_parameters']['custom_file_handling_file']);
        $settingController->updateSettingsAction();

        $_GET['tab_group'] = SettingsController::GROUP_CACHE;
        $_GET['tab_group_section'] = MainConfig::CACHE_PROVIDER_NONE;
        $settingController->updateSettingsAction();

        $_GET['tab_group'] = SettingsController::GROUP_CACHE;
        $_GET['tab_group_section'] = 'cacheProviderId';
        $settingController->updateSettingsAction();

        self::assertEquals(TXT_UAM_UPDATE_SETTINGS, $settingController->getUpdateMessage());
    }

    /**
     * @group  unit
     * @covers ::isPostTypeGroup()
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

        $settingController = new SettingsController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getCache(),
            $this->getFileHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertTrue($settingController->isPostTypeGroup(ObjectHandler::ATTACHMENT_OBJECT_TYPE));
        self::assertTrue($settingController->isPostTypeGroup(ObjectHandler::POST_OBJECT_TYPE));
        self::assertTrue($settingController->isPostTypeGroup(ObjectHandler::PAGE_OBJECT_TYPE));
        self::assertFalse($settingController->isPostTypeGroup('something'));
    }
}
