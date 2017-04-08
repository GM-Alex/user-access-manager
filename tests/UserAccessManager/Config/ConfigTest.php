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
 * @version   SVN: $Id$
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
    private $aDefaultValues;

    /**
     * Create default mocked objects.
     */
    public function setUp()
    {
        $this->aDefaultValues = [
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
            'full_access_role' => 'selection|full_access_role|administrator|'
                .'administrator|editor|author|contributor|subscriber',
        ];
    }

    /**
     * @param int $iCallExpectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\ObjectHandler\ObjectHandler
     */
    private function getDefaultObjectHandler($iCallExpectation)
    {
        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly($iCallExpectation))
            ->method('getPostTypes')
            ->will($this->returnValue(['post', 'page', 'attachment']));

        $ObjectHandler->expects($this->exactly($iCallExpectation))
            ->method('getTaxonomies')
            ->will($this->returnValue(['category']));

        return $ObjectHandler;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameterFactory
     */
    private function getConfigParameterFactory()
    {
        return $this->createMock('\UserAccessManager\Config\ConfigParameterFactory');
    }

    /**
     * @param callable $cClosure
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigParameterFactory
     */
    private function getFactory($cClosure = null)
    {
        if ($cClosure === null) {
            $cClosure = function ($sId) {
                $Stub = self::getMockForAbstractClass(
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

                $Stub->expects(self::any())
                    ->method('getId')
                    ->will($this->returnValue($sId));

                $Stub->expects(self::any())
                    ->method('setValue')
                    ->with($this->equalTo($sId.'|value'))
                    ->will($this->returnValue(null));

                $Stub->expects(self::any())
                    ->method('getValue')
                    ->will($this->returnValue($sId));

                return $Stub;
            };
        }


        $ConfigParameterFactory = $this->getConfigParameterFactory();
        $ConfigParameterFactory->expects($this->any())
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback($cClosure));

        $ConfigParameterFactory->expects($this->any())
            ->method('createStringConfigParameter')
            ->will($this->returnCallback($cClosure));

        $ConfigParameterFactory->expects($this->any())
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback($cClosure));

        return $ConfigParameterFactory;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::__construct()
     */
    public function testCanCreateInstance()
    {
        $Config = new Config(
            $this->getWordpress(),
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertInstanceOf('\UserAccessManager\Config\Config', $Config);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getWpOption()
     */
    public function testGetWpOption()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(3))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('optionValueOne', 'optionValueTwo'));

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        $mOptionOne = $Config->getWpOption('optionOne');
        $mOptionOneAgain = $Config->getWpOption('optionOne');

        self::assertEquals('optionValueOne', $mOptionOne);
        self::assertEquals('optionValueOne', $mOptionOneAgain);

        $mOptionTwo = $Config->getWpOption('optionTwo');
        self::assertEquals('optionValueTwo', $mOptionTwo);

        $mOptionTwo = $Config->getWpOption('optionNotExisting');
        self::assertEquals(null, $mOptionTwo);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getConfigParameters()
     *
     * @return Config
     */
    public function testGetConfigParameters()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $ObjectHandler = $this->getDefaultObjectHandler(2);

        $ConfigParameterFactory = $this->getConfigParameterFactory();
        $ConfigParameterFactory->expects($this->exactly(17))
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback(
                function ($sId, $blValue) {
                    $sReturn = 'bool|'.$sId.'|';
                    $sReturn .= ($blValue === true) ? 'true' : 'false';
                    return $sReturn;
                }
            ));

        $ConfigParameterFactory->expects($this->exactly(11))
            ->method('createStringConfigParameter')
            ->will($this->returnCallback(
                function ($sId, $sValue) {
                    return 'string|'.$sId.'|'.$sValue;
                }
            ));

        $ConfigParameterFactory->expects($this->exactly(5))
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback(
                function ($sId, $sValue, $aSelections) {
                    return 'selection|'.$sId.'|'.$sValue.'|'.implode('|', $aSelections);
                }
            ));

        $Config = new Config(
            $Wordpress,
            $ObjectHandler,
            $ConfigParameterFactory,
            'baseFile'
        );

        self::assertEquals($this->aDefaultValues, $Config->getConfigParameters());

        $aOptionKeys = array_keys($this->aDefaultValues);
        $aTestValues = array_map(function ($sElement) {
            return $sElement.'|value';
        }, $aOptionKeys);

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(array_combine($aOptionKeys, $aTestValues)));

        $ConfigParameterFactory = $this->getFactory();

        $Config = new Config(
            $Wordpress,
            $ObjectHandler,
            $ConfigParameterFactory,
            'baseFile'
        );

        $aParameters = $Config->getConfigParameters();

        foreach ($aParameters as $Parameter) {
            self::assertEquals($Parameter->getId(), $Parameter->getValue());
        }

        return $Config;
    }

    /**
     * @group   unit
     * @depends testGetConfigParameters
     * @covers  \UserAccessManager\Config\Config::flushConfigParameters()
     *
     * @param Config $Config
     */
    public function testFlushConfigParameters(Config $Config)
    {
        self::assertAttributeNotEmpty('aConfigParameters', $Config);
        $Config->flushConfigParameters();
        self::assertAttributeEquals(null, 'aConfigParameters', $Config);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::setConfigParameters()
     */
    public function testSetConfigParameters()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $Wordpress->expects($this->once())
            ->method('updateOption')
            ->with(Config::ADMIN_OPTIONS_NAME);

        $cClosure = function ($sId) {
            $Stub = self::getMockForAbstractClass(
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

            $Stub->expects(self::any())
                ->method('getId')
                ->will($this->returnValue($sId));

            $Stub->expects(self::any())
                ->method('setValue')
                ->with($this->logicalOr('blog_admin_hint|value', 'lock_file|value'))
                ->will($this->returnValue(null));

            $Stub->expects(self::any())
                ->method('getValue')
                ->will($this->returnValue($sId));

            return $Stub;
        };

        $ObjectHandler = $this->getDefaultObjectHandler(1);
        $ConfigParameterFactory = $this->getFactory($cClosure);

        $Config = new Config(
            $Wordpress,
            $ObjectHandler,
            $ConfigParameterFactory,
            'baseFile'
        );

        $Config->setConfigParameters(
            [
                'blog_admin_hint' => 'blog_admin_hint|value',
                'lock_file' => 'lock_file|value'
            ]
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getParameterValue()
     */
    public function testGetParameterValue()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $ObjectHandler = $this->getDefaultObjectHandler(1);
        $ConfigParameterFactory = $this->getFactory();

        $Config = new Config(
            $Wordpress,
            $ObjectHandler,
            $ConfigParameterFactory,
            'baseFile'
        );

        $sReturn = self::callMethod($Config, 'getParameterValue', ['lock_file']);
        self::assertEquals('lock_file', $sReturn);

        self::expectException('\Exception');
        self::callMethod($Config, 'getParameterValue', ['undefined']);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\Config::atAdminPanel()
     */
    public function testAtAdminPanel()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(true, false));

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertTrue($Config->atAdminPanel());
        self::assertFalse($Config->atAdminPanel());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::isPermalinksActive()
     */
    public function testIsPermalinksActive()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('aaa', ''));

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertTrue($Config->isPermalinksActive());
        self::setValue($Config, 'aWpOptions', []);
        self::assertFalse($Config->isPermalinksActive());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getUploadDirectory()
     */
    public function testGetUploadDirectory()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
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

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertEquals(null, $Config->getUploadDirectory());
        self::assertEquals('baseDir/', $Config->getUploadDirectory());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getMimeTypes()
     */
    public function testGetMimeTypes()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
            ->method('getAllowedMimeTypes')
            ->will(
                $this->onConsecutiveCalls(
                    ['a|b' => 'firstType', 'c' => 'secondType'],
                    ['c|b' => 'firstType', 'a' => 'secondType']
                )
            );

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $Config->getMimeTypes()
        );
        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $Config->getMimeTypes()
        );

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $this->getConfigParameterFactory(),
            'baseFile'
        );

        self::assertEquals(
            ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType'],
            $Config->getMimeTypes()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getUrlPath()
     */
    public function testGetUrlPath()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('pluginsUrl')
            ->will($this->returnValue('pluginsUrl'));

        $ConfigParameterFactory = $this->getFactory();

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $ConfigParameterFactory,
            'baseFile'
        );

        self::assertEquals(
            'pluginsUrl'.DIRECTORY_SEPARATOR,
            $Config->getUrlPath()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getRealPath()
     */
    public function testGetRealPath()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getPluginDir')
            ->will($this->returnValue('pluginDir'));
        $Wordpress->expects($this->once())
            ->method('pluginBasename')
            ->will($this->returnValue('pluginBasename'));

        $ConfigParameterFactory = $this->getFactory();

        $Config = new Config(
            $Wordpress,
            $this->getObjectHandler(),
            $ConfigParameterFactory,
            'baseFile'
        );
        self::assertEquals(
            'pluginDir'.DIRECTORY_SEPARATOR.'pluginBasename'.DIRECTORY_SEPARATOR,
            $Config->getRealPath()
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
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $ObjectHandler = $this->getDefaultObjectHandler(1);
        $ConfigParameterFactory = $this->getFactory();

        $Config = new Config(
            $Wordpress,
            $ObjectHandler,
            $ConfigParameterFactory,
            'baseFile'
        );

        self::assertEquals('hide_post', self::callMethod($Config, 'hideObject', ['hide_post']));
        self::assertTrue(self::callMethod($Config, 'hideObject', ['hide_undefined']));

        self::assertEquals('hide_post', $Config->hidePostType('post'));
        self::assertTrue($Config->hidePostType('undefined'));

        self::assertEquals('hide_post_title', $Config->hidePostTypeTitle('post'));
        self::assertTrue($Config->hidePostTypeTitle('undefined'));

        self::assertEquals('post_comments_locked', $Config->hidePostTypeComments('post'));
        self::assertTrue($Config->hidePostTypeComments('undefined'));

        self::assertEquals('hide_empty_category', $Config->hideEmptyTaxonomy('category'));
        self::assertTrue($Config->hideEmptyTaxonomy('undefined'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getPostTypeTitle()
     * @covers \UserAccessManager\Config\Config::getPostTypeContent()
     * @covers \UserAccessManager\Config\Config::getPostTypeCommentContent()
     */
    public function testObjectGetter()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $ObjectHandler = $this->getDefaultObjectHandler(1);
        $ConfigParameterFactory = $this->getFactory();

        $Config = new Config(
            $Wordpress,
            $ObjectHandler,
            $ConfigParameterFactory,
            'baseFile'
        );

        self::assertEquals('post_title', $Config->getPostTypeTitle('post'));
        self::assertEquals('post_content', $Config->getPostTypeContent('post'));
        self::assertEquals('post_comment_content', $Config->getPostTypeCommentContent('post'));
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
        $aMethods = [
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
            'showPostContentBeforeMore' => 'show_post_content_before_more',
            'getFullAccessRole' => 'full_access_role'
        ];

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $ObjectHandler = $this->getDefaultObjectHandler(1);
        $ConfigParameterFactory = $this->getFactory();

        $Config = new Config(
            $Wordpress,
            $ObjectHandler,
            $ConfigParameterFactory,
            'baseFile'
        );

        foreach ($aMethods as $sMethod => $sExpected) {
            self::assertEquals($sExpected, $Config->{$sMethod}());
        }
    }
}
