<?php
/**
 * UserAccessManager.php
 *
 * The UserAccessManager class file.
 *
 * PHP versions 5
 *
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\Config;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileProtectionFactory;
use UserAccessManager\FileHandler\NginxFileProtection;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Wordpress;

/**
 * The user user access manager class.
 *
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */
class UserAccessManager
{
    const VERSION = '1.2.14';
    const DB_VERSION = '1.4';

    /**
     * names of style and script handles
     */
    const HANDLE_STYLE_ADMIN = 'UserAccessManagerAdmin';
    const HANDLE_STYLE_LOGIN_FORM = 'UserAccessManagerLoginForm';
    const HANDLE_SCRIPT_ADMIN = 'UserAccessManagerFunctions';

    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * @var FileHandler
     */
    protected $_oFileHandler;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * @var ControllerFactory
     */
    protected $_oControllerFactory;

    /**
     * @var FileProtectionFactory
     */
    protected $_oFileProtectionFactory;

    // TODO move
    protected $_aPostUrls = array();

    /**
     * UserAccessManager constructor.
     *
     * @param Wordpress             $oWrapper
     * @param Config                $oConfig
     * @param Database              $oDatabase
     * @param ObjectHandler         $oObjectHandler
     * @param AccessHandler         $oAccessHandler
     * @param FileHandler           $oFileHandler
     * @param Util                  $oUtil
     * @param ControllerFactory     $oControllerFactory,
     * @param FileProtectionFactory $oFileProtectionFactory
     */
    public function __construct(
        Wordpress $oWrapper,
        Config $oConfig,
        Database $oDatabase,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        FileHandler $oFileHandler,
        Util $oUtil,
        ControllerFactory $oControllerFactory,
        FileProtectionFactory $oFileProtectionFactory
    )
    {
        $this->_oWrapper = $oWrapper;
        $this->_oConfig = $oConfig;
        $this->_oDatabase = $oDatabase;
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oUtil = $oUtil;
        $this->_oFileHandler = $oFileHandler;
        $this->_oControllerFactory = $oControllerFactory;
        $this->_oFileProtectionFactory = $oFileProtectionFactory;
    }

    /**
     * Register the admin actions and filters
     */
    public function registerAdminActionsAndFilters()
    {
        if (ini_get('safe_mode') && $this->_oConfig->getDownloadType() === 'fopen') {
            $this->_oWrapper->addAction(
                'admin_notices',
                create_function(
                    '',
                    'echo \'<div id="message" class="error"><p><strong>'.
                    TXT_UAM_FOPEN_WITHOUT_SAVEMODE_OFF.
                    '</strong></p></div>\';'
                )
            );
        }
        
        if ($this->isDatabaseUpdateNecessary()) {
            $sLink = 'admin.php?page=uam_setup';

            $this->_oWrapper->addAction(
                'admin_notices',
                create_function(
                    '',
                    'echo \'<div id="message" class="error"><p><strong>'.
                    sprintf(TXT_UAM_NEED_DATABASE_UPDATE, $sLink).
                    '</strong></p></div>\';'
                )
            );
        }

        $aTaxonomies = $this->_oObjectHandler->getPostTypes();

        if (isset($_POST['taxonomy'])) {
            $aTaxonomies[$_POST['taxonomy']] = $_POST['taxonomy'];
        } elseif (isset($_GET['taxonomy'])) {
            $aTaxonomies[$_GET['taxonomy']] = $_GET['taxonomy'];
        }

        $oAdminObjectController = $this->_oControllerFactory->createAdminObjectController();

        if ($this->_oAccessHandler->checkUserAccess() 
            || $this->_oConfig->authorsCanAddPostsToGroups() === true
        ) {
            //Admin actions
            $this->_oWrapper->addAction('manage_posts_custom_column', array($oAdminObjectController, 'addPostColumn'), 10, 2);
            $this->_oWrapper->addAction('manage_pages_custom_column', array($oAdminObjectController, 'addPostColumn'), 10, 2);
            $this->_oWrapper->addAction('save_post', array($oAdminObjectController, 'savePostData'));
            $this->_oWrapper->addAction('edit_user_profile', array($oAdminObjectController, 'showUserProfile'));
            $this->_oWrapper->addAction('profile_update', array($oAdminObjectController, 'saveUserData'));

            $this->_oWrapper->addAction('bulk_edit_custom_box', array($oAdminObjectController, 'addBulkAction'));
            $this->_oWrapper->addAction('create_term', array($oAdminObjectController, 'saveTermData'));
            $this->_oWrapper->addAction('edit_term', array($oAdminObjectController, 'saveTermData'));

            //Taxonomies
            foreach ($aTaxonomies as $sTaxonomy) {
                $this->_oWrapper->addAction('manage_'.$sTaxonomy.'_custom_column', array($oAdminObjectController, 'addTermColumn'), 10, 3);
                $this->_oWrapper->addAction($sTaxonomy.'_add_form_fields', array($oAdminObjectController, 'showTermEditForm'));
                $this->_oWrapper->addAction($sTaxonomy.'_edit_form_fields', array($oAdminObjectController, 'showTermEditForm'));
            }

            if ($this->_oConfig->lockFile() === true) {
                $this->_oWrapper->addAction('manage_media_custom_column', array($oAdminObjectController, 'addPostColumn'), 10, 2);
                $this->_oWrapper->addAction('media_meta', array($oAdminObjectController, 'showMediaFile'), 10, 2);
            }

            //Admin filters
            //The filter we use instead of add|edit_attachment action, reason see top
            $this->_oWrapper->addFilter('attachment_fields_to_save', array($oAdminObjectController, 'saveAttachmentData'));

            $this->_oWrapper->addFilter('manage_posts_columns', array($oAdminObjectController, 'addPostColumnsHeader'));
            $this->_oWrapper->addFilter('manage_pages_columns', array($oAdminObjectController, 'addPostColumnsHeader'));

            $this->_oWrapper->addFilter('manage_users_columns', array($oAdminObjectController, 'addUserColumnsHeader'), 10);
            $this->_oWrapper->addFilter('manage_users_custom_column', array($oAdminObjectController, 'addUserColumn'), 10, 3);

            foreach ($aTaxonomies as $sTaxonomy) {
                $this->_oWrapper->addFilter('manage_edit-'.$sTaxonomy.'_columns', array($oAdminObjectController, 'addTermColumnsHeader'));
            }

            if ($this->_oConfig->lockFile() === true) {
                $this->_oWrapper->addFilter('manage_media_columns', array($oAdminObjectController, 'addPostColumnsHeader'));
            }
        }

        //Clean up at deleting should always be done.
        $this->_oWrapper->addAction('update_option_permalink_structure', array($oAdminObjectController, 'updatePermalink'));
        $this->_oWrapper->addAction('wp_dashboard_setup', array($oAdminObjectController, 'setupAdminDashboard'));
        $this->_oWrapper->addAction('delete_post', array($oAdminObjectController, 'removePostData'));
        $this->_oWrapper->addAction('delete_attachment', array($oAdminObjectController, 'removePostData'));
        $this->_oWrapper->addAction('delete_user', array($oAdminObjectController, 'removeUserData'));
        $this->_oWrapper->addAction('delete_term', array($oAdminObjectController, 'removeTermData'));

        if ($this->_oAccessHandler->checkUserAccess()
            || $this->_oConfig->authorsCanAddPostsToGroups() === true
        ) {
            //Admin meta boxes
            $aPostableTypes = $this->_oObjectHandler->getPostableTypes();

            foreach ($aPostableTypes as $sPostableType) {
                // there is no need for a metabox for attachments if files are locked
                if ($sPostableType === 'attachment' && $this->_oConfig->lockFile() !== true) {
                    continue;
                }

                $this->_oWrapper->addMetaBox(
                    'uma_post_access',
                    __('Access', 'user-access-manager'),
                    array($oAdminObjectController, 'editPostContent'),
                    $sPostableType,
                    'side'
                );
            }
        }

        $oAdminObjectController->noRightsToEditContent();
    }

