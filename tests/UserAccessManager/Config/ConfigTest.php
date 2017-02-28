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

/**
 * Class ConfigTest
 *
 * @package UserAccessManager\Config
 */
class ConfigTest extends \UserAccessManagerTestCase
{
    /**
     * @var array
     */
    private $_aDefaultValues;

    /**
     * Create default mocked objects.
     */
    public function setUp()
    {
        $this->_aDefaultValues = [
            'hide_post' => 'bool|hide_post|false',
            'hide_post_title' => 'bool|hide_post_title|false',
            'post_title' => 'string|post_title|No rights!',
            'show_post_content_before_more' => 'bool|show_post_content_before_more|false',
            'post_content' => 'string|post_content|Sorry you have no rights to view this entry!',
            'hide_post_comment' => 'bool|hide_post_comment|false',
            'post_comment_content' => 'string|post_comment_content|Sorry no rights to view comments!',
            'post_comments_locked' => 'bool|post_comments_locked|false',
            'hide_page' => 'bool|hide_page|false',
            'hide_page_title' => 'bool|hide_page_title|false',
            'page_title' => 'string|page_title|No rights!',
            'page_content' => 'string|page_content|Sorry you have no rights to view this entry!',
            'hide_page_comment' => 'bool|hide_page_comment|false',
            'page_comment_content' => 'string|page_comment_content|Sorry no rights to view comments!',
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
            'full_access_role' => 'selection|full_access_role|administrator|administrator|editor|author|contributor|subscriber',
        ];
    }

    /**
     * @param int $iCallExpectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\ObjectHandler\ObjectHandler
     */
    private function getDefaultObjectHandler($iCallExpectation)
    {
        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly($iCallExpectation))
            ->method('getPostTypes')
            ->will($this->returnValue(['post', 'page', 'attachment']));

        $oObjectHandler->expects($this->exactly($iCallExpectation))
            ->method('getTaxonomies')
            ->will($this->returnValue(['category']));

        return $oObjectHandler;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameterFactory
     */
    private function getConfigParameterFactory()
    {
        return $this->createMock('\UserAccessManager\Config\ConfigParameterFactory');
    }

    private function _getFactory($cClosure = null)
    {
        if ($cClosure === null) {
            $cClosure = function ($sId) {
                $oStub = self::getMockForAbstractClass(
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

                $oStub->expects(self::any())
                    ->method('getId')
                    ->will($this->returnValue($sId));

                $oStub->expects(self::any())
                    ->method('setValue')
                    ->with($this->equalTo($sId.'|value'))
                    ->will($this->returnValue(null));

                $oStub->expects(self::any())
                    ->method('getValue')
                    ->will($this->returnValue($sId));

                return $oStub;
            };
        }


        $oConfigParameterFactory = $this->getConfigParameterFactory();
        $oConfigParameterFactory->expects($this->any())
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback($cClosure));

        $oConfigParameterFactory->expects($this->any())
            ->method('createStringConfigParameter')
            ->will($this->returnCallback($cClosure));

        $oConfigParameterFactory->expects($this->any())
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback($cClosure));

        return $oConfigParameterFactory;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::__construct()
     */
    public function testCanCreateInstance()
    {
        $oConfig = new Config($this->getWrapper(), $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        self::assertInstanceOf('\UserAccessManager\Config\Config', $oConfig);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getWpOption()
     */
    public function testGetWpOption()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(3))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('optionValueOne', 'optionValueTwo'));

        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        $mOptionOne = $oConfig->getWpOption('optionOne');
        $mOptionOneAgain = $oConfig->getWpOption('optionOne');

        self::assertEquals('optionValueOne', $mOptionOne);
        self::assertEquals('optionValueOne', $mOptionOneAgain);

        $mOptionTwo = $oConfig->getWpOption('optionTwo');
        self::assertEquals('optionValueTwo', $mOptionTwo);

