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
    protected $uamDbVersion = "1.1";
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
        $uamDbVersion = $this->uamDbVersion;
        $installed_ver = get_option("uam_db_version");
        
        if (empty($installed_ver)) {
            $this->install();
        }
        
        $dbUserGroup = $wpdb->get_var(
        	"SHOW TABLES 
        	LIKE '" . DB_ACCESSGROUP . "'"
        );
        
        if ($installed_ver != $uamDbVersion) {
            if ($installed_ver == '1.0') {
                
                
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
                    
                    update_option("uam_db_version", $uamDbVersion);
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
        delete_option("uam_db_version");
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
            $url = $wud['basedir'] . "/";
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
            $htaccess_txt = "";
            
            if ($uamOptions['lock_file_types'] == 'selected') {
                $htaccess_txt.= "<FilesMatch '\.(" . $fileTypes . ")'>\n";
            }
            
            if ($uamOptions['lock_file_types'] == 'not_selected') {
                $htaccess_txt.= "<FilesMatch '^\.(" . $fileTypes . ")'>\n";
            }
            
            $htaccess_txt.= "AuthType Basic" . "\n";
            $htaccess_txt.= "AuthName \"" . $areaname . "\"" . "\n";
            $htaccess_txt.= "AuthUserFile " . $url . ".htpasswd" . "\n";
            $htaccess_txt.= "require valid-user" . "\n";
            
            if ($uamOptions['lock_file_types'] == 'selected' 
                || $uamOptions['lock_file_types'] == 'not_selected'
            ) {
                $htaccess_txt.= "</FilesMatch>\n";
            }

            // save files
            $htaccess = fopen($url . ".htaccess", "w");
            fwrite($htaccess, $htaccess_txt);
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
            unlink($url . ".htaccess");
            unlink($url . ".htpasswd");
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
            	'hide_post_comment' => 'false', 
            	'post_comment_content' => __(
            		'Sorry no rights to view comments!', 
            		'user-access-manager'
                ), 
            	'allow_comments_locked' => 'false', 
            	'post_content' => 'Sorry no rights!', 
            	'hide_post' => 'false', 
            	'hide_page_title' => 'false', 
            	'page_title' => 'No rights!', 
            	'page_content' => __(
            		'Sorry you have no rights to view this page!', 
            		'user-access-manager'
                ), 
            	'hide_page' => 'false', 
            	'redirect' => 'false', 
            	'redirect_custom_page' => '', 
            	'redirect_custom_url' => '', 
            	'lock_recursive' => 'true', 
            	'lock_file' => 'true', 
            	'file_pass_type' => 'random', 
            	'lock_file_types' => 'all', 
            	'download_type' => 'fopen', 
            	'locked_file_types' => 'zip,rar,tar,gz,bz2', 
            	'not_locked_file_types' => 'gif,jpg,jpeg,png', 
            	'blog_admin_hint' => 'true', 
            	'blog_admin_hint_text' => '[L]', 
            	'core_mod' => 'false', 
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
        	'UserAccessManager', 
            UAM_URLPATH . "css/uma_admin.css", 
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
        	'UserAccessManager', 
            UAM_URLPATH . 'js/functions.js', 
            array('jquery'), 
            '1.0'
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
            include UAM_REALPATH."/tpl/adminSettings.php";
        } elseif ($curAdminPage == 'uam_usergroup') {
            include UAM_REALPATH."/tpl/adminGroup.php";
        } elseif ($curAdminPage == 'uam_setup') {
            include UAM_REALPATH."/tpl/adminSetup.php";
        }
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
            echo $this->getIncludeContents(UAM_REALPATH.'/tpl/postColumn.php', $id);
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
        include UAM_REALPATH.'/tpl/postEditForm.php';
    }
    
    /**
     * The function for the save_post and the add_attachment action.
     * 
     * @param integer $postId The post id.
     * 
     * @return object
     */    
    function savePostData($postId)
    {
        /*global $current_user;
        
        $curUserdata = get_userdata($current_user->ID);
        $uamOptions = $this->getAdminOptions();
        
        if ($curUserdata->user_level < $uamOptions['full_access_level']) {
            $uamOptions = $this->getAdminOptions();
            $cur_categories = wp_get_post_categories($postId);
            $allowded_categories = get_categories();
            
            foreach ($cur_categories as $category) {
                foreach ($allowded_categories as $allowded_category) {
                    if ($allowded_category->term_id == $category) {
                        $post_categories[] = $allowded_category->term_id;
                        break;
                    }
                }
            }
            
            if (!isset($post_categories)) {
                $last_category = array_pop($allowded_categories);
                $post_categories[] = $last_category->term_id;
            }
            
            wp_set_post_categories($postId, $post_categories);
        }*/
        
        $post = get_post($postId);
        
        if ($post->post_parent != 0) {
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
        
        $uamAccessHandler = &$this->getAccessHandler();
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
        $content .= $this->getIncludeContents(UAM_REALPATH.'/tpl/postEditForm.php');
        
        return $content;
    }
    
	/**
     * The function for the attachment_fields_to_save action.
     * 
     * @param integer $post The post.
     * 
     * @return object
     */    
    /*function saveAttachmentData($post)
    {
        if (isset($_POST['usergroups'])) {
            $userGroups = $_POST['usergroups'];
        }
        
        if ($curUserdata->user_level >= $uamOptions['full_access_level']) {
            if (isset($userGroups)) {
                foreach ($userGroups as $userGroupId) {
                    $uamUserGroup = new uamUserGroup($userGroupId);
                    
                    $uamUserGroup->addFile($userId);
                }
            }
        }
        
        return $post;
    }*/
    
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
                UAM_REALPATH.'/tpl/userColumn.php', 
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
        echo $this->getIncludeContents(UAM_REALPATH.'/tpl/userProfileEditForm.php');
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
        /*if ($curUserdata->user_level >= $uamOptions['full_access_level']) {

        }*/
        
        $uamAccessHandler = &$this->getAccessHandler();
        $userGroupsForPost = $uamAccessHandler->getUserGroupsForUser($userId);
        
        foreach ($userGroupsForPost as $uamUserGroup) {
            $uamUserGroup->removeUser($userId);
            $uamUserGroup->save();
        }
        
        if (isset($_POST['usergroups'])) {
            $userGroups = $_POST['usergroups'];
        }
        
        if (isset($userGroups)) {
            foreach ($userGroups as $userGroupId) {
                $uamUserGroup = $uamAccessHandler->getUserGroups($userGroupId);

                $uamUserGroup->addUser($userId);
                $uamUserGroup->save();
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
        global $wpdb, $current_user;
        $curUserdata = get_userdata($current_user->ID);
        $uamOptions = $this->getAdminOptions();
        
        if ($curUserdata->user_level >= $uamOptions['full_access_level']) {
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
            	WHERE user_id = $userId"
            );
        }
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
                UAM_REALPATH.'/tpl/categoryColumn.php', 
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
        include UAM_REALPATH.'/tpl/categoryEditForm.php';
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

    /**
     * Shows the info html for the user group.
     * 
     * @param integer $groupId The group id.
     * 
     * @return string
     */
    function getUserGroupInfoHtml($groupId)
    {
        return $this->getIncludeContents(UAM_REALPATH.'/tpl/groupInfo.php');
    }

    
    /*
     * Functions for the blog content.
     */
    
    /**
     * The function for the the_posts filter.
     * 
     * @param arrray $posts The posts.
     * 
     * @return array
     */
    function showPost($posts = array())
    {
        $showPosts = null;
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        if (!is_feed() 
            || ($uamOptions['protect_feed'] == 'true' && is_feed())
        ) {
            foreach ($posts as $post) {
                $postType = $post->post_type;
                
                if ($uamOptions['hide_'.$postType] == 'true'
                    || $this->atAdminPanel
                ) {
                    if ($uamAccessHandler->checkAccess($post->ID)) {
                        $post->post_title .= $this->adminOutput($post->ID);
                        $showPosts[] = $post;
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
                        
                        if ($uamOptions['allow_comments_locked'] == 'false') {
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
                    $showPosts[] = $post;
                }
            }
            
            $posts = $showPosts;
        }
        
        return $posts;
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
            if ($uamOptions['hide_post_comment'] == 'true' 
                || $uamOptions['hide_post'] == 'true' 
                || $this->atAdminPanel
            ) {
                if ($uamAccessHandler->checkAccess($comment->comment_post_ID)) {
                    $showComments[] = $comment;
                }
            } else {
                if (!$uamAccessHandler->checkAccess($comment->comment_post_ID)) {
                    $comment->comment_content = $uamOptions['post_comment_content'];
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
     * The function for the get_terms filter.
     * 
     * @param array $categories The categories.
     * 
     * @return array
     */
    function showCategory($categories = array())
    {
        global $current_user;
        $curUserdata = get_userdata($current_user->ID);
        $uamOptions = $this->getAdminOptions();
        $uamAccessHandler = &$this->getAccessHandler();
        
        if (!isset($curUserdata->user_level)) {
            $curUserdata->user_level = null;
        }
        
        //if ($curUserdata->user_level <= $uamOptions['full_access_level']) {
        $uamOptions = $this->getAdminOptions();

        foreach ($categories as $category) {
            if (!is_object($category)) {
                $category = get_category($category);
                
                if (!isset($category->term_id)) {
                    $category->term_id = null;
                }
            }

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
                        $showCategories[$category->term_id] = $category;
                    } elseif ($category->count == 0) {
                        $emptyCategories[$category->term_id] = $category;
                    }   
                } else {
                    $showCategories[$category->term_id] = $category; 
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
        //}

        if (isset($showCategories)) {
            $categories = $showCategories;
        } else {
            $categories = array();    
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
        
        if (!$uamAccessHandler->checkAccess($postId) && $post != null) {
            $title = $uamOptions[$post->post_type.'_title'];
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
                
                if ($curUserdata->user_level >= $uamOptions['full_access_level'] 
                    && $groups != array()
                ) { 
                    return $uamOptions['blog_admin_hint_text'];
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
            return $this->getIncludeContents(UAM_REALPATH.'/tpl/loginBar.php');
        }
        
        return '';
    }
    
    
    /*
     * Functions for the redirection and files.
     */
    
    /**
     * Redirects the user to his destination.
     * 
     * @return null
     */
    function redirectUser()
    {
        global $wp_query;
        $uamOptions = $this->getAdminOptions();
        
        if (isset($_GET['getfile'])) {
            $curFileId = $_GET['getfile'];
        }
        
        if ($uamOptions['redirect'] != 'false' 
            && ((!$this->check_access() && !$this->atAdminPanel && empty($curFileId))
            || (!$this->check_access($curFileId) && !wp_attachment_is_image($curFileId) && isset($curFileId)))
        ) {
            $curId = null;
            $post = & get_post($curId);
            
            if ($uamOptions['redirect'] == 'blog') {
                $url = get_option('siteurl');
            } elseif ($uamOptions['redirect'] == 'custom_page') {
                $postToGo = & get_post($uamOptions['redirect_custom_page']);
                $url = $postToGo->guid;
            } elseif ($uamOptions['redirect'] == 'custom_url') {
                $url = $uamOptions['redirect_custom_url'];
            }
            
            $posts = $wp_query->get_posts();
            
            if (isset($posts)) {
                foreach ($posts as $post) {
                    if ($this->check_access($post->ID)) {
                        $post_to_show = true;
                        break;
                    }
                }
            }
            
            if ($url != "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"] 
                && empty($post_to_show)
            ) {
                header("Location: $url");
                exit;
            }
        } elseif (isset($_GET['getfile'])) {
            $curId = $_GET['getfile'];
            $post = & get_post($curId);
            
            if ($post->post_type == 'attachment' 
                && $this->check_access($post->ID)
            ) {
                $file = str_replace(get_option('siteurl') . '/', "", $post->guid);
                $fileName = basename($file);
                
                if (file_exists($file)) {
                    $len = filesize($file);
                    header('content-type: ' . $post->post_mime_type);
                    header('content-length: ' . $len);
                    
                    if (wp_attachment_is_image($curId)) {
                        readfile($file);
                        exit;
                    } else {
                        header('content-disposition: attachment; filename='.basename($file));
                        
                        if ($uamOptions['download_type'] == 'fopen') {
                            $fp = fopen($file, 'rb');
                            
                            while (!feof($fp)) {
                                set_time_limit(30);
                                $buffer = fread($fp, 1024);
                                echo $buffer;
                            }
                            
                            exit;
                        } else {
                            readfile($file);
                            exit;
                        }
                    }
                } else {
                    echo 'Error: File not found';
                }
            } elseif (wp_attachment_is_image($curId)) {
                $file = UAM_URLPATH . 'gfx/no_access_pic.png';
                $fileName = basename($file);
                
                if (file_exists($file)) {
                    $len = filesize($file);
                    
                    header('content-type: ' . $post->post_mime_type);
                    header('content-length: ' . $len);
                    
                    readfile($file);
                    
                    exit;
                } else {
                    echo 'Error: File not found';
                }
            }
        }
    }
    
    /**
     * Returns the url for a locked file.
     * 
     * @param string  $URL The base url.
     * @param integer $ID  The id of the file.
     * 
     * @return string
     */
    function getFile($URL, $ID)
    {
        $uamOptions = $this->getAdminOptions();
        
        if ($uamOptions['lock_file'] == 'true') {
            $curId = $ID;
            $post = & get_post($curId);
            $curParentId = $post->post_parent;
            $curParent = & get_post($curParentId);
            $type = explode("/", $post->post_mime_type);
            $type = $type[1];
            $fileTypes = $uamOptions['locked_file_types'];
            $fileTypes = explode(",", $fileTypes);
            
            if (in_array($type, $fileTypes) 
                || $uamOptions['lock_file_types'] == 'all'
            ) {
                $curGuid = get_bloginfo('url');
                $URL = $curGuid . '?getfile=' . $post->ID;
            }
        }
        
        return $URL;
    }
}