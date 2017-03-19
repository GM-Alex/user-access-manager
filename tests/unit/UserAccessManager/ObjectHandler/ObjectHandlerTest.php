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
            $this->getWrapper(),
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

        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($aReturn));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
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

        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue($aReturn));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
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
        $oUser = $this->createMock('\WP_User');
        $oUser->id = 1;

        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(2))
            ->method('getUserData')
            ->withConsecutive([123], [321])
            ->will($this->onConsecutiveCalls($oUser, false));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
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

        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(3))
            ->method('getPost')
            ->withConsecutive([123], [321], [231])
            ->will($this->onConsecutiveCalls($oPost, null, ['id' => 2]));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

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

        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(4))
            ->method('getTerm')
            ->withConsecutive([123, ''], [321, 'firstTax'], [231, 'secondTax'], [231])
            ->will($this->onConsecutiveCalls($oTerm, null, ['id' => 2], $oError));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

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
     *
     * @return array
     */
    private function getExpectedMapResult($sGeneralType)
    {
        return [
            ObjectHandler::TREE_MAP_CHILDREN => [
                'post' => [
                    0 => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 123 => 123, 321 => 321],
                    1 => [2 => 2, 3 => 3, 4 => 4, 123 => 123, 321 => 321],
                    2 => [3 => 3, 4 => 4, 123 => 123, 321 => 321],
                    3 => [123 => 123, 321 => 321]
                ],
                'page' => [
                    6 => [7 => 7, 8 => 8],
                    7 => [8 => 8]
                ],
                $sGeneralType => [
                    0 => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 123 => 123, 321 => 321],
                    1 => [2 => 2, 3 => 3, 4 => 4, 123 => 123, 321 => 321],
                    2 => [3 => 3, 4 => 4, 123 => 123, 321 => 321],
                    3 => [123 => 123, 321 => 321],
                    6 => [7 => 7, 8 => 8],
                    7 => [8 => 8]
                ]
            ],
            ObjectHandler::TREE_MAP_PARENTS => [
                'post' => [
                    1 => [0 => 0],
                    2 => [0 => 0, 1 => 1],
                    3 => [0 => 0, 1 => 1, 2 => 2],
                    4 => [0 => 0, 1 => 1, 2 => 2],
                    123 => [0 => 0, 1 => 1, 2 => 2, 3 => 3],
                    321 => [0 => 0, 1 => 1, 2 => 2, 3 => 3]
                ],
                'page' => [
                    7 => [6 => 6],
                    8 => [6 => 6, 7 => 7]
                ],
                $sGeneralType => [
                    1 => [0 => 0],
                    2 => [0 => 0, 1 => 1],
                    3 => [0 => 0, 1 => 1, 2 => 2],
                    4 => [0 => 0, 1 => 1, 2 => 2],
                    123 => [0 => 0, 1 => 1, 2 => 2, 3 => 3],
                    321 => [0 => 0, 1 => 1, 2 => 2, 3 => 3],
                    7 => [6 => 6],
                    8 => [6 => 6, 7 => 7]
                ]
            ]
        ];
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
        $aDatabaseResult = [];
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(1);
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(2, 1);
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(3, 2);
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(4, 2);
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(123, 3);
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(321, 3);
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(7, 6, 'page');
        $aDatabaseResult[] = $this->createTreeMapDbResultElement(8, 7, 'page');

        $oWrapper = $this->getWrapper();
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
            )->will($this->returnValue($aDatabaseResult));

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
        $aExpectedPostResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_POST_OBJECT_TYPE);
        $aExpectedTermResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_TERM_OBJECT_TYPE);

        self::assertEquals($aExpectedPostResult, $oObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedPostResult, $oObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedTermResult, $oObjectHandler->getTermTreeMap());
        self::assertEquals($aExpectedTermResult, $oObjectHandler->getTermTreeMap());
    }

    /**
     * @param int    $iObjectId
     * @param int    $iTermId
     * @param string $sType
     *
     * @return \stdClass
     */
    private function createTermMapDbResultElement($iObjectId, $iTermId, $sType = 'post')
    {
        $oElement = new \stdClass();
        $oElement->objectId = $iObjectId;
        $oElement->termId = $iTermId;
        $oElement->postType = $sType;

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

        $oWrapper = $this->getWrapper();
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

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

        $aExpectedResult = [
            1 => [1 => 1, 2 => 2],
            2 => [1 => 1, 3 => 3, 4 => 4],
            3 => [123 => 123, 321 => 321],
            6 => [7 => 7],
            7 => [8 => 8]
        ];

        self::assertEquals($aExpectedResult, $oObjectHandler->getTermPostMap());
        self::assertEquals($aExpectedResult, $oObjectHandler->getTermPostMap());

        return $oObjectHandler;
    }

    /**
     * @group   unit
     * @depends testGetTermPostMap
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPostTermMap()
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testGetPostTermMap(ObjectHandler $oObjectHandler)
    {
        $aExpectedResult = [
            1 => [1 => 1, 2 => 2],
            2 => [1 => 1],
            3 => [2 => 2],
            4 => [2 => 2],
            123 => [3 => 3],
            321 => [3 => 3],
            7 => [6 => 6],
            8 => [7 => 7]
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
        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

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
        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue(['a' => 'a1', 'b' => 'b1']));

        $oWrapper->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

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
        $oWrapper = $this->getWrapper();
        $oDatabase = $this->getDatabase();

        $oFirstPluggableObject = $this->getPluggableObject('firstObjectName', $this->once());
        $oSecondPluggableObject = $this->getPluggableObject('secondObjectName', $this->once());

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
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

        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue($aTaxonomiesReturn));

        $aPostTypesReturn = ['c' => 'c1', 'd' => 'd1'];

        $oWrapper->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($aPostTypesReturn));

        $oDatabase = $this->getDatabase();
        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

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
