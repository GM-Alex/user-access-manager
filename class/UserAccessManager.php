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
    const USER_OBJECT_TYPE = 'user';
    const POST_OBJECT_TYPE = 'post';
    const PAGE_OBJECT_TYPE = 'page';
    const TERM_OBJECT_TYPE = 'term';
    const ROLE_OBJECT_TYPE = 'role';
    const ATTACHMENT_OBJECT_TYPE = 'attachment';

    protected $_oConfig = null;
    protected $_blAtAdminPanel = false;
    protected $_sUamVersion = '1.2.8';
    protected $_sUamDbVersion = '1.4';
    protected $_oAccessHandler = null;
    protected $_aPostUrls = array();
    protected $_aMimeTypes = null;
    protected $_aCache = array();
    protected $_aUsers = array();
    protected $_aPosts = array();
    protected $_aTerms = array();
    protected $_aWpOptions = array();
    protected $_aTermPostMap = null;
    protected $_aTermTreeMap = null;
    protected $_aPostTypes = null;
    protected $_aTaxonomies = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        do_action('uam_init', $this);
    }

    /**
     * Flushes the cache.
     */
    public function flushCache()
    {
        $this->_aCache = array();
    }

    /**
     * Returns the database.
     *
     * @return wpdb
     */
    public function getDatabase()
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Returns all post types.
     *
     * @return array
     */
    public function getPostTypes()
    {
        if ($this->_aPostTypes === null) {
            $this->_aPostTypes = get_post_types(array('publicly_queryable' => true));
        }

        return $this->_aPostTypes;
    }

    /**
     * Returns the taxonomies.
     *
     * @return array
     */
    public function getTaxonomies()
    {
        if ($this->_aTaxonomies === null) {
            $this->_aTaxonomies = get_taxonomies();
        }

        return $this->_aTaxonomies;
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

    /**
     * Returns a user.
     *
     * @param string $sId The user id.
     *
     * @return mixed
     */
    public function getUser($sId)
    {
        if (!isset($this->_aUsers[$sId])) {
            $this->_aUsers[$sId] = get_userdata($sId);
        }

        return $this->_aUsers[$sId];
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
     * Returns a term.
     *
     * @param string $sId       The term id.
     * @param string $sTaxonomy The taxonomy.
     *
     * @return mixed
     */
    public function getTerm($sId, $sTaxonomy = '')
    {
        if (!isset($this->_aTerms[$sId])) {
            $this->_aTerms[$sId] = get_term($sId, $sTaxonomy);
        }

        return $this->_aTerms[$sId];
    }
    
    /**
     * Returns all blog of the network.
     * 
     * @return array()
     */
    protected function _getBlogIds()
    {
        $oDatabase = $this->getDatabase();
        $aBlogIds = array();

        if (is_multisite()) {
            $aBlogIds = $oDatabase->get_col(
                "SELECT blog_id
                FROM ".$oDatabase->blogs
            );
        }

        return $aBlogIds;
    }
    
    /**
     * Installs the user access manager.
     */
    public function install()
    {
        $oDatabase = $this->getDatabase();
        $aBlogIds = $this->_getBlogIds();
 
        if (isset($_GET['networkwide'])
            && ((int)$_GET['networkwide'] === 1)
        ) {
            $iCurrentBlogId = $oDatabase->blogid;
            
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
     */
    protected function _installUam()
    {
        $oDatabase = $this->getDatabase();
        include_once ABSPATH.'wp-admin/includes/upgrade.php';
  
        $sCharsetCollate = $this->_getCharset();
        
        $sDbAccessGroupTable = $oDatabase->prefix.'uam_accessgroups';
        
        $sDbUserGroup = $oDatabase->get_var(
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

        $sDbAccessGroupToObjectTable = $oDatabase->prefix.'uam_accessgroup_to_object';

        $sDbAccessGroupToObject = $oDatabase->get_var(
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
        $oDatabase = $this->getDatabase();
        $aBlogIds = $this->_getBlogIds();

        if ($aBlogIds !== array()
            && is_super_admin()
        ) {
            foreach ($aBlogIds as $iBlogId) {
                $sTable = $oDatabase->get_blog_prefix($iBlogId).'options';
                $sSelect = "SELECT option_value FROM {$sTable} WHERE option_name = %s LIMIT 1";
                $sSelect = $oDatabase->prepare($sSelect, 'uam_db_version');
                $sCurrentDbVersion = $oDatabase->get_var($sSelect);

                if (version_compare($sCurrentDbVersion, $this->_sUamDbVersion, '<')) {
                    return true;
                }
            }
        }

        $sCurrentDbVersion = get_option('uam_db_version');
        return version_compare($sCurrentDbVersion, $this->_sUamDbVersion, '<');
    }
    
    /**
     * Updates the user access manager if an old version was installed.
     * 
     * @param boolean $blNetworkWide If true update network wide
     */
    public function update($blNetworkWide)
    {
        $oDatabase = $this->getDatabase();
        $aBlogIds = $this->_getBlogIds();
 
        if ($blNetworkWide
            && $aBlogIds !== array()
        ) {
            $iCurrentBlogId = $oDatabase->blogid;
            
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
     */
    protected function _updateUam()
    {
        $oDatabase = $this->getDatabase();
        $sCurrentDbVersion = get_option('uam_db_version');
        
        if (empty($sCurrentDbVersion)) {
            $this->install();
        }
        
        if (!get_option('uam_version') || version_compare(get_option('uam_version'), "1.0", '<')) {
            delete_option('allow_comments_locked');
        }
        
        $sDbAccessGroup = $oDatabase->prefix.'uam_accessgroups';
        
        $sDbUserGroup = $oDatabase->get_var(
            "SHOW TABLES 
            LIKE '".$sDbAccessGroup."'"
        );
        
        if (version_compare($sCurrentDbVersion, $this->_sUamDbVersion, '<')) {
            $sCharsetCollate = $this->_getCharset();

            if (version_compare($sCurrentDbVersion, "1.0", '<=')) {
                if ($sDbUserGroup == $sDbAccessGroup) {
                    $oDatabase->query(
                        "ALTER TABLE ".$sDbAccessGroup."
                        ADD read_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD write_access TINYTEXT NOT NULL DEFAULT '', 
                        ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                    );
                    
                    $oDatabase->query(
                        "UPDATE ".$sDbAccessGroup."
                        SET read_access = 'group', 
                            write_access = 'group'"
                    );
                    
                    $sDbIpRange = $oDatabase->get_var(
                        "SHOW columns 
                        FROM ".$sDbAccessGroup."
                        LIKE 'ip_range'"
                    );
            
                    if ($sDbIpRange != 'ip_range') {
                        $oDatabase->query(
                            "ALTER TABLE ".$sDbAccessGroup."
                            ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                        );
                    }
                }

                $sDbAccessGroupToObject = $oDatabase->prefix.'uam_accessgroup_to_object';
                $sDbAccessGroupToPost = $oDatabase->prefix.'uam_accessgroup_to_post';
                $sDbAccessGroupToUser = $oDatabase->prefix.'uam_accessgroup_to_user';
                $sDbAccessGroupToCategory = $oDatabase->prefix.'uam_accessgroup_to_category';
                $sDbAccessGroupToRole = $oDatabase->prefix.'uam_accessgroup_to_role';
                
                $oDatabase->query(
                    "ALTER TABLE '{$sDbAccessGroupToObject}'
                    CHANGE 'object_id' 'object_id' VARCHAR(64)
                    ".$sCharsetCollate
                );
                
                $aObjectTypes = $this->getAccessHandler()->getObjectTypes();
                
                foreach ($aObjectTypes as $sObjectType) {
                    $sAddition = '';

                    if ($this->getAccessHandler()->isPostableType($sObjectType)) {
                        $sDbIdName = 'post_id';
                        $sDatabase = $sDbAccessGroupToPost.', '.$oDatabase->posts;
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
                        
                    $aDbObjects = $oDatabase->get_results($sSql);
                    
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
                        
                        $oDatabase->query($sSql);
                    } 
                }
                
                $oDatabase->query(
                    "DROP TABLE {$sDbAccessGroupToPost},
                        {$sDbAccessGroupToUser},
                        {$sDbAccessGroupToCategory},
                        {$sDbAccessGroupToRole}"
                );
            }

            if (version_compare($sCurrentDbVersion, "1.2", '<=')) {
                $sDbAccessGroupToObject = $oDatabase->prefix.'uam_accessgroup_to_object';

                $sSql = "
                    ALTER TABLE `{$sDbAccessGroupToObject}`
                    CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
                    CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

                $oDatabase->query($sSql);
            }

            if (version_compare($sCurrentDbVersion, "1.3", '<=')) {
                $sDbAccessGroupToObject = $oDatabase->prefix.'uam_accessgroup_to_object';
                $sTermType = UserAccessManager::TERM_OBJECT_TYPE;

                $sSql = "
                    UPDATE `{$sDbAccessGroupToObject}` AS ag2o
                    SET ag2o.`object_type` = '{$sTermType}'
                    WHERE `object_type` = 'category'";

                $oDatabase->query($sSql);
            }
            
            update_option('uam_db_version', $this->_sUamDbVersion);
        }
    }    
    
    /**
     * Clean up wordpress if the plugin will be uninstalled.
     */
    public function uninstall()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->query(
            "DROP TABLE ".DB_ACCESSGROUP.", 
                ".DB_ACCESSGROUP_TO_OBJECT
        );
        
        delete_option(UamConfig::ADMIN_OPTIONS_NAME);
        delete_option('uam_version');
        delete_option('uam_db_version');
        $this->deleteFileProtectionFiles();
    }
    
    /**
     * Returns the database charset.
     * 
     * @return string
     */
    protected function _getCharset()
    {
        $oDatabase = $this->getDatabase();
        $sCharsetCollate = '';

        $sMySlqVersion = $oDatabase->get_var("SELECT VERSION() as mysql_version");
        
        if (version_compare($sMySlqVersion, '4.1.0', '>=')) {
            if (!empty($oDatabase->charset)) {
                $sCharsetCollate = "DEFAULT CHARACTER SET $oDatabase->charset";
            }
            
            if (!empty($oDatabase->collate)) {
                $sCharsetCollate.= " COLLATE $oDatabase->collate";
            }
        }
        
        return $sCharsetCollate;
    }
    
    /**
     * Remove the htaccess file if the plugin is deactivated.
     */
    public function deactivate()
    {
        $this->deleteFileProtectionFiles();
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
     * Returns true if web server is nginx.
     *
     * @return bool
     */
    public function isNginx()
    {
        global $is_nginx;
        return $is_nginx;
    }
    
    /**
     * Creates a htaccess file.
     * 
     * @param string $sDir        The destination directory.
     * @param string $sObjectType The object type.
     */
    public function createFileProtection($sDir = null, $sObjectType = null)
    {
        $blNginx = $this->isNginx();

        if ($sDir === null) {
            $aWordpressUploadDir = wp_upload_dir();

            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'] . "/";
            }
        }

        $sFileName = ($blNginx === true) ? "uam.conf" : ".htaccess";

        if ($sDir !== null) {
            $sFile = "";
            $sAreaName = "WP-Files";
            $oConfig = $this->getConfig();

            if (!$this->isPermalinksActive()) {
                $sFileTypes = null;

                if ($oConfig->getLockedFileTypes() == 'selected') {
                    $sFileTypes = $this->_cleanUpFileTypesForHtaccess($oConfig->getLockedFileTypes());
                    $sFileTypes = "\.(".$sFileTypes.")";
                } elseif ($blNginx === false && $oConfig->getLockedFileTypes() == 'not_selected') {
                    $sFileTypes = $this->_cleanUpFileTypesForHtaccess($oConfig->getLockedFileTypes());
                    $sFileTypes = "^\.(".$sFileTypes.")";
                }

                if ($blNginx === true) {
                    $sFile = "location " . str_replace(ABSPATH, '/', $sDir) . " {\n";

                    if ($sFileTypes !== null) {
                        $sFile .= "location ~ $sFileTypes {\n";
                    }

                    $sFile .= "auth_basic \"" . $sAreaName . "\";" . "\n";
                    $sFile .= "auth_basic_user_file ". $sDir . ".htpasswd;" . "\n";
                    $sFile .= "}\n";

                    if ($sFileTypes !== null) {
                        $sFile .= "}\n";
                    }
                } else {
                    // make .htaccess and .htpasswd
                    $sFile .= "AuthType Basic" . "\n";
                    $sFile .= "AuthName \"" . $sAreaName . "\"" . "\n";
                    $sFile .= "AuthUserFile " . $sDir . ".htpasswd" . "\n";
                    $sFile .= "require valid-user" . "\n";

                    if ($sFileTypes !== null) {
                        $sFile = "<FilesMatch '" . $sFileTypes . "'>\n" . $sFile . "</FilesMatch>\n";
                    }
                }

                $this->createHtpasswd(true);
            } else {
                if ($sObjectType === null) {
                    $sObjectType = UserAccessManager::ATTACHMENT_OBJECT_TYPE;
                }

                if ($blNginx === true) {
                    $sFile = "location " . str_replace(ABSPATH, '/', $sDir) . " {" . "\n";
                    $sFile .= "rewrite ^(.*)$ /index.php?uamfiletype=" . $sObjectType . "&uamgetfile=$1 last;" . "\n";
                    $sFile .= "}" . "\n";
                } else {
                    $aHomeRoot = parse_url(home_url());
                    $sHomeRoot = (isset($aHomeRoot['path'])) ? trailingslashit($aHomeRoot['path']) : '/';

                    $sFile = "<IfModule mod_rewrite.c>\n";
                    $sFile .= "RewriteEngine On\n";
                    $sFile .= "RewriteBase " . $sHomeRoot . "\n";
                    $sFile .= "RewriteRule ^index\.php$ - [L]\n";
                    $sFile .= "RewriteRule (.*) ";
                    $sFile .= $sHomeRoot . "index.php?uamfiletype=" . $sObjectType . "&uamgetfile=$1 [L]\n";
                    $sFile .= "</IfModule>\n";
                }
            }

            // save files
            $sFileWithPath = ($blNginx === true) ? ABSPATH.$sFileName : $sDir.$sFileName;

            $oFileHandler = fopen($sFileWithPath, "w");
            fwrite($oFileHandler, $sFile);
            fclose($oFileHandler);
        }
    }
    
    /**
     * Creates a htpasswd file.
     * 
     * @param boolean $blCreateNew Force to create new file.
     * @param string  $sDir       The destination directory.
     */
    public function createHtpasswd($blCreateNew = false, $sDir = null)
    {
        $oCurrentUser = $this->getCurrentUser();
        if (!function_exists('get_userdata')) {
            include_once ABSPATH.'wp-includes/pluggable.php';
        }
        
        $oConfig = $this->getConfig();

        // get url
        if ($sDir === null) {
            $aWordpressUploadDir = wp_upload_dir();
            
            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'] . "/";
            }
        }
        
        if ($sDir !== null) {
            $oUserData = $this->getUser($oCurrentUser->ID);
            
            if (!file_exists($sDir.".htpasswd") || $blCreateNew) {
                if ($oConfig->getFilePassType() === 'random') {
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
     */
    public function deleteFileProtectionFiles($sDir = null)
    {
        if ($sDir === null) {
            $aWordpressUploadDir = wp_upload_dir();
            
            if (empty($aWordpressUploadDir['error'])) {
                $sDir = $aWordpressUploadDir['basedir'] . "/";
            }
        }

        if ($sDir !== null) {
            $blNginx = $this->isNginx();
            $sFileName = ($blNginx === true) ? ABSPATH."uam.conf" : $sDir.".htaccess";

            if (file_exists($sFileName)) {
                unlink($sFileName);
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
     * Returns the current config.
     *
     * @return UamConfig
     */
    public function getConfig()
    {
        if ($this->_oConfig === null) {
            $this->_oConfig = new UamConfig();
        }

        return $this->_oConfig;
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
        return $sNeedle === '' || strpos($sHaystack, $sNeedle) === 0;
    }

    /**
     * Checks if a string ends with the given needle.
     *
     * @param string $sHaystack
     * @param string $sNeedle
     *
     * @return bool
     */
    public function endsWith($sHaystack, $sNeedle)
    {
        return $sNeedle === '' || substr($sHaystack, -strlen($sNeedle)) === $sNeedle;
    }
    
    /*
     * Functions for the admin panel content.
     */
    
    /**
     * The function for the wp_print_styles action.
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
     */
    public function printAdminPage()
    {
        if (isset($_GET['page'])) {
            $sAdminPage = $_GET['page'];

            if ($sAdminPage == 'uam_settings') {
                include UAM_REALPATH.'tpl/adminSettings.php';
            } elseif ($sAdminPage == 'uam_usergroup') {
                include UAM_REALPATH.'tpl/adminGroup.php';
            } elseif ($sAdminPage == 'uam_setup') {
                include UAM_REALPATH.'tpl/adminSetup.php';
            } elseif ($sAdminPage == 'uam_about') {
                include UAM_REALPATH.'tpl/about.php';
            }
        }
    }
    
    /**
     * Shows the error if the user has no rights to edit the content.
     */
    public function noRightsToEditContent()
    {
        $blNoRights = false;
        
        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            $oPost = $this->getPost($_GET['post']);
            $blNoRights = !$this->getAccessHandler()->checkObjectAccess($oPost->post_type, $oPost->ID);
        }
        
        if (isset($_GET['attachment_id']) && is_numeric($_GET['attachment_id']) && !$blNoRights) {
            $oPost = $this->getPost($_GET['attachment_id']);
            $blNoRights = !$this->getAccessHandler()->checkObjectAccess($oPost->post_type, $oPost->ID);
        }
        
        if (isset($_GET['tag_ID']) && is_numeric($_GET['tag_ID']) && !$blNoRights) {
            $blNoRights = !$this->getAccessHandler()->checkObjectAccess(self::TERM_OBJECT_TYPE, $_GET['tag_ID']);
        }

        if ($blNoRights) {
            wp_die(TXT_UAM_NO_RIGHTS);
        }
    }
    
    /**
     * The function for the wp_dashboard_setup action.
     * Removes widgets to which a user should not have access.
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
     */
    public function updatePermalink()
    {
        $this->createFileProtection();
    }
    
    
    /*
     * Meta functions
     */
    
    /**
     * Saves the object data to the database.
     * 
     * @param string         $sObjectType The object type.
     * @param integer        $iObjectId   The _iId of the object.
     * @param UamUserGroup[] $aUserGroups The new usergroups for the object.
     */
    protected function _saveObjectData($sObjectType, $iObjectId, $aUserGroups = null)
    {
        $oUamAccessHandler = $this->getAccessHandler();
        $oConfig = $this->getConfig();
        $aFormData = array();

        if (isset($_POST['uam_update_groups'])) {
            $aFormData = $_POST;
        } elseif (isset($_GET['uam_update_groups'])) {
            $aFormData = $_GET;
        }

        if (isset($aFormData['uam_update_groups'])
            && ($oUamAccessHandler->checkUserAccess('manage_user_groups')
            || $oConfig->authorsCanAddPostsToGroups() === true)
        ) {
            if ($aUserGroups === null) {
                $aUserGroups = (isset($aFormData['uam_usergroups']) && is_array($aFormData['uam_usergroups']))
                    ? $aFormData['uam_usergroups'] : array();
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

    /**
     * Removes the object data.
     *
     * @param string $sObjectType The object type.
     * @param int    $iId         The object id.
     */
    protected function _removeObjectData($sObjectType, $iId)
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->query(
            "DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
            WHERE object_id = {$iId}
                AND object_type = '{$sObjectType}'"
        );
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
     * @param integer $iId         The id.
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
     * @param object $oAttachment The attachment id.
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
     * @param integer $iPostId The post id.
     */
    public function removePostData($iPostId)
    {
        $oDatabase = $this->getDatabase();
        $oPost = $this->getPost($iPostId);
        
        $oDatabase->query(
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
     * @param integer $iId         The id.
     * 
     * @return string|null
     */
    public function addUserColumn($sReturn, $sColumnName, $iId)
    {
        if ($sColumnName == 'uam_access') {
            return $this->getIncludeContents(UAM_REALPATH.'tpl/userColumn.php', $iId, self::USER_OBJECT_TYPE);
        }

        return $sReturn;
    }
    
    /**
     * The function for the edit_user_profile action.
     */
    public function showUserProfile()
    {
        echo $this->getIncludeContents(UAM_REALPATH.'tpl/userProfileEditForm.php');
    }
    
    /**
     * The function for the profile_update action.
     * 
     * @param integer $iUserId The user id.
     */
    public function saveUserData($iUserId)
    {        
        $this->_saveObjectData(self::USER_OBJECT_TYPE, $iUserId);
    }
    
    /**
     * The function for the delete_user action.
     * 
     * @param integer $iUserId The user id.
     */
    public function removeUserData($iUserId)
    {
        $this->_removeObjectData(self::USER_OBJECT_TYPE, $iUserId);
    }

    
    /*
     * Functions for the term actions.
     */
    
    /**
     * The function for the manage_categories_columns filter.
     * 
     * @param array $aDefaults The table headers.
     * 
     * @return array
     */
    public function addTermColumnsHeader($aDefaults)
    {
        $aDefaults['uam_access'] = __('Access', 'user-access-manager');
        return $aDefaults;
    }
    
    /**
     * The function for the manage_categories_custom_column action.
     * 
     * @param string  $sEmpty      An empty string from wordpress? What the hell?!?
     * @param string  $sColumnName The column name.
     * @param integer $iId         The id.
     * 
     * @return string|null
     */
    public function addTermColumn($sEmpty, $sColumnName, $iId)
    {
        if ($sColumnName == 'uam_access') {
            return $this->getIncludeContents(UAM_REALPATH.'tpl/objectColumn.php', $iId, self::TERM_OBJECT_TYPE);
        }

        return null;
    }
    
    /**
     * The function for the edit_{term}_form action.
     * 
     * @param object $oTerm The term.
     */
    public function showTermEditForm($oTerm)
    {
        include UAM_REALPATH.'tpl/termEditForm.php';
    }
    
    /**
     * The function for the edit_{term} action.
     * 
     * @param integer $iTermId The term id.
     */
    public function saveTermData($iTermId)
    {
        $this->_saveObjectData(self::TERM_OBJECT_TYPE, $iTermId);
    }
    
    /**
     * The function for the delete_{term} action.
     * 
     * @param integer $iTermId The id of the term.
     */
    public function removeTermData($iTermId)
    {
        $this->_removeObjectData(self::TERM_OBJECT_TYPE, $iTermId);
    }
    

    /*
     * Functions for the pluggable object actions.
     */
    
    /**
     * The function for the pluggable save action.
     * 
     * @param string  $sObjectType The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object id.
     * @param array      $aUserGroups The user groups for the object.
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
        $oConfig = $this->getConfig();

        if ($oConfig->hidePost() === true) {
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
        $oConfig = $this->getConfig();
        $oUamAccessHandler = $this->getAccessHandler();
        
        $sPostType = $oPost->post_type;

        if ($this->getAccessHandler()->isPostableType($sPostType)
            && $sPostType != UserAccessManager::POST_OBJECT_TYPE
            && $sPostType != UserAccessManager::PAGE_OBJECT_TYPE
        ) {
            $sPostType = UserAccessManager::POST_OBJECT_TYPE;
        } elseif ($sPostType != UserAccessManager::POST_OBJECT_TYPE
            && $sPostType != UserAccessManager::PAGE_OBJECT_TYPE
        ) {
            return $oPost;
        }

        if ($oConfig->hideObjectType($sPostType) === true || $this->atAdminPanel()) {
            if ($oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->post_title .= $this->adminOutput($oPost->post_type, $oPost->ID);
                return $oPost;
            }
        } else {
            if (!$oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->isLocked = true;

                $sUamPostContent = $oConfig->getObjectTypeContent($sPostType);
                $sUamPostContent = str_replace('[LOGIN_FORM]',  $this->getLoginBarHtml(), $sUamPostContent);
                
                if ($oConfig->hideObjectTypeTitle($sPostType) === true) {
                    $oPost->post_title = $oConfig->getObjectTypeTitle($sPostType);
                }
                
                if ($oConfig->hideObjectTypeComments($sPostType) === false) {
                    $oPost->comment_status = 'close';
                }

                if ($sPostType === 'post'
                    && $oConfig->showPostContentBeforeMore() === true
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
        $oConfig = $this->getConfig();
        
        if (!is_feed() || ($oConfig->protectFeed() === true && is_feed())) {
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
        $oConfig = $this->getConfig();
        
        if ($oConfig->hidePost() === true) {
            $oDatabase = $this->getDatabase();
            $aExcludedPosts = $oUamAccessHandler->getExcludedPosts();
            
            if (count($aExcludedPosts) > 0) {
                $sExcludedPostsStr = implode(",", $aExcludedPosts);
                $sSql .= " AND $oDatabase->posts.ID NOT IN($sExcludedPostsStr) ";
            }
        }
        
        return $sSql;
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
        $aExclude = (isset($aArguments['exclude'])) ? wp_parse_id_list($aArguments['exclude']) : array();
        $aExcludedTerms = $this->getAccessHandler()->getExcludedTerms();

        if ($this->getConfig()->lockRecursive() === true) {
            $aTermTreeMap = $this->getTermTreeMap();

            foreach ($aExcludedTerms as $sTermId) {
                if (isset($aTermTreeMap[$sTermId])) {
                    $aExcludedTerms = array_merge($aExcludedTerms, $aTermTreeMap[$sTermId]);
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
        $aTaxonomies = $this->getTaxonomies();

        foreach ($aItems as $oItem) {
            if ($oItem->object == UserAccessManager::POST_OBJECT_TYPE
                || $oItem->object == UserAccessManager::PAGE_OBJECT_TYPE
            ) {
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
            } elseif (isset($aTaxonomies[$oItem->object])) {
                $oObject = $this->getTerm($oItem->object_id);
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
        $oConfig = $this->getConfig();
        $oUamAccessHandler = $this->getAccessHandler();
        
        foreach ($aComments as $oComment) {
            $oPost = $this->getPost($oComment->comment_post_ID);
            $sPostType = $oPost->post_type;
            
            if ($oConfig->hideObjectTypeComments($sPostType) === true
                || $oConfig->hideObjectType($sPostType) === true
                || $this->atAdminPanel()
            ) {
                if ($oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $aShowComments[] = $oComment;
                }
            } else {
                if (!$oUamAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $oComment->comment_content = $oConfig->getObjectTypeCommentContent($sPostType);
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
        $oConfig = $this->getConfig();
        $oUamAccessHandler = $this->getAccessHandler();
        
        foreach ($aPages as $oPage) {
            if ($oConfig->hidePage() === true
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
                    if ($oConfig->hidePageTitle() === true) {
                        $oPage->post_title = $oConfig->getPageTitle();
                    }

                    $oPage->post_content = $oConfig->getPageContent();
                }

                $oPage->post_title .= $this->adminOutput($oPage->post_type, $oPage->ID);
                $aShowPages[] = $oPage;
            }
        }
        
        $aPages = $aShowPages;
        
        return $aPages;
    }

    /**
     * Returns the term post map.
     *
     * @return array
     */
    protected function getTermPostMap()
    {
        if ($this->_aTermPostMap === null) {
            $this->_aTermPostMap = array();
            $oDatabase = $this->getDatabase();

            $sSelect = "
                SELECT tr.object_id, tr.term_taxonomy_id, p.post_type
                FROM {$oDatabase->term_relationships} AS tr LEFT JOIN {$oDatabase->posts} as p
                  ON (tr.object_id = p.ID)";

            $aResults = $oDatabase->get_results($sSelect);

            foreach ($aResults as $oResult) {
                if (isset($this->_aTermPostMap[$oResult->term_taxonomy_id])) {
                    $this->_aTermPostMap[$oResult->term_taxonomy_id] = array();
                }

                $this->_aTermPostMap[$oResult->term_taxonomy_id][$oResult->object_id] = $oResult->post_type;
            }
        }

        return $this->_aTermPostMap;
    }

    /**
     * Returns the term tree map.
     *
     * @return array
     */
    protected function getTermTreeMap()
    {
        if ($this->_aTermTreeMap === null) {
            $this->_aTermTreeMap = array();
            $oDatabase = $this->getDatabase();

            $sSelect = "
                SELECT term_id, parent
                FROM {$oDatabase->term_taxonomy}
                  WHERE parent != 0";

            $aResults = $oDatabase->get_results($sSelect);

            foreach ($aResults as $oResult) {
                if (!isset($this->_aTermTreeMap[$oResult->parent])) {
                    $this->_aTermTreeMap[$oResult->parent] = array();
                }

                $this->_aTermTreeMap[$oResult->parent][] = $oResult->term_id;
            }
        }

        return $this->_aTermTreeMap;
    }

    /**
     * Modifies the content of the term by the given settings.
     *
     * @param object $oTerm     The current term.
     * 
     * @return object|null
     */
    protected function _processTerm($oTerm)
    {
        if (is_object($oTerm) === false) {
            return $oTerm;
        }

        $oTerm->name .= $this->adminOutput(self::TERM_OBJECT_TYPE, $oTerm->term_id, $oTerm->name);
        $oConfig = $this->getConfig();
        $oUamAccessHandler = $this->getAccessHandler();

        $oTerm->isEmpty = false;

        if ($oUamAccessHandler->checkObjectAccess(self::TERM_OBJECT_TYPE, $oTerm->term_id)) {
            if ($oConfig->hidePost() === true || $oConfig->hidePage() === true) {
                $iTermRequest = $oTerm->term_id;

                if ($oTerm->taxonomy == 'post_tag') {
                    $iTermRequest = $oTerm->slug;
                }

                $aTermPostMap = $this->getTermPostMap();

                if (isset($aTermPostMap[$iTermRequest])) {
                    $oTerm->count = count($aTermPostMap[$iTermRequest]);

                    foreach ($aTermPostMap[$iTermRequest] as $iPostId => $sPostType) {
                        if ($oConfig->hideObjectType($sPostType) === true
                            && !$oUamAccessHandler->checkObjectAccess($sPostType, $iPostId)
                        ) {
                            $oTerm->count--;
                        }
                    }
                }

                //For post_tags
                if ($oTerm->taxonomy == 'post_tag' && $oTerm->count <= 0) {
                    return null;
                }

                //For categories
                if ($oTerm->count <= 0
                    && $oConfig->hideEmptyCategories() === true
                    && ($oTerm->taxonomy == 'term' || $oTerm->taxonomy == 'category')
                ) {
                    $oTerm->isEmpty = true;
                }

                if ($oConfig->lockRecursive() === false) {
                    $oCurrentTerm = $oTerm;

                    while ($oCurrentTerm->parent != 0) {
                        $oCurrentTerm = $this->getTerm($oCurrentTerm->parent);

                        if ($oUamAccessHandler->checkObjectAccess(UserAccessManager::TERM_OBJECT_TYPE, $oCurrentTerm->term_id)) {
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
            $oUamAccessHandler = $this->getAccessHandler();

            foreach ($aAncestors as $sKey => $aAncestorId) {
                if (!$oUamAccessHandler->checkObjectAccess(self::TERM_OBJECT_TYPE, $aAncestorId)) {
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
     * @param array         $aTerms      The terms.
     * @param array         $aTaxonomies The taxonomies.
     * @param array         $aArgs       The given arguments.
     * @param WP_Term_Query $oTermQuery  The term query.
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

                $mTerm = $this->getTerm($mTerm);
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
        $oUamAccessHandler = $this->getAccessHandler();
        $oConfig = $this->getConfig();
        
        if ($oConfig->hidePost() === true) {
            $aExcludedPosts = $oUamAccessHandler->getExcludedPosts();
            
            if (count($aExcludedPosts) > 0) {
                $sExcludedPosts = implode(',', $aExcludedPosts);
                $sSql.= " AND p.ID NOT IN({$sExcludedPosts}) ";
            }
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
        
        if (!$this->atAdminPanel()) {
            $oConfig = $this->getConfig();
            
            if ($oConfig->blogAdminHint() === true) {
                $sHintText = $oConfig->getBlogAdminHintText();

                if ($sText !== null && $this->endsWith($sText, $sHintText)) {
                    return $sOutput;
                }

                $oCurrentUser = $this->getCurrentUser();
                $oUserData = $this->getUser($oCurrentUser->ID);

                if (!isset($oUserData->user_level)) {
                    return $sOutput;
                }

                $oUamAccessHandler = $this->getAccessHandler();

                if ($oUamAccessHandler->userIsAdmin($oCurrentUser->ID)
                    && count($oUamAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId)) > 0
                ) {
                    $sOutput .= $sHintText;
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
        $aGroups = $oUamAccessHandler->getUserGroupsForObject(self::POST_OBJECT_TYPE, $iPostId);
        
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
        $sPermalinkStructure = $this->getConfig()->getWpOption('permalink_structure');
        return !empty($sPermalinkStructure);
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
        $oConfig = $this->getConfig();
        
        if (isset($_GET['uamgetfile']) && isset($_GET['uamfiletype'])) {
            $sFileUrl = $_GET['uamgetfile'];
            $sFileType = $_GET['uamfiletype'];
            $this->getFile($sFileType, $sFileUrl);
        } elseif (!$this->atAdminPanel() && $oConfig->getRedirect() !== 'false') {
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
                $oObject = $this->getTerm($oPageParams->query_vars['cat_id']);
                $oObjectType = self::TERM_OBJECT_TYPE;
                $iObjectId = $oObject->term_id;
            } elseif (isset($oPageParams->query_vars['name'])) {
                $oDatabase = $this->getDatabase();

                $sPostType = UserAccessManager::POST_OBJECT_TYPE;
                $sPageType = UserAccessManager::PAGE_OBJECT_TYPE;

                $sQuery = $oDatabase->prepare(
                    "SELECT ID
                    FROM {$oDatabase->posts}
                    WHERE post_name = %s
                    AND post_type IN ('{$sPostType}', '{$sPageType}')",
                    $oPageParams->query_vars['name']
                );

                $sObjectId = $oDatabase->get_var($sQuery);

                if ($sObjectId) {
                    $oObject = get_post($sObjectId);
                }

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            } elseif (isset($oPageParams->query_vars['pagename'])) {
                $oObject = get_page_by_path($oPageParams->query_vars['pagename']);

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
            $oConfig = $this->getConfig();
            $sPermalink = null;

            if ($oConfig->getRedirect() === 'custom_page') {
                $sRedirectCustomPage = $oConfig->getRedirectCustomPage();
                $oPost = $this->getPost($sRedirectCustomPage);
                $sUrl = $oPost->guid;
                $sPermalink = get_page_link($oPost);
            } elseif ($oConfig->getRedirect() === 'custom_url') {
                $sUrl = $oConfig->getRedirectCustomUrl();
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

            $oConfig = $this->getConfig();
            
            if ($oConfig->getDownloadType() === 'fopen'
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
            return null;
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

        if ($sObjectType == UserAccessManager::ATTACHMENT_OBJECT_TYPE) {
            $aUploadDir = wp_upload_dir();
            $sUploadDir = str_replace(ABSPATH, '/', $aUploadDir['basedir']);
            $sRegex = '/.*'.str_replace('/', '\/', $sUploadDir).'\//i';
            $sCleanObjectUrl = preg_replace($sRegex, '', $sObjectUrl);
            $sUploadUrl = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
            $sObjectUrl = $sUploadUrl.'/'.ltrim($sCleanObjectUrl, '/');
            $oPost = $this->getPost($this->getPostIdByUrl($sObjectUrl));
    
            if ($oPost !== null
                && $oPost->post_type == UserAccessManager::ATTACHMENT_OBJECT_TYPE
            ) {
                $oObject = new stdClass();
                $oObject->id = $oPost->ID;
                $oObject->isImage = wp_attachment_is_image($oPost->ID);
                $oObject->type = $sObjectType;
                $sMultiPath = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
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
        $oConfig = $this->getConfig();
            
        if (!$this->isPermalinksActive() && $oConfig->lockFile() === true) {
            $oPost = &$this->getPost($iId);
            $aType = explode("/", $oPost->post_mime_type);
            $sType = $aType[1];
            $aFileTypes = explode(',', $oConfig->getLockedFileTypes());
            
            if ($oConfig->getLockedFileTypes() === 'all' || in_array($sType, $aFileTypes)) {
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

        $oDatabase = $this->getDatabase();

        $sSql = $oDatabase->prepare(
            "SELECT ID
            FROM ".$oDatabase->prefix."posts
            WHERE guid = '%s'
            LIMIT 1",
            $sNewUrl
        );

        $oDbPost = $oDatabase->get_row($sSql);
        
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
        return ($this->getAccessHandler()->checkObjectAccess($sType, $oObject->ID)) ? $sUrl : false;
    }
}