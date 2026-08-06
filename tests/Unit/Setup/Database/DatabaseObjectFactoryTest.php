<?php

namespace UserAccessManager\Tests\Unit\Setup\Database;

use PHPUnit\Framework\TestCase;
use UserAccessManager\Setup\Database\Column;
use UserAccessManager\Setup\Database\DatabaseObjectFactory;
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Setup\Database\Table;

/**
 * @coversDefaultClass \UserAccessManager\Setup\Database\DatabaseObjectFactory
 */
class DatabaseObjectFactoryTest extends TestCase
{
    /**
     * @group  unit
     * @return DatabaseObjectFactory
     */
    public function testCanCreateInstance(): DatabaseObjectFactory
    {
        $databaseObjectFactory = new DatabaseObjectFactory();

        self::assertInstanceOf(DatabaseObjectFactory::class, $databaseObjectFactory);

        return $databaseObjectFactory;
    }

    /**
     * @group  unit
     * @depends testCanCreateInstance
     * @covers ::createTable()
     * @param DatabaseObjectFactory $databaseObjectFactory
     * @throws MissingColumnsException
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
     * @param DatabaseObjectFactory $databaseObjectFactory
     */
    public function testCreateColumn(DatabaseObjectFactory $databaseObjectFactory)
    {
        $column = $databaseObjectFactory->createColumn('columnName', 'columnType');

        self::assertInstanceOf(Column::class, $column);
        self::assertNull($column->getDefault());
        self::assertFalse($column->isNull());
        self::assertFalse($column->isKey());
        self::assertFalse($column->isAutoIncrement());
    }
}
