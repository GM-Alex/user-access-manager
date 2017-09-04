<?php
/**
 * Update2Test.php
 *
 * The Update2Test unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Setup\Update;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Setup\Update\Update2;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class Update2Test
 *
 * @package UserAccessManager\Tests\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\Update2
 */
class Update2Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new Update2(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(Update2::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new Update2(
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
            ->will($this->returnValue(1));

        $update = new Update2($database, $this->getObjectHandler());
        self::assertTrue($update->update());
    }
}
