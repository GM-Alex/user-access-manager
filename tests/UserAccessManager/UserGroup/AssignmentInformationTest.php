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
 */
class AssignmentInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AssignmentInformation::__construct()
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

        $assignmentInformation = new AssignmentInformation('type', 'fromDate', 'toDate');

        self::assertInstanceOf(AssignmentInformation::class, $assignmentInformation);
        self::assertAttributeEquals('type', 'type', $assignmentInformation);
        self::assertAttributeEquals('fromDate', 'fromDate', $assignmentInformation);
        self::assertAttributeEquals('toDate', 'toDate', $assignmentInformation);

        return $assignmentInformation;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\UserGroup\AssignmentInformation::getType()
     * @covers  \UserAccessManager\UserGroup\AssignmentInformation::getFromDate()
     * @covers  \UserAccessManager\UserGroup\AssignmentInformation::getToDate()
     *
     * @param AssignmentInformation $assignmentInformation
     */
    public function testGetters(AssignmentInformation $assignmentInformation)
    {
        self::assertEquals('type', $assignmentInformation->getType());
        self::assertEquals('fromDate', $assignmentInformation->getFromDate());
        self::assertEquals('toDate', $assignmentInformation->getToDate());
    }
}
