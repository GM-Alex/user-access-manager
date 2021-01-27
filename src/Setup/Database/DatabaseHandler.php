<?php
/**
 * DatabaseHandler.php
 *
 * The DatabaseHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Setup\Database;

use UserAccessManager\Database\Database;
use UserAccessManager\Setup\Update\UpdateFactory;
use UserAccessManager\Setup\Update\UpdateInterface;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class DatabaseHandler
 *
 * @package UserAccessManager\Setup\Database
 */
class DatabaseHandler
{
    const MISSING_TABLES = 'MISSING_TABLE';
    const MISSING_COLUMNS = 'MISSING_COLUMNS';
    const MODIFIED_COLUMNS = 'MODIFIED_COLUMNS';
    const EXTRA_COLUMNS = 'EXTRA_COLUMNS';

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var DatabaseObjectFactory
     */
    private $databaseObjectFactory;

    /**
     * @var UpdateFactory
     */
    private $updateFactory;

    /**
     * DatabaseHandler constructor.
     * @param Wordpress $wordpress
     * @param Database $database
     * @param DatabaseObjectFactory $databaseObjectFactory
     * @param UpdateFactory $updateFactory
     */
    public function __construct(
        Wordpress $wordpress,
        Database $database,
        DatabaseObjectFactory $databaseObjectFactory,
        UpdateFactory $updateFactory
    ) {
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->databaseObjectFactory = $databaseObjectFactory;
        $this->updateFactory = $updateFactory;
    }

    /**
     * Checks if the table exists.
     * @param string $table
     * @return bool
     */
    private function tableExists(string $table): bool
    {
        $dbTable = $this->database->getVariable("SHOW TABLES LIKE '{$table}'");

        return ($table === $dbTable);
    }

    /**
     * Adds a table.
     * @param Table $table
     */
    private function addTable(Table $table)
    {
        $this->database->dbDelta((string) $table);
    }

    /**
     * Returns all tables.
     * @return Table[]
     * @throws MissingColumnsException
     */
    private function getTables(): array
    {
        $charsetCollate = $this->database->getCharset();
        $tables = [];

        $tables[] = $this->databaseObjectFactory->createTable(
            $this->database->getUserGroupTable(),
            $charsetCollate,
            [
                $this->databaseObjectFactory->createColumn('ID', 'INT(11)', false, null, true, true),
                $this->databaseObjectFactory->createColumn('groupname', 'TINYTEXT'),
                $this->databaseObjectFactory->createColumn('groupdesc', 'TEXT'),
                $this->databaseObjectFactory->createColumn('read_access', 'TINYTEXT'),
                $this->databaseObjectFactory->createColumn('write_access', 'TINYTEXT'),
                $this->databaseObjectFactory->createColumn('ip_range', 'MEDIUMTEXT', true)
            ]
        );

        $tables[] = $this->databaseObjectFactory->createTable(
            $this->database->getUserGroupToObjectTable(),
            $charsetCollate,
            [
                $this->databaseObjectFactory->createColumn('object_id', 'VARCHAR(32)', false, null, true),
                $this->databaseObjectFactory->createColumn('general_object_type', 'VARCHAR(64)'),
                $this->databaseObjectFactory->createColumn('object_type', 'VARCHAR(32)', false, null, true),
                $this->databaseObjectFactory->createColumn('group_id', 'VARCHAR(32)', false, null, true),
                $this->databaseObjectFactory->createColumn('group_type', 'VARCHAR(32)', false, null, true),
                $this->databaseObjectFactory->createColumn('from_date', 'DATETIME', true),
                $this->databaseObjectFactory->createColumn('to_date', 'DATETIME', true)
            ]
        );

        return $tables;
    }

    /**
     * Adds the tables to the database.
     * @throws MissingColumnsException
     */
    public function install()
    {
        foreach ($this->getTables() as $table) {
            if ($this->tableExists($table->getName()) === false) {
                $this->addTable($table);
            }
        }

        $this->wordpress->addOption('uam_db_version', UserAccessManager::DB_VERSION);
    }

    /**
     * Returns the existing columns for a table.
     * @param Table $table
     * @return Column[]
     */
    private function getExistingColumns(Table $table): array
    {
        $query = "SHOW COLUMNS FROM `{$table->getName()}`;";
        $existingRawColumns = (array) $this->database->getResults($query);
        $existingColumns = [];

        foreach ($existingRawColumns as $existingRawColumn) {
            $existingColumns[$existingRawColumn->Field] = $this->databaseObjectFactory->createColumn(
                $existingRawColumn->Field,
                strtoupper($existingRawColumn->Type),
                $existingRawColumn->Null === 'YES',
                $existingRawColumn->Default,
                $existingRawColumn->Key === 'PRI',
                $existingRawColumn->Extra === 'auto_increment'
            );
        }

        return $existingColumns;
    }

