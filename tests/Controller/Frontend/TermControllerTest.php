<?php
/**
 * TermControllerTest.php
 *
 * The TermControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller\Frontend;

use UserAccessManager\Controller\Frontend\TermController;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class TermControllerTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\TermController
 */
class TermControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $frontendController = new TermController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf(TermController::class, $frontendController);
    }

    /**
     * @group  unit
     * @covers ::getTermArguments()
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

        $frontendController = new TermController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $accessHandler
        );

        self::assertEquals(['exclude' => [1, 3]], $frontendController->getTermArguments([]));
        self::assertEquals(['exclude' => [3, 4, 1]], $frontendController->getTermArguments(['exclude' => '3,4']));
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
     * @covers ::showTerm()
     * @covers ::showTerms()
     * @covers ::getVisibleElementsCount()
     * @covers ::getAllPostForTerm()
     * @covers ::processTerm()
     * @covers ::updateTermParent()
     * @covers ::getPostObjectHideConfig()
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

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(14))
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

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(8))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, false, false, true, true, false, true, true));

        $mainConfig->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $mainConfig->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $mainConfig->expects($this->exactly(3))
            ->method('hidePostType')
            ->withConsecutive(['customPost'], ['post'], ['page'])
            ->will($this->returnCallback(function ($type) {
                return ($type === 'customPost') ? false : true;
            }));

        $mainConfig->expects($this->exactly(4))
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
                            1 => [2 => 'taxonomy', 3 => 'taxonomy']
                        ]
                    ]
                ]
            ));

        $objectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['customPost' => 'customPost', 'post' => 'post', 'page' => 'page']));

        $objectHandler->expects($this->exactly(8))
            ->method('getTermPostMap')
            ->will($this->returnValue(
                [
                    1 => [10 => 'customPost', 11 => 'post', 12 => 'page'],
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

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));


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
            ->method('getUserGroupsForObject')
            ->with('taxonomy', 1)
            ->will($this->returnValue([1, 2]));

        $frontendController = new TermController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $util,
            $objectHandler,
            $userHandler,
            $accessHandler
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
     * @covers ::showCustomMenu()
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

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(15))
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
                true,
                true
            ));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->once())
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(true));

        $mainConfig->expects($this->once())
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('BlogAdminHintText'));

        $mainConfig->expects($this->exactly(6))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['post'], ['customPost'], ['post'], ['page'], ['customPost'])
            ->will($this->returnCallback(function ($type) {
                return ($type !== 'post');
            }));

        $mainConfig->expects($this->once())
            ->method('hidePostTypeTitle')
            ->with('post')
            ->will($this->returnValue(true));

        $mainConfig->expects($this->once())
            ->method('getPostTypeTitle')
            ->with('post')
            ->will($this->returnValue('PostTypeTitle'));

        $mainConfig->expects($this->once())
            ->method('hideEmptyTaxonomy')
            ->with('taxonomy')
            ->will($this->returnValue(true));

        $mainConfig->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->returnValue(true));

        $util = $this->getUtil();

        $util->expects($this->once())
            ->method('endsWith')
            ->with('title1', 'BlogAdminHintText')
            ->will($this->returnValue(false));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(9))
            ->method('isPostType')
            ->withConsecutive(
                ['other'],
                ['post'],
                ['post'],
                ['post'],
                ['customPost'],
                ['taxonomy'],
                ['taxonomy'],
                ['taxonomy'],
                ['taxonomy']
            )
            ->will($this->returnCallback(function ($type) {
                return ($type === 'post' || $type === 'customPost');
            }));

        $objectHandler->expects($this->exactly(5))
            ->method('isTaxonomy')
            ->withConsecutive(['other'], ['taxonomy'], ['taxonomy'], ['taxonomy'], ['taxonomy'])
            ->will($this->returnCallback(function ($type) {
                return ($type === 'taxonomy');
            }));

        $objectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'page' => 'page', 'customPost' => 'customPost']));

        $objectHandler->expects($this->exactly(2))
            ->method('getTermTreeMap')
            ->will($this->returnValue([]));

        $objectHandler->expects($this->exactly(2))
            ->method('getTermPostMap')
            ->will($this->returnValue([]));

        $objectHandler->expects($this->exactly(4))
            ->method('getTerm')
            ->will($this->returnCallback(function ($termId) {
                if ($termId === 4) {
                    return false;
                }

                return $this->getTerm($termId);
            }));


        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('userIsAdmin')
            ->with(1)
            ->will($this->returnValue(true));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(7))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['post', 1],
                ['post', 2],
                ['post', 3],
                ['customPost', 4],
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
            ->method('getUserGroupsForObject')
            ->with('other', 1)
            ->will($this->returnValue([1, 2]));

        $frontendController = new TermController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $util,
            $objectHandler,
            $userHandler,
            $accessHandler
        );

        $items = [
            1 => $this->getItem('other', 1),
            2 => $this->getItem('post', 1),
            3 => $this->getItem('post', 2),
            4 => $this->getItem('post', 3),
            5 => $this->getItem('customPost', 4),
            6 => $this->getItem('taxonomy', 1),
            7 => $this->getItem('taxonomy', 2),
            8 => $this->getItem('taxonomy', 3),
            9 => $this->getItem('taxonomy', 4)
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
}
