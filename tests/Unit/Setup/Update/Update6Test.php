<?php
/**
 * Update6Test.php
 *
 * The Update6Test unit test class file.
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

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Setup\Update\Update6;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class Update6Test
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\Update6
 */
class Update6Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new Update6(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(Update6::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new Update6(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.6', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers ::update()
     */
    public function testUpdate()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(3))
            ->method('query')
            ->with(
                new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupToObjectTable
                    ADD group_type VARCHAR(32) NOT NULL AFTER group_id,
                    ADD from_date DATETIME NULL DEFAULT NULL,
                    ADD to_date DATETIME NULL DEFAULT NULL,
                    MODIFY group_id VARCHAR(32) NOT NULL,
                    MODIFY object_id VARCHAR(32) NOT NULL,
                    MODIFY object_type VARCHAR(32) NOT NULL,
                    DROP PRIMARY KEY,
                    ADD PRIMARY KEY (object_id, object_type, group_id, group_type)'
                )
            )
            ->will($this->onConsecutiveCalls(false, true, true));

        $database->expects($this->exactly(3))
            ->method('update')
            ->with(
                'userGroupToObjectTable',
                ['group_type' => UserGroup::USER_GROUP_TYPE],
                ['group_type' => '']
            )
            ->will($this->onConsecutiveCalls(false, false, true));

        $update = new Update6($database, $this->getObjectHandler());
        self::assertFalse($update->update());
        self::assertFalse($update->update());
        self::assertTrue($update->update());
    }
}
