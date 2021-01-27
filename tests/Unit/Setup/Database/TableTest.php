<?php
/**
 * TableTest.php
 *
 * The TableTest unit test class file.
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
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Setup\Database\Table;

/**
 * Class TableTest
 *
 * @package UserAccessManager\Tests\Unit\Setup\Database
 * @coversDefaultClass \UserAccessManager\Setup\Database\Table
 */
class TableTest extends TestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @throws MissingColumnsException
     */
    public function testCanCreateInstance()
    {
        $table = new Table(
            'columnName',
            'columnType',
            ['columns']
        );

        self::assertInstanceOf(Table::class, $table);

        self::expectException(MissingColumnsException::class);
        new Table(
            'columnName',
            'columnType',
            []
        );
    }

    /**
     * @group  unit
     * @covers ::getName()
     * @covers ::getCharsetCollate()
     * @covers ::getColumns()
     * @throws MissingColumnsException
     */
    public function testGetter()
    {
        $table = new Table(
            'tableName',
            'tableCharsetCollate',
            ['columns']
        );

        self::assertEquals('tableName', $table->getName());
        self::assertEquals('tableCharsetCollate', $table->getCharsetCollate());
        self::assertEquals(['columns'], $table->getColumns());
    }

    /**
     * @group  unit
     * @covers ::__toString()
     * @throws MissingColumnsException
     */
    public function testToString()
    {
        $columnOne = $this->createMock(Column::class);

        $columnOne->expects($this->exactly(2))
            ->method('isKey')
            ->will($this->onConsecutiveCalls(false, true));

        $columnOne->expects($this->exactly(1))
            ->method('getName')
            ->will($this->returnValue('columnOne'));

        $columnOne->expects($this->exactly(2))
            ->method('__toString')
            ->will($this->returnValue('columnOneQuery'));

        $columnTwo = $this->createMock(Column::class);

        $columnTwo->expects($this->exactly(2))
            ->method('isKey')
            ->will($this->returnValue(false));

        $columnTwo->expects($this->exactly(2))
            ->method('__toString')
            ->will($this->returnValue('columnTwoQuery'));

        $table = new Table(
            'tableName',
            'tableCharsetCollate',
            [$columnOne, $columnTwo]
        );

        self::assertEquals(
            'CREATE TABLE `tableName` (
                columnOneQuery, columnTwoQuery
            ) tableCharsetCollate;',
            (string)$table
        );

        self::assertEquals(
            'CREATE TABLE `tableName` (
                columnOneQuery, columnTwoQuery, PRIMARY KEY (`columnOne`)
            ) tableCharsetCollate;',
            (string)$table
        );
    }
}
