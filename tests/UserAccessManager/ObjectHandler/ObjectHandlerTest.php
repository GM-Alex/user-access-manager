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
        $ObjectHandler = new ObjectHandler(
            $this->getWordpress(),
            $this->getDatabase()
        );

        self::assertInstanceOf('\UserAccessManager\ObjectHandler\ObjectHandler', $ObjectHandler);
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

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($aReturn));

        $Database = $this->getDatabase();

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);
        self::assertEquals($aReturn, $ObjectHandler->getPostTypes());
        self::assertEquals($aReturn, $ObjectHandler->getPostTypes());

        return $ObjectHandler;
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

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue($aReturn));

        $Database = $this->getDatabase();

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);
        self::assertEquals($aReturn, $ObjectHandler->getTaxonomies());
        self::assertEquals($aReturn, $ObjectHandler->getTaxonomies());

        return $ObjectHandler;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getUser()
     */
    public function testGetUser()
    {
        /**
         * @var \stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->id = 1;

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(2))
            ->method('getUserData')
            ->withConsecutive([123], [321])
            ->will($this->onConsecutiveCalls($User, false));

        $Database = $this->getDatabase();

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);
        self::assertEquals($User, $ObjectHandler->getUser(123));
        self::assertEquals($User, $ObjectHandler->getUser(123));
        self::assertFalse($ObjectHandler->getUser(321));
        self::assertFalse($ObjectHandler->getUser(321));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getPost()
     */
    public function testGetPost()
    {
        /**
         * @var \stdClass $Post
         */
        $Post = $this->getMockBuilder('\WP_Post')->getMock();
        $Post->id = 1;

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(3))
            ->method('getPost')
            ->withConsecutive([123], [321], [231])
            ->will($this->onConsecutiveCalls($Post, null, ['id' => 2]));

        $Database = $this->getDatabase();

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);

        self::assertEquals($Post, $ObjectHandler->getPost(123));
        self::assertEquals($Post, $ObjectHandler->getPost(123));
        self::assertFalse($ObjectHandler->getPost(321));
        self::assertFalse($ObjectHandler->getPost(321));
        self::assertEquals(['id' => 2], $ObjectHandler->getPost(231));
        self::assertEquals(['id' => 2], $ObjectHandler->getPost(231));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTerm()
     */
    public function testGetTerm()
    {
        /**
         * @var \stdClass $Term
         */
        $Term = $this->getMockBuilder('\WP_Term')->getMock();
        $Term->id = 1;

        $Error = $this->getMockBuilder('\WP_Error')->getMock();

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(4))
            ->method('getTerm')
            ->withConsecutive([123, ''], [321, 'firstTax'], [231, 'secondTax'], [231])
            ->will($this->onConsecutiveCalls($Term, null, ['id' => 2], $Error));

        $Database = $this->getDatabase();

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);

        self::assertEquals($Term, $ObjectHandler->getTerm(123));
        self::assertEquals($Term, $ObjectHandler->getTerm(123));
        self::assertFalse($ObjectHandler->getTerm(321, 'firstTax'));
        self::assertFalse($ObjectHandler->getTerm(321, 'firstTax'));
        self::assertEquals(['id' => 2], $ObjectHandler->getTerm(231, 'secondTax'));
        self::assertEquals(['id' => 2], $ObjectHandler->getTerm(231, 'secondTax'));
        self::assertEquals($Error, $ObjectHandler->getTerm(231));
        self::assertEquals($Error, $ObjectHandler->getTerm(231));
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
        $Element = new \stdClass();
        $Element->id = $iId;
        $Element->type = $sType;
        $Element->parentId = $iParentId;

        return $Element;
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
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::processTreeMapElements()
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getTreeMap()
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

        $Wordpress = $this->getWordpress();
        $Database = $this->getDatabase();

        $Database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $Database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $Database->expects($this->exactly(2))
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

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);
        $aExpectedPostResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_POST_OBJECT_TYPE);
        $aExpectedTermResult = $this->getExpectedMapResult(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 'category', 'term');

        self::assertEquals($aExpectedPostResult, $ObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedPostResult, $ObjectHandler->getPostTreeMap());
        self::assertEquals($aExpectedTermResult, $ObjectHandler->getTermTreeMap());
        self::assertEquals($aExpectedTermResult, $ObjectHandler->getTermTreeMap());
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
        $Element = new \stdClass();
        $Element->objectId = $iObjectId;
        $Element->termId = $iTermId;
        $Element->postType = $sPostType;
        $Element->termType = $sTermType;

        return $Element;
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

        $Wordpress = $this->getWordpress();
        $Database = $this->getDatabase();

        $Database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $Database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $Database->expects($this->once())
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $Database->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS termId, p.post_type AS postType
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN postTable AS p ON (tr.object_id = p.ID)
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($aDatabaseResult));

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);

        $aExpectedResult = [
            1 => [1 => 'post', 2 => 'post'],
            2 => [1 => 'post', 3 => 'post', 4 => 'post'],
            3 => [123 => 'post', 321 => 'post'],
            6 => [7 => 'page'],
            7 => [8 => 'page']
        ];

        self::assertEquals($aExpectedResult, $ObjectHandler->getTermPostMap());
        self::assertEquals($aExpectedResult, $ObjectHandler->getTermPostMap());
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

        $Wordpress = $this->getWordpress();
        $Database = $this->getDatabase();

        $Database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $Database->expects($this->once())
            ->method('getTermRelationshipsTable')
            ->will($this->returnValue('termRelationshipsTable'));

        $Database->expects($this->once())
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT tr.object_id AS objectId, tt.term_id AS termId, tt.taxonomy AS termType
                    FROM termRelationshipsTable AS tr 
                    LEFT JOIN termTaxonomyTable AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)'
                )
            )->will($this->returnValue($aDatabaseResult));

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);

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

        self::assertEquals($aExpectedResult, $ObjectHandler->getPostTermMap());
    }

    /**
     * @group   unit
     * @depends testGetPostTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::registeredPostType()
     *
     * @param ObjectHandler $ObjectHandler
     *
     * @return ObjectHandler
     */
    public function testRegisteredPostType(ObjectHandler $ObjectHandler)
    {
        /**
         * @var \stdClass|\WP_Post_Type $Arguments
         */
        $Arguments = $this->getMockBuilder('\WP_Post_Type')->getMock();
        $Arguments->public = false;

        $aExpectedResult = [
            'a' => 'a1',
            'b' => 'b1'
        ];

        $ObjectHandler->registeredPostType('postType', $Arguments);
        self::assertAttributeEquals($aExpectedResult, 'aPostTypes', $ObjectHandler);

        $Arguments->public = true;
        $aExpectedResult['postType'] = 'postType';

        $ObjectHandler->registeredPostType('postType', $Arguments);
        self::assertAttributeEquals($aExpectedResult, 'aPostTypes', $ObjectHandler);
        self::assertAttributeEquals(null, 'aObjectTypes', $ObjectHandler);
        self::assertAttributeEquals(null, 'aAllObjectTypes', $ObjectHandler);
        self::assertAttributeEquals(null, 'aValidObjectTypes', $ObjectHandler);

        return $ObjectHandler;
    }

    /**
     * @group   unit
     * @depends testGetTaxonomies
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::registeredTaxonomy()
     *
     * @param ObjectHandler $ObjectHandler
     *
     * @return ObjectHandler
     */
    public function testRegisteredTaxonomy(ObjectHandler $ObjectHandler)
    {
        $aArguments = ['public' => false];
        $aExpectedResult = [
            'a' => 'a1',
            'b' => 'b1'
        ];

        $ObjectHandler->registeredTaxonomy('taxonomy', 'objectType', $aArguments);
        self::assertAttributeEquals($aExpectedResult, 'aTaxonomies', $ObjectHandler);

        $aArguments = ['public' => true];
        $aExpectedResult['taxonomy'] = 'taxonomy';

        $ObjectHandler->registeredTaxonomy('taxonomy', 'objectType', $aArguments);
        self::assertAttributeEquals($aExpectedResult, 'aTaxonomies', $ObjectHandler);
        self::assertAttributeEquals(null, 'aObjectTypes', $ObjectHandler);
        self::assertAttributeEquals(null, 'aAllObjectTypes', $ObjectHandler);
        self::assertAttributeEquals(null, 'aValidObjectTypes', $ObjectHandler);

        return $ObjectHandler;
    }

    /**
     * @group   unit
     * @depends testRegisteredPostType
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isPostType()
     *
     * @param ObjectHandler $ObjectHandler
     */
    public function testIsPostType(ObjectHandler $ObjectHandler)
    {
        self::assertTrue($ObjectHandler->isPostType('postType'));
        self::assertFalse($ObjectHandler->isPostType('missing'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::isTaxonomy()
     */
    public function testIsTaxonomy()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $Database = $this->getDatabase();

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);

        self::assertTrue($ObjectHandler->isTaxonomy('taxonomyOne'));
        self::assertTrue($ObjectHandler->isTaxonomy('taxonomyTwo'));
        self::assertFalse($ObjectHandler->isTaxonomy('invalid'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::getGeneralObjectType()
     */
    public function testGetGeneralObjectType()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue(['a' => 'a1', 'b' => 'b1']));

        $Wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['taxonomyOne', 'taxonomyTwo']));

        $Database = $this->getDatabase();

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);

        self::assertEquals(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $ObjectHandler->getGeneralObjectType('a'));
        self::assertEquals(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $ObjectHandler->getGeneralObjectType('b'));
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $ObjectHandler->getGeneralObjectType('taxonomyOne')
        );
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $ObjectHandler->getGeneralObjectType('taxonomyTwo')
        );
        self::assertEquals(
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            $ObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
            $ObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $ObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $ObjectHandler->getGeneralObjectType(ObjectHandler::GENERAL_POST_OBJECT_TYPE)
        );
        self::assertNull($ObjectHandler->getGeneralObjectType('invalid'));
    }

    /**
     * @param string                                           $sName
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $Expectation
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PluggableObject
     */
    private function getPluggableObject($sName, $Expectation = null)
    {
        $Expectation = ($Expectation === null) ? $this->any() : $Expectation;

        /**
         * @var PluggableObject|\PHPUnit_Framework_MockObject_MockObject $PluggableObject
         */
        $PluggableObject = $this->createMock('UserAccessManager\ObjectHandler\PluggableObject');
        $PluggableObject->expects($Expectation)
            ->method('getName')
            ->will($this->returnValue($sName));

        return $PluggableObject;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::registerPluggableObject()
     */
    public function testRegisterPlObject()
    {
        $Wordpress = $this->getWordpress();
        $Database = $this->getDatabase();

        $FirstPluggableObject = $this->getPluggableObject('firstObjectName', $this->once());
        $SecondPluggableObject = $this->getPluggableObject('secondObjectName', $this->once());

        $ObjectHandler = new ObjectHandler($Wordpress, $Database);
        $ObjectHandler->registerPluggableObject($FirstPluggableObject);
        $ObjectHandler->registerPluggableObject($SecondPluggableObject);

        self::assertAttributeEquals(
            [
                'firstObjectName' => $FirstPluggableObject,
                'secondObjectName' => $SecondPluggableObject
            ],
            'aPluggableObjects',
            $ObjectHandler
        );

        return $ObjectHandler;
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObject()
     *
     * @param ObjectHandler $ObjectHandler
     */
    public function testGetPluggableObject(ObjectHandler $ObjectHandler)
    {
        self::assertEquals(
            $this->getPluggableObject('firstObjectName'),
            $ObjectHandler->getPluggableObject('firstObjectName')
        );
        self::assertEquals(
            $this->getPluggableObject('secondObjectName'),
            $ObjectHandler->getPluggableObject('secondObjectName')
        );
        self::assertNull($ObjectHandler->getPluggableObject('invalid'));
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isPluggableObject()
     *
     * @param ObjectHandler $ObjectHandler
     */
    public function testIsPluggableObject(ObjectHandler $ObjectHandler)
    {
        self::assertTrue($ObjectHandler->isPluggableObject('firstObjectName'));
        self::assertTrue($ObjectHandler->isPluggableObject('secondObjectName'));
        self::assertFalse($ObjectHandler->isPluggableObject('invalid'));
    }

    /**
     * @group   unit
     * @depends testRegisterPlObject
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getPluggableObjects()
     *
     * @param ObjectHandler $ObjectHandler
     */
    public function testGetPluggableObjects(ObjectHandler $ObjectHandler)
    {
        self::assertEquals(
            [
                'firstObjectName' => $this->getPluggableObject('firstObjectName'),
                'secondObjectName' => $this->getPluggableObject('secondObjectName')
            ],
            $ObjectHandler->getPluggableObjects()
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

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getTaxonomies')
            ->with(['public' => true])
            ->will($this->returnValue($aTaxonomiesReturn));

        $aPostTypesReturn = ['c' => 'c1', 'd' => 'd1'];

        $Wordpress->expects($this->once())
            ->method('getPostTypes')
            ->with(['public' => true])
            ->will($this->returnValue($aPostTypesReturn));

        $Database = $this->getDatabase();
        $ObjectHandler = new ObjectHandler($Wordpress, $Database);

        $aExpectation = [
            'a' => 'a1',
            'b' => 'b1',
            'c' => 'c1',
            'd' => 'd1'
        ];

        self::assertEquals($aExpectation, $ObjectHandler->getObjectTypes());
        self::assertEquals($aExpectation, $ObjectHandler->getObjectTypes());

        return $ObjectHandler;
    }

    /**
     * @group   unit
     * @depends testGetObjectTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::getAllObjectTypes()
     *
     * @param ObjectHandler $ObjectHandler
     *
     * @return ObjectHandler
     */
    public function testGetAllObjectTypes(ObjectHandler $ObjectHandler)
    {
        $FirstPluggableObject = $this->getPluggableObject('firstObjectName', $this->once());
        $SecondPluggableObject = $this->getPluggableObject('secondObjectName', $this->once());
        $ObjectHandler->registerPluggableObject($FirstPluggableObject);
        $ObjectHandler->registerPluggableObject($SecondPluggableObject);

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

        self::assertEquals($aExpectation, $ObjectHandler->getAllObjectTypes());

        return $ObjectHandler;
    }

    /**
     * @group   unit
     * @depends testGetAllObjectTypes
     * @covers  \UserAccessManager\ObjectHandler\ObjectHandler::isValidObjectType()
     *
     * @param ObjectHandler $ObjectHandler
     */
    public function testIsValidObjectType(ObjectHandler $ObjectHandler)
    {
        self::assertTrue($ObjectHandler->isValidObjectType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE));
        self::assertTrue($ObjectHandler->isValidObjectType(ObjectHandler::GENERAL_USER_OBJECT_TYPE));
        self::assertTrue($ObjectHandler->isValidObjectType(ObjectHandler::GENERAL_POST_OBJECT_TYPE));
        self::assertTrue($ObjectHandler->isValidObjectType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE));
        self::assertTrue($ObjectHandler->isValidObjectType('a'));
        self::assertTrue($ObjectHandler->isValidObjectType('b'));
        self::assertTrue($ObjectHandler->isValidObjectType('c'));
        self::assertTrue($ObjectHandler->isValidObjectType('d'));
        self::assertTrue($ObjectHandler->isValidObjectType('firstObjectName'));
        self::assertTrue($ObjectHandler->isValidObjectType('secondObjectName'));
        self::assertFalse($ObjectHandler->isValidObjectType('invalid'));
    }
}
