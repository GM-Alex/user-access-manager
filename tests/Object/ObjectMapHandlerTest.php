<?php
/**
 * ObjectMapHandlerTest.php
 *
 * The ObjectMapHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Object;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\Tests\CountableArrayHelper;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class ObjectMapHandlerTest
 *
 * @package UserAccessManager\Tests\Object
 * @coversDefaultClass \UserAccessManager\Object\ObjectMapHandler
 */
class ObjectMapHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectMapHandler = new ObjectMapHandler(
            $this->getDatabase(),
            $this->getCache()
        );

        self::assertInstanceOf(ObjectMapHandler::class, $objectMapHandler);
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
            ObjectMapHandler::TREE_MAP_CHILDREN => [
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
            ObjectMapHandler::TREE_MAP_PARENTS => [
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

        $result[ObjectMapHandler::TREE_MAP_CHILDREN][$generalType] =
            $result[ObjectMapHandler::TREE_MAP_CHILDREN][$firstType]
            + $result[ObjectMapHandler::TREE_MAP_CHILDREN][$secondType];

        $result[ObjectMapHandler::TREE_MAP_PARENTS][$generalType] =
            $result[ObjectMapHandler::TREE_MAP_PARENTS][$firstType]
            + $result[ObjectMapHandler::TREE_MAP_PARENTS][$secondType];

        return $result;
    }

    /**
     * @group  unit
     * @covers ::processTreeMapElements()
     * @covers ::getCachedTreeMap()
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

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->exactly(2))
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
                [ObjectMapHandler::POST_TREE_MAP_CACHE_KEY],
                [ObjectMapHandler::TERM_TREE_MAP_CACHE_KEY],
                [ObjectMapHandler::POST_TREE_MAP_CACHE_KEY],
                [ObjectMapHandler::TERM_TREE_MAP_CACHE_KEY]
            )
            ->will($this->onConsecutiveCalls(null, null, ['cachedPostTree'], ['cachedTermTree']));


        $cache->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [ObjectMapHandler::POST_TREE_MAP_CACHE_KEY, $expectedPostResult],
                [ObjectMapHandler::TERM_TREE_MAP_CACHE_KEY, $expectedTermResult]
            );

        $objectMapHandler = new ObjectMapHandler(
            $database,
            $cache
        );

        self::assertEquals($expectedPostResult, $objectMapHandler->getPostTreeMap());
        self::assertEquals($expectedPostResult, $objectMapHandler->getPostTreeMap());
        self::assertEquals($expectedTermResult, $objectMapHandler->getTermTreeMap());
        self::assertEquals($expectedTermResult, $objectMapHandler->getTermTreeMap());

        self::setValue($objectMapHandler, 'postTreeMap', null);
        self::assertEquals(['cachedPostTree'], $objectMapHandler->getPostTreeMap());

        self::setValue($objectMapHandler, 'termTreeMap', null);
        self::assertEquals(['cachedTermTree'], $objectMapHandler->getTermTreeMap());
        
        $map = [
            0 => [1 => 'post'],
            1 => [2 => 'post'],
            2 => [3 => 'post', 4 => 'post'],
            3 => [4 => 'post', 123 => 'post', 321 => 'post'],
            11 => [4 => 'post'],
            6 => [7 => 'page'],
            7 => [8 => 'page']
        ];
        $processed = [0 => [1 => 1]];
        $expected = [
            0 => [1 => 'post'],
            1 => [2 => 'post', 3 => 'post', 4 => 'post', 123 => 'post', 321 => 'post'],
            2 => [3 => 'post', 4 => 'post', 123 => 'post', 321 => 'post'],
            3 => [4 => 'post', 123 => 'post', 321 => 'post'],
            11 => [4 => 'post'],
            6 => [7 => 'page', 8 => 'page'],
            7 => [8 => 'page']
        ];

        $result = self::callMethod($objectMapHandler, 'processTreeMapElements', [&$map, null, &$processed]);
        self::assertEquals($expected, $result);
    }

    /**
     * @param int    $objectId
     * @param int    $termId
     * @param string $type
     *
     * @return \stdClass
     */
    private function createTermMapDbResultElement($objectId, $termId, $type)
    {
        $element = new \stdClass();
        $element->objectId = $objectId;
        $element->parentId = $termId;
        $element->type = $type;

        return $element;
    }

    /**
     * @group  unit
     * @covers ::getCachedMap()
     * @covers ::getTermPostMap()
     */
    public function testGetTermPostMap()
    {
        $databaseResult = [];
        $databaseResult[] = $this->createTermMapDbResultElement(1, 1, 'post');
        $databaseResult[] = $this->createTermMapDbResultElement(2, 1, 'post');
        $databaseResult[] = $this->createTermMapDbResultElement(1, 2, 'post');
        $databaseResult[] = $this->createTermMapDbResultElement(3, 2, 'post');
        $databaseResult[] = $this->createTermMapDbResultElement(4, 2, 'post');
        $databaseResult[] = $this->createTermMapDbResultElement(123, 3, 'post');
        $databaseResult[] = $this->createTermMapDbResultElement(321, 3, 'post');
        $databaseResult[] = $this->createTermMapDbResultElement(7, 6, 'page');
        $databaseResult[] = $this->createTermMapDbResultElement(8, 7, 'page');

        $expectedResult = [
            1 => [1 => 'post', 2 => 'post'],
            2 => [1 => 'post', 3 => 'post', 4 => 'post'],
            3 => [123 => 'post', 321 => 'post'],
            6 => [7 => 'page'],
            7 => [8 => 'page']
        ];

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->exactly(2))
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $database->expects($this->exactly(2))
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $database->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS parentId, p.post_type AS type
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN postTable AS p ON (tr.object_id = p.ID)
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($databaseResult));

        $cache = $this->getCache();

        $cache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [ObjectMapHandler::TERM_POST_MAP_CACHE_KEY],
                [ObjectMapHandler::TERM_POST_MAP_CACHE_KEY]
            )
            ->will($this->onConsecutiveCalls(null, ['cachedTermPostMap']));

        $cache->expects($this->once())
            ->method('add')
            ->with(ObjectMapHandler::TERM_POST_MAP_CACHE_KEY, $expectedResult);

        $objectMapHandler = new ObjectMapHandler(
            $database,
            $cache
        );

        self::assertEquals($expectedResult, $objectMapHandler->getTermPostMap());
        self::assertEquals($expectedResult, $objectMapHandler->getTermPostMap());

        self::setValue($objectMapHandler, 'termPostMap', null);
        self::assertEquals(['cachedTermPostMap'], $objectMapHandler->getTermPostMap());
    }

    /**
     * @group  unit
     * @covers ::getCachedMap()
     * @covers ::getPostTermMap()
     */
    public function testGetPostTermMap()
    {
        $databaseResult = [];
        $databaseResult[] = $this->createTermMapDbResultElement(1, 1, 'category');
        $databaseResult[] = $this->createTermMapDbResultElement(1, 2, 'category');
        $databaseResult[] = $this->createTermMapDbResultElement(2, 1, 'category');
        $databaseResult[] = $this->createTermMapDbResultElement(2, 3, 'category');
        $databaseResult[] = $this->createTermMapDbResultElement(2, 4, 'category');
        $databaseResult[] = $this->createTermMapDbResultElement(3, 123, 'category');
        $databaseResult[] = $this->createTermMapDbResultElement(3, 321, 'category');
        $databaseResult[] = $this->createTermMapDbResultElement(6, 7, 'term');
        $databaseResult[] = $this->createTermMapDbResultElement(7, 8, 'term');

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

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $database->expects($this->exactly(2))
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $database->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS parentId, tt.term_id AS objectId, tt.taxonomy AS type
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($databaseResult));

        $cache = $this->getCache();

        $cache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [ObjectMapHandler::POST_TERM_MAP_CACHE_KEY],
                [ObjectMapHandler::POST_TERM_MAP_CACHE_KEY]
            )
            ->will($this->onConsecutiveCalls(null, ['cachedPostTermMap']));

        $cache->expects($this->once())
            ->method('add')
            ->with(ObjectMapHandler::POST_TERM_MAP_CACHE_KEY, $expectedResult);

        $objectMapHandler = new ObjectMapHandler(
            $database,
            $cache
        );

        self::assertEquals($expectedResult, $objectMapHandler->getPostTermMap());
        self::assertEquals($expectedResult, $objectMapHandler->getPostTermMap());

        self::setValue($objectMapHandler, 'postTermMap', null);
        self::assertEquals(['cachedPostTermMap'], $objectMapHandler->getPostTermMap());
    }
}
