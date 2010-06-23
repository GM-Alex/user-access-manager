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
    var $adminOptionsName = "uamAdminOptions";
    var $atAdminPanel = false;
    var $restrictedPost = array();
    var $postAccess = array();
    var $postUserGroup = array();
    var $uam_db_version = "1.1";
    var $adminOptions;
    
    /**
     * Consturctor
     * 
     * @return null
     */
    function UserAccessManager()
    {

    }
    
    /**
     * Loads the language files
     * 
     * @return null
     */
    function init()
    {
        load_plugin_textdomain(
        	'user-access-manager', 
        	'wp-content/plugins/user-access-manager'
        );
        
        include_once 'includes/language.define.php';
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
        $uam_db_version = $this->uam_db_version;
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
        
        if ($wpdb->get_var("show tables like '" . DB_ACCESSGROUP . "'") != DB_ACCESSGROUP) {
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
        
        if ($wpdb->get_var("show tables like '" . DB_ACCESSGROUP_TO_POST . "'") != DB_ACCESSGROUP_TO_POST) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_POST . " (
					  post_id int(11) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (post_id,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        if ($wpdb->get_var("show tables like '" . DB_ACCESSGROUP_TO_USER . "'") != DB_ACCESSGROUP_TO_USER) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_USER . " (
					  user_id int(11) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (user_id,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        if ($wpdb->get_var("show tables like '" . DB_ACCESSGROUP_TO_CATEGORY . "'") != DB_ACCESSGROUP_TO_CATEGORY) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_CATEGORY . " (
					  category_id int(11) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (category_id,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        if ($wpdb->get_var("show tables like '" . DB_ACCESSGROUP_TO_ROLE . "'") != DB_ACCESSGROUP_TO_ROLE) {
            $sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_ROLE . " (
					  role_name varchar(255) NOT NULL,
					  group_id int(11) NOT NULL,
					  PRIMARY KEY  (role_name,group_id)
					) $charset_collate;";
            dbDelta($sql);
        }
        
        add_option("uam_db_version", $uam_db_version);
    }
    
    /**
     * Updates the database if an old version was installed.
     * 
     * @return null;
     */
    function update()
    {
        global $wpdb;
        $uam_db_version = $this->uam_db_version;
        $installed_ver = get_option("uam_db_version");
        
        if (empty($installed_ver)) {
            $this->install();
        }
        
        if ($installed_ver != $uam_db_version) {
            if ($installed_ver == '1.0') {
                if ($wpdb->get_var("SHOW TABLES LIKE '" . DB_ACCESSGROUP . "'") == DB_ACCESSGROUP) {
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
                    
                    update_option("uam_db_version", $uam_db_version);
                }
            }
        }
        
        if ($wpdb->get_var("SHOW tables LIKE '" . DB_ACCESSGROUP . "'") == DB_ACCESSGROUP) {
            if ($wpdb->get_var("SHOW columns FROM " . DB_ACCESSGROUP . " LIKE 'ip_range'") != 'ip_range') {
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
            if ($uamOptions['lock_file_types'] == 'selected') $file_types = $uamOptions['locked_file_types'];
            elseif ($uamOptions['lock_file_types'] == 'not_selected') $file_types = $uamOptions['not_locked_file_types'];
            if (isset($file_types)) $file_types = str_replace(",", "|", $file_types);

            // make .htaccess and .htpasswd
            $htaccess_txt = "";
            if ($uamOptions['lock_file_types'] == 'selected') $htaccess_txt.= "<FilesMatch '\.(" . $file_types . ")'>\n";
            if ($uamOptions['lock_file_types'] == 'not_selected') $htaccess_txt.= "<FilesMatch '^\.(" . $file_types . ")'>\n";
            $htaccess_txt.= "AuthType Basic" . "\n";
            $htaccess_txt.= "AuthName \"" . $areaname . "\"" . "\n";
            $htaccess_txt.= "AuthUserFile " . $url . ".htpasswd" . "\n";
            $htaccess_txt.= "require valid-user" . "\n";
            if ($uamOptions['lock_file_types'] == 'selected' || $uamOptions['lock_file_types'] == 'not_selected') $htaccess_txt.= "</FilesMatch>\n";

            // save files
            $htaccess = fopen($url . ".htaccess", "w");
            fwrite($htaccess, $htaccess_txt);
            fclose($htaccess);
        }
    }
    
	/**
     * Creates a htpasswd file.
     * 
     * @return null
     */
    function createHtpasswd($create_new = false)
    {
        global $current_user;
        $uamOptions = $this->getAdminOptions();

        // get url
        $wud = wp_upload_dir();
        if (empty($wud['error'])) {
            $url = $wud['basedir'] . "/";
            $cur_userdata = get_userdata($current_user->ID);
            $user = $cur_userdata->user_login;
            
            if (!file_exists($url . ".htpasswd") || $create_new) {
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
                    if ($capitals) for ($i = 65; $i < 90; $i++) {
                        $array[] = chr($i);
                    }

                    // specialchar:
                    if ($specialSigns) {
                        for ($i = 33; $i < 47; $i++) $array[] = chr($i);
                        for ($i = 59; $i < 64; $i++) $array[] = chr($i);
                        for ($i = 91; $i < 96; $i++) $array[] = chr($i);
                        for ($i = 123; $i < 126; $i++) $array[] = chr($i);
                    }
                    
                    mt_srand((double)microtime() * 1000000);
                    $password = '';
                    
                    for ($i = 1; $i <= $length; $i++) {
                        $rnd = mt_rand(0, count($array) - 1);
                        $password.= $array[$rnd];
                        $password = md5($password);
                    }
                } elseif ($uamOptions['file_pass_type'] == 'admin') {
                    $password = $cur_userdata->user_pass;
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
            $uamAdminOptions = array('hide_post_title' => 'false', 'post_title' => __('No rights!', 'user-access-manager'), 'hide_post_comment' => 'false', 'post_comment_content' => __('Sorry no rights to view comments!', 'user-access-manager'), 'allow_comments_locked' => 'false', 'post_content' => 'Sorry no rights!', 'hide_post' => 'false', 'hide_page_title' => 'false', 'page_title' => 'No rights!', 'page_content' => __('Sorry you have no rights to view this page!', 'user-access-manager'), 'hide_page' => 'false', 'redirect' => 'false', 'redirect_custom_page' => '', 'redirect_custom_url' => '', 'lock_recursive' => 'true', 'lock_file' => 'true', 'file_pass_type' => 'random', 'lock_file_types' => 'all', 'download_type' => 'fopen', 'locked_file_types' => 'zip,rar,tar,gz,bz2', 'not_locked_file_types' => 'gif,jpg,jpeg,png', 'blog_admin_hint' => 'true', 'blog_admin_hint_text' => '[L]', 'core_mod' => 'false', 'hide_empty_categories' => 'true', 'protect_feed' => 'true', 'show_post_content_before_more' => 'false', 'full_access_level' => 10);
            $uamOptions = get_option($this->adminOptionsName);
            if (!empty($uamOptions)) {
                foreach ($uamOptions as $key => $option) $uamAdminOptions[$key] = $option;
            }
            update_option($this->adminOptionsName, $uamAdminOptions);
            $this->adminOptions = $uamAdminOptions;
        }
        return $this->adminOptions;
    }

    /**
     * Prints the admin page
     * 
     * @return null
     */
    function printAdminPage()
    {
        if (isset($_GET['page'])) {
            $cur_admin_page = $_GET['page'];
        }
        
        if ($cur_admin_page == 'uam_settings') {
            include "adminSettings.php";
        } elseif ($cur_admin_page == 'uam_usergroup') {
            include "adminGroup.php";
        } elseif ($cur_admin_page == 'uam_setup') {
            include "adminSetup.php";
        }
    }

    /**
     * Returns the user groups which has access for the given post.
     * 
     * @param $ID
     * @return object
     */
    function getUsergroupsForPost($ID)
    {
        global $wpdb;
        if (isset($this->postUserGroup[$ID])) {
            return $this->postUserGroup[$ID];
        }
        
        $access = $this->get_access($ID);
        $post_usergroups = array();
        if (isset($access->restricted_by_posts) || isset($access->restricted_by_categories)) {
            if (isset($access->restricted_by_posts)) {
                foreach ($access->restricted_by_posts as $cur_id) {
                    $usergroups = $wpdb->get_results(
                    	"SELECT ag.ID, ag.groupname
						FROM " . DB_ACCESSGROUP . " ag, " . DB_ACCESSGROUP_TO_POST . " agtp
						WHERE agtp.post_id = " . $cur_id . "
							AND ag.ID = agtp.group_id
						GROUP BY ag.groupname", ARRAY_A
                    );
                    
                    if (isset($usergroups)) {
                        foreach ($usergroups as $usergroup) {
                            $cur_usergroup = null;
                            if (isset($post_usergroups[$usergroup['ID']])) {
                                $cur_usergroup = $post_usergroups[$usergroup['ID']];
                            }
                            
                            $cur_usergroup->ID = $usergroup['ID'];
                            $cur_usergroup->name = $usergroup['groupname'];
                            if ($cur_id != $ID) {
                                if (isset($cur_usergroup->posts)) {
                                    $posts = $cur_usergroup->posts;
                                }
                                
                                $lock_post = & get_post($cur_id);
                                $posts[$lock_post->ID] = $lock_post->ID;
                                $cur_usergroup->posts = $posts;
                            } else {
                                $cur_usergroup->itself = true;
                            }
                            
                            $post_usergroups[$usergroup['ID']] = $cur_usergroup;
                        }
                    }
                }
            }
            if (isset($access->restricted_by_categories)) {
                foreach ($access->restricted_by_categories as $cur_id) {
                    $usergroups = $wpdb->get_results(
                    	"SELECT ag.ID, ag.groupname
						FROM " . DB_ACCESSGROUP . " ag, 
							" . DB_ACCESSGROUP_TO_CATEGORY . " agtc
						WHERE agtc.category_id = " . $cur_id . "
							AND ag.ID = agtc.group_id
						GROUP BY ag.groupname", ARRAY_A
                    );
                    
                    if (isset($usergroups)) {
                        foreach ($usergroups as $usergroup) {
                            $cur_usergroup = null;
                            
                            if (isset($post_usergroups[$usergroup['ID']])) {
                                $cur_usergroup = $post_usergroups[$usergroup['ID']];
                            }
                            
                            $cur_usergroup->ID = $usergroup['ID'];
                            $cur_usergroup->name = $usergroup['groupname'];
                            
                            if (isset($cur_usergroup->categorie)) {
                                $categories = $cur_usergroup->categories;
                            }
                            
                            $lock_cat = & get_category($cur_id);
                            $categories[$lock_cat->term_id] = $lock_cat->term_id;
                            $cur_usergroup->categories = $categories;
                            $post_usergroups[$usergroup['ID']] = $cur_usergroup;
                        }
                    }
                }
            }
        }
        $this->postUserGroup[$ID] = $post_usergroups;
        return $post_usergroups;
    }
    
    function get_usergroup_info($groupid)
    {
        global $wpdb;
        $uamOptions = $this->getAdminOptions();
        $cur_group = $wpdb->get_results("	SELECT *
											FROM " . DB_ACCESSGROUP . "
											WHERE ID = " . $groupid, ARRAY_A);
        $info->group = $cur_group[0];
        $db_users = $wpdb->get_results("	SELECT *
											FROM " . DB_ACCESSGROUP_TO_USER . "
											WHERE group_id = " . $groupid . "
											ORDER BY user_id", ARRAY_A);
        $db_categories = $wpdb->get_results("	SELECT *
												FROM " . DB_ACCESSGROUP_TO_CATEGORY . "
												WHERE group_id = " . $groupid . "
												ORDER BY category_id", ARRAY_A);
        $db_roles = $wpdb->get_results("	SELECT *
											FROM " . DB_ACCESSGROUP_TO_ROLE . "
											WHERE group_id = " . $groupid, ARRAY_A);
        $wp_users = $wpdb->get_results("	SELECT ID, user_nicename
											FROM $wpdb->users 
											ORDER BY user_nicename", ARRAY_A);
        $args = array('numberposts' => - 1, 'post_type' => 'any');
        $posts = get_posts($args);
        
        if (isset($posts)) {
            foreach ($posts as $post) {
                $groupinfo = $this->getUsergroupsForPost($post->ID);
                if (isset($groupinfo[$groupid])) {
                    if ($post->post_type == 'post') {
                        $info->posts[$post->ID] = $post;
                    } elseif ($post->post_type == 'page') {
                        $info->pages[$post->ID] = $post;
                    } elseif ($post->post_type == 'attachment') {
                        $info->files[$post->ID] = $post;
                    }
                }
            }
        }
        
        $args = array('numberposts' => - 1, 'post_type' => 'attachment');
        $files = get_posts($args);
        
        if (isset($files)) {
            foreach ($files as $file) {
                $groupinfo = $this->getUsergroupsForPost($file->ID);
                if (isset($groupinfo[$groupid])) $info->files[$file->ID] = $file;
            }
        }
        
        if (isset($db_categories)) {
            foreach ($db_categories as $db_categorie) {
                $cur_category = get_category($db_categorie['category_id']);
                $info->categories[$db_categorie['category_id']] = $cur_category;
                if ($uamOptions['lock_recursive'] == 'true') {
                    $cur_categories = get_categories('child_of=' . $db_categorie['category_id']);
                    if (isset($cur_categories)) {
                        foreach ($cur_categories as $cur_category) {
                            $cur_category->recursive_lock_by_category[$db_categorie['category_id']] = $db_categorie['category_id'];
                            $info->categories[$cur_category->term_id] = $cur_category;
                        }
                    }
                }
            }
        }
        
        if (isset($db_users)) {
            $expandcontent = null;
            foreach ($db_users as $db_user) {
                $info->users[$db_user['user_id']] = get_userdata($db_user['user_id']);
            }
        }
        
        if (isset($db_roles)) {
            foreach ($db_roles as $db_role) {
                $info->roles[$db_role['role_name']] = $db_role;
            }
        }
        
        if (isset($wp_users)) {
            foreach ($wp_users as $wp_user) {
                $cur_userdata = get_userdata($wp_user['ID']);
                if ($cur_userdata->user_level >= $uamOptions['full_access_level']) {
                    $info->users[$wp_user['ID']] = $cur_userdata;
                } elseif (isset($db_roles) && $cur_userdata->user_level < $uamOptions['full_access_level']) {
                    foreach ($db_roles as $db_role) {
                        if (isset($cur_userdata->{$wpdb->prefix . "capabilities"}[$db_role['role_name']])) {
                            $info->users[$wp_user['ID']] = $cur_userdata;
                            break;
                        }
                    }
                }
            }
        }
        return $info;
    }
    
    function get_usergroup_info_html($group_id, $style = null)
    {
        $link = '<a class="uam_group_info_link">(' . TXT_INFO . ')</a>';
        $group_info = $this->get_usergroup_info($group_id);
        $content = "<ul class='uam_group_info'";
        if ($style != null) $content.= " style='" . $style . "' ";
        $content.= "><li class='uam_group_info_head'>" . TXT_GROUP_INFO . ":</li>";
        $content.= "<li>" . TXT_READ_ACCESS . ": ";
        if ($group_info->group['read_access'] == "all") {
            $content.= TXT_ALL;
        } elseif ($group_info->group['read_access'] == "group") {
            $content.= TXT_ONLY_GROUP_USERS;
        }
        $content.= "</li>";
        $content.= "<li>" . TXT_WRITE_ACCESS . ": ";
        if ($group_info->group['write_access'] == "all") $content.= TXT_ALL;
        elseif ($group_info->group['write_access'] == "group") $content.= TXT_ONLY_GROUP_USERS;
        $content.= "</li>";
        if (isset($group_info->posts)) {
            $expandcontent = null;
            foreach ($group_info->posts as $post) {
                $expandcontent.= "<li>" . $post->post_title . "</li>";
            }
            $content.= "<li><a class='uam_info_link'>" . count($group_info->posts) . " " . TXT_POSTS . "</a>";
            $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
        } else {
            $content.= "<li>" . TXT_NONE . " " . TXT_POSTS . "</li>";
        }
        if (isset($group_info->pages)) {
            $expandcontent = null;
            foreach ($group_info->pages as $page) {
                $expandcontent.= "<li>" . $page->post_title . "</li>";
            }
            $content.= "<li><a class='uam_info_link'>" . count($group_info->pages) . " " . TXT_PAGES . "</a>";
            $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
        } else {
            $content.= "<li>" . TXT_NONE . " " . TXT_PAGES . "</li>";
        }
        if (isset($group_info->categories)) {
            $expandcontent = null;
            foreach ($group_info->categories as $categorie) {
                $expandcontent.= "<li>" . $categorie->cat_name . "</li>";
            }
            $content.= "<li><a class='uam_info_link'>" . count($group_info->categories) . " " . TXT_CATEGORY . "</a>";
            $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
        } else {
            $content.= "<li>" . TXT_NONE . " " . TXT_CATEGORY . "</li>";
        }
        if (isset($group_info->users)) {
            $expandcontent = null;
            foreach ($group_info->users as $user) {
                $expandcontent.= "<li>" . $user->nickname . "</li>";
            }
            $content.= "<li><a class='uam_info_link'>" . count($group_info->users) . " " . TXT_USERS . "</a>";
            $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
        } else {
            $content.= "<li>" . TXT_NONE . " " . TXT_USERS . "</li>";
        }
        $content.= "</ul>";
        $result->link = $link;
        $result->content = $content;
        return $result;
    }
    
    function get_post_info_html($id)
    {
        $usergroups = $this->getUsergroupsForPost($id);
        if (isset($usergroups) && $usergroups != null) {
            $output = "<ul>";
            foreach ($usergroups as $usergroup) {
                $output.= "<li><a class='uma_user_access_group'>" . $usergroup->name . "</a>";
                $output.= "<ul class='uma_user_access_group_from'>";
                if (isset($usergroup->itself)) $output.= "<li>" . TXT_ITSELF . "</li>";
                if (isset($usergroup->posts)) {
                    foreach ($usergroup->posts as $cur_id) {
                        $cur_post = & get_post($cur_id);
                        $output.= "<li>$cur_post->post_title [$cur_post->post_type]</li>";
                    }
                }
                if (isset($usergroup->categories)) {
                    foreach ($usergroup->categories as $cur_id) {
                        $cur_category = & get_category($cur_id);
                        $output.= "<li>$cur_category->name [category]</li>";
                    }
                }
                $output = substr($output, 0, -2);
                $output.= "</ul></li>";
            }
            $output.= "</ul>";
        } else {
            $output = TXT_FULL_ACCESS;
        }
        return $output;
    }
    
    function add_post_columns_header($defaults)
    {
        $defaults['uam_access'] = __('Access');
        return $defaults;
    }
    
    function get_post_edit_info_html($id, $style = null)
    {
        global $wpdb;
        $accessgroups = $wpdb->get_results("SELECT *
											FROM " . DB_ACCESSGROUP . "
											ORDER BY groupname", ARRAY_A);
        $recursive_set = $this->getUsergroupsForPost($id);
        if (isset($accessgroups)) {
            $content = "";
            foreach ($accessgroups as $accessgroup) {
                $checked = $wpdb->get_results("	SELECT *
												FROM " . DB_ACCESSGROUP_TO_POST . "
												WHERE post_id = " . $id . "
												AND group_id = " . $accessgroup['ID'], ARRAY_A);
                $set_recursive = null;
                if (isset($recursive_set[$accessgroup['ID']])) $set_recursive = $recursive_set[$accessgroup['ID']];
                $content.= '<p><label for="uam_accesssgroup-' . $accessgroup['ID'] . '" class="selectit" style="display:inline;" >';
                $content.= '<input type="checkbox" id="uam_accesssgroup-' . $accessgroup['ID'] . '"';
                if (isset($checked) || isset($set_recursive->posts) || isset($set_recursive->categories)) $content.= 'checked="checked"';
                if (isset($set_recursive->posts) || isset($set_recursive->categories)) $content.= 'disabled=""';
                $content.= 'value="' . $accessgroup['ID'] . '" name="accessgroups[]"/>';
                $content.= $accessgroup['groupname'];
                $content.= "</label>";
                $group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], $style);
                $content.= $group_info_html->link;
                if (isset($set_recursive->posts) || isset($set_recursive->categories)) $content.= '&nbsp;<a class="uam_group_lock_info_link">[LR]</a>';
                $content.= $group_info_html->content;
                if (isset($set_recursive->posts) || isset($set_recursive->categories)) {
                    $recursive_info = '<ul class="uam_group_lock_info" ';
                    if ($style != null) $recursive_info.= " style='" . $style . "' ";
                    $recursive_info.= '><li class="uam_group_lock_info_head">' . TXT_GROUP_LOCK_INFO . ':</li>';
                    if (isset($set_recursive->posts)) {
                        foreach ($set_recursive->posts as $cur_id) {
                            $cur_post = & get_post($cur_id);
                            $recursive_info.= "<li>$cur_post->post_title [$cur_post->post_type]</li>";
                        }
                    }
                    if (isset($set_recursive->categories)) {
                        foreach ($set_recursive->categories as $cur_id) {
                            $cur_category = & get_category($cur_id);
                            $recursive_info.= "<li>$cur_category->name [" . TXT_CATEGORY . "]</li>";
                        }
                    }
                    $recursive_info.= "</ul>";
                    $content.= $recursive_info;
                }
                $content.= "</p>";
            }
        } else {
            $content = "<a href='admin.php?page=uam_usergroup'>";
            $content.= TXT_CREATE_GROUP_FIRST;
            $content.= "</a>";
        }
        return $content;
    }
    
    function add_post_column($column_name, $id)
    {
        if ($column_name == 'uam_access') {
            echo $this->get_post_info_html($id);
        }
    }
    
    function edit_post_content($post)
    {
        echo $this->get_post_edit_info_html($post->ID, "padding:0 0 0 36px;");
    }
    
    function save_postdata($post_id)
    {
        global $current_user, $wpdb;
        $cur_userdata = get_userdata($current_user->ID);
        $uamOptions = $this->getAdminOptions();
        if ($cur_userdata->user_level < $uamOptions['full_access_level']) {
            $uamOptions = $this->getAdminOptions();
            $cur_categories = wp_get_post_categories($post_id);
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
            wp_set_post_categories($post_id, $post_categories);
        }
        if (isset($_POST['accessgroups'])) $accessgroups = $_POST['accessgroups'];
        $wpdb->query("DELETE FROM " . DB_ACCESSGROUP_TO_POST . " WHERE post_id = $post_id");
        if (isset($accessgroups)) {
            foreach ($accessgroups as $accessgroup) {
                $wpdb->query("INSERT INTO " . DB_ACCESSGROUP_TO_POST . " (post_id,group_id) VALUES(" . $post_id . ", " . $accessgroup . ")");
            }
        }
    }
    
    function save_attachmentdata($post)
    {
        global $wpdb;
        if (isset($post['ID'])) {
            $post_id = $post['ID'];
            if (isset($_POST['accessgroups'])) $accessgroups = $_POST['accessgroups'];
            $wpdb->query("DELETE FROM " . DB_ACCESSGROUP_TO_POST . " WHERE post_id = $post_id");
            if (isset($accessgroups)) {
                foreach ($accessgroups as $accessgroup) {
                    $wpdb->query("INSERT INTO " . DB_ACCESSGROUP_TO_POST . " (post_id,group_id) VALUES(" . $post_id . ", " . $accessgroup . ")");
                }
            }
        }
        return $post;
    }
    
    function remove_postdata($post_id)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM " . DB_ACCESSGROUP_TO_POST . " WHERE post_id = $post_id");
    }
    
    function add_user_columns_header($defaults)
    {
        $defaults['uam_access'] = __('Access');
        return $defaults;
    }
    
    function add_user_column($column_name, $id)
    {
        global $wpdb;
        if ($column_name == 'uam_access') {
            $usergroups = $wpdb->get_results("	SELECT ag.groupname
												FROM " . DB_ACCESSGROUP . " ag, " . DB_ACCESSGROUP_TO_USER . " agtp
												WHERE agtp.user_id = " . $id . "
													AND ag.ID = agtp.group_id
												GROUP BY ag.groupname", ARRAY_A);
            if (isset($usergroups)) {
                $content.= "<ul>";
                foreach ($usergroups as $usergroup) {
                    $content.= "<li>" . $usergroup['groupname'] . "</li>";
                }
                $content.= "</ul>";
            } else {
                $content = TXT_NO_GROUP;
            }
            return $content;
        }
    }
    
    function show_user_profile()
    {
        global $wpdb, $current_user;
        $user_id = $_GET['user_id'];
        $cur_userdata = get_userdata($current_user->ID);
        $cur_edit_userdata = get_userdata($user_id);
        $uamOptions = $this->getAdminOptions();
        if ($cur_userdata->user_level >= $uamOptions['full_access_level']) {
            $accessgroups = $wpdb->get_results("SELECT *
												FROM " . DB_ACCESSGROUP . "
												ORDER BY groupname", ARRAY_A);
?>
<h3><?php
            echo TXT_GROUPS; ?></h3>
<table class="form-table">
	<tbody>
		<tr>
			<th><label for="usergroups"><?php
            echo TXT_SET_UP_USERGROUPS; ?></label>
		</th>
		<td><?php
            if (empty($cur_edit_userdata->{$wpdb->prefix . "capabilities"}['administrator'])) {
                if (isset($accessgroups)) {
                    foreach ($accessgroups as $accessgroup) {
                        $checked = $wpdb->get_results("	SELECT *
																		FROM " . DB_ACCESSGROUP_TO_USER . "
																		WHERE user_id = " . $user_id . "
																			AND group_id = " . $accessgroup['ID'], ARRAY_A)
?>
		<p style="margin: 6px 0;"><label
			for="uam_accesssgroup-<?php
                        echo $accessgroup['ID']; ?>"
			class="selectit"> <input type="checkbox"
			id="uam_accesssgroup-<?php
                        echo $accessgroup['ID']; ?>"
			<?php
                        if (isset($checked)) {
                            echo 'checked="checked"';
                        } ?>
			value="<?php
                        echo $accessgroup['ID']; ?>" name="accessgroups[]" /> <?php
                        echo $accessgroup['groupname']; ?>
		</label> <?php
                        $group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], "padding: 0 0 0 32px");
                        echo $group_info_html->link;
                        echo $group_info_html->content;
                        echo "</p>";
                    }
                } else {
                    echo "<a href='admin.php?page=uam_usergroup'>";
                    echo TXT_CREATE_GROUP_FIRST;
                    echo "</a>";
                }
            } else {
                echo TXT_ADMIN_HINT;
            }
?>
		
		</td>
	</tr>
</tbody>
</table>
		<?php
        }
    }
    
    function show_media_file($meta = '', $post)
    {
        $content = $meta;
        $content.= '</td></tr><tr><th class="label"><label>' . TXT_SET_UP_USERGROUPS . '</label></th><td class="field">';
        $content.= $this->get_post_edit_info_html($post->ID, "padding:0 0 0 38px;top:-12px;");
        return $content;
    }
    
    function save_userdata($user_id)
    {
        global $wpdb, $current_user;
        $cur_userdata = get_userdata($current_user->ID);
        $uamOptions = $this->getAdminOptions();
        if ($cur_userdata->user_level >= $uamOptions['full_access_level']) {
            $accessgroups = $_POST['accessgroups'];
            $wpdb->query("DELETE FROM " . DB_ACCESSGROUP_TO_USER . " WHERE user_id = $user_id");
            if (isset($accessgroups)) {
                foreach ($accessgroups as $accessgroup) {
                    $wpdb->query("INSERT INTO " . DB_ACCESSGROUP_TO_USER . " (user_id,group_id) VALUES(" . $user_id . ", " . $accessgroup . ")");
                }
            }
        }
    }
    
    function remove_userdata($user_id)
    {
        global $wpdb, $current_user;
        $cur_userdata = get_userdata($current_user->ID);
        $uamOptions = $this->getAdminOptions();
        if ($cur_userdata->user_level >= $uamOptions['full_access_level']) $wpdb->query("DELETE FROM " . DB_ACCESSGROUP_TO_USER . " WHERE user_id = $user_id");
    }
    
    function add_category_columns_header($defaults)
    {
        $defaults['uam_access'] = __('Access');
        return $defaults;
    }
    
    function add_category_column($column_name, $id)
    {
        global $wpdb;
        if ($column_name == 'uam_access') {
            $usergroups = $wpdb->get_results("	SELECT ag.groupname
												FROM " . DB_ACCESSGROUP . " ag, " . DB_ACCESSGROUP_TO_CATEGORY . " agtc
												WHERE agtc.category_id = " . $id . "
													AND ag.ID = agtc.group_id
												GROUP BY ag.groupname", ARRAY_A);
            if (isset($usergroups)) {
                $content = "<ul>";
                foreach ($usergroups as $usergroup) {
                    $content.= "<li>" . $usergroup['groupname'] . "</li>";
                }
                $content.= "</ul>";
            } else {
                $content = TXT_NO_GROUP;
            }
            return $content;
        }
    }
    
    function show_cat_edit_form($cat)
    {
        global $wpdb, $current_user;
        if (isset($cat->cat_ID)) $cat_id = $cat->cat_ID;
        $accessgroups = $wpdb->get_results("SELECT *
											FROM " . DB_ACCESSGROUP . "
											ORDER BY groupname", ARRAY_A);
        if (isset($_GET['action'])) $action = $_GET['action'];
        else $action = null;
        if ($action == 'edit') {
?>
<table class="form-table">
	<tbody>
		<tr>
			<th><label for="description"><?php
            echo TXT_SET_UP_USERGROUPS; ?></label>
		</th>
		<td><?php
            if (isset($accessgroups)) {
                $recursive_set = $this->getUsergroupsForPost($cat_id);
                foreach ($accessgroups as $accessgroup) {
                    $checked = $wpdb->get_results("	SELECT *
																	FROM " . DB_ACCESSGROUP_TO_CATEGORY . "
																	WHERE category_id = " . $cat_id . "
																		AND group_id = " . $accessgroup['ID'], ARRAY_A)

                    //$set_recursive = $recursive_set[$accessgroup['groupname']];
                    
?>
		<p style="margin: 6px 0;"><label
			for="uam_accesssgroup-<?php
                    echo $accessgroup['ID']; ?>"
			class="selectit"> <input type="checkbox"
			id="uam_accesssgroup-<?php
                    echo $accessgroup['ID']; ?>"
			<?php
                    if (isset($checked)) {
                        echo 'checked="checked"';
                    } ?>
			value="<?php
                    echo $accessgroup['ID']; ?>" name="accessgroups[]" /> <?php
                    echo $accessgroup['groupname']; ?>
		</label> <?php
                    $group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], "padding:0 0 0 32px;");
                    echo $group_info_html->link;
                    if (isset($set_recursive->posts) || isset($set_recursive->categories)) echo '&nbsp;<a class="uam_group_lock_info_link">[LR]</a>';
                    echo $group_info_html->content;
                    if (isset($set_recursive->posts) || isset($set_recursive->categories)) {
                        $recursive_info = '<ul class="uam_group_lock_info" style="padding:0 0 0 32px;"><li class="uam_group_lock_info_head">' . TXT_GROUP_LOCK_INFO . ':</li>';
                        if (isset($set_recursive->posts)) {
                            foreach ($set_recursive->posts as $cur_id) {
                                $cur_post = & get_post($cur_id);
                                $recursive_info.= "<li>$cur_post->post_title [$cur_post->post_type]</li>";
                            }
                        }
                        if (isset($set_recursive->categories)) {
                            foreach ($set_recursive->categories as $cur_id) {
                                $cur_category = & get_category($cur_id);
                                $recursive_info.= "<li>$cur_category->name [" . TXT_CATEGORY . "]</li>";
                            }
                        }
                        $recursive_info.= "</ul>";
                        echo $recursive_info;
                    }
                    echo "</p>";
                }
            } else {
                echo "<a href='admin.php?page=uam_usergroup'>";
                echo TXT_CREATE_GROUP_FIRST;
                echo "</a>";
            }
?>
			
			</td>
		</tr>
	</tbody>
</table>
<style type="text/css">
.submit {
	display: none;
	position: relative;
}
</style>
<p class="submit" style="display: block; position: relative;"><input
	class="button-primary" type="submit" value="Update Category"
	name="submit" /></p>
			<?php
        }
    }
    
    function add_styles()
    {
        wp_enqueue_style('UserAccessManager', UAM_URLPATH . "css/uma_admin.css", false, '1.0', 'screen');
    }
    
    function add_scripts()
    {
        wp_enqueue_script('UserAccessManager', UAM_URLPATH . 'js/functions.js', array('jquery'), '1.0');
    }
    
    function save_categorydata($category_id)
    {
        global $wpdb;
        if (isset($_POST['accessgroups'])) $accessgroups = $_POST['accessgroups'];
        $wpdb->query("DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " WHERE category_id = $category_id");
        if (isset($accessgroups)) {
            foreach ($accessgroups as $accessgroup) {
                $wpdb->query("INSERT INTO " . DB_ACCESSGROUP_TO_CATEGORY . " (category_id,group_id) VALUES(" . $category_id . ", " . $accessgroup . ")");
            }
        }
    }
    
    function remove_categorydata($category_id)
    {
        global $wpdb;
        $wpdb->query("DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " WHERE category_id = $category_id");
    }
    
    function get_login_bar()
    {
        if (!is_user_logged_in()) {
            if (!is_single()) {
                $output = '<a href="' . get_bloginfo('wpurl') . '/wp-login.php?redirect_to=' . urlencode($_SERVER['REQUEST_URI']) . '">' . __('Login', 'user-access-manager') . '</a>';
            } else {
                if (!isset($user_login)) $user_login = '';

                // login form
                $output = '<form action="' . get_bloginfo('wpurl') . '/wp-login.php" method="post" >';
                $output.= '<p><label for="user_login">' . __('Username:', 'user-access-manager') . '<input name="log" value="' . wp_specialchars(stripslashes($user_login), 1) . '" class="input" id="user_login" type="text" /></label></p>';
                $output.= '<p><label for="user_pass">' . __('Password:', 'user-access-manager') . '<input name="pwd" class="imput" id="user_pass" type="password" /></label></p>';
                $output.= '<p class="forgetmenot"><label for="rememberme"><input name="rememberme" class="checkbox" id="rememberme" value="forever" type="checkbox" /> ' . __('Remember me', 'user-access-manager') . '</label></p>';
                $output.= '<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" value="' . __('Login', 'user-access-manager') . ' &raquo;" />';
                $output.= '<input type="hidden" name="redirect_to" value="' . $_SERVER['REQUEST_URI'] . '" />';
                $output.= '</form>';
                $output.= '<p>';
                if (get_option('users_can_register')) $output.= '<a href="' . get_bloginfo('wpurl') . '/wp-login.php?action=register">' . __('Register', 'user-access-manager') . '</a></br>';
                $output.= '<a href="' . get_bloginfo('wpurl') . '/wp-login.php?action=lostpassword" title="' . __('Password Lost and Found', 'user-access-manager') . '">' . __('Lost your password?', 'user-access-manager') . '</a>';
                $output.= '</p>';
            }
            return $output;
        }
        return null;
    }
    
    function get_access($post_id = null)
    {
        global $wpdb;
        if (isset($this->restrictedPost[$post_id])) return $this->restrictedPost[$post_id];
        $access = null;
        $cur_id = $post_id;
        $cur_post = & get_post($cur_id);
        if (isset($cur_post->ID)) $cur_categories = get_the_category($cur_post->ID);
        $uamOptions = $this->getAdminOptions();
        if ($this->atAdminPanel) $sqlCheckLocation = "ag.write_access != 'all'";
        else $sqlCheckLocation = "ag.read_access != 'all'";
        $restricted_by_categories = array();
        $restricted_by_posts = array();

        //check categories access
        if (isset($cur_categories)) {
            foreach ($cur_categories as $cur_category) {
                if ($cur_post->post_type == "post" || $cur_post->post_type == "attachment") {
                    $restricted_access_by_cat = $wpdb->get_results("	SELECT *
																		FROM " . DB_ACCESSGROUP_TO_CATEGORY . " atc, " . DB_ACCESSGROUP . " ag
																		WHERE atc.category_id = " . $cur_category->term_id . "
																			AND atc.group_id = ag.ID
																			AND " . $sqlCheckLocation, ARRAY_A);
                    if (isset($restricted_access_by_cat)) $restricted_by_categories[$cur_category->term_id] = $cur_category->term_id;
                    if ($uamOptions['lock_recursive'] == 'true') {
                        $cur_id = $cur_category->parent;
                        while ($cur_id != 0) {
                            $restricted_access_by_cat = $wpdb->get_results("	SELECT *
																				FROM " . DB_ACCESSGROUP_TO_CATEGORY . " atc, " . DB_ACCESSGROUP . " ag
																				WHERE atc.category_id = " . $cur_id . "
																					AND atc.group_id = ag.ID
																					AND " . $sqlCheckLocation, ARRAY_A);
                            if (isset($cur_category->term_id) && isset($restricted_access_by_cat)) $restricted_by_categories[$cur_category->term_id] = $cur_category->term_id;
                            if (isset($cur_category->parent)) {
                                $cur_id = $cur_category->parent;
                                $cur_category = & get_category($cur_id);
                            } else {
                                $cur_id = 0;
                            }
                        }
                    }
                }
            }
        }

        //check posts access
        if (isset($cur_post->ID)) {
            $restricted_access_by_post = $wpdb->get_results("	SELECT *
																FROM " . DB_ACCESSGROUP_TO_POST . " atp, " . DB_ACCESSGROUP . " ag
																WHERE atp.post_id = " . $cur_post->ID . "
																	AND atp.group_id = ag.ID
																	AND " . $sqlCheckLocation, ARRAY_A);
            if (isset($restricted_access_by_post)) $restricted_by_posts[$cur_post->ID] = $cur_post->ID;
            if ($uamOptions['lock_recursive'] == 'true') {
                if (isset($cur_post->post_parent)) $cur_id = $cur_post->post_parent;
                if ($cur_id != 0 && isset($cur_id)) {
                    $restricted_access = $this->get_access($cur_id);
                    if (isset($restricted_access->restricted_by_categories)) $restricted_by_categories = array_unique(array_merge($restricted_by_categories, $restricted_access->restricted_by_categories));
                    if (isset($restricted_access->restricted_by_posts)) $restricted_by_posts = array_unique(array_merge($restricted_by_posts, $restricted_access->restricted_by_posts));
                }
            } elseif ($uamOptions['lock_recursive'] == 'false' && $cur_post->post_type == "attachment") {
                if (isset($cur_post->post_parent)) $cur_id = $cur_post->post_parent;
                if ($cur_id != 0 && isset($cur_id)) {
                    $restricted_access = $this->get_access($cur_id);
                    if (isset($restricted_access->restricted_by_categories)) $restricted_by_categories = array_unique(array_merge($restricted_by_categories, $restricted_access->restricted_by_categories));
                    if (isset($restricted_access->restricted_by_posts)) $restricted_by_posts = array_unique(array_merge($restricted_by_posts, $restricted_access->restricted_by_posts));
                }
            }
        }
        if (isset($restricted_by_categories) && count($restricted_by_categories) != 0) $access->restricted_by_categories = $restricted_by_categories;
        if (isset($restricted_by_posts) && count($restricted_by_posts) != 0) $access->restricted_by_posts = $restricted_by_posts;
        if (empty($access)) $access = - 1;
        $this->restrictedPost[$post_id] = $access;
        return $access;
    }
    
    function check_access($post_id = null)
    {
        global $wpdb, $current_user;
        if (isset($this->postAccess[$post_id])) return $this->postAccess[$post_id];
        $access = $this->get_access($post_id);
        $cur_user_ip = explode(".", $_SERVER['REMOTE_ADDR']);
        $uamOptions = $this->getAdminOptions();
        if (isset($access->restricted_by_posts) || isset($access->restricted_by_categories)) {
            if (is_user_logged_in()) {
                $cur_userdata = get_userdata($current_user->ID);
                if ($cur_userdata->user_level < $uamOptions['full_access_level']) {
                    if (isset($access->restricted_by_categories)) {
                        foreach ($access->restricted_by_categories as $cur_cat_id) {
                            $user_access = $wpdb->get_results("	SELECT *
																FROM " . DB_ACCESSGROUP_TO_CATEGORY . " atc, " . DB_ACCESSGROUP_TO_USER . " atu
																WHERE atc.category_id = " . $cur_cat_id . "
																	AND atc.group_id = atu.group_id
																	AND atu.user_id = " . $current_user->ID, ARRAY_A);
                            if (isset($user_access)) {
                                $this->postAccess[$post_id] = true;
                                return true;
                            }
                            $access_roles = $wpdb->get_results("SELECT atr.role_name
																FROM " . DB_ACCESSGROUP_TO_CATEGORY . " atc, " . DB_ACCESSGROUP_TO_ROLE . " atr
																WHERE atc.category_id = " . $cur_cat_id . "
																	AND atc.group_id = atr.group_id", ARRAY_A);
                            if (isset($access_roles)) {
                                foreach ($access_roles as $access_role) {
                                    if (isset($cur_userdata->wp_capabilities[$access_role['role_name']])) {
                                        $this->postAccess[$post_id] = true;
                                        return true;
                                    }
                                }
                            }
                            $db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
															FROM " . DB_ACCESSGROUP_TO_CATEGORY . " atc, " . DB_ACCESSGROUP . " ag
															WHERE atc.category_id = " . $cur_cat_id . "
																AND atc.group_id = ag.ID", ARRAY_A);
                            if ($this->check_user_ip($cur_user_ip, $db_ip_ranges)) {
                                $this->postAccess[$post_id] = true;
                                return true;
                            }
                        }
                    }
                    if (isset($access->restricted_by_posts)) {
                        foreach ($access->restricted_by_posts as $cur_post_id) {
                            $user_access = $wpdb->get_results("	SELECT *
																FROM " . DB_ACCESSGROUP_TO_POST . " atp, " . DB_ACCESSGROUP_TO_USER . " atu
																WHERE atp.post_id = " . $cur_post_id . "
																	AND atp.group_id = atu.group_id
																	AND atu.user_id = " . $current_user->ID, ARRAY_A);
                            if (isset($user_access)) {
                                $this->postAccess[$post_id] = true;
                                return true;
                            }
                            $access_roles = $wpdb->get_results("SELECT atr.role_name
																FROM " . DB_ACCESSGROUP_TO_POST . " atp, " . DB_ACCESSGROUP_TO_ROLE . " atr
																WHERE atp.post_id = " . $cur_post_id . "
																	AND atp.group_id = atr.group_id", ARRAY_A);
                            if (isset($access_roles)) {
                                foreach ($access_roles as $access_role) {
                                    if (isset($cur_userdata->wp_capabilities[$access_role['role_name']])) {
                                        $this->postAccess[$post_id] = true;
                                        return true;
                                    }
                                }
                            }
                            $db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
															FROM " . DB_ACCESSGROUP_TO_POST . " atp, " . DB_ACCESSGROUP . " ag
															WHERE atp.post_id = " . $cur_post_id . "
																AND atp.group_id = ag.ID", ARRAY_A);
                            if ($this->check_user_ip($cur_user_ip, $db_ip_ranges)) {
                                $this->postAccess[$post_id] = true;
                                return true;
                            }
                        }
                    }
                    if (empty($user_access)) {
                        $this->postAccess[$post_id] = false;
                        return false;
                    }
                } else {
                    $this->postAccess[$post_id] = true;
                    return true;
                }
            } else {
                if (isset($access->restricted_by_categories)) {
                    foreach ($access->restricted_by_categories as $cur_cat_id) {
                        $db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
														FROM " . DB_ACCESSGROUP_TO_CATEGORY . " atc, " . DB_ACCESSGROUP . " ag
														WHERE atc.category_id = " . $cur_cat_id . "
															AND atc.group_id = ag.ID");
                        if ($this->check_user_ip($cur_user_ip, $db_ip_ranges)) {
                            $this->postAccess[$post_id] = true;
                            return true;
                        }
                    }
                }
                if (isset($access->restricted_by_posts)) {
                    foreach ($access->restricted_by_posts as $cur_post_id) {
                        $db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
														FROM " . DB_ACCESSGROUP_TO_POST . " atp, " . DB_ACCESSGROUP . " ag
														WHERE atp.post_id = " . $cur_post_id . "
															AND atp.group_id = ag.ID");
                        if ($this->check_user_ip($cur_user_ip, $db_ip_ranges)) {
                            $this->postAccess[$post_id] = true;
                            return true;
                        }
                    }
                }
                $this->postAccess[$post_id] = false;
                return false;
            }
        } else {
            $this->postAccess[$post_id] = true;
            return true;
        }
    }
    
    function check_user_ip($cur_user_ip, $db_ip_ranges)
    {
        if (isset($db_ip_ranges)) {
            $ip_ranges = explode(";", $db_ip_ranges);
            if (isset($ip_ranges)) {
                foreach ($ip_ranges as $ip_range) {
                    $ip_range = explode("-", $ip_range);
                    $range_begin = explode(".", $ip_range[0]);
                    if (isset($ip_range[1])) $range_end = explode(".", $ip_range[1]);
                    else $range_end = explode(".", $ip_range[0]);
                    if ($range_begin[0] <= $cur_user_ip[0] && $cur_user_ip[0] <= $range_end[0] && $range_begin[1] <= $cur_user_ip[1] && $cur_user_ip[1] <= $range_end[1] && $range_begin[2] <= $cur_user_ip[2] && $cur_user_ip[2] <= $range_end[2] && $range_begin[3] <= $cur_user_ip[3] && $cur_user_ip[3] <= $range_end[3]) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    function show_post($posts = array())
    {
        $show_posts = null;
        $uamOptions = $this->getAdminOptions();
        if (!is_feed() || ($uamOptions['protect_feed'] == 'true' && is_feed())) {
            foreach ($posts as $post) {
                if (($uamOptions['hide_post'] == 'true' && $post->post_type == "post") || ($uamOptions['hide_page'] == 'true' && $post->post_type == "page") || $this->atAdminPanel) {
                    if ($this->check_access($post->ID)) {
                        $post->post_title.= $this->admin_output($post->ID);
                        $show_posts[] = $post;
                    }
                } else {
                    if (!$this->check_access($post->ID)) {
                        if ($post->post_type == "post") {
                            $uam_post_content = $uamOptions['post_content'];
                            $uam_post_content = str_replace("[LOGIN_FORM]", $this->get_login_bar(), $uam_post_content);
                            if ($uamOptions['hide_post_title'] == 'true') $post->post_title = $uamOptions['post_title'];
                            if ($uamOptions['show_post_content_before_more'] == 'true' && preg_match('/<!--more(.*?)?-->/', $post->post_content, $matches)) {
                                $post->post_content = explode($matches[0], $post->post_content, 2);
                                $post->post_content = $post->post_content[0] . " " . $uam_post_content;
                            } else {
                                $post->post_content = $uam_post_content;
                            }
                            if ($uamOptions['allow_comments_locked'] == 'false') $post->comment_status = 'close';
                        } elseif ($post->post_type == "page") {
                            if ($uamOptions['hide_page_title'] == 'true') $post->post_title = $uamOptions['page_title'];
                            $uam_post_content = $uamOptions['page_content'];
                            $uam_post_content = str_replace("[LOGIN_FORM]", $this->get_login_bar(), $uam_post_content);
                            $post->post_content = $uam_post_content;
                        }
                    }
                    $post->post_title.= $this->admin_output($post->ID);
                    $show_posts[] = $post;
                }
            }
            $posts = $show_posts;
        }
        return $posts;
    }
    
    function show_comment($comments = array())
    {
        $show_comments = null;
        $uamOptions = $this->getAdminOptions();
        foreach ($comments as $comment) {
            if ($uamOptions['hide_post_comment'] == 'true' || $uamOptions['hide_post'] == 'true' || $this->atAdminPanel) {
                if ($this->check_access($comment->comment_post_ID)) $show_comments[] = $comment;
            } else {
                if (!$this->check_access($comment->comment_post_ID)) {
                    $comment->comment_content = $uamOptions['post_comment_content'];
                }
                $show_comments[] = $comment;
            }
        }
        $comments = $show_comments;
        return $comments;
    }
    
    function show_page($pages = array())
    {
        $show_pages = null;
        $uamOptions = $this->getAdminOptions();
        foreach ($pages as $page) {
            if ($uamOptions['hide_page'] == 'true' || $this->atAdminPanel) {
                if ($this->check_access($page->ID)) {
                    $page->post_title.= $this->admin_output($page->ID);
                    $show_pages[] = $page;
                }
            } else {
                if (!$this->check_access($page->ID)) {
                    if ($uamOptions['hide_page_title'] == 'true') $page->post_title = $uamOptions['page_title'];
                    $page->post_content = $uamOptions['page_content'];
                }
                $page->post_title.= $this->admin_output($page->ID);
                $show_pages[] = $page;
            }
        }
        $pages = $show_pages;
        return $pages;
    }
    
    function show_category($categories = array())
    {
        global $current_user, $wpdb;
        $cur_userdata = get_userdata($current_user->ID);
        $uamOptions = $this->getAdminOptions();
        if (!isset($cur_userdata->user_level)) $cur_userdata->user_level = null;
        if ($cur_userdata->user_level < $uamOptions['full_access_level']) {
            $uamOptions = $this->getAdminOptions();
            if ($this->atAdminPanel) {
                $restrictedcategories = $wpdb->get_results("SELECT category_id
															FROM " . DB_ACCESSGROUP_TO_CATEGORY, ARRAY_A);
                if (isset($restrictedcategories)) {
                    foreach ($categories as $category) {
                        foreach ($restrictedcategories as $restrictedcategory) {
                            $has_access = true;
                            if ($restrictedcategory['category_id'] == $category->term_id) {
                                $has_access = false;
                                $access = $wpdb->get_results("	SELECT category_id
																FROM " . DB_ACCESSGROUP_TO_USER . " agtu, " . DB_ACCESSGROUP_TO_CATEGORY . " agtc
																WHERE agtu.user_id = " . $current_user->ID . "
																	AND agtu.group_id = agtc.group_id
																	AND agtc.category_id = " . $category->term_id, ARRAY_A);
                                if (isset($access)) $has_access = true;
                                if (empty($show_categories[$category->term_id]) && !$has_access) $restrict_categories[$category->term_id] = $category;
                            }
                            if ($has_access) {
                                $show_categories[$category->term_id] = $category;
                                if (isset($restrict_categories[$category->term_id])) unset($restrict_categories[$category->term_id]);
                            }
                        }
                    }
                    if (isset($restrict_categories) && $uamOptions['lock_recursive'] == 'true') {
                        foreach ($restrict_categories as $restrict_category) {
                            $args = array('child_of' => $restrict_category->term_id);
                            $child_categories = get_categories($args);
                            foreach ($child_categories as $child_category) unset($show_categories[$child_category->term_id]);
                        }
                    }
                    if (isset($show_categories)) $categories = $show_categories;
                    else $categories = null;
                }

                /*$accesscategories = $wpdb->get_results("SELECT agtc.category_id
                 FROM ".DB_ACCESSGROUP_TO_USER." agtu, ".DB_ACCESSGROUP_TO_CATEGORY." agtc
                 WHERE agtu.user_id = ".$current_user->ID."
                 AND agtu.group_id = agtc.group_id", ARRAY_A);
                
                 if(isset($accesscategories))
                 {
                 foreach($accesscategories as $accesscategory)
                 {
                 foreach($categories as $category)
                 {
                 if($accesscategory['category_id'] == $category->term_id)
                 {
                 $show_categories[$category->term_id] = $category;
                
                 if(isset($empty_categories[$category->term_id]))
                 unset($empty_categories[$category->term_id]);
                 }
                 else
                 {
                 if(empty($show_categories[$category->term_id]))
                 $empty_categories[$category->term_id] = $category;
                 }
                 }
                 }
                
                 if(isset($empty_categories) && $uamOptions['lock_recursive'] == 'true')
                 {
                 foreach($empty_categories as $empty_category)
                 {
                 $cur_cat = $empty_category;
                 while($cur_cat->parent != 0 && isset($show_categories))
                 {
                 if(isset($show_categories[$cur_cat->parent]))
                 {
                 $show_categories[$empty_category->term_id] = $empty_category;
                 break;
                 }
                
                 $cur_id = $cur_cat->parent;
                 $cur_cat = & get_category($cur_id);
                 }
                 }
                 }
                 }
                
                 if(isset($show_categories))
                 $categories = $show_categories;
                 else
                 $categories = null;*/
            } else {
                if ($uamOptions['hide_post'] == 'true' || $uamOptions['hide_page'] == 'true') {
                    $args = array('numberposts' => - 1);
                    $posts = get_posts($args);
                    foreach ($categories as $category) {
                        $count = 0;
                        if (isset($posts)) {
                            foreach ($posts as $cur_post) {
                                $post_cat_ids = array();
                                $post_cats = get_the_category($cur_post->ID);
                                foreach ($post_cats as $post_cat) {
                                    $post_cat_ids[] = $post_cat->term_id;
                                }
                                if (in_array($category->term_id, $post_cat_ids)) {
                                    if (($uamOptions['hide_post'] == 'true' && $cur_post->post_type == "post") || ($uamOptions['hide_page'] == 'true' && $cur_post->post_type == "page")) {
                                        if ($this->check_access($cur_post->ID)) $count++;
                                    } else {
                                        $count++;
                                    }
                                }
                            }
                        }
                        if (($count != 0 || ($uamOptions['hide_empty_categories'] == 'false' && !$this->atAdminPanel))) {
                            $category->count = $count;
                            $cur_show_categories[$category->term_id] = $category;
                        } elseif ($category->taxonomy == "link_category" || $category->taxonomy == "post_tag") {
                            $show_categories[$category->term_id] = $category;
                        } elseif ($count == 0) {
                            $category->count = $count;
                            $empty_categories[$category->term_id] = $category;
                        }
                    }
                    if ($uamOptions['hide_empty_categories'] == 'true') {
                        if (isset($cur_show_categories)) {
                            foreach ($cur_show_categories as $cur_show_category) {
                                $cur_count = $cur_show_category->count;
                                $show_categories[$cur_show_category->term_id] = $cur_show_category;
                                $cur_cat = $cur_show_category;
                                while ($cur_cat->parent != 0 && isset($empty_categories)) {
                                    if (empty($show_categories[$cur_cat->parent])) {
                                        if (isset($empty_categories[$cur_cat->parent])) {
                                            $cur_empty_cat = $empty_categories[$cur_cat->parent];
                                            $cur_empty_cat->count = $cur_count;
                                            $show_categories[$cur_cat->parent] = $cur_empty_cat;
                                        }
                                    }
                                    $cur_id = $cur_cat->parent;
                                    $cur_cat = & get_category($cur_id);
                                }
                            }
                        }
                    } else {
                        if (isset($cur_show_categories)) {
                            foreach ($cur_show_categories as $cur_show_category) {
                                $show_categories[$cur_show_category->term_id] = $cur_show_category;
                            }
                        }
                    }
                    if (isset($show_categories)) $categories = $show_categories;
                    else $categories = null;
                }
            }
        }
        return $categories;
    }
    
    function show_title($title, $post = null)
    {
        $uamOptions = $this->getAdminOptions();
        if (isset($post)) $post_id = $post->ID;
        else $post_id = null;
        if (!$this->check_access($post_id) && $post != null) {
            if ($post->post_type == "post") $title = $uamOptions['post_title'];
            elseif ($post->post_type == "page") $title = $uamOptions['page_title'];
        }
        return $title;
    }
    
    function show_next_previous_post($sql)
    {
        $uamOptions = $this->getAdminOptions();
        if ($uamOptions['hide_post'] == 'true') {
            $posts = get_posts();
            if (isset($posts)) {
                foreach ($posts as $post) {
                    if (!$this->check_access($post->ID)) $excluded_posts[] = $post->ID;
                }
                if (isset($excluded_posts)) {
                    $excluded_posts_str = implode(",", $excluded_posts);
                    $sql.= "AND ID NOT IN($excluded_posts_str)";
                }
            }
        }
        return $sql;
    }
    
    function show_post_sql($sql)
    {
        $uamOptions = $this->getAdminOptions();
        if (($uamOptions['hide_post'] == 'true' && !is_feed()) || (is_feed() && $uamOptions['protect_feed'] == 'true')) {
            $posts = get_posts();
            if (isset($posts)) {
                foreach ($posts as $post) {
                    if (!$this->check_access($post->ID)) $excluded_posts[] = $post->ID;
                }
                if (isset($excluded_posts)) {
                    $excluded_posts_str = implode(",", $excluded_posts);
                    $sql.= "AND ID NOT IN($excluded_posts_str)";
                }
            }
        }
        return $sql;
    }
    
    function admin_output($post_id)
    {
        global $wpdb;
        if (!$this->atAdminPanel) {
            $uamOptions = $this->getAdminOptions();
            if ($uamOptions['blog_admin_hint'] == 'true') {
                global $current_user;
                $cur_userdata = get_userdata($current_user->ID);
                $output = "";
                $access = $this->get_access($post_id);
                
                if (!isset($cur_userdata->user_level)) {
                    $cur_userdata->user_level = null;
                }
                
                if ($cur_userdata->user_level >= $uamOptions['full_access_level'] 
                    && (isset($access->restricted_by_posts) 
                    || isset($access->restricted_by_categories))
                ) { 
                    $output = "&nbsp;" . $uamOptions['blog_admin_hint_text'];
                }
                
                return $output;
            }
        }
        return null;
    }
    
    function redirect_user()
    {
        global $wp_query;
        $uamOptions = $this->getAdminOptions();
        if (isset($_GET['getfile'])) $cur_file_id = $_GET['getfile'];
        if ($uamOptions['redirect'] != 'false' && ((!$this->check_access() && !$this->atAdminPanel && empty($cur_file_id)) || (!$this->check_access($cur_file_id) && !wp_attachment_is_image($cur_file_id) && isset($cur_file_id)))) {
            $cur_id = null;
            $cur_post = & get_post($cur_id);
            if ($uamOptions['redirect'] == 'blog') $url = get_option('siteurl');
            elseif ($uamOptions['redirect'] == 'custom_page') {
                $post_to_go = & get_post($uamOptions['redirect_custom_page']);
                $url = $post_to_go->guid;
            } elseif ($uamOptions['redirect'] == 'custom_url') $url = $uamOptions['redirect_custom_url'];
            $cur_posts = $wp_query->get_posts();
            if (isset($cur_posts)) {
                foreach ($cur_posts as $cur_post) {
                    if ($this->check_access($cur_post->ID)) {
                        $post_to_show = true;
                        break;
                    }
                }
            }
            if ($url != "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"] && empty($post_to_show)) {
                header("Location: $url");
                exit;
            }
        } elseif (isset($_GET['getfile'])) {
            $cur_id = $_GET['getfile'];
            $cur_post = & get_post($cur_id);
            if ($cur_post->post_type == 'attachment' && $this->check_access($cur_post->ID)) {
                $file = str_replace(get_option('siteurl') . '/', "", $cur_post->guid);
                $filename = basename($file);
                if (file_exists($file)) {
                    $len = filesize($file);
                    header('content-type: ' . $cur_post->post_mime_type);
                    header('content-length: ' . $len);
                    if (wp_attachment_is_image($cur_id)) {
                        readfile($file);
                        exit;
                    } else {
                        header('content-disposition: attachment; filename=' . basename($file));
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
            } elseif (wp_attachment_is_image($cur_id)) {
                $file = UAM_URLPATH . 'gfx/no_access_pic.png';
                $filename = basename($file);
                if (file_exists($file)) {
                    $len = filesize($file);
                    header('content-type: ' . $cur_post->post_mime_type);
                    header('content-length: ' . $len);
                    readfile($file);
                    exit;
                } else {
                    echo 'Error: File not found';
                }
            }
        }
    }
    
    function get_file($URL, $ID)
    {
        $uamOptions = $this->getAdminOptions();
        if ($uamOptions['lock_file'] == 'true') {
            $cur_id = $ID;
            $cur_post = & get_post($cur_id);
            $cur_parent_id = $cur_post->post_parent;
            $cur_parent = & get_post($cur_parent_id);
            $type = explode("/", $cur_post->post_mime_type);
            $type = $type[1];
            $file_types = $uamOptions['locked_file_types'];
            $file_types = explode(",", $file_types);
            if (in_array($type, $file_types) || $uamOptions['lock_file_types'] == 'all') {
                $cur_guid = get_bloginfo('url');
                $URL = $cur_guid . '?getfile=' . $cur_post->ID;
            }
        }
        return $URL;
    }
}