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
namespace UserAccessManager\ObjectHandler;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class ObjectHandlerTest
 *
 * @package UserAccessManager\ObjectHandler
 */
class ObjectHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectHandler = new ObjectHandler(
            $this->getWordpress(),
            $this->getDatabase()
        );

        self::assertInstanceOf('\UserAccessManager\ObjectHandler\ObjectHandler', $objectHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPostTypes()
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

        $objectHandler = new ObjectHandler($wordpress, $database);
        self::assertEquals($return, $objectHandler->getPostTypes());
        self::assertEquals($return, $objectHandler->getPostTypes());

        return $objectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTaxonomies()
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

        $objectHandler = new ObjectHandler($wordpress, $database);
        self::assertEquals($return, $objectHandler->getTaxonomies());
        self::assertEquals($return, $objectHandler->getTaxonomies());

        return $objectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getUser()
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

        $objectHandler = new ObjectHandler($wordpress, $database);
        self::assertEquals($user, $objectHandler->getUser(123));
        self::assertEquals($user, $objectHandler->getUser(123));
        self::assertFalse($objectHandler->getUser(321));
        self::assertFalse($objectHandler->getUser(321));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPost()
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

        $objectHandler = new ObjectHandler($wordpress, $database);

        self::assertEquals($post, $objectHandler->getPost(123));
        self::assertEquals($post, $objectHandler->getPost(123));
        self::assertFalse($objectHandler->getPost(321));
        self::assertFalse($objectHandler->getPost(321));
        self::assertEquals(['id' => 2], $objectHandler->getPost(231));
        self::assertEquals(['id' => 2], $objectHandler->getPost(231));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTerm()
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

        $objectHandler = new ObjectHandler($wordpress, $database);

        self::assertEquals($term, $objectHandler->getTerm(123));
        self::assertEquals($term, $objectHandler->getTerm(123));
        self::assertFalse($objectHandler->getTerm(321, 'firstTax'));
        self::assertFalse($objectHandler->getTerm(321, 'firstTax'));
        self::assertEquals(['id' => 2], $objectHandler->getTerm(231, 'secondTax'));
        self::assertEquals(['id' => 2], $objectHandler->getTerm(231, 'secondTax'));
        self::assertEquals($error, $objectHandler->getTerm(231));
        self::assertEquals($error, $objectHandler->getTerm(231));
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
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::processTreeMapElements()
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTreeMap()
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPostTreeMap()
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTermTreeMap()
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
                    WHERE post_parent != 0'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT term_id AS id, parent AS parentId, taxonomy AS type
                    FROM termTaxonomyTable
                    WHERE parent != 0'
                )]
            )->will($this->onConsecutiveCalls($postResult, $termResult));

        $objectHandler = new ObjectHandler($wordpress, $database);
        $expectedPostResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_POST_OBJECT_TYPE);
        $expectedTermResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'category', 'term');

        self::assertEquals($expectedPostResult, $objectHandler->getPostTreeMap());
        self::assertEquals($expectedPostResult, $objectHandler->getPostTreeMap());
        self::assertEquals($expectedTermResult, $objectHandler->getTermTreeMap());
        self::assertEquals($expectedTermResult, $objectHandler->getTermTreeMap());
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
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTermPostMap()
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

        $objectHandler = new ObjectHandler($wordpress, $database);

        $expectedResult = [
            1 => [1 => 'post', 2 => 'post'],
            2 => [1 => 'post', 3 => 'post', 4 => 'post'],
            3 => [123 => 'post', 321 => 'post'],
            6 => [7 => 'page'],
            7 => [8 => 'page']
        ];

        self::assertEquals($expectedResult, $objectHandler->getTermPostMap());
        self::assertEquals($expectedResult, $objectHandler->getTermPostMap());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPostTermMap()
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

        $objectHandler = new ObjectHandler($wordpress, $database);

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

        self::assertEquals($expectedResult, $objectHandler->getPostTermMap());
    }

    /**
     * @group   unit
     * @depends testGetPostTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::registeredPostType()
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
        self::assertAttributeEquals(null, 'validObjectTypes', $objectHandler);

        return $objectHandler;
    }

    /**
     * @group   unit
     * @depends testGetTaxonomies
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::registeredTaxonomy()
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
        self::assertAttributeEquals(null, 'validObjectTypes', $objectHandler);

        return $objectHandler;
    }

    /**
     * @group   unit
     * @depends testRegisteredPostType
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isPostType()
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
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::isTaxonomy()
     */
    public function testIsTaxonomy()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $database = $this->getDatabase();

        $objectHandler = new ObjectHandler($wordpress, $database);

        self::assertTrue($objectHandler->isTaxonomy('taxonomyOne'));
        self::assertTrue($objectHandler->isTaxonomy('taxonomyTwo'));
        self::assertFalse($objectHandler->isTaxonomy('invalid'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getGeneralObjectType()
     */
    public function testGetGeneralObjectType()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue(['a' => 'a1', 'b' => 'b1']));

        $wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $database = $this->getDatabase();

        $objectHandler = new ObjectHandler($wordpress, $database);

        self::assertEquals(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $objectHandler->getGeneralObjectType('a'));
        self::assertEquals(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $objectHandler->getGeneralObjectType('b'));
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $objectHandler->getGeneralObjectType('taxonomyOne')
        );
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $objectHandler->getGeneralObjectType('taxonomyTwo')
        );
        self::assertEquals(
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            $objectHandler->getGeneralObjectType(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
            $objectHandler->getGeneralObjectType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $objectHandler->getGeneralObjectType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $objectHandler->getGeneralObjectType(ObjectHandler::GENERAL_POST_OBJECT_TYPE)
        );
        self::assertNull($objectHandler->getGeneralObjectType('invalid'));
    }

    /**
     * @param string                                           $name
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $expectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PluggableObject
     */
    private function getPluggableObject($name, $expectation = null)
    {
        $expectation = ($expectation === null) ? $this->any() : $expectation;

        /**
         * @var PluggableObject|\PHPUnit_Framework_MockObject_MockObject $pluggableObject
         */
        $pluggableObject = $this->createMock('UserAccessManager\ObjectHandler\PluggableObject');
        $pluggableObject->expects($expectation)
            ->method('getObjectType')
            ->will($this->returnValue($name));

        return $pluggableObject;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::registerPluggableObject()
     */
    public function testRegisterPlObject()
    {
        $wordpress = $this->getWordpress();
        $database = $this->getDatabase();

        $firstPluggableObject = $this->getPluggableObject('firstObjectName', $this->once());
        $secondPluggableObject = $this->getPluggableObject('secondObjectName', $this->once());

        $objectHandler = new ObjectHandler($wordpress, $database);
        $objectHandler->registerPluggableObject($firstPluggableObject);
        $objectHandler->registerPluggableObject($secondPluggableObject);

        self::assertAttributeEquals(
            [
                'firstObjectName' => $firstPluggableObject,
                'secondObjectName' => $secondPluggableObject
            ],
            'pluggableObjects',
            $objectHandler
        );

        return $objectHandler;
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObject()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testGetPluggableObject(ObjectHandler $objectHandler)
    {
        self::assertEquals(
            $this->getPluggableObject('firstObjectName'),
            $objectHandler->getPluggableObject('firstObjectName')
        );
        self::assertEquals(
            $this->getPluggableObject('secondObjectName'),
            $objectHandler->getPluggableObject('secondObjectName')
        );
        self::assertNull($objectHandler->getPluggableObject('invalid'));
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isPluggableObject()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testIsPluggableObject(ObjectHandler $objectHandler)
    {
        self::assertTrue($objectHandler->isPluggableObject('firstObjectName'));
        self::assertTrue($objectHandler->isPluggableObject('secondObjectName'));
        self::assertFalse($objectHandler->isPluggableObject('invalid'));
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObjects()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testGetPluggableObjects(ObjectHandler $objectHandler)
    {
        self::assertEquals(
            [
                'firstObjectName' => $this->getPluggableObject('firstObjectName'),
                'secondObjectName' => $this->getPluggableObject('secondObjectName')
            ],
            $objectHandler->getPluggableObjects()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getObjectTypes()
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
        $objectHandler = new ObjectHandler($wordpress, $database);

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
     * @depends testGetObjectTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getAllObjectTypes()
     *
     * @param ObjectHandler $objectHandler
     *
     * @return ObjectHandler
     */
    public function testGetAllObjectTypes(ObjectHandler $objectHandler)
    {
        $firstPluggableObject = $this->getPluggableObject('firstObjectName', $this->once());
        $secondPluggableObject = $this->getPluggableObject('secondObjectName', $this->once());
        $objectHandler->registerPluggableObject($firstPluggableObject);
        $objectHandler->registerPluggableObject($secondPluggableObject);

        $expectation = [
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            ObjectHandler::GENERAL_POST_OBJECT_TYPE => ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            'a' => 'a1',
            'b' => 'b1',
            'c' => 'c1',
            'd' => 'd1',
            'firstObjectName' => 'firstObjectName',
            'secondObjectName' => 'secondObjectName'
        ];

        self::assertEquals($expectation, $objectHandler->getAllObjectTypes());

        return $objectHandler;
    }

    /**
     * @group   unit
     * @depends testGetAllObjectTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isValidObjectType()
     *
     * @param ObjectHandler $objectHandler
     */
    public function testIsValidObjectType(ObjectHandler $objectHandler)
    {
        self::assertTrue($objectHandler->isValidObjectType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE));
        self::assertTrue($objectHandler->isValidObjectType(ObjectHandler::GENERAL_USER_OBJECT_TYPE));
        self::assertTrue($objectHandler->isValidObjectType(ObjectHandler::GENERAL_POST_OBJECT_TYPE));
        self::assertTrue($objectHandler->isValidObjectType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE));
        self::assertTrue($objectHandler->isValidObjectType('a'));
        self::assertTrue($objectHandler->isValidObjectType('b'));
        self::assertTrue($objectHandler->isValidObjectType('c'));
        self::assertTrue($objectHandler->isValidObjectType('d'));
        self::assertTrue($objectHandler->isValidObjectType('firstObjectName'));
        self::assertTrue($objectHandler->isValidObjectType('secondObjectName'));
        self::assertFalse($objectHandler->isValidObjectType('invalid'));
    }
}
