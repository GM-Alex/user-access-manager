<?php
/**
 * AssignmentInformationTest.php
 *
 * The AssignmentInformationTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

/**
 * Class AssignmentInformationTest
 *
 * @package UserAccessManager\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\AssignmentInformation
 */
class AssignmentInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     *
     * @return AssignmentInformation
     */
    public function testCanCreateInstance()
    {
        $assignmentInformation = new AssignmentInformation('type');

        self::assertInstanceOf(AssignmentInformation::class, $assignmentInformation);
        self::assertAttributeEquals('type', 'type', $assignmentInformation);
        self::assertAttributeEquals(null, 'fromDate', $assignmentInformation);
        self::assertAttributeEquals(null, 'toDate', $assignmentInformation);

        $assignmentInformation = new AssignmentInformation('type', 'fromDate', 'toDate', ['membership']);

        self::assertInstanceOf(AssignmentInformation::class, $assignmentInformation);
        self::assertAttributeEquals('type', 'type', $assignmentInformation);
        self::assertAttributeEquals('fromDate', 'fromDate', $assignmentInformation);
        self::assertAttributeEquals('toDate', 'toDate', $assignmentInformation);
        self::assertAttributeEquals(['membership'], 'recursiveMembership', $assignmentInformation);

        return $assignmentInformation;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getType()
     * @covers  ::getFromDate()
     * @covers  ::getToDate()
     * @covers  ::getRecursiveMembership()
     * @covers  ::setRecursiveMembership()
     *
     * @param AssignmentInformation $assignmentInformation
     */
    public function testSettersAndGetters(AssignmentInformation $assignmentInformation)
    {
        self::assertEquals('type', $assignmentInformation->getType());
        self::assertEquals('fromDate', $assignmentInformation->getFromDate());
        self::assertEquals('toDate', $assignmentInformation->getToDate());
        self::assertEquals(['membership'], $assignmentInformation->getRecursiveMembership());

        self::assertEquals($assignmentInformation, $assignmentInformation->setRecursiveMembership(['newMembership']));
        self::assertEquals(['newMembership'], $assignmentInformation->getRecursiveMembership());
    }
}