    /**
     * Add corrupted columns to the information array if the are some.
     * @param Table $table
     * @param array $information
     */
    private function addCorruptedRows(Table $table, array &$information)
    {
        $existingColumns = $this->getExistingColumns($table);

        foreach ($table->getColumns() as $column) {
            if (isset($existingColumns[$column->getName()]) === false) {
                $information[self::MISSING_COLUMNS][] = [$table, $column];
                continue;
            }

            $existingColumn = $existingColumns[$column->getName()];
            unset($existingColumns[$column->getName()]);

            if ((string) $column !== (string) $existingColumn) {
                $information[self::MODIFIED_COLUMNS][] = [$table, $column];
                continue;
            }
        }

        foreach ($existingColumns as $existingColumn) {
            $information[self::EXTRA_COLUMNS][] = [$table, $existingColumn];
        }
    }

    /**
     * Returns corrupted database information.
     * @return array
     * @throws MissingColumnsException
     */
    public function getCorruptedDatabaseInformation(): array
    {
        $information = [
            self::MISSING_TABLES => [],
            self::MISSING_COLUMNS => [],
            self::MODIFIED_COLUMNS => [],
            self::EXTRA_COLUMNS => []
        ];

        foreach ($this->getTables() as $table) {
            if ($this->tableExists($table->getName()) === false) {
                $information[self::MISSING_TABLES][] = $table;
                continue;
            }

            $this->addCorruptedRows($table, $information);
        }

        return $information;
    }

    /**
     * Adds a new column.
     * @param Table $table
     * @param Column $column
     * @return bool
     */
    private function addColumn(Table $table, Column $column): bool
    {
        return $this->database->query("ALTER TABLE `{$table->getName()}` ADD $column;") !== false;
    }

    /**
     * Modify an existing column.
     * @param Table $table
     * @param Column $column
     * @return bool
     */
    private function modifyColumn(Table $table, Column $column): bool
    {
        return $this->database->query("ALTER TABLE `{$table->getName()}` MODIFY $column;") !== false;
    }

    /**
     * Drops an existing column.
     * @param Table $table
     * @param Column $column
     * @return bool
     */
    private function dropColumn(Table $table, Column $column): bool
    {
        return $this->database->query("ALTER TABLE `{$table->getName()}` DROP `{$column->getName()}`;") !== false;
    }

    /**
     * Repairs a corrupt database.
     * @param array $information
     * @return bool
     * @throws MissingColumnsException
     */
    public function repairDatabase(array $information = []): bool
    {
        $success = true;
        $information = ($information === []) ? $this->getCorruptedDatabaseInformation() : $information;

        foreach ($information[self::MISSING_TABLES] as $table) {
            $this->addTable($table);
        }

        foreach ($information[self::MISSING_COLUMNS] as $columnInformation) {
            $success = $success && $this->addColumn($columnInformation[0], $columnInformation[1]);
        }

        foreach ($information[self::MODIFIED_COLUMNS] as $columnInformation) {
            $success = $success && $this->modifyColumn($columnInformation[0], $columnInformation[1]);
        }

        foreach ($information[self::EXTRA_COLUMNS] as $columnInformation) {
            $success = $success && $this->dropColumn($columnInformation[0], $columnInformation[1]);
        }

        return $success;
    }

    /**
     * Returns all sites where the user access manager is active.
     * @return array
     */
    private function getActivePluginSites(): array
    {
        $activeSites = [];

        foreach ($this->wordpress->getSites() as $site) {
            $this->wordpress->switchToBlog($site->blog_id);
            $plugins = (array) $this->wordpress->getOption('active_plugins', []);
            $pluginsMap = array_flip($plugins);

            if (isset($pluginsMap['user-access-manager/user-access-manager.php']) === true) {
                $activeSites[$site->blog_id] = $site->blog_id;
            }

            $this->wordpress->restoreCurrentBlog();
        }

        return $activeSites;
    }

    /**
     * Checks if a database update is necessary.
     * @return bool
     */
    public function isDatabaseUpdateNecessary(): bool
    {
        if ($this->wordpress->isSuperAdmin() === true) {
            foreach ($this->getActivePluginSites() as $siteId) {
                $table = $this->database->getBlogPrefix($siteId) . 'options';
                $select = "SELECT option_value FROM {$table} WHERE option_name = '%s' LIMIT 1";
                $select = $this->database->prepare($select, 'uam_db_version');
                $currentDbVersion = $this->database->getVariable($select);

                if ($currentDbVersion !== null
                    && version_compare($currentDbVersion, UserAccessManager::DB_VERSION, '<') === true
                ) {
                    return true;
                }
            }
        }

        $currentDbVersion = $this->wordpress->getOption('uam_db_version');
        return version_compare($currentDbVersion, UserAccessManager::DB_VERSION, '<');
    }

