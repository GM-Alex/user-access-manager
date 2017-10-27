<?php
/**
 * RedirectControllerTest.php
 *
 * The RedirectControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Controller\Frontend;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Controller\Frontend\RedirectController;
use UserAccessManager\File\FileObject;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;

/**
 * Class RedirectControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\RedirectController
 */
class RedirectControllerTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->root->unmount();
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        self::assertInstanceOf(RedirectController::class, $frontendRedirectController);
    }

    /**
     * @group  unit
     * @covers ::getWordpress()
     */
    public function testSimpleGetters()
    {
        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        self::assertEquals($this->getWordpress(), self::callMethod($frontendRedirectController, 'getWordpress'));
    }

    /**
     * @param int    $id
     * @param string $postType
     * @param string $title
     * @param string $content
     * @param bool   $closed
     * @param string $postMimeType
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Post
     */
    private function getPost(
        $id,
        $postType = 'post',
        $title = null,
        $content = null,
        $closed = false,
        $postMimeType = 'post/mime/type'
    ) {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Post $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = $id;
        $post->post_type = $postType;
        $post->post_title = ($title === null) ? "title{$id}" : $title;
        $post->post_content = ($content === null) ?
            "[LOGIN_FORM] content{$id}<!--more-->text<!--more-->\\contentAfter" : $content;
        $post->comment_status = ($closed === true) ? 'close' : 'open';
        $post->post_mime_type = $postMimeType;

        return $post;
    }

    /**
     * @group  unit
     * @covers ::getPostIdByUrl()
     */
    public function testGetPostIdByUrl()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(6))
            ->method('attachmentUrlToPostId')
            ->withConsecutive(
                ['url/part'],
                ['url/part'],
                ['url-e123/part'],
                ['url/part'],
                ['url-e123/part'],
                ['url/part.pdf']
            )
            ->will($this->onConsecutiveCalls(0, 1, 2, 3, 4, 5, 1));

        $cache = $this->getCache();

        $cache->expects($this->exactly(7))
            ->method('getFromRuntimeCache')
            ->with(RedirectController::POST_URL_CACHE_KEY)
            ->will($this->onConsecutiveCalls(
                null,
                null,
                null,
                null,
                null,
                null,
                ['url/part' => 1]
            ));

        $cache->expects($this->exactly(6))
            ->method('addToRuntimeCache')
            ->withConsecutive(
                [RedirectController::POST_URL_CACHE_KEY, ['url/part' => 0]],
                [RedirectController::POST_URL_CACHE_KEY, ['url/part' => 1]],
                [RedirectController::POST_URL_CACHE_KEY, ['url-e123/part' => 2]],
                [RedirectController::POST_URL_CACHE_KEY, ['url-123x321_z/part' => 3]],
                [RedirectController::POST_URL_CACHE_KEY, ['url-e123-123x321/part' => 4]],
                [RedirectController::POST_URL_CACHE_KEY, ['url/part-pdf.jpg' => 5]]
            );

        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        self::assertEquals(0, $frontendRedirectController->getPostIdByUrl('url/part'));
        self::assertEquals(1, $frontendRedirectController->getPostIdByUrl('url/part'));
        self::assertEquals(2, $frontendRedirectController->getPostIdByUrl('url-e123/part'));
        self::assertEquals(3, $frontendRedirectController->getPostIdByUrl('url-123x321_z/part'));
        self::assertEquals(4, $frontendRedirectController->getPostIdByUrl('url-e123-123x321/part'));
        self::assertEquals(5, $frontendRedirectController->getPostIdByUrl('url/part-pdf.jpg'));
        self::assertEquals(1, $frontendRedirectController->getPostIdByUrl('url/part'));
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::getFileSettingsByType()
     */
    public function testGetFile()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(7))
            ->method('getUploadDir')
            ->will($this->returnValue([
                'basedir' => '/baseDirectory/file/pictures/',
                'baseurl' => 'http://baseUrl/file/pictures/'
            ]));

        $wordpress->expects($this->exactly(5))
            ->method('attachmentIsImage')
            ->will($this->onConsecutiveCalls(false, true, false, true, true));

        $wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);

        $fileObject = $this->createMock(FileObject::class);

        $fileObject->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $fileObject->expects($this->any())
            ->method('getType')
            ->will($this->returnValue('type'));

        $fileObject->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue('file'));

        $fileObject->expects($this->any())
            ->method('isImage')
            ->will($this->returnValue(false));

        $wordpress->expects($this->exactly(2))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_get_file_settings_by_type', null, 'customType', 'url', null],
                ['uam_get_file_settings_by_type', null, 'customType', 'url', 'extra']
            )
            ->will($this->onConsecutiveCalls(null, $fileObject));

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(2))
            ->method('getRealPath')
            ->will($this->returnValue('realPath/'));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(3))
            ->method('getNoAccessImageType')
            ->will($this->onConsecutiveCalls('default', 'custom', 'default'));

        $mainConfig->expects($this->once())
            ->method('getCustomNoAccessImage')
            ->will($this->returnValue('customImage.jpg'));

        $cache = $this->getCache();

        $cache->expects($this->exactly(7))
            ->method('getFromRuntimeCache')
            ->with(RedirectController::POST_URL_CACHE_KEY)
            ->will($this->returnValue(['http://baseUrl/file/pictures/url' => 1]));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(7))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                false,
                $this->getPost(1),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE)
            ));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(6))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['type', 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1]
            )
            ->will($this->onConsecutiveCalls(true, false, false, true, false, false));

        $fileHandler = $this->getFileHandler();

        $fileHandler->expects($this->exactly(5))
            ->method('getFile')
            ->withConsecutive(
                ['file', false],
                ['realPath/assets/gfx/noAccessPic.png', true],
                ['/baseDirectory/file/pictures/url', false],
                ['customImage.jpg', true],
                ['realPath/assets/gfx/noAccessPic.png', true]
            );

        $fileObjectFactory = $this->getFileObjectFactory();

        $fileObjectFactory->expects($this->exactly(5))
            ->method('createFileObject')
            ->withConsecutive(
                [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/baseDirectory/file/pictures/url', false],
                [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/baseDirectory/file/pictures/url', true],
                [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/baseDirectory/file/pictures/url', false],
                [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/baseDirectory/file/pictures/url', true],
                [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/baseDirectory/file/pictures/url', true]
            )
            ->will($this->returnCallback(function ($id, $type, $file, $isImage) {
                $fileObject = $this->createMock(FileObject::class);

                $fileObject->expects($this->any())
                    ->method('getId')
                    ->will($this->returnValue($id));

                $fileObject->expects($this->any())
                    ->method('getType')
                    ->will($this->returnValue($type));

                $fileObject->expects($this->any())
                    ->method('getFile')
                    ->will($this->returnValue($file));

                $fileObject->expects($this->any())
                    ->method('isImage')
                    ->will($this->returnValue($isImage));

                return $fileObject;
            }));


        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $objectHandler,
            $accessHandler,
            $fileHandler,
            $fileObjectFactory
        );

        $frontendRedirectController->getFile('customType', 'url');
        $_GET['uamextra'] = 'extra';
        $frontendRedirectController->getFile('customType', 'url');
        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url');
        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url');
        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url');
        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url');
        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url');
        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url');
        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url');
    }

    /**
     * @group  unit
     * @covers ::redirectUser()
     * @covers ::getRedirectUrlAndPermalink()
     */
    public function testRedirectUser()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(3))
            ->method('callExit');

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Query $wpQuery
         */
        $wpQuery = $this->getMockBuilder('\WP_Query')->setMethods(['get_posts'])->getMock();
        $wpQuery->expects($this->once())
            ->method('get_posts')
            ->will($this->returnValue([
                $this->getPost(1),
                $this->getPost(2),
                $this->getPost(3)
            ]));

        $post = $this->getPost(1);
        $post->guid = 'guid';

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getWpQuery')
            ->will($this->returnValue($wpQuery));

        $wordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('HomeUrl'));

        $wordpress->expects($this->exactly(3))
            ->method('getPageLink')
            ->with($post)
            ->will($this->returnValue('PageLink'));

        $wordpress->expects($this->exactly(3))
            ->method('wpRedirect')
            ->withConsecutive(['guid'], ['RedirectCustomUrl'], ['HomeUrl']);

        $config = $this->getMainConfig();

        $config->expects($this->exactly(7))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls(
                'custom_page',
                'custom_page',
                'custom_page',
                'custom_page',
                'custom_url',
                null,
                null
            ));

        $config->expects($this->exactly(4))
            ->method('getRedirectCustomPage')
            ->will($this->returnValue('RedirectCustomPage'));

        $config->expects($this->once())
            ->method('getRedirectCustomUrl')
            ->will($this->returnValue('RedirectCustomUrl'));

        $util = $this->getUtil();

        $util->expects($this->exactly(7))
            ->method('getCurrentUrl')
            ->will($this->onConsecutiveCalls(
                'currentUrl',
                'guid',
                'PageLink',
                'currentUrl',
                'currentUrl',
                'HomeUrl',
                'currentUrl'
            ));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive(['RedirectCustomPage'], ['RedirectCustomPage'], ['RedirectCustomPage'])
            ->will($this->onConsecutiveCalls(
                false,
                $post,
                $post,
                $post
            ));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $frontendRedirectController = new RedirectController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $config,
            $this->getDatabase(),
            $util,
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        $frontendRedirectController->redirectUser();
        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
    }

    /**
     * @group  unit
     * @covers ::redirect()
     * @covers ::extractObjectTypeAndId()
     * @covers ::getPostIdByName()
     */
    public function testRedirect()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue(null));

        $wordpress->expects($this->exactly(2))
            ->method('getPageByPath')
            ->with('pageNameValue')
            ->will($this->onConsecutiveCalls(null, $this->getPost(2)));

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false, false, false, false));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(9))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls('false', null, null, null, null, null, null, null, null));

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->once())
            ->method('prepare')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT ID
                    FROM postTable
                    WHERE post_name = %s
                    AND post_type IN (\'post\',\'page\',\'other\')'
                ),
                'nameValue'
            )
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->once())
            ->method('getVariable')
            ->with('preparedQuery')
            ->will($this->returnValue(1));

        $util = $this->getUtil();
        $util->expects($this->once())
            ->method('getCurrentUrl')
            ->will($this->returnValue('currentUrl'));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'page' => 'page', 'other' => 'other']));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(7))
            ->method('checkObjectAccess')
            ->withConsecutive(
                [null, null],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 'pValue'],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 'pageIdValue'],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'catIdValue'],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1],
                [null, null],
                ['post', 2]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false,
                true,
                true,
                true,
                true,
                true
            ));

        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $database,
            $util,
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        $pageParams = new \stdClass();

        $_GET['uamfiletype'] = 'fileType';
        $pageParams->query_vars = [];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $pageParams->query_vars = [];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $pageParams->query_vars = [];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $pageParams->query_vars = ['p' => 'pValue'];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        unset($_GET['uamfiletype']);
        $_GET['uamgetfile'] = 'file';
        $pageParams->query_vars = ['page_id' => 'pageIdValue'];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $pageParams->query_vars = ['cat_id' => 'catIdValue'];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $pageParams->query_vars = ['name' => 'nameValue'];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $pageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $pageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));

        $_GET['uamfiletype'] = 'fileType';
        self::assertEquals('header', $frontendRedirectController->redirect('header', $pageParams));
    }

    /**
     * @group  unit
     * @covers ::getFileUrl()
     */
    public function testGetFileUrl()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('homeUrl'));

        $wordpress->expects($this->exactly(5))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(true, true, true, false, false));

        $wordpress->expects($this->exactly(3))
            ->method('gotModRewrite')
            ->will($this->onConsecutiveCalls(true, false, false));

        $wordpressConfig = $this->getWordpressConfig();

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(6))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

        $mainConfig->expects($this->exactly(3))
            ->method('getLockedFiles')
            ->will($this->onConsecutiveCalls('none', 'all', 'aaa,mime'));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1, 'post', null, null, false, 'type'),
                $this->getPost(1),
                $this->getPost(1)
            ));

        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $objectHandler,
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        self::assertEquals('url', $frontendRedirectController->getFileUrl('url', 1));
        self::assertEquals('url', $frontendRedirectController->getFileUrl('url', 1));
        self::assertEquals('url', $frontendRedirectController->getFileUrl('url', 1));
        self::assertEquals('url', $frontendRedirectController->getFileUrl('url', 1));
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $frontendRedirectController->getFileUrl('url', 1)
        );
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $frontendRedirectController->getFileUrl('url', 1)
        );
    }

    /**
     * @group  unit
     * @covers ::cachePostLinks()
     */
    public function testCachePostLinks()
    {
        $cache = $this->getCache();

        $cache->expects($this->exactly(2))
            ->method('getFromRuntimeCache')
            ->with(RedirectController::POST_URL_CACHE_KEY)
            ->will($this->onConsecutiveCalls(
                null,
                ['firstUrl' => 1]
            ));

        $cache->expects($this->exactly(2))
            ->method('addToRuntimeCache')
            ->withConsecutive(
                [RedirectController::POST_URL_CACHE_KEY, ['firstUrl' => 1]],
                [RedirectController::POST_URL_CACHE_KEY, ['firstUrl' => 1, 'secondUrl' => 2]]
            );

        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        self::assertEquals('firstUrl', $frontendRedirectController->cachePostLinks('firstUrl', $this->getPost(1)));
        self::assertEquals('secondUrl', $frontendRedirectController->cachePostLinks('secondUrl', $this->getPost(2)));
    }

    /**
     * @group  unit
     * @covers ::testXSendFile()
     */
    public function testTestXSendFile()
    {
        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->once())
            ->method('deliverXSendFileTestFile');

        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $fileHandler,
            $this->getFileObjectFactory()
        );

        $frontendRedirectController->testXSendFile();
    }
}
