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

        $oWrapper->expects($this->exactly(1))
            ->method('getPostTypes')
            ->with(['publicly_queryable' => true])
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
     */
    public function testGetTaxonomies()
    {
        $aReturn = ['a' => 'a1', 'b' => 'b1'];

        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(1))
            ->method('getTaxonomies')
            ->will($this->returnValue($aReturn));

        $oDatabase = $this->getDatabase();

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
        self::assertEquals($aReturn, $oObjectHandler->getTaxonomies());
        self::assertEquals($aReturn, $oObjectHandler->getTaxonomies());
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

        $oDatabase->expects($this->exactly(1))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oDatabase->expects($this->exactly(1))
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
                    'SELECT term_id AS id, parent AS parentId, taxonomy as type
                    FROM termTaxonomyTable
                    WHERE parent != 0'
                )]
            )->will($this->returnValue($aDatabaseResult));

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

        $aExpectedResult = [
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
                ]
            ]
        ];

        self::assertEquals($aExpectedResult, $oObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedResult, $oObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedResult, $oObjectHandler->getTermTreeMap());
        self::assertEquals($aExpectedResult, $oObjectHandler->getTermTreeMap());
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
    public function testTermPostMap()
    {
        $aDatabaseResult = [];
        $aDatabaseResult[] = $this->createTermMapDbResultElement(1, 1);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(2, 1);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(3, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(4, 2);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(123, 3);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(321, 3);
        $aDatabaseResult[] = $this->createTermMapDbResultElement(7, 6, 'page');
        $aDatabaseResult[] = $this->createTermMapDbResultElement(8, 7, 'page');

        $oWrapper = $this->getWrapper();
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(1))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS termId, p.post_type AS postType
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN postTable as p ON (tr.object_id = p.ID)
                    LEFT JOIN termTaxonomyTable as tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($aDatabaseResult));

        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

        $aExpectedResult = [
            1 => [1 => 'post', 2 => 'post'],
            2 => [3 => 'post', 4 => 'post'],
            3 => [123 => 'post', 321 => 'post'],
            6 => [7 => 'page'],
            7 => [8 => 'page']
        ];

        self::assertEquals($aExpectedResult, $oObjectHandler->getTermPostMap());
        self::assertEquals($aExpectedResult, $oObjectHandler->getTermPostMap());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPostableTypes()
     * @depends testGetPostTypes
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testGetPostableTypes(ObjectHandler $oObjectHandler)
    {
        $aExpectedResult = [
            ObjectHandler::POST_OBJECT_TYPE => ObjectHandler::POST_OBJECT_TYPE,
            ObjectHandler::PAGE_OBJECT_TYPE => ObjectHandler::PAGE_OBJECT_TYPE,
            ObjectHandler::ATTACHMENT_OBJECT_TYPE => ObjectHandler::ATTACHMENT_OBJECT_TYPE,
            'a' => 'a1',
            'b' => 'b1'
        ];

        self::assertEquals($aExpectedResult, $oObjectHandler->getPostableTypes());
        self::assertAttributeEquals($aExpectedResult, '_aPostableTypes', $oObjectHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::registeredPostType()
     * @depends testGetPostTypes
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
        $oArguments->publicly_queryable = false;

        $aExpectedResult = [
            ObjectHandler::POST_OBJECT_TYPE => ObjectHandler::POST_OBJECT_TYPE,
            ObjectHandler::PAGE_OBJECT_TYPE => ObjectHandler::PAGE_OBJECT_TYPE,
            ObjectHandler::ATTACHMENT_OBJECT_TYPE => ObjectHandler::ATTACHMENT_OBJECT_TYPE,
            'a' => 'a1',
            'b' => 'b1'
        ];

        $oObjectHandler->registeredPostType('postType', $oArguments);
        self::assertAttributeEquals($aExpectedResult, '_aPostableTypes', $oObjectHandler);

        $oArguments->publicly_queryable = true;
        $aExpectedResult['postType'] = 'postType';

        $oObjectHandler->registeredPostType('postType', $oArguments);
        self::assertAttributeEquals($aExpectedResult, '_aPostableTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aObjectTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aAllObjectTypes', $oObjectHandler);
        self::assertAttributeEquals(null, '_aAllObjectTypesMap', $oObjectHandler);
        self::assertAttributeEquals(null, '_aValidObjectTypes', $oObjectHandler);

        return $oObjectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::isPostableType()
     * @depends testRegisteredPostType
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testIsPostableType(ObjectHandler $oObjectHandler)
    {
        self::assertTrue($oObjectHandler->isPostableType(ObjectHandler::POST_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isPostableType('postType'));
        self::assertFalse($oObjectHandler->isPostableType('missing'));
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

        $oFirstPluggableObject = $this->getPluggableObject('firstObjectName', $this->exactly(1));
        $oSecondPluggableObject = $this->getPluggableObject('secondObjectName', $this->exactly(1));

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
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObject()
     * @depends testRegisterPlObject
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
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObjects()
     * @depends testRegisterPlObject
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

        $oWrapper->expects($this->exactly(1))
            ->method('getTaxonomies')
            ->will($this->returnValue($aTaxonomiesReturn));

        $aPostTypesReturn = ['c' => 'c1', 'd' => 'd1'];

        $oWrapper->expects($this->exactly(1))
            ->method('getPostTypes')
            ->with(['publicly_queryable' => true])
            ->will($this->returnValue($aPostTypesReturn));

        $oDatabase = $this->getDatabase();
        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);

        $aExpectation = [
            ObjectHandler::TERM_OBJECT_TYPE => ObjectHandler::TERM_OBJECT_TYPE,
            ObjectHandler::USER_OBJECT_TYPE => ObjectHandler::USER_OBJECT_TYPE,
            ObjectHandler::ROLE_OBJECT_TYPE => ObjectHandler::ROLE_OBJECT_TYPE,
            ObjectHandler::POST_OBJECT_TYPE => ObjectHandler::POST_OBJECT_TYPE,
            ObjectHandler::PAGE_OBJECT_TYPE => ObjectHandler::PAGE_OBJECT_TYPE,
            ObjectHandler::ATTACHMENT_OBJECT_TYPE => ObjectHandler::ATTACHMENT_OBJECT_TYPE,
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
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getAllObjectTypes()
     * @depends testGetObjectTypes
     *
     * @param ObjectHandler $oObjectHandler
     *
     * @return ObjectHandler
     */
    public function testGetAllObjectTypes(ObjectHandler $oObjectHandler)
    {
        $oFirstPluggableObject = $this->getPluggableObject('firstObjectName', $this->exactly(1));
        $oSecondPluggableObject = $this->getPluggableObject('secondObjectName', $this->exactly(1));
        $oObjectHandler->registerPluggableObject($oFirstPluggableObject);
        $oObjectHandler->registerPluggableObject($oSecondPluggableObject);

        $aExpectation = [
            ObjectHandler::TERM_OBJECT_TYPE => ObjectHandler::TERM_OBJECT_TYPE,
            ObjectHandler::USER_OBJECT_TYPE => ObjectHandler::USER_OBJECT_TYPE,
            ObjectHandler::ROLE_OBJECT_TYPE => ObjectHandler::ROLE_OBJECT_TYPE,
            ObjectHandler::POST_OBJECT_TYPE => ObjectHandler::POST_OBJECT_TYPE,
            ObjectHandler::PAGE_OBJECT_TYPE => ObjectHandler::PAGE_OBJECT_TYPE,
            ObjectHandler::ATTACHMENT_OBJECT_TYPE => ObjectHandler::ATTACHMENT_OBJECT_TYPE,
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
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::isValidObjectType()
     * @depends testGetAllObjectTypes
     *
     * @param ObjectHandler $oObjectHandler
     */
    public function testIsValidObjectType(ObjectHandler $oObjectHandler)
    {
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::TERM_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::USER_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::ROLE_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::POST_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::PAGE_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType(ObjectHandler::ATTACHMENT_OBJECT_TYPE));
        self::assertTrue($oObjectHandler->isValidObjectType('a'));
        self::assertTrue($oObjectHandler->isValidObjectType('b'));
        self::assertTrue($oObjectHandler->isValidObjectType('c'));
        self::assertTrue($oObjectHandler->isValidObjectType('d'));
        self::assertTrue($oObjectHandler->isValidObjectType('firstObjectName'));
        self::assertTrue($oObjectHandler->isValidObjectType('secondObjectName'));
        self::assertFalse($oObjectHandler->isValidObjectType('invalid'));
    }
}
