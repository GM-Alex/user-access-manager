<?php
/**
 * DatabaseUpdate3Test.php
 *
 * The DatabaseUpdate3Test unit test class file.
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

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Setup\Update\DatabaseUpdate3;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class DatabaseUpdate3Test
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate3
 */
class DatabaseUpdate3Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new DatabaseUpdate3(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(DatabaseUpdate3::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new DatabaseUpdate3(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.3', $update->getVersion());
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
            ->method('update')
            ->with(
                'userGroupToObjectTable',
                ['object_type' => ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                ['object_type' => 'category']
            )
            ->will($this->returnValue(true));

        $update = new DatabaseUpdate3($database, $this->getObjectHandler());
        self::assertTrue($update->update());
    }
}
