<?php
/**
 * DatabaseHandlerTest.php
 *
 * The DatabaseHandlerTest unit test class file.
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
use stdClass;
use UserAccessManager\Setup\Database\Column;
use UserAccessManager\Setup\Database\DatabaseHandler;
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Setup\Database\Table;
use UserAccessManager\Setup\Update\UpdateInterface;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserAccessManager;

/**
 * Class DatabaseHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\Setup\Database
 * @coversDefaultClass \UserAccessManager\Setup\Database\DatabaseHandler
 */
class DatabaseHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $databaseHandler = new DatabaseHandler(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getDatabaseObjectFactory(),
            $this->getUpdateFactory()
        );

        self::assertInstanceOf(DatabaseHandler::class, $databaseHandler);
    }

    /**
     * @param string $name
     * @param array $columns
     * @return MockObject|Table
     */
    private function getTable(string $name, array $columns = []): Table|MockObject
    {
        $table = $this->createMock(Table::class);
        $table->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $table->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue("CREATE TABLE `{$name}` LIKE `{$name}`"));

        $table->expects($this->any())
            ->method('getColumns')
            ->will($this->returnValue($columns));

        return $table;
    }

    /**
     * @param string $name
     * @param string $type
     * @return MockObject|Table
     */
    private function getColumn(string $name, string $type): Table|MockObject
    {
        $table = $this->createMock(Column::class);
        $table->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $table->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue("`{$name}` {$type}"));

        return $table;
    }

    /**
     * @group  unit
     * @covers ::install()
     * @covers ::getTables()
     * @covers ::addTable()
     * @covers ::tableExists()
     * @throws MissingColumnsException
     */
    public function testInstall()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects(($this->once()))
            ->method('addOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $database = $this->getDatabase();
        $database->expects($this->once())
            ->method('getCharset')
            ->will($this->returnValue('charset'));

        $database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable\'']
            )
            ->will($this->onConsecutiveCalls('', 'userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('dbDelta')
            ->with('CREATE TABLE `userGroupTable` LIKE `userGroupTable`');

        $databaseObjectFactory = $this->getDatabaseObjectFactory();
        $databaseObjectFactory->expects($this->exactly(2))
            ->method('createTable')
            ->withConsecutive(
                ['userGroupTable', 'charset'],
                ['userGroupToObjectTable', 'charset']
            )
            ->will($this->onConsecutiveCalls(
                $this->getTable('userGroupTable'),
                $this->getTable('userGroupToObjectTable')
            ));

        $databaseObjectFactory->expects($this->exactly(13))
            ->method('createColumn')
            ->withConsecutive(
                ['ID', 'INT', false, null, true, true],
                ['groupname', 'TINYTEXT'],
                ['groupdesc', 'TEXT'],
                ['read_access', 'TINYTEXT'],
                ['write_access', 'TINYTEXT'],
                ['ip_range', 'MEDIUMTEXT', true],
                ['object_id', 'VARCHAR(32)', false, null, true],
                ['general_object_type', 'VARCHAR(64)'],
                ['object_type', 'VARCHAR(32)', false, null, true],
                ['group_id', 'VARCHAR(32)', false, null, true],
                ['group_type', 'VARCHAR(32)', false, null, true],
                ['from_date', 'DATETIME', true],
                ['to_date', 'DATETIME', true]
            )
            ->will($this->returnCallback(function () {
                return $this->createMock(Column::class);
            }));

        $databaseHandler = new DatabaseHandler(
            $wordpress,
            $database,
            $databaseObjectFactory,
            $this->getUpdateFactory()
        );

        $databaseHandler->install();
    }

    /**
     * @param int $number
     * @return array
     */
    private function getDatabaseColumns(int $number): array
    {
        $columns = [];

        for ($columnNumber = 1; $columnNumber <= $number; $columnNumber++) {
            $column = new stdClass();
            $column->Field = "field{$columnNumber}";
            $column->Type = "type{$columnNumber}";
            $column->Null = ($columnNumber === 1) ? 'YES' : '';
            $column->Default = "default{$columnNumber}";
            $column->Key = ($columnNumber === 1) ? 'PRI' : '';
            $column->Extra = ($columnNumber === 1) ? 'auto_increment' : '';
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @group  unit
     * @covers ::getCorruptedDatabaseInformation()
     * @covers ::addCorruptedRows()
     * @covers ::getExistingColumns()
     * @throws MissingColumnsException
     */
    public function testGetCorruptedDatabaseInformation()
    {
        $database = $this->getDatabase();
        $database->expects($this->once())
            ->method('getCharset')
            ->will($this->returnValue('charset'));

        $database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable\'']
            )
            ->will($this->onConsecutiveCalls('', 'userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('getResults')
            ->with('SHOW COLUMNS FROM `userGroupToObjectTable`;')
            ->will($this->returnValue($this->getDatabaseColumns(3)));

        $databaseObjectFactory = $this->getDatabaseObjectFactory();

        $missingTable = $this->getTable('userGroupTable');

        $validColumn = $this->getColumn('field1', 'TYPE1');
        $modifiedColumn = $this->getColumn('field2', 'MODIFIED_TYPE');
        $missingColumn = $this->getColumn('additionalColumn', 'SOME_TYPE');
        $existingTable = $this->getTable(
            'userGroupToObjectTable',
            [$validColumn, $modifiedColumn, $missingColumn]
        );

        $databaseObjectFactory->expects($this->exactly(2))
            ->method('createTable')
            ->will($this->onConsecutiveCalls($missingTable, $existingTable));

        $databaseObjectFactory->expects($this->exactly(16))
            ->method('createColumn')
            ->withConsecutive(
                ['ID', 'INT', false, null, true, true],
                ['groupname', 'TINYTEXT'],
                ['groupdesc', 'TEXT'],
                ['read_access', 'TINYTEXT'],
                ['write_access', 'TINYTEXT'],
                ['ip_range', 'MEDIUMTEXT', true],
                ['object_id', 'VARCHAR(32)', false, null, true],
                ['general_object_type', 'VARCHAR(64)'],
                ['object_type', 'VARCHAR(32)', false, null, true],
                ['group_id', 'VARCHAR(32)', false, null, true],
                ['group_type', 'VARCHAR(32)', false, null, true],
                ['from_date', 'DATETIME', true],
                ['to_date', 'DATETIME', true],
                ['field1', 'TYPE1', true, 'default1', true, true],
                ['field2', 'TYPE2', false, 'default2', false, false],
                ['field3', 'TYPE3', false, 'default3', false, false]
            )
            ->will($this->returnCallback(function ($field, $type) {
                return $this->getColumn($field, $type);
            }));

        $databaseHandler = new DatabaseHandler(
            $this->getWordpress(),
            $database,
            $databaseObjectFactory,
            $this->getUpdateFactory()
        );

        $expectedInformation = [
            DatabaseHandler::MISSING_TABLES => [$missingTable],
            DatabaseHandler::MISSING_COLUMNS => [[$existingTable, $missingColumn]],
            DatabaseHandler::MODIFIED_COLUMNS => [[$existingTable, $modifiedColumn]],
            DatabaseHandler::EXTRA_COLUMNS => [[$existingTable, $this->getColumn('field3', 'type3')]]
        ];

        $result = $databaseHandler->getCorruptedDatabaseInformation();
        self::assertEquals($expectedInformation, $result);
        self::assertSame(
            $expectedInformation[DatabaseHandler::MODIFIED_COLUMNS],
            $result[DatabaseHandler::MODIFIED_COLUMNS]
        );
    }

    /**
     * @group  unit
     * @covers ::repairDatabase()
     * @covers ::addTable()
     * @covers ::addColumn()
     * @covers ::modifyColumn()
     * @covers ::dropColumn()
     * @throws MissingColumnsException
     */
    public function testRepairDatabase()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(6))
            ->method('dbDelta')
            ->withConsecutive(
                ['CREATE TABLE `userGroupTable` LIKE `userGroupTable`'],
                ['CREATE TABLE `userGroupTable` LIKE `userGroupTable`'],
                ['CREATE TABLE `userGroupTable` LIKE `userGroupTable`'],
                ['CREATE TABLE `userGroupTable` LIKE `userGroupTable`'],
                ['CREATE TABLE `someTable` LIKE `someTable`'],
                ['CREATE TABLE `someTable` LIKE `someTable`']
            );

        $database->expects($this->exactly(9))
            ->method('query')
            ->withConsecutive(
                ['ALTER TABLE `userGroupToObjectTable` ADD `additionalColumn` VARCHAR(64);'],
                ['ALTER TABLE `userGroupToObjectTable` ADD `additionalColumn` VARCHAR(64);'],
                ['ALTER TABLE `userGroupToObjectTable` MODIFY `field2` TEXT;'],
                ['ALTER TABLE `userGroupToObjectTable` ADD `additionalColumn` VARCHAR(64);'],
                ['ALTER TABLE `userGroupToObjectTable` MODIFY `field2` TEXT;'],
                ['ALTER TABLE `userGroupToObjectTable` DROP `field3`;'],
                ['ALTER TABLE `userGroupToObjectTable` ADD `additionalColumn` VARCHAR(64);'],
                ['ALTER TABLE `userGroupToObjectTable` MODIFY `field2` TEXT;'],
                ['ALTER TABLE `userGroupToObjectTable` DROP `field3`;']
            )->will($this->onConsecutiveCalls(
                false,
                true,
                false,
                true,
                true,
                false,
                true,
                true,
                true
            ));

        $database->expects($this->exactly(2))
            ->method('getVariable')
            ->with('SHOW TABLES LIKE \'someTable\'')
            ->will($this->onConsecutiveCalls('', ''));

        $databaseObjectFactory = $this->getDatabaseObjectFactory();
        $databaseObjectFactory->expects($this->any())
            ->method('createTable')
            ->will($this->returnValue($this->getTable('someTable')));

        $databaseObjectFactory->expects($this->any())
            ->method('createColumn')
            ->will($this->returnCallback(function ($field, $type) {
                return $this->getColumn($field, $type);
            }));

        $databaseHandler = new DatabaseHandler(
            $this->getWordpress(),
            $database,
            $databaseObjectFactory,
            $this->getUpdateFactory()
        );

        $missingTable = $this->getTable('userGroupTable');
        $validColumn = $this->getColumn('field1', 'TYPE1');
        $modifiedColumn = $this->getColumn('field2', 'TEXT');
        $missingColumn = $this->getColumn('additionalColumn', 'VARCHAR(64)');
        $existingTable = $this->getTable(
            'userGroupToObjectTable',
            [$validColumn, $modifiedColumn, $missingColumn]
        );

        $information = [
            DatabaseHandler::MISSING_TABLES => [$missingTable],
            DatabaseHandler::MISSING_COLUMNS => [[$existingTable, $missingColumn]],
            DatabaseHandler::MODIFIED_COLUMNS => [[$existingTable, $modifiedColumn]],
            DatabaseHandler::EXTRA_COLUMNS => [[$existingTable, $this->getColumn('field3', 'type3')]]
        ];

        self::assertFalse($databaseHandler->repairDatabase($information));
        self::assertFalse($databaseHandler->repairDatabase($information));
        self::assertFalse($databaseHandler->repairDatabase($information));
        self::assertTrue($databaseHandler->repairDatabase($information));
        self::assertTrue($databaseHandler->repairDatabase([]));
    }

    /**
     * @group  unit
     * @covers ::isDatabaseUpdateNecessary()
     * @covers ::getActivePluginSites()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('getSites')
            ->will($this->onConsecutiveCalls(
                $this->getSites(),
                [],
                $this->getSites(1),
                $this->getSites()
            ));

        $wordpress->expects($this->exactly(6))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, true, true, true, true));


        $wordpress->expects($this->exactly(11))
            ->method('getOption')
            ->withConsecutive(
                ['uam_db_version'],
                ['uam_db_version'],
                ['active_plugins'],
                ['active_plugins'],
                ['active_plugins'],
                ['uam_db_version'],
                ['active_plugins'],
                ['active_plugins'],
                ['active_plugins'],
                ['active_plugins'],
                ['uam_db_version']
            )
            ->will($this->onConsecutiveCalls(
                '1000.0.0',
                '0.0',
                ['some/plugin', 'user-access-manager/user-access-manager.php'],
                ['some/plugin', 'user-access-manager/user-access-manager.php'],
                ['some/plugin', 'user-access-manager/user-access-manager.php'],
                '1000.0',
                ['some/plugin', 'user-access-manager/user-access-manager.php'],
                ['some/plugin', 'user-access-manager/user-access-manager.php'],
                ['some/plugin', 'user-access-manager/user-access-manager.php'],
                ['some/plugin'],
                '1000.0'
            ));

        $database = $this->getDatabase();
        $database->expects($this->exactly(5))
            ->method('getBlogPrefix')
            ->will($this->returnValue('prefix_'));

        $database->expects($this->exactly(5))
            ->method('prepare')
            ->with('SELECT option_value FROM prefix_options WHERE option_name = \'%s\' LIMIT 1', 'uam_db_version')
            ->will($this->returnValue('preparedStatement'));

        $database->expects($this->exactly(5))
            ->method('getVariable')
            ->with('preparedStatement')
            ->will($this->onConsecutiveCalls('1000.0.0', '0.0', '0.0', null, null));

        $databaseHandler = new DatabaseHandler(
            $wordpress,
            $database,
            $this->getDatabaseObjectFactory(),
            $this->getUpdateFactory()
        );

        self::assertFalse($databaseHandler->isDatabaseUpdateNecessary());
        self::assertTrue($databaseHandler->isDatabaseUpdateNecessary());
        self::assertTrue($databaseHandler->isDatabaseUpdateNecessary());
        self::assertFalse($databaseHandler->isDatabaseUpdateNecessary());
        self::assertTrue($databaseHandler->isDatabaseUpdateNecessary());
        self::assertFalse($databaseHandler->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers ::backupDatabase()
     */
    public function testBackup()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('getOption')
            ->with('uam_db_version')
            ->will($this->onConsecutiveCalls(null, '1.1', '1.2', '1.3.0'));

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(5))
            ->method('query')
            ->withConsecutive(
                ['CREATE TABLE `userGroupTable_1-2` LIKE `userGroupTable`'],
                ['INSERT `userGroupTable_1-2` SELECT * FROM `userGroupTable`'],
                ['CREATE TABLE `userGroupToObjectTable_1-2` LIKE `userGroupToObjectTable`'],
                ['INSERT `userGroupToObjectTable_1-2` SELECT * FROM `userGroupToObjectTable`'],
                ['CREATE TABLE `userGroupTable_1-3-0` LIKE `userGroupTable`']
            )
            ->will($this->onConsecutiveCalls(true, true, true, true, false));

        $databaseHandler = new DatabaseHandler(
            $wordpress,
            $database,
            $this->getDatabaseObjectFactory(),
            $this->getUpdateFactory()
        );

        self::assertFalse($databaseHandler->backupDatabase());
        self::assertFalse($databaseHandler->backupDatabase());
        self::assertTrue($databaseHandler->backupDatabase());
        self::assertFalse($databaseHandler->backupDatabase());
    }

    /**
     * @group  unit
     * @covers ::getBackups()
     */
    public function testGetBackups()
    {
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        $database->expects($this->once())
            ->method('getColumn')
            ->with('SHOW TABLES LIKE \'prefix_uam_%\'')
            ->will($this->returnValue([
                'prefix_uam_one_1-2',
                'prefix_uam_two_1-2',
                'prefix_uam_one_1-5-6',
                'something_1-2-3',
                'invalid1-4'
            ]));

        $databaseHandler = new DatabaseHandler(
            $this->getWordpress(),
            $database,
            $this->getDatabaseObjectFactory(),
            $this->getUpdateFactory()
        );

        self::assertEquals(
            ['1.2' => '1.2', '1.5.6' => '1.5.6', '1.2.3' => '1.2.3'],
            $databaseHandler->getBackups()
        );
    }

    /**
     * @group  unit
     * @covers ::revertDatabase()
     * @covers ::getBackupTables()
     */
    public function testRevertBackup()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('updateOption')
            ->with('uam_db_version', '1.2');

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(4))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupTable_1-3-1\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-3-1\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable_1-2',
                'userGroupToObjectTable_1-2',
                '',
                'userGroupToObjectTable_1-3-0'
            ));

        $database->expects($this->exactly(5))
            ->method('query')
            ->withConsecutive(
                ['DROP TABLE IF EXISTS `userGroupTable`'],
                ['RENAME TABLE `userGroupTable_1-2` TO `userGroupTable`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable`'],
                ['RENAME TABLE `userGroupToObjectTable_1-2` TO `userGroupToObjectTable`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable`']
            )
            ->will($this->onConsecutiveCalls(true, true, true, true, false));

        $databaseHandler = new DatabaseHandler(
            $wordpress,
            $database,
            $this->getDatabaseObjectFactory(),
            $this->getUpdateFactory()
        );

        self::assertTrue($databaseHandler->revertDatabase('1.2'));
        self::assertFalse($databaseHandler->revertDatabase('1.3.1'));
    }

    /**
     * @group  unit
     * @covers ::deleteBackup()
     * @covers ::getBackupTables()
     */
    public function testDeleteBackup()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(4))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupTable_1-3-1\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-3-1\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable_1-2',
                'userGroupToObjectTable_1-2',
                '',
                'userGroupToObjectTable_1-3-1'
            ));

        $database->expects($this->exactly(3))
            ->method('query')
            ->withConsecutive(
                ['DROP TABLE IF EXISTS `userGroupTable_1-2`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable_1-2`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable_1-3-1`']
            )
            ->will($this->onConsecutiveCalls(true, true, false));

        $databaseHandler = new DatabaseHandler(
            $this->getWordpress(),
            $database,
            $this->getDatabaseObjectFactory(),
            $this->getUpdateFactory()
        );

        self::assertTrue($databaseHandler->deleteBackup('1.2'));
        self::assertFalse($databaseHandler->deleteBackup('1.3.1'));
    }

    /**
     * @param string $version
     * @param bool $executeUpdate
     * @param bool $success
     * @return MockObject|UpdateInterface
     */
    private function getUpdate(string $version, bool $executeUpdate = false, bool $success = false): MockObject|UpdateInterface
    {
        $update = $this->createMock(UpdateInterface::class);
        $update->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue($version));

        $updateExpects = ($executeUpdate === true) ? $this->any() : $this->never();

        $update->expects($updateExpects)
            ->method('update')
            ->will($this->returnValue($success));

        return $update;
    }

    /**
     * @group  unit
     * @covers ::getOrderedDatabaseUpdates()
     * @covers ::updateDatabase()
     */
    public function testUpdate()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('getOption')
            ->withConsecutive(
                ['uam_db_version', false],
                ['uam_db_version', false],
                ['uam_db_version', false],
                ['uam_db_version', false]
            )
            ->will($this->onConsecutiveCalls('0', '0.0', '1.0', '1.0'));

        $wordpress->expects($this->once())
            ->method('updateOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $updatesWithError = [
            $this->getUpdate('0'),
            $this->getUpdate('10', true),
            $this->getUpdate('1', true, true),
        ];

        $updateFactory = $this->getUpdateFactory();
        $updateFactory->expects($this->exactly(3))
            ->method('getDatabaseUpdates')
            ->will($this->onConsecutiveCalls(
                $updatesWithError,
                $updatesWithError,
                [
                    $this->getUpdate('10', true, true),
                    $this->getUpdate('1', true, true),
                ]
            ));

        $databaseHandler = new DatabaseHandler(
            $wordpress,
            $this->getDatabase(),
            $this->getDatabaseObjectFactory(),
            $updateFactory
        );

        self::assertFalse($databaseHandler->updateDatabase());
        self::assertFalse($databaseHandler->updateDatabase());
        self::assertFalse($databaseHandler->updateDatabase());
        self::assertTrue($databaseHandler->updateDatabase());
    }

    /**
     * @group  unit
     * @covers ::removeTables()
     * @covers ::getTables()
     * @throws MissingColumnsException
     */
    public function testRemoveTables()
    {
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getCharset')
            ->will($this->returnValue('charset'));

        $database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('query')
            ->withConsecutive(
                [new MatchIgnoreWhitespace('DROP TABLE IF EXISTS `userGroupTable`')],
                [new MatchIgnoreWhitespace('DROP TABLE IF EXISTS `userGroupToObjectTable`')]
            );


        $databaseObjectFactory = $this->getDatabaseObjectFactory();
        $databaseObjectFactory->expects($this->exactly(2))
            ->method('createTable')
            ->will($this->onConsecutiveCalls(
                $this->getTable('userGroupTable'),
                $this->getTable('userGroupToObjectTable')
            ));

        $databaseObjectFactory->expects($this->any())
            ->method('createColumn')
            ->will($this->returnCallback(function () {
                return $this->createMock(Column::class);
            }));

        $databaseHandler = new DatabaseHandler(
            $this->getWordpress(),
            $database,
            $databaseObjectFactory,
            $this->getUpdateFactory()
        );

        $databaseHandler->removeTables();
    }
}
