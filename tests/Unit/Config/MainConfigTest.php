<?php
/**
 * MainConfigTest.php
 *
 * The MainConfigTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Config;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\BooleanConfigParameter;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\SelectionConfigParameter;
use UserAccessManager\Config\StringConfigParameter;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class ConfigTest
 *
 * @package UserAccessManager\Tests\Unit\Config
 * @coversDefaultClass \UserAccessManager\Config\MainConfig
 */
class MainConfigTest extends UserAccessManagerTestCase
{
    /**
     * @var array
     */
    private array $defaultValues;

    /**
     * Create default mocked objects.
     */
    protected function setUp(): void
    {
        $this->defaultValues = [
            'hide_default' => 'bool|hide_default|false',
            'hide_default_title' => 'bool|hide_default_title|false',
            'default_title' => 'string|default_title|No rights!|user-access-manager',
            'show_default_content_before_more' => 'bool|show_default_content_before_more|false',
            'default_content' => 'string|default_content|'
                . 'Sorry you have no rights to view this entry!|user-access-manager',
            'hide_default_comment' => 'bool|hide_default_comment|false',
            'default_comment_content' => 'string|default_comment_content|'
                . 'Sorry no rights to view comments!|user-access-manager',
            'default_comments_locked' => 'bool|default_comments_locked|false',
            'hide_empty_default' => 'bool|hide_empty_default|true',
            'post_use_default' => 'bool|post_use_default|false',
            'hide_post' => 'bool|hide_post|false',
            'hide_post_title' => 'bool|hide_post_title|false',
            'post_title' => 'string|post_title|No rights!|user-access-manager',
            'show_post_content_before_more' => 'bool|show_post_content_before_more|false',
            'post_content' => 'string|post_content|Sorry you have no rights to view this entry!|user-access-manager',
            'hide_post_comment' => 'bool|hide_post_comment|false',
            'post_comment_content' => 'string|post_comment_content|'
                . 'Sorry no rights to view comments!|user-access-manager',
            'post_comments_locked' => 'bool|post_comments_locked|false',
            'page_use_default' => 'bool|page_use_default|false',
            'hide_page' => 'bool|hide_page|false',
            'hide_page_title' => 'bool|hide_page_title|false',
            'page_title' => 'string|page_title|No rights!|user-access-manager',
            'show_page_content_before_more' => 'bool|show_page_content_before_more|false',
            'page_content' => 'string|page_content|Sorry you have no rights to view this entry!|user-access-manager',
            'hide_page_comment' => 'bool|hide_page_comment|false',
            'page_comment_content' => 'string|page_comment_content|'
                . 'Sorry no rights to view comments!|user-access-manager',
            'page_comments_locked' => 'bool|page_comments_locked|false',
            'redirect' => 'selection|redirect|false|false|blog|login|custom_page|custom_url',
            'redirect_custom_page' => 'string|redirect_custom_page|',
            'redirect_custom_url' => 'string|redirect_custom_url|',
            'lock_recursive' => 'bool|lock_recursive|true',
            'authors_has_access_to_own' => 'bool|authors_has_access_to_own|true',
            'authors_can_add_posts_to_groups' => 'bool|authors_can_add_posts_to_groups|false',
            'lock_file' => 'bool|lock_file|false',
            'download_type' => 'selection|download_type|fopen|xsendfile|fopen|normal',
            'inline_files' => 'string|inline_files|pdf',
            'no_access_image_type' => 'selection|no_access_image_type|default|default|custom',
            'custom_no_access_image' => 'string|custom_no_access_image|',
            'use_custom_file_handling_file' => 'bool|use_custom_file_handling_file|false',
            'locked_directory_type' => 'selection|locked_directory_type|wordpress|wordpress|all|custom',
            'custom_locked_directories' => 'string|custom_locked_directories|',
            'file_pass_type' => 'selection|file_pass_type|random|random|user',
            'lock_file_types' => 'selection|lock_file_types|all|all|selected|not_selected',
            'locked_file_types' => 'string|locked_file_types|zip,rar,tar,gz',
            'not_locked_file_types' => 'string|not_locked_file_types|gif,jpg,jpeg,png',
            'blog_admin_hint' => 'bool|blog_admin_hint|true',
            'blog_admin_hint_text' => 'string|blog_admin_hint_text|[L]',
            'category_use_default' => 'bool|category_use_default|false',
            'hide_empty_category' => 'bool|hide_empty_category|true',
            'protect_feed' => 'bool|protect_feed|true',
            'full_access_role' => 'selection|full_access_role|administrator|'
                . 'administrator|editor|author|contributor|subscriber',
            'active_cache_provider' => 'selection|active_cache_provider|none|none|one',
            'show_assigned_groups' => 'bool|show_assigned_groups|true',
            'hide_edit_link_on_no_access' => 'bool|hide_edit_link_on_no_access|true',
            'extra_ip_header' => 'string|extra_ip_header|HTTP_X_REAL_IP'
        ];
    }

