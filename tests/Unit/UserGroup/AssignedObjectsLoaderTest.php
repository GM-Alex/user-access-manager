<?php
/**
 * AssignedObjectsLoaderTest.php
 *
 * The AssignedObjectsLoaderTest unit test class file.
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

use stdClass;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AssignedObjectsLoader;

/**
 * Class AssignedObjectsLoaderTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\AssignedObjectsLoader
 */
class AssignedObjectsLoaderTest extends UserAccessManagerTestCase
{
    private const QUERY_WITHOUT_DATES = 'SELECT group_id AS groupId,
              group_type AS groupType,
              object_id AS id,
              object_type AS objectType,
              from_date AS fromDate,
              to_date AS toDate
            FROM userGroupToObjectTable
            WHERE object_id != \'\'
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

    private const QUERY_WITH_DATES = self::QUERY_WITHOUT_DATES . ' AND (from_date IS NULL OR from_date <= \'%s\')
              AND (to_date IS NULL OR to_date >= \'%s\')';

    /**
     * @param string $groupId
     * @param string $groupType
     * @param string $objectId
     * @param string $objectType
     * @return stdClass
     */
    private function getAssignmentResult(
        string $groupId,
        string $groupType,
        string $objectId,
        string $objectType
    ): stdClass {
        $result = new stdClass();
        $result->groupId = $groupId;
        $result->groupType = $groupType;
        $result->id = $objectId;
        $result->objectType = $objectType;
        $result->fromDate = null;
        $result->toDate = null;

        return $result;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $assignedObjectsLoader = new AssignedObjectsLoader(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getExtendedAssignmentInformationFactory()
        );

        self::assertInstanceOf(AssignedObjectsLoader::class, $assignedObjectsLoader);
    }

    /**
     * @group  unit
     * @covers ::getAssignedObjects()
     * @covers ::loadAssignedObjects()
     */
    public function testGetAssignedObjectsLoadsAllUserGroupsWithOneQuery()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('currentTime')
            ->with('mysql')
            ->will($this->returnValue('time'));

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('prepare')
            ->with(
                new MatchIgnoreWhitespace(self::QUERY_WITH_DATES),
                ['objectType', 'objectType', 'time', 'time']
            )
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->once())
            ->method('getResults')
            ->with('preparedQuery')
            ->will($this->returnValue([
                $this->getAssignmentResult('1', 'UserGroup', '10', 'objectType'),
                $this->getAssignmentResult('1', 'UserGroup', '11', 'objectType'),
                $this->getAssignmentResult('2', 'UserGroup', '12', 'objectType'),
                $this->getAssignmentResult('1', 'role', '13', 'objectType')
            ]));

        $assignedObjectsLoader = new AssignedObjectsLoader(
            $wordpress,
            $database,
            $this->getExtendedAssignmentInformationFactory()
        );

        self::assertEquals(
            [
                10 => $this->getAssignmentInformation('objectType'),
                11 => $this->getAssignmentInformation('objectType')
            ],
            $assignedObjectsLoader->getAssignedObjects('UserGroup', 1, 'objectType', false)
        );

        // The remaining user groups are answered from the already loaded result.
        self::assertEquals(
            [12 => $this->getAssignmentInformation('objectType')],
            $assignedObjectsLoader->getAssignedObjects('UserGroup', 2, 'objectType', false)
        );

        self::assertEquals(
            [13 => $this->getAssignmentInformation('objectType')],
            $assignedObjectsLoader->getAssignedObjects('role', 1, 'objectType', false)
        );

        // A user group without assignments must not trigger a second query.
        self::assertEquals([], $assignedObjectsLoader->getAssignedObjects('UserGroup', 3, 'objectType', false));
    }

    /**
     * @group  unit
     * @covers ::getAssignedObjects()
     * @covers ::loadAssignedObjects()
     */
    public function testGetAssignedObjectsSeparatesObjectTypesAndIgnoredDates()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('currentTime')
            ->with('mysql')
            ->will($this->returnValue('time'));

        $database = $this->getDatabase();

        $database->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(3))
            ->method('prepare')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(self::QUERY_WITH_DATES), ['objectType', 'objectType', 'time', 'time']],
                [new MatchIgnoreWhitespace(self::QUERY_WITHOUT_DATES), ['objectType', 'objectType']],
                [new MatchIgnoreWhitespace(self::QUERY_WITHOUT_DATES), ['otherObjectType', 'otherObjectType']]
            )
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->exactly(3))
            ->method('getResults')
            ->with('preparedQuery')
            ->will($this->returnValue(null));

        $assignedObjectsLoader = new AssignedObjectsLoader(
            $wordpress,
            $database,
            $this->getExtendedAssignmentInformationFactory()
        );

        self::assertEquals([], $assignedObjectsLoader->getAssignedObjects('UserGroup', 1, 'objectType', false));
        self::assertEquals([], $assignedObjectsLoader->getAssignedObjects('UserGroup', 1, 'objectType', true));
        self::assertEquals([], $assignedObjectsLoader->getAssignedObjects('UserGroup', 1, 'otherObjectType', true));
        self::assertEquals([], $assignedObjectsLoader->getAssignedObjects('UserGroup', 1, 'otherObjectType', true));
    }

    /**
     * @group  unit
     * @covers ::flush()
     */
    public function testFlushForcesAReload()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('prepare')
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->exactly(2))
            ->method('getResults')
            ->with('preparedQuery')
            ->will($this->returnValue(null));

        $assignedObjectsLoader = new AssignedObjectsLoader(
            $this->getWordpress(),
            $database,
            $this->getExtendedAssignmentInformationFactory()
        );

        self::assertEquals([], $assignedObjectsLoader->getAssignedObjects('UserGroup', 1, 'objectType', true));
        $assignedObjectsLoader->flush();
        self::assertEquals([], $assignedObjectsLoader->getAssignedObjects('UserGroup', 1, 'objectType', true));
    }
}
