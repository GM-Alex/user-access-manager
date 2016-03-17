<?php
/**
 * UserAccessManager.class.php
 *
 * The UserAccessManager class file.
 *
 * PHP versions 5
 *
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

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
    protected $_blAtAdminPanel = false;
    protected $_sAdminOptionsName = "uamAdminOptions";
    protected $_sUamVersion = "1.2.6.7";
    protected $_sUamDbVersion = "1.3";
    protected $_aAdminOptions = null;
    protected $_oAccessHandler = null;
    protected $_aPostUrls = array();
    protected $_aMimeTypes = null;
    protected $_aCache = array();
    protected $_aPosts = array();
    protected $_aCategories = array();
    protected $_aWpOptions = array();
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        do_action('uam_init', $this);
    }

    /**
     * Returns the admin options name for the uam.
     *
     * @return string
     */
    public function getAdminOptionsName()
    {
        return $this->_sAdminOptionsName;
    }

    /**
     * Flushes the cache.
     */
    public function flushCache()
    {
        $this->_aCache = array();
    }

    /**
     * Adds the variable to the cache.
     *
     * @param string $sKey   The cache key
     * @param mixed  $mValue The value.
     */
    public function addToCache($sKey, $mValue)
    {
        $this->_aCache[$sKey] = $mValue;
    }

    /**
     * Returns a value from the cache by the given key.
     *
     * @param string $sKey
     *
     * @return mixed
     */
    public function getFromCache($sKey)
    {
        if (isset($this->_aCache[$sKey])) {
            return $this->_aCache[$sKey];
        }

        return null;
    }

    public function getWpOption($sOption)
    {
        if (!isset($this->_aWpOptions[$sOption])) {
            $this->_aWpOptions[$sOption] = get_option($sOption);
        }

        return $this->_aWpOptions[$sOption];
    }

    /**
     * Returns a post.
     *
     * @param string $sId The post id.
     *
     * @return mixed
     */
    public function getPost($sId)
    {
        if (!isset($this->_aPosts[$sId])) {
            $this->_aPosts[$sId] = get_post($sId);
        }

        return $this->_aPosts[$sId];
    }

    /**
     * Returns a category.
     *
     * @param string $sId The category id.
     *
     * @return mixed
     */
    public function getCategory($sId)
    {
        if (!isset($this->_aCategories[$sId])) {
            $this->_aCategories[$sId] = get_category($sId);
        }

        return $this->_aCategories[$sId];
    }
    
    /**
     * Returns all blog of the network.
     * 
     * @return array()
     */
    protected function _getBlogIds()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        $aBlogIds = array();

        if (is_multisite()) {
            $aBlogIds = $wpdb->get_col(
                "SELECT blog_id
                FROM ".$wpdb->blogs
            );
        }

        return $aBlogIds;
    }
    
    /**
     * Installs the user access manager.
     * 
     * @return null;
     */
    public function install()
    {
        global $wpdb;
        $aBlogIds = $this->_getBlogIds();
 
        if (isset($_GET['networkwide'])
            && ($_GET['networkwide'] == 1)
        ) {
            $iCurrentBlogId = $wpdb->blogid;
            
            foreach ($aBlogIds as $iBlogId) {
                switch_to_blog($iBlogId);
                $this->_installUam();
            }
            
            switch_to_blog($iCurrentBlogId);
            
            return null;
        }
        
        $this->_installUam();
    }
    
    /**
     * Creates the needed tables at the database and adds the options
     * 
     * @return null;
     */
    protected function _installUam()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        include_once ABSPATH.'wp-admin/includes/upgrade.php';
  
        $sCharsetCollate = $this->_getCharset();
        
        $sDbAccessGroupTable = $wpdb->prefix.'uam_accessgroups';
        
        $sDbUserGroup = $wpdb->get_var(
            "SHOW TABLES 
            LIKE '".$sDbAccessGroupTable."'"
        );
        
        if ($sDbUserGroup != $sDbAccessGroupTable) {
            dbDelta(
                "CREATE TABLE ".$sDbAccessGroupTable." (
                    ID int(11) NOT NULL auto_increment,
                    groupname tinytext NOT NULL,
                    groupdesc text NOT NULL,
                    read_access tinytext NOT NULL,
                    write_access tinytext NOT NULL,
                    ip_range mediumtext NULL,
                    PRIMARY KEY (ID)
                ) $sCharsetCollate;"
            );
        }

        $sDbAccessGroupToObjectTable = $wpdb->prefix.'uam_accessgroup_to_object';

        $sDbAccessGroupToObject = $wpdb->get_var(
            "SHOW TABLES 
            LIKE '".$sDbAccessGroupToObjectTable."'"
        );
        
        if ($sDbAccessGroupToObject != $sDbAccessGroupToObjectTable) {
            dbDelta(
                "CREATE TABLE " . $sDbAccessGroupToObjectTable . " (
                    object_id VARCHAR(64) NOT NULL,
                    object_type varchar(64) NOT NULL,
                    group_id int(11) NOT NULL,
                    PRIMARY KEY (object_id,object_type,group_id)
                ) $sCharsetCollate;"
            );
        }
        
        add_option("uam_db_version", $this->_sUamDbVersion);
    }
    
    /**
     * Checks if a database update is necessary.
     * 
     * @return boolean
     */
    public function isDatabaseUpdateNecessary()
    {
        global $wpdb;
        $sBlogIds = $this->_getBlogIds();
 
        if ($sBlogIds !== array()
            && is_super_admin()
        ) {
            $iCurrentBlogId = $wpdb->blogid;
            
            foreach ($sBlogIds as $iBlogId) {
                switch_to_blog($iBlogId);
                $sCurrentDbVersion = $this->getWpOption("uam_db_version");
                
                if (version_compare($sCurrentDbVersion, $this->_sUamDbVersion, '<')) {
                    switch_to_blog($iCurrentBlogId);
                    return true;
                }
            }
            
            switch_to_blog($iCurrentBlogId);
        }
        
        $sCurrentDbVersion = $this->getWpOption("uam_db_version");
        return version_compare($sCurrentDbVersion, $this->_sUamDbVersion, '<');
    }
    
    /**
     * Updates the user access manager if an old version was installed.
     * 
     * @param boolean $blNetworkWide If true update network wide
     * 
     * @return null;
     */
    public function update($blNetworkWide)
    {
        global $wpdb;
        $aBlogIds = $this->_getBlogIds();
 
        if ($blNetworkWide
            && $aBlogIds !== array()
        ) {
            $iCurrentBlogId = $wpdb->blogid;
            
            foreach ($aBlogIds as $iBlogId) {
                switch_to_blog($iBlogId);
                $this->_installUam();
                $this->_updateUam();
            }
            
            switch_to_blog($iCurrentBlogId);
        } else {
            $this->_updateUam();
        }
    }
    
    /**
     * Updates the user access manager if an old version was installed.
     * 
     * @return null;
     */
    protected function _updateUam()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        $sCurrentDbVersion = $this->getWpOption("uam_db_version");
        
        if (empty($sCurrentDbVersion)) {
            $this->install();
        }
        
        if (!$this->getWpOption('uam_version') || version_compare($this->getWpOption('uam_version'), "1.0", '<')) {
            delete_option('allow_comments_locked');
        }
        
        $sDbAccessGroup = $wpdb->prefix.'uam_accessgroups';
        
        $sDbUserGroup = $wpdb->get_var(
            "SHOW TABLES 
            LIKE '".$sDbAccessGroup."'"
        );
        
        if (version_compare($sCurrentDbVersion, $this->_sUamDbVersion, '<')) {
            $sCharsetCollate = $this->_getCharset();

            if (version_compare($sCurrentDbVersion, "1.0", '<=')) {
                if ($sDbUserGroup == $sDbAccessGroup) {
                    $wpdb->query(
                        "ALTER TABLE ".$sDbAccessGroup."
                        ADD read_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD write_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                    );
                    
                    $wpdb->query(
                        "UPDATE ".$sDbAccessGroup."
                        SET read_access = 'group', 
                            write_access = 'group'"
                    );
                    
                    $sDbIpRange = $wpdb->get_var(
                        "SHOW columns 
                        FROM ".$sDbAccessGroup."
                        LIKE 'ip_range'"
                    );
            
                    if ($sDbIpRange != 'ip_range') {
                        $wpdb->query(
                            "ALTER TABLE ".$sDbAccessGroup."
                            ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                        );
                    }
                }

                $sDbAccessGroupToObject = $wpdb->prefix.'uam_accessgroup_to_object';
                $sDbAccessGroupToPost = $wpdb->prefix.'uam_accessgroup_to_post';
                $sDbAccessGroupToUser = $wpdb->prefix.'uam_accessgroup_to_user';
                $sDbAccessGroupToCategory = $wpdb->prefix.'uam_accessgroup_to_category';
                $sDbAccessGroupToRole = $wpdb->prefix.'uam_accessgroup_to_role';
                
                $wpdb->query(
                    "ALTER TABLE '{$sDbAccessGroupToObject}'
                    CHANGE 'object_id' 'object_id' VARCHAR(64)
                    ".$sCharsetCollate
                );
                
                $aObjectTypes = $this->getAccessHandler()->getObjectTypes();
                
                foreach ($aObjectTypes as $sObjectType) {
                    $sAddition = '';

                    if ($this->getAccessHandler()->isPostableType($sObjectType)) {
                        $sDbIdName = 'post_id';
                        $sDatabase = $sDbAccessGroupToPost.', '.$wpdb->posts;
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
                        
                    $aDbObjects = $wpdb->get_results($sSql);
                    
                    foreach ($aDbObjects as $oDbObject) {
                        $sSql = "INSERT INTO {$sDbAccessGroupToObject} (
                                group_id, 
                                object_id,
                                object_type
                            ) 
                            VALUES(
                                '{$oDbObject->groupId}',
                                '{$oDbObject->id}',
                                '{$sObjectType}'
                            )";
                        
                        $wpdb->query($sSql);
                    } 
                }
                
                $wpdb->query(
                    "DROP TABLE {$sDbAccessGroupToPost},
                        {$sDbAccessGroupToUser},
                        {$sDbAccessGroupToCategory},
                        {$sDbAccessGroupToRole}"
                );
            }

            if (version_compare($sCurrentDbVersion, "1.2", '<=')) {
                $sDbAccessGroupToObject = $wpdb->prefix.'uam_accessgroup_to_object';

                $sSql = "
                    ALTER TABLE `{$sDbAccessGroupToObject}`
                    CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
                    CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

                $wpdb->query($sSql);
            }
            
            update_option('uam_db_version', $this->_sUamDbVersion);
        }
    }    
    
    /**
     * Clean up wordpress if the plugin will be uninstalled.
     * 
     * @return null
     */
    public function uninstall()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $wpdb->query(
            "DROP TABLE ".DB_ACCESSGROUP.", 
                ".DB_ACCESSGROUP_TO_OBJECT
        );
        
        delete_option($this->_sAdminOptionsName);
        delete_option('uam_version');
        delete_option('uam_db_version');
        $this->deleteHtaccessFiles();
    }
    
    /**
     * Returns the database charset.
     * 
     * @return string
     */
    protected function _getCharset()
    {
        global $wpdb;
        $sCharsetCollate = '';

        $sMySlqVersion = $wpdb->get_var("SELECT VERSION() as mysql_version");
        
        if (version_compare($sMySlqVersion, '4.1.0', '>=')) {
            if (!empty($wpdb->charset)) {
                $sCharsetCollate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            
            if (!empty($wpdb->collate)) {
                $sCharsetCollate.= " COLLATE $wpdb->collate";
            }
        }
        
        return $sCharsetCollate;
    }
    
    /**
     * Remove the htaccess file if the plugin is deactivated.
     * 
     * @return null
     */
    public function deactivate()
    {
        $this->deleteHtaccessFiles();
    }

    /**
     * Returns the current user.
     *
     * @return WP_User
     */
    public function getCurrentUser()
    {
        if (!function_exists('get_userdata')) {
            include_once ABSPATH.'wp-includes/pluggable.php';
        }

        //Force user information
        return wp_get_current_user();
    }

    /**
     * Returns the full supported mine types.
     *
     * @return array
     */
    protected function _getMimeTypes()
    {
        if ($this->_aMimeTypes === null) {
            $aMimeTypes = get_allowed_mime_types();
            $aFullMimeTypes = array();

            foreach ($aMimeTypes as $sExtensions => $sMineType) {
                $aExtension = explode('|', $sExtensions);

                foreach ($aExtension as $sExtension) {
                    $aFullMimeTypes[$sExtension] = $sMineType;
                }
            }

            $this->_aMimeTypes = $aFullMimeTypes;
        }

        return $this->_aMimeTypes;
    }

    /**
     * @param string $sFileTypes The file types which should be cleaned up.
     *
     * @return string
     */
    protected function _cleanUpFileTypesForHtaccess($sFileTypes)
    {
        $aValidFileTypes = array();
        $aFileTypes = explode(',', $sFileTypes);
        $aMimeTypes = $this->_getMimeTypes();

        foreach ($aFileTypes as $sFileType) {
            $sCleanFileType = trim($sFileType);

            if (isset($aMimeTypes[$sCleanFileType])) {
                $aValidFileTypes[$sCleanFileType] = $sCleanFileType;
            }
        }

        return implode('|', $aValidFileTypes);
    }
    
    /**
     * Creates a htaccess file.
     * 
     * @param string $sDir        The destination directory.
     * @param string $sObjectType The object type.
     * 
     * @return null.
     */
    public function createHtaccess($sDir = null, $sObjectType = null)
    {
        if ($sDir === null) {
            $aWordpressUploadDir = wp_upload_dir();
            
            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'] . "/";
            }
        }
        
        if ($sObjectType === null) {
            $sObjectType = 'attachment';
        }
        
        if ($sDir !== null) {
            if (!$this->isPermalinksActive()) {
                $sAreaName = "WP-Files";
                $aUamOptions = $this->getAdminOptions();
    
                // make .htaccess and .htpasswd
                $sHtaccessTxt = "";
                
                if ($aUamOptions['lock_file_types'] == 'selected') {
                    $sFileTypes = $this->_cleanUpFileTypesForHtaccess($aUamOptions['locked_file_types']);
                    $sHtaccessTxt .= "<FilesMatch '\.(".$sFileTypes.")'>\n";
                } elseif ($aUamOptions['lock_file_types'] == 'not_selected') {
                    $sFileTypes = $this->_cleanUpFileTypesForHtaccess($aUamOptions['not_locked_file_types']);
                    $sHtaccessTxt .= "<FilesMatch '^\.(".$sFileTypes.")'>\n";
                }

                $sHtaccessTxt .= "AuthType Basic" . "\n";
                $sHtaccessTxt .= "AuthName \"" . $sAreaName . "\"" . "\n";
                $sHtaccessTxt .= "AuthUserFile " . $sDir . ".htpasswd" . "\n";
                $sHtaccessTxt .= "require valid-user" . "\n";
                
                if ($aUamOptions['lock_file_types'] == 'selected'
                    || $aUamOptions['lock_file_types'] == 'not_selected'
                ) {
                    $sHtaccessTxt.= "</FilesMatch>\n";
                }
            } else {
                $aHomeRoot = parse_url(home_url());
                if (isset($aHomeRoot['path'])) {
                    $aHomeRoot = trailingslashit($aHomeRoot['path']);
                } else {
                    $aHomeRoot = '/';
                }
                
                $sHtaccessTxt = "<IfModule mod_rewrite.c>\n";
                $sHtaccessTxt .= "RewriteEngine On\n";
                $sHtaccessTxt .= "RewriteBase ".$aHomeRoot."\n";
                $sHtaccessTxt .= "RewriteRule ^index\.php$ - [L]\n";
                $sHtaccessTxt .= "RewriteRule (.*) ";
                $sHtaccessTxt .= $aHomeRoot."index.php?uamfiletype=".$sObjectType."&uamgetfile=$1 [L]\n";
                $sHtaccessTxt .= "</IfModule>\n";
            }
            
            // save files
            $oFileHandler = fopen($sDir.".htaccess", "w");
            fwrite($oFileHandler, $sHtaccessTxt);
            fclose($oFileHandler);
        }
    }
    
    /**
     * Creates a htpasswd file.
     * 
     * @param boolean $blCreateNew Force to create new file.
     * @param string  $sDir       The destination directory.
     * 
     * @return null
     */
    public function createHtpasswd($blCreateNew = false, $sDir = null)
    {
        $oCurrentUser = $this->getCurrentUser();
        if (!function_exists('get_userdata')) {
            include_once ABSPATH.'wp-includes/pluggable.php';
        }
        
        $aUamOptions = $this->getAdminOptions();

        // get url
        if ($sDir === null) {
            $aWordpressUploadDir = wp_upload_dir();
            
            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'] . "/";
            }
        }
        
        if ($sDir !== null) {
            $oUserData = get_userdata($oCurrentUser->ID);
            
            if (!file_exists($sDir.".htpasswd") || $blCreateNew) {
                if ($aUamOptions['file_pass_type'] == 'random') {
                    $sPassword = md5($this->getRandomPassword());
                } else {
                    $sPassword = $oUserData->user_pass;
                }
              
                $sUser = $oUserData->user_login;

                // make .htpasswd
                $sHtpasswdTxt = "$sUser:" . $sPassword . "\n";
                
                // save file
                $oFileHandler = fopen($sDir.".htpasswd", "w");
                fwrite($oFileHandler, $sHtpasswdTxt);
                fclose($oFileHandler);
            }
        }
    }
    
    /**
     * Deletes the htaccess files.
     * 
     * @param string $sDir The destination directory.
     * 
     * @return null
     */
    public function deleteHtaccessFiles($sDir = null)
    {
        if ($sDir === null) {
            $aWordpressUploadDir = wp_upload_dir();
            
            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'] . "/";
            }
        }

        if ($sDir !== null) {
            if (file_exists($sDir.".htaccess")) {
                unlink($sDir.".htaccess");
            }
            
            if (file_exists($sDir.".htpasswd")) {
                unlink($sDir.".htpasswd");
            }
        }
    }
    
    /**
     * Generates and returns a random password.
     * 
     * @return string
     */
    public function getRandomPassword()
    {
        //create password
        $aArray = array();
        $iLength = 16;

        // numbers
        for ($i = 48; $i < 58; $i++) {
            $aArray[] = chr($i);
        }

        // small
        for ($i = 97; $i < 122; $i++) {
            $aArray[] = chr($i);
        }

        // capitals
        for ($i = 65; $i < 90; $i++) {
            $aArray[] = chr($i);
        }
        
        mt_srand((double)microtime() * 1000000);
        $sPassword = '';
        
        for ($i = 1; $i <= $iLength; $i++) {
            $iRandomNumber = mt_rand(0, count($aArray) - 1);
            $sPassword .= $aArray[$iRandomNumber];
        }
        
        return $sPassword;
    }
    
    /**
     * Returns the current settings
     * 
     * @return array
     */
    public function getAdminOptions()
    {
        if ($this->_aAdminOptions === null) {
            $aUamAdminOptions = array(
                'hide_post_title' => 'false', 
                'post_title' => __('No rights!', 'user-access-manager'),
                'post_content' => __(
                    'Sorry you have no rights to view this post!', 
                    'user-access-manager'
                ),
                'hide_post' => 'false', 
                'hide_post_comment' => 'false', 
                'post_comment_content' => __(
                    'Sorry no rights to view comments!', 
                    'user-access-manager'
                ), 
                'post_comments_locked' => 'false',
                'hide_page_title' => 'false', 
                'page_title' => __('No rights!', 'user-access-manager'), 
                'page_content' => __(
                    'Sorry you have no rights to view this page!', 
                    'user-access-manager'
                ), 
                'hide_page' => 'false',
                'hide_page_comment' => 'false', 
                'page_comment_content' => __(
                    'Sorry no rights to view comments!', 
                    'user-access-manager'
                ), 
                'page_comments_locked' => 'false',
                'redirect' => 'false', 
                'redirect_custom_page' => '', 
                'redirect_custom_url' => '', 
                'lock_recursive' => 'true',
                'authors_has_access_to_own' => 'true',
                'authors_can_add_posts_to_groups' => 'false',
                'lock_file' => 'false', 
                'file_pass_type' => 'random', 
                'lock_file_types' => 'all', 
                'download_type' => 'fopen', 
                'locked_file_types' => 'zip,rar,tar,gz',
                'not_locked_file_types' => 'gif,jpg,jpeg,png', 
                'blog_admin_hint' => 'true', 
                'blog_admin_hint_text' => '[L]',
                'hide_empty_categories' => 'true', 
                'protect_feed' => 'true', 
                'show_post_content_before_more' => 'false', 
                'full_access_role' => 'administrator'
            );
            
            $aUamOptions = $this->getWpOption($this->_sAdminOptionsName);
            
            if (!empty($aUamOptions)) {
                foreach ($aUamOptions as $sKey => $mOption) {
                    $aUamAdminOptions[$sKey] = $mOption;
                }
            }
            
            update_option($this->_sAdminOptionsName, $aUamAdminOptions);
            $this->_aAdminOptions = $aUamAdminOptions;
        }

        return $this->_aAdminOptions;
    }

    /**
     * Returns the content of the excluded php file.
     * 
     * @param string  $sFileName   The file name
     * @param integer $iObjectId   The _iId if needed.
     * @param string  $sObjectType The object type if needed.
     * 
     * @return string
     */
    public function getIncludeContents($sFileName, $iObjectId = null, $sObjectType = null)
    {
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
     * Returns the access handler object.
     * 
     * @return UamAccessHandler
     */
    public function &getAccessHandler()
    {
        if ($this->_oAccessHandler == null) {
            $this->_oAccessHandler = new UamAccessHandler($this);
        }
        
        return $this->_oAccessHandler;
    }
    
    /**
     * Returns the current version of the user access manager.
     * 
     * @return string
     */
    public function getVersion()
    {
        return $this->_sUamVersion;
    }
    
    /**
     * Returns true if a user is at the admin panel.
     * 
     * @return boolean
     */
    public function atAdminPanel()
    {
        return $this->_blAtAdminPanel;
    }
    
    /**
     * Sets the atAdminPanel var to true.
     * 
     * @return null
     */
    public function setAtAdminPanel()
    {
        $this->_blAtAdminPanel = true;
    }
    
    
    /*
     * Helper functions.
     */
    
    /**
     * Checks if a string starts with the given needle.
     * 
     * @param string $sHaystack The haystack.
     * @param string $sNeedle   The needle.
     * 
     * @return boolean
     */
    public function startsWith($sHaystack, $sNeedle)
    {
        return strpos($sHaystack, $sNeedle) === 0;
    }
    
    
    /*
     * Functions for the admin panel content.
     */
    
    /**
     * The function for the wp_print_styles action.
     * 
     * @return null
     */
    public function addStyles()
    {
        wp_enqueue_style(
            'UserAccessManagerAdmin', 
            UAM_URLPATH . "css/uamAdmin.css",
            array() ,
            '1.0',
            'screen'
        );
        
        wp_enqueue_style(
            'UserAccessManagerLoginForm', 
            UAM_URLPATH . "css/uamLoginForm.css",
            array() ,
            '1.0',
            'screen'
        );
    }
    
    /**
     * The function for the wp_print_scripts action.
     * 
     * @return null
     */
    public function addScripts()
    {
        wp_enqueue_script(
            'UserAccessManagerFunctions', 
            UAM_URLPATH . 'js/functions.js', 
            array('jquery')
        );
    }
    
    /**
     * Prints the admin page.
     * 
     * @return null
     */
    public function printAdminPage()
    {
        if (isset($_GET['page'])) {
            $sAdminPage = $_GET['page'];

            if ($sAdminPage == 'uam_settings') {
                include UAM_REALPATH."tpl/adminSettings.php";
            } elseif ($sAdminPage == 'uam_usergroup') {
                include UAM_REALPATH."tpl/adminGroup.php";
            } elseif ($sAdminPage == 'uam_setup') {
                include UAM_REALPATH."tpl/adminSetup.php";
            } elseif ($sAdminPage == 'uam_about') {
                include UAM_REALPATH."tpl/about.php";
            }
        }
    }
    
    /**
     * Shows the error if the user has no rights to edit the content.
     * 
     * @return null
     */
    public function noRightsToEditContent()
    {
        $blNoRights = false;
        
        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            $oPost = $this->getPost($_GET['post']);
            $blNoRights = !$this->getAccessHandler()->checkObjectAccess( $oPost->post_type, $oPost->ID );
        }
        
        if (isset($_GET['attachment_id']) && is_numeric($_GET['attachment_id']) && !$blNoRights) {
            $oPost = $this->getPost($_GET['attachment_id']);
            $blNoRights = !$this->getAccessHandler()->checkObjectAccess($oPost->post_type, $oPost->ID);
        }
        
        if (isset($_GET['tag_ID']) && is_numeric($_GET['tag_ID']) && !$blNoRights) {
            $blNoRights = !$this->getAccessHandler()->checkObjectAccess('category', $_GET['tag_ID']);
        }

        if ($blNoRights) {
            wp_die(TXT_UAM_NO_RIGHTS);
        }
    }
    
    /**
     * The function for the wp_dashboard_setup action.
     * Removes widgets to which a user should not have access.
     * 
     * @return null
     */
    public function setupAdminDashboard()
    {
        global $wp_meta_boxes;
        
        if (!$this->getAccessHandler()->checkUserAccess('manage_user_groups')) {
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
        }
    }
    
    /**
     * The function for the update_option_permalink_structure action.
     * 
     * @return null
     */
    public function updatePermalink()
    {
        $this->createHtaccess();
        $this->createHtpasswd();
    }
    
    
    /*
     * Meta functions
     */
    
    /**
     * Saves the object data to the database.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The _iId of the object.
     * @param array   $aUserGroups The new usergroups for the object.
     * 
     * @return null
     */
    protected function _saveObjectData($sObjectType, $iObjectId, $aUserGroups = null)
    {        
        $oUamAccessHandler = $this->getAccessHandler();
        $oUamOptions = $this->getAdminOptions();
        $aFormData = array();

        if (isset($_POST['uam_update_groups'])) {
            $aFormData = $_POST;
        } elseif (isset($_GET['uam_update_groups'])) {
            $aFormData = $_GET;
        }

        if (isset($aFormData['uam_update_groups'])
            && ($oUamAccessHandler->checkUserAccess('manage_user_groups')
            || $oUamOptions['authors_can_add_posts_to_groups'] == 'true')
        ) {
            if ($aUserGroups === null) {
                $aUserGroups = isset($aFormData['uam_usergroups']) ? $aFormData['uam_usergroups'] : array();
            }

            $aAddUserGroups = array_flip($aUserGroups);
            $aRemoveUserGroups = $oUamAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId);
            $aUamUserGroups = $oUamAccessHandler->getUserGroups();
            $blRemoveOldAssignments = true;

            if (isset($aFormData['uam_bulk_type'])) {
                $sBulkType = $aFormData['uam_bulk_type'];

                if ($sBulkType === 'add') {
                    $blRemoveOldAssignments = false;
                } elseif ($sBulkType === 'remove') {
                    $aRemoveUserGroups = $aAddUserGroups;
                    $aAddUserGroups = array();
                }
            }

            foreach ($aUamUserGroups as $sGroupId => $oUamUserGroup) {
                if (isset($aRemoveUserGroups[$sGroupId])) {
                    $oUamUserGroup->removeObject($sObjectType, $iObjectId);
                }

                if (isset($aAddUserGroups[$sGroupId])) {
                    $oUamUserGroup->addObject($sObjectType, $iObjectId);
                }

                $oUamUserGroup->save($blRemoveOldAssignments);
            }
        }
    }
    
    
    /*
     * Functions for the post actions.
     */
    
    /**
     * The function for the manage_posts_columns and 
     * the manage_pages_columns filter.
     * 
     * @param array $aDefaults The table headers.
     * 
     * @return array
     */
    public function addPostColumnsHeader($aDefaults)
    {
        $aDefaults['uam_access'] = __('Access', 'user-access-manager');
        return $aDefaults;
    }
    
    /**
     * The function for the manage_users_custom_column action.
     * 
     * @param string  $sColumnName The column name.
     * @param integer $iId         The _iId.
     * 
     * @return string
     */
    public function addPostColumn($sColumnName, $iId)
    {
        if ($sColumnName == 'uam_access') {
            $oPost = $this->getPost($iId);
            echo $this->getIncludeContents(UAM_REALPATH.'tpl/objectColumn.php', $oPost->ID, $oPost->post_type);
        }
    }
    
    /**
     * The function for the uma_post_access metabox.
     * 
     * @param object $oPost The post.
     * 
     * @return null;
     */
    public function editPostContent($oPost)
    {
        $iObjectId = $oPost->ID;
        include UAM_REALPATH.'tpl/postEditForm.php';
    }

    public function addBulkAction($sColumnName)
    {
        if ($sColumnName == 'uam_access') {
            include UAM_REALPATH.'tpl/bulkEditForm.php';
        }
    }
    
    /**
     * The function for the save_post action.
     * 
     * @param mixed $mPostParam The post _iId or a array of a post.
     * 
     * @return null
     */    
    public function savePostData($mPostParam)
    {
        if (is_array($mPostParam)) {
            $oPost = $this->getPost($mPostParam['ID']);
        } else {
            $oPost = $this->getPost($mPostParam);
        }
        
        $iPostId = $oPost->ID;
        $sPostType = $oPost->post_type;
        
        if ($sPostType == 'revision') {
            $iPostId = $oPost->post_parent;
            $oParentPost = $this->getPost($iPostId);
            $sPostType = $oParentPost->post_type;
        }
        
        $this->_saveObjectData($sPostType, $iPostId);
    }

    /**
     * The function for the attachment_fields_to_save filter.
     * We have to use this because the attachment actions work
     * not in the way we need.
     * 
     * @param object $oAttachment The attachment _iId.
     * 
     * @return object
     */    
    public function saveAttachmentData($oAttachment)
    {
        $this->savePostData($oAttachment['ID']);
        
        return $oAttachment;
    }
    
    /**
     * The function for the delete_post action.
     * 
     * @param integer $iPostId The post _iId.
     * 
     * @return null
     */
    public function removePostData($iPostId)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        $oPost = $this->getPost($iPostId);
        
        $wpdb->query(
            "DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
            WHERE object_id = '".$iPostId."'
                AND object_type = '".$oPost->post_type."'"
        );
    }
    
    /**
     * The function for the media_meta action.
     * 
     * @param string $sMeta The meta.
     * @param object $oPost The post.
     * 
     * @return string
     */
    public function showMediaFile($sMeta = '', $oPost = null)
    {  
        $sContent = $sMeta;
        $sContent .= '</td></tr><tr>';
        $sContent .= '<th class="label">';
        $sContent .= '<label>'.TXT_UAM_SET_UP_USERGROUPS.'</label>';
        $sContent .= '</th>';
        $sContent .= '<td class="field">';
        $sContent .= $this->getIncludeContents(UAM_REALPATH.'tpl/postEditForm.php', $oPost->ID);
        
        return $sContent;
    }
    
    
    /*
     * Functions for the user actions.
     */
    
    /**
     * The function for the manage_users_columns filter.
     * 
     * @param array $aDefaults The table headers.
     * 
     * @return array
     */
    public function addUserColumnsHeader($aDefaults)
    {
        $aDefaults['uam_access'] = __('uam user groups');
        return $aDefaults;
    }
    
    /**
     * The function for the manage_users_custom_column action.
     * 
     * @param string  $sReturn     The normal return value.
     * @param string  $sColumnName The column name.
     * @param integer $iId         The _iId.
     * 
     * @return string|null
     */
    public function addUserColumn($sReturn, $sColumnName, $iId)
    {
        if ($sColumnName == 'uam_access') {
            return $this->getIncludeContents(UAM_REALPATH.'tpl/userColumn.php', $iId, 'user');
        }

        return $sReturn;
    }
    
    /**
     * The function for the edit_user_profile action.
     * 
     * @return null
     */
    public function showUserProfile()
    {
        echo $this->getIncludeContents(UAM_REALPATH.'tpl/userProfileEditForm.php');
    }
    
    /**
     * The function for the profile_update action.
     * 
     * @param integer $iUserId The user _iId.
     * 
     * @return null
     */
    public function saveUserData($iUserId)
    {        
        $this->_saveObjectData('user', $iUserId);
    }
    
    /**
     * The function for the delete_user action.
     * 
     * @param integer $iUserId The user _iId.
     * 
     * @return null
     */
    public function removeUserData($iUserId)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $wpdb->query(
            "DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . "
            WHERE object_id = ".$iUserId."
                AND object_type = 'user'"
        );
    }
    
    
    /*
     * Functions for the category actions.
     */
    
    /**
     * The function for the manage_categories_columns filter.
     * 
     * @param array $aDefaults The table headers.
     * 
     * @return array
     */
    public function addCategoryColumnsHeader($aDefaults)
    {
        $aDefaults['uam_access'] = __('Access', 'user-access-manager');
        return $aDefaults;
    }
    
    /**
     * The function for the manage_categories_custom_column action.
     * 
     * @param string  $sEmpty      An empty string from wordpress? What the hell?!?
     * @param string  $sColumnName The column name.
     * @param integer $iId         The _iId.
     * 
     * @return string|null
     */
    public function addCategoryColumn($sEmpty, $sColumnName, $iId)
    {
        if ($sColumnName == 'uam_access') {
            return $this->getIncludeContents(UAM_REALPATH.'tpl/objectColumn.php', $iId, 'category');
        }

        return null;
    }
    
    /**
     * The function for the edit_category_form action.
     * 
     * @param object $oCategory The category.
     * 
     * @return null
     */
    public function showCategoryEditForm($oCategory)
    {
        include UAM_REALPATH.'tpl/categoryEditForm.php';
    }
    
    /**
     * The function for the edit_category action.
     * 
     * @param integer $iCategoryId The category _iId.
     * 
     * @return null
     */
    public function saveCategoryData($iCategoryId)
    {
        $this->_saveObjectData('category', $iCategoryId);
    }
    
    /**
     * The function for the delete_category action.
     * 
     * @param integer $iCategoryId The _iId of the category.
     * 
     * @return null
     */
    public function removeCategoryData($iCategoryId)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
            WHERE object_id = ".$iCategoryId."
                AND object_type = 'category'"
        );
    }
    

    /*
     * Functions for the pluggable object actions.
     */
    
    /**
     * The function for the pluggable save action.
     * 
     * @param string  $sObjectType The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object _iId.
     * @param array      $aUserGroups The user groups for the object.
     * 
     * @return null
     */
    public function savePlObjectData($sObjectType, $iObjectId, $aUserGroups = null)
    {
        $this->_saveObjectData($sObjectType, $iObjectId, $aUserGroups);
    }
    
    /**
     * The function for the pluggable remove action.
     * 
     * @param string  $sObjectName The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object _iId.
     * 
     * @return null
     */
    public function removePlObjectData($sObjectName, $iObjectId)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $wpdb->query(
            "DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
            WHERE object_id = ".$iObjectId."
                AND object_type = ".$sObjectName
        );
    }
    
    /**
     * Returns the group selection form for pluggable _aObjects.
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
        $aUamUserGroups = $this->getAccessHandler()->getUserGroups();
        $aUserGroupsForObject = $this->getAccessHandler()->getUserGroupsForObject($sObjectType, $iObjectId);
        
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
     * @param integer $iObjectId   The object _iId.
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
     * 
     * @return null
     */
    public function parseQuery($oWpQuery)
    {
        $aUamOptions = $this->getAdminOptions();
        
        if ($aUamOptions['hide_post'] == 'true') {
            $oUamAccessHandler = $this->getAccessHandler();
            $aExcludedPosts = $oUamAccessHandler->getExcludedPosts();
            
            if (count($aExcludedPosts) > 0) {
                $oWpQuery->query_vars['post__not_in'] = array_merge(
                    $oWpQuery->query_vars['post__not_in'],
                    $aExcludedPosts
                );
            }
        }
    }
    
    /**
     * Modifies the content of the post by the given settings.
     * 
     * @param object $oPost The current post.
     * 
     * @return object|null
     */
    protected function _getPost($oPost)
    {
        $aUamOptions = $this->getAdminOptions();
        $oUamAccessHandler = $this->getAccessHandler();
        
        $sPostType = $oPost->post_type;

        if ($this->getAccessHandler()->isPostableType($sPostType) && $sPostType != 'post' && $sPostType != 'page') {
            $sPostType = 'post';
        } elseif ($sPostType != 'post' && $sPostType != 'page') {
            return $oPost;
        }
        
        if ($aUamOptions['hide_'.$sPostType] == 'true' || $this->atAdminPanel()) {
            if ($oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->post_title .= $this->adminOutput($oPost->post_type, $oPost->ID);
                return $oPost;
            }
        } else {
            if (!$oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->isLocked = true;
                
                $sUamPostContent = $aUamOptions[$sPostType.'_content'];
                $sUamPostContent = str_replace("[LOGIN_FORM]",  $this->getLoginBarHtml(), $sUamPostContent);
                
                if ($aUamOptions['hide_'.$sPostType.'_title'] == 'true') {
                    $oPost->post_title = $aUamOptions[$sPostType.'_title'];
                }
                
                if ($aUamOptions[$sPostType.'_comments_locked'] == 'false') {
                    $oPost->comment_status = 'close';
                }

                if ($aUamOptions['show_post_content_before_more'] == 'true'
                    && $sPostType == "post"
                    && preg_match('/<!--more(.*?)?-->/', $oPost->post_content, $aMatches)
                ) {
                    $oPost->post_content = explode($aMatches[0], $oPost->post_content, 2);
                    $sUamPostContent = $oPost->post_content[0] . " " . $sUamPostContent;
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
    public function showPost($aPosts = array())
    {
        $aShowPosts = array();
        $aUamOptions = $this->getAdminOptions();
        
        if (!is_feed() || ($aUamOptions['protect_feed'] == 'true' && is_feed())) {
            foreach ($aPosts as $iPostId) {
                if ($iPostId !== null) {
                    $oPost = $this->_getPost($iPostId);

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
        $oUamAccessHandler = $this->getAccessHandler();
        $aUamOptions = $this->getAdminOptions();
        
        if ($aUamOptions['hide_post'] == 'true') {
            global $wpdb;
            $aExcludedPosts = $oUamAccessHandler->getExcludedPosts();
            
            if (count($aExcludedPosts) > 0) {
                $sExcludedPostsStr = implode(",", $aExcludedPosts);
                $sSql .= " AND $wpdb->posts.ID NOT IN($sExcludedPostsStr) ";
            }
        }
        
        return $sSql;
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
        
        foreach ($aItems as $oItem) {
            if ($oItem->object == 'post' || $oItem->object == 'page') {
                $oObject = $this->getPost($oItem->object_id);
              
                if ($oObject !== null) {
                    $oPost = $this->_getPost($oObject);

                    if ($oPost !== null) {
                        if (isset($oPost->isLocked)) {
                            $oItem->title = $oPost->post_title;
                        }

                        $oItem->title .= $this->adminOutput($oItem->object, $oItem->object_id);
                        $aShowItems[] = $oItem;
                    }
                }
            } elseif ($oItem->object == 'category') {
                $oObject = $this->getCategory($oItem->object_id);
                $oCategory = $this->_getTerm('category', $oObject);

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
        $aUamOptions = $this->getAdminOptions();
        $oUamAccessHandler = $this->getAccessHandler();
        
        foreach ($aComments as $oComment) {
            $oPost = $this->getPost($oComment->comment_post_ID);
            $sPostType = $oPost->post_type;
            
            if ($aUamOptions['hide_'.$sPostType.'_comment'] == 'true'
                || $aUamOptions['hide_'.$sPostType] == 'true'
                || $this->atAdminPanel()
            ) {
                if ($oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $aShowComments[] = $oComment;
                }
            } else {
                if (!$oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $oComment->comment_content = $aUamOptions[$sPostType.'_comment_content'];
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
    public function showPage($aPages = array())
    {
        $aShowPages = array();
        $aUamOptions = $this->getAdminOptions();
        $oUamAccessHandler = $this->getAccessHandler();
        
        foreach ($aPages as $oPage) {
            if ($aUamOptions['hide_page'] == 'true'
                || $this->atAdminPanel()
            ) {
                if ($oUamAccessHandler->checkObjectAccess($oPage->post_type, $oPage->ID)) {
                    $oPage->post_title .= $this->adminOutput(
                        $oPage->post_type,
                        $oPage->ID
                    );
                    $aShowPages[] = $oPage;
                }
            } else {
                if (!$oUamAccessHandler->checkObjectAccess($oPage->post_type, $oPage->ID)) {
                    if ($aUamOptions['hide_page_title'] == 'true') {
                        $oPage->post_title = $aUamOptions['page_title'];
                    }

                    $oPage->post_content = $aUamOptions['page_content'];
                }

                $oPage->post_title .= $this->adminOutput($oPage->post_type, $oPage->ID);
                $aShowPages[] = $oPage;
            }
        }
        
        $aPages = $aShowPages;
        
        return $aPages;
    }
    
    /**
     * Modifies the content of the term by the given settings.
     * 
     * @param string $sTermType The type of the term.
     * @param object $oTerm     The current term.
     * 
     * @return object|null
     */
    protected function _getTerm($sTermType, $oTerm)
    {
        $aUamOptions = $this->getAdminOptions();
        $oUamAccessHandler = $this->getAccessHandler();
        
        $oTerm->isEmpty = false;
        
        $oTerm->name .= $this->adminOutput('term', $oTerm->term_id);
        
        if ($sTermType == 'post_tag'
            || ( $sTermType == 'category' || $sTermType == $oTerm->taxonomy)
            && $oUamAccessHandler->checkObjectAccess('category', $oTerm->term_id)
        ) {
            if ($this->atAdminPanel() == false
                && ($aUamOptions['hide_post'] == 'true'
                || $aUamOptions['hide_page'] == 'true')
            ) {
                $iTermRequest = $oTerm->term_id;
                $sTermRequestType = $sTermType;
                
                if ($sTermType == 'post_tag') {
                    $iTermRequest = $oTerm->slug;
                    $sTermRequestType = 'tag';
                }
                
                $aArgs = array(
                    'numberposts' => - 1,
                    $sTermRequestType => $iTermRequest
                );
                
                $aTermPosts = get_posts($aArgs);
                $oTerm->count = count($aTermPosts);
                
                if (isset($aTermPosts)) {
                    foreach ($aTermPosts as $oPost) {
                        if ($aUamOptions['hide_'.$oPost->post_type] == 'true'
                            && !$oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)
                        ) {
                            $oTerm->count--;
                        }
                    }
                }
                
                //For post_tags
                if ($sTermType == 'post_tag' && $oTerm->count <= 0) {
                    return null;
                }
                
                //For categories
                if ($oTerm->count <= 0
                    && $aUamOptions['hide_empty_categories'] == 'true'
                    && ($oTerm->taxonomy == "term"
                    || $oTerm->taxonomy == "category")
                ) {
                    $oTerm->isEmpty = true;
                }
                
                if ($aUamOptions['lock_recursive'] == 'false') {
                    $oCurCategory = $oTerm;
                    
                    while ($oCurCategory->parent != 0) {
                        $oCurCategory = get_term($oCurCategory->parent, 'category');
                        
                        if ($oUamAccessHandler->checkObjectAccess('term', $oCurCategory->term_id)) {
                            $oTerm->parent = $oCurCategory->term_id;
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
     * The function for the get_terms filter.
     * 
     * @param array $aTerms The terms.
     * @param array $aArgs  The given arguments.
     * 
     * @return array
     */
    public function showTerms($aTerms = array(), $aArgs = array())
    {
        $aShowTerms = array();

        foreach ($aTerms as $oTerm) {
            if (!is_object($oTerm)) {
                return $aTerms;
            }

            if ($oTerm->taxonomy == 'category'  || $oTerm->taxonomy == 'post_tag') {
                $oTerm = $this->_getTerm($oTerm->taxonomy, $oTerm);
            }

            if ($oTerm !== null && (!isset($oTerm->isEmpty) || !$oTerm->isEmpty)) {
                $aShowTerms[$oTerm->term_id] = $oTerm;
            }
        }
        
        foreach ($aTerms as $sKey => $oTerm) {
            if (!isset($aShowTerms[$oTerm->term_id])) {
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
        $oUamAccessHandler = $this->getAccessHandler();
        $aUamOptions = $this->getAdminOptions();
        
        if ($aUamOptions['hide_post'] == 'true') {
            $aExcludedPosts = $oUamAccessHandler->getExcludedPosts();
            
            if (count($aExcludedPosts) > 0) {
                $sExcludedPosts = implode(",", $aExcludedPosts);
                $sSql.= " AND p.ID NOT IN($sExcludedPosts) ";
            }
        }
        
        return $sSql;
    }
     
    /**
     * Returns the admin hint.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object _iId we want to check.
     * 
     * @return string
     */
    public function adminOutput($sObjectType, $iObjectId)
    {
        $sOutput = "";
        
        if (!$this->atAdminPanel()) {
            $aUamOptions = $this->getAdminOptions();
            
            if ($aUamOptions['blog_admin_hint'] == 'true') {
                $oCurrentUser = $this->getCurrentUser();

                $oUserData = get_userdata($oCurrentUser->ID);

                if (!isset($oUserData->user_level)) {
                    return $sOutput;
                }

                $oUamAccessHandler = $this->getAccessHandler();

                if ($oUamAccessHandler->userIsAdmin($oCurrentUser->ID)
                    && count($oUamAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId)) > 0
                ) {
                    $sOutput .= $aUamOptions['blog_admin_hint_text'];
                }
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
        $oUamAccessHandler = $this->getAccessHandler();
        $aGroups = $oUamAccessHandler->getUserGroupsForObject('post', $iPostId);
        
        if (count($aGroups) > 0) {
            $sLink .= ' | '.TXT_UAM_ASSIGNED_GROUPS.': ';
            
            foreach ($aGroups as $oGroup) {
                $sLink .= $oGroup->getGroupName().', ';
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
        if (!is_user_logged_in()) {
            return $this->getIncludeContents(UAM_REALPATH.'tpl/loginBar.php');
        }
        
        return '';
    }

    
    /*
     * Functions for the redirection and files.
     */
    
    /**
     * Returns true if permalinks are active otherwise false.
     * 
     * @return boolean
     */
    public function isPermalinksActive()
    {
        $sPermalinkStructure = $this->getWpOption('permalink_structure');
            
        if (empty($sPermalinkStructure)) {
            return false;
        } else {
            return true;
        }
    }
    
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
        $oUamOptions = $this->getAdminOptions();
        
        if (isset($_GET['uamgetfile']) && isset($_GET['uamfiletype'])) {
            $sFileUrl = $_GET['uamgetfile'];
            $sFileType = $_GET['uamfiletype'];
            $this->getFile($sFileType, $sFileUrl);
        } elseif (!$this->atAdminPanel() && $oUamOptions['redirect'] !== 'false') {
            $oObject = null;

            if (isset($oPageParams->query_vars['p'])) {
                $oObject = $this->getPost($oPageParams->query_vars['p']);
                $oObjectType = $oObject->post_type;
                $iObjectId = $oObject->ID;
            } elseif (isset($oPageParams->query_vars['page_id'])) {
                $oObject = $this->getPost($oPageParams->query_vars['page_id']);
                $oObjectType = $oObject->post_type;
                $iObjectId = $oObject->ID;
            } elseif (isset($oPageParams->query_vars['cat_id'])) {
                $oObject = $this->getCategory($oPageParams->query_vars['cat_id']);
                $oObjectType = 'category';
                $iObjectId = $oObject->term_id;
            } elseif (isset($oPageParams->query_vars['name'])) {
                global $wpdb;

                $sQuery = $wpdb->prepare(
                    "SELECT ID
                    FROM {$wpdb->posts}
                    WHERE post_name = %s
                    AND post_type IN ('post', 'page')",
                    $oPageParams->query_vars['name']
                );

                $sObjectId = $wpdb->get_var($sQuery);

                if ($sObjectId) {
                    $oObject = get_post($sObjectId);
                }

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            } elseif (isset($oPageParams->query_vars['pagename'])) {
                $oObject = get_page_by_title($oPageParams->query_vars['pagename']);

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            }
            
            if ($oObject === null || $oObject !== null && isset($oObjectType) && isset($iObjectId)
                && !$this->getAccessHandler()->checkObjectAccess($oObjectType, $iObjectId)
            ) {
                $this->redirectUser($oObject);
            }
        }

        return $sHeaders;
    }
    
    /**
     * Returns the current url.
     * 
     * @return string
     */
    public function getCurrentUrl()
    { 
        if (!isset($_SERVER['REQUEST_URI'])) {
            $sServerRequestUri = $_SERVER['PHP_SELF'];
        } else { 
            $sServerRequestUri = $_SERVER['REQUEST_URI'];
        } 
        
        $sSecure = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $aProtocols = explode("/", strtolower($_SERVER["SERVER_PROTOCOL"]));
        $sProtocol = $aProtocols[0].$sSecure;
        $sPort = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        
        return $sProtocol."://".$_SERVER['SERVER_NAME'].$sPort.$sServerRequestUri;
    }
    
    /**
     * Redirects the user to his destination.
     * 
     * @param object $oObject The current object we want to access.
     * 
     * @return null
     */
    public function redirectUser($oObject = null)
    {
        global $wp_query;
        
        $blPostToShow = false;
        $aPosts = $wp_query->get_posts();
        
        if ($oObject === null && isset($aPosts)) {
            foreach ($aPosts as $oPost) {
                if ($this->getAccessHandler()->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $blPostToShow = true;
                    break;
                }
            }
        }
        
        if (!$blPostToShow) {
            $aUamOptions = $this->getAdminOptions();
            $sPermalink = null;

            if ($aUamOptions['redirect'] == 'custom_page') {
                $oPost = $this->getPost($aUamOptions['redirect_custom_page']);
                $sUrl = $oPost->guid;
                $sPermalink = get_page_link($oPost);
            } elseif ($aUamOptions['redirect'] == 'custom_url') {
                $sUrl = $aUamOptions['redirect_custom_url'];
            } else {
                $sUrl = home_url('/');
            }

            if ($sUrl != $this->getCurrentUrl() && $sPermalink != $this->getCurrentUrl()) {
                wp_redirect($sUrl);
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
        
        if ($this->getAccessHandler()->checkObjectAccess($oObject->type, $oObject->id)) {
            $sFile = $oObject->file;
        } elseif ($oObject->isImage) {
            $sFile = UAM_REALPATH.'gfx/noAccessPic.png';
        } else {
            wp_die(TXT_UAM_NO_RIGHTS);
        }
        
        //Deliver content
        if (file_exists($sFile)) {
            $sFileName = basename($sFile);
            
            /*
             * This only for compatibility
             * mime_content_type has been deprecated as the PECL extension file info
             * provides the same functionality (and more) in a much cleaner way.
             */
            $sFileExt = strtolower(array_pop(explode('.', $sFileName)));
            $aMimeTypes = $this->_getMimeTypes();

            if (function_exists('finfo_open')) {
                $sFileInfo = finfo_open(FILEINFO_MIME);
                $sFileMimeType = finfo_file($sFileInfo, $sFile);
                finfo_close($sFileInfo);
            } elseif (function_exists('mime_content_type')) {
                $sFileMimeType = mime_content_type($sFile);
            } elseif (isset($aMimeTypes[$sFileExt])) {
                $sFileMimeType = $aMimeTypes[$sFileExt];
            } else {
                $sFileMimeType = 'application/octet-stream';
            }
            
            header('Content-Description: File Transfer');
            header('Content-Type: '.$sFileMimeType);
            
            if (!$oObject->isImage) {
                $sBaseName = str_replace(' ', '_', basename($sFile));
                header('Content-Disposition: attachment; filename="'.$sBaseName.'"');
            }
           
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($sFile));

            $aUamOptions = $this->getAdminOptions();
            
            if ($aUamOptions['download_type'] == 'fopen'
                && !$oObject->isImage
            ) {
                $oHandler = fopen($sFile, 'r');
                
                //TODO find better solution (prevent '\n' / '0A')
                ob_clean();
                flush();
                
                while (!feof($oHandler)) {
                    if (!ini_get('safe_mode')) {
                        set_time_limit(30);
                    }

                    echo fread($oHandler, 1024);
                }
                                
                exit;
            } else {
                ob_clean();
                flush();
                readfile($sFile);
                exit;
            }
        } else {
            wp_die(TXT_UAM_FILE_NOT_FOUND_ERROR);
        }
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

        if ($sObjectType == 'attachment') {
            $aUploadDir = wp_upload_dir();

            $sMultiPath = str_replace(ABSPATH, '/', $aUploadDir['basedir']);
            $sMultiPath = str_replace('/files', $sMultiPath, $aUploadDir['baseurl']);
            
            if ($this->isPermalinksActive()) {
                $sObjectUrl = $sMultiPath.'/'.$sObjectUrl;
            }
            
            $oPost = $this->getPost($this->getPostIdByUrl($sObjectUrl));
    
            if ($oPost !== null
                && $oPost->post_type == 'attachment'
            ) {
                $oObject = new stdClass();
                $oObject->id = $oPost->ID;
                $oObject->isImage = wp_attachment_is_image($oPost->ID);
                $oObject->type = $sObjectType;
                $oObject->file = $aUploadDir['basedir'].str_replace($sMultiPath, '', $sObjectUrl );
            }
        } else {
            $aPlObject = $this->getAccessHandler()->getPlObject($sObjectType);
            
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
        $aUamOptions = $this->getAdminOptions();
            
        if (!$this->isPermalinksActive() && $aUamOptions['lock_file'] == 'true') {
            $oPost = &$this->getPost($iId);
            $aType = explode("/", $oPost->post_mime_type);
            $sType = $aType[1];
            $aFileTypes = explode(',', $aUamOptions['locked_file_types']);
            
            if ($aUamOptions['lock_file_types'] == 'all' || in_array($sType, $aFileTypes)) {
                $sUrl = home_url('/').'?uamfiletype=attachment&uamgetfile='.$sUrl;
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

        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $sSql = $wpdb->prepare(
            "SELECT ID
            FROM ".$wpdb->prefix."posts
            WHERE guid = %s
            LIMIT 1",
            $sNewUrl
        );

        $oDbPost = $wpdb->get_row($sSql);
        
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
     * @return null
     */
    public function cachePostLinks($sUrl, $oPost)
    {
        $this->_aPostUrls[$sUrl] = $oPost->ID;
        return $sUrl;
    }
}