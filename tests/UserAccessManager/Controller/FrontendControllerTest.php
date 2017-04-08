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
 * @version   SVN: $Id$
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
    private $Root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->oRoot = FileSystem::factory('vfs://');
        $this->oRoot->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->oRoot->unmount();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::__construct()
     */
    public function testCanCreateInstance()
    {
        $FrontendController = new FrontendController(
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

        self::assertInstanceOf('\UserAccessManager\Controller\FrontendController', $FrontendController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::enqueueStylesAndScripts()
     * @covers \UserAccessManager\Controller\FrontendController::registerStylesAndScripts()
     */
    public function testEnqueueStylesAndScripts()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('registerStyle')
            ->with(
                FrontendController::HANDLE_STYLE_LOGIN_FORM,
                'http://url/assets/css/uamLoginForm.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            );

        $Wordpress->expects($this->once())
            ->method('enqueueStyle')
            ->with(FrontendController::HANDLE_STYLE_LOGIN_FORM);

        $Config = $this->getConfig();
        $Config->expects($this->once())
            ->method('getUrlPath')
            ->will($this->returnValue('http://url/'));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $FrontendController->enqueueStylesAndScripts();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::parseQuery()
     */
    public function testParseQuery()
    {
        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([], [3], [2, 3, 5]));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Query $WpQuery
         */
        $WpQuery = $this->getMockBuilder('\WP_Query')->getMock();
        $WpQuery->query_vars = [
            'post__not_in' => [1, 1, 2, 4]
        ];

        $FrontendController->parseQuery($WpQuery);
        self::assertEquals([1, 1, 2, 4], $WpQuery->query_vars['post__not_in']);

        $FrontendController->parseQuery($WpQuery);
        self::assertEquals([1, 2, 3, 4], $WpQuery->query_vars['post__not_in'], '', 0.0, 10, true);

        $FrontendController->parseQuery($WpQuery);
        self::assertEquals([1, 2, 3, 4, 5], $WpQuery->query_vars['post__not_in'], '', 0.0, 10, true);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::adminOutput()
     */
    public function testAdminOutput()
    {
        $Wordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $AdminUser
         */
        $AdminUser = $this->getMockBuilder('\WP_User')->getMock();
        $AdminUser->ID = 1;

        /**
         * @var \WP_User|\stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->ID = 2;

        $Wordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->onConsecutiveCalls(
                $User,
                $AdminUser,
                $AdminUser
            ));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(6))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $Config->expects($this->exactly(5))
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

        $Config->expects($this->exactly(4))
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('hintText'));

        $Util = $this->getUtil();

        $Util->expects($this->once())
            ->method('endsWith')
            ->withConsecutive(
                ['text hintText', 'hintText']
            )
            ->will($this->onConsecutiveCalls(true));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(3))
            ->method('userIsAdmin')
            ->withConsecutive([2], [1], [1])
            ->will($this->returnCallback(function ($iId) {
                return ($iId === 1);
            }));

        $AccessHandler->expects($this->exactly(2))
            ->method('getUserGroupsForObject')
            ->withConsecutive(
                ['objectType', 'objectId'],
                ['secondObjectType', 'secondObjectId']
            )
            ->will($this->onConsecutiveCalls(
                [],
                [$this->getUserGroup(1)]
            ));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $Util,
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('', $FrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $FrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $FrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $FrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('hintText', $FrontendController->adminOutput('secondObjectType', 'secondObjectId'));
        self::assertEquals('', $FrontendController->adminOutput(
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
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('src', new Directory([
            'UserAccessManager' => new Directory([
                'View' => new Directory([
                    'LoginForm.php' => new File('<?php echo \'LoginForm\';')
                ])
            ])
        ]));

        $Php = $this->getPhp();

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(2))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(true, false));

        $Wordpress->expects($this->exactly(2))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_login_form', ''],
                ['uam_login_form', 'LoginForm']
            )
            ->will($this->onConsecutiveCalls('filter', 'LoginFormWithFilter'));


        $Config = $this->getConfig();

        $Config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $FrontendController = new FrontendController(
            $Php,
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $Php->expects($this->once())
            ->method('includeFile')
            ->with($FrontendController, 'vfs://src/UserAccessManager/View/LoginForm.php')
            ->will($this->returnCallback(function () {
                echo 'LoginForm';
            }));

        self::assertEquals('filter', $FrontendController->getLoginFormHtml());
        self::assertEquals('LoginFormWithFilter', $FrontendController->getLoginFormHtml());
    }

    /**
     * @param int    $iId
     * @param string $sPostType
     * @param string $sTitle
     * @param string $sContent
     * @param bool   $blClosed
     * @param string $sPostMimeType
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Post
     */
    private function getPost(
        $iId,
        $sPostType = 'post',
        $sTitle = null,
        $sContent = null,
        $blClosed = false,
        $sPostMimeType = 'post/mime/type'
    ) {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Post $Post
         */
        $Post = $this->getMockBuilder('\WP_Post')->getMock();
        $Post->ID = $iId;
        $Post->post_type = $sPostType;
        $Post->post_title = ($sTitle === null) ? "title{$iId}" : $sTitle;
        $Post->post_content = ($sContent === null) ?
            "[LOGIN_FORM] content{$iId}<!--more-->text<!--more-->\\contentAfter" : $sContent;
        $Post->comment_status = ($blClosed === true) ? 'close' : 'open';
        $Post->post_mime_type = $sPostMimeType;

        return $Post;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPosts()
     * @covers \UserAccessManager\Controller\FrontendController::showPages()
     * @covers \UserAccessManager\Controller\FrontendController::processPost()
     */
    public function testShowPostsAtAdminPanel()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(4))
            ->method('isFeed')
            ->will($this->onConsecutiveCalls(true, true, false, false));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(2))
            ->method('protectFeed')
            ->will($this->onConsecutiveCalls(false, true));

        $Config->expects($this->exactly(7))
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

        $Config->expects($this->exactly(15))
            ->method('atAdminPanel')
            ->will($this->returnValue(true));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(11))
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

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        $aPosts = [
            1 => $this->getPost(1),
            2 => $this->getPost(2),
            3 => $this->getPost(3, 'other'),
            4 => $this->getPost(4)
        ];

        $aPages = [
            1 => $this->getPost(1),
            2 => $this->getPost(2, 'page'),
            3 => $this->getPost(3, 'other')
        ];


        self::assertEquals([], $FrontendController->showPosts($aPosts));
        self::assertEquals([$this->getPost(2)], $FrontendController->showPosts($aPosts));
        self::assertEquals([$this->getPost(1), $this->getPost(2)], $FrontendController->showPosts($aPosts));
        self::assertEquals([], $FrontendController->showPosts());
        self::assertEquals([], $FrontendController->showPages());
        self::assertEquals([$this->getPost(2, 'page')], $FrontendController->showPages($aPages));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPosts()
     * @covers \UserAccessManager\Controller\FrontendController::showPages()
     * @covers \UserAccessManager\Controller\FrontendController::processPost()
     */
    public function testShowPosts()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('isFeed')
            ->will($this->returnValue(false));

        $Wordpress->expects($this->exactly(5))
            ->method('isUserLoggedIn')
            ->will($this->returnValue(true));

        $Wordpress->expects($this->exactly(5))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', '']
            )
            ->will($this->onConsecutiveCalls('', 'LoginForm', '', '', 'LoginForm'));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(6))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['other'], ['post'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, false, true, false, false, false));

        $Config->expects($this->exactly(12))
            ->method('atAdminPanel')
            ->will($this->returnValue(false));

        $Config->expects($this->exactly(5))
            ->method('getPostTypeContent')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->returnValue('postContent'));

        $Config->expects($this->exactly(2))
            ->method('showPostContentBeforeMore')
            ->will($this->onConsecutiveCalls(true, false));

        $Config->expects($this->exactly(5))
            ->method('hidePostTypeTitle')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(true, false, true, false, true));

        $Config->expects($this->exactly(3))
            ->method('getPostTypeTitle')
            ->withConsecutive(['post'], ['page'], ['other'])
            ->will($this->returnValue('postTitle'));


        $Config->expects($this->exactly(5))
            ->method('hidePostTypeComments')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, true, true, false, false));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(7))
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

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        $aPosts = [
            1 => $this->getPost(1),
            2 => $this->getPost(2),
            3 => $this->getPost(3, 'other'),
            4 => $this->getPost(4)
        ];

        $aPages = [
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
            $FrontendController->showPosts($aPosts)
        );
        self::assertEquals(
            [
                $this->getPost(1, 'page', 'postTitle', 'postContent', true),
                $this->getPost(2, 'post', null, 'postContent'),
                $this->getPost(3, 'other', 'postTitle', 'postContent')
            ],
            $FrontendController->showPages($aPages)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPostSql()
     */
    public function testShowPostSql()
    {
        $Database = $this->getDatabase();

        $Database->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([], [1 => 1], [1 => 1, 3 => 3]));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $Database,
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('query', $FrontendController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN(1) ', $FrontendController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN(1,3) ', $FrontendController->showPostSql('query'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPostCount()
     */
    public function testShowPostCount()
    {
        $Counts = new \stdClass();
        $Counts->firstStatus = 3;
        $Counts->secondStatus = 8;

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(3))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(false, true, true));

        $PostTypeObject = new \stdClass();
        $PostTypeObject->cap = new \stdClass();
        $PostTypeObject->cap->read_private_posts = 'readPrivatePostsValue';

        $Wordpress->expects($this->exactly(2))
            ->method('getPostTypeObject')
            ->will($this->returnValue($PostTypeObject));

        $Wordpress->expects($this->exactly(2))
            ->method('currentUserCan')
            ->with('readPrivatePostsValue')
            ->will($this->onConsecutiveCalls(true, false));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_User $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->ID = 1;

        $Wordpress->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue($User));

        $Database = $this->getDatabase();

        $Database->expects($this->exactly(4))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $Database->expects($this->exactly(4))
            ->method('getResults')
            ->with('preparedQuery', ARRAY_A)
            ->will($this->returnValue([
                ['post_status' => 'firstStatus', 'num_posts' => 2],
                ['post_status' => 'thirdStatus', 'num_posts' => 5],
            ]));

        $Database->expects($this->exactly(5))
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

        $Cache = $this->getCache();

        $Cache->expects($this->exactly(6))
            ->method('getFromCache')
            ->with(FrontendController::POST_COUNTS_CACHE_KEY)
            ->will($this->onConsecutiveCalls('cachedResult', null, null, null, null, null));

        $Cache->expects($this->exactly(5))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_COUNTS_CACHE_KEY, $Counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $Counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $Counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $Counts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $Counts]
            );

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(5))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls(
                [],
                [1 => 1],
                [1 => 1, 3 => 3],
                [1 => 1, 3 => 3],
                [1 => 1, 3 => 3]
            ));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $Database,
            $this->getUtil(),
            $Cache,
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('cachedResult', $FrontendController->showPostCount($Counts, 'type', 'perm'));
        self::assertEquals($Counts, $FrontendController->showPostCount($Counts, 'type', 'perm'));

        $Counts->firstStatus = 2;
        self::assertEquals($Counts, $FrontendController->showPostCount($Counts, 'type', 'perm'));
        self::assertEquals($Counts, $FrontendController->showPostCount($Counts, 'type', 'readable'));
        self::assertEquals($Counts, $FrontendController->showPostCount($Counts, 'type', 'readable'));
        self::assertEquals($Counts, $FrontendController->showPostCount($Counts, 'type', 'readable'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getTermArguments()
     */
    public function testGetTermArguments()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('parseIdList')
            ->with('3,4')
            ->will($this->returnValue([3, 4]));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(2))
            ->method('getExcludedTerms')
            ->will($this->returnValue([1, 3]));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals(['exclude' => [1, 3]], $FrontendController->getTermArguments([]));
        self::assertEquals(['exclude' => [3, 4, 1]], $FrontendController->getTermArguments(['exclude' => '3,4']));
    }

    /**
     * @param int    $iPostId
     * @param string $sContent
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Comment
     */
    private function getComment($iPostId, $sContent = null)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Comment $Comment
         */
        $Comment = $this->getMockBuilder('\WP_Comment')->getMock();
        $Comment->comment_post_ID = $iPostId;
        $Comment->comment_content = ($sContent === null) ? "commentContent$iPostId" : $sContent;

        return $Comment;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showComment()
     */
    public function testShowComment()
    {
        $Config = $this->getConfig();

        $Config->expects($this->exactly(4))
            ->method('hidePostTypeComments')
            ->withConsecutive(['post'], ['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false, false));

        $Config->expects($this->exactly(3))
            ->method('hidePostType')
            ->withConsecutive(['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false));

        $Config->expects($this->exactly(2))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false));

        $Config->expects($this->once())
            ->method('getPostTypeCommentContent')
            ->with('post')
            ->will($this->returnValue('PostTypeCommentContent'));

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly(6))
            ->method('getPost')
            ->will($this->returnCallback(function ($iPostId) {
                $sType = ($iPostId === 4) ? 'page' : 'post';
                return ($iPostId !== 2) ? $this->getPost($iPostId, $sType) : false;
            }));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(5))
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

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $ObjectHandler,
            $AccessHandler,
            $this->getFileHandler()
        );

        $aComments = [
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
            $FrontendController->showComment($aComments)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showAncestors()
     */
    public function testShowAncestors()
    {
        $Config = $this->getConfig();

        $Config->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, true));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(5))
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

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        $aAncestors = [
            1 => 1,
            2 => 2,
            3 => 3
        ];

        self::assertEquals([], $FrontendController->showAncestors($aAncestors, 'objectId', 'objectType'));
        self::assertEquals(
            [1 => 1, 3 => 3],
            $FrontendController->showAncestors($aAncestors, 'objectId', 'objectType')
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showNextPreviousPost()
     */
    public function testShowNextPreviousPost()
    {
        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls(
                [],
                [2],
                [2, 3, 5]
            ));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('query', $FrontendController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2) ', $FrontendController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2, 3, 5) ', $FrontendController->showNextPreviousPost('query'));
    }

    /**
     * @param int    $iTermId
     * @param string $sTaxonomy
     * @param string $sName
     * @param int    $iCount
     * @param int    $iParent
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Term
     */
    private function getTerm($iTermId, $sTaxonomy = 'taxonomy', $sName = null, $iCount = 0, $iParent = 0)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Term $Term
         */
        $Term = $this->getMockBuilder('\WP_Term')->getMock();
        $Term->term_id = $iTermId;
        $Term->taxonomy = $sTaxonomy;
        $Term->name = ($sName === null) ? "name{$iTermId}" : $sName;
        $Term->count = $iCount;
        $Term->parent = $iParent;

        return $Term;
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
        $Wordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->ID = 1;

        $Wordpress->expects($this->exactly(1))
            ->method('getCurrentUser')
            ->will($this->returnValue($User));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(7))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, false, false, true, true, false, true));

        $Config->expects($this->exactly(12))
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
                true
            ));

        $Config->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $Config->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $Config->expects($this->exactly(5))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['post'], ['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $Config->expects($this->exactly(4))
            ->method('hideEmptyTaxonomy')
            ->withConsecutive(['taxonomy'], ['taxonomy'], ['taxonomy'], ['taxonomy'])
            ->will($this->onConsecutiveCalls(false, true, true, false));

        $Util = $this->getUtil();

        $Util->expects($this->once())
            ->method('endsWith')
            ->with('name1', 'BlogAdminHintText')
            ->will($this->returnValue(false));

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly(7))
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

        $ObjectHandler->expects($this->exactly(7))
            ->method('getTermPostMap')
            ->will($this->returnValue(
                [
                    1 => [10 => 'post', 11 => 'post', 12 => 'page'],
                    2 => [13 => 'post']
                ]
            ));

        $ObjectHandler->expects($this->exactly(4))
            ->method('getTerm')
            ->will($this->returnCallback(function ($iTermId) {
                if ($iTermId === 104) {
                    return false;
                } elseif ($iTermId >= 105) {
                    return $this->getTerm($iTermId, 'taxonomy', null, 0, ($iTermId - 1));
                }

                return $this->getTerm($iTermId);
            }));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(14))
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
                [ObjectHandler::GENERAL_POST_OBJECT_TYPE, 13]
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
                true
            ));

        $AccessHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));

        $AccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('taxonomy', 1)
            ->will($this->returnValue([1, 2]));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $Util,
            $this->getCache(),
            $ObjectHandler,
            $AccessHandler,
            $this->getFileHandler()
        );

        /**
         * @var \WP_Term $FakeTerm
         */
        $FakeTerm = new \stdClass();
        self::assertEquals($FakeTerm, $FrontendController->showTerm($FakeTerm));

        $Term = $this->getTerm(1);
        self::assertEquals(null, $FrontendController->showTerm($Term));
        self::assertEquals(
            $this->getTerm(1, 'taxonomy', 'name1BlogAdminHintText', 3),
            $FrontendController->showTerm($Term)
        );

        $Term = $this->getTerm(107, 'taxonomy', null, 0, 106);
        self::assertEquals($this->getTerm(107, 'taxonomy', null, 0, 105), $FrontendController->showTerm($Term));

        $Term = $this->getTerm(105, 'taxonomy', null, 0, 104);
        self::assertEquals($this->getTerm(105, 'taxonomy', null, 0, 104), $FrontendController->showTerm($Term));

        $aTerms = [
            1 => new \stdClass(),
            0 => 0,
            10 => 10,
            11 => $this->getTerm(11),
            12 => $this->getTerm(12),
            2 => $this->getTerm(2)
        ];
        self::assertEquals(
            [
                1 => new \stdClass(),
                12 => $this->getTerm(12),
                11 => $this->getTerm(11),
                2 => $this->getTerm(2, 'taxonomy', null, 1)
            ],
            $FrontendController->showTerms($aTerms)
        );
    }

    /**
     * @param string $sObjectType
     * @param string $sObjectId
     * @param string $sTitle
     *
     * @return \stdClass
     */
    private function getItem($sObjectType, $sObjectId, $sTitle = null)
    {
        $Item = new \stdClass();
        $Item->object = $sObjectType;
        $Item->object_id = $sObjectId;
        $Item->title = ($sTitle === null) ? "title{$sObjectId}" : $sTitle;

        return $Item;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showCustomMenu()
     */
    public function testShowCustomMenu()
    {
        $Wordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->ID = 1;

        $Wordpress->expects($this->exactly(1))
            ->method('getCurrentUser')
            ->will($this->returnValue($User));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(14))
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

        $Config->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $Config->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $Config->expects($this->exactly(3))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(false, false, true));

        $Config->expects($this->once())
            ->method('hidePostTypeTitle')
            ->with('post')
            ->will($this->returnValue(true));

        $Config->expects($this->once())
            ->method('getPostTypeTitle')
            ->with('post')
            ->will($this->returnValue('PostTypeTitle'));

        $Config->expects($this->once())
            ->method('hideEmptyTaxonomy')
            ->with('taxonomy')
            ->will($this->returnValue(true));

        $Config->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->returnValue(true));

        $Util = $this->getUtil();

        $Util->expects($this->once())
            ->method('endsWith')
            ->with('title1', 'BlogAdminHintText')
            ->will($this->returnValue(false));

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly(8))
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
            ->will($this->returnCallback(function ($sType) {
                return ($sType === 'post');
            }));

        $ObjectHandler->expects($this->exactly(4))
            ->method('isTaxonomy')
            ->withConsecutive(['other'], ['taxonomy'], ['taxonomy'], ['taxonomy'])
            ->will($this->returnCallback(function ($sType) {
                return ($sType === 'taxonomy');
            }));


        $ObjectHandler->expects($this->exactly(2))
            ->method('getTermTreeMap')
            ->will($this->returnValue([]));

        $ObjectHandler->expects($this->exactly(2))
            ->method('getTermPostMap')
            ->will($this->returnValue([]));

        $ObjectHandler->expects($this->exactly(3))
            ->method('getTerm')
            ->will($this->returnCallback(function ($iTermId) {
                return $this->getTerm($iTermId);
            }));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(7))
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

        $AccessHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));

        $AccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('other', 1)
            ->will($this->returnValue([1, 2]));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $Util,
            $this->getCache(),
            $ObjectHandler,
            $AccessHandler,
            $this->getFileHandler()
        );

        $aItems = [
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
            $FrontendController->showCustomMenu($aItems)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showGroupMembership()
     */
    public function testShowGroupMembership()
    {
        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(3))
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

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('link', $FrontendController->showGroupMembership('link', 1));
        self::assertEquals(
            'link | '.TXT_UAM_ASSIGNED_GROUPS.': name2',
            $FrontendController->showGroupMembership('link', 1)
        );
        self::assertEquals(
            'link | '.TXT_UAM_ASSIGNED_GROUPS.': &lt;a&gt;test&lt;/a&gt;, name2',
            $FrontendController->showGroupMembership('link', 1)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showLoginForm()
     */
    public function testShowLoginForm()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(4))
            ->method('isSingle')
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $Wordpress->expects($this->exactly(2))
            ->method('isPage')
            ->will($this->onConsecutiveCalls(false, true));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertTrue($FrontendController->showLoginForm());
        self::assertFalse($FrontendController->showLoginForm());
        self::assertTrue($FrontendController->showLoginForm());
        self::assertTrue($FrontendController->showLoginForm());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getLoginUrl()
     */
    public function testGetLoginUrl()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getBlogInfo')
            ->with('wpurl')
            ->will($this->returnValue('BlogInfo'));

        $Wordpress->expects($this->once())
            ->method('applyFilters')
            ->with('uam_login_form_url', 'BlogInfo/wp-login.php')
            ->will($this->returnValue('filter'));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('filter', $FrontendController->getLoginUrl());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getRedirectLoginUrl()
     */
    public function testGetRedirectLoginUrl()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getBlogInfo')
            ->with('wpurl')
            ->will($this->returnValue('BlogInfo'));

        $Wordpress->expects($this->once())
            ->method('applyFilters')
            ->with('uam_login_url', 'BlogInfo/wp-login.php?redirect_to=uri%40')
            ->will($this->returnValue('filter'));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $_SERVER['REQUEST_URI'] = 'uri@';

        self::assertEquals('filter', $FrontendController->getRedirectLoginUrl());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getUserLogin()
     */
    public function testGetUserLogin()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('escHtml')
            ->with('/log/')
            ->will($this->returnValue('escHtml'));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $_GET['log'] = '/log\/';

        self::assertEquals('escHtml', $FrontendController->getUserLogin());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getPostIdByUrl()
     */
    public function testGetPostIdByUrl()
    {
        $Cache = $this->getCache();

        $Cache->expects($this->exactly(6))
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

        $Cache->expects($this->exactly(4))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_URL_CACHE_KEY, ['url/part' => 1]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-e123/part' => 2]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-123x321/part' => 3]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-e123-123x321/part' => 4]]
            );

        $Database = $this->getDatabase();

        $Database->expects($this->exactly(5))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $Database->expects($this->exactly(5))
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

        $Database->expects($this->exactly(5))
            ->method('getRow')
            ->with('preparedQuery')
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1),
                $this->getPost(2),
                $this->getPost(3),
                $this->getPost(4)
            ));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $Database,
            $this->getUtil(),
            $Cache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals(null, $FrontendController->getPostIdByUrl('url/part'));
        self::assertEquals(1, $FrontendController->getPostIdByUrl('url/part'));
        self::assertEquals(2, $FrontendController->getPostIdByUrl('url-e123/part'));
        self::assertEquals(3, $FrontendController->getPostIdByUrl('url-123x321/part'));
        self::assertEquals(4, $FrontendController->getPostIdByUrl('url-e123-123x321/part'));
        self::assertEquals(1, $FrontendController->getPostIdByUrl('url/part'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getFile()
     * @covers \UserAccessManager\Controller\FrontendController::getFileSettingsByType()
     */
    public function testGetFile()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(5))
            ->method('getUploadDir')
            ->will($this->returnValue([
                'basedir' => '/baseDirectory/file/pictures/',
                'baseurl' => 'http://baseUrl/file/pictures/'
            ]));

        $Wordpress->expects($this->exactly(3))
            ->method('attachmentIsImage')
            ->will($this->onConsecutiveCalls(false, true, false));

        $Wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS);

        $Config = $this->getConfig();

        $Config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('realPath/'));

        $Cache = $this->getCache();

        $Cache->expects($this->exactly(5))
            ->method('getFromCache')
            ->with(FrontendController::POST_URL_CACHE_KEY)
            ->will($this->returnValue(['http://baseUrl/file/pictures/url' => 1]));

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly(5))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE)
            ));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(3))
            ->method('checkObjectAccess')
            ->withConsecutive(
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1]
            )
            ->will($this->onConsecutiveCalls(false, false, true));

        $FileHandler = $this->getFileHandler();

        $FileHandler->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                ['realPath/gfx/noAccessPic.png', true],
                ['/baseDirectory/file/pictures/url', false]
            )
            ->will($this->returnValue('getFile'));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $Cache,
            $ObjectHandler,
            $AccessHandler,
            $FileHandler
        );

        self::assertEquals(null, $FrontendController->getFile('type', 'url'));
        self::assertEquals(null, $FrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals(null, $FrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals(null, $FrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals('getFile', $FrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals('getFile', $FrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::redirectUser()
     */
    public function testRedirectUser()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Query $WpQuery
         */
        $WpQuery = $this->getMockBuilder('\WP_Query')->setMethods(['get_posts'])->getMock();
        $WpQuery->expects($this->once())
            ->method('get_posts')
            ->will($this->returnValue([
                $this->getPost(1),
                $this->getPost(2),
                $this->getPost(3)
            ]));

        $Post = $this->getPost(1);
        $Post->guid = 'guid';

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getWpQuery')
            ->will($this->returnValue($WpQuery));

        $Wordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('HomeUrl'));

        $Wordpress->expects($this->exactly(3))
            ->method('getPageLink')
            ->with($Post)
            ->will($this->returnValue('PageLink'));

        $Wordpress->expects($this->exactly(3))
            ->method('wpRedirect')
            ->withConsecutive(['guid'], ['RedirectCustomUrl'], ['HomeUrl']);

        $Config = $this->getConfig();

        $Config->expects($this->exactly(7))
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

        $Config->expects($this->exactly(4))
            ->method('getRedirectCustomPage')
            ->will($this->returnValue('RedirectCustomPage'));

        $Config->expects($this->once())
            ->method('getRedirectCustomUrl')
            ->will($this->returnValue('RedirectCustomUrl'));

        $Util = $this->getUtil();

        $Util->expects($this->exactly(7))
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

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive(['RedirectCustomPage'], ['RedirectCustomPage'], ['RedirectCustomPage'])
            ->will($this->onConsecutiveCalls(
                false,
                $Post,
                $Post,
                $Post
            ));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $Util,
            $this->getCache(),
            $ObjectHandler,
            $AccessHandler,
            $this->getFileHandler()
        );

        $FrontendController->redirectUser();
        $FrontendController->redirectUser(false);
        $FrontendController->redirectUser(false);
        $FrontendController->redirectUser(false);
        $FrontendController->redirectUser(false);
        $FrontendController->redirectUser(false);
        $FrontendController->redirectUser(false);
        $FrontendController->redirectUser(false);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::redirect()
     */
    public function testRedirect()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue(null));

        $Wordpress->expects($this->exactly(2))
            ->method('getPageByPath')
            ->with('pageNameValue')
            ->will($this->onConsecutiveCalls(null, $this->getPost(2)));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false, false, false, false));

        $Config->expects($this->exactly(9))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls('false', null, null, null, null, null, null, null, null));

        $Database = $this->getDatabase();

        $Database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $Database->expects($this->once())
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

        $Database->expects($this->once())
            ->method('getVariable')
            ->with('preparedQuery')
            ->will($this->returnValue(1));

        $Util = $this->getUtil();
        $Util->expects($this->once())
            ->method('getCurrentUrl')
            ->will($this->returnValue('currentUrl'));

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post', 'page', 'other']));

        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(7))
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

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $Database,
            $Util,
            $this->getCache(),
            $ObjectHandler,
            $AccessHandler,
            $this->getFileHandler()
        );

        $PageParams = new \stdClass();

        $_GET['uamfiletype'] = 'fileType';
        $PageParams->query_vars = [];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $PageParams->query_vars = [];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $PageParams->query_vars = [];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $PageParams->query_vars = ['p' => 'pValue'];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        unset($_GET['uamfiletype']);
        $_GET['uamgetfile'] = 'file';
        $PageParams->query_vars = ['page_id' => 'pageIdValue'];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $PageParams->query_vars = ['cat_id' => 'catIdValue'];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $PageParams->query_vars = ['name' => 'nameValue'];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $PageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $PageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));

        $_GET['uamfiletype'] = 'fileType';
        self::assertEquals('header', $FrontendController->redirect('header', $PageParams));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getFileUrl()
     */
    public function testGetFileUrl()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('homeUrl'));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(6))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $Config->expects($this->exactly(5))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $Config->expects($this->exactly(3))
            ->method('getLockedFileTypes')
            ->will($this->onConsecutiveCalls('none', 'all', 'aaa,mime'));

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1, 'post', null, null, false, 'type'),
                $this->getPost(1),
                $this->getPost(1)
            ));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $ObjectHandler,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('url', $FrontendController->getFileUrl('url', 1));
        self::assertEquals('url', $FrontendController->getFileUrl('url', 1));
        self::assertEquals('url', $FrontendController->getFileUrl('url', 1));
        self::assertEquals('url', $FrontendController->getFileUrl('url', 1));
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $FrontendController->getFileUrl('url', 1)
        );
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $FrontendController->getFileUrl('url', 1)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::cachePostLinks()
     */
    public function testCachePostLinks()
    {
        $Cache = $this->getCache();

        $Cache->expects($this->exactly(2))
            ->method('getFromCache')
            ->with(FrontendController::POST_URL_CACHE_KEY)
            ->will($this->onConsecutiveCalls(
                null,
                ['firstUrl' => 1]
            ));

        $Cache->expects($this->exactly(2))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_URL_CACHE_KEY, ['firstUrl' => 1]],
                [FrontendController::POST_URL_CACHE_KEY, ['firstUrl' => 1, 'secondUrl' => 2]]
            );

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $Cache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('firstUrl', $FrontendController->cachePostLinks('firstUrl', $this->getPost(1)));
        self::assertEquals('secondUrl', $FrontendController->cachePostLinks('secondUrl', $this->getPost(2)));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getWpSeoUrl()
     */
    public function testGetWpSeoUrl()
    {
        $AccessHandler = $this->getAccessHandler();

        $AccessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['type', 1],
                ['type', 1]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false
            ));

        $FrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getFileHandler()
        );

        $Object = new \stdClass();
        $Object->ID = 1;

        self::assertEquals('url', $FrontendController->getWpSeoUrl('url', 'type', $Object));
        self::assertFalse($FrontendController->getWpSeoUrl('url', 'type', $Object));
    }
}
