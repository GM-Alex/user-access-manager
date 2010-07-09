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
    var $atAdminPanel = false;
    protected $adminOptionsName = "uamAdminOptions";
    protected $uamVersion = 1.0;
    protected $uamDbVersion = 1.1;
    protected $adminOptions;
    protected $accessHandler = null;
    
    /**
     * Consturctor
     * 
     * @return null
     */
    function UserAccessManager()
    {

    }
    
    /**
     * Creates the needed tables at the database
     * 
     * @return null;
     */
    function install()
    {
        $this->createHtaccess();
        $this->createHtpasswd();
        global $wpdb;
        $uamDbVersion = $this->uam_db_version;
        include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = '';
        
        if (version_compare(mysql_get_server_info(), '4.1.0', '>=')) {
            if (!empty($wpdb->charset)) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            
            if (!empty($wpdb->collate)) {
                $charset_collate.= " COLLATE $wpdb->collate";
            }
        }
        
        $dbUserGroup = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '" . DB_ACCESSGROUP . "'"
        );
        
        if ($dbUserGroup != DB_ACCESSGROUP) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP . " (
					  ID int(11) NOT NULL auto_increment,
					  groupname tinytext NOT NULL,
					  groupdesc text NOT NULL,
					  read_access tinytext NOT NULL,
					  write_access tinytext NOT NULL,
					  ip_range mediumtext NULL,
					  PRIMARY KEY  (ID)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        $dbUserGroupToPost = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '" . DB_ACCESSGROUP_TO_POST . "'"
        );
        
        if ($dbUserGroupToPost != DB_ACCESSGROUP_TO_POST) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_POST . " (
					  post_id int(11) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (post_id,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        $dbUserGroupToUser = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '" . DB_ACCESSGROUP_TO_USER . "'"
        );
        
        if ($dbUserGroupToUser != DB_ACCESSGROUP_TO_USER) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_USER . " (
					  user_id int(11) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (user_id,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        $dbUserGroupToCategory = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '" . DB_ACCESSGROUP_TO_CATEGORY . "'"
        );
        
        if ($dbUserGroupToCategory != DB_ACCESSGROUP_TO_CATEGORY) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_CATEGORY . " (
					  category_id int(11) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (category_id,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        $dbUserGroupToRole = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '" . DB_ACCESSGROUP_TO_ROLE . "'"
        );
        
        if ($dbUserGroupToRole != DB_ACCESSGROUP_TO_ROLE) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_ROLE . " (
					  role_name varchar(255) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (role_name,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        add_option("uam_db_version", $uamDbVersion);
    }
    
    /**
     * Updates the database if an old version was installed.
     * 
     * @return null;
     */
    function update()
    {
        global $wpdb;
        $currentDbVersion = get_option("uam_db_version");
        
        if (empty($currentDbVersion)) {
            $this->install();
        }
        
        if (!get_option('uam_version')
            || get_option('uam_version') < $this->uamVersion
        ) {
            update_option('uam_version', $this->uamVersion);
            
            delete_option('allow_comments_locked');
        }
        
        $dbUserGroup = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '" . DB_ACCESSGROUP . "'"
        );
        
        if ($currentDbVersion != $this->uamDbVersion) {
            if ($currentDbVersion == 1.0) {
                
                
                if ($dbUserGroup == DB_ACCESSGROUP) {
                    $wpdb->query(
                    	"ALTER TABLE " . DB_ACCESSGROUP . " 
                    	ADD read_access TINYTEXT NOT NULL DEFAULT '', 
                    	ADD write_access TINYTEXT NOT NULL DEFAULT '', 
                    	ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                    );
                    
                    $wpdb->query(
                    	"UPDATE " . DB_ACCESSGROUP . " 
                    	SET read_access = 'group', 
                    		write_access = 'group'"
                    );
                    
                    update_option('uam_db_version', $this->uamDbVersion);
                }
            }
        }
        
        if ($dbUserGroup == DB_ACCESSGROUP) {
            $dbIpRange = $wpdb->get_var(
            	"SHOW columns 
            	FROM " . DB_ACCESSGROUP . " 
            	LIKE 'ip_range'"
            );
            
            if ($dbIpRange != 'ip_range') {
                $wpdb->query(
                	"ALTER TABLE " . DB_ACCESSGROUP . " 
                	ADD ip_range MEDIUMTEXT NULL DEFAULT ''"
                );
            }
        }
    }
    
    /**
     * Clean up wordpress if the plugin will be uninstalled.
     * 
     * @return null
     */
    function uninstall()
    {
        global $wpdb;
        $wpdb->query(
        	"DROP TABLE " . DB_ACCESSGROUP . ", 
        		" . DB_ACCESSGROUP_TO_POST . ", 
        		" . DB_ACCESSGROUP_TO_USER . ", 
        		" . DB_ACCESSGROUP_TO_CATEGORY . ", 
        		" . DB_ACCESSGROUP_TO_ROLE
        );
        
        delete_option($this->adminOptionsName);
        delete_option('uam_version');
        delete_option('uam_db_version');
        $this->deleteHtaccessFiles();
    }
    
    /**
     * Remove the htaccess file if the plugin is deactivated.
     * 
     * @return null
     */
    function deactivate()
    {
        $this->deleteHtaccessFiles();
    }
    
    /**
     * Creates a htaccess file.
     * 
     * @return null.
     */
    function createHtaccess()
    {
        // Make .htaccess file to protect data
        // get url

        $wud = wp_upload_dir();
        if (empty($wud['error'])) {
            $dir = $wud['basedir'] . "/";
            $permaStruc = get_option('permalink_structure');
            
            if (empty($permaStruc)) {
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
                $htaccessTxt .= "RewriteRule (.*) ".$homeRoot."index.php?getfile=$1 [L]\n";
                $htaccessTxt .= "</IfModule>\n";
            }
            
            // save files
            $htaccess = fopen($dir . ".htaccess", "w");
            fwrite($htaccess, $htaccessTxt);
            fclose($htaccess);
        }
    }
    
	/**
     * Creates a htpasswd file.
     * 
     * @param boolean $createNew Force to create new file.
     * 
     * @return null
     */
    function createHtpasswd($createNew = false)
    {
        global $current_user;
        $uamOptions = $this->getAdminOptions();

        // get url
        $wud = wp_upload_dir();
        if (empty($wud['error'])) {
            $url = $wud['basedir'] . "/";
            $curUserdata = get_userdata($current_user->ID);
            $user = $curUserdata->user_login;
            
            if (!file_exists($url . ".htpasswd") || $createNew) {
                if ($uamOptions['file_pass_type'] == 'random') {
                    // create password
                    $array = array();
                    $length = 10;
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
                        $password = md5($password);
                    }
                } elseif ($uamOptions['file_pass_type'] == 'admin') {
                    $password = $curUserdata->user_pass;
                }

                // make .htpasswd
                $htpasswd_txt = "$user:" . $password . "\n";

                // save file
                $htpasswd = fopen($url . ".htpasswd", "w");
                fwrite($htpasswd, $htpasswd_txt);
                fclose($htpasswd);
            }
        }
    }
    
    /**
     * Deletes the htaccess files.
     * 
     * @return null
     */
    function deleteHtaccessFiles()
    {
        $wud = wp_upload_dir();
        if (empty($wud['error'])) {
            $url = $wud['basedir'] . "/";
            
            if (file_exists($url.".htaccess")) {
                unlink($url.".htaccess");
            }
            
            if (file_exists($url.".htpasswd")) {
                unlink($url.".htpasswd");
            }
        }
    }
    
    /**
     * Returns the current settings
     * 
     * @return array
     */
    function getAdminOptions()
    {
        if ($this->atAdminPanel || empty($this->adminOptions)) {
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
            	'lock_file' => 'true', 
            	'file_pass_type' => 'random', 
            	'lock_file_types' => 'all', 
            	'download_type' => 'fopen', 
            	'locked_file_types' => 'zip,rar,tar,gz,bz2', 
            	'not_locked_file_types' => 'gif,jpg,jpeg,png', 
            	'blog_admin_hint' => 'true', 
            	'blog_admin_hint_text' => '[L]',
            	'hide_empty_categories' => 'true', 
            	'protect_feed' => 'true', 
            	'showPost_content_before_more' => 'false', 
            	'full_access_level' => 10
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
     * @param string  $fileName The file name
     * @param integer $id       The id if needed.
     * 
     * @return string
     */
    function getIncludeContents($fileName, $id = null) 
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
    function &getAccessHandler()
    {
        if ($this->accessHandler == null) {
            $this->accessHandler = new UamAccessHandler(&$this);
        }
        
        return $this->accessHandler;
    }
    
    
    /*
     * Functions for the admin panel content.
     */
    
    /**
     * The function for the wp_print_styles action.
     * 
     * @return null
     */
    function addStyles()
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
    function addScripts()
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
    function printAdminPage()
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
    function noRightsToEditContent()
    {
        $noRights = false;
        
        if (isset($_GET['post'])) {
            $noRights 
                = !$this->getAccessHandler()->checkAccess($_GET['post']); 
        }
        
        if (isset($_GET['attachment_id']) && !$noRights) {
            $noRights 
                = !$this->getAccessHandler()->checkAccess($_GET['attachment_id']);
        }
        
        if (isset($_GET['tag_ID']) && !$noRights) {
            $noRights 
                = !$this->getAccessHandler()->checkCategoryAccess($_GET['tag_ID']);
        }

        if ($noRights) {
            wp_die(TXT_NO_RIGHTS);
        }
    }
    
    /**
     * The function for the wp_dashboard_setup action.
     * Removes widgets to which a user should not have access.
     * 
     * @return null
     */
    function setupAdminDashboard()
    {
        global $wp_meta_boxes;
        
        if (!$this->getAccessHandler()->checkUserAccess()) {
            unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
        }
    }
    
    /**
     * The function for the update_option_permalink_structure action.
     * 
     * @return null
     */
    function updatePermalink()
    {
        $this->createHtaccess();
    }
    
    /**
     * The function for the manage_posts_columns and 
     * the manage_pages_columns filter.
     * 
     * @param array $defaults The table headers.
     * 
     * @return array
     */
    function addPostColumnsHeader($defaults)
    {
        $defaults['uam_access'] = __('Access');
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
    function addPostColumn($columnName, $id)
    {
        if ($columnName == 'uam_access') {
            echo $this->getIncludeContents(UAM_REALPATH.'tpl/postColumn.php', $id);
        }
    }
    
    /**
     * The function for the uma_post_access metabox.
     * 
     * @param object $post The post.
     * 
     * @return null;
     */
    function editPostContent($post)
    {
        include UAM_REALPATH.'tpl/postEditForm.php';
    }
    
    /**
     * The function for the save_post and the add_attachment action.
     * 
     * @param mixed $postParam The post id or a array of a post.
     * 
     * @return object
     */    
    function savePostData($postParam)
    {
        $uamAccessHandler = &$this->getAccessHandler();
        
        if ($uamAccessHandler->checkUserAccess()) {        
            if (is_array($postParam)) {
                $post = get_post($postParam['ID']);
            } else {
                $post = get_post($postParam);
            }

            if ($post->post_type == 'revision') {
                $postId = $post->post_parent;
                $post = get_post($postId);
            } else {
                $postId = $post->ID;
            }
            
            if ($post->post_type == 'post') {
                $postType = 'Post';
            } elseif ($post->post_type == 'page') {
                $postType = 'Page';
            } elseif ($post->post_type == 'attachment') {
                $postType = 'File';
            }
            
            $userGroupsForPost = $uamAccessHandler->getUserGroupsForPost($postId);
            
            foreach ($userGroupsForPost as $uamUserGroup) {
                $uamUserGroup->{'remove'.$postType}($postId);
                $uamUserGroup->save();
            }
            
            if (isset($_POST['usergroups'])) {
                $userGroups = $_POST['usergroups'];
            }
            
            if (isset($userGroups)) {
                foreach ($userGroups as $userGroupId) {
                    $uamUserGroup = $uamAccessHandler->getUserGroups($userGroupId);
    
                    $uamUserGroup->{'add'.$postType}($postId);
                    $uamUserGroup->save();
                }
            }
        }
    }

    /**
     * The function for the delete_post action.
     * 
     * @param integer $postId The post id.
     * 
     * @return null
     */
    function removePostData($postId)
    {
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
        	WHERE post_id = $postId"
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
    function showMediaFile($meta = '', $post = null)
    {
        $content = $meta;
        $content .= '</td></tr><tr>';
        $content .= '<th class="label">';
        $content .= '<label>'.TXT_SET_UP_USERGROUPS.'</label>';
        $content .= '</th>';
        $content .= '<td class="field">';
        $content .= $this->getIncludeContents(UAM_REALPATH.'tpl/postEditForm.php');
        
        return $content;
    }
    
    /**
     * The function for the manage_users_columns filter.
     * 
     * @param array $defaults The table headers.
     * 
     * @return array
     */
    function addUserColumnsHeader($defaults)
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
    function addUserColumn($empty, $columnName, $id)
    {
        if ($columnName == 'uam_access') {
            return $this->getIncludeContents(
                UAM_REALPATH.'tpl/userColumn.php', 
                $id
            );
        }
    }
    
    /**
     * The function for the edit_user_profile action.
     * 
     * @return null
     */
    function showUserProfile()
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
    function saveUserData($userId)
    {        
        $uamAccessHandler = &$this->getAccessHandler();
        
        if ($uamAccessHandler->checkUserAccess()) {
            if ($uamAccessHandler->checkUserAccess()) {
                $userGroupsForPost 
                    = $uamAccessHandler->getUserGroupsForUser($userId);
                
                foreach ($userGroupsForPost as $uamUserGroup) {
                    $uamUserGroup->removeUser($userId);
                    $uamUserGroup->save();
                }
                
                if (isset($_POST['usergroups'])) {
                    $userGroups = $_POST['usergroups'];
                }
                
                if (isset($userGroups)) {
                    foreach ($userGroups as $userGroupId) {
                        $uamUserGroup 
                            = $uamAccessHandler->getUserGroups($userGroupId);
        
                        $uamUserGroup->addUser($userId);
                        $uamUserGroup->save();
                    }
                }
            }
        }
    }
    
    /**
     * The function for the delete_user action.
     * 
     * @param integer $userId The user id.
     * 
     * @return null
     */
    function removeUserData($userId)
    {
        global $wpdb;

        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
        	WHERE user_id = $userId"
        );
    }
    
    /**
     * The function for the manage_categories_columns filter.
     * 
     * @param array $defaults The table headers.
     * 
     * @return array
     */
    function addCategoryColumnsHeader($defaults)
    {
        $defaults['uam_access'] = __('Access');
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
    function addCategoryColumn($empty, $columnName, $id)
    {
        if ($columnName == 'uam_access') {
            return $this->getIncludeContents(
                UAM_REALPATH.'tpl/categoryColumn.php', 
                $id
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
    function showCategoryEditForm($category)
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
    function saveCategoryData($categoryId)
    {
        $uamAccessHandler = &$this->getAccessHandler();
        
        if ($uamAccessHandler->checkUserAccess()) {
            $userGroupsForPost 
                = $uamAccessHandler->getUserGroupsForCategory($categoryId);
            
            foreach ($userGroupsForPost as $uamUserGroup) {
                $uamUserGroup->removeCategory($categoryId);
                $uamUserGroup->save();
            }
            
            if (isset($_POST['usergroups'])) {
                $userGroups = $_POST['usergroups'];
            }
            
            if (isset($userGroups)) {
                foreach ($userGroups as $userGroupId) {
                    $uamUserGroup = $uamAccessHandler->getUserGroups($userGroupId);
    
                    $uamUserGroup->addCategory($categoryId);
                    $uamUserGroup->save();
                }
            }
        }
    }
    
    /**
     * The function for the delete_category action.
     * 
     * @param integer $categoryId The id of the category.
     * 
     * @return null
     */
    function removeCategoryData($categoryId)
    {
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " 
        	WHERE category_id = $categoryId"
        );
    }

    
    /*
     * Functions for the blog content.
     */
    
    /**
     * Modifies the content of the post by the given settings.
     * 
     * @param object $post The current post.
     * 
     * @return object
     */
    function _getPost($post)
    {
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        $postType = $post->post_type;
                
        if ($postType == 'attachment') {
            $postType = 'post';
        }
        
        if ($uamOptions['hide_'.$postType] == 'true'
            || $this->atAdminPanel
        ) {
            if ($uamAccessHandler->checkAccess($post->ID)) {
                $post->post_title .= $this->adminOutput($post->ID);
                
                return $post;
            }
        } else {
            if (!$uamAccessHandler->checkAccess($post->ID)) {
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

                if ($uamOptions['showPost_content_before_more'] == 'true'
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
            
            $post->post_title .= $this->adminOutput($post->ID);
            
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
    function showPost($posts = array())
    {
        $showPosts = array();
        $uamOptions = $this->getAdminOptions();
        
        if (!is_feed() 
            || ($uamOptions['protect_feed'] == 'true' && is_feed())
        ) {
            foreach ($posts as $post) {
                $post = $this->_getPost($post);
                
                if ($post !== null) {
                    $showPosts[] = $post;
                }
            }
            
            $posts = $showPosts;
        }
        
        return $posts;
    }
    
    /**
     * The function for the wp_get_nav_menu_items filter.
     * 
     * @param array $items The menu item.
     * 
     * @return array
     */
    function showCustomMenu($items)
    {
        $showItems = array();
        
        foreach ($items as $item) {            
            if ($item->object == 'post'
                || $item->object == 'page'
            ) {
                $object = get_post($item->object_id);
                $post = $this->_getPost($object);
   
                if ($post !== null) {
                    $item->title = $post->post_title;
                    
                    $showItems[] = $item;
                }
            } elseif ($item->object == 'category') {
                $object = get_category($item->object_id);
                $post = $this->_getCategory($object);
   
                //TODO look if it really works
                if ($post !== null) {
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
    function showComment($comments = array())
    {
        $showComments = null;
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        foreach ($comments as $comment) {
            $post = get_post($comment->comment_post_ID);
            $postType = $post->post_type;
            
            if ($uamOptions['hide_'.$postType.'_comment'] == 'true' 
                || $uamOptions['hide_'.$postType] == 'true' 
                || $this->atAdminPanel
            ) {
                if ($uamAccessHandler->checkAccess($post->ID)) {
                    $showComments[] = $comment;
                }
            } else {
                if (!$uamAccessHandler->checkAccess($post->ID)) {
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
    function showPage($pages = array())
    {
        $showPages = null;
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        foreach ($pages as $page) {
            if ($uamOptions['hide_page'] == 'true' 
                || $this->atAdminPanel
            ) {
                if ($uamAccessHandler->checkAccess($page->ID)) {
                    $page->post_title.= $this->adminOutput($page->ID);
                    $showPages[] = $page;
                }
            } else {
                if (!$uamAccessHandler->checkAccess($page->ID)) {
                    if ($uamOptions['hide_page_title'] == 'true') {
                        $page->post_title = $uamOptions['page_title'];
                    }
                    
                    $page->post_content = $uamOptions['page_content'];
                }
                
                $page->post_title.= $this->adminOutput($page->ID);
                $showPages[] = $page;
            }
        }
        
        $pages = $showPages;
        
        return $pages;
    }
    
    /**
     * Modifies the content of the category by the given settings.
     * 
     * @param object $category The current category.
     * 
     * @return object
     */
    function _getCategory($category)
    {
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        $category->isEmpty = false;
        
        if ($uamAccessHandler->checkCategoryAccess($category->term_id)) {
            if ($uamOptions['hide_post'] == 'true'
                || $uamOptions['hide_page'] == 'true'
            ) {
                $args = array(
                	'numberposts' => - 1,
                    'category' => $category->term_id
                );
                $categoryPosts = get_posts($args);
                
                if (isset($categoryPosts)) {
                    foreach ($categoryPosts as $post) {
                        if ($uamOptions['hide_'.$post->post_type] == 'true'
                            && !$uamAccessHandler->checkAccess($post->ID)
                        ) {
                            $category->count--;   
                        }
                    }
                }
                
                if ($category->count !=0
                    || $category->taxonomy == "link_category" 
                    || $category->taxonomy == "post_tag" 
                    || ($uamOptions['hide_empty_categories'] == 'false' 
                    && $this->atAdminPanel == false)
                ) {
                    return $category;
                } elseif ($category->count == 0) {
                    $category->isEmpty = true;
                    return $category;
                }   
            } else {
                return $category;
            } 
        }
        
        return null;
    }
    
    /**
     * The function for the get_terms filter.
     * 
     * @param array $categories The categories.
     * 
     * @return array
     */
    function &showCategory($categories = array())
    {
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        $showCategories = array();

        $uamOptions = $this->getAdminOptions();

        foreach ($categories as $category) {
            if (!is_object($category)) {
                $category = get_category($category);
                
                if (!isset($category->term_id)) {
                    $category->term_id = null;
                }
            }

            $category = $this->_getCategory($category);

            if ($category !== null) {
                if (!$category->isEmpty) {
                    $showCategories[$category->term_id] = $category;
                } else {
                    $emptyCategories[$category->term_id] = $category;
                }
            }
            
            if ($uamOptions['hide_empty_categories'] == 'true'
                && isset($showCategories)
                && isset($emptyCategories)
            ) {
                foreach ($showCategories as $showCategory) {
                    $curCategory = $showCategory;
                
                    while ($curCategory->parent != 0 
                           && isset($emptyCategories)
                    ) {
                        $showCategories[$showCategory->parent] 
                            = $emptyCategories[$curCategory->parent];
                        $showCategories[$showCategory->parent]->count 
                            = $showCategory->count;
                        unset($emptyCategories[$curCategory->parent]);
                        
                        $curCategory = & get_category($curCategory->parent);
                    }
                }
            }
        }

        /* Don't know why this don't work.
         * Sets wp_option category_children on an empty value, but why?
         * Reason could be locatet at clean_term_cache() function in
         * taxonomy.php line 2120
         * 
         * $categories = array();
         * 
         * foreach ($showCategories as $showCategory) {
         *     $categories[$i] = $showCategory;
         * }
         */
        
        $catCount = count($categories);
        $i = 0;
        
        foreach ($showCategories as $showCategory) {
            $categories[$i] = $showCategory;
            $i++;
        }
        
        for ($i; $i < $catCount; $i++) {
            unset($categories[$i]);
        }
        
        return $categories;
    }
    
    /**
     * The function for the get_the_title filter.
     * 
     * @param string $title  The title of the post.
     * @param object $postId The post id.
     * 
     * @return string
     */
    function showTitle($title, $postId = null)
    {
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        $post = get_post($postId);
        $postType = $post->post_type;
        
        if (!$uamAccessHandler->checkAccess($postId) 
            && $post != null 
            && $uamOptions['hide_'.$postType.'_title'] == 'true'
        ) {
            $title = $uamOptions[$postType.'_title'];
        }
        
        return $title;
    }
    
    /**
     * The function for the get_previous_post_where and 
     * the get_next_post_where filter.
     * 
     * @param string $sql The current sql string.
     * 
     * @return string
     */
    function showNextPreviousPost($sql)
    {
        $uamOptions = $this->getAdminOptions();
        
        if ($uamOptions['hide_post'] == 'true') {
            $posts = get_posts();
            $uamAccessHandler = &$this->getAccessHandler();
            
            if (isset($posts)) {
                foreach ($posts as $post) {
                    if (!$uamAccessHandler->checkAccess($post->ID)) {
                        $excludedPosts[] = $post->ID;
                    }
                }
                
                if (isset($excludedPosts)) {
                    $excludedPostsStr = implode(",", $excludedPosts);
                    $sql.= "AND ID NOT IN($excludedPostsStr)";
                }
            }
        }
        return $sql;
    }
    
    /**
     * The function for the posts_where filter.
     * 
     * @param string $sql The current sql string.
     * 
     * @return string
     */
    function showPostSql($sql)
    {
        $uamOptions = $this->getAdminOptions();
        
        if (($uamOptions['hide_post'] == 'true' && !is_feed()) 
            || (is_feed() && $uamOptions['protect_feed'] == 'true')
        ) {
            $posts = get_posts();
            $uamAccessHandler = &$this->getAccessHandler();
            
            if (isset($posts)) {
                foreach ($posts as $post) {
                    if (!$uamAccessHandler->checkAccess($post->ID)) {
                        $excludedPosts[] = $post->ID;
                    }
                }
                
                if (isset($excludedPosts)) {
                    $excludedPostsStr = implode(",", $excludedPosts);
                    $sql.= "AND ID NOT IN($excludedPostsStr)";
                }
            }
        }
        
        return $sql;
    }
    
    /**
     * Returns the admin hint.
     * 
     * @param integer $postId The post id we want to check.
     * 
     * @return string
     */
    function adminOutput($postId)
    {
        $output = "";
        
        if (!$this->atAdminPanel) {
            $uamOptions = $this->getAdminOptions();
            
            if ($uamOptions['blog_admin_hint'] == 'true') {
                global $current_user;
                
                $curUserdata = get_userdata($current_user->ID);

                if (!isset($curUserdata->user_level)) {
                    return $output;
                }
                
                $uamAccessHandler = &$this->getAccessHandler();
                $groups = $uamAccessHandler->getUserGroupsForPost($postId);
                
                if ($uamAccessHandler->checkUserAccess()
                    && $groups != array()
                ) {
                    $output .= '<span class="uam_group_info_link">';
                    $output .= $uamOptions['blog_admin_hint_text'];
                    $output .= '</span>';
                    $output .= '<div class="tooltip">';
                     $output .= '<b>'.TXT_ASSIGNED_GROUPS.':</b>&nbsp;';
                    
                    foreach ($groups as $group) {
                        $output .= $group->getGroupName().', ';
                    }
                    
                    $output = rtrim($output, ', ');
                    
                    $output .= '</div>';
                }
            }
        }
        
        return $output;
    }
    
    /**
     * Returns the login bar.
     * 
     * @return string
     */
    function getLoginBarHtml()
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
     * Redirects to a page or to content.
     * 
     * @return null
     */
    function redirect()
    {
        $uamOptions = $this->getAdminOptions();
        
        if (isset($_GET['getfile'])) {
            $fileUrl = $_GET['getfile'];
        }
        
        $emptyId = null;
        $post = get_post($emptyId);
        
        //|| (!$this->getAccessHandler()->checkAccess($fileId) && !wp_attachment_is_image($fileId) && isset($fileId)))
        
        if ($uamOptions['redirect'] != 'false' 
            && !$this->getAccessHandler()->checkAccess($post->ID) 
            && !$this->atAdminPanel 
            && !isset($fileUrl)
        ) {
            $this->redirectUser();
        } elseif (isset($fileUrl)) {
            $permaStruc = get_option('permalink_structure');
            
            if (!empty($permaStruc)) {
                $uploadDir = wp_upload_dir();
                $fileUrl = $uploadDir['baseurl'].'/'.$fileUrl;
            }
            
            $this->getFile($fileUrl);
        }
    }
    
    /**
     * Redirects the user to his destination.
     * 
     * @return null
     */
    function redirectUser()
    {
        global $wp_query;
        
        $postToShow = false;
        $posts = $wp_query->get_posts();
        
        if (isset($posts)) {
            foreach ($posts as $post) {
                if ($this->getAccessHandler()->checkAccess($post->ID)) {
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
            
            if ($url != "http://".$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"]) {
                wp_redirect($url);
            }      
        }
    }
    
    /**
     * Delivers the content of the requestet file.
     * 
     * @param string $url The file url.
     * 
     * @return null
     */
    function getFile($url) 
    {
        $post = get_post($this->getAttachmentIdByUrl($url));

        if ($post !== null) {
            $file = null;
        } else {
            return null;
        }
        
        if ($post->post_type == 'attachment' 
            && $this->getAccessHandler()->checkAccess($post->ID)
        ) {
            $uploadDir = wp_upload_dir();
            $file = $uploadDir['basedir'].'/'.str_replace(
                $uploadDir['baseurl'], 
                '', 
                $url
            );
        } else if (wp_attachment_is_image($post->ID)) {
    		$file = UAM_REALPATH.'gfx/noAccessPic.png';
        } else {
            wp_die(TXT_NO_RIGHTS);
        }
        
        //Deliver content
        if (file_exists($file)) {
            $fileName = basename($file);

            //This only for compatibility
            //mime_content_type has been deprecated as the PECL extension Fileinfo 
            //provides the same functionality (and more) in a much cleaner way.
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME);
        
                if (!$finfo) {
                    wp_die(TXT_FILEINFO_DB_ERROR);
                }
                
                $fileType = finfo_file($finfo, $file);
            } else {
                $fileType = mime_content_type($file);
            }
            
            header('Content-Description: File Transfer');
            header('Content-Type: '.$fileType);
            header('Content-Length: '.filesize($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            
            if (!wp_attachment_is_image($post->ID)) {
                header('Content-Disposition: attachment; filename='.basename($file));
            }

            if ($uamOptions['download_type'] == 'fopen'
                && !wp_attachment_is_image($post->ID)
            ) {
                $fp = fopen($file, 'rb');
                
                while (!feof($fp)) {
                    set_time_limit(30);
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
            wp_die(TXT_FILE_NOT_FOUND_ERROR);
        }
    }
    
    /**
     * Returns the url for a locked file.
     * 
     * @param string  $url The base url.
     * @param integer $id  The id of the file.
     * 
     * @return string
     */
    function getFileUrl($url, $id)
    {
        $uamOptions = $this->getAdminOptions();
        $permaStruc = get_option('permalink_structure');
            
        if (empty($permaStruc)
            && $uamOptions['lock_file'] == 'true'
        ) {
            $post = &get_post($id);

            $type = explode("/", $post->post_mime_type);
            $type = $type[1];

            $fileTypes = explode(
            	",", 
                $uamOptions['locked_file_types']
            );
            
            if (in_array($type, $fileTypes) 
                || $uamOptions['lock_file_types'] == 'all'
            ) {
                $url = home_url('/').'?getfile='.$url;
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
    function getAttachmentIdByUrl($url)
    {
        $newUrl = preg_split("/-[0-9]*x[0-9]*/", $url);

        if (count($newUrl) == 2) {
            $newUrl = $newUrl[0].$newUrl[1];
        } else {
            $newUrl = $newUrl[0];
        }
        
        /*$permaStruc = get_option('permalink_structure');
            
        if (!empty($permaStruc)) {
            $uploadDir = wp_upload_dir();
            $newUrl = $uploadDir['baseurl'].'/'.$newUrl;
        }*/
        
        global $wpdb;
        $dbPost = $wpdb->get_row(
        	"SELECT ID
			FROM ".$wpdb->prefix."posts
			WHERE guid = '" . $newUrl . "'
			LIMIT 1", 
            ARRAY_A
        );
        
        if ($dbPost) {
            return $dbPost['ID'];
        }
        
        return null;
    }
}