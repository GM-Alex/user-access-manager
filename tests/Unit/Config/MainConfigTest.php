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

use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Config\MainConfig;
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
    private $defaultValues;

    /**
     * Create default mocked objects.
     */
    public function setUp()
    {
        $this->defaultValues = [
            'hide_default' => 'bool|hide_default|false',
            'hide_default_title' => 'bool|hide_default_title|false',
            'default_title' => 'string|default_title|No rights!|user-access-manager',
            'default_content' => 'string|default_content|'
                .'Sorry you have no rights to view this entry!|user-access-manager',
            'hide_default_comment' => 'bool|hide_default_comment|false',
            'default_comment_content' => 'string|default_comment_content|'
                .'Sorry no rights to view comments!|user-access-manager',
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
                .'Sorry no rights to view comments!|user-access-manager',
            'post_comments_locked' => 'bool|post_comments_locked|false',
            'page_use_default' => 'bool|page_use_default|false',
            'hide_page' => 'bool|hide_page|false',
            'hide_page_title' => 'bool|hide_page_title|false',
            'page_title' => 'string|page_title|No rights!|user-access-manager',
            'page_content' => 'string|page_content|Sorry you have no rights to view this entry!|user-access-manager',
            'hide_page_comment' => 'bool|hide_page_comment|false',
            'page_comment_content' => 'string|page_comment_content|'
                .'Sorry no rights to view comments!|user-access-manager',
            'page_comments_locked' => 'bool|page_comments_locked|false',
            'redirect' => 'selection|redirect|false|false|custom_page|custom_url',
            'redirect_custom_page' => 'string|redirect_custom_page|',
            'redirect_custom_url' => 'string|redirect_custom_url|',
            'lock_recursive' => 'bool|lock_recursive|true',
            'authors_has_access_to_own' => 'bool|authors_has_access_to_own|true',
            'authors_can_add_posts_to_groups' => 'bool|authors_can_add_posts_to_groups|false',
            'lock_file' => 'bool|lock_file|false',
            'file_pass_type' => 'selection|file_pass_type|random|random|user',
            'download_type' => 'selection|download_type|fopen|fopen|normal',
            'lock_file_types' => 'selection|lock_file_types|all|all|selected|not_selected',
            'locked_file_types' => 'string|locked_file_types|zip,rar,tar,gz',
            'not_locked_file_types' => 'string|not_locked_file_types|gif,jpg,jpeg,png',
            'blog_admin_hint' => 'bool|blog_admin_hint|true',
            'blog_admin_hint_text' => 'string|blog_admin_hint_text|[L]',
            'category_use_default' => 'bool|category_use_default|false',
            'hide_empty_category' => 'bool|hide_empty_category|true',
            'protect_feed' => 'bool|protect_feed|true',
            'full_access_role' => 'selection|full_access_role|administrator|'
                .'administrator|editor|author|contributor|subscriber',
            'active_cache_provider' => 'selection|active_cache_provider|none|none',
            'show_assigned_groups' => 'bool|show_assigned_groups|true'
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Cache\Cache
     */
    protected function getCache()
    {
        $cache = parent::getCache();

        $cache->expects($this->any())
            ->method('getRegisteredCacheProviders')
            ->will($this->returnValue([]));

        return $cache;
    }

    /**
     * @param int $callExpectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Object\ObjectHandler
     */
    protected function getDefaultObjectHandler($callExpectation)
    {
        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly($callExpectation))
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'page' => 'page', 'attachment' => 'attachment']));

        $objectHandler->expects($this->exactly($callExpectation))
            ->method('getTaxonomies')
            ->will($this->returnValue(['category' => 'category']));

        return $objectHandler;
    }

    /**
     * @param callable $closure
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigParameterFactory
     */
    protected function getFactory($closure = null)
    {
        if ($closure === null) {
            $closure = function ($id) {
                $stub = $this->getMockForAbstractClass(
                    ConfigParameter::class,
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
                    ->with($id.'|value')
                    ->will($this->returnValue(null));

                $stub->expects(self::any())
                    ->method('getValue')
                    ->will($this->returnValue($id));

                return $stub;
            };
        }


        $configParameterFactory = $this->getConfigParameterFactory();
        $configParameterFactory->expects($this->any())
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback($closure));

        $configParameterFactory->expects($this->any())
            ->method('createStringConfigParameter')
            ->will($this->returnCallback($closure));

        $configParameterFactory->expects($this->any())
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback($closure));

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
     *
     * @return MainConfig
     */
    public function testGetDefaultConfigParameters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->with(MainConfig::MAIN_CONFIG_KEY)
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(2);

        $configParameterFactory = $this->getConfigParameterFactory();
        $configParameterFactory->expects($this->exactly(25))
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value) {
                    $return = 'bool|'.$id.'|';
                    $return .= ($value === true) ? 'true' : 'false';
                    return $return;
                }
            ));

        $configParameterFactory->expects($this->exactly(14))
            ->method('createStringConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value) {
                    return 'string|'.$id.'|'.$value;
                }
            ));

        $configParameterFactory->expects($this->exactly(6))
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value, $selections) {
                    return 'selection|'.$id.'|'.$value.'|'.implode('|', $selections);
                }
            ));

        $config = new MainConfig(
            $wordpress,
            $objectHandler,
            $this->getCache(),
            $configParameterFactory
        );

        self::assertEquals($this->defaultValues, $config->getConfigParameters());

        $optionKeys = array_keys($this->defaultValues);
        $testValues = array_map(function ($element) {
            return $element.'|value';
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

        $parameters = $config->getConfigParameters();

        foreach ($parameters as $parameter) {
            self::assertEquals($parameter->getId(), $parameter->getValue());
        }

        return $config;
    }

    /**
     * @group  unit
     * @covers ::getObjectParameter()
     */
    public function testGetObjectParameter()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory(function ($id) {
            $stub = $this->getMockForAbstractClass(
                ConfigParameter::class,
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
        self::assertEquals('hide_post', self::callMethod($config, 'hideObject', ['post', 'hide_%s']));
        self::assertEquals('hide_default', self::callMethod($config, 'hideObject', ['undefined', 'hide_%s']));

        self::assertEquals('hide_post', $config->hidePostType('post'));
        self::assertEquals('hide_default', $config->hidePostType('undefined'));

        self::assertEquals('hide_post_title', $config->hidePostTypeTitle('post'));
        self::assertEquals('hide_default_title', $config->hidePostTypeTitle('undefined'));

        self::assertEquals('hide_post_comment', $config->hidePostTypeComments('post'));
        self::assertEquals('hide_default_comment', $config->hidePostTypeComments('undefined'));

        self::assertEquals('post_comments_locked', $config->lockPostTypeComments('post'));
        self::assertEquals('default_comments_locked', $config->lockPostTypeComments('undefined'));

        self::assertEquals('hide_empty_category', $config->hideEmptyTaxonomy('category'));
        self::assertEquals('hide_empty_default', $config->hideEmptyTaxonomy('undefined'));

        self::setValue($config, 'configParameters', []);
        self::assertFalse($config->hideEmptyTaxonomy('undefined'));
    }

    /**
     * @group  unit
     * @covers ::getPostTypeTitle()
     * @covers ::getPostTypeContent()
     * @covers ::getPostTypeCommentContent()
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
     * @covers ::getFilePassType
     * @covers ::getLockFileTypes
     * @covers ::getDownloadType
     * @covers ::getLockedFileTypes
     * @covers ::getNotLockedFileTypes
     * @covers ::blogAdminHint
     * @covers ::getBlogAdminHintText
     * @covers ::showAssignedGroups
     * @covers ::protectFeed
     * @covers ::showPostContentBeforeMore
     * @covers ::getFullAccessRole
     * @covers ::getActiveCacheProvider
     */
    public function testSimpleGetters()
    {
        $methods = [
            'getRedirect' => 'redirect',
            'getRedirectCustomPage' => 'redirect_custom_page',
            'getRedirectCustomUrl' => 'redirect_custom_url',
            'lockRecursive' => 'lock_recursive',
            'authorsHasAccessToOwn' => 'authors_has_access_to_own',
            'authorsCanAddPostsToGroups' => 'authors_can_add_posts_to_groups',
            'lockFile' => 'lock_file',
            'getFilePassType' => 'file_pass_type',
            'getLockFileTypes' => 'lock_file_types',
            'getDownloadType' => 'download_type',
            'getLockedFileTypes' => 'locked_file_types',
            'getNotLockedFileTypes' => 'not_locked_file_types',
            'blogAdminHint' => 'blog_admin_hint',
            'getBlogAdminHintText' => 'blog_admin_hint_text',
            'showAssignedGroups' => 'show_assigned_groups',
            'protectFeed' => 'protect_feed',
            'showPostContentBeforeMore' => 'show_post_content_before_more',
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
