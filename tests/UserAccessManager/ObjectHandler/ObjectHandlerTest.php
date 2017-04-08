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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\ObjectHandler;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;

/**
 * Class ObjectHandlerTest
 *
 * @package UserAccessManager\ObjectHandler
 */
class ObjectHandlerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $oObjectHandler = new ObjectHandler(
            $this->getWordpress(),
            $this->getDatabase()
        );

        self::assertInstanceOf('\UserAccessManager\ObjectHandler\ObjectHandler', $oObjectHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPostTypes()
     *
     * @return ObjectHandler
     */
    public function testGetPostTypes()
    {
        $aReturn = ['a' => 'a1', 'b' => 'b1'];

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($aReturn));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);
        self::assertEquals($aReturn, $oObjectHandler->getPostTypes());
        self::assertEquals($aReturn, $oObjectHandler->getPostTypes());

        return $oObjectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTaxonomies()
     *
     * @return ObjectHandler
     */
    public function testGetTaxonomies()
    {
        $aReturn = ['a' => 'a1', 'b' => 'b1'];

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue($aReturn));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);
        self::assertEquals($aReturn, $oObjectHandler->getTaxonomies());
        self::assertEquals($aReturn, $oObjectHandler->getTaxonomies());

        return $oObjectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getUser()
     */
    public function testGetUser()
    {
        /**
         * @var \stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->id = 1;

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(2))
            ->method('getUserData')
            ->withConsecutive([123], [321])
            ->will($this->onConsecutiveCalls($oUser, false));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);
        self::assertEquals($oUser, $oObjectHandler->getUser(123));
        self::assertEquals($oUser, $oObjectHandler->getUser(123));
        self::assertFalse($oObjectHandler->getUser(321));
        self::assertFalse($oObjectHandler->getUser(321));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPost()
     */
    public function testGetPost()
    {
        /**
         * @var \stdClass $oPost
         */
        $oPost = $this->getMockBuilder('\WP_Post')->getMock();
        $oPost->id = 1;

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(3))
            ->method('getPost')
            ->withConsecutive([123], [321], [231])
            ->will($this->onConsecutiveCalls($oPost, null, ['id' => 2]));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);

        self::assertEquals($oPost, $oObjectHandler->getPost(123));
        self::assertEquals($oPost, $oObjectHandler->getPost(123));
        self::assertFalse($oObjectHandler->getPost(321));
        self::assertFalse($oObjectHandler->getPost(321));
        self::assertEquals(['id' => 2], $oObjectHandler->getPost(231));
        self::assertEquals(['id' => 2], $oObjectHandler->getPost(231));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTerm()
     */
    public function testGetTerm()
    {
        /**
         * @var \stdClass $oTerm
         */
        $oTerm = $this->getMockBuilder('\WP_Term')->getMock();
        $oTerm->id = 1;

        $oError = $this->getMockBuilder('\WP_Error')->getMock();

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(4))
            ->method('getTerm')
            ->withConsecutive([123, ''], [321, 'firstTax'], [231, 'secondTax'], [231])
            ->will($this->onConsecutiveCalls($oTerm, null, ['id' => 2], $oError));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);

        self::assertEquals($oTerm, $oObjectHandler->getTerm(123));
        self::assertEquals($oTerm, $oObjectHandler->getTerm(123));
        self::assertFalse($oObjectHandler->getTerm(321, 'firstTax'));
        self::assertFalse($oObjectHandler->getTerm(321, 'firstTax'));
        self::assertEquals(['id' => 2], $oObjectHandler->getTerm(231, 'secondTax'));
        self::assertEquals(['id' => 2], $oObjectHandler->getTerm(231, 'secondTax'));
        self::assertEquals($oError, $oObjectHandler->getTerm(231));
        self::assertEquals($oError, $oObjectHandler->getTerm(231));
    }

    /**
     * @param int    $iId
     * @param int    $iParentId
     * @param string $sType
     *
     * @return \stdClass
     */
    private function createTreeMapDbResultElement($iId, $iParentId = 0, $sType = 'post')
    {
        $oElement = new \stdClass();
        $oElement->id = $iId;
        $oElement->type = $sType;
        $oElement->parentId = $iParentId;

        return $oElement;
    }

    /**
     * @param string $sGeneralType
     * @param string $sFirstType
     * @param string $sSecondType
     *
     * @return array
     */
    private function getExpectedMapResult($sGeneralType, $sFirstType = 'post', $sSecondType = 'page')
    {
        $aResult = [
            ObjectHandler::TREE_MAP_CHILDREN => [
                $sFirstType => [
                    0 => [
                        1 => $sFirstType, 2 => $sFirstType, 3 => $sFirstType,
                        4 => $sFirstType, 123 => $sFirstType, 321 => $sFirstType
                    ],
                    1 => [2 => $sFirstType, 3 => $sFirstType, 4 => $sFirstType, 123 => $sFirstType, 321 => $sFirstType],
                    2 => [3 => $sFirstType, 4 => $sFirstType, 123 => $sFirstType, 321 => $sFirstType],
                    3 => [123 => $sFirstType, 321 => $sFirstType, 4 => $sFirstType],
                    11 => [4 => $sFirstType]
                ],
                $sSecondType => [
                    6 => [7 => $sSecondType, 8 => $sSecondType],
                    7 => [8 => $sSecondType]
                ]
            ],
            ObjectHandler::TREE_MAP_PARENTS => [
                $sFirstType => [
                    1 => [0 => $sFirstType],
                    2 => [0 => $sFirstType, 1 => $sFirstType],
                    3 => [0 => $sFirstType, 1 => $sFirstType, 2 => $sFirstType],
                    4 => [0 => $sFirstType, 1 => $sFirstType, 2 => $sFirstType, 3 => $sFirstType, 11 => $sFirstType],
                    123 => [0 => $sFirstType, 1 => $sFirstType, 2 => $sFirstType, 3 => $sFirstType],
                    321 => [0 => $sFirstType, 1 => $sFirstType, 2 => $sFirstType, 3 => $sFirstType]
                ],
                $sSecondType => [
                    7 => [6 => $sSecondType],
                    8 => [6 => $sSecondType, 7 => $sSecondType]
                ]
            ]
        ];

        $aResult[ObjectHandler::TREE_MAP_CHILDREN][$sGeneralType] =
            $aResult[ObjectHandler::TREE_MAP_CHILDREN][$sFirstType]
            + $aResult[ObjectHandler::TREE_MAP_CHILDREN][$sSecondType];

        $aResult[ObjectHandler::TREE_MAP_PARENTS][$sGeneralType] =
            $aResult[ObjectHandler::TREE_MAP_PARENTS][$sFirstType]
            + $aResult[ObjectHandler::TREE_MAP_PARENTS][$sSecondType];
        
        return $aResult;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::_processTreeMapElements()
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::_getTreeMap()
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPostTreeMap()
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTermTreeMap()
     */
    public function testTreeMap()
    {
        $aPostResult = [];
        $aPostResult[] = $this->createTreeMapDbResultElement(1);
        $aPostResult[] = $this->createTreeMapDbResultElement(2, 1);
        $aPostResult[] = $this->createTreeMapDbResultElement(3, 2);
        $aPostResult[] = $this->createTreeMapDbResultElement(4, 2);
        $aPostResult[] = $this->createTreeMapDbResultElement(4, 3);
        $aPostResult[] = $this->createTreeMapDbResultElement(4, 11);
        $aPostResult[] = $this->createTreeMapDbResultElement(123, 3);
        $aPostResult[] = $this->createTreeMapDbResultElement(321, 3);
        $aPostResult[] = $this->createTreeMapDbResultElement(7, 6, 'page');
        $aPostResult[] = $this->createTreeMapDbResultElement(8, 7, 'page');

        $aTermResult = [];
        $aTermResult[] = $this->createTreeMapDbResultElement(1, 0, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(2, 1, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(3, 2, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(4, 2, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(4, 3, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(4, 11, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(123, 3, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(321, 3, 'category');
        $aTermResult[] = $this->createTreeMapDbResultElement(7, 6, 'term');
        $aTermResult[] = $this->createTreeMapDbResultElement(8, 7, 'term');

        $oWordpress = $this->getWordpress();
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oDatabase->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $oDatabase->expects($this->exactly(2))
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
            )->will($this->onConsecutiveCalls($aPostResult, $aTermResult));

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);
        $aExpectedPostResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_POST_OBJECT_TYPE);
        $aExpectedTermResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'category', 'term');

        self::assertEquals($aExpectedPostResult, $oObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedPostResult, $oObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedTermResult, $oObjectHandler->getTermTreeMap());
        self::assertEquals($aExpectedTermResult, $oObjectHandler->getTermTreeMap());
    }

    /**
     * @param int    $iObjectId
     * @param int    $iTermId
     * @param string $sPostType
     * @param string $sTermType
     *
     * @return \stdClass
     */
    private function createTermMapDbResultElement($iObjectId, $iTermId, $sPostType = 'post', $sTermType = 'category')
    {
        $oElement = new \stdClass();
        $oElement->objectId = $iObjectId;
        $oElement->termId = $iTermId;
        $oElement->postType = $sPostType;
        $oElement->termType = $sTermType;

        return $oElement;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTermPostMap()
     */
    public function testGetTermPostMap()
    {
        $aDatabaseResult = [];
        $aDatabaseResult[] = $this->createTermMapDbResultElement(1, 1);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(2, 1);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(1, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(3, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(4, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(123, 3);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(321, 3);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(7, 6, 'page');
        $aDatabaseResult[] = $this->createTermMapDbResultElement(8, 7, 'page');

        $oWordpress = $this->getWordpress();
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oDatabase->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $oDatabase->expects($this->once())
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $oDatabase->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS termId, p.post_type AS postType
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN postTable AS p ON (tr.object_id = p.ID)
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($aDatabaseResult));

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);

        $aExpectedResult = [
            1 => [1 => 'post', 2 => 'post'],
            2 => [1 => 'post', 3 => 'post', 4 => 'post'],
            3 => [123 => 'post', 321 => 'post'],
            6 => [7 => 'page'],
            7 => [8 => 'page']
        ];

        self::assertEquals($aExpectedResult, $oObjectHandler->getTermPostMap());
        self::assertEquals($aExpectedResult, $oObjectHandler->getTermPostMap());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPostTermMap()
     */
    public function testGetPostTermMap()
    {
        $aDatabaseResult = [];
        $aDatabaseResult[] = $this->createTermMapDbResultElement(1, 1);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(2, 1);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(1, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(3, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(4, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(123, 3);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(321, 3);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(7, 6, 'page', 'term');
        $aDatabaseResult[] = $this->createTermMapDbResultElement(8, 7, 'page', 'term');

        $oWordpress = $this->getWordpress();
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $oDatabase->expects($this->once())
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $oDatabase->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS termId, tt.taxonomy AS termType
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($aDatabaseResult));

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);

        $aExpectedResult = [
            1 => [1 => 'category', 2 => 'category'],
            2 => [1 => 'category'],
            3 => [2 => 'category'],
            4 => [2 => 'category'],
            123 => [3 => 'category'],
            321 => [3 => 'category'],
            7 => [6 => 'term'],
            8 => [7 => 'term']
        ];

        self::assertEquals($aExpectedResult, $oObjectHandler->getPostTermMap());
    }

    /**
     * @group   unit
     * @depends testGetPostTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::registeredPostType()
     *
     * @param ObjectHandler $oObjectHandler
     *
     * @return ObjectHandler
     */
    public function testRegisteredPostType(ObjectHandler $oObjectHandler)
    {
        /**
         * @var \stdClass|\WP_Post_Type $oArguments
         */
        $oArguments = $this->getMockBuilder('\WP_Post_Type')->getMock();
        $oArguments->public = false;

        $aExpectedResult = [
            'a' => 'a1',
            'b' => 'b1'
        ];

        $oObjectHandler->registeredPostType('postType', $oArguments);
        self::assertAttributeEquals($aExpectedResult, '_aPostTypes', $oObjectHandler);

        $oArguments->public = true;
        $aExpectedResult['postType'] = 'postType';

        $oObjectHandler->registeredPostType('postType', $oArguments);
        self::assertAttributeEquals($aExpectedResult, '_aPostTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aObjectTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aAllObjectTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aValidObjectTypes', $oObjectHandler);

        return $oObjectHandler;
    }

    /**
     * @group   unit
     * @depends testGetTaxonomies
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::registeredTaxonomy()
     *
     * @param ObjectHandler $oObjectHandler
     *
     * @return ObjectHandler
     */
    public function testRegisteredTaxonomy(ObjectHandler $oObjectHandler)
    {
        $aArguments = ['public' => false];
        $aExpectedResult = [
            'a' => 'a1',
            'b' => 'b1'
        ];

        $oObjectHandler->registeredTaxonomy('taxonomy', 'objectType', $aArguments);
        self::assertAttributeEquals($aExpectedResult, '_aTaxonomies', $oObjectHandler);

        $aArguments = ['public' => true];
        $aExpectedResult['taxonomy'] = 'taxonomy';

        $oObjectHandler->registeredTaxonomy('taxonomy', 'objectType', $aArguments);
        self::assertAttributeEquals($aExpectedResult, '_aTaxonomies', $oObjectHandler);
        self::assertAttributeEquals(null, '_aObjectTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aAllObjectTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aValidObjectTypes', $oObjectHandler);

        return $oObjectHandler;
    }

    /**
     * @group   unit
     * @depends testRegisteredPostType
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isPostType()
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testIsPostType(ObjectHandler $oObjectHandler)
    {
        self::assertTrue($oObjectHandler->isPostType('postType'));
        self::assertFalse($oObjectHandler->isPostType('missing'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::isTaxonomy()
     */
    public function testIsTaxonomy()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);

        self::assertTrue($oObjectHandler->isTaxonomy('taxonomyOne'));
        self::assertTrue($oObjectHandler->isTaxonomy('taxonomyTwo'));
        self::assertFalse($oObjectHandler->isTaxonomy('invalid'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getGeneralObjectType()
     */
    public function testGetGeneralObjectType()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue(['a' => 'a1', 'b' => 'b1']));

        $oWordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);

        self::assertEquals(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $oObjectHandler->getGeneralObjectType('a'));
        self::assertEquals(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $oObjectHandler->getGeneralObjectType('b'));
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $oObjectHandler->getGeneralObjectType('taxonomyOne')
        );
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $oObjectHandler->getGeneralObjectType('taxonomyTwo')
        );
        self::assertEquals(
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            $oObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
            $oObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $oObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $oObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_POST_OBJECT_TYPE)
        );
        self::assertNull($oObjectHandler->getGeneralObjectType('invalid'));
    }

    /**
     * @param string                                           $sName
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $oExpectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PluggableObject
     */
    private function getPluggableObject($sName, $oExpectation = null)
    {
        $oExpectation = ($oExpectation === null) ? $this->any() : $oExpectation;

        /**
         * @var PluggableObject|\PHPUnit_Framework_MockObject_MockObject $oPluggableObject
         */
        $oPluggableObject = $this->createMock('UserAccessManager\ObjectHandler\PluggableObject');
        $oPluggableObject->expects($oExpectation)
            ->method('getName')
            ->will($this->returnValue($sName));

        return $oPluggableObject;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::registerPluggableObject()
     */
    public function testRegisterPlObject()
    {
        $oWordpress = $this->getWordpress();
        $oDatabase = $this->getDatabase();

        $oFirstPluggableObject = $this->getPluggableObject('firstObjectName', $this->once());
        $oSecondPluggableObject = $this->getPluggableObject('secondObjectName', $this->once());

        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);
        $oObjectHandler->registerPluggableObject($oFirstPluggableObject);
        $oObjectHandler->registerPluggableObject($oSecondPluggableObject);

        self::assertAttributeEquals([
            'firstObjectName' => $oFirstPluggableObject,
            'secondObjectName' => $oSecondPluggableObject
        ],
            '_aPluggableObjects',
            $oObjectHandler
        );

        return $oObjectHandler;
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObject()
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testGetPluggableObject(ObjectHandler $oObjectHandler)
    {
        self::assertEquals(
            $this->getPluggableObject('firstObjectName'),
            $oObjectHandler->getPluggableObject('firstObjectName')
        );
        self::assertEquals(
            $this->getPluggableObject('secondObjectName'),
            $oObjectHandler->getPluggableObject('secondObjectName')
        );
        self::assertNull($oObjectHandler->getPluggableObject('invalid'));
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isPluggableObject()
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testIsPluggableObject(ObjectHandler $oObjectHandler)
    {
        self::assertTrue($oObjectHandler->isPluggableObject('firstObjectName'));
        self::assertTrue($oObjectHandler->isPluggableObject('secondObjectName'));
        self::assertFalse($oObjectHandler->isPluggableObject('invalid'));
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObjects()
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testGetPluggableObjects(ObjectHandler $oObjectHandler)
    {
        self::assertEquals(
            [
                'firstObjectName' => $this->getPluggableObject('firstObjectName'),
                'secondObjectName' => $this->getPluggableObject('secondObjectName')
            ],
            $oObjectHandler->getPluggableObjects()
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
        $aTaxonomiesReturn = ['a' => 'a1', 'b' => 'b1'];

        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->once())
            ->method('getTaxonomies')
            ->with(['public' => true])
            ->will($this->returnValue($aTaxonomiesReturn));

        $aPostTypesReturn = ['c' => 'c1', 'd' => 'd1'];

        $oWordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($aPostTypesReturn));

        $oDatabase = $this->getDatabase();
        $oObjectHandler = new ObjectHandler($oWordpress, $oDatabase);

        $aExpectation = [
            'a' => 'a1',
            'b' => 'b1',
            'c' => 'c1',
            'd' => 'd1'
        ];

        self::assertEquals($aExpectation, $oObjectHandler->getObjectTypes());
        self::assertEquals($aExpectation, $oObjectHandler->getObjectTypes());

        return $oObjectHandler;
    }

    /**
     * @group   unit
     * @depends testGetObjectTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getAllObjectTypes()
     *
     * @param ObjectHandler $oObjectHandler
     *
     * @return ObjectHandler
     */
    public function testGetAllObjectTypes(ObjectHandler $oObjectHandler)
    {
        $oFirstPluggableObject = $this->getPluggableObject('firstObjectName', $this->once());
        $oSecondPluggableObject = $this->getPluggableObject('secondObjectName', $this->once());
        $oObjectHandler->registerPluggableObject($oFirstPluggableObject);
        $oObjectHandler->registerPluggableObject($oSecondPluggableObject);

        $aExpectation = [
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

        self::assertEquals($aExpectation, $oObjectHandler->getAllObjectTypes());

        return $oObjectHandler;
    }

    /**
     * @group   unit
     * @depends testGetAllObjectTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isValidObjectType()
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testIsValidObjectType(ObjectHandler $oObjectHandler)
    {
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::GENERAL_USER_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::GENERAL_POST_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType('a'));
        self::assertTrue($oObjectHandler->isValidObjectType('b'));
        self::assertTrue($oObjectHandler->isValidObjectType('c'));
        self::assertTrue($oObjectHandler->isValidObjectType('d'));
        self::assertTrue($oObjectHandler->isValidObjectType('firstObjectName'));
        self::assertTrue($oObjectHandler->isValidObjectType('secondObjectName'));
        self::assertFalse($oObjectHandler->isValidObjectType('invalid'));
    }
}
