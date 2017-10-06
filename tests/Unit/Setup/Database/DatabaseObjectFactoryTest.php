<?php
/**
 * DatabaseObjectFactoryTest.php
 *
 * The DatabaseObjectFactoryTest unit test class file.
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

use UserAccessManager\Setup\Database\Column;
use UserAccessManager\Setup\Database\DatabaseObjectFactory;
use UserAccessManager\Setup\Database\Table;

/**
 * Class DatabaseObjectFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Setup\Database
 * @coversDefaultClass \UserAccessManager\Setup\Database\DatabaseObjectFactory
 */
class DatabaseObjectFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     *
     * @return DatabaseObjectFactory
     */
    public function testCanCreateInstance()
    {
        $databaseObjectFactory = new DatabaseObjectFactory();

        self::assertInstanceOf(DatabaseObjectFactory::class, $databaseObjectFactory);

        return $databaseObjectFactory;
    }

    /**
     * @group  unit
     * @depends testCanCreateInstance
     * @covers ::createTable()
     *
     * @param DatabaseObjectFactory $databaseObjectFactory
     */
    public function testCreateTable(DatabaseObjectFactory $databaseObjectFactory)
    {
        self::assertInstanceOf(
            Table::class,
            $databaseObjectFactory->createTable(
                'tableName',
                'tableCharsetCollate',
                ['columns']
            )
        );
    }

    /**
     * @group  unit
     * @depends testCanCreateInstance
     * @covers ::createColumn()
     *
     * @param DatabaseObjectFactory $databaseObjectFactory
     */
    public function testCreateColumn(DatabaseObjectFactory $databaseObjectFactory)
    {
        self::assertInstanceOf(
            Column::class,
            $databaseObjectFactory->createColumn('columnName', 'columnType')
        );
    }
}
