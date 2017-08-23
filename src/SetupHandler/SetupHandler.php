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
namespace UserAccessManager\SetupHandler;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroup;
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
     * SetupHandler constructor.
     *
     * @param Wordpress     $wordpress
     * @param Database      $database
     * @param ObjectHandler $objectHandler
     * @param FileHandler   $fileHandler
     */
    public function __construct(
        Wordpress $wordpress,
        Database $database,
        ObjectHandler $objectHandler,
        FileHandler $fileHandler
    ) {
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->fileHandler = $fileHandler;
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
                    object_id VARCHAR(64) NOT NULL,
                    general_object_type VARCHAR(64) NOT NULL,
                    object_type VARCHAR(64) NOT NULL,
                    group_id VARCHAR(64) NOT NULL,
                    group_type VARCHAR(64) NOT NULL,
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
     * Updates the user group table to version 1.0.
     *
     * @param string $userGroupTable
     */
    private function updateTo10UserGroupTableUpdate($userGroupTable)
    {
        $alterQuery = "ALTER TABLE {$userGroupTable}
                ADD read_access TINYTEXT NOT NULL DEFAULT '', 
                ADD write_access TINYTEXT NOT NULL DEFAULT '', 
                ADD ip_range MEDIUMTEXT NULL DEFAULT ''";

        $this->database->query($alterQuery);

        $updateQuery = "UPDATE {$userGroupTable} SET read_access = 'group', write_access = 'group'";
        $this->database->query($updateQuery);

        $selectQuery = "SHOW columns FROM {$userGroupTable} LIKE 'ip_range'";
        $dbIpRange = $this->database->getVariable($selectQuery);

        if ($dbIpRange != 'ip_range') {
            $alterQuery = "ALTER TABLE {$userGroupTable} ADD ip_range MEDIUMTEXT NULL DEFAULT ''";
            $this->database->query($alterQuery);
        }
    }

    /**
     * Updates the user group to object table to version 1.0.
     */
    private function updateTo10UserGroupToObjectTableUpdate()
    {
        $prefix = $this->database->getPrefix();

        $charsetCollate = $this->database->getCharset();
        $userGroupToObject = $prefix.'uam_accessgroup_to_object';
        $userGroupToPost = $prefix.'uam_accessgroup_to_post';
        $userGroupToUser = $prefix.'uam_accessgroup_to_user';
        $userGroupToCategory = $prefix.'uam_accessgroup_to_category';
        $userGroupToRole = $prefix.'uam_accessgroup_to_role';

        $alterQuery = "ALTER TABLE '{$userGroupToObject}'
            CHANGE 'object_id' 'object_id' VARCHAR(64) {$charsetCollate}";
        $this->database->query($alterQuery);

        $objectTypes = $this->objectHandler->getObjectTypes();
        $postTable = $this->database->getPostsTable();

        foreach ($objectTypes as $objectType) {
            $addition = '';

            if ($this->objectHandler->isPostType($objectType) === true) {
                $dbIdName = 'post_id';
                $database = $userGroupToPost.', '.$postTable;
                $addition = " WHERE post_id = ID AND post_type = '{$objectType}'";
            } elseif ($objectType === 'category') {
                $dbIdName = 'category_id';
                $database = $userGroupToCategory;
            } elseif ($objectType === 'user') {
                $dbIdName = 'user_id';
                $database = $userGroupToUser;
            } elseif ($objectType === 'role') {
                $dbIdName = 'role_name';
                $database = $userGroupToRole;
            } else {
                continue;
            }

            $query = "SELECT {$dbIdName} AS id, group_id AS groupId FROM {$database} {$addition}";
            $dbObjects = (array)$this->database->getResults($query);

            foreach ($dbObjects as $dbObject) {
                $this->database->insert(
                    $userGroupToObject,
                    [
                        'group_id' => $dbObject->groupId,
                        'object_id' => $dbObject->id,
                        'object_type' => $objectType,
                    ],
                    [
                        '%d',
                        '%d',
                        '%s',
                    ]
                );
            }
        }

        $dropQuery = "DROP TABLE {$userGroupToPost},
            {$userGroupToUser},
            {$userGroupToCategory},
            {$userGroupToRole}";

        $this->database->query($dropQuery);
    }
    
    /**
     * Update to database version 1.0.
     */
    private function updateTo10()
    {
        $userGroupTable = $this->database->getUserGroupTable();
        $dbUserGroup = $this->database->getVariable("SHOW TABLES LIKE '{$userGroupTable}'");

        if ($dbUserGroup === $userGroupTable) {
            $this->updateTo10UserGroupTableUpdate($userGroupTable);
        }

        $this->updateTo10UserGroupToObjectTableUpdate();
    }

    /**
     * Update to database version 1.2.
     */
    private function updateTo12()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "ALTER TABLE `{$dbAccessGroupToObject}`
            CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
            CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

        $this->database->query($query);
    }

    /**
     * Update to database version 1.3.
     */
    private function updateTo13()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $generalTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $this->database->update(
            $dbAccessGroupToObject,
            ['object_type' => $generalTermType],
            ['object_type' => 'category']
        );
    }

    /**
     * Update to database version 1.4.
     */
    private function updateTo14()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $alterQuery = "ALTER TABLE {$dbAccessGroupToObject}
            ADD general_object_type VARCHAR(64) NOT NULL AFTER object_id";

        $this->database->query($alterQuery);

        // Update post entries
        $generalPostType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalPostType}'
            WHERE object_type IN ('post', 'page', 'attachment')";

        $this->database->query($query);

        // Update role entries
        $generalRoleType = ObjectHandler::GENERAL_ROLE_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalRoleType}'
            WHERE object_type = 'role'";

        $this->database->query($query);

        // Update user entries
        $generalUserType = ObjectHandler::GENERAL_USER_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalUserType}'
            WHERE object_type = 'user'";

        $this->database->query($query);

        // Update term entries
        $generalTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalTermType}'
            WHERE object_type = 'term'";

        $this->database->query($query);

        $query = "UPDATE {$dbAccessGroupToObject} AS gto
            LEFT JOIN {$this->database->getTermTaxonomyTable()} AS tt 
              ON gto.object_id = tt.term_id
            SET gto.object_type = tt.taxonomy
            WHERE gto.general_object_type = '{$generalTermType}'";

        $this->database->query($query);
    }

    /**
     * Update to database version 1.5.1.
     */
    private function updateTo151()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "SELECT object_id AS objectId, object_type AS objectType, group_id AS groupId
            FROM {$dbAccessGroupToObject}
            WHERE general_object_type = ''";

        $dbObjects = (array)$this->database->getResults($query);

        foreach ($dbObjects as $dbObject) {
            $this->database->update(
                $dbAccessGroupToObject,
                ['general_object_type' => $this->objectHandler->getGeneralObjectType($dbObject->objectType)],
                [
                    'object_id' => $dbObject->objectId,
                    'group_id' => $dbObject->groupId,
                    'object_type' => $dbObject->objectType
                ]
            );
        }
    }
    
    /**
     * Update to database version 1.6.
     */
    private function updateTo16()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $alterQuery = "ALTER TABLE {$dbAccessGroupToObject}
            ADD group_type VARCHAR(64) NOT NULL AFTER group_id,
            ADD from_date DATETIME NULL DEFAULT NULL,
            ADD to_date DATETIME NULL DEFAULT NULL,
            MODIFY group_id VARCHAR(64) NOT NULL,
            MODIFY object_id VARCHAR(64) NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (object_id, object_type, group_id, group_type)";

        $this->database->query($alterQuery);

        $this->database->update(
            $dbAccessGroupToObject,
            ['group_type' => UserGroup::USER_GROUP_TYPE],
            ['group_type' => '']
        );
    }

    /**
     * Checks if an update is necessary and if yes executes the update function.
     *
     * @param string   $currentDbVersion
     * @param string   $version
     * @param callable $updateFunction
     */
    private function isUpdateNecessary($currentDbVersion, $version, $updateFunction)
    {
        if (version_compare($currentDbVersion, $version, '<=') === true) {
            $updateFunction();
        }
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

        if (version_compare($currentDbVersion, UserAccessManager::DB_VERSION, '<') === true) {
            $this->isUpdateNecessary(
                $currentDbVersion,
                '1.0',
                function () {
                    $this->updateTo10();
                }
            );
            $this->isUpdateNecessary(
                $currentDbVersion,
                '1.2',
                function () {
                    $this->updateTo12();
                }
            );
            $this->isUpdateNecessary(
                $currentDbVersion,
                '1.3',
                function () {
                    $this->updateTo13();
                }
            );
            $this->isUpdateNecessary(
                $currentDbVersion,
                '1.4',
                function () {
                    $this->updateTo14();
                }
            );
            $this->isUpdateNecessary(
                $currentDbVersion,
                '1.5.1',
                function () {
                    $this->updateTo151();
                }
            );
            $this->isUpdateNecessary(
                $currentDbVersion,
                '1.6',
                function () {
                    $this->updateTo16();
                }
            );

            $this->wordpress->updateOption('uam_db_version', UserAccessManager::DB_VERSION);
        }

        return true;
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