    /**
     * Creates a database backup.
     * @return bool
     */
    public function backupDatabase(): bool
    {
        $currentDbVersion = $this->wordpress->getOption('uam_db_version');

        if (empty($currentDbVersion) === true
            || version_compare($currentDbVersion, '1.2', '<') === true
        ) {
            return false;
        }

        $tables = [
            $this->database->getUserGroupTable(),
            $this->database->getUserGroupToObjectTable()
        ];

        $currentDbVersion = str_replace('.', '-', $currentDbVersion);
        $success = true;

        foreach ($tables as $table) {
            $createQuery = "CREATE TABLE `{$table}_{$currentDbVersion}` LIKE `{$table}`";
            $success = $success && ($this->database->query($createQuery) !== false);
            $insertQuery = "INSERT `{$table}_{$currentDbVersion}` SELECT * FROM `{$table}`";
            $success = $success && ($this->database->query($insertQuery) !== false);
        }

        return $success;
    }

    /**
     * Returns the version for which a backup was created.
     * @return array
     */
    public function getBackups(): array
    {
        $versions = [];
        $tables = (array) $this->database->getColumn(
            "SHOW TABLES LIKE '{$this->database->getPrefix()}uam_%'"
        );

        foreach ($tables as $table) {
            if (preg_match('/.*_([0-9\-]+)/i', $table, $matches) === 1) {
                $version = str_replace('-', '.', $matches[1]);
                $versions[$version] = $version;
            }
        }

        return $versions;
    }

    /**
     * Returns the backup tables for the given version.
     * @param string $version
     * @return array
     */
    private function getBackupTables(string $version): array
    {
        $backupTables = [];
        $tables = [
            $this->database->getUserGroupTable(),
            $this->database->getUserGroupToObjectTable()
        ];

        $versionForDb = str_replace('.', '-', $version);

        foreach ($tables as $table) {
            $backupTable = (string) $this->database->getVariable(
                "SHOW TABLES LIKE '{$table}_{$versionForDb}'"
            );

            if ($backupTable !== '') {
                $backupTables[$table] = $backupTable;
            }
        }

        return $backupTables;
    }

    /**
     * Reverts the database to the given version.
     * @param string $version
     * @return bool
     */
    public function revertDatabase(string $version): bool
    {
        $success = true;
        $tables = $this->getBackupTables($version);

        foreach ($tables as $table => $backupTable) {
            $dropQuery = "DROP TABLE IF EXISTS `{$table}`";
            $success = $success && ($this->database->query($dropQuery) !== false);
            $renameQuery = "RENAME TABLE `{$backupTable}` TO `{$table}`";
            $success = $success && ($this->database->query($renameQuery) !== false);
        }

        if ($success === true) {
            $this->wordpress->updateOption('uam_db_version', $version);
        }

        return $success;
    }

    /**
     * Deletes the given database backup.
     * @param string $version
     * @return bool
     */
    public function deleteBackup(string $version): bool
    {
        $success = true;
        $tables = $this->getBackupTables($version);

        foreach ($tables as $table => $backupTable) {
            $dropQuery = "DROP TABLE IF EXISTS `{$backupTable}`";
            $success = $success && ($this->database->query($dropQuery) !== false);
        }

        return $success;
    }

    /**
     * Returns the ordered updates.
     * @return UpdateInterface[]
     */
    private function getOrderedDatabaseUpdates(): array
    {
        $rawUpdates = $this->updateFactory->getDatabaseUpdates();
        $updates = [];

        foreach ($rawUpdates as $rawUpdate) {
            $updates[$rawUpdate->getVersion()] = $rawUpdate;
        }

        uksort($updates, 'version_compare');
        return $updates;
    }

    /**
     * Updates the database.
     * @return bool
     */
    public function updateDatabase(): bool
    {
        $currentDbVersion = $this->wordpress->getOption('uam_db_version');

        if (empty($currentDbVersion) === true) {
            return false;
        }

        $success = true;

        foreach ($this->getOrderedDatabaseUpdates() as $orderedUpdate) {
            if (version_compare($currentDbVersion, $orderedUpdate->getVersion(), '<') === true) {
                $success = $success && $orderedUpdate->update();
            }
        }

        if ($success === true) {
            $this->wordpress->updateOption('uam_db_version', UserAccessManager::DB_VERSION);
        }

        return $success;
    }

    /**
     * Removes the tables.
     * @throws MissingColumnsException
     */
    public function removeTables()
    {
        foreach ($this->getTables() as $table) {
            $dropQuery = "DROP TABLE IF EXISTS `{$table->getName()}`";
            $this->database->query($dropQuery);
        }
    }
}
