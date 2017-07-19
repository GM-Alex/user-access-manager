<?php
/**
 * ConfigTest.php
 *
 * The ConfigTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class ConfigTest
 *
 * @package UserAccessManager\Config
 */
class ConfigTest extends UserAccessManagerTestCase
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
            'hide_post' => 'bool|hide_post|false',
            'hide_post_title' => 'bool|hide_post_title|false',
            'post_title' => 'string|post_title|No rights!|user-access-manager',
            'show_post_content_before_more' => 'bool|show_post_content_before_more|false',
            'post_content' => 'string|post_content|Sorry you have no rights to view this entry!|user-access-manager',
            'hide_post_comment' => 'bool|hide_post_comment|false',
            'post_comment_content' => 'string|post_comment_content|'
                .'Sorry no rights to view comments!|user-access-manager',
            'post_comments_locked' => 'bool|post_comments_locked|false',
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
            'hide_empty_category' => 'bool|hide_empty_category|true',
            'protect_feed' => 'bool|protect_feed|true',
            'show_assigned_groups' => 'bool|show_assigned_groups|true',
            'full_access_role' => 'selection|full_access_role|administrator|'
                .'administrator|editor|author|contributor|subscriber',
        ];
    }

    /**
     * @param int $callExpectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\ObjectHandler\ObjectHandler
     */
    private function getDefaultObjectHandler($callExpectation)
    {
        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly($callExpectation))
            ->method('getPostTypes')
            ->will($this->returnValue(['post', 'page', 'attachment']));

        $objectHandler->expects($this->exactly($callExpectation))
            ->method('getTaxonomies')
            ->will($this->returnValue(['category']));

        return $objectHandler;
    }

    /**
     * @param callable $closure
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigParameterFactory
     */
    private function getFactory($closure = null)
    {
        if ($closure === null) {
            $closure = function ($id) {
                $stub = self::getMockForAbstractClass(
                    '\UserAccessManager\Config\ConfigParameter',
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
                    ->with($this->equalTo($id.'|value'))
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
     * @covers \UserAccessManager\Config\Config::__construct()
     */
    public function testCanCreateInstance()
    {
        $config = new Config(
            $this->getWordpress(),
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertInstanceOf('\UserAccessManager\Config\Config', $config);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getWpOption()
     */
    public function testGetWpOption()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('optionValueOne', 'optionValueTwo'));

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        $optionOne = $config->getWpOption('optionOne');
        $optionOneAgain = $config->getWpOption('optionOne');

        self::assertEquals('optionValueOne', $optionOne);
        self::assertEquals('optionValueOne', $optionOneAgain);

        $optionTwo = $config->getWpOption('optionTwo');
        self::assertEquals('optionValueTwo', $optionTwo);

        $optionTwo = $config->getWpOption('optionNotExisting');
        self::assertEquals(null, $optionTwo);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getConfigParameters()
     *
     * @return Config
     */
    public function testGetConfigParameters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->with(Config::ADMIN_OPTIONS_NAME)
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(2);

        $configParameterFactory = $this->getConfigParameterFactory();
        $configParameterFactory->expects($this->exactly(16))
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value) {
                    $return = 'bool|'.$id.'|';
                    $return .= ($value === true) ? 'true' : 'false';
                    return $return;
                }
            ));

        $configParameterFactory->expects($this->exactly(11))
            ->method('createStringConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value) {
                    return 'string|'.$id.'|'.$value;
                }
            ));

        $configParameterFactory->expects($this->exactly(5))
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback(
                function ($id, $value, $selections) {
                    return 'selection|'.$id.'|'.$value.'|'.implode('|', $selections);
                }
            ));

        $config = new Config(
            $wordpress,
            $objectHandler,
            $configParameterFactory,
            'baseFile'
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
            ->with(Config::ADMIN_OPTIONS_NAME)
            ->will($this->returnValue($options));

        $configParameterFactory = $this->getFactory();

        $config = new Config(
            $wordpress,
            $objectHandler,
            $configParameterFactory,
            'baseFile'
        );

        $parameters = $config->getConfigParameters();

        foreach ($parameters as $parameter) {
            self::assertEquals($parameter->getId(), $parameter->getValue());
        }

        return $config;
    }

    /**
     * @group   unit
     * @depends testGetConfigParameters
     * @covers  \UserAccessManager\Config\Config::flushConfigParameters()
     *
     * @param Config $config
     */
    public function testFlushConfigParameters(Config $config)
    {
        self::assertAttributeNotEmpty('configParameters', $config);
        $config->flushConfigParameters();
        self::assertAttributeEquals(null, 'configParameters', $config);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::setConfigParameters()
     */
    public function testSetConfigParameters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $wordpress->expects($this->once())
            ->method('updateOption')
            ->with(Config::ADMIN_OPTIONS_NAME);

        $closure = function ($id) {
            $stub = self::getMockForAbstractClass(
                '\UserAccessManager\Config\ConfigParameter',
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
                ->with($this->logicalOr('blog_admin_hint|value', 'lock_file|value'))
                ->will($this->returnValue(null));

            $stub->expects(self::any())
                ->method('getValue')
                ->will($this->returnValue($id));

            return $stub;
        };

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory($closure);

        $config = new Config(
            $wordpress,
            $objectHandler,
            $configParameterFactory,
            'baseFile'
        );

        $config->setConfigParameters(
            [
                'blog_admin_hint' => 'blog_admin_hint|value',
                'lock_file' => 'lock_file|value',
                'invalid' => 'invalid'
            ]
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getParameterValue()
     */
    public function testGetParameterValue()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory();

        $config = new Config(
            $wordpress,
            $objectHandler,
            $configParameterFactory,
            'baseFile'
        );

        $return = self::callMethod($config, 'getParameterValue', ['lock_file']);
        self::assertEquals('lock_file', $return);

        self::expectException('\Exception');
        self::callMethod($config, 'getParameterValue', ['undefined']);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\Config::atAdminPanel()
     */
    public function testAtAdminPanel()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(true, false));

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertTrue($config->atAdminPanel());
        self::assertFalse($config->atAdminPanel());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::isPermalinksActive()
     */
    public function testIsPermalinksActive()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('aaa', ''));

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertTrue($config->isPermalinksActive());
        self::setValue($config, 'wpOptions', []);
        self::assertFalse($config->isPermalinksActive());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getUploadDirectory()
     */
    public function testGetUploadDirectory()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getUploadDir')
            ->will(
                $this->onConsecutiveCalls(
                    [
                        'error' => 'error',
                        'basedir' => 'baseDir'
                    ],
                    [
                        'error' => null,
                        'basedir' => 'baseDir'
                    ]
                )
            );

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertEquals(null, $config->getUploadDirectory());
        self::assertEquals('baseDir/', $config->getUploadDirectory());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getMimeTypes()
     */
    public function testGetMimeTypes()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getAllowedMimeTypes')
            ->will(
                $this->onConsecutiveCalls(
                    ['a|b' => 'firstType', 'c' => 'secondType'],
                    ['c|b' => 'firstType', 'a' => 'secondType']
                )
            );

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $config->getMimeTypes()
        );
        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $config->getMimeTypes()
        );

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertEquals(
            ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType'],
            $config->getMimeTypes()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getUrlPath()
     */
    public function testGetUrlPath()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('pluginsUrl')
            ->will($this->returnValue('pluginsUrl'));

        $configParameterFactory = $this->getFactory();

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $configParameterFactory,
            'baseFile'
        );

        self::assertEquals(
            'pluginsUrl/',
            $config->getUrlPath()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getRealPath()
     */
    public function testGetRealPath()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getPluginDir')
            ->will($this->returnValue('pluginDir'));
        $wordpress->expects($this->once())
            ->method('pluginBasename')
            ->will($this->returnValue('pluginBasename'));

        $configParameterFactory = $this->getFactory();

        $config = new Config(
            $wordpress,
            $this->getObjectHandler(),
            $configParameterFactory,
            'baseFile'
        );
        self::assertEquals(
            'pluginDir'.DIRECTORY_SEPARATOR.'pluginBasename'.DIRECTORY_SEPARATOR,
            $config->getRealPath()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::hideObject()
     * @covers \UserAccessManager\Config\Config::hidePostType()
     * @covers \UserAccessManager\Config\Config::hidePostTypeTitle()
     * @covers \UserAccessManager\Config\Config::hidePostTypeComments()
     * @covers \UserAccessManager\Config\Config::hideEmptyTaxonomy()
     */
    public function testHideObject()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory();

        $config = new Config(
            $wordpress,
            $objectHandler,
            $configParameterFactory,
            'baseFile'
        );

        self::assertEquals('hide_post', self::callMethod($config, 'hideObject', ['hide_post']));
        self::assertTrue(self::callMethod($config, 'hideObject', ['hide_undefined']));

        self::assertEquals('hide_post', $config->hidePostType('post'));
        self::assertTrue($config->hidePostType('undefined'));

        self::assertEquals('hide_post_title', $config->hidePostTypeTitle('post'));
        self::assertTrue($config->hidePostTypeTitle('undefined'));

        self::assertEquals('post_comments_locked', $config->hidePostTypeComments('post'));
        self::assertTrue($config->hidePostTypeComments('undefined'));

        self::assertEquals('hide_empty_category', $config->hideEmptyTaxonomy('category'));
        self::assertFalse($config->hideEmptyTaxonomy('undefined'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getPostTypeTitle()
     * @covers \UserAccessManager\Config\Config::getPostTypeContent()
     * @covers \UserAccessManager\Config\Config::getPostTypeCommentContent()
     */
    public function testObjectGetter()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory();

        $config = new Config(
            $wordpress,
            $objectHandler,
            $configParameterFactory,
            'baseFile'
        );

        self::assertEquals('post_title', $config->getPostTypeTitle('post'));
        self::assertEquals('post_content', $config->getPostTypeContent('post'));
        self::assertEquals('post_comment_content', $config->getPostTypeCommentContent('post'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getRedirect
     * @covers \UserAccessManager\Config\Config::getRedirectCustomPage
     * @covers \UserAccessManager\Config\Config::getRedirectCustomUrl
     * @covers \UserAccessManager\Config\Config::lockRecursive
     * @covers \UserAccessManager\Config\Config::authorsHasAccessToOwn
     * @covers \UserAccessManager\Config\Config::authorsCanAddPostsToGroups
     * @covers \UserAccessManager\Config\Config::lockFile
     * @covers \UserAccessManager\Config\Config::getFilePassType
     * @covers \UserAccessManager\Config\Config::getLockFileTypes
     * @covers \UserAccessManager\Config\Config::getDownloadType
     * @covers \UserAccessManager\Config\Config::getLockedFileTypes
     * @covers \UserAccessManager\Config\Config::getNotLockedFileTypes
     * @covers \UserAccessManager\Config\Config::blogAdminHint
     * @covers \UserAccessManager\Config\Config::getBlogAdminHintText
     * @covers \UserAccessManager\Config\Config::protectFeed
     * @covers \UserAccessManager\Config\Config::showPostContentBeforeMore
     * @covers \UserAccessManager\Config\Config::getFullAccessRole
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
            'protectFeed' => 'protect_feed',
            'showAssignedGroups' => 'show_assigned_groups',
            'showPostContentBeforeMore' => 'show_post_content_before_more',
            'getFullAccessRole' => 'full_access_role'
        ];

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $objectHandler = $this->getDefaultObjectHandler(1);
        $configParameterFactory = $this->getFactory();

        $config = new Config(
            $wordpress,
            $objectHandler,
            $configParameterFactory,
            'baseFile'
        );

        foreach ($methods as $method => $expected) {
            self::assertEquals($expected, $config->{$method}());
        }
    }
}
