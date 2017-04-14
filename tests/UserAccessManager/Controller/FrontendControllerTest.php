<?php
/**
 * FrontendControllerTest.php
 *
 * The FrontendControllerTest unit test class file.
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

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class FrontendControllerTest
 *
 * @package UserAccessManager\Controller
 */
class FrontendControllerTest extends UserAccessManagerTestCase
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
     * @covers \UserAccessManager\Controller\FrontendController::__construct()
     */
    public function testCanCreateInstance()
    {
        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\FrontendController', $frontendController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::enqueueStylesAndScripts()
     * @covers \UserAccessManager\Controller\FrontendController::registerStylesAndScripts()
     */
    public function testEnqueueStylesAndScripts()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('registerStyle')
            ->with(
                FrontendController::HANDLE_STYLE_LOGIN_FORM,
                'http://url/assets/css/uamLoginForm.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            );

        $wordpress->expects($this->once())
            ->method('enqueueStyle')
            ->with(FrontendController::HANDLE_STYLE_LOGIN_FORM);

        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('getUrlPath')
            ->will($this->returnValue('http://url/'));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $frontendController->enqueueStylesAndScripts();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::parseQuery()
     */
    public function testParseQuery()
    {
        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(4))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([3, 2, 1], [], [3], [2, 3, 5]));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Query $wpQuery
         */
        $wpQuery = $this->getMockBuilder('\WP_Query')->getMock();
        $wpQuery->query_vars = [];

        $frontendController->parseQuery($wpQuery);
        self::assertEquals($wpQuery, $wpQuery);

        $wpQuery->query_vars['suppress_filters'] = false;
        $frontendController->parseQuery($wpQuery);
        self::assertEquals($wpQuery, $wpQuery);

        $wpQuery->query_vars['suppress_filters'] = true;
        $frontendController->parseQuery($wpQuery);
        self::assertEquals([3, 2, 1], $wpQuery->query_vars['post__not_in']);

        $wpQuery->query_vars['post__not_in'] = [1, 1, 2, 4];
        $frontendController->parseQuery($wpQuery);
        self::assertEquals([1, 1, 2, 4], $wpQuery->query_vars['post__not_in']);

        $frontendController->parseQuery($wpQuery);
        self::assertEquals([1, 2, 3, 4], $wpQuery->query_vars['post__not_in'], '', 0.0, 10, true);

        $frontendController->parseQuery($wpQuery);
        self::assertEquals([1, 2, 3, 4, 5], $wpQuery->query_vars['post__not_in'], '', 0.0, 10, true);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::adminOutput()
     */
    public function testAdminOutput()
    {
        $wordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $adminUser
         */
        $adminUser = $this->getMockBuilder('\WP_User')->getMock();
        $adminUser->ID = 1;

        /**
         * @var \WP_User|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = 2;

        $wordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->onConsecutiveCalls(
                $user,
                $adminUser,
                $adminUser
            ));

        $config = $this->getConfig();

        $config->expects($this->exactly(6))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $config->expects($this->exactly(5))
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

        $config->expects($this->exactly(4))
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('hintText'));

        $util = $this->getUtil();

        $util->expects($this->once())
            ->method('endsWith')
            ->withConsecutive(
                ['text hintText', 'hintText']
            )
            ->will($this->onConsecutiveCalls(true));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(3))
            ->method('userIsAdmin')
            ->withConsecutive([2], [1], [1])
            ->will($this->returnCallback(function ($id) {
                return ($id === 1);
            }));

        $accessHandler->expects($this->exactly(2))
            ->method('getUserGroupsForObject')
            ->withConsecutive(
                ['objectType', 'objectId'],
                ['secondObjectType', 'secondObjectId']
            )
            ->will($this->onConsecutiveCalls(
                [],
                [$this->getUserGroup(1)]
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $util,
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('', $frontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $frontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $frontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $frontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('hintText', $frontendController->adminOutput('secondObjectType', 'secondObjectId'));
        self::assertEquals('', $frontendController->adminOutput(
            'secondObjectType',
            'secondObjectId',
            'text hintText'
        ));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getLoginFormHtml()
     */
    public function testGetLoginFormHtml()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('src', new Directory([
            'UserAccessManager' => new Directory([
                'View' => new Directory([
                    'LoginForm.php' => new File('<?php echo \'LoginForm\';')
                ])
            ])
        ]));

        $php = $this->getPhp();

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(2))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(true, false));

        $wordpress->expects($this->exactly(2))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_login_form', ''],
                ['uam_login_form', 'LoginForm']
            )
            ->will($this->onConsecutiveCalls('filter', 'LoginFormWithFilter'));


        $config = $this->getConfig();

        $config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $frontendController = new FrontendController(
            $php,
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $php->expects($this->once())
            ->method('includeFile')
            ->with($frontendController, 'vfs://src/UserAccessManager/View/LoginForm.php')
            ->will($this->returnCallback(function () {
                echo 'LoginForm';
            }));

        self::assertEquals('filter', $frontendController->getLoginFormHtml());
        self::assertEquals('LoginFormWithFilter', $frontendController->getLoginFormHtml());
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
     * @covers \UserAccessManager\Controller\FrontendController::showPosts()
     * @covers \UserAccessManager\Controller\FrontendController::showPages()
     * @covers \UserAccessManager\Controller\FrontendController::processPost()
     */
    public function testShowPostsAtAdminPanel()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('isFeed')
            ->will($this->onConsecutiveCalls(true, true, false, false));

        $config = $this->getConfig();

        $config->expects($this->exactly(2))
            ->method('protectFeed')
            ->will($this->onConsecutiveCalls(false, true));

        $config->expects($this->exactly(7))
            ->method('hidePostType')
            ->withConsecutive(
                ['post'],
                ['other'],
                ['post'],
                ['other'],
                ['post'],
                ['post'],
                ['other']
            )
            ->will($this->onConsecutiveCalls(
                false,
                true,
                false,
                true,
                false,
                true,
                false
            ));

        $config->expects($this->exactly(15))
            ->method('atAdminPanel')
            ->will($this->returnValue(true));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(11))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2],
                ['other', 3],
                ['post', 4],
                ['post', 1],
                ['post', 2],
                ['other', 3],
                ['post', 4],
                ['post', 1],
                ['page', 2],
                ['other', 3]
            )
            ->will($this->onConsecutiveCalls(
                false,
                true,
                false,
                false,
                true,
                true,
                false,
                false,
                false,
                true,
                false
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        $posts = [
            1 => $this->getPost(1),
            2 => $this->getPost(2),
            3 => $this->getPost(3, 'other'),
            4 => $this->getPost(4)
        ];

        $pages = [
            1 => $this->getPost(1),
            2 => $this->getPost(2, 'page'),
            3 => $this->getPost(3, 'other')
        ];


        self::assertEquals([], $frontendController->showPosts($posts));
        self::assertEquals([$this->getPost(2)], $frontendController->showPosts($posts));
        self::assertEquals([$this->getPost(1), $this->getPost(2)], $frontendController->showPosts($posts));
        self::assertEquals([], $frontendController->showPosts());
        self::assertEquals([], $frontendController->showPages());
        self::assertEquals([$this->getPost(2, 'page')], $frontendController->showPages($pages));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPosts()
     * @covers \UserAccessManager\Controller\FrontendController::showPages()
     * @covers \UserAccessManager\Controller\FrontendController::processPost()
     */
    public function testShowPosts()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('isFeed')
            ->will($this->returnValue(false));

        $wordpress->expects($this->exactly(5))
            ->method('isUserLoggedIn')
            ->will($this->returnValue(true));

        $wordpress->expects($this->exactly(5))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', '']
            )
            ->will($this->onConsecutiveCalls('', 'LoginForm', '', '', 'LoginForm'));

        $config = $this->getConfig();

        $config->expects($this->exactly(6))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['other'], ['post'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, false, true, false, false, false));

        $config->expects($this->exactly(12))
            ->method('atAdminPanel')
            ->will($this->returnValue(false));

        $config->expects($this->exactly(5))
            ->method('getPostTypeContent')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->returnValue('postContent'));

        $config->expects($this->exactly(2))
            ->method('showPostContentBeforeMore')
            ->will($this->onConsecutiveCalls(true, false));

        $config->expects($this->exactly(5))
            ->method('hidePostTypeTitle')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(true, false, true, false, true));

        $config->expects($this->exactly(3))
            ->method('getPostTypeTitle')
            ->withConsecutive(['post'], ['page'], ['other'])
            ->will($this->returnValue('postTitle'));


        $config->expects($this->exactly(5))
            ->method('hidePostTypeComments')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, true, true, false, false));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(7))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2],
                ['other', 3],
                ['post', 4],
                ['page', 1],
                ['post', 2],
                ['other', 3]
            )
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        $posts = [
            1 => $this->getPost(1),
            2 => $this->getPost(2),
            3 => $this->getPost(3, 'other'),
            4 => $this->getPost(4)
        ];

        $pages = [
            1 => $this->getPost(1, 'page'),
            2 => $this->getPost(2),
            3 => $this->getPost(3, 'other')
        ];


        self::assertEquals(
            [
                $this->getPost(1),
                $this->getPost(2, 'post', 'postTitle', '[LOGIN_FORM] content2 postContent'),
                $this->getPost(3, 'other', null, 'postContent', true)
            ],
            $frontendController->showPosts($posts)
        );
        self::assertEquals(
            [
                $this->getPost(1, 'page', 'postTitle', 'postContent', true),
                $this->getPost(2, 'post', null, 'postContent'),
                $this->getPost(3, 'other', 'postTitle', 'postContent')
            ],
            $frontendController->showPages($pages)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPostSql()
     */
    public function testShowPostSql()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([], [1 => 1], [1 => 1, 3 => 3]));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $database,
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('query', $frontendController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN (1) ', $frontendController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN (1, 3) ', $frontendController->showPostSql('query'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPostCount()
     */
    public function testShowPostCount()
    {
        $counts = new \stdClass();
        $counts->firstStatus = 3;
        $counts->secondStatus = 8;

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(false, true, true));

        $postTypeObject = new \stdClass();
        $postTypeObject->cap = new \stdClass();
        $postTypeObject->cap->read_private_posts = 'readPrivatePostsValue';

        $wordpress->expects($this->exactly(2))
            ->method('getPostTypeObject')
            ->will($this->returnValue($postTypeObject));

        $wordpress->expects($this->exactly(2))
            ->method('currentUserCan')
            ->with('readPrivatePostsValue')
            ->will($this->onConsecutiveCalls(true, false));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_User $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = 1;

        $wordpress->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $database = $this->getDatabase();

        $database->expects($this->exactly(4))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->exactly(4))
            ->method('getResults')
            ->with('preparedQuery', ARRAY_A)
            ->will($this->returnValue([
                ['post_status' => 'firstStatus', 'num_posts' => 2],
                ['post_status' => 'thirdStatus', 'num_posts' => 5],
            ]));

        $database->expects($this->exactly(5))
            ->method('prepare')
            ->withConsecutive(
                [
                    new MatchIgnoreWhitespace(
                        'SELECT post_status, COUNT(*) AS num_posts 
                        FROM postTable 
                        WHERE post_type = %s AND ID NOT IN (\'1\') GROUP BY post_status'
                    ),
                    'type'
                ],
                [
                    new MatchIgnoreWhitespace(
                        'SELECT post_status, COUNT(*) AS num_posts 
                        FROM postTable 
                        WHERE post_type = %s AND ID NOT IN (\'1\', \'3\') GROUP BY post_status'
                    ),
                    'type'
                ],
                [
                    new MatchIgnoreWhitespace(
                        'SELECT post_status, COUNT(*) AS num_posts 
                        FROM postTable 
                        WHERE post_type = %s AND ID NOT IN (\'1\', \'3\') GROUP BY post_status'
                    ),
                    'type'
                ],
                [
                    new MatchIgnoreWhitespace(
                        'AND (post_status != \'private\' OR (post_author = %d AND post_status = \'private\'))'
                    ),
                    1
                ],
                [
                    new MatchIgnoreWhitespace(
                        'SELECT post_status, COUNT(*) AS num_posts 
                        FROM postTable 
                        WHERE post_type = %s 
                          AND ID NOT IN (\'1\', \'3\')
                          AND (post_status != \'private\' OR (post_author = 1 AND post_status = \'private\'))
                        GROUP BY post_status'
                    ),
                    'type'
                ]
            )
            ->will($this->onConsecutiveCalls(
                'preparedQuery',
                'preparedQuery',
                'preparedQuery',
                ' AND (post_status != \'private\' OR (post_author = 1 AND post_status = \'private\')) ',
                'preparedQuery'
            ));

        $cache = $this->getCache();

        $cache->expects($this->exactly(6))
            ->method('getFromCache')
            ->with(FrontendController::POST_COUNTS_CACHE_KEY)
            ->will($this->onConsecutiveCalls('cachedResult', null, null, null, null, null));

        $cache->expects($this->exactly(5))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_COUNTS_CACHE_KEY, $counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $counts]
            );

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(5))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls(
                [],
                [1 => 1],
                [1 => 1, 3 => 3],
                [1 => 1, 3 => 3],
                [1 => 1, 3 => 3]
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $database,
            $this->getUtil(),
            $cache,
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('cachedResult', $frontendController->showPostCount($counts, 'type', 'perm'));
        self::assertEquals($counts, $frontendController->showPostCount($counts, 'type', 'perm'));

        $counts->firstStatus = 2;
        self::assertEquals($counts, $frontendController->showPostCount($counts, 'type', 'perm'));
        self::assertEquals($counts, $frontendController->showPostCount($counts, 'type', 'readable'));
        self::assertEquals($counts, $frontendController->showPostCount($counts, 'type', 'readable'));
        self::assertEquals($counts, $frontendController->showPostCount($counts, 'type', 'readable'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getTermArguments()
     */
    public function testGetTermArguments()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('parseIdList')
            ->with('3,4')
            ->will($this->returnValue([3, 4]));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('getExcludedTerms')
            ->will($this->returnValue([1, 3]));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        self::assertEquals(['exclude' => [1, 3]], $frontendController->getTermArguments([]));
        self::assertEquals(['exclude' => [3, 4, 1]], $frontendController->getTermArguments(['exclude' => '3,4']));
    }

    /**
     * @param int    $postId
     * @param string $content
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Comment
     */
    private function getComment($postId, $content = null)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Comment $comment
         */
        $comment = $this->getMockBuilder('\WP_Comment')->getMock();
        $comment->comment_post_ID = $postId;
        $comment->comment_content = ($content === null) ? "commentContent$postId" : $content;

        return $comment;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showComment()
     */
    public function testShowComment()
    {
        $config = $this->getConfig();

        $config->expects($this->exactly(4))
            ->method('hidePostTypeComments')
            ->withConsecutive(['post'], ['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false, false));

        $config->expects($this->exactly(3))
            ->method('hidePostType')
            ->withConsecutive(['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false));

        $config->expects($this->exactly(2))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false));

        $config->expects($this->once())
            ->method('getPostTypeCommentContent')
            ->with('post')
            ->will($this->returnValue('PostTypeCommentContent'));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(6))
            ->method('getPost')
            ->will($this->returnCallback(function ($postId) {
                $type = ($postId === 4) ? 'page' : 'post';
                return ($postId !== 2) ? $this->getPost($postId, $type) : false;
            }));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(5))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 3],
                ['page', 4],
                ['post', 5],
                ['post', 6]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false,
                false,
                false,
                false
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler()
        );

        $comments = [
            $this->getComment(1),
            $this->getComment(2),
            $this->getComment(3),
            $this->getComment(4),
            $this->getComment(5),
            $this->getComment(6)
        ];

        self::assertEquals(
            [
                $this->getComment(1),
                $this->getComment(2),
                $this->getComment(6, 'PostTypeCommentContent')
            ],
            $frontendController->showComment($comments)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showAncestors()
     */
    public function testShowAncestors()
    {
        $config = $this->getConfig();

        $config->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, true));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(5))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['objectType', 'objectId'],
                ['objectType', 'objectId'],
                ['objectType', 1],
                ['objectType', 2],
                ['objectType', 3]
            )
            ->will($this->onConsecutiveCalls(
                false,
                true,
                true,
                false,
                true
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        $ancestors = [
            1 => 1,
            2 => 2,
            3 => 3
        ];

        self::assertEquals([], $frontendController->showAncestors($ancestors, 'objectId', 'objectType'));
        self::assertEquals(
            [1 => 1, 3 => 3],
            $frontendController->showAncestors($ancestors, 'objectId', 'objectType')
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showNextPreviousPost()
     */
    public function testShowNextPreviousPost()
    {
        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls(
                [],
                [2],
                [2, 3, 5]
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('query', $frontendController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2) ', $frontendController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2, 3, 5) ', $frontendController->showNextPreviousPost('query'));
    }

    /**
     * @param int    $termId
     * @param string $taxonomy
     * @param string $name
     * @param int    $count
     * @param int    $parent
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Term
     */
    private function getTerm($termId, $taxonomy = 'taxonomy', $name = null, $count = 0, $parent = 0)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Term $term
         */
        $term = $this->getMockBuilder('\WP_Term')->getMock();
        $term->term_id = $termId;
        $term->taxonomy = $taxonomy;
        $term->name = ($name === null) ? "name{$termId}" : $name;
        $term->count = $count;
        $term->parent = $parent;

        return $term;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showTerm()
     * @covers \UserAccessManager\Controller\FrontendController::showTerms()
     * @covers \UserAccessManager\Controller\FrontendController::getVisibleElementsCount()
     * @covers \UserAccessManager\Controller\FrontendController::processTerm()
     */
    public function testShowTerm()
    {
        $wordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = 1;

        $wordpress->expects($this->exactly(1))
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $config = $this->getConfig();

        $config->expects($this->exactly(8))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, false, false, true, true, false, true, true));

        $config->expects($this->exactly(14))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(
                false,
                true,
                false,
                true,
                false,
                true,
                false,
                true,
                false,
                true,
                true,
                true,
                true,
                true
            ));

        $config->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $config->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $config->expects($this->exactly(5))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['post'], ['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $config->expects($this->exactly(4))
            ->method('hideEmptyTaxonomy')
            ->withConsecutive(['taxonomy'], ['taxonomy'], ['taxonomy'], ['taxonomy'])
            ->will($this->onConsecutiveCalls(false, true, true, false));

        $util = $this->getUtil();

        $util->expects($this->once())
            ->method('endsWith')
            ->with('name1', 'BlogAdminHintText')
            ->will($this->returnValue(false));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(8))
            ->method('getTermTreeMap')
            ->will($this->returnValue(
                [
                    ObjectHandler::TREE_MAP_CHILDREN => [
                        'taxonomy' => [
                            1 => [2 => 2, 3 => 3]
                        ]
                    ]
                ]
            ));

        $objectHandler->expects($this->exactly(8))
            ->method('getTermPostMap')
            ->will($this->returnValue(
                [
                    1 => [10 => 'post', 11 => 'post', 12 => 'page'],
                    2 => [13 => 'post']
                ]
            ));

        $objectHandler->expects($this->exactly(5))
            ->method('getTerm')
            ->will($this->returnCallback(function ($termId) {
                if ($termId === 104) {
                    return false;
                } elseif ($termId >= 105) {
                    return $this->getTerm($termId, 'taxonomy', null, 0, ($termId - 1));
                }

                return $this->getTerm($termId);
            }));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(15))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['taxonomy', 1],
                ['taxonomy', 1],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 11],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 12],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 13],
                ['taxonomy', 107],
                ['taxonomy', 106],
                ['taxonomy', 105],
                ['taxonomy', 105],
                ['taxonomy', 10],
                ['taxonomy', 11],
                ['taxonomy', 12],
                ['taxonomy', 2],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 13],
                ['taxonomy', 50]
            )
            ->will($this->onConsecutiveCalls(
                false,
                true,
                false,
                true,
                true,
                true,
                false,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true
            ));

        $accessHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));

        $accessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('taxonomy', 1)
            ->will($this->returnValue([1, 2]));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $util,
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler()
        );

        /**
         * @var \WP_Term $fakeTerm
         */
        $fakeTerm = new \stdClass();
        self::assertEquals($fakeTerm, $frontendController->showTerm($fakeTerm));

        $term = $this->getTerm(1);
        self::assertEquals(null, $frontendController->showTerm($term));
        self::assertEquals(
            $this->getTerm(1, 'taxonomy', 'name1BlogAdminHintText', 3),
            $frontendController->showTerm($term)
        );

        $term = $this->getTerm(107, 'taxonomy', null, 0, 106);
        self::assertEquals($this->getTerm(107, 'taxonomy', null, 0, 105), $frontendController->showTerm($term));

        $term = $this->getTerm(105, 'taxonomy', null, 0, 104);
        self::assertEquals($this->getTerm(105, 'taxonomy', null, 0, 104), $frontendController->showTerm($term));

        $terms = [
            1 => new \stdClass(),
            0 => 0,
            10 => 10,
            11 => $this->getTerm(11),
            12 => $this->getTerm(12),
            2 => $this->getTerm(2),
            50 => 50
        ];
        self::assertEquals(
            [
                1 => new \stdClass(),
                12 => $this->getTerm(12),
                11 => $this->getTerm(11),
                2 => $this->getTerm(2, 'taxonomy', null, 1),
                50 => 50
            ],
            $frontendController->showTerms($terms)
        );
    }

    /**
     * @param string $objectType
     * @param string $objectId
     * @param string $title
     *
     * @return \stdClass
     */
    private function getItem($objectType, $objectId, $title = null)
    {
        $item = new \stdClass();
        $item->object = $objectType;
        $item->object_id = $objectId;
        $item->title = ($title === null) ? "title{$objectId}" : $title;

        return $item;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showCustomMenu()
     */
    public function testShowCustomMenu()
    {
        $wordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = 1;

        $wordpress->expects($this->exactly(1))
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $config = $this->getConfig();

        $config->expects($this->exactly(14))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(
                false,
                true,
                true,
                false,
                true,
                true,
                true,
                true,
                true,
                true,
                false,
                true,
                true,
                true
            ));

        $config->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $config->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $config->expects($this->exactly(3))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(false, false, true));

        $config->expects($this->once())
            ->method('hidePostTypeTitle')
            ->with('post')
            ->will($this->returnValue(true));

        $config->expects($this->once())
            ->method('getPostTypeTitle')
            ->with('post')
            ->will($this->returnValue('PostTypeTitle'));

        $config->expects($this->once())
            ->method('hideEmptyTaxonomy')
            ->with('taxonomy')
            ->will($this->returnValue(true));

        $config->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->returnValue(true));

        $util = $this->getUtil();

        $util->expects($this->once())
            ->method('endsWith')
            ->with('title1', 'BlogAdminHintText')
            ->will($this->returnValue(false));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(8))
            ->method('isPostType')
            ->withConsecutive(
                ['other'],
                ['post'],
                ['post'],
                ['post'],
                ['post'],
                ['taxonomy'],
                ['taxonomy'],
                ['taxonomy']
            )
            ->will($this->returnCallback(function ($type) {
                return ($type === 'post');
            }));

        $objectHandler->expects($this->exactly(4))
            ->method('isTaxonomy')
            ->withConsecutive(['other'], ['taxonomy'], ['taxonomy'], ['taxonomy'])
            ->will($this->returnCallback(function ($type) {
                return ($type === 'taxonomy');
            }));


        $objectHandler->expects($this->exactly(2))
            ->method('getTermTreeMap')
            ->will($this->returnValue([]));

        $objectHandler->expects($this->exactly(2))
            ->method('getTermPostMap')
            ->will($this->returnValue([]));

        $objectHandler->expects($this->exactly(3))
            ->method('getTerm')
            ->will($this->returnCallback(function ($termId) {
                return $this->getTerm($termId);
            }));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(7))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2],
                ['post', 3],
                ['post', 4],
                ['taxonomy', 1],
                ['taxonomy', 2],
                ['taxonomy', 3]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false,
                false,
                false,
                false,
                true,
                true
            ));

        $accessHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));

        $accessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('other', 1)
            ->will($this->returnValue([1, 2]));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $util,
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler()
        );

        $items = [
            1 => $this->getItem('other', 1),
            2 => $this->getItem('post', 1),
            3 => $this->getItem('post', 2),
            4 => $this->getItem('post', 3),
            5 => $this->getItem('post', 4),
            6 => $this->getItem('taxonomy', 1),
            7 => $this->getItem('taxonomy', 2),
            8 => $this->getItem('taxonomy', 3)
        ];

        self::assertEquals(
            [
                1 => $this->getItem('other', 1, 'title1BlogAdminHintText'),
                2 => $this->getItem('post', 1),
                3 => $this->getItem('post', 2, 'PostTypeTitle'),
                8 => $this->getItem('taxonomy', 3)
            ],
            $frontendController->showCustomMenu($items)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showGroupMembership()
     */
    public function testShowGroupMembership()
    {
        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(3))
            ->method('getFilteredUserGroupsForObject')
            ->with(ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1)
            ->will($this->onConsecutiveCalls(
                [],
                [
                    $this->getUserGroup(2)
                ],
                [
                    $this->getUserGroup(1, true, false, [''], 'none', 'none', [], [], '<a>test</a>'),
                    $this->getUserGroup(2)
                ]
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('link', $frontendController->showGroupMembership('link', 1));
        self::assertEquals(
            'link | '.TXT_UAM_ASSIGNED_GROUPS.': name2',
            $frontendController->showGroupMembership('link', 1)
        );
        self::assertEquals(
            'link | '.TXT_UAM_ASSIGNED_GROUPS.': &lt;a&gt;test&lt;/a&gt;, name2',
            $frontendController->showGroupMembership('link', 1)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showLoginForm()
     */
    public function testShowLoginForm()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('isSingle')
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $wordpress->expects($this->exactly(2))
            ->method('isPage')
            ->will($this->onConsecutiveCalls(false, true));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertTrue($frontendController->showLoginForm());
        self::assertFalse($frontendController->showLoginForm());
        self::assertTrue($frontendController->showLoginForm());
        self::assertTrue($frontendController->showLoginForm());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getLoginUrl()
     */
    public function testGetLoginUrl()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getBlogInfo')
            ->with('wpurl')
            ->will($this->returnValue('BlogInfo'));

        $wordpress->expects($this->once())
            ->method('applyFilters')
            ->with('uam_login_form_url', 'BlogInfo/wp-login.php')
            ->will($this->returnValue('filter'));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('filter', $frontendController->getLoginUrl());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getRedirectLoginUrl()
     */
    public function testGetRedirectLoginUrl()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getBlogInfo')
            ->with('wpurl')
            ->will($this->returnValue('BlogInfo'));

        $wordpress->expects($this->once())
            ->method('applyFilters')
            ->with('uam_login_url', 'BlogInfo/wp-login.php?redirect_to=uri%40')
            ->will($this->returnValue('filter'));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $_SERVER['REQUEST_URI'] = 'uri@';

        self::assertEquals('filter', $frontendController->getRedirectLoginUrl());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getUserLogin()
     */
    public function testGetUserLogin()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('escHtml')
            ->with('/log/')
            ->will($this->returnValue('escHtml'));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $_GET['log'] = '/log\/';

        self::assertEquals('escHtml', $frontendController->getUserLogin());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getPostIdByUrl()
     */
    public function testGetPostIdByUrl()
    {
        $cache = $this->getCache();

        $cache->expects($this->exactly(6))
            ->method('getFromCache')
            ->with(FrontendController::POST_URL_CACHE_KEY)
            ->will($this->onConsecutiveCalls(
                null,
                null,
                null,
                null,
                null,
                ['url/part' => 1]
            ));

        $cache->expects($this->exactly(4))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_URL_CACHE_KEY, ['url/part' => 1]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-e123/part' => 2]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-123x321/part' => 3]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-e123-123x321/part' => 4]]
            );

        $database = $this->getDatabase();

        $database->expects($this->exactly(5))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->exactly(5))
            ->method('prepare')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT ID
                    FROM postTable
                    WHERE guid = \'%s\' 
                    LIMIT 1'
                ),
                'url/part'
            )
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->exactly(5))
            ->method('getRow')
            ->with('preparedQuery')
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1),
                $this->getPost(2),
                $this->getPost(3),
                $this->getPost(4)
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $database,
            $this->getUtil(),
            $cache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals(null, $frontendController->getPostIdByUrl('url/part'));
        self::assertEquals(1, $frontendController->getPostIdByUrl('url/part'));
        self::assertEquals(2, $frontendController->getPostIdByUrl('url-e123/part'));
        self::assertEquals(3, $frontendController->getPostIdByUrl('url-123x321/part'));
        self::assertEquals(4, $frontendController->getPostIdByUrl('url-e123-123x321/part'));
        self::assertEquals(1, $frontendController->getPostIdByUrl('url/part'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getFile()
     * @covers \UserAccessManager\Controller\FrontendController::getFileSettingsByType()
     */
    public function testGetFile()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(5))
            ->method('getUploadDir')
            ->will($this->returnValue([
                'basedir' => '/baseDirectory/file/pictures/',
                'baseurl' => 'http://baseUrl/file/pictures/'
            ]));

        $wordpress->expects($this->exactly(3))
            ->method('attachmentIsImage')
            ->will($this->onConsecutiveCalls(false, true, false));

        $wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS);

        $config = $this->getConfig();

        $config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('realPath/'));

        $cache = $this->getCache();

        $cache->expects($this->exactly(5))
            ->method('getFromCache')
            ->with(FrontendController::POST_URL_CACHE_KEY)
            ->will($this->returnValue(['http://baseUrl/file/pictures/url' => 1]));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(5))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE)
            ));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(3))
            ->method('checkObjectAccess')
            ->withConsecutive(
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1]
            )
            ->will($this->onConsecutiveCalls(false, false, true));

        $fileHandler = $this->getFileHandler();

        $fileHandler->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                ['realPath/gfx/noAccessPic.png', true],
                ['/baseDirectory/file/pictures/url', false]
            )
            ->will($this->returnValue('getFile'));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $objectHandler,
            $accessHandler,
            $fileHandler
        );

        self::assertEquals(null, $frontendController->getFile('type', 'url'));
        self::assertEquals(null, $frontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals(null, $frontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals(null, $frontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals('getFile', $frontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals('getFile', $frontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::redirectUser()
     */
    public function testRedirectUser()
    {
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

        $config = $this->getConfig();

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

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $util,
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler()
        );

        $frontendController->redirectUser();
        $frontendController->redirectUser(false);
        $frontendController->redirectUser(false);
        $frontendController->redirectUser(false);
        $frontendController->redirectUser(false);
        $frontendController->redirectUser(false);
        $frontendController->redirectUser(false);
        $frontendController->redirectUser(false);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::redirect()
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

        $config = $this->getConfig();

        $config->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false, false, false, false));

        $config->expects($this->exactly(9))
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
            ->will($this->returnValue(['post', 'page', 'other']));

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

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $database,
            $util,
            $this->getCache(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler()
        );

        $pageParams = new \stdClass();

        $_GET['uamfiletype'] = 'fileType';
        $pageParams->query_vars = [];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $pageParams->query_vars = [];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $pageParams->query_vars = [];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $pageParams->query_vars = ['p' => 'pValue'];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        unset($_GET['uamfiletype']);
        $_GET['uamgetfile'] = 'file';
        $pageParams->query_vars = ['page_id' => 'pageIdValue'];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $pageParams->query_vars = ['cat_id' => 'catIdValue'];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $pageParams->query_vars = ['name' => 'nameValue'];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $pageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $pageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));

        $_GET['uamfiletype'] = 'fileType';
        self::assertEquals('header', $frontendController->redirect('header', $pageParams));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getFileUrl()
     */
    public function testGetFileUrl()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('homeUrl'));

        $config = $this->getConfig();

        $config->expects($this->exactly(6))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $config->expects($this->exactly(5))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $config->expects($this->exactly(3))
            ->method('getLockedFileTypes')
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

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $objectHandler,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('url', $frontendController->getFileUrl('url', 1));
        self::assertEquals('url', $frontendController->getFileUrl('url', 1));
        self::assertEquals('url', $frontendController->getFileUrl('url', 1));
        self::assertEquals('url', $frontendController->getFileUrl('url', 1));
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $frontendController->getFileUrl('url', 1)
        );
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $frontendController->getFileUrl('url', 1)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::cachePostLinks()
     */
    public function testCachePostLinks()
    {
        $cache = $this->getCache();

        $cache->expects($this->exactly(2))
            ->method('getFromCache')
            ->with(FrontendController::POST_URL_CACHE_KEY)
            ->will($this->onConsecutiveCalls(
                null,
                ['firstUrl' => 1]
            ));

        $cache->expects($this->exactly(2))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_URL_CACHE_KEY, ['firstUrl' => 1]],
                [FrontendController::POST_URL_CACHE_KEY, ['firstUrl' => 1, 'secondUrl' => 2]]
            );

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $cache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('firstUrl', $frontendController->cachePostLinks('firstUrl', $this->getPost(1)));
        self::assertEquals('secondUrl', $frontendController->cachePostLinks('secondUrl', $this->getPost(2)));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getWpSeoUrl()
     */
    public function testGetWpSeoUrl()
    {
        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['type', 1],
                ['type', 1]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler()
        );

        $object = new \stdClass();
        $object->ID = 1;

        self::assertEquals('url', $frontendController->getWpSeoUrl('url', 'type', $object));
        self::assertFalse($frontendController->getWpSeoUrl('url', 'type', $object));
    }
}
