<?php
/**
 * TermMembershipHandlerTest.php
 *
 * The TermMembershipHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\ObjectMembership;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\ObjectMembership\TermMembershipHandler;

/**
 * Class TermMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\ObjectMembership
 * @coversDefaultClass \UserAccessManager\ObjectMembership\TermMembershipHandler
 */
class TermObjectMembershipHandlerTest extends ObjectMembershipHandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $termMembershipHandler = new TermMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $this->getWordpress(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(TermMembershipHandler::class, $termMembershipHandler);
    }

    /**
     * @group  unit
     * @covers ::getObjectName()
     */
    public function testGetObjectName()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $taxonomy
         */
        $taxonomy = $this->getMockBuilder('\WP_Taxonomy')->getMock();
        $taxonomy->labels = new \stdClass();
        $taxonomy->labels->name = 'category';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getTaxonomy')
            ->with('termTaxonomy')
            ->will($this->onConsecutiveCalls(false, $taxonomy));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $term
         */
        $term = $this->getMockBuilder('\WP_Term')->getMock();
        $term->name = 'categoryThree';
        $term->taxonomy = 'termTaxonomy';

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(3))
            ->method('getTerm')
            ->withConsecutive([-1], [3], [3])
            ->will($this->onConsecutiveCalls(false, $term, $term));

        $termMembershipHandler = new TermMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $wordpress,
            $objectHandler
        );

        $typeName = 'someType';
        self::assertEquals(-1, $termMembershipHandler->getObjectName(-1, $typeName));
        self::assertEquals('someType', $typeName);

        $typeName = 'someType';
        self::assertEquals('categoryThree', $termMembershipHandler->getObjectName(3, $typeName));
        self::assertEquals('someType', $typeName);

        $typeName = 'someType';
        self::assertEquals('categoryThree', $termMembershipHandler->getObjectName(3, $typeName));
        self::assertEquals('category', $typeName);
    }

    /**
     * @group  unit
     * @covers ::getHandledObjects()
     */
    public function testGetHandledObjects()
    {
        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->once())
            ->method('getTaxonomies')
            ->will($this->returnValue(['category' => 'category']));

        $termMembershipHandler = new TermMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $this->getWordpress(),
            $objectHandler
        );

        self::assertEquals(
            [
                '_term_' => '_term_',
                'category' => 'category'
            ],
            $termMembershipHandler->getHandledObjects()
        );
    }

    /**
     * @group  unit
     * @covers ::isMember()
     * @covers ::getMap()
     */
    public function testIsMember()
    {
        $userGroup = $this->getMembershipUserGroup(
            [
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 2],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 3],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 1],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 4],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE , 5]
            ],
            [4, 5]
        );

        $termMembershipHandler = new TermMembershipHandler(
            $this->getExtendedAssignmentInformationFactory(),
            $this->getWordpress(),
            $this->getObjectHandler()
        );

        // term tests
        $return = $termMembershipHandler->isMember($userGroup, false, 1, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('term'), $assignmentInformation);

        $return = $termMembershipHandler->isMember($userGroup, true, 2, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                'term',
                null,
                null,
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => $this->getAssignmentInformation('term')]]
            ),
            $assignmentInformation
        );

        $return = $termMembershipHandler->isMember($userGroup, true, 3, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('term'), $assignmentInformation);

        $return = $termMembershipHandler->isMember($userGroup, true, 4, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                null,
                null,
                null,
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => $this->getAssignmentInformation('term')]]
            ),
            $assignmentInformation
        );

        $return = $termMembershipHandler->isMember($userGroup, true, 5, $assignmentInformation);
        self::assertFalse($return);
        self::assertEquals(null, $assignmentInformation);
    }

    /**
     * @group  unit
     * @covers ::getFullObjects()
     * @covers ::getMap()
     */
    public function testGetFullObjects()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractUserGroup $userGroup
         */
        $userGroup = $this->createMock(AbstractUserGroup::class);

        $userGroup->expects($this->exactly(3))
            ->method('getAssignedObjects')
            ->withConsecutive(
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                ['category']
            )
            ->will($this->returnCallback(function ($objectType) {
                $elements = [];

                if ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
                    || $objectType === 'term'
                ) {
                    $elements[1] = $this->getAssignmentInformation('term');
                    $elements[2] = $this->getAssignmentInformation('term');
                    $elements[3] = $this->getAssignmentInformation('term');
                }

                if ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
                    || $objectType === 'category'
                ) {
                    $elements[100] = $this->getAssignmentInformation('category');
                }

                return $elements;
            }));

        $test = null;
        $userGroup->expects($this->exactly(3))
            ->method('isObjectMember')
            ->withConsecutive(
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1, &$test],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 2, &$test],
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4, &$test]
            )
            ->will($this->onConsecutiveCalls(true, false, true));


        $termMembershipHandler = new TermMembershipHandler(
            $this->getExtendedAssignmentInformationFactory(),
            $this->getWordpress(),
            $this->getObjectHandler()
        );

        self::assertEquals(
            [1 => 'term', 2 => 'term', 3 => 'term', 100 => 'category'],
            $termMembershipHandler->getFullObjects($userGroup, false)
        );

        self::assertEquals(
            [1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term', 100 => 'category'],
            $termMembershipHandler->getFullObjects($userGroup, true)
        );

        self::assertEquals(
            [100 => 'category'],
            $termMembershipHandler->getFullObjects($userGroup, true, 'category')
        );
    }
}
