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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\SetupHandler;

use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
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
    protected $Wordpress;

    /**
     * @var Database
     */
    protected $Database;

    /**
     * @var ObjectHandler
     */
    protected $ObjectHandler;

    /**
     * @var FileHandler
     */
    protected $FileHandler;

    /**
     * SetupHandler constructor.
     *
     * @param Wordpress     $Wordpress
     * @param Database      $Database
     * @param ObjectHandler $ObjectHandler
     * @param FileHandler   $FileHandler
     */
    public function __construct(
        Wordpress $Wordpress,
        Database $Database,
        ObjectHandler $ObjectHandler,
        FileHandler $FileHandler
    ) {
        $this->Wordpress = $Wordpress;
        $this->Database = $Database;
        $this->ObjectHandler = $ObjectHandler;
        $this->FileHandler = $FileHandler;
    }

    /**
     * Returns all blog of the network.
     *
     * @return array()
     */
    public function getBlogIds()
    {
        $iCurrentBlogId = $this->Database->getCurrentBlogId();
        $aBlogIds = [$iCurrentBlogId => $iCurrentBlogId];
        $aSites = $this->Wordpress->getSites();

        foreach ($aSites as $Site) {
            $aBlogIds[$Site->blog_id] = $Site->blog_id;
        }

        return $aBlogIds;
    }

    /**
     * Installs the user access manager.
     *
     * @param bool $blNetworkWide
     */
    public function install($blNetworkWide = false)
    {
        if ($blNetworkWide === true) {
            $aBlogIds = $this->getBlogIds();
            $iCurrentBlogId = $this->Database->getCurrentBlogId();

            foreach ($aBlogIds as $iBlogId) {
                $this->Wordpress->switchToBlog($iBlogId);
                $this->runInstall();
            }

            $this->Wordpress->switchToBlog($iCurrentBlogId);
        } else {
            $this->runInstall();
        }
    }

    /**
     * Creates the needed tables at the database and adds the options
     */
    protected function runInstall()
    {
        $sCharsetCollate = $this->Database->getCharset();
        $sDbAccessGroupTable = $this->Database->getUserGroupTable();

        $sDbUserGroup = $this->Database->getVariable(
            "SHOW TABLES 
            LIKE '{$sDbAccessGroupTable}'"
        );

        if ($sDbUserGroup !== $sDbAccessGroupTable) {
            $this->Database->dbDelta(
                "CREATE TABLE {$sDbAccessGroupTable} (
                    ID INT(11) NOT NULL AUTO_INCREMENT,
                    groupname TINYTEXT NOT NULL,
                    groupdesc TEXT NOT NULL,
                    read_access TINYTEXT NOT NULL,
                    write_access TINYTEXT NOT NULL,
                    ip_range MEDIUMTEXT NULL,
                    PRIMARY KEY (ID)
                ) {$sCharsetCollate};"
            );
        }

        $sDbAccessGroupToObjectTable = $this->Database->getUserGroupToObjectTable();

        $sDbAccessGroupToObject = (string)$this->Database->getVariable(
            "SHOW TABLES 
            LIKE '".$sDbAccessGroupToObjectTable."'"
        );

        if ($sDbAccessGroupToObject !== $sDbAccessGroupToObjectTable) {
            $this->Database->dbDelta(
                "CREATE TABLE {$sDbAccessGroupToObjectTable} (
                    object_id VARCHAR(64) NOT NULL,
                    general_object_type VARCHAR(64) NOT NULL,
                    object_type VARCHAR(64) NOT NULL,
                    group_id INT(11) NOT NULL,
                    PRIMARY KEY (object_id,object_type,group_id)
                ) {$sCharsetCollate};"
            );
        }

        $this->Wordpress->addOption('uam_db_version', UserAccessManager::DB_VERSION);
    }

    /**
     * Checks if a database update is necessary.
     *
     * @return boolean
     */
    public function isDatabaseUpdateNecessary()
    {
        $aBlogIds = $this->getBlogIds();

        if ($this->Wordpress->isSuperAdmin() === true) {
            foreach ($aBlogIds as $iBlogId) {
                $sTable = $this->Database->getBlogPrefix($iBlogId).'options';
                $sSelect = "SELECT option_value FROM {$sTable} WHERE option_name = '%s' LIMIT 1";
                $sSelect = $this->Database->prepare($sSelect, 'uam_db_version');
                $sCurrentDbVersion = $this->Database->getVariable($sSelect);

                if (version_compare($sCurrentDbVersion, UserAccessManager::DB_VERSION, '<') === true) {
                    return true;
                }
            }
        }

        $sCurrentDbVersion = $this->Wordpress->getOption('uam_db_version');
        return version_compare($sCurrentDbVersion, UserAccessManager::DB_VERSION, '<');
    }

    /**
     * Updates the user access manager if an old version was installed.
     *
     * @return true;
     */
    public function update()
    {
        $sCurrentDbVersion = $this->Wordpress->getOption('uam_db_version');

        if (empty($sCurrentDbVersion)) {
            return false;
        }

        $sUamVersion = $this->Wordpress->getOption('uam_version', '0');

        if (version_compare($sUamVersion, '1.0', '<') === true) {
            $this->Wordpress->deleteOption('allow_comments_locked');
        }

        $sDbAccessGroup = $this->Database->getUserGroupTable();

        $sDbUserGroup = $this->Database->getVariable(
            "SHOW TABLES LIKE '{$sDbAccessGroup}'"
        );

        if (version_compare($sCurrentDbVersion, UserAccessManager::DB_VERSION, '<') === true) {
            $sPrefix = $this->Database->getPrefix();
            $sCharsetCollate = $this->Database->getCharset();

            if (version_compare($sCurrentDbVersion, '1.0', '<=') === true) {
                if ($sDbUserGroup === $sDbAccessGroup) {
                    $sAlterQuery = "ALTER TABLE {$sDbAccessGroup}
                        ADD read_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD write_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD ip_range MEDIUMTEXT NULL DEFAULT ''";
                    $this->Database->query($sAlterQuery);

                    $sUpdateQuery = "UPDATE {$sDbAccessGroup}
                        SET read_access = 'group', write_access = 'group'";
                    $this->Database->query($sUpdateQuery);

                    $sSelectQuery = "SHOW columns FROM {$sDbAccessGroup} LIKE 'ip_range'";
                    $sDbIpRange = $this->Database->getVariable($sSelectQuery);

                    if ($sDbIpRange != 'ip_range') {
                        $sAlterQuery = "ALTER TABLE {$sDbAccessGroup}
                            ADD ip_range MEDIUMTEXT NULL DEFAULT ''";

                        $this->Database->query($sAlterQuery);
                    }
                }

                $sDbAccessGroupToObject = $sPrefix.'uam_accessgroup_to_object';
                $sDbAccessGroupToPost = $sPrefix.'uam_accessgroup_to_post';
                $sDbAccessGroupToUser = $sPrefix.'uam_accessgroup_to_user';
                $sDbAccessGroupToCategory = $sPrefix.'uam_accessgroup_to_category';
                $sDbAccessGroupToRole = $sPrefix.'uam_accessgroup_to_role';

                $sAlterQuery = "ALTER TABLE '{$sDbAccessGroupToObject}'
                    CHANGE 'object_id' 'object_id' VARCHAR(64) {$sCharsetCollate}";
                $this->Database->query($sAlterQuery);

                $aObjectTypes = $this->ObjectHandler->getObjectTypes();
                $sPostTable = $this->Database->getPostsTable();

                foreach ($aObjectTypes as $sObjectType) {
                    $sAddition = '';

                    if ($this->ObjectHandler->isPostType($sObjectType) === true) {
                        $sDbIdName = 'post_id';
                        $sDatabase = $sDbAccessGroupToPost.', '.$sPostTable;
                        $sAddition = " WHERE post_id = ID
                            AND post_type = '".$sObjectType."'";
                    } elseif ($sObjectType === 'category') {
                        $sDbIdName = 'category_id';
                        $sDatabase = $sDbAccessGroupToCategory;
                    } elseif ($sObjectType === 'user') {
                        $sDbIdName = 'user_id';
                        $sDatabase = $sDbAccessGroupToUser;
                    } elseif ($sObjectType === 'role') {
                        $sDbIdName = 'role_name';
                        $sDatabase = $sDbAccessGroupToRole;
                    } else {
                        continue;
                    }

                    $sFullDatabase = $sDatabase.$sAddition;

                    $sQuery = "SELECT {$sDbIdName} AS id, group_id AS groupId
                        FROM {$sFullDatabase}";

                    $aDbObjects = (array)$this->Database->getResults($sQuery);

                    foreach ($aDbObjects as $DbObject) {
                        $this->Database->insert(
                            $sDbAccessGroupToObject,
                            [
                                'group_id' => $DbObject->groupId,
                                'object_id' => $DbObject->id,
                                'object_type' => $sObjectType,
                            ],
                            [
                                '%d',
                                '%d',
                                '%s',
                            ]
                        );
                    }
                }

                $sDropQuery = "DROP TABLE {$sDbAccessGroupToPost},
                    {$sDbAccessGroupToUser},
                    {$sDbAccessGroupToCategory},
                    {$sDbAccessGroupToRole}";

                $this->Database->query($sDropQuery);
            }

            $sDbAccessGroupToObject = $this->Database->getUserGroupToObjectTable();

            if (version_compare($sCurrentDbVersion, '1.2', '<=') === true) {
                $sQuery = "
                    ALTER TABLE `{$sDbAccessGroupToObject}`
                    CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
                    CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

                $this->Database->query($sQuery);
            }

            if (version_compare($sCurrentDbVersion, '1.3', '<=') === true) {
                $sGeneralTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
                $this->Database->update(
                    $sDbAccessGroupToObject,
                    [
                        'object_type' => $sGeneralTermType,
                    ],
                    [
                        'object_type' => 'category',
                    ]
                );
            }

            if (version_compare($sCurrentDbVersion, '1.4', '<=') === true) {
                $sAlterQuery = "ALTER TABLE {$sDbAccessGroupToObject}
                    ADD general_object_type VARCHAR(64) NOT NULL AFTER object_id";

                $this->Database->query($sAlterQuery);

                // Update post entries
                $sGeneralPostType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;

                $sQuery = "UPDATE {$sDbAccessGroupToObject}
                    SET general_object_type = '{$sGeneralPostType}'
                    WHERE object_type IN ('post', 'page', 'attachment')";

                $this->Database->query($sQuery);

                // Update role entries
                $sGeneralRoleType = ObjectHandler::GENERAL_ROLE_OBJECT_TYPE;

                $sQuery = "UPDATE {$sDbAccessGroupToObject}
                    SET general_object_type = '{$sGeneralRoleType}'
                    WHERE object_type = 'role'";

                $this->Database->query($sQuery);

                // Update user entries
                $sGeneralUserType = ObjectHandler::GENERAL_USER_OBJECT_TYPE;

                $sQuery = "UPDATE {$sDbAccessGroupToObject}
                    SET general_object_type = '{$sGeneralUserType}'
                    WHERE object_type = 'user'";

                $this->Database->query($sQuery);

                // Update term entries
                $sGeneralTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;

                $sQuery = "UPDATE {$sDbAccessGroupToObject}
                    SET general_object_type = '{$sGeneralTermType}'
                    WHERE object_type = 'term'";

                $this->Database->query($sQuery);

                $sQuery = "UPDATE {$sDbAccessGroupToObject} AS gto
                    LEFT JOIN {$this->Database->getTermTaxonomyTable()} AS tt 
                      ON gto.object_id = tt.term_id
                    SET gto.object_type = tt.taxonomy
                    WHERE gto.general_object_type = '{$sGeneralTermType}'";

                $this->Database->query($sQuery);
            }

            $this->Wordpress->updateOption('uam_db_version', UserAccessManager::DB_VERSION);
        }

        return true;
    }

    /**
     * Clean up wordpress if the plugin will be uninstalled.
     */
    public function uninstall()
    {
        $aBlogIds = $this->getBlogIds();

        foreach ($aBlogIds as $iBlogId) {
            $this->Wordpress->switchToBlog($iBlogId);
            $sUserGroupTable = $this->Database->getUserGroupTable();
            $sUserGroupToObjectTable = $this->Database->getUserGroupToObjectTable();

            $sDropQuery = "DROP TABLE {$sUserGroupTable}, {$sUserGroupToObjectTable}";
            $this->Database->query($sDropQuery);

            $this->Wordpress->deleteOption(Config::ADMIN_OPTIONS_NAME);
            $this->Wordpress->deleteOption('uam_version');
            $this->Wordpress->deleteOption('uam_db_version');
        }

        $this->FileHandler->deleteFileProtection();
    }

    /**
     * Remove the htaccess file if the plugin is deactivated.
     *
     * @return bool
     */
    public function deactivate()
    {
        return $this->FileHandler->deleteFileProtection();
    }
}
