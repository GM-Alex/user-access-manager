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
 * @copyright 2008-2010 Alexander Schneider
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
    protected $atAdminPanel = false;
    protected $adminOptionsName = "uamAdminOptions";
    protected $uamVersion = "1.2";
    protected $uamDbVersion = "1.1";
    protected $adminOptions;
    protected $accessHandler = null;
    protected $postUrls = array();
    protected $mimeTypes = array(
		'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
    
    /**
     * Consturctor
     * 
     * @return null
     */
    public function __construct()
    {
        do_action('uam_init', $this);
    }
    
    /**
     * Returns all blogs of the network
     * 
     * @return array()
     */
    private function _getBlogIds()
    {
        global $wpdb;
 
    	if (is_multisite()) {
			$blogIds = $wpdb->get_col(
				"SELECT blog_id
				FROM $wpdb->blogs"
			);
			
			return $blogIds;
    	}
    	
    	return array();
    }
    
    /**
     * Installs the user access manager.
     * 
     * @return null;
     */
    public function install()
    {
        global $wpdb;
        $blogIds = $this->_getBlogIds();
 
    	if ($blogIds !== array()
    	    && isset($_GET['networkwide']) 
    		&& ($_GET['networkwide'] == 1)
    	) {
	        $currentBlog = $wpdb->blogid;
			
			foreach ($blogIds as $blogId) {
				switch_to_blog($blogId);
				$this->_installUam();
			}
			
			switch_to_blog($currentBlog);
			
			return;
    	}
    	
    	$this->_installUam();
    }
    
    /**
     * Creates the needed tables at the database and adds the options
     * 
     * @return null;
     */
    private function _installUam()
    {
        global $wpdb;
        $uamDbVersion = $this->uamDbVersion;
        
        include_once ABSPATH.'wp-admin/includes/upgrade.php';
  
        $charsetCollate = $this->_getCharset();
        
        $dbAccessGroup = $wpdb->prefix.'uam_accessgroups';
        $dbAccessGroupToObject = $wpdb->prefix.'uam_accessgroup_to_object';
        
        $dbUserGroup = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '".$dbAccessGroup."'"
        );
        
        if ($dbUserGroup != $dbAccessGroup) {
            dbDelta(
                "CREATE TABLE ".$dbAccessGroup." (
					ID int(11) NOT NULL auto_increment,
					groupname tinytext NOT NULL,
					groupdesc text NOT NULL,
					read_access tinytext NOT NULL,
					write_access tinytext NOT NULL,
					ip_range mediumtext NULL,
					PRIMARY KEY  (ID)
				) $charsetCollate;"
            );
        }

        $dbUserGroupToObject = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '".$dbAccessGroupToObject."'"
        );
        
        if ($dbUserGroupToObject != $dbAccessGroupToObject) {
            dbDelta(
            	"CREATE TABLE " . $dbAccessGroupToObject . " (
					object_id VARCHAR(11) NOT NULL,
					object_type varchar(255) NOT NULL,
					group_id int(11) NOT NULL,
					PRIMARY KEY  (object_id,object_type,group_id)
				) $charsetCollate;"
            );
        }
        
        add_option("uam_db_version", $this->uamDbVersion);
    }
    
    /**
     * Checks if a database update is necessary.
     * 
     * @return boolean
     */
    public function isDatabaseUpdateNecessary()
    {
        global $wpdb;
        $blogIds = $this->_getBlogIds();
 
    	if ($blogIds !== array()
            && is_super_admin()
        ) {
            $currentBlog = $wpdb->blogid;
			
			foreach ($blogIds as $blogId) {
				switch_to_blog($blogId);
				$currentDbVersion = get_option("uam_db_version");
				
                if (version_compare($currentDbVersion, $this->uamDbVersion, '<')) {
                    switch_to_blog($currentBlog);
                    return true;
                }
			}
			
			switch_to_blog($currentBlog);
        }
        
        $currentDbVersion = get_option("uam_db_version");
        return version_compare($currentDbVersion, $this->uamDbVersion, '<');
    }
    
    /**
     * Updates the user access manager if an old version was installed.
     * 
     * @param boolean $networkWide If true update network wide
     * 
     * @return null;
     */
    public function update($networkWide)
    {
        global $wpdb;
        $blogIds = $this->_getBlogIds();
 
    	if ($blogIds !== array()
    	    && $networkWide
    	) {
	        $currentBlog = $wpdb->blogid;
			
			foreach ($blogIds as $blogId) {
				switch_to_blog($blogId);
				$this->_installUam();
			}
			
			switch_to_blog($currentBlog);
			
			return;
    	}
        
        $this->_updateUam();
    }
    
    /**
     * Updates the user access manager if an old version was installed.
     * 
     * @return null;
     */
    private function _updateUam()
    {
        global $wpdb;
        $currentDbVersion = get_option("uam_db_version");
        
        if (empty($currentDbVersion)) {
            $this->install();
        }
        
        if (!get_option('uam_version')
            || version_compare(get_option('uam_version'), "1.0") === -1
        ) {
            delete_option('allow_comments_locked');
        }
        
        $dbAccessGroup = $wpdb->prefix.'uam_accessgroups';
        
        $dbUserGroup = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '".$dbAccessGroup."'"
        );
        
        if (version_compare($currentDbVersion, $this->uamDbVersion) === -1) {
            if (version_compare($currentDbVersion, "1.0") === 0) {
                if ($dbUserGroup == $dbAccessGroup) {
                    $wpdb->query(
                    	"ALTER TABLE ".$dbAccessGroup." 
                    	ADD read_access TINYTEXT NOT NULL DEFAULT '', 
                    	ADD write_access TINYTEXT NOT NULL DEFAULT '', 
                    	ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                    );
                    
                    $wpdb->query(
                    	"UPDATE ".$dbAccessGroup." 
                    	SET read_access = 'group', 
                    		write_access = 'group'"
                    );
                    
                    $dbIpRange = $wpdb->get_var(
                    	"SHOW columns 
                    	FROM ".$dbAccessGroup." 
                    	LIKE 'ip_range'"
                    );
            
                    if ($dbIpRange != 'ip_range') {
                        $wpdb->query(
                        	"ALTER TABLE ".$dbAccessGroup." 
                        	ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                        );
                    }
                }
                
                $currentDbVersion = "1.1";
            } 
            
            if (version_compare($currentDbVersion, "1.1") === 0) {
                $dbAccessGroupToObject = $wpdb->prefix.'uam_accessgroup_to_object';
                $dbAccessgroupToPost = $wpdb->prefix.'uam_accessgroup_to_post';
                $dbAccessgroupToUser = $wpdb->prefix.'uam_accessgroup_to_user';
                $dbAccessgroupToCategory = $wpdb->prefix.'uam_accessgroup_to_category';
                $dbAccessgroupToRole = $wpdb->prefix.'uam_accessgroup_to_role';
                
                $charsetCollate = $this->_getCharset();
                
                $wpdb->query(
                    "ALTER TABLE 'wp_uam_accessgroup_to_object' 
                    CHANGE 'object_id' 'object_id' VARCHAR(11) 
                    $charsetCollate;"
                );
                
                $objectTypes = $this->getAccessHandler()->getObjectTypes();
                
                foreach ($objectTypes as $objectType) {
                    $addition = '';
                    
                    $postableTypes = $this->getAccessHandler()->getPostableTypes();

                    if (in_array($objectType, $postableTypes)) {
                        $dbIdName = 'post_id';
                        $database = $dbAccessgroupToPost.', '.$wpdb->posts;
                        $addition = " WHERE post_id = ID
                        	AND post_type = '".$objectType."'";
                    } elseif ($objectType == 'category') {
                        $dbIdName = 'category_id';
                        $database = $dbAccessgroupToCategory;
                    } elseif ($objectType == 'user') {
                        $dbIdName = 'user_id';
                        $database = $dbAccessgroupToUser;
                    } elseif ($objectType == 'role') {
                        $dbIdName = 'role_name';
                        $database = $dbAccessgroupToRole;
                    }
                    
                    $sql = "SELECT ".$dbIdName." as id, group_id as groupId
                    	FROM ".$database.$addition;
                        
                    $dbObjects = $wpdb->get_results($sql);
                    
                    foreach ($dbObjects as $dbObject) {
                        $sql = "INSERT INTO ".$dbAccessGroupToObject." (
                        		group_id, 
                        		object_id,
                        		object_type
                        	) 
                        	VALUES(
                        		'".$dbObject->groupId."', 
                        		'".$dbObject->id."',
                        		'".$objectType."'
                        	)";
                        
                        $wpdb->query($sql);
                    } 
                }
                
                $wpdb->query(
                	"DROP TABLE ".$dbAccessgroupToPost.", 
                		".$dbAccessgroupToUser.", 
                		".$dbAccessgroupToCategory.", 
                		".$dbAccessgroupToRole
                );
            }
            
            update_option('uam_db_version', $this->uamDbVersion);
        }
    }    
    
    /**
     * Clean up wordpress if the plugin will be uninstalled.
     * 
     * @return null
     */
    public function uninstall()
    {
        global $wpdb;
        $wpdb->query(
        	"DROP TABLE ".DB_ACCESSGROUP.", 
        		".DB_ACCESSGROUP_TO_OBJECT
        );
        
        delete_option($this->adminOptionsName);
        delete_option('uam_version');
        delete_option('uam_db_version');
        $this->deleteHtaccessFiles();
    }
    
    /**
     * Returns the database charset.
     * 
     * @return string
     */
    private function _getCharset()
    {
        $charsetCollate = '';
        
        if (version_compare(mysql_get_server_info(), '4.1.0', '>=')) {
            if (!empty($wpdb->charset)) {
                $charsetCollate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            
            if (!empty($wpdb->collate)) {
                $charsetCollate.= " COLLATE $wpdb->collate";
            }
        }
        
        return $charsetCollate;
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
     * Creates a htaccess file.
     * 
     * @param string $dir        The destination directory.
     * @param string $objectType The object type.
     * 
     * @return null.
     */
    public function createHtaccess($dir = null, $objectType = null)
    {
        if ($dir === null) {
            $wud = wp_upload_dir();
            
            if (empty($wud['error'])) {
                $dir = $wud['basedir'] . "/";
            }
        }
        
        if ($objectType === null) {
            $objectType = 'attachment';
        }
        
        if ($dir !== null) {   
            if (!$this->isPermalinksActive()) {
                $areaname = "WP-Files";
                $uamOptions = $this->getAdminOptions();
                
                if ($uamOptions['lock_file_types'] == 'selected') {
                    $fileTypes = $uamOptions['locked_file_types'];
                } elseif ($uamOptions['lock_file_types'] == 'not_selected') {
                    $fileTypes = $uamOptions['not_locked_file_types'];
                }
                
                if (isset($fileTypes)) {
                    $fileTypes = str_replace(",", "|", $fileTypes);
                }
    
                // make .htaccess and .htpasswd
                $htaccessTxt = "";
                
                if ($uamOptions['lock_file_types'] == 'selected') {
                    $htaccessTxt .= "<FilesMatch '\.(" . $fileTypes . ")'>\n";
                } elseif ($uamOptions['lock_file_types'] == 'not_selected') {
                    $htaccessTxt .= "<FilesMatch '^\.(" . $fileTypes . ")'>\n";
                }
                
                $htaccessTxt .= "AuthType Basic" . "\n";
                $htaccessTxt .= "AuthName \"" . $areaname . "\"" . "\n";
                $htaccessTxt .= "AuthUserFile " . $dir . ".htpasswd" . "\n";
                $htaccessTxt .= "require valid-user" . "\n";
                
                if ($uamOptions['lock_file_types'] == 'selected' 
                    || $uamOptions['lock_file_types'] == 'not_selected'
                ) {
                    $htaccessTxt.= "</FilesMatch>\n";
                }
            } else {
                $homeRoot = parse_url(home_url());
        		if (isset($homeRoot['path'])) {
        			$homeRoot = trailingslashit($homeRoot['path']);
        		} else {
        			$homeRoot = '/';
        		}
                
                $htaccessTxt = "<IfModule mod_rewrite.c>\n";
                $htaccessTxt .= "RewriteEngine On\n";
                $htaccessTxt .= "RewriteBase ".$homeRoot."\n";
                $htaccessTxt .= "RewriteRule ^index\.php$ - [L]\n";
                $htaccessTxt .= "RewriteRule (.*) ";
                $htaccessTxt .= $homeRoot."index.php?uamfiletype=".$objectType."&uamgetfile=$1 [L]\n";
                $htaccessTxt .= "</IfModule>\n";
            }
            
            // save files
            $htaccess = fopen($dir.".htaccess", "w");
            fwrite($htaccess, $htaccessTxt);
            fclose($htaccess);
        }
    }
    
	/**
     * Creates a htpasswd file.
     * 
     * @param boolean $createNew Force to create new file.
     * @param string  $dir       The destination directory.
     * 
     * @return null
     */
    public function createHtpasswd($createNew = false, $dir = null)
    {
        if (!function_exists('get_userdata')) {
            include_once ABSPATH.'wp-includes/pluggable.php';
        }
        
        global $current_user;
        //Force user infos
        wp_get_current_user();
        
        $uamOptions = $this->getAdminOptions();

        // get url
        if ($dir === null) {
            $wud = wp_upload_dir();
            
            if (empty($wud['error'])) {
                $dir = $wud['basedir'] . "/";
            }
        }
        
        if ($dir !== null) {
            $curUserdata = get_userdata($current_user->ID);
            
            if (!file_exists($dir.".htpasswd") || $createNew) {
                if ($uamOptions['file_pass_type'] == 'random') {
                    $password = md5($this->getRandomPassword());
                } elseif ($uamOptions['file_pass_type'] == 'admin') {
                    $password = $curUserdata->user_pass;
                }
              
                $user = $curUserdata->user_login;

                // make .htpasswd
                $htpasswdTxt = "$user:" . $password . "\n";
                
                // save file
                $htpasswd = fopen($dir.".htpasswd", "w");
                fwrite($htpasswd, $htpasswdTxt);
                fclose($htpasswd);
            }
        }
    }
    
    /**
     * Deletes the htaccess files.
     * 
     * @param string $dir The destination directory.
     * 
     * @return null
     */
    public function deleteHtaccessFiles($dir = null)
    {
        if ($dir === null) {
            $wud = wp_upload_dir();
            
            if (empty($wud['error'])) {
                $dir = $wud['basedir'] . "/";
            }
        }

        if ($dir !== null) {    
            if (file_exists($dir.".htaccess")) {
                unlink($dir.".htaccess");
            }
            
            if (file_exists($dir.".htpasswd")) {
                unlink($dir.".htpasswd");
            }
        }
    }
    
    /**
     * Generates and retruns a randmom password.
     * 
     * @return string
     */
    public function getRandomPassword()
    {
        //create password
        $array = array();
        $length = 16;
        $capitals = true;
        $specialSigns = false;
        if ($length < 8) {
            $length = mt_rand(8, 20);
        }

        // numbers
        for ($i = 48; $i < 58; $i++) {
            $array[] = chr($i);
        }

        // small
        for ($i = 97; $i < 122; $i++) {
            $array[] = chr($i);
        }

        // capitals
        if ($capitals) {
            for ($i = 65; $i < 90; $i++) {
                $array[] = chr($i);
            }
        } 

        // specialchar:
        if ($specialSigns) {
            for ($i = 33; $i < 47; $i++) {
                $array[] = chr($i);
            }
            
            for ($i = 59; $i < 64; $i++) {
                $array[] = chr($i);
            }
            
            for ($i = 91; $i < 96; $i++) {
                $array[] = chr($i);
            }
            
            for ($i = 123; $i < 126; $i++) {
                $array[] = chr($i);
            }
        }
        
        mt_srand((double)microtime() * 1000000);
        $password = '';
        
        for ($i = 1; $i <= $length; $i++) {
            $rnd = mt_rand(0, count($array) - 1);
            $password.= $array[$rnd];
        }
        
        return $password;
    }
    
    /**
     * Returns the current settings
     * 
     * @return array
     */
    public function getAdminOptions()
    {
        if (empty($this->adminOptions)) {
            $uamAdminOptions = array(
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
            	'locked_file_types' => 'zip,rar,tar,gz,bz2', 
            	'not_locked_file_types' => 'gif,jpg,jpeg,png', 
            	'blog_admin_hint' => 'true', 
            	'blog_admin_hint_text' => '[L]',
            	'hide_empty_categories' => 'true', 
            	'protect_feed' => 'true', 
            	'show_post_content_before_more' => 'false', 
            	'full_access_role' => 'administrator'
            );
            
            $uamOptions = get_option($this->adminOptionsName);
            
            if (!empty($uamOptions)) {
                foreach ($uamOptions as $key => $option) {
                    $uamAdminOptions[$key] = $option;
                }
            }
            
            update_option($this->adminOptionsName, $uamAdminOptions);
            $this->adminOptions = $uamAdminOptions;
        }

        return $this->adminOptions;
    }

    /**
     * Retruns the content of the excecuded php file.
     * 
     * @param string  $fileName   The file name
     * @param integer $objectId   The id if needed.
     * @param string  $objectType The object type if needed.
     * 
     * @return string
     */
    public function getIncludeContents($fileName, $objectId = null, $objectType = null)
    {
        if (is_file($fileName)) {
            ob_start();
            include $fileName;
            $contents = ob_get_contents();
            ob_end_clean();
            
            return $contents;
        }
        
        return '';
    }
    
    /**
     * Returns the access handler object.
     * 
     * @return object
     */
    public function &getAccessHandler()
    {
        if ($this->accessHandler == null) {
            $this->accessHandler = new UamAccessHandler($this);
        }
        
        return $this->accessHandler;
    }
    
    /**
     * Returns the current version of the user access manager.
     * 
     * @return string
     */
    public function getVersion()
    {
        return $this->uamVersion;
    }
    
    /**
     * Returns true if a user is at the admin panel.
     * 
     * @return boolean
     */
    public function atAdminPanel()
    {
        return $this->atAdminPanel;
    }
    
    /**
     * Sets the atAdminPanel var to true.
     * 
     * @return null
     */
    public function setAtAdminPanel()
    {
        $this->atAdminPanel = true;
    }
    
    
    /*
     * Helper functions.
     */
    
    /**
     * Checks if a string starts with the given needle.
     * 
     * @param string $haystack The haystack.
     * @param string $needle   The needle
     * 
     * @return boolean
     */
    public function startsWith($haystack, $needle)
    {
        return strpos($haystack, $needle) === 0;
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
            false, 
            '1.0',
            'screen'
        );
        
        wp_enqueue_style(
        	'UserAccessManagerLoginForm', 
            UAM_URLPATH . "css/uamLoginForm.css", 
            false, 
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
        	'UserAccessManagerJQueryTools', 
            UAM_URLPATH . 'js/jquery.tools.min.js',
            array('jquery')
        );
        wp_enqueue_script(
        	'UserAccessManagerFunctions', 
            UAM_URLPATH . 'js/functions.js', 
            array('jquery', 'UserAccessManagerJQueryTools')
        );
    }
    
    /**
     * Prints the admin page
     * 
     * @return null
     */
    public function printAdminPage()
    {
        if (isset($_GET['page'])) {
            $curAdminPage = $_GET['page'];
        }
        
        if ($curAdminPage == 'uam_settings') {
            include UAM_REALPATH."tpl/adminSettings.php";
        } elseif ($curAdminPage == 'uam_usergroup') {
            include UAM_REALPATH."tpl/adminGroup.php";
        } elseif ($curAdminPage == 'uam_setup') {
            include UAM_REALPATH."tpl/adminSetup.php";
        } elseif ($curAdminPage == 'uam_about') {
            include UAM_REALPATH."tpl/about.php";
        }
    }
    
    /**
     * Shows the error if the user has no rights to edit the content
     * 
     * @return null
     */
    public function noRightsToEditContent()
    {
        $noRights = false;
        
        if (isset($_GET['post']) 
            && is_numeric($_GET['post'])
        ) {
            $post = get_post($_GET['post']);
            
            $noRights = !$this->getAccessHandler()->checkObjectAccess(
                $post->post_type, 
                $post->ID
            ); 
        }
        
        if (isset($_GET['attachment_id'])
            && is_numeric($_GET['attachment_id'])
            && !$noRights
        ) {
            $post = get_post($_GET['attachment_id']);
            
            $noRights = !$this->getAccessHandler()->checkObjectAccess(
            	$post->post_type, 
                $post->ID
            );
        }
        
        if (isset($_GET['tag_ID']) 
            && is_numeric($_GET['tag_ID'])
            && !$noRights
        ) {
            $noRights = !$this->getAccessHandler()->checkObjectAccess(
                'category', 
                $_GET['tag_ID']
            );
        }

        if ($noRights) {
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
     * @param string  $objectType The object type.
     * @param integer $objectId   The id of the object.
     * @param array   $userGroups The new usergroups for the object.
     * 
     * @return null
     */
    private function _saveObjectData($objectType, $objectId, $userGroups = null)
    {        
        $uamAccessHandler = &$this->getAccessHandler();
        $uamOptions = $this->getAdminOptions();
        
        if (isset($_POST['uam_update_groups'])
            && ($uamAccessHandler->checkUserAccess('manage_user_groups')
            || $uamOptions['authors_can_add_posts_to_groups'] == 'true')
        ) {            
            $userGroupsForObject = $uamAccessHandler->getUserGroupsForObject(
                $objectType, 
                $objectId
            );

            foreach ($userGroupsForObject as $uamUserGroup) {
                $uamUserGroup->removeObject($objectType, $objectId);
                $uamUserGroup->save();
            }
            
            if ($userGroups === null
                && isset($_POST['uam_usergroups'])
            ) {
                $userGroups = $_POST['uam_usergroups'];
            }
            
            if ($userGroups !== null) {
                foreach ($userGroups as $userGroupId) {
                    $uamUserGroup = $uamAccessHandler->getUserGroups($userGroupId);
    
                    $uamUserGroup->addObject($objectType, $objectId);
                    $uamUserGroup->save();
                }
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
     * @param array $defaults The table headers.
     * 
     * @return array
     */
    public function addPostColumnsHeader($defaults)
    {
        $defaults['uam_access'] = __('Access', 'user-access-manager');
        return $defaults;
    }
    
    /**
     * The function for the manage_users_custom_column action.
     * 
     * @param string  $columnName The column name.
     * @param integer $id         The id.
     * 
     * @return String
     */
    public function addPostColumn($columnName, $id)
    {
        if ($columnName == 'uam_access') {
            $post = get_post($id);
            
            echo $this->getIncludeContents(
                UAM_REALPATH.'tpl/objectColumn.php', 
                $post->ID, 
                $post->post_type
            );
        }
    }
    
    /**
     * The function for the uma_post_access metabox.
     * 
     * @param object $post The post.
     * 
     * @return null;
     */
    public function editPostContent($post)
    {
        $objectId = $post->ID;
        
        include UAM_REALPATH.'tpl/postEditForm.php';
    }
    
    /**
     * The function for the save_post action.
     * 
     * @param mixed $postParam The post id or a array of a post.
     * 
     * @return null
     */    
    public function savePostData($postParam)
    {
        if (is_array($postParam)) {
            $post = get_post($postParam['ID']);
        } else {
            $post = get_post($postParam);
        }
        
        $postId = $post->ID;
        $postType = $post->post_type;
        
        if ($postType == 'revision') {
            $postId = $post->post_parent;
            $parentPost = get_post($postId);
            $postType = $parentPost->post_type;
        }
        
        $this->_saveObjectData($postType, $postId);
    }

    /**
     * The function for the attachment_fields_to_save filter.
     * We have to use this because the attachment actions work
     * not in the way we need.
     * 
     * @param object $attachment The attachment id.
     * 
     * @return object
     */    
    public function saveAttachmentData($attachment)
    {
        $this->savePostData($attachment['ID']);
        
        return $attachment;
    }
    
    /**
     * The function for the delete_post action.
     * 
     * @param integer $postId The post id.
     * 
     * @return null
     */
    public function removePostData($postId)
    {
        global $wpdb;
        $post = get_post($postId);
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
        	WHERE object_id = '".$postId."'
        		AND object_type = '".$post->post_type."'"
        );
    }
    
    /**
     * The function for the media_meta action.
     * 
     * @param string $meta The meta.
     * @param object $post The post.
     * 
     * @return string
     */
    public function showMediaFile($meta = '', $post = null)
    {  
        $content = $meta;
        $content .= '</td></tr><tr>';
        $content .= '<th class="label">';
        $content .= '<label>'.TXT_UAM_SET_UP_USERGROUPS.'</label>';
        $content .= '</th>';
        $content .= '<td class="field">';
        $content .= $this->getIncludeContents(
            UAM_REALPATH.'tpl/postEditForm.php',
            $post->ID
        );
        
        return $content;
    }
    
    
    /*
     * Functions for the user actions.
     */
    
    /**
     * The function for the manage_users_columns filter.
     * 
     * @param array $defaults The table headers.
     * 
     * @return array
     */
    public function addUserColumnsHeader($defaults)
    {
        $defaults['uam_access'] = __('uam user groups');
        return $defaults;
    }
    
    /**
     * The function for the manage_users_custom_column action.
     * 
     * @param unknown $empty      An empty string from wordpress? What the hell?!?
     * @param string  $columnName The column name.
     * @param integer $id         The id.
     * 
     * @return String
     */
    public function addUserColumn($empty, $columnName, $id)
    {
        if ($columnName == 'uam_access') {
            return $this->getIncludeContents(
                UAM_REALPATH.'tpl/userColumn.php', 
                $id,
                'user'
            );
        }
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
     * @param integer $userId The user id.
     * 
     * @return null
     */
    public function saveUserData($userId)
    {        
        $this->_saveObjectData('user', $userId);
    }
    
    /**
     * The function for the delete_user action.
     * 
     * @param integer $userId The user id.
     * 
     * @return null
     */
    public function removeUserData($userId)
    {
        global $wpdb;

        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
        	WHERE object_id = ".$userId."
                AND object_type = 'user'"
        );
    }
    
    
    /*
     * Functions for the category actions.
     */
    
    /**
     * The function for the manage_categories_columns filter.
     * 
     * @param array $defaults The table headers.
     * 
     * @return array
     */
    public function addCategoryColumnsHeader($defaults)
    {
        $defaults['uam_access'] = __('Access', 'user-access-manager');
        return $defaults;
    }
    
    /**
     * The function for the manage_categories_custom_column action.
     * 
     * @param unknown $empty      An empty string from wordpress? What the hell?!?
     * @param string  $columnName The column name.
     * @param integer $id         The id.
     * 
     * @return String
     */
    public function addCategoryColumn($empty, $columnName, $id)
    {
        if ($columnName == 'uam_access') {
            return $this->getIncludeContents(
                UAM_REALPATH.'tpl/objectColumn.php', 
                $id,
                'category'
            );
        }
    }
    
    /**
     * The function for the edit_category_form action.
     * 
     * @param object $category The category.
     * 
     * @return null
     */
    public function showCategoryEditForm($category)
    {
        include UAM_REALPATH.'tpl/categoryEditForm.php';
    }
    
    /**
     * The function for the edit_category action.
     * 
     * @param integer $categoryId The category id.
     * 
     * @return null
     */
    public function saveCategoryData($categoryId)
    {
        $this->_saveObjectData('category', $categoryId);
    }
    
    /**
     * The function for the delete_category action.
     * 
     * @param integer $categoryId The id of the category.
     * 
     * @return null
     */
    public function removeCategoryData($categoryId)
    {
        //TODO
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
        	WHERE object_id = ".$categoryId."
        		AND object_type = 'category'"
        );
    }
    

    /*
     * Functions for the pluggable object actions.
     */
    
    /**
     * The function for the pluggable save action.
     * 
     * @param string  $objectType The name of the pluggable object.
     * @param integer $objectId   The pluggable object id.
     * @param array	  $userGroups The user groups for the object.
     * 
     * @return null
     */
    public function savePlObjectData($objectType, $objectId, $userGroups = null)
    {
        $this->_saveObjectData($objectType, $objectId, $userGroups);
    }
    
    /**
     * The function for the pluggable remove action.
     * 
     * @param string  $objectName The name of the pluggable object.
     * @param integer $objectId   The pluggable object id.
     * 
     * @return null
     */
    public function removePlObjectData($objectName, $objectId)
    {
        global $wpdb;

        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
        	WHERE user_id = ".$userId."
                AND object_type = ".$objectName
        );
    }
    
    /**
     * Returns the group selection form for pluggable objects.
     * 
     * @param string  $objectType     The object type.
     * @param integer $objectId       The id of the object.
     * @param string  $groupsFormName The name of the form which contains the groups.
     * 
     * @return string;
     */
    public function showPlGroupSelectionForm($objectType, $objectId, $groupsFormName = null)
    {   
        $fileName = UAM_REALPATH.'tpl/groupSelectionForm.php';
        $uamUserGroups = $this->getAccessHandler()->getUserGroups();
        $userGroupsForObject = $this->getAccessHandler()->getUserGroupsForObject(
        	$objectType, 
            $objectId
        );
        
        if (is_file($fileName)) {
            ob_start();
            include $fileName;
            $contents = ob_get_contents();
            ob_end_clean();
            
            return $contents;
        }
        
        return '';
    }
    
    /**
     * Returns the column for a pluggable object.
     * 
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id.
     * 
     * @return string
     */
    public function getPlColumn($objectType, $objectId)
    {
        return $this->getIncludeContents(
            UAM_REALPATH.'tpl/objectColumn.php', 
            $objectId, 
            $objectType
        );
    }
    
    
    /*
     * Functions for the blog content.
     */
    
    /**
     * Manipulates the wordpress query object to filter content.
     * 
     * @param object $wpQuery The wordpress query object.
     * 
     * @return null
     */
    public function parseQuery($wpQuery)
    {
        $uamOptions = $this->getAdminOptions();
        
        if ($uamOptions['hide_post'] == 'true') {
            $uamAccessHandler = &$this->getAccessHandler();
            $excludedPosts = $uamAccessHandler->getExcludedPosts();
            
            if (count($excludedPosts) > 0) {
                $wpQuery->query_vars['post__not_in'] = array_merge(
                    $wpQuery->query_vars['post__not_in'],
                    $excludedPosts
                );
            }
        }
    }
    
    /**
     * Modifies the content of the post by the given settings.
     * 
     * @param object $post The current post.
     * 
     * @return object
     */
    private function _getPost($post)
    {
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        $postType = $post->post_type;
        
        $postableTypes = $uamAccessHandler->getPostableTypes();

        if (in_array($postType, $postableTypes)
        	&& $postType != 'post' 
        	&& $postType != 'page'
        ) {
            $postType = 'post';
        } elseif ($postType != 'post' && $postType != 'page') {
            return $post;
        }
        
        if ($uamOptions['hide_'.$postType] == 'true'
            || $this->atAdminPanel()
        ) {
            if ($uamAccessHandler->checkObjectAccess($post->post_type, $post->ID)) {
                $post->post_title .= $this->adminOutput($post->post_type, $post->ID);
                
                return $post;
            }
        } else {
            if (!$uamAccessHandler->checkObjectAccess($post->post_type, $post->ID)) {
                $post->isLocked = true;
                
                $uamPostContent = $uamOptions[$postType.'_content'];
                $uamPostContent = str_replace(
                	"[LOGIN_FORM]", 
                    $this->getLoginBarHtml(), 
                    $uamPostContent
                );
                
                if ($uamOptions['hide_'.$postType.'_title'] == 'true') {
                    $post->post_title = $uamOptions[$postType.'_title'];
                }
                
                if ($uamOptions[$postType.'_comments_locked'] == 'false') {
                    $post->comment_status = 'close';
                }

                if ($uamOptions['show_post_content_before_more'] == 'true'
                	&& $postType == "post"
                    && preg_match('/<!--more(.*?)?-->/', $post->post_content, $matches)
                ) {
                    $post->post_content = explode(
                        $matches[0], 
                        $post->post_content, 
                        2
                    );
                    $uamPostContent 
                        = $post->post_content[0] . " " . $uamPostContent;
                } 
                
                $post->post_content = $uamPostContent;
            }
            
            $post->post_title .= $this->adminOutput($post->post_type, $post->ID);
            
            return $post;
        }
        
        return null;
    }
    
    /**
     * The function for the the_posts filter.
     * 
     * @param arrray $posts The posts.
     * 
     * @return array
     */
    public function showPost($posts = array())
    {
        $showPosts = array();
        $uamOptions = $this->getAdminOptions();
        
        if (!is_feed() 
            || ($uamOptions['protect_feed'] == 'true' && is_feed())
        ) {
            foreach ($posts as $post) {
                if ($post !== null) {
                    $post = $this->_getPost($post);
                }
                
                if ($post !== null) {
                    $showPosts[] = $post;
                }
            }
            
            $posts = $showPosts;
        }
        
        return $posts;
    }
    
    /**
     * The function for the posts_where_paged filter.
     * 
     * @param string $sql The where sql statment.
     * 
     * @return string
     */
    public function showPostSql($sql)
    {   
        $uamAccessHandler = &$this->getAccessHandler();
        $uamOptions = $this->getAdminOptions();
        
        if ($uamOptions['hide_post'] == 'true') {
            global $wpdb;
            $excludedPosts = $uamAccessHandler->getExcludedPosts();
            
            if (count($excludedPosts) > 0) {
                $excludedPostsStr = implode(",", $excludedPosts);
                $sql .= " AND $wpdb->posts.ID NOT IN($excludedPostsStr) ";
            }
        }
        
        return $sql;
    }
    
    /**
     * The function for the wp_get_nav_menu_items filter.
     * 
     * @param array $items The menu item.
     * 
     * @return array
     */
    public function showCustomMenu($items)
    {
        $showItems = array();
        
        foreach ($items as $item) {            
            if ($item->object == 'post'
                || $item->object == 'page'
            ) {
                $object = get_post($item->object_id);
              
                if ($object !== null) {
                    $post = $this->_getPost($object);
                }
   
                if ($post !== null) {
                    if (isset($post->isLocked)) {
                        $item->title = $post->post_title;
                    }

                    $item->title .= $this->adminOutput(
                        $item->object, 
                        $item->object_id
                    );
                    
                    $showItems[] = $item;
                }
            } elseif ($item->object == 'category') {
                $object = get_category($item->object_id);
                $category = $this->_getTerm('category', $object);

                if ($category !== null
                    && !$category->isEmpty
                ) {
                    $item->title .= $this->adminOutput(
                        $item->object, 
                        $item->object_id
                    );
                    $showItems[] = $item;
                }
            } else {
                $showItems[] = $item;
            }
        }
        
        return $showItems;
    }
    
    /**
     * The function for the comments_array filter.
     * 
     * @param array $comments The comments.
     * 
     * @return array
     */
    public function showComment($comments = array())
    {
        $showComments = array();
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        foreach ($comments as $comment) {
            $post = get_post($comment->comment_post_ID);
            $postType = $post->post_type;
            
            if ($uamOptions['hide_'.$postType.'_comment'] == 'true' 
                || $uamOptions['hide_'.$postType] == 'true' 
                || $this->atAdminPanel()
            ) {
                if ($uamAccessHandler->checkObjectAccess($post->post_type, $post->ID)) {
                    $showComments[] = $comment;
                }
            } else {
                if (!$uamAccessHandler->checkObjectAccess($post->post_type, $post->ID)) {
                    $comment->comment_content 
                        = $uamOptions[$postType.'_comment_content'];
                }
                
                $showComments[] = $comment;
            }
        }
        
        $comments = $showComments;
        
        return $comments;
    }
    
    /**
     * The function for the get_pages filter.
     * 
     * @param array $pages The pages.
     * 
     * @return array
     */
    public function showPage($pages = array())
    {
        $showPages = array();
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        foreach ($pages as $page) {
            if ($uamOptions['hide_page'] == 'true' 
                || $this->atAdminPanel()
            ) {
                if ($uamAccessHandler->checkObjectAccess($page->post_type, $page->ID)) {
                    $page->post_title .= $this->adminOutput(
                        $page->post_type, 
                        $page->ID
                    );
                    $showPages[] = $page;
                }
            } else {
                if (!$uamAccessHandler->checkObjectAccess($page->post_type, $page->ID)) {
                    if ($uamOptions['hide_page_title'] == 'true') {
                        $page->post_title = $uamOptions['page_title'];
                    }
                    
                    $page->post_content = $uamOptions['page_content'];
                }
                
                $page->post_title .= $this->adminOutput($page->post_type, $page->ID);
                $showPages[] = $page;
            }
        }
        
        $pages = $showPages;
        
        return $pages;
    }
    
    /**
     * Modifies the content of the term by the given settings.
     * 
     * @param string $termType The type of the term.
     * @param object $term     The current term.
     * 
     * @return object
     */
    private function _getTerm($termType, $term)
    {
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        $term->isEmpty = false;
        
        $term->name .= $this->adminOutput('term', $term->term_id);
        
        if ($termType == 'post_tag'
            || $termType == 'category'
            && $uamAccessHandler->checkObjectAccess('category', $term->term_id)
        ) {
            if ($this->atAdminPanel() == false
                && ($uamOptions['hide_post'] == 'true'
                || $uamOptions['hide_page'] == 'true')
            ) {
                $termRequest = $term->term_id;
                $termRequestType = $termType;
                
                if ($termType == 'post_tag') {
                    $termRequest = $term->slug;
                    $termRequestType = 'tag';
                }
                
                $args = array(
                	'numberposts' => - 1,
                    $termRequestType => $termRequest
                );
                
                $termPosts = get_posts($args);
                $term->count = count($termPosts);
                
                if (isset($termPosts)) {
                    foreach ($termPosts as $post) {
                        if ($uamOptions['hide_'.$post->post_type] == 'true'
                            && !$uamAccessHandler->checkObjectAccess($post->post_type, $post->ID)
                        ) {
                            $term->count--;
                        }
                    }
                }
                
                //For post_tags
                if ($termType == 'post_tag'
                	&& $term->count <= 0
        	    ) {
        	        return null;
        	    }
                
        	    //For categories
                if ($term->count <= 0 
                    && $uamOptions['hide_empty_categories'] == 'true'
                    && ($term->taxonomy == "term"
                    || $term->taxonomy == "category")
                ) {
                    $term->isEmpty = true;
                }
                
                if ($uamOptions['lock_recursive'] == 'false') {
                    $curCategory = $term;
                    
                    while ($curCategory->parent != 0) {
                        $curCategory = get_term($curCategory->parent, 'category');
                        
                        if ($uamAccessHandler->checkObjectAccess('term', $curCategory->term_id)) {
                            $term->parent = $curCategory->term_id;
                            break;
                        }
                    }
                }
                
                return $term;
            } else {
                return $term;
            } 
        }
        
        return null;
    }
    
    /**
     * The function for the get_terms filter.
     * 
     * @param array $terms The terms.
     * @param array $args  The given arguments.
     * 
     * @return array
     */
    public function showTerms($terms = array(), $args = array())
    {
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        $showTerms = array();

        $uamOptions = $this->getAdminOptions();

        foreach ($terms as $term) {
            if (!is_object($term)) {
                return $terms;
            }

            if ($term->taxonomy == 'category') {
                $term = $this->_getTerm('category', $term);
            } elseif ($term->taxonomy == 'post_tag') {
                $term = $this->_getTerm('post_tag', $term);
            }

            if ($term !== null) {
                if (!isset($term->isEmpty)
                    || !$term->isEmpty
                ) {
                    $showTerms[$term->term_id] = $term;
                }
            }
        }
        
        foreach ($terms as $key => $term) {
            if (!array_key_exists($term->term_id, $showTerms)) {
                unset($terms[$key]);
            }
        }
        
        return $terms;
    }
    
    /**
     * The function for the get_previous_post_where and 
     * the get_next_post_where filter.
     * 
     * @param string $sql The current sql string.
     * 
     * @return string
     */
    public function showNextPreviousPost($sql)
    {
        $uamAccessHandler = &$this->getAccessHandler();
        $uamOptions = $this->getAdminOptions();
        
        if ($uamOptions['hide_post'] == 'true') {
            $excludedPosts = $uamAccessHandler->getExcludedPosts();
            
            if (count($excludedPosts) > 0) {
                $excludedPostsStr = implode(",", $excludedPosts);
                $sql.= " AND p.ID NOT IN($excludedPostsStr) ";
            }
        }
        
        return $sql;
    }
     
    /**
     * Returns the admin hint.
     * 
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id we want to check.
     * 
     * @return string
     */
    public function adminOutput($objectType, $objectId)
    {
        $output = "";
        
        if (!$this->atAdminPanel()) {
            $uamOptions = $this->getAdminOptions();
            
            if ($uamOptions['blog_admin_hint'] == 'true') {
                global $current_user;
                
                $curUserdata = get_userdata($current_user->ID);

                if (!isset($curUserdata->user_level)) {
                    return $output;
                }
                
                $uamAccessHandler = &$this->getAccessHandler();
                
                if ($uamAccessHandler->userIsAdmin($current_user->ID)
                    && count($uamAccessHandler->getUserGroupsForObject($objectType, $objectId)) > 0
                ) {
                    $output .= $uamOptions['blog_admin_hint_text'];
                }
            }
        }
        
        return $output;
    }
    
    /**
     * The function for the edit_post_link filter.
     * 
     * @param string  $link   The edit link.
     * @param integer $postId The id of the post.
     * 
     * @return string
     */
    public function showGroupMembership($link, $postId)
    {
        $uamAccessHandler = &$this->getAccessHandler();
        $groups = $uamAccessHandler->getUserGroupsForObject('post', $postId);
        
        if (count($groups) > 0) {
            $link .= ' | '.TXT_UAM_ASSIGNED_GROUPS.': ';
            
            foreach ($groups as $group) {
                $link .= $group->getGroupName().', ';
            }
            
            $link = rtrim($link, ', ');
        }
        
        return $link;
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
     * Returns ture if permalinks are active otherwise false.
     * 
     * @return boolean
     */
    public function isPermalinksActive()
    {
        $permaStruc = get_option('permalink_structure');
            
        if (empty($permaStruc)) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Redirects to a page or to content.
     *  
     * @param string $headers    The headers which are given from wordpress.
     * @param object $pageParams The params of the current page.
     * 
     * @return null
     */
    public function redirect($headers, $pageParams)
    {
        $uamOptions = $this->getAdminOptions();
        
        if (isset($_GET['uamgetfile'])
            && isset($_GET['uamfiletype'])
        ) {
            $fileUrl = $_GET['uamgetfile'];
            $fileType = $_GET['uamfiletype'];
            $this->getFile($fileType, $fileUrl);
        } elseif (!$this->atAdminPanel() && $uamOptions['redirect'] != 'false') {
            $object = null;
        
            if (isset($pageParams->query_vars['p'])) {
                $object = get_post($pageParams->query_vars['p']);
                $objectType = $object->post_type;
                $objectId = $object->ID;
            } elseif (isset($pageParams->query_vars['page_id'])) {
                $object = get_post($pageParams->query_vars['page_id']);
                $objectType = $object->post_type;
                $objectId = $object->ID;
            } elseif (isset($pageParams->query_vars['cat_id'])) {
                $object = get_category($pageParams->query_vars['cat_id']);
                $objectType = 'category';
                $objectId = $object->term_id;
            }
            
            if ($object === null
                ||$object !== null
                && !$this->getAccessHandler()->checkObjectAccess($objectType, $objectId)
            ) {
                $this->redirectUser($object);
            }
        }
    }
    
    /**
     * Returns the current url.
     * 
     * @return string
     */
    public function getCurrentUrl()
    { 
        if (!isset($_SERVER['REQUEST_URI'])) {
            $serverrequri = $_SERVER['PHP_SELF']; 
        } else { 
            $serverrequri = $_SERVER['REQUEST_URI']; 
        } 
        
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocolArray = explode("/", strtolower($_SERVER["SERVER_PROTOCOL"]));
        $protocol = $protocolArray[0].$s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        
        $fullUrl = $protocol."://".$_SERVER['SERVER_NAME'].$port.$serverrequri;
        
        return $fullUrl; 
    }
    
    /**
     * Redirects the user to his destination.
     * 
     * @param object $object The current object we want to access.
     * 
     * @return null
     */
    public function redirectUser($object = null)
    {
        global $wp_query;
        
        $postToShow = false;
        $posts = $wp_query->get_posts();
        
        if ($object === null
            && isset($posts)
        ) {
            foreach ($posts as $post) {
                if ($this->getAccessHandler()->checkObjectAccess($post->post_type, $post->ID)) {
                    $postToShow = true;
                    break;
                }
            }
        }
        
        if (!$postToShow) {
            $uamOptions = $this->getAdminOptions();

            if ($uamOptions['redirect'] == 'blog') {
                $url = home_url('/');
            } elseif ($uamOptions['redirect'] == 'custom_page') {
                $post = get_post($uamOptions['redirect_custom_page']);
                $url = $post->guid;
            } elseif ($uamOptions['redirect'] == 'custom_url') {
                $url = $uamOptions['redirect_custom_url'];
            }

            if ($url != $this->getCurrentUrl()) {
                wp_redirect($url);
                exit;
            }      
        }
    }
    
    /**
     * Delivers the content of the requestet file.
     * 
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl  The file url.
     * 
     * @return null
     */
    public function getFile($objectType, $objectUrl) 
    {
        $object = $this->_getFileSettingsByType($objectType, $objectUrl);

        if ($object === null) {
            return null;
        }
        
        $file = null;
        
        if ($this->getAccessHandler()->checkObjectAccess($object->type, $object->id)) {
            $file = $object->file;
        } elseif ($object->isImage) {
    		$file = UAM_REALPATH.'gfx/noAccessPic.png';
        } else {
            wp_die(TXT_UAM_NO_RIGHTS);
        }
        
        //Deliver content
        if (file_exists($file)) {
            $fileName = basename($file);
            
            /*
             * This only for compatibility
             * mime_content_type has been deprecated as the PECL extension Fileinfo 
             * provides the same functionality (and more) in a much cleaner way.
             */
            $ext = strtolower(array_pop(explode('.', $fileName)));
            
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME);
                $fileMimeType = finfo_file($finfo, $file);
                finfo_close($finfo);
            } elseif (function_exists('mime_content_type')) {
                $fileMimeType = mime_content_type($file);
            } elseif (array_key_exists($ext, $this->mimeTypes)) {
                $fileMimeType = $this->mimeTypes[$ext];
            } else {
                $fileMimeType = 'application/octet-stream';
            }
            
            header('Content-Description: File Transfer');
            header('Content-Type: '.$fileMimeType);
            
            if (!$object->isImage) {
                $baseName = str_replace(' ', '_', basename($file));
                
                header('Content-Disposition: attachment; filename="'.$baseName.'"');
            }
           
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: '.filesize($file));

            $uamOptions = $this->getAdminOptions();
            
            if ($uamOptions['download_type'] == 'fopen'
                && !$object->isImage
            ) {
                $fp = fopen($file, 'r');
                
                //TODO find better solution (prevent '\n' / '0A')
                ob_clean();
                flush();
                
                while (!feof($fp)) {
                    if (!ini_get('safe_mode')) {
                        set_time_limit(30);
                    }
                    $buffer = fread($fp, 1024);
                    echo $buffer;
                }
                                
                exit;
            } else {
                ob_clean();
                flush();
                readfile($file);
                exit;
            }
        } else {
            wp_die(TXT_UAM_FILE_NOT_FOUND_ERROR);
        }
    }
    
    /**
     * Returns the file object by the given type and url.
     * 
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl  The file url.
     * 
     * @return object|null
     */
    private function _getFileSettingsByType($objectType, $objectUrl)
    {
        $object = null;

        if ($objectType == 'attachment') {
            $uploadDir = wp_upload_dir();

            $multiPath = str_replace(ABSPATH, '/', $uploadDir['basedir']);
            $multiPath = str_replace('/files', $multiPath, $uploadDir['baseurl']);
            
            if ($this->isPermalinksActive()) {
                //TODO Remove if not needed.
                //$objectUrl = $uploadDir['baseurl'].'/'.$objectUrl;
                $objectUrl = $multiPath.'/'.$objectUrl;
            }
            
            $post = get_post($this->getPostIdByUrl($objectUrl));
    
            if ($post !== null
                && $post->post_type == 'attachment' 
            ) {
                $object->id = $post->ID;
                $object->isImage = wp_attachment_is_image($post->ID);
                $object->type = $objectType;

                //TODO Remove if not needed.
                /*$object->file = $uploadDir['basedir'].str_replace(
                    $uploadDir['baseurl'], 
                    '', 
                    $objectUrl
                );*/
                
                $object->file = $uploadDir['basedir'].str_replace(
                    $multiPath, 
                    '', 
                    $objectUrl
                );
            }
        } else {
            $plObject = $this->getAccessHandler()->getPlObject($objectType);
            
            if (isset($plObject)
                && isset($plObject['getFileObject'])
            ) {
                $object = $plObject['reference']->{$plObject['getFileObject']}(
                    $objectUrl
                );
            }
        }
        
        return $object;
    }
    
    /**
     * Returns the url for a locked file.
     * 
     * @param string  $url The base url.
     * @param integer $id  The id of the file.
     * 
     * @return string
     */
    public function getFileUrl($url, $id)
    {
        $uamOptions = $this->getAdminOptions();
            
        if (!$this->isPermalinksActive()
            && $uamOptions['lock_file'] == 'true'
        ) {
            $post = &get_post($id);

            $type = explode("/", $post->post_mime_type);
            $type = $type[1];

            $fileTypes = explode(
            	",", 
                $uamOptions['locked_file_types']
            );
            
            if ($uamOptions['lock_file_types'] == 'all'
                || in_array($type, $fileTypes) 
            ) {
                $url = home_url('/').'?uamfiletype=attachment&uamgetfile='.$url;
            }
        }
        
        return $url;
    }
    
    /**
     * Returns the post by the given url.
     * 
     * @param string $url The url of the post(attachment).
     * 
     * @return object The post.
     */
    public function getPostIdByUrl($url)
    {
        if (isset($this->postUrls[$url])) {
            return $this->postUrls[$url];
        }
        
        $this->postUrls[$url] = null;
        
        //Filter edit string
        $newUrl = preg_split("/-e[0-9]{1,}/", $url);

        if (count($newUrl) == 2) {
            $newUrl = $newUrl[0].$newUrl[1];
        } else {
            $newUrl = $newUrl[0];
        }
        
        //Filter size
        $newUrl = preg_split("/-[0-9]{1,}x[0-9]{1,}/", $newUrl);

        if (count($newUrl) == 2) {
            $newUrl = $newUrl[0].$newUrl[1];
        } else {
            $newUrl = $newUrl[0];
        }
        
        global $wpdb;
        $dbPost = $wpdb->get_row(
        	"SELECT ID
			FROM ".$wpdb->prefix."posts
			WHERE guid = '" . $newUrl . "'
			LIMIT 1"
        );
        
        if ($dbPost) {
            $this->postUrls[$url] = $dbPost->ID;
        }
        
        return $this->postUrls[$url];
    }
    
    /**
     * Caches the urls for the post for a later lookup.
     * 
     * @param string $url  The url of the post.
     * @param object $post The post object.
     * 
     * @return null
     */
    public function cachePostLinks($url, $post)
    {
        $this->postUrls[$url] = $post->ID;
        return $url;
    }
}