        $mOptionTwo = $oConfig->getWpOption('optionNotExisting');
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
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValue(null));

        $oObjectHandler = $this->getDefaultObjectHandler(2);

        $oConfigParameterFactory = $this->getConfigParameterFactory();
        $oConfigParameterFactory->expects($this->exactly(17))
            ->method('createBooleanConfigParameter')
            ->will($this->returnCallback(
                function ($sId, $blValue) {
                    $sReturn = 'bool|'.$sId.'|';
                    $sReturn .= ($blValue === true) ? 'true' : 'false';
                    return $sReturn;
                }
            ));

        $oConfigParameterFactory->expects($this->exactly(11))
            ->method('createStringConfigParameter')
            ->will($this->returnCallback(
                function ($sId, $sValue) {
                    return 'string|'.$sId.'|'.$sValue;
                }
            ));

        $oConfigParameterFactory->expects($this->exactly(5))
            ->method('createSelectionConfigParameter')
            ->will($this->returnCallback(
                function ($sId, $sValue, $aSelections) {
                    return 'selection|'.$sId.'|'.$sValue.'|'.implode('|', $aSelections);
                }
            ));

        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, 'baseFile');

        self::assertEquals($this->_aDefaultValues, $oConfig->getConfigParameters());

        $aOptionKeys = array_keys($this->_aDefaultValues);
        $aTestValues = array_map(function ($sElement) {
            return $sElement.'|value';
        }, $aOptionKeys);

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValue(array_combine($aOptionKeys, $aTestValues)));

        $oConfigParameterFactory = $this->_getFactory();
        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, 'baseFile');

        $aParameters = $oConfig->getConfigParameters();

        foreach ($aParameters as $oParameter) {
            self::assertEquals($oParameter->getId(), $oParameter->getValue());
        }

        return $oConfig;
    }

    /**
     * @group   unit
     * @depends testGetConfigParameters
     * @covers  \UserAccessManager\Config\Config::flushConfigParameters()
     *
     * @param Config $oConfig
     */
    public function testFlushConfigParameters(Config $oConfig)
    {
        self::assertAttributeNotEmpty('_aConfigParameters', $oConfig);
        $oConfig->flushConfigParameters();
        self::assertAttributeEquals(null, '_aConfigParameters', $oConfig);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::setConfigParameters()
     */
    public function testSetConfigParameters()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValue(null));

        $oWrapper->expects($this->exactly(1))
            ->method('updateOption')
            ->with(Config::ADMIN_OPTIONS_NAME);

        $cClosure = function ($sId) {
            $oStub = self::getMockForAbstractClass(
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

            $oStub->expects(self::any())
                ->method('getId')
                ->will($this->returnValue($sId));

            $oStub->expects(self::any())
                ->method('setValue')
                ->with($this->logicalOr('blog_admin_hint|value', 'lock_file|value'))
                ->will($this->returnValue(null));

            $oStub->expects(self::any())
                ->method('getValue')
                ->will($this->returnValue($sId));

            return $oStub;
        };

        $oObjectHandler = $this->getDefaultObjectHandler(1);
        $oConfigParameterFactory = $this->_getFactory($cClosure);
        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, 'baseFile');

        $oConfig->setConfigParameters(
            [
                'blog_admin_hint' => 'blog_admin_hint|value',
                'lock_file' => 'lock_file|value'
            ]
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::_getParameterValue()
     */
    public function testGetParameterValue()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValue(null));

        $oObjectHandler = $this->getDefaultObjectHandler(1);
        $oConfigParameterFactory = $this->_getFactory();
        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, 'baseFile');

        $sReturn = self::callMethod($oConfig, '_getParameterValue', ['lock_file']);
        self:
        self::assertEquals('lock_file', $sReturn);

        self::expectException('\Exception');
        self::callMethod($oConfig, '_getParameterValue', ['undefined']);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\Config::atAdminPanel()
     */
    public function testAtAdminPanel()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(2))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(true, false));

        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        self::assertEquals(true, $oConfig->atAdminPanel());
        self::assertEquals(false, $oConfig->atAdminPanel());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::isPermalinksActive()
     */
    public function testIsPermalinksActive()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(2))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('aaa', ''));

        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        self::assertEquals(true, $oConfig->isPermalinksActive());

        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        self::assertEquals(false, $oConfig->isPermalinksActive());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getUploadDirectory()
     */
    public function testGetUploadDirectory()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(2))
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

        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        self::assertEquals(null, $oConfig->getUploadDirectory());
        self::assertEquals('baseDir/', $oConfig->getUploadDirectory());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getMimeTypes()
     */
    public function testGetMimeTypes()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(2))
            ->method('getAllowedMimeTypes')
            ->will(
                $this->onConsecutiveCalls(
                    ['a|b' => 'firstType', 'c' => 'secondType'],
                    ['c|b' => 'firstType', 'a' => 'secondType']
                )
            );

        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $oConfig->getMimeTypes()
        );
        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $oConfig->getMimeTypes()
        );

        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $this->getConfigParameterFactory(), 'baseFile');
        self::assertEquals(
            ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType'],
            $oConfig->getMimeTypes()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getUrlPath()
     */
    public function testGetUrlPath()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('pluginsUrl')
            ->will($this->returnValue('pluginsUrl'));

        $oConfigParameterFactory = $this->_getFactory();
        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $oConfigParameterFactory, 'baseFile');
        self::assertEquals(
            'pluginsUrl'.DIRECTORY_SEPARATOR,
            $oConfig->getUrlPath()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getRealPath()
     */
    public function testGetRealPath()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('getPluginDir')
            ->will($this->returnValue('pluginDir'));
        $oWrapper->expects($this->once())
            ->method('pluginBasename')
            ->will($this->returnValue('pluginBasename'));

        $oConfigParameterFactory = $this->_getFactory();
        $oConfig = new Config($oWrapper, $this->getObjectHandler(), $oConfigParameterFactory, 'baseFile');
        self::assertEquals(
            'pluginDir'.DIRECTORY_SEPARATOR.'pluginBasename'.DIRECTORY_SEPARATOR,
            $oConfig->getRealPath()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::_hideObject()
     * @covers \UserAccessManager\Config\Config::hideObjectType()
     * @covers \UserAccessManager\Config\Config::hideObjectTypeTitle()
     * @covers \UserAccessManager\Config\Config::hideObjectTypeComments()
     */
    public function testHideObject()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValue(null));

        $oObjectHandler = $this->getDefaultObjectHandler(1);
        $oConfigParameterFactory = $this->_getFactory();
        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, 'baseFile');

        self::assertEquals('hide_post', self::callMethod($oConfig, '_hideObject', ['hide_post']));
        self::assertEquals(true, self::callMethod($oConfig, '_hideObject', ['hide_undefined']));

        self::assertEquals('hide_post', $oConfig->hideObjectType('post'));
        self::assertEquals(true, $oConfig->hideObjectType('undefined'));

        self::assertEquals('hide_post_title', $oConfig->hideObjectTypeTitle('post'));
        self::assertEquals(true, $oConfig->hideObjectTypeTitle('undefined'));

        self::assertEquals('post_comments_locked', $oConfig->hideObjectTypeComments('post'));
        self::assertEquals(true, $oConfig->hideObjectTypeComments('undefined'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getObjectTypeTitle()
     * @covers \UserAccessManager\Config\Config::getObjectTypeContent()
     * @covers \UserAccessManager\Config\Config::getObjectTypeCommentContent()
     */
    public function testObjectGetter()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValue(null));
        $oObjectHandler = $this->getDefaultObjectHandler(1);
        $oConfigParameterFactory = $this->_getFactory();
        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, 'baseFile');

        self::assertEquals('post_title', $oConfig->getObjectTypeTitle('post'));
        self::assertEquals('post_content', $oConfig->getObjectTypeContent('post'));
        self::assertEquals('post_comment_content', $oConfig->getObjectTypeCommentContent('post'));
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
     * @covers \UserAccessManager\Config\Config::hideEmptyCategories
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
            'hideEmptyCategories' => 'hide_empty_category',
            'protectFeed' => 'protect_feed',
            'showPostContentBeforeMore' => 'show_post_content_before_more',
            'getFullAccessRole' => 'full_access_role'
        ];

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getOption')
            ->will($this->returnValue(null));
        $oObjectHandler = $this->getDefaultObjectHandler(1);
        $oConfigParameterFactory = $this->_getFactory();
        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, 'baseFile');

        foreach ($aMethods as $sMethod => $sExpected) {
            self::assertEquals($sExpected, $oConfig->{$sMethod}());
        }
    }
}
