<?php
/**
 * DatabaseUpdate2Test.php
 *
 * The DatabaseUpdate2Test unit test class file.
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

use UserAccessManager\Setup\Update\DatabaseUpdate2;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class DatabaseUpdate2Test
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate2
 */
class DatabaseUpdate2Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new DatabaseUpdate2(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(DatabaseUpdate2::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new DatabaseUpdate2(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.2', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers ::update()
     */
    public function testUpdate()
    {
        $database = $this->getDatabase();
        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('query')
            ->with(
                new MatchIgnoreWhitespace(
                    'ALTER TABLE `userGroupToObjectTable`
                    CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
                    CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL'
                )
            )
            ->will($this->returnValue(true));

        $update = new DatabaseUpdate2($database, $this->getObjectHandler());
        self::assertTrue($update->update());
    }
}
