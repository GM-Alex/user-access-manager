<?php
/**
 * AssignmentInformationFactoryTest.php
 *
 * The AssignmentInformationFactoryTest unit test class file.
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
use UserAccessManager\UserGroup\AssignmentInformationFactory;

/**
 * Class AssignmentInformationFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\AssignmentInformationFactory
 */
class AssignmentInformationFactoryTest extends TestCase
{
    /**
     * @group  unit
     * @return AssignmentInformationFactory
     */
    public function testCanCreateInstance(): AssignmentInformationFactory
    {
        $assignmentInformationFactory = new AssignmentInformationFactory();
        self::assertInstanceOf(AssignmentInformationFactory::class, $assignmentInformationFactory);

        return $assignmentInformationFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createAssignmentInformation()
     * @param AssignmentInformationFactory $assignmentInformationFactory
     */
    public function testCreateAssignmentInformation(AssignmentInformationFactory $assignmentInformationFactory)
    {
        self::assertInstanceOf(
            AssignmentInformation::class,
            $assignmentInformationFactory->createAssignmentInformation('type')
        );

        $assignmentInformation = $assignmentInformationFactory->createAssignmentInformation(
            'type',
            'fromDate',
            'toDate',
            ['membership']
        );

        self::assertEquals('type', $assignmentInformation->getType());
        self::assertEquals('fromDate', $assignmentInformation->getFromDate());
        self::assertEquals('toDate', $assignmentInformation->getToDate());
        self::assertEquals(['membership'], $assignmentInformation->getRecursiveMembership());
    }
}