    /**
     * @return MockObject|Cache
     */
    protected function getCache(): Cache|MockObject
    {
        $cache = parent::getCache();

        $cache->expects($this->any())
            ->method('getRegisteredCacheProviders')
            ->will($this->returnValue(['one' => 'cacheOne']));

        return $cache;
    }

    /**
     * @param int $callExpectation
     * @return MockObject|ObjectHandler
     */
    protected function getDefaultObjectHandler(int $callExpectation): ObjectHandler|MockObject
    {
        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly($callExpectation))
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'attachment' => 'attachment', 'page' => 'page']));

        $objectHandler->expects($this->exactly($callExpectation))
            ->method('getTaxonomies')
            ->will($this->returnValue(['category' => 'category']));

        return $objectHandler;
    }

    /**
     * @param callable|null $closure
     * @return MockObject|ConfigParameterFactory
     */
    protected function getFactory(callable $closure = null): ConfigParameterFactory|MockObject
    {
        if ($closure === null) {
            $closure = function ($type) {
                return function ($id) use ($type) {
                    $stub = $this->createMock(
                        $type
                    );

                    $stub->expects(self::any())
                        ->method('getId')
                        ->will($this->returnValue($id));

                    $stub->expects(self::any())
                        ->method('setValue')
                        ->with($id . '|value')
                        ->will($this->returnValue(null));

                    $stub->expects(self::any())
                        ->method('getValue')
                        ->will($this->returnValue($type === BooleanConfigParameter::class ? false : $id));

                    return $stub;
                };
            };
        }


        $configParameterFactory = $this->getConfigParameterFactory();
        $configParameterFactory->expects($this->any())
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback($closure(BooleanConfigParameter::class)));

        $configParameterFactory->expects($this->any())
            ->method('createStringConfigParameter')
            ->will($this->returnCallback($closure(StringConfigParameter::class)));

        $configParameterFactory->expects($this->any())
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback($closure(SelectionConfigParameter::class)));

        return $configParameterFactory;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $config = new MainConfig(
            $this->getWordpress(),
            $this->getObjectHandler(),
            $this->getCache(),
            $this->getConfigParameterFactory()
        );

        self::assertInstanceOf(MainConfig::class, $config);
    }

    /**
     * @group  unit
     * @covers ::getDefaultConfigParameters()
     * @covers ::addDefaultGeneralConfigParameters()
     * @covers ::addDefaultPostConfigParameters()
     * @covers ::addDefaultTaxonomyConfigParameters()
     * @covers ::addDefaultFileConfigParameters()
     * @return MainConfig
     */
    public function testGetDefaultConfigParameters(): MainConfig
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->with(MainConfig::MAIN_CONFIG_KEY)
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(2);

        $configParameterFactory = $this->getConfigParameterFactory();
        $configParameterFactory->expects($this->exactly(29))
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value) {
                    $configParameter = $this->createMock(BooleanConfigParameter::class);

                    $configParameter->expects(self::any())
                        ->method('getId')
                        ->will($this->returnValue($id));

                    $configParameter->expects(self::any())
                        ->method('setValue')
                        ->with($value)
                        ->will($this->returnValue(null));

                    $configParameter->expects(self::any())
                        ->method('getValue')
                        ->will($this->returnValue($value));

                    return $configParameter;
                }
            ));

        $configParameterFactory->expects($this->exactly(18))
            ->method('createStringConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value) {
                    $configParameter = $this->createMock(StringConfigParameter::class);

                    $configParameter->expects(self::any())
                        ->method('getId')
                        ->will($this->returnValue($id));

                    $configParameter->expects(self::any())
                        ->method('setValue')
                        ->with($value)
                        ->will($this->returnValue(null));

                    $configParameter->expects(self::any())
                        ->method('getValue')
                        ->will($this->returnValue($value));

                    return $configParameter;
                }
            ));

        $configParameterFactory->expects($this->exactly(8))
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value, $selections) {
                    $configParameter = $this->createMock(SelectionConfigParameter::class);

                    $configParameter->expects(self::any())
                        ->method('getId')
                        ->will($this->returnValue($id));

                    $configParameter->expects(self::any())
                        ->method('setValue')
                        ->with($value)
                        ->will($this->returnValue(null));

                    $configParameter->expects(self::any())
                        ->method('getValue')
                        ->will($this->returnValue($value));

                    $configParameter->expects(self::any())
                        ->method('getSelections')
                        ->will($this->returnValue($selections));

                    return $configParameter;
                }
            ));

        $config = new MainConfig(
            $wordpress,
            $objectHandler,
            $this->getCache(),
            $configParameterFactory
        );

        foreach ($config->getConfigParameters() as $configParameterKey => $configParameter) {
            $expectedValues = explode('|', $this->defaultValues[$configParameterKey]);
            [$type, $id, $value] = $expectedValues;

            if ($type === 'bool') {
                $value = $value === 'true';
                self::assertInstanceOf(BooleanConfigParameter::class, $configParameter);
            } elseif ($type === 'string') {
                if (count($expectedValues) > 3) {
                    $value .= '|' . implode('|', array_slice($expectedValues, 3));
                }
                self::assertInstanceOf(StringConfigParameter::class, $configParameter);
            } elseif ($type === 'selection') {
                self::assertInstanceOf(SelectionConfigParameter::class, $configParameter);
                self::assertEquals(array_slice($expectedValues, 3), $configParameter->getSelections());
            }

            self::assertEquals($id, $configParameter->getId());
            self::assertEquals($value, $configParameter->getValue());
        }

        $optionKeys = array_keys($this->defaultValues);
        $testValues = array_map(function ($element) {
            return $element . '|value';
        }, $optionKeys);

        $options = array_combine($optionKeys, $testValues);
        $options['invalid'] = 'invalid';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->with(MainConfig::MAIN_CONFIG_KEY)
            ->will($this->returnValue($options));

        $configParameterFactory = $this->getFactory();

        $config = new MainConfig(
            $wordpress,
            $objectHandler,
            $this->getCache(),
            $configParameterFactory
        );

        $config->getConfigParameters();

        return $config;
    }

    /**
     * @group  unit
     * @covers ::getObjectParameter()
     * @throws ReflectionException
     */
    public function testGetObjectParameter()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory(function ($type) {
            return function ($id) use ($type) {
                $stub = $this->getMockForAbstractClass(
                    $type,
                    [],
                    '',
                    false,
                    true,
                    true,
                    [
                        'getId',
                        'setValue',
                        'getValue'
                    ]
                );

                $stub->expects(self::any())
                    ->method('getId')
                    ->will($this->returnValue($id));

                $stub->expects(self::any())
                    ->method('setValue')
                    ->will($this->returnValue(null));

                $stub->expects(self::any())
                    ->method('getValue')
                    ->will($this->returnCallback(function () use ($id) {
                        return ($id === 'post_use_default') ? true : $id;
                    }));

                return $stub;
            };
        });

        $config = new MainConfig(
            $wordpress,
            $objectHandler,
            $this->getCache(),
            $configParameterFactory
        );

        self::assertEquals(null, self::callMethod($config, 'getObjectParameter', ['post', 'something_%s']));

        $parameter = self::callMethod($config, 'getObjectParameter', ['post', 'hide_%s']);
        self::assertEquals('hide_default', $parameter->getValue());

        $parameter = self::callMethod($config, 'getObjectParameter', ['page', 'hide_%s']);
        self::assertEquals('hide_page', $parameter->getValue());
    }

    /**
     * @group  unit
     * @covers ::hideObject()
     * @covers ::hidePostType()
     * @covers ::hidePostTypeTitle()
     * @covers ::hidePostTypeComments()
     * @covers ::lockPostTypeComments()
     * @covers ::hideEmptyTaxonomy()
     * @throws ReflectionException
     */
    public function testHideObject()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory();

        $config = new MainConfig(
            $wordpress,
            $objectHandler,
            $this->getCache(),
            $configParameterFactory
        );

        self::assertTrue(self::callMethod($config, 'hideObject', ['post', 'something_%s']));
        self::assertFalse(self::callMethod($config, 'hideObject', ['post', 'hide_%s']));
        self::assertFalse(self::callMethod($config, 'hideObject', ['undefined', 'hide_%s']));

        self::assertFalse($config->hidePostType('post'));
        self::assertFalse($config->hidePostType('undefined'));

        self::assertFalse($config->hidePostTypeTitle('post'));
        self::assertFalse($config->hidePostTypeTitle('undefined'));

        self::assertFalse($config->hidePostTypeComments('post'));
        self::assertFalse($config->hidePostTypeComments('undefined'));

        self::assertFalse($config->lockPostTypeComments('post'));
        self::assertFalse($config->lockPostTypeComments('undefined'));

        self::assertFalse($config->hideEmptyTaxonomy('category'));
        self::assertFalse($config->hideEmptyTaxonomy('undefined'));

        self::setValue($config, 'configParameters', []);
        self::assertFalse($config->hideEmptyTaxonomy('undefined'));
    }

    /**
     * @group  unit
     * @covers ::getPostTypeTitle()
     * @covers ::getPostTypeContent()
     * @covers ::getPostTypeCommentContent()
     * @covers ::showPostTypeContentBeforeMore()
     * @covers ::getObjectContent()
     */
    public function testObjectGetter()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory();

        $config = new MainConfig(
            $wordpress,
            $objectHandler,
            $this->getCache(),
            $configParameterFactory
        );

        self::assertEquals('post_title', $config->getPostTypeTitle('post'));
        self::assertEquals('post_content', $config->getPostTypeContent('post'));
        self::assertEquals('post_comment_content', $config->getPostTypeCommentContent('post'));
        self::assertFalse($config->showPostTypeContentBeforeMore('post'));
    }

    /**
     * @group  unit
     * @covers ::getRedirect
     * @covers ::getRedirectCustomPage
     * @covers ::getRedirectCustomUrl
     * @covers ::lockRecursive
     * @covers ::authorsHasAccessToOwn
     * @covers ::authorsCanAddPostsToGroups
     * @covers ::lockFile
     * @covers ::getDownloadType
     * @covers ::getInlineFiles
     * @covers ::getNoAccessImageType
     * @covers ::getCustomNoAccessImage
     * @covers ::useCustomFileHandlingFile
     * @covers ::getLockedDirectoryType
     * @covers ::getCustomLockedDirectories
     * @covers ::getFilePassType
     * @covers ::getLockedFileType
     * @covers ::getLockedFiles
     * @covers ::getNotLockedFiles
     * @covers ::blogAdminHint
     * @covers ::getBlogAdminHintText
     * @covers ::showAssignedGroups
     * @covers ::hideEditLinkOnNoAccess
     * @covers ::protectFeed
     * @covers ::getFullAccessRole
     * @covers ::getActiveCacheProvider
     */
    public function testSimpleGetters()
    {
        $methods = [
            'getRedirect' => 'redirect',
            'getRedirectCustomPage' => 'redirect_custom_page',
            'getRedirectCustomUrl' => 'redirect_custom_url',
            'lockRecursive' => false,
            'authorsHasAccessToOwn' => false,
            'authorsCanAddPostsToGroups' => false,
            'lockFile' => false,
            'getDownloadType' => 'download_type',
            'getInlineFiles' => 'inline_files',
            'getNoAccessImageType' => 'no_access_image_type',
            'getCustomNoAccessImage' => 'custom_no_access_image',
            'useCustomFileHandlingFile' => false,
            'getLockedDirectoryType' => 'locked_directory_type',
            'getCustomLockedDirectories' => 'custom_locked_directories',
            'getFilePassType' => 'file_pass_type',
            'getLockedFileType' => 'lock_file_types',
            'getLockedFiles' => 'locked_file_types',
            'getNotLockedFiles' => 'not_locked_file_types',
            'blogAdminHint' => false,
            'getBlogAdminHintText' => 'blog_admin_hint_text',
            'showAssignedGroups' => false,
            'hideEditLinkOnNoAccess' => false,
            'protectFeed' => false,
            'getFullAccessRole' => 'full_access_role',
            'getActiveCacheProvider' => 'active_cache_provider'
        ];

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory();

        $config = new MainConfig(
            $wordpress,
            $objectHandler,
            $this->getCache(),
            $configParameterFactory
        );

        foreach ($methods as $method => $expected) {
            self::assertEquals($expected, $config->{$method}());
        }
    }
}
