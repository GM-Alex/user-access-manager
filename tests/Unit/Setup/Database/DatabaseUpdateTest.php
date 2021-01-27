<?php
/**
 * DatabaseUpdateTest.php
 *
 * The DatabaseUpdateTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Setup\Database;

use PHPUnit\Framework\MockObject\MockObject;
use UserAccessManager\Setup\Database\DatabaseUpdate;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class DatabaseUpdateTest
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Database\DatabaseUpdate
 */
class DatabaseUpdateTest extends UserAccessManagerTestCase
{
    /**
     * @return MockObject|DatabaseUpdate
     */
    private function getStub()
    {
        return $this->getMockForAbstractClass(
            DatabaseUpdate::class,
            [],
            '',
            false,
            true,
            true
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub();
        $stub->__construct($this->getDatabase(), $this->getObjectHandler());
        self::assertInstanceOf(DatabaseUpdate::class, $stub);
    }
}
