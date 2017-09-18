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
namespace UserAccessManager\Tests\Unit\Object;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\ObjectMembership\PostMembershipHandler;
use UserAccessManager\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\TermMembershipHandler;
use UserAccessManager\ObjectMembership\UserMembershipHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class ObjectHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\Object
 * @coversDefaultClass \UserAccessManager\Object\ObjectHandler
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

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
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

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
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

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
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

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
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

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
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
        self::assertAttributeEquals(null, 'allObjectTypesMap', $objectHandler);
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
        self::assertAttributeEquals(null, 'allObjectTypesMap', $objectHandler);
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

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
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

        $objectHandler = new ObjectHandler(
            $this->getPhp(),
            $wordpress,
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
        $php->expects($this->exactly(9))
            ->method('arrayFill')
            ->withConsecutive(
                [0, 2, 'role'],
                [0, 2, 'term'],
                [0, 2, 'post'],
                [0, 2, 'someObject'],
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
        $emptyUserMembershipHandler = $this->getMembershipHandler(UserMembershipHandler::class, 'user', [2], []);

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

        $membershipHandlerFactory->expects($this->exactly(2))
            ->method('createUserMembershipHandler')
            ->will($this->onConsecutiveCalls($emptyUserMembershipHandler, $userMembershipHandler));

        $objectHandler = new ObjectHandler(
            $php,
            $wordpress,
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
        $reducedExpectation = $expectation;
        unset($reducedExpectation['user']);
        unset($reducedExpectation['otherUser']);

        self::assertEquals($reducedExpectation, $objectHandler->getAllObjectTypes());

        self::setValue($objectHandler, 'allObjectTypes', null);
        self::setValue($objectHandler, 'allObjectTypesMap', null);
        self::setValue($objectHandler, 'objectMembershipHandlers', null);
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