    /**
     * Resister the administration menu.
     */
    public function registerAdminMenu()
    {
        if ($this->_oAccessHandler->checkUserAccess()) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access. Capabilities seems
             * to be the right way, but it is way difficult.
             */
            //Admin main menu
            $this->_oWrapper->addMenuPage('User Access Manager', 'UAM', 'manage_options', 'uam_user_group', null, 'div');

            //Admin sub menus
            $oAdminUserGroupController = $this->_oControllerFactory->createAdminUserGroupController();
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_MANAGE_GROUP,
                TXT_UAM_MANAGE_GROUP,
                'read',
                'uam_user_group',
                array($oAdminUserGroupController, 'render')
            );

            $oAdminSetupController = $this->_oControllerFactory->createAdminSettingController();
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETTINGS,
                TXT_UAM_SETTINGS,
                'read',
                'uam_settings',
                array($oAdminSetupController, 'render')
            );

            $oAdminSetupController = $this->_oControllerFactory->createAdminSetupController($this);
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETUP,
                TXT_UAM_SETUP,
                'read',
                'uam_setup',
                array($oAdminSetupController, 'render')
            );

            $oAdminAboutController = $this->_oControllerFactory->createAdminAboutController();
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_ABOUT,
                TXT_UAM_ABOUT,
                'read',
                'uam_about',
                array($oAdminAboutController, 'render')
            );

            $this->_oWrapper->doAction('uam_add_submenu');

            /**
             * --- EOF ---
             */
        }
    }
    
    /**
     * Returns all blog of the network.
     *
     * @return array()
     */
    protected function _getBlogIds()
    {
        $aBlogIds = array();

        if ($this->_oWrapper->isMultiSite()) {
            $aBlogIds = $this->_oDatabase->getColumn(
                "SELECT blog_id
                FROM ".$this->_oDatabase->getBlogsTable()
            );
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
        $aBlogIds = $this->_getBlogIds();

        if ($blNetworkWide === true) {
            $iCurrentBlogId = $this->_oDatabase->getCurrentBlogId();

            foreach ($aBlogIds as $iBlogId) {
                $this->_oWrapper->switchToBlog($iBlogId);
                $this->_installUam();
            }

            $this->_oWrapper->switchToBlog($iCurrentBlogId);
        } else {
            $this->_installUam();
        }
    }

    /**
     * Creates the needed tables at the database and adds the options
     */
    protected function _installUam()
    {
        include_once ABSPATH.'wp-admin/includes/upgrade.php';

        $sCharsetCollate = $this->_oDatabase->getCharset();
        $sDbAccessGroupTable = $this->_oDatabase->getUserGroupTable();

        $sDbUserGroup = $this->_oDatabase->getVariable(
            "SHOW TABLES 
            LIKE '{$sDbAccessGroupTable}'"
        );

        if ($sDbUserGroup !== $sDbAccessGroupTable) {
            $this->_oDatabase->dbDelta(
                "CREATE TABLE {$sDbAccessGroupTable} (
                    ID int(11) NOT NULL auto_increment,
                    groupname tinytext NOT NULL,
                    groupdesc text NOT NULL,
                    read_access tinytext NOT NULL,
                    write_access tinytext NOT NULL,
                    ip_range mediumtext NULL,
                    PRIMARY KEY (ID)
                ) {$sCharsetCollate};"
            );
        }

        $sDbAccessGroupToObjectTable = $this->_oDatabase->getUserGroupToObjectTable();

        $sDbAccessGroupToObject = $this->_oDatabase->getVariable(
            "SHOW TABLES 
            LIKE '".$sDbAccessGroupToObjectTable."'"
        );

        if ($sDbAccessGroupToObject !== $sDbAccessGroupToObjectTable) {
            $this->_oDatabase->dbDelta(
                "CREATE TABLE {$sDbAccessGroupToObjectTable} (
                    object_id VARCHAR(64) NOT NULL,
                    object_type varchar(64) NOT NULL,
                    group_id int(11) NOT NULL,
                    PRIMARY KEY (object_id,object_type,group_id)
                ) {$sCharsetCollate};"
            );
        }

        $this->_oWrapper->addOption('uam_db_version', self::DB_VERSION);
    }

    /**
     * Checks if a database update is necessary.
     *
     * @return boolean
     */
    public function isDatabaseUpdateNecessary()
    {
        $aBlogIds = $this->_getBlogIds();

        if ($aBlogIds !== array()
            && $this->_oWrapper->isSuperAdmin()
        ) {
            foreach ($aBlogIds as $iBlogId) {
                $sTable = $this->_oDatabase->getBlogPrefix($iBlogId).'options';
                $sSelect = "SELECT option_value FROM {$sTable} WHERE option_name = %s LIMIT 1";
                $sSelect = $this->_oDatabase->prepare($sSelect, 'uam_db_version');
                $sCurrentDbVersion = $this->_oDatabase->getVariable($sSelect);

                if (version_compare($sCurrentDbVersion, self::DB_VERSION, '<')) {
                    return true;
                }
            }
        }

        $sCurrentDbVersion = $this->_oWrapper->getOption('uam_db_version');
        return version_compare($sCurrentDbVersion, self::DB_VERSION, '<');
    }

    /**
     * Updates the user access manager if an old version was installed.
     *
     * @param boolean $blNetworkWide If true update network wide
     */
    public function update($blNetworkWide)
    {
        $aBlogIds = $this->_getBlogIds();

        if ($blNetworkWide
            && $aBlogIds !== array()
        ) {
            $iCurrentBlogId = $this->_oDatabase->getCurrentBlogId();

            foreach ($aBlogIds as $iBlogId) {
                $this->_oWrapper->switchToBlog($iBlogId);
                $this->_installUam();
                $this->_updateUam();
            }

            $this->_oWrapper->switchToBlog($iCurrentBlogId);
        } else {
            $this->_updateUam();
        }
    }

    /**
     * Updates the user access manager if an old version was installed.
     */
    protected function _updateUam()
    {
        $sCurrentDbVersion = $this->_oWrapper->getOption('uam_db_version');

        if (empty($sCurrentDbVersion)) {
            $this->install();
        }

        $sUamVersion = $this->_oWrapper->getOption('uam_version');

        if (!$sUamVersion || version_compare($sUamVersion, "1.0", '<')) {
            $this->_oWrapper->deleteOption('allow_comments_locked');
        }

        $sDbAccessGroup = $this->_oDatabase->getUserGroupTable();

        $sDbUserGroup = $this->_oDatabase->getVariable(
            "SHOW TABLES LIKE '{$sDbAccessGroup}'"
        );

        if (version_compare($sCurrentDbVersion, self::DB_VERSION, '<')) {
            $sPrefix = $this->_oDatabase->getPrefix();
            $sCharsetCollate = $this->_oDatabase->getCharset();

            if (version_compare($sCurrentDbVersion, '1.0', '<=')) {
                if ($sDbUserGroup == $sDbAccessGroup) {
                    $sAlterQuery = "ALTER TABLE {$sDbAccessGroup}
                        ADD read_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD write_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD ip_range MEDIUMTEXT NULL DEFAULT ''";
                    $this->_oDatabase->query($sAlterQuery);

                    $sUpdateQuery = "UPDATE {$sDbAccessGroup}
                        SET read_access = 'group', write_access = 'group'";
                    $this->_oDatabase->query($sUpdateQuery);

                    $sSelectQuery = "SHOW columns FROM {$sDbAccessGroup} LIKE 'ip_range'";
                    $sDbIpRange = $this->_oDatabase->getVariable($sSelectQuery);

                    if ($sDbIpRange != 'ip_range') {
                        $sAlterQuery = "ALTER TABLE ".$sDbAccessGroup."
                            ADD ip_range MEDIUMTEXT NULL DEFAULT ''";
                        $this->_oDatabase->query($sAlterQuery);
                    }
                }

                $sDbAccessGroupToObject = $sPrefix.'uam_accessgroup_to_object';
                $sDbAccessGroupToPost = $sPrefix.'uam_accessgroup_to_post';
                $sDbAccessGroupToUser = $sPrefix.'uam_accessgroup_to_user';
                $sDbAccessGroupToCategory = $sPrefix.'uam_accessgroup_to_category';
                $sDbAccessGroupToRole = $sPrefix.'uam_accessgroup_to_role';

                $sAlterQuery = "ALTER TABLE '{$sDbAccessGroupToObject}'
                    CHANGE 'object_id' 'object_id' VARCHAR(64) {$sCharsetCollate}";
                $this->_oDatabase->query($sAlterQuery);

                $aObjectTypes = $this->_oObjectHandler->getObjectTypes();

                foreach ($aObjectTypes as $sObjectType) {
                    $sAddition = '';

                    if ($this->_oObjectHandler->isPostableType($sObjectType)) {
                        $sDbIdName = 'post_id';
                        $sDatabase = $sDbAccessGroupToPost.', '.$this->_oDatabase->getPostsTable();
                        $sAddition = " WHERE post_id = ID
                            AND post_type = '".$sObjectType."'";
                    } elseif ($sObjectType == 'category') {
                        $sDbIdName = 'category_id';
                        $sDatabase = $sDbAccessGroupToCategory;
                    } elseif ($sObjectType == 'user') {
                        $sDbIdName = 'user_id';
                        $sDatabase = $sDbAccessGroupToUser;
                    } elseif ($sObjectType == 'role') {
                        $sDbIdName = 'role_name';
                        $sDatabase = $sDbAccessGroupToRole;
                    } else {
                        continue;
                    }

                    $sFullDatabase = $sDatabase.$sAddition;

                    $sSql = "SELECT {$sDbIdName} as id, group_id as groupId
                        FROM {$sFullDatabase}";

                    $aDbObjects = $this->_oDatabase->getResults($sSql);

                    foreach ($aDbObjects as $oDbObject) {
                        $this->_oDatabase->insert(
                            $sDbAccessGroupToObject,
                            array(
                                'group_id' => $oDbObject->groupId,
                                'object_id' => $oDbObject->id,
                                'object_type' => $sObjectType,
                            ),
                            array(
                                '%d',
                                '%d',
                                '%s',
                            )
                        );
                    }
                }

                $sDropQuery = "DROP TABLE {$sDbAccessGroupToPost},
                    {$sDbAccessGroupToUser},
                    {$sDbAccessGroupToCategory},
                    {$sDbAccessGroupToRole}";

                $this->_oDatabase->query($sDropQuery);
            }

            if (version_compare($sCurrentDbVersion, '1.2', '<=')) {
                $sDbAccessGroupToObject = $this->_oDatabase->getUserGroupToObjectTable();

                $sSql = "
                    ALTER TABLE `{$sDbAccessGroupToObject}`
                    CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
                    CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

                $this->_oDatabase->query($sSql);
            }

            if (version_compare($sCurrentDbVersion, '1.3', '<=')) {
                $sDbAccessGroupToObject = $this->_oDatabase->getUserGroupToObjectTable();
                $sTermType = ObjectHandler::TERM_OBJECT_TYPE;
                $this->_oDatabase->update(
                    $sDbAccessGroupToObject,
                    array(
                        'object_type' => $sTermType,
                    ),
                    array(
                        'object_type' => 'category',
                    )
                );
            }

            $this->_oWrapper->updateOption('uam_db_version', self::DB_VERSION);
        }
    }

    /**
     * TODO
     * Clean up wordpress if the plugin will be uninstalled.
     *
     * @param bool $blNetworkWide
     */
    public static function uninstall($blNetworkWide = false)
    {
        global $wpdb;

        $aSites = get_sites();

        foreach ($aSites as $oSite) {
            switch_to_blog($oSite->blog_id);
            $sPrefix = $wpdb->prefix;
            $sUserGroupTable = $sPrefix.Database::USER_GROUP_TABLE_NAME;
            $sUserGroupToObjectTable = $sPrefix.Database::USER_GROUP_TO_OBJECT_TABLE_NAME;

            $sDropQuery = "DROP TABLE {$sUserGroupTable}, {$sUserGroupToObjectTable}";
            $wpdb->query($sDropQuery);

            delete_option(Config::ADMIN_OPTIONS_NAME);
            delete_option(Config::ADMIN_OPTIONS_NAME);
            delete_option('uam_version');
            delete_option('uam_db_version');
        }

        //TODO
        $sDir = '';
        $sNginxFileName = $sDir.NginxFileProtection::FILE_NAME;

        if (file_exists($sNginxFileName)) {
            unlink($sNginxFileName);
        }

        $sPasswordFile = $sDir.NginxFileProtection::PASSWORD_FILE_NAME;

        if (file_exists($sPasswordFile)) {
            unlink($sPasswordFile);
        }
    }

    /**
     * Remove the htaccess file if the plugin is deactivated.
     */
    public function deactivate()
    {
        $this->deleteFileProtectionFiles();
    }


    /*
     * Functions for the admin panel content.
     */

    /**
     * Register styles and scripts with handle for admin panel.
     */
    protected function registerAdminStylesAndScripts()
    {
        //TODO wrapper
        wp_register_style(
            self::HANDLE_STYLE_ADMIN,
            UAM_URLPATH.'assets/css/uamAdmin.css',
            array(),
            self::VERSION,
            'screen'
        );
        
        wp_register_script(
            self::HANDLE_SCRIPT_ADMIN,
            UAM_URLPATH.'assets/js/functions.js',
            array('jquery'),
            self::VERSION
        );
    }

    /**
     * The function for the admin_enqueue_scripts action for styles and scripts.
     *
     * @param string $sHook
     */
    public function enqueueAdminStylesAndScripts($sHook)
    {
        $this->registerAdminStylesAndScripts();
        wp_enqueue_style(self::HANDLE_STYLE_ADMIN);

        if ($sHook === 'uam_page_uam_settings' || $sHook === 'uam_page_uam_setup') {
            wp_enqueue_script(self::HANDLE_SCRIPT_ADMIN);
        }
    }

    /**
     * Functions for other content.
     */

    /**
     * Register all other styles.
     */
    protected function registerStylesAndScripts()
    {
        wp_register_style(
            self::HANDLE_STYLE_LOGIN_FORM,
            UAM_URLPATH.'assets/css/uamLoginForm.css',
            array(),
            self::VERSION,
            'screen'
        );
    }

    /**
     * The function for the wp_enqueue_scripts action.
     */
    public function enqueueStylesAndScripts()
    {
        $this->registerStylesAndScripts();
        wp_enqueue_style(self::HANDLE_STYLE_LOGIN_FORM);
    }



    /**
     * The function for the wp_dashboard_setup action.
     * Removes widgets to which a user should not have access.
     */
    public function setupAdminDashboard()
    {
        global $wp_meta_boxes;

        if (!$this->_oAccessHandler->checkUserAccess('manage_user_groups')) {
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
        }
    }

    /**
     * The function for the update_option_permalink_structure action.
     */
    public function updatePermalink()
    {
        $this->createFileProtection();
    }


    /*
     * Functions for the pluggable object actions.
     */

    /**
     * The function for the pluggable save action.
     *
     * @param string  $sObjectType The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object id.
     * @param array   $aUserGroups The user groups for the object.
     */
    public function savePlObjectData($sObjectType, $iObjectId, $aUserGroups = null)
    {
        $this->_saveObjectData($sObjectType, $iObjectId, $aUserGroups);
    }

    /**
     * The function for the pluggable remove action.
     *
     * @param string  $sObjectName The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object id.
     */
    public function removePlObjectData($sObjectName, $iObjectId)
    {
        $this->_removeObjectData($sObjectName, $iObjectId);
    }

    /**
     * Returns the group selection form for pluggable objects.
     *
     * @param string  $sObjectType     The object type.
     * @param integer $iObjectId       The _iId of the object.
     * @param string  $aGroupsFormName The name of the form which contains the groups.
     *
     * @return string;
     */
    public function showPlGroupSelectionForm($sObjectType, $iObjectId, $aGroupsFormName = null)
    {
        $sFileName = UAM_REALPATH.'tpl/groupSelectionForm.php';
        $aUamUserGroups = $this->_oAccessHandler->getUserGroups();
        $aUserGroupsForObject = $this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId);

        if (is_file($sFileName)) {
            ob_start();
            include $sFileName;
            $sContents = ob_get_contents();
            ob_end_clean();

            return $sContents;
        }

        return '';
    }

    /**
     * Returns the column for a pluggable object.
     *
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object id.
     *
     * @return string
     */
    public function getPlColumn($sObjectType, $iObjectId)
    {
        return $this->getIncludeContents(UAM_REALPATH.'tpl/objectColumn.php', $iObjectId, $sObjectType);
    }


    /*
     * Functions for the blog content.
     */

    /**
     * Manipulates the wordpress query object to filter content.
     *
     * @param object $oWpQuery The wordpress query object.
     */
    public function parseQuery($oWpQuery)
    {
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();
        $aAllExcludedPosts = $aExcludedPosts['all'];

        if (count($aAllExcludedPosts) > 0) {
            $oWpQuery->query_vars['post__not_in'] = array_merge(
                $oWpQuery->query_vars['post__not_in'],
                $aAllExcludedPosts
            );
        }
    }

    /**
     * Modifies the content of the post by the given settings.
     *
     * @param object $oPost The current post.
     *
     * @return object|null
     */
    protected function _processPost($oPost)
    {
        $sPostType = $oPost->post_type;

        if ($this->_oObjectHandler->isPostableType($sPostType)
            && $sPostType != ObjectHandler::POST_OBJECT_TYPE
            && $sPostType != ObjectHandler::PAGE_OBJECT_TYPE
        ) {
            $sPostType = ObjectHandler::POST_OBJECT_TYPE;
        } elseif ($sPostType != ObjectHandler::POST_OBJECT_TYPE
            && $sPostType != ObjectHandler::PAGE_OBJECT_TYPE
        ) {
            return $oPost;
        }

        if ($this->_oConfig->hideObjectType($sPostType) === true || $this->_oConfig->atAdminPanel()) {
            if ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->post_title .= $this->adminOutput($oPost->post_type, $oPost->ID);
                return $oPost;
            }
        } else {
            if (!$this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->isLocked = true;

                $sUamPostContent = $this->_oConfig->getObjectTypeContent($sPostType);
                $sUamPostContent = str_replace('[LOGIN_FORM]', $this->getLoginBarHtml(), $sUamPostContent);

                if ($this->_oConfig->hideObjectTypeTitle($sPostType) === true) {
                    $oPost->post_title = $this->_oConfig->getObjectTypeTitle($sPostType);
                }

                if ($this->_oConfig->hideObjectTypeComments($sPostType) === false) {
                    $oPost->comment_status = 'close';
                }

                if ($sPostType === 'post'
                    && $this->_oConfig->showPostContentBeforeMore() === true
                    && preg_match('/<!--more(.*?)?-->/', $oPost->post_content, $aMatches)
                ) {
                    $oPost->post_content = explode($aMatches[0], $oPost->post_content, 2);
                    $sUamPostContent = $oPost->post_content[0]." ".$sUamPostContent;
                }

                $oPost->post_content = stripslashes($sUamPostContent);
            }

            $oPost->post_title .= $this->adminOutput($oPost->post_type, $oPost->ID);

            return $oPost;
        }

        return null;
    }

    /**
     * The function for the the_posts filter.
     *
     * @param array $aPosts The posts.
     *
     * @return array
     */
    public function showPosts($aPosts = array())
    {
        $aShowPosts = array();
        
        if ($this->_oWrapper->isFeed() === false
            || ($this->_oConfig->protectFeed() === true && $this->_oWrapper->isFeed()) === true
        ) {
            foreach ($aPosts as $iPostId) {
                if ($iPostId !== null) {
                    $oPost = $this->_processPost($iPostId);

                    if ($oPost !== null) {
                        $aShowPosts[] = $oPost;
                    }
                }
            }

            $aPosts = $aShowPosts;
        }

        return $aPosts;
    }

    /**
     * The function for the posts_where_paged filter.
     *
     * @param string $sSql The where sql statement.
     *
     * @return string
     */
    public function showPostSql($sSql)
    {
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();
        $aAllExcludedPosts = $aExcludedPosts['all'];

        if (count($aAllExcludedPosts) > 0) {
            $sExcludedPostsStr = implode(',', $aAllExcludedPosts);
            $sSql .= " AND {$this->_oDatabase->getPostsTable()}.ID NOT IN($sExcludedPostsStr) ";
        }

        return $sSql;
    }

    /**
     * Function for the wp_count_posts filter.
     *
     * @param \stdClass $oCounts
     * @param string   $sType
     *
     * @return \stdClass
     */
    public function showPostCount($oCounts, $sType)
    {
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();

        if (isset($aExcludedPosts[$sType])) {
            $oCounts->publish -= count($aExcludedPosts[$sType]);
        }

        return $oCounts;
    }

    /**
     * Sets the excluded terms as argument.
     *
     * @param array $aArguments
     *
     * @return array
     */
    public function getTermArguments($aArguments)
    {
        $aExclude = (isset($aArguments['exclude'])) ? $this->_oWrapper->parseIdList($aArguments['exclude']) : array();
        $aExcludedTerms = $this->_oAccessHandler->getExcludedTerms();

        if ($this->_oConfig->lockRecursive() === true) {
            $aTermTreeMap = $this->_oObjectHandler->getTermTreeMap();

            foreach ($aExcludedTerms as $sTermId) {
                if (isset($aTermTreeMap[$sTermId])) {
                    $aExcludedTerms = array_merge($aExcludedTerms, array_keys($aTermTreeMap[$sTermId]));
                }
            }
        }

        $aArguments['exclude'] = array_merge($aExclude, $aExcludedTerms);

        return $aArguments;
    }

    /**
     * The function for the wp_get_nav_menu_items filter.
     *
     * @param array $aItems The menu item.
     *
     * @return array
     */
    public function showCustomMenu($aItems)
    {
        $aShowItems = array();
        $aTaxonomies = $this->_oObjectHandler->getTaxonomies();

        foreach ($aItems as $oItem) {
            if ($oItem->object == ObjectHandler::POST_OBJECT_TYPE
                || $oItem->object == ObjectHandler::PAGE_OBJECT_TYPE
            ) {
                $oObject = $this->_oObjectHandler->getPost($oItem->object_id);

                if ($oObject !== null) {
                    $oPost = $this->_processPost($oObject);

                    if ($oPost !== null) {
                        if (isset($oPost->isLocked)) {
                            $oItem->title = $oPost->post_title;
                        }

                        $oItem->title .= $this->adminOutput($oItem->object, $oItem->object_id);
                        $aShowItems[] = $oItem;
                    }
                }
            } elseif (isset($aTaxonomies[$oItem->object])) {
                $oObject = $this->_oObjectHandler->getTerm($oItem->object_id);
                $oCategory = $this->_processTerm($oObject);

                if ($oCategory !== null && !$oCategory->isEmpty) {
                    $oItem->title .= $this->adminOutput($oItem->object, $oItem->object_id);
                    $aShowItems[] = $oItem;
                }
            } else {
                $aShowItems[] = $oItem;
            }
        }

        return $aShowItems;
    }

    /**
     * The function for the comments_array filter.
     *
     * @param array $aComments The comments.
     *
     * @return array
     */
    public function showComment($aComments = array())
    {
        $aShowComments = array();

        foreach ($aComments as $oComment) {
            $oPost = $this->_oObjectHandler->getPost($oComment->comment_post_ID);
            $sPostType = $oPost->post_type;

            if ($this->_oConfig->hideObjectTypeComments($sPostType) === true
                || $this->_oConfig->hideObjectType($sPostType) === true
                || $this->_oConfig->atAdminPanel()
            ) {
                if ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $aShowComments[] = $oComment;
                }
            } else {
                if (!$this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $oComment->comment_content = $this->_oConfig->getObjectTypeCommentContent($sPostType);
                }

                $aShowComments[] = $oComment;
            }
        }

        $aComments = $aShowComments;

        return $aComments;
    }

    /**
     * The function for the get_pages filter.
     *
     * @param array $aPages The pages.
     *
     * @return array
     */
    public function showPages($aPages = array())
    {
        $aShowPages = array();

        foreach ($aPages as $oPage) {
            if ($this->_oConfig->hidePage() === true
                || $this->_oConfig->atAdminPanel()
            ) {
                if ($this->_oAccessHandler->checkObjectAccess($oPage->post_type, $oPage->ID)) {
                    $oPage->post_title .= $this->adminOutput(
                        $oPage->post_type,
                        $oPage->ID
                    );
                    $aShowPages[] = $oPage;
                }
            } else {
                if (!$this->_oAccessHandler->checkObjectAccess($oPage->post_type, $oPage->ID)) {
                    if ($this->_oConfig->hidePageTitle() === true) {
                        $oPage->post_title = $this->_oConfig->getPageTitle();
                    }

                    $oPage->post_content = $this->_oConfig->getPageContent();
                }

                $oPage->post_title .= $this->adminOutput($oPage->post_type, $oPage->ID);
                $aShowPages[] = $oPage;
            }
        }

        $aPages = $aShowPages;

        return $aPages;
    }

    /**
     * Returns the post count for the term.
     *
     * @param int $iTermId
     *
     * @return int
     */
    protected function _getVisibleElementsCount($iTermId)
    {
        $iCount = 0;
        $aTermPostMap = $this->_oObjectHandler->getTermPostMap();

        if (isset($aTermPostMap[$iTermId])) {
            foreach ($aTermPostMap[$iTermId] as $iPostId => $sPostType) {
                if ($this->_oConfig->hideObjectType($sPostType) === false
                    || $this->_oAccessHandler->checkObjectAccess($sPostType, $iPostId)
                ) {
                    $iCount++;
                }
            }
        }

        return $iCount;
    }

    /**
     * Modifies the content of the term by the given settings.
     *
     * @param object $oTerm The current term.
     *
     * @return object|null
     */
    protected function _processTerm($oTerm)
    {
        return $oTerm;
        if (is_object($oTerm) === false) {
            return $oTerm;
        }

        $oTerm->name .= $this->adminOutput(ObjectHandler::TERM_OBJECT_TYPE, $oTerm->term_id, $oTerm->name);

        $oTerm->isEmpty = false;

        if ($this->_oAccessHandler->checkObjectAccess(ObjectHandler::TERM_OBJECT_TYPE, $oTerm->term_id)) {
            if ($this->_oConfig->hidePost() === true || $this->_oConfig->hidePage() === true) {
                $iTermRequest = $oTerm->term_id;
                $oTerm->count = $this->_getVisibleElementsCount($iTermRequest);
                $iFullCount = $oTerm->count;

                if ($iFullCount <= 0) {
                    $aTermTreeMap = $this->_oObjectHandler->getTermTreeMap();

                    if (isset($aTermTreeMap[$iTermRequest])) {
                        foreach ($aTermTreeMap[$iTermRequest] as $iTermId => $sType) {
                            if ($oTerm->taxonomy === $sType) {
                                $iFullCount += $this->_getVisibleElementsCount($iTermId);

                                if ($iFullCount > 0) {
                                    break;
                                }
                            }
                        }
                    }
                }

                //For categories
                if ($iFullCount <= 0
                    && $this->_oConfig->atAdminPanel() === false
                    && $this->_oConfig->hideEmptyCategories() === true
                    && ($oTerm->taxonomy == 'term' || $oTerm->taxonomy == 'category')
                ) {
                    $oTerm->isEmpty = true;
                }

                if ($this->_oConfig->lockRecursive() === false) {
                    $oCurrentTerm = $oTerm;

                    while ($oCurrentTerm->parent != 0) {
                        $oCurrentTerm = $this->_oObjectHandler->getTerm($oCurrentTerm->parent);

                        if ($this->_oAccessHandler->checkObjectAccess(ObjectHandler::TERM_OBJECT_TYPE, $oCurrentTerm->term_id)) {
                            $oTerm->parent = $oCurrentTerm->term_id;
                            break;
                        }
                    }
                }
            }

            return $oTerm;
        }

        return null;
    }

    /**
     * The function for the get_ancestors filter.
     *
     * @param array  $aAncestors
     * @param int    $sObjectId
     * @param string $sObjectType
     * @param string $sResourceType
     *
     * @return array
     */
    public function showAncestors($aAncestors, $sObjectId, $sObjectType, $sResourceType)
    {
        if ($sResourceType === 'taxonomy') {
            foreach ($aAncestors as $sKey => $aAncestorId) {
                if (!$this->_oAccessHandler->checkObjectAccess(ObjectHandler::TERM_OBJECT_TYPE, $aAncestorId)) {
                    unset($aAncestors[$sKey]);
                }
            }
        }

        return $aAncestors;
    }

    /**
     * The function for the get_term filter.
     *
     * @param object $oTerm
     *
     * @return null|object
     */
    public function showTerm($oTerm)
    {
        return $this->_processTerm($oTerm);
    }

    /**
     * The function for the get_terms filter.
     *
     * @param array          $aTerms      The terms.
     * @param array          $aTaxonomies The taxonomies.
     * @param array          $aArgs       The given arguments.
     * @param \WP_Term_Query $oTermQuery  The term query.
     *
     * @return array
     */
    public function showTerms($aTerms = array(), $aTaxonomies = array(), $aArgs = array(), $oTermQuery = null)
    {
        $aShowTerms = array();

        foreach ($aTerms as $mTerm) {
            if (!is_object($mTerm) && is_numeric($mTerm)) {
                if ((int)$mTerm === 0) {
                    continue;
                }

                $mTerm = $this->_oObjectHandler->getTerm($mTerm);
            }

            $mTerm = $this->_processTerm($mTerm);

            if ($mTerm !== null && (!isset($mTerm->isEmpty) || !$mTerm->isEmpty)) {
                $aShowTerms[$mTerm->term_id] = $mTerm;
            }
        }

        foreach ($aTerms as $sKey => $mTerm) {
            if ($mTerm === null || is_object($mTerm) && !isset($aShowTerms[$mTerm->term_id])) {
                unset($aTerms[$sKey]);
            }
        }

        return $aTerms;
    }

    /**
     * The function for the get_previous_post_where and
     * the get_next_post_where filter.
     *
     * @param string $sSql The current sql string.
     *
     * @return string
     */
    public function showNextPreviousPost($sSql)
    {
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();
        $aAllExcludedPosts = $aExcludedPosts['all'];

        if (count($aAllExcludedPosts) > 0) {
            $sExcludedPosts = implode(',', $aAllExcludedPosts);
            $sSql .= " AND p.ID NOT IN({$sExcludedPosts}) ";
        }

        return $sSql;
    }

    /**
     * Returns the admin hint.
     *
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object id we want to check.
     * @param string  $sText       The text on which we want to append the hint.
     *
     * @return string
     */
    public function adminOutput($sObjectType, $iObjectId, $sText = null)
    {
        $sOutput = '';

        if ($this->_oConfig->atAdminPanel() === false
            && $this->_oConfig->blogAdminHint() === true
        ) {
            $sHintText = $this->_oConfig->getBlogAdminHintText();

            if ($sText !== null && $this->_oUtil->endsWith($sText, $sHintText)) {
                return $sOutput;
            }

            $oCurrentUser = $this->_oWrapper->getCurrentUser();

            if (!isset($oCurrentUser->user_level)) {
                return $sOutput;
            }

            if ($this->_oAccessHandler->userIsAdmin($oCurrentUser->ID)
                && count($this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId)) > 0
            ) {
                $sOutput .= $sHintText;
            }
        }


        return $sOutput;
    }

    /**
     * The function for the edit_post_link filter.
     *
     * @param string  $sLink   The edit link.
     * @param integer $iPostId The _iId of the post.
     *
     * @return string
     */
    public function showGroupMembership($sLink, $iPostId)
    {
        $aGroups = $this->_oAccessHandler->getUserGroupsForObject(ObjectHandler::POST_OBJECT_TYPE, $iPostId);

        if (count($aGroups) > 0) {
            $sLink .= ' | '.TXT_UAM_ASSIGNED_GROUPS.': ';

            foreach ($aGroups as $oGroup) {
                $sLink .= htmlentities($oGroup->getGroupName()).', ';
            }

            $sLink = rtrim($sLink, ', ');
        }

        return $sLink;
    }

    /**
     * Returns the login bar.
     *
     * @return string
     */
    public function getLoginBarHtml()
    {
        if ($this->_oWrapper->isUserLoggedIn() === false) {
            return $this->getIncludeContents(UAM_REALPATH.'tpl/loginBar.php');
        }

        return '';
    }


    /*
     * Functions for the redirection and files.
     */

    /**
     * Redirects to a page or to content.
     *
     * @param string $sHeaders    The headers which are given from wordpress.
     * @param object $oPageParams The params of the current page.
     *
     * @return string
     */
    public function redirect($sHeaders, $oPageParams)
    {
        if (isset($_GET['uamgetfile']) && isset($_GET['uamfiletype'])) {
            $sFileUrl = $_GET['uamgetfile'];
            $sFileType = $_GET['uamfiletype'];
            $this->getFile($sFileType, $sFileUrl);
        } elseif (!$this->_oConfig->atAdminPanel() && $this->_oConfig->getRedirect() !== 'false') {
            $oObject = null;

            if (isset($oPageParams->query_vars['p'])) {
                $oObject = $this->_oObjectHandler->getPost($oPageParams->query_vars['p']);
                $oObjectType = $oObject->post_type;
                $iObjectId = $oObject->ID;
            } elseif (isset($oPageParams->query_vars['page_id'])) {
                $oObject = $this->_oObjectHandler->getPost($oPageParams->query_vars['page_id']);
                $oObjectType = $oObject->post_type;
                $iObjectId = $oObject->ID;
            } elseif (isset($oPageParams->query_vars['cat_id'])) {
                $oObject = $this->_oObjectHandler->getTerm($oPageParams->query_vars['cat_id']);
                $oObjectType = ObjectHandler::TERM_OBJECT_TYPE;
                $iObjectId = $oObject->term_id;
            } elseif (isset($oPageParams->query_vars['name'])) {
                $sPostableTypes = "'".implode("','", $this->_oObjectHandler->getPostableTypes())."'";

                $sQuery = $this->_oDatabase->prepare(
                    "SELECT ID
                    FROM {$this->_oDatabase->getPostsTable()}
                    WHERE post_name = %s
                      AND post_type IN ({$sPostableTypes})",
                    $oPageParams->query_vars['name']
                );

                $sObjectId = $this->_oDatabase->getVariable($sQuery);

                if ($sObjectId) {
                    $oObject = $this->_oObjectHandler->getPost($sObjectId);
                }

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            } elseif (isset($oPageParams->query_vars['pagename'])) {
                $oObject = $this->_oWrapper->getPageByPath($oPageParams->query_vars['pagename']);

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            }

            if ($oObject !== null
                && isset($oObjectType)
                && isset($iObjectId)
                && !$this->_oAccessHandler->checkObjectAccess($oObjectType, $iObjectId)
            ) {
                $this->redirectUser($oObject);
            }
        }

        return $sHeaders;
    }

    /**
     * Redirects the user to his destination.
     *
     * @param object $oObject The current object we want to access.
     */
    public function redirectUser($oObject = null)
    {
        $blPostToShow = false;
        $aPosts = $this->_oWrapper->getWpQuery()->get_posts();

        if ($oObject === null && isset($aPosts)) {
            foreach ($aPosts as $oPost) {
                if ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $blPostToShow = true;
                    break;
                }
            }
        }

        if ($blPostToShow === false) {
            $sPermalink = null;

            if ($this->_oConfig->getRedirect() === 'custom_page') {
                $sRedirectCustomPage = $this->_oConfig->getRedirectCustomPage();
                $oPost = $this->_oObjectHandler->getPost($sRedirectCustomPage);
                $sUrl = $oPost->guid;
                $sPermalink = $this->_oWrapper->getPageLink($oPost);
            } elseif ($this->_oConfig->getRedirect() === 'custom_url') {
                $sUrl = $this->_oConfig->getRedirectCustomUrl();
            } else {
                $sUrl = $this->_oWrapper->getHomeUrl('/');
            }

            $sCurrentUrl = $this->_oUtil->getCurrentUrl();

            if ($sUrl != $sCurrentUrl && $sPermalink != $sCurrentUrl) {
                $this->_oWrapper->wpRedirect($sUrl);
                exit;
            }
        }
    }

    /**
     * Delivers the content of the requested file.
     *
     * @param string $sObjectType The type of the requested file.
     * @param string $sObjectUrl  The file url.
     *
     * @return null
     */
    public function getFile($sObjectType, $sObjectUrl)
    {
        $oObject = $this->_getFileSettingsByType($sObjectType, $sObjectUrl);

        if ($oObject === null) {
            return null;
        }

        $sFile = null;

        if ($this->_oAccessHandler->checkObjectAccess($oObject->type, $oObject->id)) {
            $sFile = $oObject->file;
        } elseif ($oObject->isImage) {
            $sFile = UAM_REALPATH.'gfx/noAccessPic.png';
        } else {
            $this->_oWrapper->wpDie(TXT_UAM_NO_RIGHTS);
        }

        $blIsImage = $oObject->isFile;

        $this->_oFileHandler->getFile($sFile, $blIsImage);
        return null;
    }

    /**
     * Returns the file object by the given type and url.
     *
     * @param string $sObjectType The type of the requested file.
     * @param string $sObjectUrl  The file url.
     *
     * @return object|null
     */
    protected function _getFileSettingsByType($sObjectType, $sObjectUrl)
    {
        $oObject = null;

        if ($sObjectType == ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            $aUploadDir = wp_upload_dir();
            $sUploadDir = str_replace(ABSPATH, '/', $aUploadDir['basedir']);
            $sRegex = '/.*'.str_replace('/', '\/', $sUploadDir).'\//i';
            $sCleanObjectUrl = preg_replace($sRegex, '', $sObjectUrl);
            $sUploadUrl = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
            $sObjectUrl = $sUploadUrl.'/'.ltrim($sCleanObjectUrl, '/');
            $oPost = $this->_oObjectHandler->getPost($this->getPostIdByUrl($sObjectUrl));

            if ($oPost !== null
                && $oPost->post_type == ObjectHandler::ATTACHMENT_OBJECT_TYPE
            ) {
                $oObject = new \stdClass();
                $oObject->id = $oPost->ID;
                $oObject->isImage = wp_attachment_is_image($oPost->ID);
                $oObject->type = $sObjectType;
                $sMultiPath = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
                $oObject->file = $aUploadDir['basedir'].str_replace($sMultiPath, '', $sObjectUrl);
            }
        } else {
            $aPlObject = $this->_oObjectHandler->getPlObject($sObjectType);

            if (isset($aPlObject) && isset($aPlObject['getFileObject'])) {
                $oObject = $aPlObject['reference']->{$aPlObject['getFileObject']}($sObjectUrl);
            }
        }

        return $oObject;
    }

    /**
     * Returns the url for a locked file.
     *
     * @param string  $sUrl The base url.
     * @param integer $iId  The _iId of the file.
     *
     * @return string
     */
    public function getFileUrl($sUrl, $iId)
    {
        if ($this->_oConfig->isPermalinksActive() === false && $this->_oConfig->lockFile() === true) {
            $oPost = $this->_oObjectHandler->getPost($iId);
            $aType = explode('/', $oPost->post_mime_type);
            $sType = $aType[1];
            $aFileTypes = explode(',', $this->_oConfig->getLockedFileTypes());

            if ($this->_oConfig->getLockedFileTypes() === 'all' || in_array($sType, $aFileTypes)) {
                $sUrl = $this->_oWrapper->getHomeUrl('/').'?uamfiletype=attachment&uamgetfile='.$sUrl;
            }
        }

        return $sUrl;
    }

    /**
     * Returns the post by the given url.
     *
     * @param string $sUrl The url of the post(attachment).
     *
     * @return object The post.
     */
    public function getPostIdByUrl($sUrl)
    {
        if (isset($this->_aPostUrls[$sUrl])) {
            return $this->_aPostUrls[$sUrl];
        }

        $this->_aPostUrls[$sUrl] = null;

        //Filter edit string
        $sNewUrl = preg_split("/-e[0-9]{1,}/", $sUrl);

        if (count($sNewUrl) == 2) {
            $sNewUrl = $sNewUrl[0].$sNewUrl[1];
        } else {
            $sNewUrl = $sNewUrl[0];
        }

        //Filter size
        $sNewUrl = preg_split("/-[0-9]{1,}x[0-9]{1,}/", $sNewUrl);

        if (count($sNewUrl) == 2) {
            $sNewUrl = $sNewUrl[0].$sNewUrl[1];
        } else {
            $sNewUrl = $sNewUrl[0];
        }

        $sSql = $this->_oDatabase->prepare(
            "SELECT ID
            FROM {$this->_oDatabase->getPostsTable()}
            WHERE guid = '%s'
            LIMIT 1",
            $sNewUrl
        );

        $oDbPost = $this->_oDatabase->getRow($sSql);

        if ($oDbPost) {
            $this->_aPostUrls[$sUrl] = $oDbPost->ID;
        }

        return $this->_aPostUrls[$sUrl];
    }

    /**
     * Caches the urls for the post for a later lookup.
     *
     * @param string $sUrl  The url of the post.
     * @param object $oPost The post object.
     *
     * @return string
     */
    public function cachePostLinks($sUrl, $oPost)
    {
        $this->_aPostUrls[$sUrl] = $oPost->ID;
        return $sUrl;
    }

    /**
     * Filter for Yoast SEO Plugin
     *
     * Hides the url from the site map if the user has no access
     *
     * @param string $sUrl    The url to check
     * @param string $sType   The object type
     * @param object $oObject The object
     *
     * @return false|string
     */
    function wpSeoUrl($sUrl, $sType, $oObject)
    {
        return ($this->_oAccessHandler->checkObjectAccess($sType, $oObject->ID)) ? $sUrl : false;
    }
}
