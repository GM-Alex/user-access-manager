<?php
/**
 * ColumnTest.php
 *
 * The ColumnTest unit test class file.
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

use PHPUnit\Framework\TestCase;
use UserAccessManager\Setup\Database\Column;

/**
 * Class ColumnTest
 *
 * @package UserAccessManager\Tests\Unit\Setup\Database
 * @coversDefaultClass \UserAccessManager\Setup\Database\Column
 */
class ColumnTest extends TestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $column = new Column(
            'columnName',
            'columnType'
        );

        self::assertInstanceOf(Column::class, $column);
    }

    /**
     * @group  unit
     * @covers ::getName()
     * @covers ::getType()
     * @covers ::getDefault()
     * @covers ::isNull()
     * @covers ::isKey()
     * @covers ::isAutoIncrement()
     */
    public function testGetter()
    {
        $column = new Column(
            'columnName',
            'columnType',
            true,
            'columnDefault',
            true,
            true
        );

        self::assertEquals('columnName', $column->getName());
        self::assertEquals('columnType', $column->getType());
        self::assertTrue($column->isNull());
        self::assertEquals('columnDefault', $column->getDefault());
        self::assertTrue($column->isKey());
        self::assertTrue($column->isAutoIncrement());
    }

    /**
     * @group  unit
     * @covers ::__toString()
     */
    public function testToString()
    {
        $column = new Column(
            'columnName',
            'columnType'
        );

        self::assertEquals('`columnName` columnType NOT NULL', (string)$column);

        $column = new Column(
            'columnName',
            'columnType',
            true,
            null,
            true,
            true
        );

        self::assertEquals('`columnName` columnType NULL DEFAULT NULL AUTO_INCREMENT', (string)$column);

        $column = new Column(
            'columnName',
            'columnType',
            true,
            1
        );

        self::assertEquals('`columnName` columnType NULL DEFAULT 1', (string)$column);

        $column = new Column(
            'columnName',
            'columnType',
            false,
            'default'
        );

        self::assertEquals('`columnName` columnType NOT NULL DEFAULT \'default\'', (string)$column);
    }
}
