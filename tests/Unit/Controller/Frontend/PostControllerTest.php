<?php
/**
 * PostControllerTest.php
 *
 * The PostControllerTest unit test class file.
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
use UserAccessManager\Controller\Frontend\PostController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_Comment;
use WP_Post;
use WP_Post_Type;
use WP_Query;
use WP_User;

/**
 * Class PostControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\PostController
 */
class PostControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $frontendPostController = new PostController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf(PostController::class, $frontendPostController);
    }

    /**
     * @group  unit
     * @covers ::parseQuery()
     * @covers ::filtersSuppressed()
     * @throws UserGroupTypeException
     */
    public function testParseQuery()
    {
        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(4))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([3, 2, 1], [], [3], [2, 3, 5]));

        $frontendPostController = new PostController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
        );

        /**
         * @var MockObject|WP_Query $wpQuery
         */
        $wpQuery = $this->getMockBuilder('\WP_Query')->getMock();
        $wpQuery->query_vars = [];

        $frontendPostController->parseQuery($wpQuery);
        self::assertEquals($wpQuery, $wpQuery);

        $wpQuery->query_vars['suppress_filters'] = false;
        $frontendPostController->parseQuery($wpQuery);
        self::assertEquals($wpQuery, $wpQuery);

        $wpQuery->query_vars['suppress_filters'] = true;
        $frontendPostController->parseQuery($wpQuery);
        self::assertEquals([3, 2, 1], $wpQuery->query_vars['post__not_in']);

        $wpQuery->query_vars['post__not_in'] = [1, 1, 2, 4];
        $frontendPostController->parseQuery($wpQuery);
        self::assertEquals([1, 1, 2, 4], $wpQuery->query_vars['post__not_in']);

        $frontendPostController->parseQuery($wpQuery);
        self::assertEqualsCanonicalizing([1, 2, 3, 4], $wpQuery->query_vars['post__not_in']);

        $frontendPostController->parseQuery($wpQuery);
        self::assertEqualsCanonicalizing([1, 2, 3, 4, 5], $wpQuery->query_vars['post__not_in']);
    }

    /**
     * @group  unit
     * @covers ::postsPreQuery()
     * @covers ::filtersSuppressed()
     * @covers ::extractOwnFilters()
     * @covers ::getWordpressFilters()
     */
    public function testPostsPreQuery()
    {
        $wordpress = $this->getWordpress();

        $frontendPostControllerMock = $this->createMock(PostController::class);
        $postsFilter = new stdClass();
        $postsFilter->callbacks = [
            5 => [],
            10 => [
                ['function' => [new stdClass(), 'showPosts']],
                ['function' => [$frontendPostControllerMock, 'someFunction']],
                ['function' => [$frontendPostControllerMock, 'showPosts']],
                ['function' => 'someFunction']
            ]
        ];

        $firstFilter = [
            'the_posts' => 'the_posts_content',
            'posts_results' => 'posts_results_content',
            'other_filter' => 'other_filter_content'
        ];
        $secondFilters = [
            'the_posts' => $postsFilter,
            'posts_results' => 'posts_results_content',
            'other_filter' => 'other_filter_content'
        ];

        $wordpress->expects($this->exactly(2))
            ->method('getFilters')
            ->will($this->onConsecutiveCalls($firstFilter, $secondFilters));

        $expectedPostsFilter = new stdClass();
        $expectedPostsFilter->callbacks = [10 => [['function' => [$frontendPostControllerMock, 'showPosts']]]];
        $expectedFilters = [
            'the_posts' => $expectedPostsFilter,
            'other_filter' => 'other_filter_content'
        ];

        $wordpress->expects($this->once())
            ->method('setFilters')
            ->with($expectedFilters);

        $frontendPostController = new PostController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler()
        );

        /**
         * @var WP_Query $wpQuery
         */
        $wpQuery = $this->getMockBuilder('\WP_Query')->getMock();

        self::assertEquals(
            ['firstPost', 'secondPost'],
            $frontendPostController->postsPreQuery(['firstPost', 'secondPost'], $wpQuery)
        );

        $wpQuery->query_vars = ['suppress_filters' => false];

        self::assertEquals(['firstPost'], $frontendPostController->postsPreQuery(['firstPost'], $wpQuery));
        self::assertFalse($wpQuery->query_vars['suppress_filters']);

        $wpQuery->query_vars = ['suppress_filters' => true];

        self::assertEquals(['firstPost'], $frontendPostController->postsPreQuery(['firstPost'], $wpQuery));
        self::assertTrue($wpQuery->query_vars['suppress_filters']);
        self::assertEquals([], $frontendPostController->getWordpressFilters());

        $wpQuery->query_vars = ['suppress_filters' => true];
        self::assertEquals(['firstPost'], $frontendPostController->postsPreQuery(['firstPost'], $wpQuery));
        self::assertFalse($wpQuery->query_vars['suppress_filters']);
        self::assertEquals(
            [
                'the_posts' => $expectedPostsFilter,
                'posts_results' => 'posts_results_content'
            ],
            $frontendPostController->getWordpressFilters()
        );
    }

    /**
     * @group  unit
     * @covers ::restoreFilters()
     * @covers ::getWordpressFilters()
     * @throws ReflectionException
     */
    public function testRestoreFilters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getFilters')
            ->will($this->returnValue(['firstFilter' => 'firstFilterValue']));

        $wordpress->expects($this->once())
            ->method('setFilters')
            ->with([
                'firstFilter' => 'firstFilterValue',
                'secondFilter' => 'secondFilterValue'
            ]);

        $frontendPostController = new PostController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler()
        );

        $this->callMethod($frontendPostController, 'restoreFilters');
        $this->setValue($frontendPostController, 'wordpressFilters', ['secondFilter' => 'secondFilterValue']);
        $this->callMethod($frontendPostController, 'restoreFilters');
        self::assertEquals([], $frontendPostController->getWordpressFilters());
    }

    /**
     * @param int $id
     * @param string $postType
     * @param null $title
     * @param null $content
     * @param bool $closed
     * @param string $postMimeType
     * @return MockObject|WP_Post
     */
    private function getPost(
        int $id,
        $postType = 'post',
        $title = null,
        $content = null,
        $closed = false,
        $postMimeType = 'post/mime/type'
    )
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
     * @covers ::showPosts()
     * @covers ::showPages()
     * @covers ::filterRawPosts()
     * @covers ::getPost()
     * @covers ::processPost()
     * @covers ::processPostContent()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testShowPostsAtAdminPanel()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('isFeed')
            ->will($this->onConsecutiveCalls(true, true, false, false));

        $wordpress->expects($this->once())
            ->method('getFilters');

        $wordpress->expects($this->once())
            ->method('setFilters');

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(15))
            ->method('atAdminPanel')
            ->will($this->returnValue(true));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(2))
            ->method('protectFeed')
            ->will($this->onConsecutiveCalls(false, true));

        $mainConfig->expects($this->exactly(7))
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

        $frontendPostController = new PostController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
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


        self::assertEquals($posts, $frontendPostController->showPosts($posts));
        self::assertEquals([$this->getPost(2)], $frontendPostController->showPosts($posts));
        self::assertEquals([$this->getPost(1), $this->getPost(2)], $frontendPostController->showPosts($posts));
        self::setValue($frontendPostController, 'wordpressFilters', ['a' => 'b']);
        self::assertEquals([], $frontendPostController->showPosts());
        self::setValue($frontendPostController, 'wordpressFilters', []);
        self::assertEquals([], $frontendPostController->showPages());
        self::assertEquals([$this->getPost(2, 'page')], $frontendPostController->showPages($pages));
    }

    /**
     * @group  unit
     * @covers ::showPosts()
     * @covers ::showPages()
     * @covers ::getPost()
     * @covers ::processPost()
     * @covers ::processPostContent()
     * @covers ::filterRawPosts()
     * @throws UserGroupTypeException
     */
    public function testShowPosts()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('isFeed')
            ->will($this->returnValue(false));

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(12))
            ->method('atAdminPanel')
            ->will($this->returnValue(false));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(6))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['other'], ['post'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, false, true, false, false, false));

        $mainConfig->expects($this->exactly(5))
            ->method('getPostTypeContent')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->returnValue('postContent'));

        $mainConfig->expects($this->exactly(5))
            ->method('showPostTypeContentBeforeMore')
            ->withConsecutive(
                ['post'],
                ['other'],
                ['page'],
                ['post'],
                ['other']
            )
            ->will($this->onConsecutiveCalls(
                true,
                false,
                false,
                false,
                false
            ));

        $mainConfig->expects($this->exactly(5))
            ->method('hidePostTypeTitle')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(true, false, true, false, true));

        $mainConfig->expects($this->exactly(3))
            ->method('getPostTypeTitle')
            ->withConsecutive(['post'], ['page'], ['other'])
            ->will($this->returnValue('postTitle'));

        $mainConfig->expects($this->exactly(5))
            ->method('lockPostTypeComments')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, true, true, false, false));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive([1], [2], [5], [5])
            ->will($this->returnCallback(function ($postId) {
                if ($postId === 5) {
                    return false;
                }

                return $this->getPost($postId);
            }));

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

        $frontendPostController = new PostController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $objectHandler,
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
        );

        $stdClassPost = new stdClass();
        $stdClassPost->ID = 2;

        $invalidStdClassPost = new stdClass();

        $posts = [
            1 => 1,
            2 => $stdClassPost,
            3 => $this->getPost(3, 'other'),
            4 => $this->getPost(4),
            5 => 5,
            6 => 'invalid',
            7 => $invalidStdClassPost
        ];

        $pages = [
            1 => $this->getPost(1, 'page'),
            2 => $this->getPost(2),
            3 => $this->getPost(3, 'other'),
            5 => 5
        ];


        self::assertEquals(
            [
                $this->getPost(1),
                $this->getPost(2, 'post', 'postTitle', '[LOGIN_FORM] content2 postContent'),
                $this->getPost(3, 'other', null, 'postContent', true),
                5,
                'invalid',
                $invalidStdClassPost
            ],
            $frontendPostController->showPosts($posts)
        );
        self::assertEquals(
            [
                $this->getPost(1, 'page', 'postTitle', 'postContent', true),
                $this->getPost(2, 'post', null, 'postContent'),
                $this->getPost(3, 'other', 'postTitle', 'postContent'),
                5
            ],
            $frontendPostController->showPages($pages)
        );
    }

    /**
     * @group  unit
     * @covers ::getAttachedFile()
     * @throws UserGroupTypeException
     */
    public function testGetAttachedFile()
    {
        $config = $this->getMainConfig();

        $config->expects($this->exactly(3))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 2]
            )
            ->will($this->returnCallback(function ($objectType, $id) {
                return ($objectType === ObjectHandler::ATTACHMENT_OBJECT_TYPE && $id === 1);
            }));

        $frontendPostController = new PostController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
        );

        self::assertEquals('testFile.gif', $frontendPostController->getAttachedFile('testFile.gif', 1));
        self::assertEquals('testFile.gif  ', $frontendPostController->getAttachedFile('testFile.gif  ', 1));
        self::assertEquals('firstFile', $frontendPostController->getAttachedFile('firstFile', 1));
        self::assertFalse($frontendPostController->getAttachedFile('secondFile', 2));
    }

    /**
     * @group  unit
     * @covers ::showPostSql()
     * @covers ::addQueryExcludedPostFilter()
     * @throws UserGroupTypeException
     */
    public function testShowPostSql()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(3))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([], [1 => 1], [1 => 1, 3 => 3]));

        $frontendPostController = new PostController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $database,
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
        );

        self::assertEquals('query', $frontendPostController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN (1) ', $frontendPostController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN (1, 3) ', $frontendPostController->showPostSql('query'));
    }

    /**
     * @param array $properties
     * @return stdClass
     */
    private function createCounts(array $properties): stdClass
    {
        $counts = new stdClass();

        foreach ($properties as $property => $value) {
            $counts->{$property} = $value;
        }

        return $counts;
    }

    /**
     * @group  unit
     * @covers ::showPostCount()
     * @covers ::getPostCountQuery()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testShowPostCount()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(false, true, true));

        $postTypeObject = $this->createMock(WP_Post_Type::class);
        $postTypeObject->cap = new stdClass();
        $postTypeObject->cap->read_private_posts = 'readPrivatePostsValue';

        $wordpress->expects($this->exactly(2))
            ->method('getPostTypeObject')
            ->will($this->returnValue($postTypeObject));

        $wordpress->expects($this->exactly(2))
            ->method('currentUserCan')
            ->with('readPrivatePostsValue')
            ->will($this->onConsecutiveCalls(true, false));

        /**
         * @var MockObject|WP_User $user
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
                ['post_status' => 'firstStatus', 'num_posts' => 3],
                ['post_status' => 'thirdStatus', 'num_posts' => 5],
                ['post_status' => 'invalid', 'num_posts' => 5],
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

        $frontendPostController = new PostController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $database,
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
        );

        $cachedResult = new stdClass();
        $cachedResult->test = 'cachedResult';
        self::setValue($frontendPostController, 'cachedCounts', ['type' => $cachedResult]);
        self::assertEquals(
            $cachedResult,
            $frontendPostController->showPostCount(
                $this->createCounts(['firstStatus' => 3, 'secondStatus' => 8]),
                'type',
                'perm'
            )
        );

        self::setValue($frontendPostController, 'cachedCounts', []);
        self::assertEquals(
            $this->createCounts(['firstStatus' => 3, 'secondStatus' => 8]),
            $frontendPostController->showPostCount(
                $this->createCounts(['firstStatus' => 3, 'secondStatus' => 8]),
                'type',
                'perm'
            )
        );

        self::setValue($frontendPostController, 'cachedCounts', []);
        self::assertEquals(
            $this->createCounts(['firstStatus' => 3, 'secondStatus' => 8]),
            $frontendPostController->showPostCount(
                $this->createCounts(['firstStatus' => 2, 'secondStatus' => 8]),
                'type',
                'perm'
            )
        );

        self::setValue($frontendPostController, 'cachedCounts', []);
        self::assertEquals(
            $this->createCounts(['firstStatus' => 3, 'secondStatus' => 8]),
            $frontendPostController->showPostCount(
                $this->createCounts(['firstStatus' => 2, 'secondStatus' => 8]),
                'type',
                'readable'
            )
        );

        self::setValue($frontendPostController, 'cachedCounts', []);
        self::assertEquals(
            $this->createCounts(['firstStatus' => 3, 'secondStatus' => 8]),
            $frontendPostController->showPostCount(
                $this->createCounts(['firstStatus' => 2, 'secondStatus' => 8]),
                'type',
                'readable'
            )
        );

        self::setValue($frontendPostController, 'cachedCounts', []);
        self::assertEquals(
            $this->createCounts(['firstStatus' => 3, 'secondStatus' => 8]),
            $frontendPostController->showPostCount(
                $this->createCounts(['firstStatus' => 2, 'secondStatus' => 8]),
                'type',
                'readable'
            )
        );
    }

    /**
     * @param int $postId
     * @param null $content
     * @return MockObject|WP_Comment
     */
    private function getComment(int $postId, $content = null)
    {
        /**
         * @var MockObject|WP_Comment $comment
         */
        $comment = $this->getMockBuilder('\WP_Comment')->getMock();
        $comment->comment_post_ID = $postId;
        $comment->comment_content = ($content === null) ? "commentContent$postId" : $content;

        return $comment;
    }

    /**
     * @group  unit
     * @covers ::showComment()
     * @covers ::hidePostComment()
     * @throws UserGroupTypeException
     */
    public function testShowComment()
    {
        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(3))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(5))
            ->method('lockPostTypeComments')
            ->withConsecutive(['post'], ['page'], ['post'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false, false, false));

        $mainConfig->expects($this->exactly(4))
            ->method('hidePostType')
            ->withConsecutive(['page'], ['post'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false, false));

        $mainConfig->expects($this->exactly(2))
            ->method('hidePostTypeComments')
            ->withConsecutive(['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false));

        $mainConfig->expects($this->once())
            ->method('getPostTypeCommentContent')
            ->with('post')
            ->will($this->returnValue('PostTypeCommentContent'));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(7))
            ->method('getPost')
            ->will($this->returnCallback(function ($postId) {
                $type = ($postId === 4) ? 'page' : 'post';
                return ($postId !== 2) ? $this->getPost($postId, $type) : false;
            }));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(6))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 3],
                ['page', 4],
                ['post', 5],
                ['post', 6],
                ['post', 7]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false,
                false,
                false,
                false,
                false
            ));

        $frontendPostController = new PostController(
            $this->getPhp(),
            $this->getWordpress(),
            $wordpressConfig,
            $mainConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $objectHandler,
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
        );

        $comments = [
            $this->getComment(1),
            $this->getComment(2),
            $this->getComment(3),
            $this->getComment(4),
            $this->getComment(5),
            $this->getComment(6),
            $this->getComment(7)
        ];

        self::assertEquals(
            [
                $this->getComment(1),
                $this->getComment(2),
                $this->getComment(6, 'PostTypeCommentContent'),
                $this->getComment(7)
            ],
            $frontendPostController->showComment($comments)
        );
    }

    /**
     * @group  unit
     * @covers ::showNextPreviousPost()
     * @covers ::addQueryExcludedPostFilter()
     * @throws UserGroupTypeException
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

        $frontendPostController = new PostController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $accessHandler
        );

        self::assertEquals('query', $frontendPostController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2) ', $frontendPostController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2, 3, 5) ', $frontendPostController->showNextPreviousPost('query'));
    }

    /**
     * @group  unit
     * @covers ::showEditLink()
     * @throws UserGroupTypeException
     */
    public function testShowEditLink()
    {
        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(4))
            ->method('showAssignedGroups')
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $mainConfig->expects($this->exactly(4))
            ->method('hideEditLinkOnNoAccess')
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroupHandler->expects($this->exactly(3))
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

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(3))
            ->method('checkObjectAccess')
            ->withConsecutive(
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1, true],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1, true],
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1, true]
            )
            ->will($this->onConsecutiveCalls(true, true, false));

        $frontendPostController = new PostController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $mainConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $userGroupHandler,
            $accessHandler
        );

        self::assertEquals('link', $frontendPostController->showEditLink('link', 1));
        self::assertEquals('link', $frontendPostController->showEditLink('link', 1));
        self::assertEquals(
            'link | ' . TXT_UAM_ASSIGNED_GROUPS . ': name2',
            $frontendPostController->showEditLink('link', 1)
        );
        self::assertEquals(
            ' ' . TXT_UAM_ASSIGNED_GROUPS . ': &lt;a&gt;test&lt;/a&gt;, name2',
            $frontendPostController->showEditLink('link', 1)
        );
    }
}
