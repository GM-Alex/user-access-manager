<?php
/**
 * DatabaseUpdate8Test.php
 *
 * The DatabaseUpdate8Test unit test class file.
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

use UserAccessManager\Setup\Update\DatabaseUpdate8;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class DatabaseUpdate8Test
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate8
 */
class DatabaseUpdate8Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new DatabaseUpdate8(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(DatabaseUpdate8::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new DatabaseUpdate8(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.6.3', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Setup\Update\DatabaseUpdate7::update()
     */
    public function testUpdateRepeatsTheUpdateOfItsParent()
    {
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->once())
            ->method('query')
            ->with(
                new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable
                    MODIFY ID INT NOT NULL AUTO_INCREMENT'
                )
            )
            ->will($this->returnValue(5));

        $update = new DatabaseUpdate8($database, $this->getObjectHandler());

        self::assertTrue($update->update());
    }
}
