<?php
/**
 * DatabaseUpdate6Test.php
 *
 * The DatabaseUpdate6Test unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Setup\Update;

use UserAccessManager\Setup\Update\DatabaseUpdate6;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class DatabaseUpdate6Test
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate6
 */
class DatabaseUpdate6Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new DatabaseUpdate6(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(DatabaseUpdate6::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new DatabaseUpdate6(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.6.1', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers ::update()
     */
    public function testUpdate()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(4))
            ->method('getVariable')
            ->with(
                new MatchIgnoreWhitespace(
                    'SHOW COLUMNS FROM userGroupToObjectTable
                    LIKE \'group_type\''
                )
            )
            ->will($this->onConsecutiveCalls('', '', '', 'group_type'));

        $alterTableAddQuery = 'ALTER TABLE userGroupToObjectTable
            ADD group_type VARCHAR(32) NOT NULL AFTER group_id,
            ADD from_date DATETIME NULL DEFAULT NULL,
            ADD to_date DATETIME NULL DEFAULT NULL,
            MODIFY group_id VARCHAR(32) NOT NULL,
            MODIFY object_id VARCHAR(32) NOT NULL,
            MODIFY object_type VARCHAR(32) NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (object_id, object_type, group_id, group_type)';

        $database->expects($this->exactly(4))
            ->method('query')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($alterTableAddQuery)],
                [new MatchIgnoreWhitespace($alterTableAddQuery)],
                [new MatchIgnoreWhitespace($alterTableAddQuery)],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupToObjectTable
                    MODIFY group_type VARCHAR(32) NOT NULL,
                    MODIFY group_id VARCHAR(32) NOT NULL,
                    MODIFY object_id VARCHAR(32) NOT NULL,
                    MODIFY object_type VARCHAR(32) NOT NULL'
                )]
            )
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $database->expects($this->exactly(4))
            ->method('update')
            ->with(
                'userGroupToObjectTable',
                ['group_type' => UserGroup::USER_GROUP_TYPE],
                ['group_type' => '']
            )
            ->will($this->onConsecutiveCalls(false, false, true, true));

        $update = new DatabaseUpdate6($database, $this->getObjectHandler());
        self::assertFalse($update->update());
        self::assertFalse($update->update());
        self::assertTrue($update->update());
        self::assertTrue($update->update());
    }
}
