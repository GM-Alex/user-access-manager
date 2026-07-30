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

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;
use UserAccessManager\Controller\Frontend\RedirectController;
use UserAccessManager\File\FileObject;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroupTypeException;
use Vfs\FileSystem;
use WP_Post;
use WP_Query;

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
    private FileSystem $root;

    /**
     * Setup virtual file system.
     */
    protected function setUp(): void
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    protected function tearDown(): void
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
     * @throws ReflectionException
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
     * @return MockObject|WP_Post
     */
    private function getPost(
        int $id,
        ?string $postType = 'post',
        ?string $title = null,
        ?string $content = null,
        bool $closed = false,
        string $postMimeType = 'post/mime/type'
    ): MockObject|WP_Post
    {
        /**
         * @var MockObject|WP_Post $post
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

        $wordpress->expects($this->exactly(7))
            ->method('attachmentUrlToPostId')
            ->withConsecutive(
                ['url/part.ext'],
                ['url/part-scaled.ext'],
                ['url/part'],
                ['url-e123/part'],
                ['url/part'],
                ['url-e123/part'],
                ['url/part.pdf']
            )
            ->will($this->onConsecutiveCalls(0, 0, 1, 2, 3, 4, 5, 1));

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
                [RedirectController::POST_URL_CACHE_KEY, ['url/part.ext' => 0]],
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

        self::assertEquals(0, $frontendRedirectController->getPostIdByUrl('url/part.ext'));
        self::assertEquals(1, $frontendRedirectController->getPostIdByUrl('url/part'));
        self::assertEquals(2, $frontendRedirectController->getPostIdByUrl('url-e123/part'));
        self::assertEquals(3, $frontendRedirectController->getPostIdByUrl('url-123x321_z/part'));
        self::assertEquals(4, $frontendRedirectController->getPostIdByUrl('url-e123-123x321/part'));
        self::assertEquals(5, $frontendRedirectController->getPostIdByUrl('url/part-pdf.jpg'));
        self::assertEquals(1, $frontendRedirectController->getPostIdByUrl('url/part'));
    }

    // The trailing slash on the base url makes the rtrim in normalizeAttachmentUrl observable.
    private const UPLOAD_DIRS = [
        'basedir' => ABSPATH . 'baseDirectory/file/pictures',
        'baseurl' => 'http://baseUrl/file/pictures/'
    ];
    private const ATTACHMENT_FILE = ABSPATH . 'baseDirectory/file/pictures/foo/picture.png';

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::getFileSettingsByType()
     * @throws UserGroupTypeException
     */
    public function testGetFileWithCustomType()
    {
        $wordpress = $this->getWordpress();

        $fileObject = $this->createMock(FileObject::class);
        $fileObject->method('getId')->will($this->returnValue('1'));
        $fileObject->method('getType')->will($this->returnValue('type'));
        $fileObject->method('getFile')->will($this->returnValue('file'));
        $fileObject->method('isImage')->will($this->returnValue(false));

        $wordpress->expects($this->exactly(2))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_get_file_settings_by_type', null, 'customType', 'url', null],
                ['uam_get_file_settings_by_type', null, 'customType', 'url', 'extra']
            )
            ->will($this->onConsecutiveCalls(null, $fileObject));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->once())
            ->method('checkObjectAccess')
            ->with('type', 1)
            ->will($this->returnValue(true));

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->once())
            ->method('getFile')
            ->with('file', false);

        $frontendRedirectController = new RedirectController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $fileHandler,
            $this->getFileObjectFactory()
        );

        $frontendRedirectController->getFile('customType', 'url');
        $_GET['uamextra'] = 'extra';
        $frontendRedirectController->getFile('customType', 'url');
    }

    /**
     * Prepares the collaborators so that an attachment url resolves to attachment 1 and its stored file.
     */
    private function getAttachmentRedirectController(
        MockObject $accessHandler,
        MockObject $fileHandler,
        MockObject $mainConfig,
        bool $isImage
    ): RedirectController {
        $wordpress = $this->getWordpress();
        $wordpress->method('getUploadDir')->will($this->returnValue(self::UPLOAD_DIRS));
        $wordpress->method('attachmentUrlToPostId')
            ->with('http://baseUrl/file/pictures/foo/picture.png')
            ->will($this->returnValue(1));
        $wordpress->method('getAttachedFile')->with(1)->will($this->returnValue(self::ATTACHMENT_FILE));
        $wordpress->method('attachmentIsImage')->with(1)->will($this->returnValue($isImage));

        $php = $this->getPhp();
        $php->method('realpath')->will($this->returnCallback(fn (string $path) => $path));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->method('getRealPath')->will($this->returnValue('realPath/'));

        $cache = $this->getCache();
        $cache->method('getFromRuntimeCache')->will($this->returnValue(null));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->method('getPost')->with(1)
            ->will($this->returnValue($this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE)));

        $fileObjectFactory = $this->getFileObjectFactory();
        $fileObjectFactory->method('createFileObject')
            ->will($this->returnCallback(function ($id, $type, $file, $isImage) {
                $fileObject = $this->createMock(FileObject::class);
                $fileObject->method('getId')->will($this->returnValue($id));
                $fileObject->method('getType')->will($this->returnValue($type));
                $fileObject->method('getFile')->will($this->returnValue($file));
                $fileObject->method('isImage')->will($this->returnValue($isImage));

                return $fileObject;
            }));

        return new RedirectController(
            $php,
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
    }

    /**
     * The delivered path comes from the resolved attachment, not from the request.
     *
     * @group  unit
     * @covers ::getFile()
     * @covers ::getFileSettingsByType()
     * @covers ::getAttachmentFileObject()
     * @covers ::normalizeAttachmentUrl()
     * @covers ::isInsideUploadDirectory()
     * @throws UserGroupTypeException
     */
    public function testGetFileDeliversTheAttachmentsOwnFile()
    {
        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->once())
            ->method('checkObjectAccess')
            ->with(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1)
            ->will($this->returnValue(true));

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->once())
            ->method('getFile')
            ->with(self::ATTACHMENT_FILE, true);

        $controller = $this->getAttachmentRedirectController(
            $accessHandler,
            $fileHandler,
            $this->getMainConfig(),
            true
        );
        $controller->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/foo/picture.png');
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::getAttachmentFileObject()
     * @covers ::normalizeAttachmentUrl()
     * @throws UserGroupTypeException
     */
    public function testGetFileFallsBackToTheNoAccessImage()
    {
        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->with(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1)
            ->will($this->returnValue(false));

        $mainConfig = $this->getMainConfig();
        $mainConfig->method('getNoAccessImageType')->will($this->onConsecutiveCalls('default', 'custom'));
        $mainConfig->method('getCustomNoAccessImage')->will($this->returnValue('customImage.jpg'));

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                ['realPath/assets/gfx/noAccessPic.png', true],
                ['customImage.jpg', true]
            );

        $controller = $this->getAttachmentRedirectController($accessHandler, $fileHandler, $mainConfig, true);
        $controller->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/foo/picture.png');
        $controller->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/foo/picture.png');
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @throws UserGroupTypeException
     */
    public function testGetFileRefusesANonImageWithoutAccess()
    {
        $wordpress = $this->getWordpress();
        $wordpress->method('getUploadDir')->will($this->returnValue(self::UPLOAD_DIRS));
        $wordpress->method('attachmentUrlToPostId')->will($this->returnValue(1));
        $wordpress->method('getAttachedFile')->with(1)->will($this->returnValue(self::ATTACHMENT_FILE));
        $wordpress->method('attachmentIsImage')->with(1)->will($this->returnValue(false));
        $wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);

        $php = $this->getPhp();
        $php->method('realpath')->will($this->returnCallback(fn (string $path) => $path));

        $cache = $this->getCache();
        $cache->method('getFromRuntimeCache')->will($this->returnValue(null));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->method('getPost')->with(1)
            ->will($this->returnValue($this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE)));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->method('checkObjectAccess')->will($this->returnValue(false));

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->never())->method('getFile');

        $fileObjectFactory = $this->getFileObjectFactory();
        $fileObjectFactory->method('createFileObject')
            ->will($this->returnCallback(function ($id, $type, $file, $isImage) {
                $fileObject = $this->createMock(FileObject::class);
                $fileObject->method('getId')->will($this->returnValue($id));
                $fileObject->method('getType')->will($this->returnValue($type));
                $fileObject->method('getFile')->will($this->returnValue($file));
                $fileObject->method('isImage')->will($this->returnValue($isImage));

                return $fileObject;
            }));

        $frontendRedirectController = new RedirectController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $objectHandler,
            $accessHandler,
            $fileHandler,
            $fileObjectFactory
        );

        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/foo/picture.png');
    }

    private const UPLOAD_BASE_DIR = ABSPATH . 'baseDirectory/file/pictures';

    /**
     * @return array<string, array{int, string, string|false, string|false}>
     */
    public function attachmentRejectionProvider(): array
    {
        return [
            'unresolvable url falls back to no attachment' => [0, 'post', self::ATTACHMENT_FILE, self::ATTACHMENT_FILE],
            'resolved post is not an attachment' => [1, 'post', self::ATTACHMENT_FILE, self::ATTACHMENT_FILE],
            'attachment has no stored file' => [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, false, false],
            'stored file does not resolve' =>
                [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, self::ATTACHMENT_FILE, false],
            'stored file is outside the uploads directory' =>
                [1, ObjectHandler::ATTACHMENT_OBJECT_TYPE, ABSPATH . 'wp-config.php', ABSPATH . 'wp-config.php'],
            'stored file only shares the uploads prefix' =>
                [
                    1,
                    ObjectHandler::ATTACHMENT_OBJECT_TYPE,
                    self::UPLOAD_BASE_DIR . '-evil/x.png',
                    self::UPLOAD_BASE_DIR . '-evil/x.png'
                ]
        ];
    }

    /**
     * No attachment object, no unresolved, escaping or prefix-sharing path may reach the file handler.
     *
     * @group        unit
     * @dataProvider attachmentRejectionProvider
     * @covers ::getFile()
     * @covers ::getAttachmentFileObject()
     * @covers ::normalizeAttachmentUrl()
     * @covers ::isInsideUploadDirectory()
     * @throws UserGroupTypeException
     */
    public function testGetFileRejectsInvalidAttachments(int $postId, string $postType, $attachedFile, $realFile)
    {
        $wordpress = $this->getWordpress();
        $wordpress->method('getUploadDir')->will($this->returnValue(self::UPLOAD_DIRS));
        $wordpress->method('attachmentUrlToPostId')->will($this->returnValue($postId));
        $wordpress->method('getAttachedFile')->will($this->returnValue($attachedFile));

        $php = $this->getPhp();
        $php->method('realpath')->will($this->returnCallback(function (string $path) use ($attachedFile, $realFile) {
            return $path === $attachedFile ? $realFile : $path;
        }));

        $cache = $this->getCache();
        $cache->method('getFromRuntimeCache')->will($this->returnValue(null));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->method('getPost')
            ->will($this->returnValue($this->getPost(max($postId, 1), $postType)));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->never())->method('checkObjectAccess');

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->never())->method('getFile');

        $frontendRedirectController = new RedirectController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $objectHandler,
            $accessHandler,
            $fileHandler,
            $this->getFileObjectFactory()
        );

        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/foo/x.png');
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::getAttachmentFileObject()
     * @covers ::normalizeAttachmentUrl()
     * @covers ::isInsideUploadDirectory()
     * @throws UserGroupTypeException
     */
    public function testGetFileRejectsWhenTheUploadDirectoryDoesNotResolve()
    {
        // An absolute file path so a missing base directory can not degrade the containment check into a
        // plain "starts with a slash" test.
        $attachedFile = DIRECTORY_SEPARATOR . 'real' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'x.png';

        $wordpress = $this->getWordpress();
        $wordpress->method('getUploadDir')->will($this->returnValue(self::UPLOAD_DIRS));
        $wordpress->method('attachmentUrlToPostId')->will($this->returnValue(1));
        $wordpress->method('getAttachedFile')->will($this->returnValue($attachedFile));

        $php = $this->getPhp();
        $php->method('realpath')->will($this->returnCallback(function (string $path) {
            return $path === self::UPLOAD_DIRS['basedir'] ? false : $path;
        }));

        $cache = $this->getCache();
        $cache->method('getFromRuntimeCache')->will($this->returnValue(null));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->method('getPost')
            ->will($this->returnValue($this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE)));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->never())->method('checkObjectAccess');

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->never())->method('getFile');

        $frontendRedirectController = new RedirectController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $objectHandler,
            $accessHandler,
            $fileHandler,
            $this->getFileObjectFactory()
        );

        $frontendRedirectController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, '/foo/x.png');
    }

    /**
     * @group  unit
     * @covers ::redirectUser()
     * @covers ::getRedirectUrlAndPermalink()
     * @throws UserGroupTypeException
     */
    public function testRedirectUser()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(4))
            ->method('callExit');

        /**
         * @var MockObject|WP_Query $wpQuery
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

        $wordpress->expects($this->exactly(4))
            ->method('wpRedirect')
            ->withConsecutive(['guid'], ['RedirectCustomUrl'], ['LoginUrl'], ['HomeUrl']);

        $wordpress->expects($this->once())
            ->method('wpLoginUrl')
            ->with('requestUri')
            ->will($this->returnValue('LoginUrl'));

        $config = $this->getMainConfig();

        $config->expects($this->exactly(8))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls(
                'custom_page',
                'custom_page',
                'custom_page',
                'custom_page',
                'custom_url',
                'login',
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

        $util->expects($this->exactly(8))
            ->method('getCurrentUrl')
            ->will($this->onConsecutiveCalls(
                'currentUrl',
                'guid',
                'PageLink',
                'currentUrl',
                'currentUrl',
                'currentUrl',
                'LoginUrl',
                'HomeUrl'
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

        $_SERVER['REQUEST_URI'] = 'requestUri';
        $frontendRedirectController->redirectUser();
        $frontendRedirectController->redirectUser(false);
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
     * @covers ::redirectUser()
     * @throws UserGroupTypeException
     */
    public function testRedirectUserAppendsRequestedUrlAsRedirectToParameter()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(2))
            ->method('callExit');

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('HomeUrl'));

        $wordpress->expects($this->once())
            ->method('addQueryArg')
            ->with(['redirect_to' => 'currentUrl'], 'HomeUrl')
            ->will($this->returnValue('HomeUrl?redirect_to=currentUrl'));

        $wordpress->expects($this->exactly(2))
            ->method('wpRedirect')
            ->withConsecutive(['HomeUrl?redirect_to=currentUrl'], ['HomeUrl']);

        $config = $this->getMainConfig();
        $config->expects($this->exactly(2))
            ->method('appendRedirectToParameter')
            ->will($this->onConsecutiveCalls(true, false));

        $util = $this->getUtil();
        $util->expects($this->exactly(2))
            ->method('getCurrentUrl')
            ->will($this->returnValue('currentUrl'));

        $frontendRedirectController = new RedirectController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $config,
            $this->getDatabase(),
            $util,
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
    }

    /**
     * @group  unit
     * @covers ::redirectUser()
     * @covers ::getRedirectUrlAndPermalink()
     * @throws UserGroupTypeException
     */
    public function testRedirectUserToOrigin()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(2))
            ->method('callExit');

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(2))
            ->method('getReferer')
            ->will($this->onConsecutiveCalls('RefererUrl', false));

        $wordpress->expects($this->once())
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('HomeUrl'));

        $wordpress->expects($this->exactly(2))
            ->method('wpRedirect')
            ->withConsecutive(['RefererUrl'], ['HomeUrl']);

        $config = $this->getMainConfig();
        $config->expects($this->exactly(2))
            ->method('getRedirect')
            ->will($this->returnValue('origin'));

        $util = $this->getUtil();
        $util->expects($this->exactly(2))
            ->method('getCurrentUrl')
            ->will($this->returnValue('currentUrl'));

        $frontendRedirectController = new RedirectController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $config,
            $this->getDatabase(),
            $util,
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getFileObjectFactory()
        );

        $frontendRedirectController->redirectUser(false);
        $frontendRedirectController->redirectUser(false);
    }

    /**
     * @group  unit
     * @covers ::redirect()
     * @covers ::extractObjectTypeAndId()
     * @covers ::getPostIdByName()
     * @throws UserGroupTypeException
     */
    public function testRedirect()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue(''));

        $wordpress->expects($this->exactly(2))
            ->method('getPageByPath')
            ->with('pageNameValue')
            ->will($this->onConsecutiveCalls(null, $this->getPost(2)));

        $wordpress->expects($this->exactly(1))
            ->method('applyFilters');

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
                ['', 0],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 'pValue'],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 'pageIdValue'],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'catIdValue'],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1],
                ['', 0],
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

        $pageParams = new stdClass();

        $_GET['uamfiletype'] = 'fileType';
        $pageParams->query_vars = [];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $pageParams->query_vars = [];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $pageParams->query_vars = [];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $pageParams->query_vars = ['p' => 'pValue'];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        unset($_GET['uamfiletype']);
        $_GET['uamgetfile'] = 'file';
        $pageParams->query_vars = ['page_id' => 'pageIdValue'];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $pageParams->query_vars = ['cat_id' => 'catIdValue'];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $pageParams->query_vars = ['name' => 'nameValue'];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $pageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $pageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));

        $_GET['uamfiletype'] = 'fileType';
        self::assertEquals(['headers'], $frontendRedirectController->redirect(['headers'], $pageParams));
    }

    /**
     * @group  unit
     * @covers ::getFileUrl()
     */
    public function testGetFileUrl()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(3))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('homeUrl'));

        $wordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false));

        $wordpress->expects($this->exactly(6))
            ->method('gotModRewrite')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $wordpressConfig = $this->getWordpressConfig();

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(8))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true, true, true));

        $mainConfig->expects($this->exactly(4))
            ->method('getLockedFiles')
            ->will($this->onConsecutiveCalls('none', 'type', 'all', 'aaa,mime'));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(5))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                false,
                $this->getPost(1, 'post', null, null, false, 'type'),
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
        self::assertEquals('url', $frontendRedirectController->getFileUrl('url', 1));
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $frontendRedirectController->getFileUrl('url', 1)
        );
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
