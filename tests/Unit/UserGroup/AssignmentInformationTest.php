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

namespace UserAccessManager\Tests\Unit\UserGroup;

use PHPUnit\Framework\TestCase;
use UserAccessManager\UserGroup\AssignmentInformation;

/**
 * Class AssignmentInformationTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\AssignmentInformation
 */
class AssignmentInformationTest extends TestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @return AssignmentInformation
     */
    public function testCanCreateInstance(): AssignmentInformation
    {
        $assignmentInformation = new AssignmentInformation('type');

        self::assertInstanceOf(AssignmentInformation::class, $assignmentInformation);
        self::assertEquals('type', $assignmentInformation->getType());
        self::assertEquals(null, $assignmentInformation->getFromDate());
        self::assertEquals(null, $assignmentInformation->getToDate());

        $assignmentInformation = new AssignmentInformation('type', 'fromDate', 'toDate', ['membership']);

        self::assertInstanceOf(AssignmentInformation::class, $assignmentInformation);
        self::assertEquals('type', $assignmentInformation->getType());
        self::assertEquals('fromDate', $assignmentInformation->getFromDate());
        self::assertEquals('toDate', $assignmentInformation->getToDate());
        self::assertEquals(['membership'], $assignmentInformation->getRecursiveMembership());

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
     * @param AssignmentInformation $assignmentInformation
     */
    public function testSettersAndGetters(AssignmentInformation $assignmentInformation)
    {
        self::assertEquals('type', $assignmentInformation->getType());
        self::assertEquals('fromDate', $assignmentInformation->getFromDate());
        self::assertEquals('toDate', $assignmentInformation->getToDate());
        self::assertEquals(['membership'], $assignmentInformation->getRecursiveMembership());

        $assignmentInformation->setRecursiveMembership(['newMembership']);
        self::assertEquals(['newMembership'], $assignmentInformation->getRecursiveMembership());
    }
}
