<?php
/**
 * SetupHandler.php
 *
 * The SetupHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Setup;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Setup\Update\UpdateFactory;
use UserAccessManager\Setup\Update\UpdateInterface;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class SetupHandler
 *
 * @package UserAccessManager\SetupHandler
 */
class SetupHandler
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var UpdateFactory
     */
    private $updateFactory;

    /**
     * SetupHandler constructor.
     *
     * @param Wordpress     $wordpress
     * @param Database      $database
     * @param ObjectHandler $objectHandler
     * @param FileHandler   $fileHandler
     * @param UpdateFactory $updateFactory
     */
    public function __construct(
        Wordpress $wordpress,
        Database $database,
        ObjectHandler $objectHandler,
        FileHandler $fileHandler,
        UpdateFactory $updateFactory
    ) {
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->fileHandler = $fileHandler;
        $this->updateFactory = $updateFactory;
    }

    /**
     * Returns all blog of the network.
     *
     * @return integer[]
     */
    public function getBlogIds()
    {
        $currentBlogId = $this->database->getCurrentBlogId();
        $blogIds = [$currentBlogId => $currentBlogId];
        $sites = $this->wordpress->getSites();

        foreach ($sites as $site) {
            $blogIds[$site->blog_id] = $site->blog_id;
        }

        return $blogIds;
    }

    /**
     * Installs the user access manager.
     *
     * @param bool $networkWide
     */
    public function install($networkWide = false)
    {
        if ($networkWide === true) {
            $blogIds = $this->getBlogIds();
            $currentBlogId = $this->database->getCurrentBlogId();

            foreach ($blogIds as $blogId) {
                $this->wordpress->switchToBlog($blogId);
                $this->runInstall();
            }

            $this->wordpress->switchToBlog($currentBlogId);
        } else {
            $this->runInstall();
        }
    }

    /**
     * Creates the needed tables at the database and adds the options
     */
    private function runInstall()
    {
        $charsetCollate = $this->database->getCharset();
        $dbAccessGroupTable = $this->database->getUserGroupTable();

        $dbUserGroup = $this->database->getVariable(
            "SHOW TABLES 
            LIKE '{$dbAccessGroupTable}'"
        );

        if ($dbUserGroup !== $dbAccessGroupTable) {
            $this->database->dbDelta(
                "CREATE TABLE {$dbAccessGroupTable} (
                    ID INT(11) NOT NULL AUTO_INCREMENT,
                    groupname TINYTEXT NOT NULL,
                    groupdesc TEXT NOT NULL,
                    read_access TINYTEXT NOT NULL,
                    write_access TINYTEXT NOT NULL,
                    ip_range MEDIUMTEXT NULL,
                    PRIMARY KEY (ID)
                ) {$charsetCollate};"
            );
        }

        $dbAccessGroupToObjectTable = $this->database->getUserGroupToObjectTable();

        $dbAccessGroupToObject = (string)$this->database->getVariable(
            "SHOW TABLES 
            LIKE '".$dbAccessGroupToObjectTable."'"
        );

        if ($dbAccessGroupToObject !== $dbAccessGroupToObjectTable) {
            $this->database->dbDelta(
                "CREATE TABLE {$dbAccessGroupToObjectTable} (
                    object_id VARCHAR(32) NOT NULL,
                    general_object_type VARCHAR(64) NOT NULL,
                    object_type VARCHAR(32) NOT NULL,
                    group_id VARCHAR(32) NOT NULL,
                    group_type VARCHAR(32) NOT NULL,
                    from_date DATETIME NULL DEFAULT NULL,
                    to_date DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (object_id, object_type, group_id, group_type)
                ) {$charsetCollate};"
            );
        }

        $this->wordpress->addOption('uam_db_version', UserAccessManager::DB_VERSION);
    }

    /**
     * Checks if a database update is necessary.
     *
     * @return bool
     */
    public function isDatabaseUpdateNecessary()
    {
        $blogIds = $this->getBlogIds();

        if ($this->wordpress->isSuperAdmin() === true) {
            foreach ($blogIds as $blogId) {
                $table = $this->database->getBlogPrefix($blogId).'options';
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
     *
     * @return bool
     */
    public function backupDatabase()
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
     *
     * @return array
     */
    public function getBackups()
    {
        $versions = [];
        $tables = (array)$this->database->getColumn(
            "SHOW TABLES LIKE '{$this->database->getPrefix()}uam_%'"
        );

        foreach ($tables as $table) {
            if (preg_match('/.*\_([0-9\-]+)/i', $table, $matches) === 1) {
                $version = str_replace('-', '.', $matches[1]);
                $versions[$version] = $version;
            }
        }

        return $versions;
    }

    /**
     * Returns the backup tables for the given version.
     *
     * @param string $version
     *
     * @return array
     */
    private function getBackupTables($version)
    {
        $backupTables = [];
        $tables = [
            $this->database->getUserGroupTable(),
            $this->database->getUserGroupToObjectTable()
        ];

        $versionForDb = str_replace('.', '-', $version);

        foreach ($tables as $table) {
            $backupTable = (string)$this->database->getVariable(
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
     *
     * @param string $version
     *
     * @return bool
     */
    public function revertDatabase($version)
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
     *
     * @param string $version
     *
     * @return bool
     */
    public function deleteBackup($version)
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
     *
     * @return UpdateInterface[]
     */
    private function getOrderedUpdates()
    {
        $rawUpdates = $this->updateFactory->getUpdates();
        $updates = [];

        foreach ($rawUpdates as $rawUpdate) {
            $updates[$rawUpdate->getVersion()] = $rawUpdate;
        }

        uksort($updates, 'version_compare');
        return $updates;
    }

    /**
     * Updates the user access manager if an old version was installed.
     *
     * @return bool
     */
    public function update()
    {
        $currentDbVersion = $this->wordpress->getOption('uam_db_version');

        if (empty($currentDbVersion) === true) {
            return false;
        }

        $uamVersion = $this->wordpress->getOption('uam_version', '0');

        if (version_compare($uamVersion, '1.0', '<') === true) {
            $this->wordpress->deleteOption('allow_comments_locked');
        }

        $success = true;

        if (version_compare($currentDbVersion, UserAccessManager::DB_VERSION, '<') === true) {
            foreach ($this->getOrderedUpdates() as $orderedUpdate) {
                if (version_compare($currentDbVersion, $orderedUpdate->getVersion(), '<=') === true) {
                    $success = $success && $orderedUpdate->update();
                }
            }

            if ($success === true) {
                $this->wordpress->updateOption('uam_db_version', UserAccessManager::DB_VERSION);
            }
        }

        return $success;
    }

    /**
     * Clean up wordpress if the plugin will be uninstalled.
     */
    public function uninstall()
    {
        $currentBlogId = $this->database->getCurrentBlogId();
        $blogIds = $this->getBlogIds();

        foreach ($blogIds as $blogId) {
            $this->wordpress->switchToBlog($blogId);
            $userGroupTable = $this->database->getUserGroupTable();
            $userGroupToObjectTable = $this->database->getUserGroupToObjectTable();

            $dropQuery = "DROP TABLE IF EXISTS {$userGroupTable}, {$userGroupToObjectTable}";
            $this->database->query($dropQuery);

            $this->wordpress->deleteOption(MainConfig::MAIN_CONFIG_KEY);
            $this->wordpress->deleteOption('uam_version');
            $this->wordpress->deleteOption('uam_db_version');
        }

        $this->wordpress->switchToBlog($currentBlogId);
        $this->fileHandler->deleteFileProtection();
    }

    /**
     * Remove the htaccess file if the plugin is deactivated.
     *
     * @return bool
     */
    public function deactivate()
    {
        return $this->fileHandler->deleteFileProtection();
    }
}
