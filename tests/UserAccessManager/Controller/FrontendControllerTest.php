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
    private $oRoot;

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
        $oFrontendController = new FrontendController(
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

        self::assertInstanceOf('\UserAccessManager\Controller\FrontendController', $oFrontendController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::enqueueStylesAndScripts()
     * @covers \UserAccessManager\Controller\FrontendController::_registerStylesAndScripts()
     */
    public function testEnqueueStylesAndScripts()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('registerStyle')
            ->with(
                FrontendController::HANDLE_STYLE_LOGIN_FORM,
                'http://url/assets/css/uamLoginForm.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            );

        $oWordpress->expects($this->once())
            ->method('enqueueStyle')
            ->with(FrontendController::HANDLE_STYLE_LOGIN_FORM);

        $oConfig = $this->getConfig();
        $oConfig->expects($this->once())
            ->method('getUrlPath')
            ->will($this->returnValue('http://url/'));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $oFrontendController->enqueueStylesAndScripts();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::parseQuery()
     */
    public function testParseQuery()
    {
        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([], [3], [2, 3, 5]));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Query $oWpQuery
         */
        $oWpQuery = $this->getMockBuilder('\WP_Query')->getMock();
        $oWpQuery->query_vars = [
            'post__not_in' => [1, 1, 2, 4]
        ];

        $oFrontendController->parseQuery($oWpQuery);
        self::assertEquals([1, 1, 2, 4], $oWpQuery->query_vars['post__not_in']);

        $oFrontendController->parseQuery($oWpQuery);
        self::assertEquals([1, 2, 3, 4], $oWpQuery->query_vars['post__not_in'], '', 0.0, 10, true);

        $oFrontendController->parseQuery($oWpQuery);
        self::assertEquals([1, 2, 3, 4, 5], $oWpQuery->query_vars['post__not_in'], '', 0.0, 10, true);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::adminOutput()
     */
    public function testAdminOutput()
    {
        $oWordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $oAdminUser
         */
        $oAdminUser = $this->getMockBuilder('\WP_User')->getMock();
        $oAdminUser->ID = 1;

        /**
         * @var \WP_User|\stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->ID = 2;

        $oWordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->onConsecutiveCalls(
                $oUser,
                $oAdminUser,
                $oAdminUser
            ));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(6))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $oConfig->expects($this->exactly(5))
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

        $oConfig->expects($this->exactly(4))
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('hintText'));

        $oUtil = $this->getUtil();

        $oUtil->expects($this->once())
            ->method('endsWith')
            ->withConsecutive(
                ['text hintText', 'hintText']
            )
            ->will($this->onConsecutiveCalls(true));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(3))
            ->method('userIsAdmin')
            ->withConsecutive([2], [1], [1])
            ->will($this->returnCallback(function ($iId) {
                return ($iId === 1);
            }));

        $oAccessHandler->expects($this->exactly(2))
            ->method('getUserGroupsForObject')
            ->withConsecutive(
                ['objectType', 'objectId'],
                ['secondObjectType', 'secondObjectId']
            )
            ->will($this->onConsecutiveCalls(
                [],
                [$this->getUserGroup(1)]
            ));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $oUtil,
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('', $oFrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $oFrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $oFrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $oFrontendController->adminOutput('objectType', 'objectId'));
        self::assertEquals('hintText', $oFrontendController->adminOutput('secondObjectType', 'secondObjectId'));
        self::assertEquals('', $oFrontendController->adminOutput(
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
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('src', new Directory([
            'UserAccessManager' => new Directory([
                'View' => new Directory([
                    'LoginForm.php' => new File('<?php echo \'LoginForm\';')
                ])
            ])
        ]));

        $oPhp = $this->getPhp();

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(2))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(true, false));

        $oWordpress->expects($this->exactly(2))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_login_form', ''],
                ['uam_login_form', 'LoginForm']
            )
            ->will($this->onConsecutiveCalls('filter', 'LoginFormWithFilter'));


        $oConfig = $this->getConfig();

        $oConfig->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $oFrontendController = new FrontendController(
            $oPhp,
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $oPhp->expects($this->once())
            ->method('includeFile')
            ->with($oFrontendController, 'vfs://src/UserAccessManager/View/LoginForm.php')
            ->will($this->returnCallback(function () {
                echo 'LoginForm';
            }));

        self::assertEquals('filter', $oFrontendController->getLoginFormHtml());
        self::assertEquals('LoginFormWithFilter', $oFrontendController->getLoginFormHtml());
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
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Post $oPost
         */
        $oPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oPost->ID = $iId;
        $oPost->post_type = $sPostType;
        $oPost->post_title = ($sTitle === null) ? "title{$iId}" : $sTitle;
        $oPost->post_content = ($sContent === null) ?
            "[LOGIN_FORM] content{$iId}<!--more-->text<!--more-->\\contentAfter" : $sContent;
        $oPost->comment_status = ($blClosed === true) ? 'close' : 'open';
        $oPost->post_mime_type = $sPostMimeType;

        return $oPost;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPosts()
     * @covers \UserAccessManager\Controller\FrontendController::showPages()
     * @covers \UserAccessManager\Controller\FrontendController::_processPost()
     */
    public function testShowPostsAtAdminPanel()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(4))
            ->method('isFeed')
            ->will($this->onConsecutiveCalls(true, true, false, false));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(2))
            ->method('protectFeed')
            ->will($this->onConsecutiveCalls(false, true));

        $oConfig->expects($this->exactly(7))
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

        $oConfig->expects($this->exactly(15))
            ->method('atAdminPanel')
            ->will($this->returnValue(true));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(11))
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

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
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


        self::assertEquals([], $oFrontendController->showPosts($aPosts));
        self::assertEquals([$this->getPost(2)], $oFrontendController->showPosts($aPosts));
        self::assertEquals([$this->getPost(1), $this->getPost(2)], $oFrontendController->showPosts($aPosts));
        self::assertEquals([], $oFrontendController->showPosts());
        self::assertEquals([], $oFrontendController->showPages());
        self::assertEquals([$this->getPost(2, 'page')], $oFrontendController->showPages($aPages));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPosts()
     * @covers \UserAccessManager\Controller\FrontendController::showPages()
     * @covers \UserAccessManager\Controller\FrontendController::_processPost()
     */
    public function testShowPosts()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('isFeed')
            ->will($this->returnValue(false));

        $oWordpress->expects($this->exactly(5))
            ->method('isUserLoggedIn')
            ->will($this->returnValue(true));

        $oWordpress->expects($this->exactly(5))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', '']
            )
            ->will($this->onConsecutiveCalls('', 'LoginForm', '', '', 'LoginForm'));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(6))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['other'], ['post'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, false, true, false, false, false));

        $oConfig->expects($this->exactly(12))
            ->method('atAdminPanel')
            ->will($this->returnValue(false));

        $oConfig->expects($this->exactly(5))
            ->method('getPostTypeContent')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->returnValue('postContent'));

        $oConfig->expects($this->exactly(2))
            ->method('showPostContentBeforeMore')
            ->will($this->onConsecutiveCalls(true, false));

        $oConfig->expects($this->exactly(5))
            ->method('hidePostTypeTitle')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(true, false, true, false, true));

        $oConfig->expects($this->exactly(3))
            ->method('getPostTypeTitle')
            ->withConsecutive(['post'], ['page'], ['other'])
            ->will($this->returnValue('postTitle'));


        $oConfig->expects($this->exactly(5))
            ->method('hidePostTypeComments')
            ->withConsecutive(['post'], ['other'], ['page'], ['post'], ['other'])
            ->will($this->onConsecutiveCalls(false, true, true, false, false));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(7))
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

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
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
            $oFrontendController->showPosts($aPosts)
        );
        self::assertEquals(
            [
                $this->getPost(1, 'page', 'postTitle', 'postContent', true),
                $this->getPost(2, 'post', null, 'postContent'),
                $this->getPost(3, 'other', 'postTitle', 'postContent')
            ],
            $oFrontendController->showPages($aPages)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPostSql()
     */
    public function testShowPostSql()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls([], [1 => 1], [1 => 1, 3 => 3]));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $oDatabase,
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('query', $oFrontendController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN(1) ', $oFrontendController->showPostSql('query'));
        self::assertEquals('query AND postTable.ID NOT IN(1,3) ', $oFrontendController->showPostSql('query'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showPostCount()
     */
    public function testShowPostCount()
    {
        $oCounts = new \stdClass();
        $oCounts->firstStatus = 3;
        $oCounts->secondStatus = 8;

        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->exactly(3))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(false, true, true));

        $oPostTypeObject = new \stdClass();
        $oPostTypeObject->cap = new \stdClass();
        $oPostTypeObject->cap->read_private_posts = 'readPrivatePostsValue';

        $oWordpress->expects($this->exactly(2))
            ->method('getPostTypeObject')
            ->will($this->returnValue($oPostTypeObject));

        $oWordpress->expects($this->exactly(2))
            ->method('currentUserCan')
            ->with('readPrivatePostsValue')
            ->will($this->onConsecutiveCalls(true, false));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_User $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->ID = 1;

        $oWordpress->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue($oUser));

        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(4))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oDatabase->expects($this->exactly(4))
            ->method('getResults')
            ->with('preparedQuery', ARRAY_A)
            ->will($this->returnValue([
                ['post_status' => 'firstStatus', 'num_posts' => 2],
                ['post_status' => 'thirdStatus', 'num_posts' => 5],
            ]));

        $oDatabase->expects($this->exactly(5))
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

        $oCache = $this->getCache();

        $oCache->expects($this->exactly(6))
            ->method('getFromCache')
            ->with(FrontendController::POST_COUNTS_CACHE_KEY)
            ->will($this->onConsecutiveCalls('cachedResult', null, null, null, null, null));

        $oCache->expects($this->exactly(5))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_COUNTS_CACHE_KEY, $oCounts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $oCounts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $oCounts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $oCounts],
                [FrontendController::POST_COUNTS_CACHE_KEY, $oCounts]
            );

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(5))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls(
                [],
                [1 => 1],
                [1 => 1, 3 => 3],
                [1 => 1, 3 => 3],
                [1 => 1, 3 => 3]
            ));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $oDatabase,
            $this->getUtil(),
            $oCache,
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('cachedResult', $oFrontendController->showPostCount($oCounts, 'type', 'perm'));
        self::assertEquals($oCounts, $oFrontendController->showPostCount($oCounts, 'type', 'perm'));

        $oCounts->firstStatus = 2;
        self::assertEquals($oCounts, $oFrontendController->showPostCount($oCounts, 'type', 'perm'));
        self::assertEquals($oCounts, $oFrontendController->showPostCount($oCounts, 'type', 'readable'));
        self::assertEquals($oCounts, $oFrontendController->showPostCount($oCounts, 'type', 'readable'));
        self::assertEquals($oCounts, $oFrontendController->showPostCount($oCounts, 'type', 'readable'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getTermArguments()
     */
    public function testGetTermArguments()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('parseIdList')
            ->with('3,4')
            ->will($this->returnValue([3, 4]));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(2))
            ->method('getExcludedTerms')
            ->will($this->returnValue([1, 3]));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals(['exclude' => [1, 3]], $oFrontendController->getTermArguments([]));
        self::assertEquals(['exclude' => [3, 4, 1]], $oFrontendController->getTermArguments(['exclude' => '3,4']));
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
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Comment $oComment
         */
        $oComment = $this->getMockBuilder('\WP_Comment')->getMock();
        $oComment->comment_post_ID = $iPostId;
        $oComment->comment_content = ($sContent === null) ? "commentContent$iPostId" : $sContent;

        return $oComment;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showComment()
     */
    public function testShowComment()
    {
        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(4))
            ->method('hidePostTypeComments')
            ->withConsecutive(['post'], ['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false, false));

        $oConfig->expects($this->exactly(3))
            ->method('hidePostType')
            ->withConsecutive(['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(true, false, false));

        $oConfig->expects($this->exactly(2))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false));

        $oConfig->expects($this->once())
            ->method('getPostTypeCommentContent')
            ->with('post')
            ->will($this->returnValue('PostTypeCommentContent'));

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(6))
            ->method('getPost')
            ->will($this->returnCallback(function ($iPostId) {
                $sType = ($iPostId === 4) ? 'page' : 'post';
                return ($iPostId !== 2) ? $this->getPost($iPostId, $sType) : false;
            }));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(5))
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

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $oObjectHandler,
            $oAccessHandler,
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
            $oFrontendController->showComment($aComments)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showAncestors()
     */
    public function testShowAncestors()
    {
        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, true));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(5))
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

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        $aAncestors = [
            1 => 1,
            2 => 2,
            3 => 3
        ];

        self::assertEquals([], $oFrontendController->showAncestors($aAncestors, 'objectId', 'objectType'));
        self::assertEquals(
            [1 => 1, 3 => 3],
            $oFrontendController->showAncestors($aAncestors, 'objectId', 'objectType')
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showNextPreviousPost()
     */
    public function testShowNextPreviousPost()
    {
        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(3))
            ->method('getExcludedPosts')
            ->will($this->onConsecutiveCalls(
                [],
                [2],
                [2, 3, 5]
            ));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('query', $oFrontendController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2) ', $oFrontendController->showNextPreviousPost('query'));
        self::assertEquals('query AND p.ID NOT IN (2, 3, 5) ', $oFrontendController->showNextPreviousPost('query'));
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
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Term $oTerm
         */
        $oTerm = $this->getMockBuilder('\WP_Term')->getMock();
        $oTerm->term_id = $iTermId;
        $oTerm->taxonomy = $sTaxonomy;
        $oTerm->name = ($sName === null) ? "name{$iTermId}" : $sName;
        $oTerm->count = $iCount;
        $oTerm->parent = $iParent;

        return $oTerm;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showTerm()
     * @covers \UserAccessManager\Controller\FrontendController::showTerms()
     * @covers \UserAccessManager\Controller\FrontendController::_getVisibleElementsCount()
     * @covers \UserAccessManager\Controller\FrontendController::_processTerm()
     */
    public function testShowTerm()
    {
        $oWordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->ID = 1;

        $oWordpress->expects($this->exactly(1))
            ->method('getCurrentUser')
            ->will($this->returnValue($oUser));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(7))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, false, false, true, true, false, true));

        $oConfig->expects($this->exactly(12))
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

        $oConfig->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $oConfig->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $oConfig->expects($this->exactly(5))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['post'], ['page'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $oConfig->expects($this->exactly(4))
            ->method('hideEmptyTaxonomy')
            ->withConsecutive(['taxonomy'], ['taxonomy'], ['taxonomy'], ['taxonomy'])
            ->will($this->onConsecutiveCalls(false, true, true, false));

        $oUtil = $this->getUtil();

        $oUtil->expects($this->once())
            ->method('endsWith')
            ->with('name1', 'BlogAdminHintText')
            ->will($this->returnValue(false));

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(7))
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

        $oObjectHandler->expects($this->exactly(7))
            ->method('getTermPostMap')
            ->will($this->returnValue(
                [
                    1 => [10 => 'post', 11 => 'post', 12 => 'page'],
                    2 => [13 => 'post']
                ]
            ));

        $oObjectHandler->expects($this->exactly(4))
            ->method('getTerm')
            ->will($this->returnCallback(function ($iTermId) {
                if ($iTermId === 104) {
                    return false;
                } elseif ($iTermId >= 105) {
                    return $this->getTerm($iTermId, 'taxonomy', null, 0, ($iTermId - 1));
                }

                return $this->getTerm($iTermId);
            }));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(14))
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

        $oAccessHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));

        $oAccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('taxonomy', 1)
            ->will($this->returnValue([1, 2]));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $oUtil,
            $this->getCache(),
            $oObjectHandler,
            $oAccessHandler,
            $this->getFileHandler()
        );

        /**
         * @var \WP_Term $oFakeTerm
         */
        $oFakeTerm = new \stdClass();
        self::assertEquals($oFakeTerm, $oFrontendController->showTerm($oFakeTerm));

        $oTerm = $this->getTerm(1);
        self::assertEquals(null, $oFrontendController->showTerm($oTerm));
        self::assertEquals(
            $this->getTerm(1, 'taxonomy', 'name1BlogAdminHintText', 3),
            $oFrontendController->showTerm($oTerm)
        );

        $oTerm = $this->getTerm(107, 'taxonomy', null, 0, 106);
        self::assertEquals($this->getTerm(107, 'taxonomy', null, 0, 105), $oFrontendController->showTerm($oTerm));

        $oTerm = $this->getTerm(105, 'taxonomy', null, 0, 104);
        self::assertEquals($this->getTerm(105, 'taxonomy', null, 0, 104), $oFrontendController->showTerm($oTerm));

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
            $oFrontendController->showTerms($aTerms)
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
        $oItem = new \stdClass();
        $oItem->object = $sObjectType;
        $oItem->object_id = $sObjectId;
        $oItem->title = ($sTitle === null) ? "title{$sObjectId}" : $sTitle;

        return $oItem;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showCustomMenu()
     */
    public function testShowCustomMenu()
    {
        $oWordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->ID = 1;

        $oWordpress->expects($this->exactly(1))
            ->method('getCurrentUser')
            ->will($this->returnValue($oUser));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(14))
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

        $oConfig->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $oConfig->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $oConfig->expects($this->exactly(3))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['post'], ['post'])
            ->will($this->onConsecutiveCalls(false, false, true));

        $oConfig->expects($this->once())
            ->method('hidePostTypeTitle')
            ->with('post')
            ->will($this->returnValue(true));

        $oConfig->expects($this->once())
            ->method('getPostTypeTitle')
            ->with('post')
            ->will($this->returnValue('PostTypeTitle'));

        $oConfig->expects($this->once())
            ->method('hideEmptyTaxonomy')
            ->with('taxonomy')
            ->will($this->returnValue(true));

        $oConfig->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->returnValue(true));

        $oUtil = $this->getUtil();

        $oUtil->expects($this->once())
            ->method('endsWith')
            ->with('title1', 'BlogAdminHintText')
            ->will($this->returnValue(false));

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(8))
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

        $oObjectHandler->expects($this->exactly(4))
            ->method('isTaxonomy')
            ->withConsecutive(['other'], ['taxonomy'], ['taxonomy'], ['taxonomy'])
            ->will($this->returnCallback(function ($sType) {
                return ($sType === 'taxonomy');
            }));


        $oObjectHandler->expects($this->exactly(2))
            ->method('getTermTreeMap')
            ->will($this->returnValue([]));

        $oObjectHandler->expects($this->exactly(2))
            ->method('getTermPostMap')
            ->will($this->returnValue([]));

        $oObjectHandler->expects($this->exactly(3))
            ->method('getTerm')
            ->will($this->returnCallback(function ($iTermId) {
                return $this->getTerm($iTermId);
            }));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(7))
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

        $oAccessHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));

        $oAccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('other', 1)
            ->will($this->returnValue([1, 2]));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $oUtil,
            $this->getCache(),
            $oObjectHandler,
            $oAccessHandler,
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
            $oFrontendController->showCustomMenu($aItems)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showGroupMembership()
     */
    public function testShowGroupMembership()
    {
        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(3))
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

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        self::assertEquals('link', $oFrontendController->showGroupMembership('link', 1));
        self::assertEquals(
            'link | '.TXT_UAM_ASSIGNED_GROUPS.': name2',
            $oFrontendController->showGroupMembership('link', 1)
        );
        self::assertEquals(
            'link | '.TXT_UAM_ASSIGNED_GROUPS.': &lt;a&gt;test&lt;/a&gt;, name2',
            $oFrontendController->showGroupMembership('link', 1)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::showLoginForm()
     */
    public function testShowLoginForm()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(4))
            ->method('isSingle')
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $oWordpress->expects($this->exactly(2))
            ->method('isPage')
            ->will($this->onConsecutiveCalls(false, true));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertTrue($oFrontendController->showLoginForm());
        self::assertFalse($oFrontendController->showLoginForm());
        self::assertTrue($oFrontendController->showLoginForm());
        self::assertTrue($oFrontendController->showLoginForm());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getLoginUrl()
     */
    public function testGetLoginUrl()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getBlogInfo')
            ->with('wpurl')
            ->will($this->returnValue('BlogInfo'));

        $oWordpress->expects($this->once())
            ->method('applyFilters')
            ->with('uam_login_form_url', 'BlogInfo/wp-login.php')
            ->will($this->returnValue('filter'));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('filter', $oFrontendController->getLoginUrl());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getRedirectLoginUrl()
     */
    public function testGetRedirectLoginUrl()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getBlogInfo')
            ->with('wpurl')
            ->will($this->returnValue('BlogInfo'));

        $oWordpress->expects($this->once())
            ->method('applyFilters')
            ->with('uam_login_url', 'BlogInfo/wp-login.php?redirect_to=uri%40')
            ->will($this->returnValue('filter'));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $_SERVER['REQUEST_URI'] = 'uri@';

        self::assertEquals('filter', $oFrontendController->getRedirectLoginUrl());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getUserLogin()
     */
    public function testGetUserLogin()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('escHtml')
            ->with('/log/')
            ->will($this->returnValue('escHtml'));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $_GET['log'] = '/log\/';

        self::assertEquals('escHtml', $oFrontendController->getUserLogin());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getPostIdByUrl()
     */
    public function testGetPostIdByUrl()
    {
        $oCache = $this->getCache();

        $oCache->expects($this->exactly(6))
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

        $oCache->expects($this->exactly(4))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_URL_CACHE_KEY, ['url/part' => 1]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-e123/part' => 2]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-123x321/part' => 3]],
                [FrontendController::POST_URL_CACHE_KEY, ['url-e123-123x321/part' => 4]]
            );

        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(5))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oDatabase->expects($this->exactly(5))
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

        $oDatabase->expects($this->exactly(5))
            ->method('getRow')
            ->with('preparedQuery')
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1),
                $this->getPost(2),
                $this->getPost(3),
                $this->getPost(4)
            ));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $oDatabase,
            $this->getUtil(),
            $oCache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals(null, $oFrontendController->getPostIdByUrl('url/part'));
        self::assertEquals(1, $oFrontendController->getPostIdByUrl('url/part'));
        self::assertEquals(2, $oFrontendController->getPostIdByUrl('url-e123/part'));
        self::assertEquals(3, $oFrontendController->getPostIdByUrl('url-123x321/part'));
        self::assertEquals(4, $oFrontendController->getPostIdByUrl('url-e123-123x321/part'));
        self::assertEquals(1, $oFrontendController->getPostIdByUrl('url/part'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getFile()
     * @covers \UserAccessManager\Controller\FrontendController::_getFileSettingsByType()
     */
    public function testGetFile()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(5))
            ->method('getUploadDir')
            ->will($this->returnValue([
                'basedir' => '/baseDirectory/file/pictures/',
                'baseurl' => 'http://baseUrl/file/pictures/'
            ]));

        $oWordpress->expects($this->exactly(3))
            ->method('attachmentIsImage')
            ->will($this->onConsecutiveCalls(false, true, false));

        $oWordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_NO_RIGHTS);

        $oConfig = $this->getConfig();

        $oConfig->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('realPath/'));

        $oCache = $this->getCache();

        $oCache->expects($this->exactly(5))
            ->method('getFromCache')
            ->with(FrontendController::POST_URL_CACHE_KEY)
            ->will($this->returnValue(['http://baseUrl/file/pictures/url' => 1]));

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(5))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE),
                $this->getPost(1, ObjectHandler::ATTACHMENT_OBJECT_TYPE)
            ));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(3))
            ->method('checkObjectAccess')
            ->withConsecutive(
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1],
                [ObjectHandler::ATTACHMENT_OBJECT_TYPE, 1]
            )
            ->will($this->onConsecutiveCalls(false, false, true));

        $oFileHandler = $this->getFileHandler();

        $oFileHandler->expects($this->exactly(2))
            ->method('getFile')
            ->withConsecutive(
                ['realPath/gfx/noAccessPic.png', true],
                ['/baseDirectory/file/pictures/url', false]
            )
            ->will($this->returnValue('getFile'));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $oCache,
            $oObjectHandler,
            $oAccessHandler,
            $oFileHandler
        );

        self::assertEquals(null, $oFrontendController->getFile('type', 'url'));
        self::assertEquals(null, $oFrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals(null, $oFrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals(null, $oFrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals('getFile', $oFrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
        self::assertEquals('getFile', $oFrontendController->getFile(ObjectHandler::ATTACHMENT_OBJECT_TYPE, 'url'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::redirectUser()
     */
    public function testRedirectUser()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Query $oWpQuery
         */
        $oWpQuery = $this->getMockBuilder('\WP_Query')->setMethods(['get_posts'])->getMock();
        $oWpQuery->expects($this->once())
            ->method('get_posts')
            ->will($this->returnValue([
                $this->getPost(1),
                $this->getPost(2),
                $this->getPost(3)
            ]));

        $oPost = $this->getPost(1);
        $oPost->guid = 'guid';

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getWpQuery')
            ->will($this->returnValue($oWpQuery));

        $oWordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('HomeUrl'));

        $oWordpress->expects($this->exactly(3))
            ->method('getPageLink')
            ->with($oPost)
            ->will($this->returnValue('PageLink'));

        $oWordpress->expects($this->exactly(3))
            ->method('wpRedirect')
            ->withConsecutive(['guid'], ['RedirectCustomUrl'], ['HomeUrl']);

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(7))
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

        $oConfig->expects($this->exactly(4))
            ->method('getRedirectCustomPage')
            ->will($this->returnValue('RedirectCustomPage'));

        $oConfig->expects($this->once())
            ->method('getRedirectCustomUrl')
            ->will($this->returnValue('RedirectCustomUrl'));

        $oUtil = $this->getUtil();

        $oUtil->expects($this->exactly(7))
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

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive(['RedirectCustomPage'], ['RedirectCustomPage'], ['RedirectCustomPage'])
            ->will($this->onConsecutiveCalls(
                false,
                $oPost,
                $oPost,
                $oPost
            ));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $oUtil,
            $this->getCache(),
            $oObjectHandler,
            $oAccessHandler,
            $this->getFileHandler()
        );

        $oFrontendController->redirectUser();
        $oFrontendController->redirectUser(false);
        $oFrontendController->redirectUser(false);
        $oFrontendController->redirectUser(false);
        $oFrontendController->redirectUser(false);
        $oFrontendController->redirectUser(false);
        $oFrontendController->redirectUser(false);
        $oFrontendController->redirectUser(false);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::redirect()
     */
    public function testRedirect()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue(null));

        $oWordpress->expects($this->exactly(2))
            ->method('getPageByPath')
            ->with('pageNameValue')
            ->will($this->onConsecutiveCalls(null, $this->getPost(2)));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false, false, false, false));

        $oConfig->expects($this->exactly(9))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls('false', null, null, null, null, null, null, null, null));

        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oDatabase->expects($this->once())
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

        $oDatabase->expects($this->once())
            ->method('getVariable')
            ->with('preparedQuery')
            ->will($this->returnValue(1));

        $oUtil = $this->getUtil();
        $oUtil->expects($this->once())
            ->method('getCurrentUrl')
            ->will($this->returnValue('currentUrl'));

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post', 'page', 'other']));

        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(7))
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

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $oDatabase,
            $oUtil,
            $this->getCache(),
            $oObjectHandler,
            $oAccessHandler,
            $this->getFileHandler()
        );

        $oPageParams = new \stdClass();

        $_GET['uamfiletype'] = 'fileType';
        $oPageParams->query_vars = [];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $oPageParams->query_vars = [];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $oPageParams->query_vars = [];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $oPageParams->query_vars = ['p' => 'pValue'];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        unset($_GET['uamfiletype']);
        $_GET['uamgetfile'] = 'file';
        $oPageParams->query_vars = ['page_id' => 'pageIdValue'];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $oPageParams->query_vars = ['cat_id' => 'catIdValue'];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $oPageParams->query_vars = ['name' => 'nameValue'];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $oPageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $oPageParams->query_vars = ['pagename' => 'pageNameValue'];
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));

        $_GET['uamfiletype'] = 'fileType';
        self::assertEquals('header', $oFrontendController->redirect('header', $oPageParams));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getFileUrl()
     */
    public function testGetFileUrl()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->with('/')
            ->will($this->returnValue('homeUrl'));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(6))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $oConfig->expects($this->exactly(5))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $oConfig->expects($this->exactly(3))
            ->method('getLockedFileTypes')
            ->will($this->onConsecutiveCalls('none', 'all', 'aaa,mime'));

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(4))
            ->method('getPost')
            ->withConsecutive([1], [1], [1], [1])
            ->will($this->onConsecutiveCalls(
                null,
                $this->getPost(1, 'post', null, null, false, 'type'),
                $this->getPost(1),
                $this->getPost(1)
            ));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $oObjectHandler,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('url', $oFrontendController->getFileUrl('url', 1));
        self::assertEquals('url', $oFrontendController->getFileUrl('url', 1));
        self::assertEquals('url', $oFrontendController->getFileUrl('url', 1));
        self::assertEquals('url', $oFrontendController->getFileUrl('url', 1));
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $oFrontendController->getFileUrl('url', 1)
        );
        self::assertEquals(
            'homeUrl?uamfiletype=attachment&uamgetfile=url',
            $oFrontendController->getFileUrl('url', 1)
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::cachePostLinks()
     */
    public function testCachePostLinks()
    {
        $oCache = $this->getCache();

        $oCache->expects($this->exactly(2))
            ->method('getFromCache')
            ->with(FrontendController::POST_URL_CACHE_KEY)
            ->will($this->onConsecutiveCalls(
                null,
                ['firstUrl' => 1]
            ));

        $oCache->expects($this->exactly(2))
            ->method('addToCache')
            ->withConsecutive(
                [FrontendController::POST_URL_CACHE_KEY, ['firstUrl' => 1]],
                [FrontendController::POST_URL_CACHE_KEY, ['firstUrl' => 1, 'secondUrl' => 2]]
            );

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $oCache,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertEquals('firstUrl', $oFrontendController->cachePostLinks('firstUrl', $this->getPost(1)));
        self::assertEquals('secondUrl', $oFrontendController->cachePostLinks('secondUrl', $this->getPost(2)));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::getWpSeoUrl()
     */
    public function testGetWpSeoUrl()
    {
        $oAccessHandler = $this->getAccessHandler();

        $oAccessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['type', 1],
                ['type', 1]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false
            ));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        $oObject = new \stdClass();
        $oObject->ID = 1;

        self::assertEquals('url', $oFrontendController->getWpSeoUrl('url', 'type', $oObject));
        self::assertFalse($oFrontendController->getWpSeoUrl('url', 'type', $oObject));
    }
}
