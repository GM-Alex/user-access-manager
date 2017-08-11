<?php
/**
 * ObjectHandlerTest.php
 *
 * The ObjectHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\ObjectHandler;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\ObjectMembership\PostMembershipHandler;
use UserAccessManager\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\TermMembershipHandler;
use UserAccessManager\ObjectMembership\UserMembershipHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class ObjectHandlerTest
 *
 * @package UserAccessManager\ObjectHandler
 * @coversDefaultClass \UserAccessManager\ObjectHandler\ObjectHandler
 */
class ObjectHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectMembershipHandlerFactory()
        );

        self::assertInstanceOf(ObjectHandler::class, $objectHandler);
    }

    /**
     * @group  unit
     * @covers ::getPostTypes()
     *
     * @return ObjectHandler
     */
    public function testGetPostTypes()
    {
        $return = ['a' => 'a1', 'b' => 'b1'];

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($return));

        $database = $this->getDatabase();
        $cache = $this->getCache();

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );
        self::assertEquals($return, $objectHandler->getPostTypes());
        self::assertEquals($return, $objectHandler->getPostTypes());

        return $objectHandler;
    }

    /**
     * @group  unit
     * @covers ::getTaxonomies()
     *
     * @return ObjectHandler
     */
    public function testGetTaxonomies()
    {
        $return = ['a' => 'a1', 'b' => 'b1'];

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue($return));

        $database = $this->getDatabase();
        $cache = $this->getCache();

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );
        self::assertEquals($return, $objectHandler->getTaxonomies());
        self::assertEquals($return, $objectHandler->getTaxonomies());

        return $objectHandler;
    }

    /**
     * @group  unit
     * @covers ::getUser()
     */
    public function testGetUser()
    {
        /**
         * @var \stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->id = 1;

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(2))
            ->method('getUserData')
            ->withConsecutive([123], [321])
            ->will($this->onConsecutiveCalls($user, false));

        $database = $this->getDatabase();
        $cache = $this->getCache();

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );
        self::assertEquals($user, $objectHandler->getUser(123));
        self::assertEquals($user, $objectHandler->getUser(123));
        self::assertFalse($objectHandler->getUser(321));
        self::assertFalse($objectHandler->getUser(321));
    }

    /**
     * @group  unit
     * @covers ::getPost()
     */
    public function testGetPost()
    {
        /**
         * @var \stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->id = 1;

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(3))
            ->method('getPost')
            ->withConsecutive([123], [321], [231])
            ->will($this->onConsecutiveCalls($post, null, ['id' => 2]));

        $database = $this->getDatabase();
        $cache = $this->getCache();

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );

        self::assertEquals($post, $objectHandler->getPost(123));
        self::assertEquals($post, $objectHandler->getPost(123));
        self::assertFalse($objectHandler->getPost(321));
        self::assertFalse($objectHandler->getPost(321));
        self::assertFalse($objectHandler->getPost(231));
        self::assertFalse($objectHandler->getPost(231));
    }

    /**
     * @group  unit
     * @covers ::getTerm()
     */
    public function testGetTerm()
    {
        /**
         * @var \stdClass $term
         */
        $term = $this->getMockBuilder('\WP_Term')->getMock();
        $term->id = 1;

        $error = $this->getMockBuilder('\WP_Error')->getMock();

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('getTerm')
            ->withConsecutive([123, ''], [321, 'firstTax'], [231, 'secondTax'], [231])
            ->will($this->onConsecutiveCalls($term, null, ['id' => 2], $error));

        $database = $this->getDatabase();
        $cache = $this->getCache();

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );

        self::assertEquals($term, $objectHandler->getTerm(123));
        self::assertEquals($term, $objectHandler->getTerm(123));
        self::assertFalse($objectHandler->getTerm(321, 'firstTax'));
        self::assertFalse($objectHandler->getTerm(321, 'firstTax'));
        self::assertFalse($objectHandler->getTerm(231, 'secondTax'));
        self::assertFalse($objectHandler->getTerm(231, 'secondTax'));
        self::assertFalse($objectHandler->getTerm(231));
        self::assertFalse($objectHandler->getTerm(231));
    }

    /**
     * @param int    $id
     * @param int    $parentId
     * @param string $type
     *
     * @return \stdClass
     */
    private function createTreeMapDbResultElement($id, $parentId = 0, $type = 'post')
    {
        $element = new \stdClass();
        $element->id = $id;
        $element->type = $type;
        $element->parentId = $parentId;

        return $element;
    }

    /**
     * @param string $generalType
     * @param string $firstType
     * @param string $secondType
     *
     * @return array
     */
    private function getExpectedMapResult($generalType, $firstType = 'post', $secondType = 'page')
    {
        $result = [
            ObjectHandler::TREE_MAP_CHILDREN => [
                $firstType => [
                    0 => [
                        1 => $firstType, 2 => $firstType, 3 => $firstType,
                        4 => $firstType, 123 => $firstType, 321 => $firstType
                    ],
                    1 => [2 => $firstType, 3 => $firstType, 4 => $firstType, 123 => $firstType, 321 => $firstType],
                    2 => [3 => $firstType, 4 => $firstType, 123 => $firstType, 321 => $firstType],
                    3 => [123 => $firstType, 321 => $firstType, 4 => $firstType],
                    11 => [4 => $firstType]
                ],
                $secondType => [
                    6 => [7 => $secondType, 8 => $secondType],
                    7 => [8 => $secondType]
                ]
            ],
            ObjectHandler::TREE_MAP_PARENTS => [
                $firstType => [
                    1 => [0 => $firstType],
                    2 => [0 => $firstType, 1 => $firstType],
                    3 => [0 => $firstType, 1 => $firstType, 2 => $firstType],
                    4 => [0 => $firstType, 1 => $firstType, 2 => $firstType, 3 => $firstType, 11 => $firstType],
                    123 => [0 => $firstType, 1 => $firstType, 2 => $firstType, 3 => $firstType],
                    321 => [0 => $firstType, 1 => $firstType, 2 => $firstType, 3 => $firstType]
                ],
                $secondType => [
                    7 => [6 => $secondType],
                    8 => [6 => $secondType, 7 => $secondType]
                ]
            ]
        ];

        $result[ObjectHandler::TREE_MAP_CHILDREN][$generalType] =
            $result[ObjectHandler::TREE_MAP_CHILDREN][$firstType]
            + $result[ObjectHandler::TREE_MAP_CHILDREN][$secondType];

        $result[ObjectHandler::TREE_MAP_PARENTS][$generalType] =
            $result[ObjectHandler::TREE_MAP_PARENTS][$firstType]
            + $result[ObjectHandler::TREE_MAP_PARENTS][$secondType];
        
        return $result;
    }

    /**
     * @group  unit
     * @covers ::processTreeMapElements()
     * @covers ::getTreeMap()
     * @covers ::getPostTreeMap()
     * @covers ::getTermTreeMap()
     */
    public function testTreeMap()
    {
        $postResult = [];
        $postResult[] = $this->createTreeMapDbResultElement(1);
        $postResult[] = $this->createTreeMapDbResultElement(2, 1);
        $postResult[] = $this->createTreeMapDbResultElement(3, 2);
        $postResult[] = $this->createTreeMapDbResultElement(4, 2);
        $postResult[] = $this->createTreeMapDbResultElement(4, 3);
        $postResult[] = $this->createTreeMapDbResultElement(4, 11);
        $postResult[] = $this->createTreeMapDbResultElement(123, 3);
        $postResult[] = $this->createTreeMapDbResultElement(321, 3);
        $postResult[] = $this->createTreeMapDbResultElement(7, 6, 'page');
        $postResult[] = $this->createTreeMapDbResultElement(8, 7, 'page');

        $termResult = [];
        $termResult[] = $this->createTreeMapDbResultElement(1, 0, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(2, 1, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(3, 2, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(4, 2, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(4, 3, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(4, 11, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(123, 3, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(321, 3, 'category');
        $termResult[] = $this->createTreeMapDbResultElement(7, 6, 'term');
        $termResult[] = $this->createTreeMapDbResultElement(8, 7, 'term');

        $expectedPostResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_POST_OBJECT_TYPE);
        $expectedTermResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'category', 'term');

        $wordpress = $this->getWordpress();
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $database->expects($this->exactly(2))
            ->method('getResults')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'SELECT ID AS id, post_parent AS parentId, post_type AS type 
                    FROM postTable
                    WHERE post_parent != 0 AND post_type != \'revision\''
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT term_id AS id, parent AS parentId, taxonomy AS type
                    FROM termTaxonomyTable
                    WHERE parent != 0'
                )]
            )->will($this->onConsecutiveCalls($postResult, $termResult));

        $cache = $this->getCache();
        $cache->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                [ObjectHandler::POST_TREE_MAP_CACHE_KEY],
                [ObjectHandler::TERM_TREE_MAP_CACHE_KEY],
                [ObjectHandler::POST_TREE_MAP_CACHE_KEY],
                [ObjectHandler::TERM_TREE_MAP_CACHE_KEY]
            )
            ->will($this->onConsecutiveCalls(null, null, ['cachedPostTree'], ['cachedTermTree']));


        $cache->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [ObjectHandler::POST_TREE_MAP_CACHE_KEY, $expectedPostResult],
                [ObjectHandler::TERM_TREE_MAP_CACHE_KEY, $expectedTermResult]
            );

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );

        self::assertEquals($expectedPostResult, $objectHandler->getPostTreeMap());
        self::assertEquals($expectedPostResult, $objectHandler->getPostTreeMap());
        self::assertEquals($expectedTermResult, $objectHandler->getTermTreeMap());
        self::assertEquals($expectedTermResult, $objectHandler->getTermTreeMap());

        self::setValue($objectHandler, 'postTreeMap', null);
        self::assertEquals(['cachedPostTree'], $objectHandler->getPostTreeMap());

        self::setValue($objectHandler, 'termTreeMap', null);
        self::assertEquals(['cachedTermTree'], $objectHandler->getTermTreeMap());
    }

    /**
     * @param int    $objectId
     * @param int    $termId
     * @param string $postType
     * @param string $termType
     *
     * @return \stdClass
     */
    private function createTermMapDbResultElement($objectId, $termId, $postType = 'post', $termType = 'category')
    {
        $element = new \stdClass();
        $element->objectId = $objectId;
        $element->termId = $termId;
        $element->postType = $postType;
        $element->termType = $termType;

        return $element;
    }

    /**
     * @group  unit
     * @covers ::getTermPostMap()
     */
    public function testGetTermPostMap()
    {
        $databaseResult = [];
        $databaseResult[] = $this->createTermMapDbResultElement(1, 1);
        $databaseResult[] = $this->createTermMapDbResultElement(2, 1);
        $databaseResult[] = $this->createTermMapDbResultElement(1, 2);
        $databaseResult[] = $this->createTermMapDbResultElement(3, 2);
        $databaseResult[] = $this->createTermMapDbResultElement(4, 2);
        $databaseResult[] = $this->createTermMapDbResultElement(123, 3);
        $databaseResult[] = $this->createTermMapDbResultElement(321, 3);
        $databaseResult[] = $this->createTermMapDbResultElement(7, 6, 'page');
        $databaseResult[] = $this->createTermMapDbResultElement(8, 7, 'page');

        $expectedResult = [
            1 => [1 => 'post', 2 => 'post'],
            2 => [1 => 'post', 3 => 'post', 4 => 'post'],
            3 => [123 => 'post', 321 => 'post'],
            6 => [7 => 'page'],
            7 => [8 => 'page']
        ];

        $wordpress = $this->getWordpress();
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $database->expects($this->once())
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $database->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS termId, p.post_type AS postType
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN postTable AS p ON (tr.object_id = p.ID)
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($databaseResult));

        $cache = $this->getCache();

        $cache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [ObjectHandler::TERM_POST_MAP_CACHE_KEY],
                [ObjectHandler::TERM_POST_MAP_CACHE_KEY]
            )
            ->will($this->onConsecutiveCalls(null, ['cachedTermPostMap']));

        $cache->expects($this->once())
            ->method('add')
            ->with(ObjectHandler::TERM_POST_MAP_CACHE_KEY, $expectedResult);

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );

        self::assertEquals($expectedResult, $objectHandler->getTermPostMap());
        self::assertEquals($expectedResult, $objectHandler->getTermPostMap());

        self::setValue($objectHandler, 'termPostMap', null);
        self::assertEquals(['cachedTermPostMap'], $objectHandler->getTermPostMap());
    }

    /**
     * @group   unit
     * @covers  ::getPostTermMap()
     */
    public function testGetPostTermMap()
    {
        $databaseResult = [];
        $databaseResult[] = $this->createTermMapDbResultElement(1, 1);
        $databaseResult[] = $this->createTermMapDbResultElement(2, 1);
        $databaseResult[] = $this->createTermMapDbResultElement(1, 2);
        $databaseResult[] = $this->createTermMapDbResultElement(3, 2);
        $databaseResult[] = $this->createTermMapDbResultElement(4, 2);
        $databaseResult[] = $this->createTermMapDbResultElement(123, 3);
        $databaseResult[] = $this->createTermMapDbResultElement(321, 3);
        $databaseResult[] = $this->createTermMapDbResultElement(7, 6, 'page', 'term');
        $databaseResult[] = $this->createTermMapDbResultElement(8, 7, 'page', 'term');

        $expectedResult = [
            1 => [1 => 'category', 2 => 'category'],
            2 => [1 => 'category'],
            3 => [2 => 'category'],
            4 => [2 => 'category'],
            123 => [3 => 'category'],
            321 => [3 => 'category'],
            7 => [6 => 'term'],
            8 => [7 => 'term']
        ];

        $wordpress = $this->getWordpress();
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $database->expects($this->once())
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $database->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS termId, tt.taxonomy AS termType
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($databaseResult));

        $cache = $this->getCache();

        $cache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [ObjectHandler::POST_TERM_MAP_CACHE_KEY],
                [ObjectHandler::POST_TERM_MAP_CACHE_KEY]
            )
            ->will($this->onConsecutiveCalls(null, ['cachedPostTermMap']));

        $cache->expects($this->once())
            ->method('add')
            ->with(ObjectHandler::POST_TERM_MAP_CACHE_KEY, $expectedResult);

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );

        self::assertEquals($expectedResult, $objectHandler->getPostTermMap());
        self::assertEquals($expectedResult, $objectHandler->getPostTermMap());

        self::setValue($objectHandler, 'postTermMap', null);
        self::assertEquals(['cachedPostTermMap'], $objectHandler->getPostTermMap());
    }

    /**
     * @group   unit
     * @depends testGetPostTypes
     * @covers  ::registeredPostType()
     *
     * @param ObjectHandler $objectHandler
     *
     * @return ObjectHandler
     */
    public function testRegisteredPostType(ObjectHandler $objectHandler)
    {
        /**
         * @var \stdClass|\WP_Post_Type $arguments
         */
        $arguments = $this->getMockBuilder('\WP_Post_Type')->getMock();
        $arguments->public = false;

        $expectedResult = [
            'a' => 'a1',
            'b' => 'b1'
        ];

        $objectHandler->registeredPostType('postType', $arguments);
        self::assertAttributeEquals($expectedResult, 'postTypes', $objectHandler);

        $arguments->public = true;
        $expectedResult['postType'] = 'postType';

        $objectHandler->registeredPostType('postType', $arguments);
        self::assertAttributeEquals($expectedResult, 'postTypes', $objectHandler);
        self::assertAttributeEquals(null, 'objectTypes', $objectHandler);
        self::assertAttributeEquals(null, 'allObjectTypes', $objectHandler);
        self::assertAttributeEquals([], 'validObjectTypes', $objectHandler);

        return $objectHandler;
    }

    /**
     * @group   unit
     * @depends testGetTaxonomies
     * @covers  ::registeredTaxonomy()
     *
     * @param ObjectHandler $objectHandler
     *
     * @return ObjectHandler
     */
    public function testRegisteredTaxonomy(ObjectHandler $objectHandler)
    {
        $arguments = ['public' => false];
        $expectedResult = [
            'a' => 'a1',
            'b' => 'b1'
        ];

        $objectHandler->registeredTaxonomy('taxonomy', 'objectType', $arguments);
        self::assertAttributeEquals($expectedResult, 'taxonomies', $objectHandler);

        $arguments = ['public' => true];
        $expectedResult['taxonomy'] = 'taxonomy';

        $objectHandler->registeredTaxonomy('taxonomy', 'objectType', $arguments);
        self::assertAttributeEquals($expectedResult, 'taxonomies', $objectHandler);
        self::assertAttributeEquals(null, 'objectTypes', $objectHandler);
        self::assertAttributeEquals(null, 'allObjectTypes', $objectHandler);
        self::assertAttributeEquals([], 'validObjectTypes', $objectHandler);

        return $objectHandler;
    }

    /**
     * @group   unit
     * @depends testRegisteredPostType
     * @covers  ::isPostType()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testIsPostType(ObjectHandler $objectHandler)
    {
        self::assertTrue($objectHandler->isPostType('postType'));
        self::assertFalse($objectHandler->isPostType('missing'));
    }

    /**
     * @group  unit
     * @covers ::isTaxonomy()
     */
    public function testIsTaxonomy()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne' => 'taxonomyOne', 'taxonomyTwo' => 'taxonomyTwo']));

        $database = $this->getDatabase();
        $cache = $this->getCache();

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );

        self::assertTrue($objectHandler->isTaxonomy('taxonomyOne'));
        self::assertTrue($objectHandler->isTaxonomy('taxonomyTwo'));
        self::assertFalse($objectHandler->isTaxonomy('invalid'));
    }

    /**
     * @group  unit
     * @covers ::getObjectTypes()
     *
     * @return ObjectHandler
     */
    public function testGetObjectTypes()
    {
        $taxonomiesReturn = ['a' => 'a1', 'b' => 'b1'];

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->with(['public' => true])
            ->will($this->returnValue($taxonomiesReturn));

        $postTypesReturn = ['c' => 'c1', 'd' => 'd1'];

        $wordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($postTypesReturn));

        $database = $this->getDatabase();
        $cache = $this->getCache();

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
            $database,
            $cache,
            $this->getObjectMembershipHandlerFactory()
        );

        $expectation = [
            'a' => 'a1',
            'b' => 'b1',
            'c' => 'c1',
            'd' => 'd1'
        ];

        self::assertEquals($expectation, $objectHandler->getObjectTypes());
        self::assertEquals($expectation, $objectHandler->getObjectTypes());

        return $objectHandler;
    }

    /**
     * @group   unit
     * @covers  ::getAllObjectTypes()
     * @covers  ::getAllObjectsTypesMap()
     * @covers  ::getObjectMembershipHandlers()
     *
     * @return ObjectHandler
     */
    public function testGetAllObjectTypes()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(5))
            ->method('arrayFill')
            ->withConsecutive(
                [0, 2, 'role'],
                [0, 2, 'user'],
                [0, 2, 'term'],
                [0, 2, 'post'],
                [0, 2, 'someObject']
            )->will($this->returnCallback(function ($startIndex, $numberOfElements, $value) {
                return array_fill($startIndex, $numberOfElements, $value);
            }));

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->any())
            ->method('applyFilters')
            ->with('uam_register_object_membership_handler')
            ->will($this->returnCallback(function ($filter, $objectMembershipHandlers) {
                if ($filter === 'uam_register_object_membership_handler') {
                    $objectMembershipHandlers['someObject'] = $this->getMembershipHandler(
                        ObjectMembershipHandler::class,
                        'someObject',
                        [2]
                    );

                    return $objectMembershipHandlers;
                }

                return [];
            }));

        $postMembershipHandler = $this->getMembershipHandler(PostMembershipHandler::class, 'post', [2]);
        $roleMembershipHandler = $this->getMembershipHandler(RoleMembershipHandler::class, 'role', [2]);
        $termMembershipHandler = $this->getMembershipHandler(TermMembershipHandler::class, 'term', [2]);
        $userMembershipHandler = $this->getMembershipHandler(UserMembershipHandler::class, 'user', [2]);

        $membershipHandlerFactory = $this->getObjectMembershipHandlerFactory();

        $membershipHandlerFactory->expects($this->any())
            ->method('createPostMembershipHandler')
            ->will($this->returnValue($postMembershipHandler));

        $membershipHandlerFactory->expects($this->any())
            ->method('createRoleMembershipHandler')
            ->will($this->returnValue($roleMembershipHandler));

        $membershipHandlerFactory->expects($this->any())
            ->method('createTermMembershipHandler')
            ->will($this->returnValue($termMembershipHandler));

        $membershipHandlerFactory->expects($this->any())
            ->method('createUserMembershipHandler')
            ->will($this->returnValue($userMembershipHandler));

        $objectHandler = new ObjectHandler(
            $php,
            $wordpress,
            $this->getDatabase(),
            $this->getCache(),
            $membershipHandlerFactory
        );

        $expectation = [
            'role' => 'role',
            'otherRole' => 'otherRole',
            'user' => 'user',
            'otherUser' => 'otherUser',
            'term' => 'term',
            'otherTerm' => 'otherTerm',
            'post' => 'post',
            'otherPost' => 'otherPost',
            'someObject' => 'someObject',
            'otherSomeObject' => 'otherSomeObject'
        ];

        self::assertEquals($expectation, $objectHandler->getAllObjectTypes());

        return $objectHandler;
    }

    /**
     * @group   unit
     * @depends testGetAllObjectTypes
     * @covers  ::getGeneralObjectType()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testGetGeneralObjectType(ObjectHandler $objectHandler)
    {
        self::assertEquals('role', $objectHandler->getGeneralObjectType('role'));
        self::assertEquals('role', $objectHandler->getGeneralObjectType('otherRole'));
        self::assertEquals('user', $objectHandler->getGeneralObjectType('user'));
        self::assertEquals('user', $objectHandler->getGeneralObjectType('otherUser'));
        self::assertEquals('term', $objectHandler->getGeneralObjectType('term'));
        self::assertEquals('term', $objectHandler->getGeneralObjectType('otherTerm'));
        self::assertEquals('post', $objectHandler->getGeneralObjectType('post'));
        self::assertEquals('post', $objectHandler->getGeneralObjectType('otherPost'));
        self::assertEquals('someObject', $objectHandler->getGeneralObjectType('someObject'));
        self::assertEquals('someObject', $objectHandler->getGeneralObjectType('otherSomeObject'));
        
        self::assertNull($objectHandler->getGeneralObjectType('invalid'));
    }

    /**
     * @group   unit
     * @depends testGetAllObjectTypes
     * @covers  ::isValidObjectType()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testIsValidObjectType(ObjectHandler $objectHandler)
    {
        self::assertTrue($objectHandler->isValidObjectType('role'));
        self::assertTrue($objectHandler->isValidObjectType('otherRole'));
        self::assertTrue($objectHandler->isValidObjectType('user'));
        self::assertTrue($objectHandler->isValidObjectType('otherUser'));
        self::assertTrue($objectHandler->isValidObjectType('term'));
        self::assertTrue($objectHandler->isValidObjectType('otherTerm'));
        self::assertTrue($objectHandler->isValidObjectType('post'));
        self::assertTrue($objectHandler->isValidObjectType('otherPost'));
        self::assertTrue($objectHandler->isValidObjectType('someObject'));
        self::assertTrue($objectHandler->isValidObjectType('otherSomeObject'));
        self::assertFalse($objectHandler->isValidObjectType('invalid'));
    }

    /**
     * @group   unit
     * @depends testGetAllObjectTypes
     * @covers  ::getObjectMembershipHandlers()
     * @covers  ::getObjectMembershipHandler()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testGetObjectMembershipHandler(ObjectHandler $objectHandler)
    {
        $postMembershipHandler = $this->getMembershipHandler(PostMembershipHandler::class, 'post', [2]);
        $roleMembershipHandler = $this->getMembershipHandler(RoleMembershipHandler::class, 'role', [2]);
        $termMembershipHandler = $this->getMembershipHandler(TermMembershipHandler::class, 'term', [2]);
        $userMembershipHandler = $this->getMembershipHandler(UserMembershipHandler::class, 'user', [2]);

        self::assertEquals($postMembershipHandler, $objectHandler->getObjectMembershipHandler('post'));
        self::assertEquals($roleMembershipHandler, $objectHandler->getObjectMembershipHandler('role'));
        self::assertEquals($termMembershipHandler, $objectHandler->getObjectMembershipHandler('term'));
        self::assertEquals($userMembershipHandler, $objectHandler->getObjectMembershipHandler('user'));
    }

    /**
     * @group   unit
     * @depends testGetAllObjectTypes
     * @covers  ::getObjectMembershipHandler()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testGetObjectMembershipHandlerException(ObjectHandler $objectHandler)
    {
        self::expectException(MissingObjectMembershipHandlerException::class);
        self::expectExceptionMessage('Missing membership handler for \'invalid\'.');
        $objectHandler->getObjectMembershipHandler('invalid');
    }
}
