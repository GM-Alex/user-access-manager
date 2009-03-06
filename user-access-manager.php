<?php
/* 
Plugin Name: User Access Manager
Plugin URI: http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/
Author URI: http://www.gm-alex.de/
Version: 0.8.0.1
Author: Alexander Schneider
Description: Manage the access to your posts and pages. <strong>Note:</strong> <em>If you activate the plugin your upload dir will protect by a '.htaccess' with a random password and all old downloads insert in a previous post/page will not work anymore. You have to update your posts/pages. If you use already a '.htaccess' file to protect your files the plugin will <strong>overwrite</strong> the '.htaccess'. You can disabel the file locking and set up an other password for the '.htaccess' file at the UAM setting page.</em>
 
Copyright 2008  Alexander Schneider  (email :  alexanderschneider85 [at] gmail DOT com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Uses an Image by: Everaldo Coelho - http://www.everaldo.com/
*/

//DB
global $wpdb;
define('DB_ACCESSGROUP', $wpdb->prefix.'uam_accessgroups');
define('DB_ACCESSGROUP_TO_POST', $wpdb->prefix.'uam_accessgroup_to_post');
define('DB_ACCESSGROUP_TO_USER', $wpdb->prefix.'uam_accessgroup_to_user');
define('DB_ACCESSGROUP_TO_CATEGORY', $wpdb->prefix.'uam_accessgroup_to_category');
define('DB_ACCESSGROUP_TO_ROLE', $wpdb->prefix.'uam_accessgroup_to_role');

//PATH
define('UAM_URLPATH', WP_CONTENT_URL.'/plugins/user-access-manager/');//define('UAM_URLPATH', WP_CONTENT_URL.'/plugins/'.plugin_basename( dirname(__FILE__)).'/' );

//###Lang###

if (!class_exists("UserAccessManager"))
{
	class UserAccessManager
	{
		var $adminOptionsName = "uamAdminOptions";
		var $atAdminPanel = false;
		var $restrictedPost = array();
		var $postAccess = array();
		var $uam_db_version = "1.1";
		var $adminOptions;
		
		function UserAccessManager() 
		{ //constructor
			
		}
		
		function init()
		{
			echo load_plugin_textdomain('user-access-manager', 'wp-content/plugins/user-access-manager');
			
			//---Lang Settings---
			define('TXT_SETTINGS', __('Settings', 'user-access-manager'));
			
			define('TXT_POST_SETTING', __('Post settings', 'user-access-manager'));
			define('TXT_POST_SETTING_DESC', __('Set up the behaviour of locked posts', 'user-access-manager'));
			define('TXT_POST_TITLE', __('Post title', 'user-access-manager'));
			define('TXT_POST_TITLE_DESC', __('Displayed text as post title if user has no access', 'user-access-manager'));
			define('TXT_DISPLAY_POST_TITLE', __('Hide post titel', 'user-access-manager'));
			define('TXT_DISPLAY_POST_TITLE_DESC', sprintf(__('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'), TXT_POST_TITLE));
			define('TXT_POST_CONTENT', __('Post content', 'user-access-manager'));
			define('TXT_POST_CONTENT_DESC', __('Content displayed if user has no access', 'user-access-manager'));
			define('TXT_HIDE_POST', __('Hide complete posts', 'user-access-manager'));
			define('TXT_HIDE_POST_DESC', __('Selecting "Yes" will hide posts if the user has no access.', 'user-access-manager'));
			define('TXT_POST_COMMENT_CONTENT', __('Post commtent text', 'user-access-manager'));
			define('TXT_POST_COMMENT_CONTENT_DESC', __('Displayed text as post comment text if user has no access', 'user-access-manager'));
			define('TXT_DISPLAY_POST_COMMENT', __('Hide post comments', 'user-access-manager'));
			define('TXT_DISPLAY_POST_COMMENT_DESC', sprintf(__('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'), TXT_POST_COMMENT_CONTENT) );
			define('TXT_ALLOW_COMMENTS_LOCKED', __('Allow comments', 'user-access-manager'));
			define('TXT_ALLOW_COMMENTS_LOCKED_DESC', __('Selecting "yes" allows users to comment on locked posts', 'user-access-manager'));
			
			define('TXT_PAGE_SETTING', __('Page settings', 'user-access-manager'));
			define('TXT_PAGE_SETTING_DESC', __('Set up the behaviour of locked pages', 'user-access-manager'));
			define('TXT_PAGE_TITLE', __('Page title', 'user-access-manager'));
			define('TXT_PAGE_TITLE_DESC', __('Displayed text as page title if user has no access', 'user-access-manager'));
			define('TXT_DISPLAY_PAGE_TITLE', __('Hide page titel', 'user-access-manager'));
			define('TXT_DISPLAY_PAGE_TITLE_DESC', sprintf(__('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'), TXT_POST_TITLE));
			define('TXT_PAGE_CONTENT', __('Page content', 'user-access-manager'));
			define('TXT_PAGE_CONTENT_DESC', __('Content displayed if user has no access', 'user-access-manager'));
			define('TXT_HIDE_PAGE', __('Hide complete pages', 'user-access-manager'));
			define('TXT_HIDE_PAGE_DESC', __('Selecting "Yes" will hide pages if the user has no access. Pages will also hide in the navigation.', 'user-access-manager'));
			
			define('TXT_FILE_SETTING', __('File settings', 'user-access-manager'));
			define('TXT_FILE_SETTING_DESC', __('Set up the behaviour of files', 'user-access-manager'));
			define('TXT_LOCK_FILE', __('Lock files', 'user-access-manager'));
			define('TXT_LOCK_FILE_DESC', __('If you select "Yes" all files will locked by a .htaccess file and only users with access can download files.', 'user-access-manager'));
			define('TXT_SELECTED_FILE_TYPES', __('Filetypes to lock: ', 'user-access-manager'));
			define('TXT_NOT_SELECTED_FILE_TYPES', __('Filetypes not to lock: ', 'user-access-manager'));
			define('TXT_DOWNLOAD_FILE_TYPE', __('Locked file types', 'user-access-manager'));
			define('TXT_DOWNLOAD_FILE_TYPE_DESC', __('Lock all files, type in file types which you will lock if the post/page is locked or define file types which will not be locked. <b>Note:</b> If you have no problems use all to get the maximum security.', 'user-access-manager'));
			define('TXT_FILE_PASS_TYPE', __('.htaccess password', 'user-access-manager'));
			define('TXT_FILE_PASS_TYPE_DESC', __('Set up the password for the .htaccess access. This password is only needed if you need a direct access to your files.', 'user-access-manager'));
			define('TXT_RANDOM_PASS', __('Use a random generated pass word.', 'user-access-manager'));
			define('TXT_CURRENT_LOGGEDIN_ADMIN_PASS', __('Use the password of the current logged in admin.', 'user-access-manager'));
			define('TXT_DOWNLOAD_TYPE', __('Download type', 'user-access-manager'));
			define('TXT_DOWNLOAD_TYPE_DESC', __('Selecting the type for downloading. <strong>Note:</strong> For using fopen you need "safe_mode = off".', 'user-access-manager'));
			define('TXT_NORMAL', __('Normal', 'user-access-manager'));
			define('TXT_FOPEN', __('fopen', 'user-access-manager'));
			
			define('TXT_OTHER_SETTING', __('Other settings', 'user-access-manager'));
			define('TXT_OTHER_SETTING_DESC', __('Here you will find all other settings', 'user-access-manager'));
			define('TXT_HIDE_EMPTY_CATEGORIES', __('Hide empty categories', 'user-access-manager'));
			define('TXT_HIDE_EMPTY_CATEGORIES_DESC', __('Selecting "Yes" will hide empty categories which are containing only empty childs or no childs.', 'user-access-manager'));
			define('TXT_REDIRECT', __('Redirect user', 'user-access-manager'));
			define('TXT_REDIRECT_DESC', __('Setup what happen if a user visit a post/page with no access.', 'user-access-manager'));
			define('TXT_REDIRECT_TO_BOLG', __('To blog startpage', 'user-access-manager'));
			define('TXT_REDIRECT_TO_PAGE', __('Custom page: ', 'user-access-manager'));
			define('TXT_REDIRECT_TO_URL', __('Custom URL: ', 'user-access-manager'));
			define('TXT_LOCK_RECURSIVE', __('Lock recursive', 'user-access-manager'));
			define('TXT_LOCK_RECURSIVE_DESC', __('Selecting "Yes" will lock all child posts/pages of a post/page if a user has no access to the parent page. Note: Setting this option to "No" could result in display errors relating to the hierarchy.', 'user-access-manager'));
			define('TXT_BLOG_ADMIN_HINT_TEXT', __('Admin hint text', 'user-access-manager'));
			define('TXT_BLOG_ADMIN_HINT_TEXT_DESC', __('The text which will shown behinde the post/page.', 'user-access-manager'));
			define('TXT_BLOG_ADMIN_HINT', __('Show admin hint at Posts', 'user-access-manager'));
			define('TXT_BLOG_ADMIN_HINT_DESC', sprintf(__('Selecting "Yes" will show the defined text at "%s" behinde the post/page to an logged in admin to show him which posts/pages are locked if he visits his blog.', 'user-access-manager'), TXT_BLOG_ADMIN_HINT_TEXT));
			define('TXT_CORE_MOD', __('Core modifications installed?', 'user-access-manager'));
			define('TXT_CORE_MOD_DESC', __('If you installed the core modifications activated this option.', 'user-access-manager'));
			
			define('TXT_YES', __('Yes', 'user-access-manager'));
			define('TXT_NO', __('No', 'user-access-manager'));
			
			define('TXT_UPDATE_SETTING', __('Update settings', 'user-access-manager'));
			define('TXT_UPDATE_SETTINGS', __('Settings updated.', 'user-access-manager'));
			
			//---Access groups---
			
			define('TXT_MANAGE_GROUP', __('Manage user access groups', 'user-access-manager'));
			define('TXT_GROUP_ROLE', __('Role affiliation', 'user-access-manager'));
			define('TXT_NAME', __('Name', 'user-access-manager'));
			define('TXT_DESCRIPTION', __('Description', 'user-access-manager'));
			define('TXT_READ_ACCESS', __('Read access', 'user-access-manager'));
			define('TXT_WRITE_ACCESS', __('Write access', 'user-access-manager'));
			define('TXT_POSTS', __('Posts', 'user-access-manager'));
			define('TXT_PAGES', __('Pages', 'user-access-manager'));
			define('TXT_FILES', __('Files', 'user-access-manager'));
			define('TXT_CATEGORY', __('Categories', 'user-access-manager'));
			define('TXT_USERS', __('Users', 'user-access-manager'));
			define('TXT_DELETE', __('Delete', 'user-access-manager'));
			define('TXT_UPDATE_GROUP', __('Update group', 'user-access-manager'));
			define('TXT_ADD', __('Add', 'user-access-manager'));
			define('TXT_ADD_GROUP', __('Add access group', 'user-access-manager'));
			define('TXT_GROUP_NAME', __('Access group name', 'user-access-manager'));
			define('TXT_GROUP_NAME_DESC', __('The name is used to identify the access user group.', 'user-access-manager'));
			define('TXT_GROUP_DESC', __('Access group description', 'user-access-manager'));
			define('TXT_GROUP_DESC_DESC', __('The description of the group.', 'user-access-manager'));
			define('TXT_GROUP_IP_RANGE', __('IP range', 'user-access-manager'));
			define('TXT_GROUP_IP_RANGE_DESC', __('Type in the IP ranges of users which are join these groups by there IP address without loggin. Set ranges like "BEGIN"-"END", seperate ranges by using ";", single IPs are also allowded. Example: 192.168.0.1-192.168.0.10;192.168.0.20-192.168.0.30', 'user-access-manager'));
			define('TXT_GROUP_READ_ACCESS', __('Read access', 'user-access-manager'));
			define('TXT_GROUP_READ_ACCESS_DESC', __('The read access.', 'user-access-manager'));
			define('TXT_GROUP_WRITE_ACCESS', __('Write access', 'user-access-manager'));
			define('TXT_GROUP_WRITE_ACCESS_DESC', __('The write access.', 'user-access-manager'));
			define('TXT_GROUP_ADDED', __('Group was added successfully.', 'user-access-manager'));
			define('TXT_DEL_GROUP', __('Group(s) was deleted successfully.', 'user-access-manager'));
			define('TXT_NONE', __('none', 'user-access-manager')); 
			define('TXT_ACCESS_GROUP_EDIT_SUC', __('Access group edit successfully.', 'user-access-manager'));
			define('TXT_CONTAIN_POSTS', __('Contains %1$s of %2$s posts', 'user-access-manager'));
			define('TXT_CONTAIN_PAGES', __('Contains %1$s of %2$s pages', 'user-access-manager'));
			define('TXT_CONTAIN_FILES', __('Contains %1$s of %2$s files', 'user-access-manager'));
			define('TXT_CONTAIN_CATEGORIES', __('Contains %1$s of %2$s categories', 'user-access-manager'));
			define('TXT_CONTAIN_USERS', __('Contains %1$s of %2$s users', 'user-access-manager'));
			define('TXT_IP_RANGE', __('IP range', 'user-access-manager'));
			
			//---Misc---
			define('TXT_FULL_ACCESS', __('Full access', 'user-access-manager'));
			define('TXT_FULL_ACCESS_ADMIN', __('Full access (Administrator)', 'user-access-manager'));
			define('TXT_NO_GROUP', __('No group', 'user-access-manager'));
			define('TXT_SET_ACCESS', __('Set access', 'user-access-manager'));
			
			define('TXT_DATE', __('Date', 'user-access-manager'));
			define('TXT_TITLE', __('Title', 'user-access-manager'));
			define('TXT_GROUP_ACCESS', __('Group access', 'user-access-manager'));
			define('TXT_USERNAME', __('Username', 'user-access-manager'));
			
			define('TXT_MAIL', __('E-mail', 'user-access-manager'));
			define('TXT_ACCESS', __('Access', 'user-access-manager'));
			define('TXT_ADMIN_HINT', __('<strong>Note:</strong> An administrator has allways access to all posts/pages.', 'user-access-manager'));
			
			define('TXT_SET_POST_ACCESS', __('Set post access', 'user-access-manager'));
			define('TXT_SET_PAGE_ACCESS', __('Set page access', 'user-access-manager'));
			define('TXT_GROUPS', __('Access Groups', 'user-access-manager'));
			define('TXT_CREATE_GROUP_FIRST', __('Please create a access group first.', 'user-access-manager'));
			define('TXT_SET_USER_ACCESS', __('Set user access', 'user-access-manager'));
			
			define('TXT_SET_UP_USERGROUPS', __('Set up usergroups', 'user-access-manager'));
			
			define('TXT_ITSELF', __('itself', 'user-access-manager'));
			define('TXT_INFO', __('Info', 'user-access-manager'));
			define('TXT_GROUP_INFO', __('Group infos', 'user-access-manager'));
			define('TXT_GROUP_LOCK_INFO', __('Locked by', 'user-access-manager'));
			define('TXT_IS_ADMIN', __('User is Admin. Full access.', 'user-access-manager'));
			define('TXT_EXPAND', __('expand', 'user-access-manager'));
			define('TXT_EXPAND_ALL', __('expand all', 'user-access-manager'));
			define('TXT_COLLAPS', __('collaps', 'user-access-manager'));
			define('TXT_COLLAPS_ALL', __('collaps all', 'user-access-manager'));
			define('TXT_ALL', __('all', 'user-access-manager'));
			define('TXT_ONLY_GROUP_USERS', __('only group users', 'user-access-manager'));
		}
		
		function install()
		{
			$this->create_htaccess();
			$this->create_htpasswd();
			
			global $wpdb;
			$uam_db_version = $this->uam_db_version;
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			$charset_collate = '';

			if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') )
			{
				if ( ! empty($wpdb->charset) )
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				if ( ! empty($wpdb->collate) )
					$charset_collate .= " COLLATE $wpdb->collate";
			}
				
			if($wpdb->get_var("show tables like '".DB_ACCESSGROUP."'") != DB_ACCESSGROUP)
			{			
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
			
			if($wpdb->get_var("show tables like '".DB_ACCESSGROUP_TO_POST."'") != DB_ACCESSGROUP_TO_POST)
			{							
				$sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_POST . " ( 
						  post_id int(11) NOT NULL,
						  group_id int(11) NOT NULL,
						  PRIMARY KEY  (post_id,group_id)
						) $charset_collate;";
	
				dbDelta($sql);
			}
			
			if($wpdb->get_var("show tables like '".DB_ACCESSGROUP_TO_USER."'") != DB_ACCESSGROUP_TO_USER)
			{			
				$sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_USER . " (
						  user_id int(11) NOT NULL,
						  group_id int(11) NOT NULL,
						  PRIMARY KEY  (user_id,group_id)
						) $charset_collate;";		  
	
				dbDelta($sql);
			}
			
			if($wpdb->get_var("show tables like '".DB_ACCESSGROUP_TO_CATEGORY."'") != DB_ACCESSGROUP_TO_CATEGORY)
			{			
				$sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_CATEGORY . " (
						  category_id int(11) NOT NULL,
						  group_id int(11) NOT NULL,
						  PRIMARY KEY  (category_id,group_id)
						) $charset_collate;";		  
	
				dbDelta($sql);
			}
			
			if($wpdb->get_var("show tables like '".DB_ACCESSGROUP_TO_ROLE."'") != DB_ACCESSGROUP_TO_ROLE)
			{			
				$sql = "CREATE TABLE " . DB_ACCESSGROUP_TO_ROLE . " (
						  role_name varchar(255) NOT NULL,
						  group_id int(11) NOT NULL,
						  PRIMARY KEY  (role_name,group_id)
						) $charset_collate;";		  
	
				dbDelta($sql);
			}
				
			add_option("uam_db_version", $uam_db_version);
		}
		
		function update()
		{
			global $wpdb;
			$uam_db_version = $this->uam_db_version;
			$installed_ver = get_option( "uam_db_version" );
			
			if($installed_ver != $uam_db_version)
			{
				if($installed_ver == '1.0')
				{
					if($wpdb->get_var("show tables like '".DB_ACCESSGROUP."'") == DB_ACCESSGROUP)
					{			
						$wpdb->query("ALTER TABLE ".DB_ACCESSGROUP." ADD read_access TINYTEXT NOT NULL DEFAULT '', ADD write_access TINYTEXT NOT NULL DEFAULT '', ADD ip_range MEDIUMTEXT NULL DEFAULT ''");
						$wpdb->query("UPDATE ".DB_ACCESSGROUP." SET read_access = 'group', write_access = 'group'");
						update_option("uam_db_version", $uam_db_version);
					}
				}
			}
			if($wpdb->get_var("show columns from ".DB_ACCESSGROUP." like 'ip_range'") != 'ip_range')
			{
				$wpdb->query("ALTER TABLE ".DB_ACCESSGROUP." ADD ip_range MEDIUMTEXT NULL DEFAULT ''");
			}
		}
		
		function uninstall()
		{
			global $wpdb;
			$wpdb->query("DROP TABLE ".DB_ACCESSGROUP.", ".DB_ACCESSGROUP_TO_POST.", ".DB_ACCESSGROUP_TO_USER.", ".DB_ACCESSGROUP_TO_CATEGORY.", ".DB_ACCESSGROUP_TO_ROLE);
		}
		
		function create_htaccess()
		{
			// Make .htaccess file to protect data
			
			// get url
			$wud = wp_upload_dir();
			if(empty($wud['error']))
			{
				$url = $wud['basedir']."/";
				
				$areaname = "WP-Files";
					
				$uamOptions = $this->getAdminOptions();

				if($uamOptions['lock_file_types'] == 'selected')
					$file_types = $uamOptions['locked_file_types'];
				elseif($uamOptions['lock_file_types'] == 'not_selected')
					$file_types = $uamOptions['not_locked_file_types'];

				if(isset($file_types))
					$file_types = str_replace(",", "|", $file_types);
					
				// make .htaccess and .htpasswd
					
				$htaccess_txt = "";
				
				if($uamOptions['lock_file_types'] == 'selected')
						$htaccess_txt .= "<FilesMatch '\.(".$file_types.")'>\n";
						
				if($uamOptions['lock_file_types'] == 'not_selected')
						$htaccess_txt .= "<FilesMatch '^\.(".$file_types.")'>\n";

				$htaccess_txt .= "AuthType Basic"."\n";
				$htaccess_txt .= "AuthName \"".$areaname."\""."\n";
				$htaccess_txt .= "AuthUserFile ".$url.".htpasswd"."\n";
				$htaccess_txt .= "require valid-user"."\n";
					
				if($uamOptions['lock_file_types'] == 'selected' || $uamOptions['lock_file_types'] == 'not_selected')
					$htaccess_txt .= "</FilesMatch>\n";

				// save files
				$htaccess = fopen($url.".htaccess", "w");
				fwrite($htaccess, $htaccess_txt);
				fclose($htaccess);
			}
		}
		
		function create_htpasswd($create_new = false)
		{
			global $current_user;
			
			$uamOptions = $this->getAdminOptions();
			
			// get url
			$wud = wp_upload_dir();
			
			if(empty($wud['error']))
			{
				$url = $wud['basedir']."/";
				$cur_userdata = get_userdata($current_user->ID);
				
				$user = $cur_userdata->user_login;
				
				if(!file_exists($url.".htpasswd") || $create_new)
				{
					if($uamOptions['file_pass_type'] == 'random')
					{
						# create password
					    $array = array();
					
					    $length = 10;
						$capitals = true;
						$specialSigns = false;
						
					    if($length < 8)
					     	$length = mt_rand(8,20);
					
					    # numbers
					    for($i=48;$i<58;$i++)
					      	$array[] = chr($i);
					
					    # small
					    for($i=97;$i<122;$i++)
					      	$array[] = chr($i);
					
					    # capitals
					    if($capitals)
					      for($i=65;$i<90;$i++)
					        $array[] = chr($i);
					
					    # specialchar:
					    if($specialSigns)
					    {
					    	for($i=33;$i<47;$i++)
					      		$array[] = chr($i);
					      	for($i=59;$i<64;$i++)
					     	   $array[] = chr($i);
					     	 for($i=91;$i<96;$i++)
					        	$array[] = chr($i);
					      	for($i=123;$i<126;$i++)
					        	$array[] = chr($i);
					    }
					
					    mt_srand((double)microtime()*1000000);
					    $password = '';
					
					    for ($i=1; $i<=$length; $i++)
					    {
					      	$rnd = mt_rand( 0, count($array)-1 );
					      	$password .= $array[$rnd];
					      	$password = md5($password);
					    }
					}
					elseif($uamOptions['file_pass_type'] == 'admin')
					{
						$password = $cur_userdata->user_pass;
					}
						
				    // make .htpasswd
				    $htpasswd_txt = "$user:".$password."\n";
					
					// save file
					$htpasswd = fopen($url.".htpasswd", "w");
					fwrite($htpasswd, $htpasswd_txt);
					fclose($htpasswd);
				}
			}
		}
		
		function delete_htaccess_files()
		{
			$wud = wp_upload_dir();
			if(empty($wud['error']))
			{
				$url = $wud['basedir']."/";
				unlink($url.".htaccess");
				unlink($url.".htpasswd");
			}
		}
		
		//Returns an array of admin options
		function getAdminOptions() 
		{
			if($this->atAdminPanel || empty($this->adminOptions))
			{
				$uamAdminOptions = array(	'hide_post_title' => 'false',
											'post_title' => __('No rights!', 'user-access-manager'),
											'hide_post_comment' => 'false',
											'post_comment_content' => __('Sorry no rights to view comments!', 'user-access-manager'),
											'allow_comments_locked' => 'false',
											'post_content' => 'Sorry no rights!',
											'hide_post' => 'false',
											'hide_page_title' => 'false',
											'page_title' => 'No rights!',
											'page_content' => __('Sorry you have no rights to view this page!', 'user-access-manager'),
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
											'hide_empty_categories' => 'true');
				
				$uamOptions = get_option($this->adminOptionsName);
				if (!empty($uamOptions)) {
					foreach ($uamOptions as $key => $option)
						$uamAdminOptions[$key] = $option;
				}				
				update_option($this->adminOptionsName, $uamAdminOptions);
				$this->adminOptions = $uamAdminOptions;
			}
			return $this->adminOptions;
		}
		
		//Prints out the admin page
		function printAdminPage()
		{
			global $wpdb;
			
			$uamOptions = $this->getAdminOptions();
										
			if (isset($_POST['update_uam_settings']))
			{
				if (isset($_POST['uam_hide_post_title']))
				{
					$uamOptions['hide_post_title'] = $_POST['uam_hide_post_title'];
				}
				if (isset($_POST['uam_post_title']))
				{
					$uamOptions['post_title'] = $_POST['uam_post_title'];
				}
				if (isset($_POST['uam_post_content']))
				{
					$uamOptions['post_content'] = $_POST['uam_post_content'];
				}
				if (isset($_POST['uam_hide_post']))
				{
					$uamOptions['hide_post'] = $_POST['uam_hide_post'];
				}
				if (isset($_POST['uam_hide_post_comment']))
				{
					$uamOptions['hide_post_comment'] = $_POST['uam_hide_post_comment'];
				}
				if (isset($_POST['uam_post_comment_content']))
				{
					$uamOptions['post_comment_content'] = $_POST['uam_post_comment_content'];
				}
				if (isset($_POST['uam_allow_comments_locked']))
				{
					$uamOptions['allow_comments_locked'] = $_POST['uam_allow_comments_locked'];
				}
				if (isset($_POST['uam_hide_page_title']))
				{
					$uamOptions['hide_page_title'] = $_POST['uam_hide_page_title'];
				}
				if (isset($_POST['uam_page_title']))
				{
					$uamOptions['page_title'] = $_POST['uam_page_title'];
				}
				if (isset($_POST['uam_page_content']))
				{
					$uamOptions['page_content'] = $_POST['uam_page_content'];
				}
				if (isset($_POST['uam_hide_page']))
				{
					$uamOptions['hide_page'] = $_POST['uam_hide_page'];
				}
				if (isset($_POST['uam_hide_empty_categories']))
				{
					$uamOptions['hide_empty_categories'] = $_POST['uam_hide_empty_categories'];
				}
				if (isset($_POST['uam_redirect']))
				{
					$uamOptions['redirect'] = $_POST['uam_redirect'];
				}
				if (isset($_POST['uam_redirect_custom_page']))
				{
					$uamOptions['redirect_custom_page'] = $_POST['uam_redirect_custom_page'];
				}
				if (isset($_POST['uam_redirect_custom_url']))
				{
					$uamOptions['redirect_custom_url'] = $_POST['uam_redirect_custom_url'];
				}
				if (isset($_POST['uam_lock_recursive']))
				{
					$uamOptions['lock_recursive'] = $_POST['uam_lock_recursive'];
				}
				if (isset($_POST['uam_blog_admin_hint']))
				{
					$uamOptions['blog_admin_hint'] = $_POST['uam_blog_admin_hint'];
				}
				if (isset($_POST['uam_blog_admin_hint_text']))
				{
					$uamOptions['blog_admin_hint_text'] = $_POST['uam_blog_admin_hint_text'];
				}
				if (isset($_POST['uam_core_mod']))
				{
					$uamOptions['core_mod'] = $_POST['uam_core_mod'];
				}
				if (isset($_POST['uam_lock_file']))
				{
					if($_POST['uam_lock_file'] == 'false')
					{
						if($uamOptions['lock_file'] != $_POST['uam_lock_file'])
							$this->delete_htaccess_files();
					}
					else
					{
						if($uamOptions['lock_file'] != $_POST['uam_lock_file'])
							$lock_file_changed = true;
					}
					$uamOptions['lock_file'] = $_POST['uam_lock_file'];
				}
				
				if (isset($_POST['uam_file_pass_type']))
				{
					if($uamOptions['file_pass_type'] != $_POST['uam_file_pass_type'])
						$file_pass_changed = true;
					else
						$file_pass_changed = false;
						
					$uamOptions['file_pass_type'] = $_POST['uam_file_pass_type'];
				}
				if (isset($_POST['uam_download_type']))
				{
					$uamOptions['download_type'] = $_POST['uam_download_type'];
				}
				if (isset($_POST['uam_locked_file_types']))
				{
					if($uamOptions['locked_file_types'] != $_POST['uam_locked_file_types'])
						$locked_file_types_changed = true;
					else
						$locked_file_types_changed = false;
					
					$uamOptions['locked_file_types'] = $_POST['uam_locked_file_types'];
				}
				if (isset($_POST['uam_not_locked_file_types']))
				{
					if($uamOptions['not_locked_file_types'] != $_POST['uam_not_locked_file_types'])
						$not_locked_file_types_changed = true;
					else
						$not_locked_file_types_changed = false;
					
					$uamOptions['not_locked_file_types'] = $_POST['uam_not_locked_file_types'];
				}
				if (isset($_POST['uam_lock_file_types']))
				{
					if($uamOptions['lock_file_types'] != $_POST['uam_lock_file_types'])
						$locked_file_types_changed = true;
					
					$uamOptions['lock_file_types'] = $_POST['uam_lock_file_types'];
				}
				
				update_option($this->adminOptionsName, $uamOptions);
				
				if(($locked_file_types_changed || $not_locked_file_types_changed || isset($lock_file_changed) || $file_pass_changed) && $uamOptions['lock_file'] != 'false')
				{
					$this->create_htaccess();
					$this->create_htpasswd(true);
				}
				?>
					<div class="updated"><p><strong><?php echo TXT_UPDATE_SETTINGS; ?></strong></p></div>
				<?php
			} 
			
			if(isset($_GET['page']))
				$cur_admin_page = $_GET['page'];
			
			if(isset($_GET['action']))
				$action = $_GET['action'];
			else
				$action = null;
			
			if(isset($_POST['action']))
				$post_action = $_POST['action'];
			else
				$post_action = null;
			
			if($post_action == 'delgroup')
			{
				if(isset($_POST['delete']))
					$del_ids = $_POST['delete'];
					
				if(isset($del_ids))
				{
					foreach($del_ids as $del_id)
					{
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP." WHERE ID = $del_id LIMIT 1");
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE group_id = $del_id");
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE group_id = $del_id");
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_CATEGORY." WHERE group_id = $del_id");
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_ROLE." WHERE group_id = $del_id");
					}
					?>
						<div class="updated"><p><strong><?php echo TXT_DEL_GROUP; ?></strong></p></div> 
					<?php
				}
			}
			
			if($post_action == 'update_group' || $post_action == 'addgroup')
			{
				if($post_action == 'addgroup')
				{
					$wpdb->query("INSERT INTO ".DB_ACCESSGROUP." (ID, groupname, groupdesc, read_access, write_access, ip_range) VALUES(NULL, '".$_POST['access_group_name']."', '".$_POST['access_group_description']."', '".$_POST['read_access']."', '".$_POST['write_access']."', '".$_POST['ip_range']."')");
					
					$group_id = $wpdb->insert_id;
				}
				elseif($post_action == 'update_group')
				{
					$wpdb->query("	UPDATE ".DB_ACCESSGROUP." 
									SET groupname = '".$_POST['access_group_name']."', groupdesc = '".$_POST['access_group_description']."', read_access = '".$_POST['read_access']."', write_access = '".$_POST['write_access']."', ip_range = '".$_POST['ip_range']."'
									WHERE ID = ".$_POST['access_group_id']);
					
					$group_id = $_POST['access_group_id'];
					
					$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_ROLE." WHERE group_id = ".$group_id);
					$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE group_id = ".$group_id);
					$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_CATEGORY." WHERE group_id = ".$group_id);
					$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE group_id = ".$group_id);
				}
				
				if(isset($_POST['roles']))
					$roles = $_POST['roles'];
				else
					$roles = null;
				
				if($roles)
				{
					foreach($roles as $role)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_ROLE." (group_id, role_name) VALUES('".$group_id."', '".$role."')");
					}
				}
				
				if(isset($_POST['post']))
					$posts = $_POST['post'];
				else
					$posts = null;
					
				if($posts)
				{
					foreach($posts as $post)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_POST." (group_id, post_id) VALUES('".$group_id."', '".$post."')");
					}
				}
				
				if(isset($_POST['page']))
					$pages = $_POST['page'];
				else
					$pages = null;
					
				if($pages)
				{
					foreach($pages as $page)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_POST." (group_id, post_id) VALUES('".$group_id."', '".$page."')");
					}
				}
				
				if(isset($_POST['file']))
					$files = $_POST['file'];
				else
					$files = null;
					
				if($files)
				{
					foreach($files as $file)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_POST." (group_id, post_id) VALUES('".$group_id."', '".$file."')");
					}
				}
				
				if(isset($_POST['category']))
					$categories = $_POST['category'];
				else
					$categories = null;
					
				if($categories)
				{
					foreach($categories as $category)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_CATEGORY." (group_id, category_id) VALUES('".$group_id."', '".$category."')");
					}
				}
				
				if(isset($_POST['user']))
					$users = $_POST['user'];
				else
					$users = null;
					
				if($users)
				{
					foreach($users as $user)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_USER." (group_id, user_id) VALUES('".$group_id."', '".$user."')");
					}
				}
				
				if($post_action == 'addgroup')
				{
				?>
					<div class="updated"><p><strong><?php echo TXT_GROUP_ADDED; ?></strong></p></div> 
				<?php
				}
				elseif($post_action == 'update_group')
				{
				?>
					<div class="updated"><p><strong><?php echo TXT_ACCESS_GROUP_EDIT_SUC; ?></strong></p></div> 
				<?php
				}
			}
			
			if($cur_admin_page == 'uam_settings')
			{
				?>
				<div class=wrap>
					<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
						<h2><?php echo TXT_SETTINGS; ?></h2>
						<h3><?php echo TXT_POST_SETTING; ?></h3>
						<p><?php echo TXT_POST_SETTING_DESC; ?></p>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_HIDE_POST; ?>
									</th>
									<td>
										<label for="uam_hide_post_yes">
											<input type="radio" id="uam_hide_post_yes" class="uam_hide_post" name="uam_hide_post" value="true" <?php if ($uamOptions['hide_post'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_post_no">
											<input type="radio" id="uam_hide_post_no" class="uam_hide_post" name="uam_hide_post" value="false" <?php if ($uamOptions['hide_post'] == "false") { echo 'checked="checked"'; }?>/>
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_HIDE_POST_DESC; ?>
									</td>
								</tr>
							</tbody>
						</table>
						<table class="form-table" id="uam_post_settings">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_DISPLAY_POST_TITLE; ?>
									</th>
									<td>
										<label for="uam_hide_post_title_yes">
											<input type="radio" id="uam_hide_post_title_yes" name="uam_hide_post_title" value="true" <?php if ($uamOptions['hide_post_title'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_post_title_no">
											<input type="radio" id="uam_hide_post_title_no" name="uam_hide_post_title" value="false" <?php if ($uamOptions['hide_post_title'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_DISPLAY_POST_TITLE_DESC; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_POST_TITLE; ?>
									</th>
									<td>
										<input name="uam_post_title" value="<?php echo $uamOptions['post_title']; ?>" />
										<br />
										<?php echo TXT_POST_TITLE_DESC; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_POST_CONTENT; ?>
									</th>
									<td>
										<textarea name="uam_post_content" style="width: 80%; height: 100px;"><?php echo apply_filters('format_to_edit',$uamOptions['post_content']); ?></textarea>
										<br />
										<?php echo TXT_POST_CONTENT_DESC; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_DISPLAY_POST_COMMENT; ?>
									</th>
									<td>
										<label for="uam_hide_post_comment_yes">
											<input type="radio" name="uam_hide_post_comment" value="true" <?php if ($uamOptions['hide_post_comment'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_post_comment_no">
											<input type="radio"name="uam_hide_post_comment" value="false" <?php if ($uamOptions['hide_post_comment'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_DISPLAY_POST_COMMENT_DESC; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_POST_COMMENT_CONTENT; ?>
									</th>
									<td>
										<input name="uam_post_comment_content" value="<?php echo $uamOptions['post_comment_content']; ?>" />
										<br />
										<?php echo TXT_POST_COMMENT_CONTENT_DESC; ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_ALLOW_COMMENTS_LOCKED; ?>
									</th>
									<td>
										<label for="uam_allow_comments_locked_yes">
											<input type="radio" name="uam_allow_comments_locked" value="true" <?php if ($uamOptions['allow_comments_locked'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_allow_comments_locked_no">
											<input type="radio"name="uam_allow_comments_locked" value="false" <?php if ($uamOptions['allow_comments_locked'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_ALLOW_COMMENTS_LOCKED_DESC; ?>
									</td>
								</tr>
							</tbody>
						</table>
						<h3><?php echo TXT_PAGE_SETTING; ?></h3>
						<p><?php echo TXT_PAGE_SETTING_DESC; ?></p>
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<?php echo TXT_HIDE_PAGE; ?>
									</th>
									<td>
										<label for="uam_hide_page_yes">
											<input type="radio" id="uam_hide_page_yes" class="uam_hide_page" name="uam_hide_page" value="true" <?php if ($uamOptions['hide_page'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_page_no">
											<input type="radio" id="uam_hide_page_no" class="uam_hide_page" name="uam_hide_page" value="false" <?php if ($uamOptions['hide_page'] == "false") { echo 'checked="checked"'; }?>/>
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_HIDE_PAGE_DESC; ?>
									</td>
								</tr>
							</tbody>
						</table>
						<table class="form-table" id="uam_page_settings">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<?php echo TXT_DISPLAY_PAGE_TITLE; ?>
									</th>
									<td>
										<label for="uam_hide_page_title_yes">
											<input type="radio" id="uam_hide_page_title_yes" name="uam_hide_page_title" value="true" <?php if ($uamOptions['hide_page_title'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_page_title_no">
											<input type="radio" id="uam_hide_page_title_no" name="uam_hide_page_title" value="false" <?php if ($uamOptions['hide_page_title'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_DISPLAY_PAGE_TITLE_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_PAGE_TITLE; ?>
									</th>
									<td>
										<input name="uam_page_title" value="<?php echo $uamOptions['page_title']; ?>" />
										<br />
										<?php echo TXT_PAGE_TITLE_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_PAGE_CONTENT; ?>
									</th>
									<td>
										<textarea name="uam_page_content" style="width: 80%; height: 100px;"><?php echo apply_filters('format_to_edit',$uamOptions['page_content']); ?></textarea>
										<br />
										<?php echo TXT_PAGE_CONTENT_DESC; ?>									
									</td>
								</tr>
							</tbody>
						</table>
						<h3><?php echo TXT_FILE_SETTING; ?></h3>
						<p><?php echo TXT_FILE_SETTING_DESC; ?></p>
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<?php echo TXT_LOCK_FILE; ?>
									</th>
									<td>
										<label for="uam_lock_file_yes">
											<input type="radio" id="uam_lock_file_yes" class="uam_lock_file" name="uam_lock_file" value="true" <?php if ($uamOptions['lock_file'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_lock_file_no">
											<input type="radio" id="uam_lock_file_no" class="uam_lock_file" name="uam_lock_file" value="false" <?php if ($uamOptions['lock_file'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_LOCK_FILE_DESC; ?>
									</td>
								</tr>
							</tbody>
						</table>
						<table class="form-table" id="uam_file_settings">
							<tbody>
								<tr>
									<th>
										<?php echo TXT_DOWNLOAD_FILE_TYPE; ?>
									</th>
									<td>
										<label for="uam_lock_file_types_all">
											<input type="radio" id="uam_lock_file_types_all" name="uam_lock_file_types" value="all" <?php if ($uamOptions['lock_file_types'] == "all") { echo 'checked="checked"'; }?> />
											<?php echo TXT_ALL; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_lock_file_types_selected">
											<input type="radio" id="uam_lock_file_types_selected" name="uam_lock_file_types" value="selected" <?php if ($uamOptions['lock_file_types'] == "selected") { echo 'checked="checked"'; }?> />
											<?php echo TXT_SELECTED_FILE_TYPES; ?>
										</label><input name="uam_locked_file_types" value="<?php echo $uamOptions['locked_file_types']; ?>" />
										<label for="uam_lock_file_types_not_selected">
											<input type="radio" id="uam_lock_file_types_not_selected" name="uam_lock_file_types" value="not_selected" <?php if ($uamOptions['lock_file_types'] == "not_selected") { echo 'checked="checked"'; }?> />
											<?php echo TXT_NOT_SELECTED_FILE_TYPES; ?>
										</label><input name="uam_not_locked_file_types" value="<?php echo $uamOptions['not_locked_file_types']; ?>" />
										<br />
										<?php echo TXT_DOWNLOAD_FILE_TYPE_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_FILE_PASS_TYPE; ?>
									</th>
									<td>
										<label for="uam_file_pass_type_admin">
											<input type="radio" id="uam_file_pass_type_admin" name="uam_file_pass_type" value="admin" <?php if ($uamOptions['file_pass_type'] == "admin") { echo 'checked="checked"'; }?> />
											<?php echo TXT_CURRENT_LOGGEDIN_ADMIN_PASS; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_file_pass_type_random">
											<input type="radio" id="uam_file_pass_type_random" name="uam_file_pass_type" value="random" <?php if ($uamOptions['file_pass_type'] == "random") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_RANDOM_PASS; ?>
										</label>
										<br />
										<?php echo TXT_FILE_PASS_TYPE_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_DOWNLOAD_TYPE; ?>
									</th>
									<td>
										<label for="uam_download_type_normal">
											<input type="radio" id="uam_download_type_normal" name="uam_download_type" value="normal" <?php if ($uamOptions['download_type'] == "normal") { echo 'checked="checked"'; }?> />
											<?php echo TXT_NORMAL; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_download_type_fopen">
											<input type="radio" id="uam_download_type_fopen" name="uam_download_type" value="fopen" <?php if ($uamOptions['download_type'] == "fopen") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_FOPEN; ?>
										</label>
										<br />
										<?php echo TXT_DOWNLOAD_TYPE_DESC; ?>
									</td>
								</tr>
							</tbody>
						</table>
						<h3><?php echo TXT_OTHER_SETTING; ?></h3>
						<p><?php echo TXT_OTHER_SETTING_DESC; ?></p>
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<?php echo TXT_HIDE_EMPTY_CATEGORIES; ?>
									</th>
									<td>
										<label for="uam_hide_empty_categories_yes">
											<input type="radio" id="uam_hide_empty_categories_yes" name="uam_hide_empty_categories" value="true" <?php if ($uamOptions['hide_empty_categories'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_empty_categories_no">
											<input type="radio" id="uam_hide_empty_categories_no" name="uam_hide_empty_categories" value="false" <?php if ($uamOptions['hide_empty_categories'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_HIDE_EMPTY_CATEGORIES_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_REDIRECT; ?>
									</th>
									<td>
										<label for="uam_redirect_no">
											<input type="radio" id="uam_redirect_no" name="uam_redirect" value="false" <?php if ($uamOptions['redirect'] == "false") { echo 'checked="checked"'; }?> />
											<?php echo TXT_NO; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_redirect_blog">
											<input type="radio" id="uam_redirect_blog" name="uam_redirect" value="blog" <?php if ($uamOptions['redirect'] == "blog") { echo 'checked="checked"'; }?> />
											<?php echo TXT_REDIRECT_TO_BOLG; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_redirect_custom_page">
											<input type="radio" id="uam_redirect_custom_p" name="uam_redirect" value="custom_page" <?php if ($uamOptions['redirect'] == "custom_page") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_REDIRECT_TO_PAGE; ?> 
										</label><select name="uam_redirect_custom_page">
										<?php 
											$pages = get_pages('sort_column=menu_order');
											
											if(isset($pages))
											{
												foreach($pages as $page)
												{
													echo '<option value="'.$page->ID;
													
													if($uamOptions['redirect_custom_page'] == $page->ID)
														echo ' selected = "selected"';

													echo '>'.$page->post_title.'</option>';
												}
											} 
										?></select>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_redirect_custom_url">
											<input type="radio" id="uam_redirect_custom_u" name="uam_redirect" value="custom_url" <?php if ($uamOptions['redirect'] == "custom_url") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_REDIRECT_TO_URL; ?> 
										</label><input name="uam_redirect_custom_url" value="<?php echo $uamOptions['redirect_custom_url']; ?>" />
										<br />
										<?php echo TXT_REDIRECT_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_LOCK_RECURSIVE; ?>
									</th>
									<td>
										<label for="uam_lock_recursive_yes">
											<input type="radio" id="uam_lock_recursive_yes" name="uam_lock_recursive" value="true" <?php if ($uamOptions['lock_recursive'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_lock_recursive_no">
											<input type="radio" id="uam_lock_recursive_no" name="uam_lock_recursive" value="false" <?php if ($uamOptions['lock_recursive'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_LOCK_RECURSIVE_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_BLOG_ADMIN_HINT; ?>
									</th>
									<td>
										<label for="uam_blog_admin_hint_yes">
											<input type="radio" id="uam_blog_admin_hint_yes" name="uam_blog_admin_hint" value="true" <?php if ($uamOptions['blog_admin_hint'] == "true") { echo 'checked="checked"'; }?> />
											<?php echo TXT_YES; ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_blog_admin_hint_no">
											<input type="radio" id="uam_blog_admin_hint_no" name="uam_blog_admin_hint" value="false" <?php if ($uamOptions['blog_admin_hint'] == "false") { echo 'checked="checked"'; }?>/> 
											<?php echo TXT_NO; ?>
										</label>
										<br />
										<?php echo TXT_BLOG_ADMIN_HINT_DESC; ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php echo TXT_BLOG_ADMIN_HINT_TEXT; ?>
									</th>
									<td>
										<input name="uam_blog_admin_hint_text" value="<?php echo $uamOptions['blog_admin_hint_text']; ?>" />
										<br />
										<?php echo TXT_BLOG_ADMIN_HINT_TEXT_DESC; ?>
									</td>
								</tr>
								<?php 
								global $wp_version;
								
								if($wp_version < 2.8)
								{
									?>
									<tr>
										<th>
											<?php echo TXT_CORE_MOD; ?>
										</th>
										<td>
											<label for="uam_core_mod_yes">
												<input type="radio" id="uam_core_mod_yes" name="uam_core_mod" value="true" <?php if ($uamOptions['core_mod'] == "true") { echo 'checked="checked"'; }?> />
												<?php echo TXT_YES; ?>
											</label>&nbsp;&nbsp;&nbsp;&nbsp;
											<label for="uam_core_mod_no">
												<input type="radio" id="uam_core_mod_no" name="uam_core_mod" value="false" <?php if ($uamOptions['core_mod'] == "false") { echo 'checked="checked"'; }?>/> 
												<?php echo TXT_NO; ?>
											</label>
											<br />
											<?php echo TXT_CORE_MOD_DESC; ?>
										</td>
									</tr>
									<?php 
								}
								?>
							</tbody>
						</table>
						<div class="submit">
							<input type="submit" name="update_uam_settings" value="<?php echo TXT_UPDATE_SETTING; ?>" />
						</div>
					</form>
				</div>
				<?php
			}
			elseif($action == 'edit_group' && isset($_GET['id']))
			{
				$group_id = $_GET['id'];
				$accessgroup = $wpdb->get_row("	SELECT *
												FROM ".DB_ACCESSGROUP."
												WHERE ID = ".$group_id."
												LIMIT 1", ARRAY_A);
				?>
				<div class="wrap">
					<?php $this->get_print_edit_group($group_id); ?>
				</div>
				<?php 
			}
			elseif($cur_admin_page == 'uam_usergroup')
			{
				$accessgroups = $wpdb->get_results("SELECT *
													FROM ".DB_ACCESSGROUP."
													ORDER BY ID", ARRAY_A);
				?>
				<div class=wrap>
					<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
						<input type="hidden" value="delgroup" name="action"/>
						<h2><?php echo TXT_MANAGE_GROUP; ?></h2>
						<div class="tablenav">
							<div class="alignleft">
								<input type="submit" class="button-secondary delete" name="deleteit" value="<?php echo TXT_DELETE; ?>"/>
								<input type="hidden" id="TXT_COLLAPS_ALL" name="deleteit" value="<?php echo TXT_COLLAPS_ALL; ?>"/>
								<input type="hidden" id="TXT_EXPAND_ALL" name="deleteit" value="<?php echo TXT_EXPAND_ALL; ?>"/>
							</div>
							<br class="clear"/>
						</div>
						<br class="clear"/>
						<table class="widefat">
							<thead>
								<tr class="thead">
									<th scope="col"></th>
									<th scope="col"><?php echo TXT_NAME; ?></th>
									<th scope="col"><?php echo TXT_DESCRIPTION; ?></th>
									<th scope="col"><?php echo TXT_READ_ACCESS; ?></th>
									<th scope="col"><?php echo TXT_WRITE_ACCESS; ?></th>
									<th scope="col"><?php echo TXT_IP_RANGE; ?></th>
									<th scope="col"><?php echo TXT_POSTS; ?></th>
									<th scope="col"><?php echo TXT_PAGES; ?></th>
									<th scope="col"><?php echo TXT_FILES; ?></th>
									<th scope="col"><?php echo TXT_CATEGORY; ?></th>
									<th scope="col"><?php echo TXT_USERS; ?></th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if(isset($accessgroups))
								{
									foreach($accessgroups as $accessgroup)
									{
										$group_info = $this->get_usergroup_info($accessgroup['ID']);
									?>
										<tr class="alternate" id="group-<?php echo $accessgroup['ID']; ?>">
		 									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $accessgroup['ID']; ?>" name="delete[]"/></th>
											<td><strong><a href="?page=<?php echo $cur_admin_page; ?>&action=edit_group&id=<?php echo $accessgroup['ID']; ?>"><?php echo $accessgroup['groupname']; ?></a></strong></td>
											<td><?php echo $accessgroup['groupdesc']; ?></td>
											<td><?php if($accessgroup['read_access'] == "all"){ echo TXT_ALL;}elseif($accessgroup['read_access'] == "group"){ echo TXT_ONLY_GROUP_USERS;} ?></td>
											<td><?php if($accessgroup['write_access'] == "all"){ echo TXT_ALL;}elseif($accessgroup['write_access'] == "group"){ echo TXT_ONLY_GROUP_USERS;} ?></td>
											<td><?php if(isset($accessgroup['ip_range'])){ echo $accessgroup['ip_range']; }else{ echo TXT_NONE; }?></td>
											<td>
												<?php
												if(isset($group_info->posts))
												{
													$expandcontent = null;
													foreach($group_info->posts as $post)
													{
															$expandcontent .= "<li>".$post->post_title."</li>";
													}
													echo "<a class='uam_info_link'>".count($group_info->posts)." ".TXT_POSTS."</a>";;
													echo "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul>";
												}
												else
												{
													echo TXT_NONE;
												}
												?>
											</td>
											<td>
												<?php
												if(isset($group_info->pages))
												{
													$expandcontent = null;
													foreach($group_info->pages as $page)
													{
														$expandcontent .= "<li>".$page->post_title."</li>";
													}
													echo "<a class='uam_info_link'>".count($group_info->pages)." ".TXT_PAGES."</a>";
													echo "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul>";
												}
												else
												{
													echo TXT_NONE;
												}
												?>
											</td>
											<td>
												<?php
												if(isset($group_info->files))
												{
													$expandcontent = null;
													foreach($group_info->files as $file)
													{
														$expandcontent .= "<li>".$file->post_title."</li>";
													}
													echo "<a class='uam_info_link'>".count($group_info->files)." ".TXT_FILES."</a>";
													echo "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul>";
												}
												else
												{
													echo TXT_NONE;
												}
												?>
											</td>
											<td>
												<?php
												if(isset($group_info->categories))
												{
													$expandcontent = null;
													foreach($group_info->categories as $categorie)
													{
														$expandcontent .= "<li>".$categorie->cat_name."</li>";
													}
													echo "<a class='uam_info_link'>".count($group_info->categories)." ".TXT_CATEGORY."</a>";
													echo "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul>";
												}
												else
												{
													echo TXT_NONE;
												}
												?>
											</td>
											<td>
												<?php
												if(isset($group_info->users))
												{
													$expandcontent = null;
													foreach($group_info->users as $user)
													{
														$expandcontent .= "<li>".$user->nickname."</li>";
													}
													echo "<a class='uam_info_link'>".count($group_info->users)." ".TXT_USERS."</a>";
													echo "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul>";
												}
												else
												{
													echo TXT_NONE;
												}
												?>
											</td>
											<td>
												<a class="uam_info_link_all" href="#"><?php echo TXT_EXPAND_ALL; ?></a>
											</td>
		   								</tr>
									<?php
									}
								}
								?>
							</tbody>
						</table>
					</form>
				</div>
				<div class="wrap">
					<h2><?php echo TXT_ADD_GROUP; ?></h2>
					<div id="ajax-response"/>
						<?php $this->get_print_edit_group(); ?>
					</div>
				<?php
			}
		}//End function printAdminPage()
		
		function get_print_edit_group($group_id = null)
		{
			global $wpdb;
			$uamOptions = $this->getAdminOptions();
			
			if(isset($group_id))
				$group_info = $this->get_usergroup_info($group_id);
			?>
				<form method="post" action="<?php echo reset(explode("?", $_SERVER["REQUEST_URI"]))."?page=".$_GET['page']; ?>">
					<input type="hidden" id="TXT_COLLAPS" name="deleteit" value="<?php echo TXT_COLLAPS; ?>"/>
					<input type="hidden" id="TXT_EXPAND" name="deleteit" value="<?php echo TXT_EXPAND; ?>"/>
					<?php 
						if(isset($group_id))
						{
					?>
						<input type="hidden" value="update_group" name="action"/>
						<input type="hidden" value="<?php echo $group_id; ?>" name="access_group_id"/>
					<?php
						} 
						else
						{
					?>
						<input type="hidden" value="addgroup" name="action"/>
					<?php 
						}
					?>
					<input type="hidden" value="<?php echo $uamOptions['lock_recursive']; ?>" name="uam_lock_recursive" id="uam_set_lock_recursive"/>
					<table class="form-table">
						<tbody>
							<tr class="form-field form-required">
								<th valign="top" scope="row"><?php echo TXT_GROUP_NAME; ?></th>
								<td><input type="text" size="40" value="<?php if(isset($group_id)){echo $group_info->group["groupname"]; }?>" id="access_group_name" name="access_group_name"/><br/>
				            	<?php echo TXT_GROUP_NAME_DESC; ?></td>
							</tr>
							<tr class="form-field form-required">
								<th valign="top" scope="row"><?php echo TXT_GROUP_DESC; ?></th>
								<td><input type="text" size="40" value="<?php if(isset($group_id)){ echo $group_info->group["groupdesc"]; }?>" id="access_group_description" name="access_group_description"/><br/>
				            	<?php echo TXT_GROUP_DESC_DESC; ?></td>
				            </tr>
				            <tr class="form-field form-required">
								<th valign="top" scope="row"><?php echo TXT_GROUP_IP_RANGE; ?></th>
								<td><input type="text" size="40" value="<?php if(isset($group_id)){ echo $group_info->group["ip_range"]; }?>" id="ip_range" name="ip_range"/><br/>
				            	<?php echo TXT_GROUP_IP_RANGE_DESC; ?></td>
				            </tr>
				            <tr class="form-field form-required">
								<th valign="top" scope="row"><?php echo TXT_GROUP_READ_ACCESS; ?></th>
								<td><select name="read_access">
									<option value="group" <?php if(isset($group_id)){ if($group_info->group["read_access"] == "group"){ echo 'selected="selected"'; }} ?> ><?php echo TXT_ONLY_GROUP_USERS ?></option>
									<option value="all" <?php if(isset($group_id)){ if($group_info->group["read_access"] == "all"){ echo 'selected="selected"'; }} ?> ><?php echo TXT_ALL ?></option>
								</select><br />
				            	<?php echo TXT_GROUP_READ_ACCESS_DESC; ?></td>
				            </tr>
				              <tr class="form-field form-required">
								<th valign="top" scope="row"><?php echo TXT_GROUP_WRITE_ACCESS; ?></th>
								<td><select name="write_access">
									<option value="group" <?php if(isset($group_id)){ if($group_info->group["write_access"] == "group"){ echo 'selected="selected"'; }} ?> ><?php echo TXT_ONLY_GROUP_USERS ?></option>
									<option value="all" <?php if(isset($group_id)){ if($group_info->group["write_access"] == "all"){ echo 'selected="selected"'; }} ?> ><?php echo TXT_ALL ?></option>
								</select><br />
				            	<?php echo TXT_GROUP_WRITE_ACCESS_DESC; ?></td>
				            </tr>
				            <tr>
				            	<th valign="top" scope="row"><?php echo TXT_GROUP_ROLE; ?></th>
								<td>
									<ul class='uam_role'>
					            	<?php
					               	global $wp_roles;
   		
   									foreach($wp_roles->role_names as $role => $name)
									{
										if($role != "administrator")
										{
											?>
											<li class="selectit">
												<input id="role-<?php echo $role; ?>" type="checkbox" <?php if(isset($group_info->posts[$role])){ echo 'checked="checked"'; } ?> value="<?php echo $role; ?>" name="roles[]"/>
												<label for="role-<?php echo $role; ?>"><?php echo $role ?></label>
											</li>
											<?php
										}
									}
   								 	?>
   								 	</ul>
   							 	</td>
   							</tr>
   							<tr>
   								<?php 
   									$args = array('numberposts' => -1);  
   									$posts = get_posts($args); 
   								?>
								<th valign="top" scope="row"><?php echo TXT_POSTS; if(count($posts) > 0){ echo " <label>(<a class='selectit uam_group_stuff_link'>".TXT_EXPAND."</a>)</label>"; }?></th>
								<td>
									<?php
										if(isset($group_info->posts))
											$posts_in_group = count($group_info->posts);
										else
											$posts_in_group = 0;
											
										echo "<strong>".sprintf(TXT_CONTAIN_POSTS, "<span class='uam_element_count'>".$posts_in_group."</span>", count($posts))."</strong>";
										
										if(isset($posts))
										{
											echo "<ul class='uam_group_stuff'>";
											foreach($posts as $post)
											{
												$cur_categories = get_the_category($post->ID);	
												?>
												<li class="selectit <?php foreach($cur_categories as $cur_cat){ echo "cat-".$cur_cat->term_id." "; }?>">
													<input id="post-<?php echo $post->ID; ?>" type="checkbox" value="<?php echo $post->ID;?>" <?php if(isset($group_info->posts[$post->ID])){ echo 'checked="checked"'; } ?> name="post[]"/>
													<label for="post-<?php echo $post->ID; ?>"><strong><?php echo $post->post_title; ?></strong> - <?php echo $post->post_date; ?></label>
													<?php
													
												?>
												</li>
											<?php
											}
											echo "</ul>";
										}
									?>
								</td>
				           	</tr>
   							<tr>
   								<?php
   									$pages = get_pages('sort_column=menu_order');
   								?>
								<th valign="top" scope="row"><?php echo TXT_PAGES; if(count($pages) > 0){ echo " <label>(<a class='selectit uam_group_stuff_link'>".TXT_EXPAND."</a>)</label>"; } ?></th>
								<td>
									<?php
										if(isset($group_info->pages))
											$pages_in_group = count($group_info->pages);
										else
											$pages_in_group = 0;
											
										echo "<strong>".sprintf(TXT_CONTAIN_PAGES, "<span class='uam_element_count'>".$pages_in_group."</span>", count($pages))."</strong>";
										
										if(isset($pages))
										{
											echo "<ul class='uam_group_stuff'>";
											$deepness = 0;
											
											foreach($pages as $page)
											{
												$old_deepness = $deepness;
												$deepness = 0;
												
												if($page->post_parent != 0)
												{
													$cur_page = $page;
													$cur_id = $page->ID;
													while($cur_page->post_parent != 0)
													{
														$deepness++;
														$cur_parent_id = $cur_page->post_parent;
														$cur_page = & get_post($cur_parent_id);
													}
												}
													
												if($old_deepness > $deepness)
												{
													$count = abs($old_deepness - $deepness);
													for($i = 0; $i < $count; $i++)
														echo "</ul>";
												}
												elseif($old_deepness < $deepness)
												{
													$count = abs($old_deepness - $deepness);
													for($i = 0; $i < $count; $i++)
														echo "<ul class='uam_group_stuff_child'>";
												}
												
												?>
												<li class="selectit">
													<input id="post-<?php echo $page->ID; ?>" type="checkbox" value="<?php echo $page->ID;?>" <?php if(isset($group_info->pages[$page->ID])){ echo 'checked="checked"'; } ?>  name="page[]"/>
													<label for="post-<?php echo $page->ID; ?>"><strong><?php echo $page->post_title; ?></strong> - <?php echo $page->post_date; ?></label>
												</li>
											<?php
											}
											echo "</ul>";
										}
									?>
								</td>
				            </tr>
				            <tr>
   								<?php 
   									$args = array(	'numberposts' => -1,
   													'post_type' => 'attachment');  
   									$files = get_posts($args); 
   								?>
								<th valign="top" scope="row"><?php echo TXT_FILES; if(count($files) > 0){ echo " <label>(<a class='selectit uam_group_stuff_link'>".TXT_EXPAND."</a>)</label>"; }?></th>
								<td>
									<?php
										if(isset($group_info->files))
											$files_in_group = count($group_info->files);
										else
											$files_in_group = 0;
											
										echo "<strong>".sprintf(TXT_CONTAIN_FILES, "<span class='uam_element_count'>".$files_in_group."</span>", count($files))."</strong>";
										
										if(isset($files))
										{
											echo "<ul class='uam_group_stuff'>";
											foreach($files as $file)
											{
												$cur_categories = get_the_category($file->ID);	
												?>
												<li class="selectit <?php foreach($cur_categories as $cur_cat){ echo "cat-".$cur_cat->term_id." "; } echo "parent_post-".$file->post_parent." ";?>">
													<input id="post-<?php echo $file->ID; ?>" type="checkbox" value="<?php echo $file->ID;?>" <?php if(isset($group_info->files[$file->ID])){ echo 'checked="checked"'; } ?> name="file[]"/>
													<label for="post-<?php echo $file->ID; ?>"><strong><?php echo $file->post_title; ?></strong> - <?php echo $file->post_date; ?></label>
												</li>
											<?php
											}
											echo "</ul>";
										}
									?>
								</td>
				           	</tr>
				            <tr>
				            	<?php 
				            		$categories = get_categories();
				            	?>
								<th valign="top" scope="row"><?php echo TXT_CATEGORY;  if(count($categories) > 0){ echo " <label>(<a class='selectit uam_group_stuff_link'>".TXT_EXPAND."</a>)</label>"; } ?></th>
								<td>
									<?php
										if(isset($group_info->categories))
											$categories_in_group = count($group_info->categories);
										else
											$categories_in_group = 0;
											
										echo "<strong>".sprintf(TXT_CONTAIN_CATEGORIES, "<span class='uam_element_count'>".$categories_in_group."</span>", count($categories))."</strong>";
											
										function print_elements($group_id, $group_info = null, $category = 0, $deepness = 0)
										{
											global $wpdb;
								
											$args = array('child_of' => $category);
											$categories = get_categories($args);
											
											if(isset($categories))
											{
												$first_round = true;
												foreach($categories as $cat)
												{
													
													if($cat->parent == $category)
													{
														if($first_round == true)
														{
															if($category == 0)
																echo "<ul class='uam_group_stuff uam_category'>";
															else
																echo "<ul class='uam_group_stuff_child uam_category'>";
																
															$first_round = false;
														}	
														?>
														<li class="selectit">
															<input id="category-<?php echo $cat->term_id; ?>" type="checkbox" name="category[]" value="<?php echo $cat->term_id;?>" <?php if(isset($group_info[$cat->term_id])){ echo 'checked="checked"'; } ?>  />
															<label for="category-<?php echo $cat->term_id; ?>"><strong><?php echo $cat->cat_name; ?></strong></label>
														</li>
														<?php
														print_elements($group_id, $group_info, $cat->term_id, $deepness++);
													}
												}
												if($first_round == false)
												{
													echo "</ul>";
												}
											}
										}
										
										$callFkt = "print_elements";
										
										if(isset($group_info->categories))
											echo $callFkt($group_id, $group_info->categories);
										else
											echo $callFkt($group_id);
									?>
								</td>
				            </tr>
				            <tr>
				            	<?php 
				            		$users = $wpdb->get_results("	SELECT ID
																	FROM $wpdb->users
																	ORDER BY ID", ARRAY_A);
				            	?>
								<th valign="top" scope="row"><?php echo TXT_USERS;  if(count($users) > 0){ echo " <label>(<a class='selectit uam_group_stuff_link'>".TXT_EXPAND."</a>)</label>"; } ?></th>
								<td>
									<?php
										if(isset($group_info))
											$users_in_group = count($group_info->users);
										else
											$users_in_group = 0;
										
										echo "<strong>".sprintf(TXT_CONTAIN_USERS, "<span class='uam_element_count'>".$users_in_group."</span>", count($users))."</strong>";
										
										if(isset($users))
										{
											echo "<ul class='uam_group_stuff'>";
											foreach($users as $user)
											{
												$cur_user = get_userdata($user['ID']);
												$user_capabilities = array_keys($cur_user->{$wpdb->prefix."capabilities"})
												?>
													<li class="selectit <?php foreach($user_capabilities as $user_capability){ echo "usercap_".$user_capability." "; } ?>">
														<?php 
															if(empty($cur_user->{$wpdb->prefix."capabilities"}['administrator']))
															{
																	?>
																	<input id="user-<?php echo $cur_user->ID; ?>" type="checkbox" value="<?php echo $cur_user->ID; ?>" <?php if(isset($group_info->users[$cur_user->ID])){ echo 'checked="checked"'; } ?> name="user[]"/>
																	<label for="user-<?php echo $cur_user->ID; ?>"><strong><?php echo $cur_user->nickname; ?></strong> - <?php echo $cur_user->user_firstname." ".$cur_user->user_lastname; ?>
																	<?php
															}
															else
															{
																?>
																	<input id="user-<?php echo $cur_user->ID; ?>" type="checkbox" value="<?php echo $cur_user->ID; ?>" checked="checked" disabled="disabled" name="user[]"/>
																	<label for="user-<?php echo $cur_user->ID; ?>"><strong><?php echo $cur_user->nickname; ?></strong> - <?php echo $cur_user->user_firstname." ".$cur_user->user_lastname; ?>
																<?php
																echo "(".TXT_IS_ADMIN.")";
															}
														?>
														
					   								</li>
												<?php
											}
											echo "</ul>";
										}
									?>
								</td>
				            </tr>
						</tbody>
					</table> 
					<p class="submit"><input type="submit" value="<?php if(isset($group_id)) { echo TXT_UPDATE_GROUP; }else{ echo TXT_ADD_GROUP;} ?>" name="submit" class="button"/></p>
				</form>
			<?php
		}
		
		function get_usergroups_for_post($ID)
		{
			global $wpdb;
			
			$access = $this->get_access($ID);
			
			$post_usergroups = array();
			
			if(isset($access->restricted_by_posts) || isset($access->restricted_by_categories))
			{
				if(isset($access->restricted_by_posts))
				{
					foreach($access->restricted_by_posts as $cur_id)
					{
						$usergroups = $wpdb->get_results("	SELECT ag.ID, ag.groupname
															FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_POST." agtp
															WHERE agtp.post_id = ".$cur_id."
																AND ag.ID = agtp.group_id
															GROUP BY ag.groupname", ARRAY_A);
			   		
						if(isset($usergroups))
						{
							foreach($usergroups as $usergroup)
							{
								$cur_usergroup = null;
								
								if(isset($post_usergroups[$usergroup['ID']]))
									$cur_usergroup = $post_usergroups[$usergroup['ID']];
								
								$cur_usergroup->ID = $usergroup['ID'];
								$cur_usergroup->name = $usergroup['groupname'];
			
								if($cur_id != $ID)
								{
									if(isset($cur_usergroup->posts))
										$posts = $cur_usergroup->posts;
									
									$lock_post = & get_post($cur_id);
									$posts[] = $lock_post->ID;
									$cur_usergroup->posts = $posts;
								}
								else
								{
									$cur_usergroup->itself = true;
								}
								
								$post_usergroups[$usergroup['ID']] = $cur_usergroup;
							}
						}	
					}
				}
				
				if(isset($access->restricted_by_categories))
				{
					foreach($access->restricted_by_categories as $cur_id)
					{
						$usergroups = $wpdb->get_results("	SELECT ag.ID, ag.groupname
															FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_CATEGORY." agtc
															WHERE agtc.category_id = ".$cur_id."
																AND ag.ID = agtc.group_id
															GROUP BY ag.groupname", ARRAY_A);
						
						if(isset($usergroups))
						{
							foreach($usergroups as $usergroup)
							{
								$cur_usergroup = null;
								
								if(isset($post_usergroups[$usergroup['ID']]))
									$cur_usergroup = $post_usergroups[$usergroup['ID']];
								
								$cur_usergroup->ID = $usergroup['ID'];
								$cur_usergroup->name = $usergroup['groupname'];

								if(isset($cur_usergroup->categorie))
									$categories = $cur_usergroup->categories;
									
								$lock_cat = & get_category($cur_id);
								$categories[] = $lock_cat->term_id;
								$cur_usergroup->categories = $categories;

								$post_usergroups[$usergroup['ID']] = $cur_usergroup;
							}
						}

						
					}
				}
			}
			
			return $post_usergroups;
		}
		
		function get_usergroup_info($groupid)
		{
			global $wpdb;
			
			$uamOptions = $this->getAdminOptions();
		
			$cur_group = $wpdb->get_results("	SELECT *
												FROM ".DB_ACCESSGROUP."
												WHERE ID = ".$groupid, ARRAY_A);
			
			$info->group = $cur_group[0];
			
			$db_users = $wpdb->get_results("	SELECT *
												FROM ".DB_ACCESSGROUP_TO_USER."
												WHERE group_id = ".$groupid."
												ORDER BY user_id", ARRAY_A);
			
			$db_posts = $wpdb->get_results("	SELECT *
												FROM ".DB_ACCESSGROUP_TO_POST."
												WHERE group_id = ".$groupid."
												ORDER BY post_id", ARRAY_A);
			
			$db_categories = $wpdb->get_results("	SELECT *
													FROM ".DB_ACCESSGROUP_TO_CATEGORY."
													WHERE group_id = ".$groupid."
													ORDER BY category_id", ARRAY_A);
			
			$db_roles = $wpdb->get_results("	SELECT *
												FROM ".DB_ACCESSGROUP_TO_ROLE."
												WHERE group_id = ".$groupid, ARRAY_A);
			
			$wp_users = $wpdb->get_results("	SELECT ID, user_nicename 
												FROM $wpdb->users 
												ORDER BY user_nicename", ARRAY_A);
			

			if(isset($db_posts))
			{
				foreach($db_posts as $db_post)
				{
					$cur_id = $db_post['post_id'];
					$cur_post = & get_post($cur_id);
					
					if(isset($cur_post->post_type))
					{
						if($cur_post->post_type == 'post')
						{
							$info->posts[$cur_id] = $cur_post;
						}
						elseif($cur_post->post_type == 'page')
						{
							$info->pages[$cur_id] = $cur_post;
						}
						elseif($cur_post->post_type == 'attachment')
						{
							$info->files[$cur_id] = $cur_post;
						}
					}
				}
			}

			if(isset($db_categories))
			{
				foreach($db_categories as $db_categorie)
				{
					$cur_category = get_category($db_categorie['category_id']);
					
					$info->categories[$db_categorie['category_id']] = $cur_category;
					
					if($uamOptions['lock_recursive'] == 'true')
					{	
						$cur_categories = get_categories('child_of='.$db_categorie['category_id']);
						if(isset($cur_categories))
						{
							foreach($cur_categories as $cur_category)
							{
								$cur_category->recursive_lock_by_category[$db_categorie['category_id']] = $db_categorie['category_id'];
								$info->categories[$cur_category->term_id] = $cur_category;
							}
						}
					}
				}
			}

			if(isset($db_users))
			{
				$expandcontent = null;
				foreach($db_users as $db_user)
				{
					$info->users[$db_user['user_id']] = get_userdata($db_user['user_id']);
				}
			}

			if(isset($db_roles))
			{
				foreach($db_roles as $db_role)
				{
					$info->roles[$db_role['role_name']] = $db_role;
				}
			}
			
			if(isset($wp_users))
			{
				foreach($wp_users as $wp_user)
				{
					$cur_userdata = get_userdata($wp_user['ID']);
					
					if(isset($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']))
					{
						$info->users[$wp_user['ID']] = $cur_userdata;
					}
					elseif(isset($db_roles) && empty($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']))
					{
						foreach($db_roles as $db_role)
						{
							if(isset($cur_userdata->{$wpdb->prefix."capabilities"}[$db_role['role_name']]))
							{
								$info->users[$wp_user['ID']] = $cur_userdata;
								break;
							}
						}
					}
				}
			}
			

			if(isset($info->categories))
			{
				foreach($info->categories as $group_category)
				{
					$cur_posts = get_posts('category='.$group_category->term_id);
					if(isset($cur_posts))
					{
						foreach($cur_posts as $cur_post)
						{
							if($uamOptions['lock_recursive'] == 'false')
							{
								$cur_categories = get_the_category($cur_post->ID);	
								foreach($cur_categories as $cur_cat)
								{
									if($cur_cat->term_id == $group_category->term_id)
									{
										$add = true;
										break;
									}
								}
							}
							
							if(isset($add) || $uamOptions['lock_recursive'] == 'true')
							{
								if(isset($info->posts[$cur_post->ID]))
									$cur_post = $info->posts[$cur_post->ID];
								
								$cur_post->recursive_lock_by_category[$group_category->term_id] = $group_category->term_id;
								$info->posts[$cur_post->ID] = $cur_post;
							}
						}
					}
					
					$cur_files = get_posts('category='.$group_category->term_id.'&post_type=attachment');
					if(isset($cur_files))
					{
						foreach($cur_files as $cur_file)
						{
							if($uamOptions['lock_recursive'] == 'false')
							{
								$cur_categories = get_the_category($cur_post->ID);	
								foreach($cur_categories as $cur_cat)
								{
									if($cur_cat->term_id == $group_category->term_id)
									{
										$add = true;
										break;
									}
								}
							}
							
							if(isset($add) || $uamOptions['lock_recursive'] == 'true')
							{
								if(isset($info->files[$cur_file->ID]))
									$cur_file = $info->files[$cur_file->ID];
								
								$cur_file->recursive_lock_by_category[$group_category->term_id] = $group_category->term_id;
								$info->files[$cur_file->ID] = $cur_file;
							}
						}
					}
				}	
			}
			
			if(isset($info->posts))
			{
				foreach($info->posts as $group_post)
				{
					if($uamOptions['lock_recursive'] == 'true')
					{
						$cur_posts = get_posts('post_parent='.$group_post->ID);
						if(isset($cur_posts))
						{
							foreach($cur_posts as $cur_post)
							{
								if(isset($info->posts[$cur_post->ID]))
									$cur_post = $info->posts[$cur_post->ID];
								
								$cur_post->recursive_lock_by_post[$group_post->ID] = $group_post->ID;
								$info->posts[$cur_post->ID] = $cur_post;
							}
						}
					}
					$cur_files = get_posts('post_parent='.$group_post->ID.'&post_type=attachment');
					if(isset($cur_files))
					{
						foreach($cur_files as $cur_file)
						{
							if(isset($info->files[$cur_file->ID]))
								$cur_file = $info->files[$cur_file->ID];
							
							$cur_file->recursive_lock_by_post[$group_post->ID] = $group_post->ID;
							$info->files[$cur_file->ID] = $cur_file;
						}
					}
				}
			}	
				
			if(isset($info->pages))
			{		
				foreach($info->pages as $group_page)
				{
					if($uamOptions['lock_recursive'] == 'true')
					{
						$cur_pages = get_pages('child_of='.$group_page->ID);
						if(isset($cur_pages))
						{
							foreach($cur_pages as $cur_page)
							{
								if(isset($info->posts[$cur_post->ID]))
									$cur_post = $info->posts[$cur_post->ID];
								
								$cur_post->recursive_lock_by_post[$group_page->ID] = $group_page->ID;
								$info->pages[$cur_page->ID] = $cur_page;
							}
						}
					}
				
					$cur_files = get_posts('post_parent='.$group_page->ID.'&post_type=attachment');
					if(isset($cur_files))
					{
						foreach($cur_files as $cur_file)
						{
							if(isset($info->files[$cur_file->ID]))
								$cur_file = $info->files[$cur_file->ID];
							
							$cur_file->recursive_lock_by_post[$group_post->ID] = $group_post->ID;
							$info->files[$cur_file->ID] = $cur_file;
						}
					}
				}
			}	
			
			return $info;
		}
		
		function get_usergroup_info_html($group_id, $style = null)
		{
			$link = '<a class="uam_group_info_link">('.TXT_INFO.')</a>';
							
			$group_info = $this->get_usergroup_info($group_id);

			$content = "<ul class='uam_group_info'";
			
			if($style != null)
				$content .= " style='".$style."' ";
				
			$content .= "><li class='uam_group_info_head'>".TXT_GROUP_INFO.":</li>";

			$content .= "<li>".TXT_READ_ACCESS.": ";
			if($group_info->group['read_access'] == "all")
				$content .= TXT_ALL;
			elseif($group_info->group['read_access'] == "group")
				$content .= TXT_ONLY_GROUP_USERS;
			$content .= "</li>";
				
			$content .= "<li>".TXT_WRITE_ACCESS.": ";
			if($group_info->group['write_access'] == "all")
				$content .= TXT_ALL;
			elseif($group_info->group['write_access'] == "group")
				$content .= TXT_ONLY_GROUP_USERS;
			$content .= "</li>";
			
			if(isset($group_info->posts))
			{
				$expandcontent = null;
				foreach($group_info->posts as $post)
				{
					$expandcontent .= "<li>".$post->post_title."</li>";
				}
				$content .= "<li><a class='uam_info_link'>".count($group_info->posts)." ".TXT_POSTS."</a>";
				$content .= "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul></li>";
			}
			else
			{
				$content .= "<li>".TXT_NONE." ".TXT_POSTS."</li>";
			}

			if(isset($group_info->pages))
			{
				$expandcontent = null;
				foreach($group_info->pages as $page)
				{
					$expandcontent .= "<li>".$page->post_title."</li>";
				}
				$content .= "<li><a class='uam_info_link'>".count($group_info->pages)." ".TXT_PAGES."</a>";
				$content .= "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul></li>";
			}
			else
			{
				$content .= "<li>".TXT_NONE." ".TXT_PAGES."</li>";
			}

			if(isset($group_info->categories))
			{
				$expandcontent = null;
				foreach($group_info->categories as $categorie)
				{
					$expandcontent .= "<li>".$categorie->cat_name."</li>";
				}
				$content .= "<li><a class='uam_info_link'>".count($group_info->categories)." ".TXT_CATEGORY."</a>";
				$content .= "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul></li>";
			}
			else
			{
				$content .= "<li>".TXT_NONE." ".TXT_CATEGORY."</li>";
			}
			if(isset($group_info->users))
			{
				$expandcontent = null;
				foreach($group_info->users as $user)
				{
					$expandcontent .= "<li>".$user->nickname."</li>";
				}
				$content .= "<li><a class='uam_info_link'>".count($group_info->users)." ".TXT_USERS."</a>";
				$content .= "<ul class='uam_info_content expand_deactive'>".$expandcontent."</ul></li>";
			}
			else
			{
				$content .= "<li>".TXT_NONE." ".TXT_USERS."</li>";
			}
			$content .= "</ul>";

			$result->link = $link;
			$result->content = $content;
			
			return $result;
		}
		
		function get_post_info_html($id)
		{
			$usergroups = $this->get_usergroups_for_post($id);

		    if(isset($usergroups) && $usergroups != null)
		    {
	    		$output = "<ul>";
	    		foreach($usergroups as $usergroup)
	    		{
	    			$output .= "<li><a class='uma_user_access_group'>".$usergroup->name."</a>";
	    			$output .= "<ul class='uma_user_access_group_from'>";
	    			
	    			if(isset($usergroup->itself))
	    				$output .= "<li>".TXT_ITSELF."</li>";
	    			
	    			if(isset($usergroup->posts))
	    			{
	    				foreach($usergroup->posts as $cur_id)
	    				{
	    					$cur_post = & get_post($cur_id);
	    					$output .= "<li>$cur_post->post_title [$cur_post->post_type]</li>";
	    				}
	    			}
	    			
	    			if(isset($usergroup->categories))
	    			{
	    				foreach($usergroup->categories as $cur_id)
	    				{
	    					$cur_category = & get_category($cur_id);
	    					$output .= "<li>$cur_category->name [category]</li>";
	    				}
	    			}
					
	    			$output = substr($output, 0, -2);
	    			
	    			$output .= "</ul></li>";
	    		}
	    		$output .= "</ul>";
	    	}
	    	else
			{ 
				$output = TXT_FULL_ACCESS;
			}
			return $output;
		}
		function add_post_columns_header($defaults)
		{
    		$defaults['uam_access'] = __('Access');
    		return $defaults;
		}
		
		function get_post_edit_info_html($id , $style = null)
		{
			global $wpdb;
			$accessgroups = $wpdb->get_results("SELECT *
												FROM ".DB_ACCESSGROUP."
												ORDER BY groupname", ARRAY_A);
			
			$recursive_set = $this->get_usergroups_for_post($id);

			if(isset($accessgroups))
			{
				$content = "";
				
				foreach($accessgroups as $accessgroup)
				{
					$checked = $wpdb->get_results("	SELECT *
													FROM ".DB_ACCESSGROUP_TO_POST."
													WHERE post_id = ".$id."
													AND group_id = ".$accessgroup['ID'], ARRAY_A);
					
					$set_recursive = null;
					if(isset($recursive_set[$accessgroup['ID']]))
						$set_recursive = $recursive_set[$accessgroup['ID']];

					$content .= '<p><label for="uam_accesssgroup-'.$accessgroup['ID'].'" class="selectit" style="display:inline;" >';
					$content .= '<input type="checkbox" id="uam_accesssgroup-'.$accessgroup['ID'].'"';
					if(isset($checked) || isset($set_recursive->posts) || isset($set_recursive->categories))
						$content .= 'checked="checked"';
					if(isset($set_recursive->posts) || isset($set_recursive->categories))
						$content .= 'disabled=""';
					$content .= 'value="'.$accessgroup['ID'].'" name="accessgroups[]"/>';
					$content .= $accessgroup['groupname'];					
					$content .=	"</label>";

					$group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], $style);

					$content .= $group_info_html->link;
							
					if(isset($set_recursive->posts) || isset($set_recursive->categories))
						$content .= '&nbsp;<a class="uam_group_lock_info_link">[LR]</a>';
						
					$content .= $group_info_html->content;							

					if(isset($set_recursive->posts) || isset($set_recursive->categories))
					{
						$recursive_info = '<ul class="uam_group_lock_info" ';
						if($style != null)
							$recursive_info .= " style='".$style."' ";
						$recursive_info .= '><li class="uam_group_lock_info_head">'.TXT_GROUP_LOCK_INFO.':</li>';
						
						if(isset($set_recursive->posts))
		    			{
		    				foreach($set_recursive->posts as $cur_id)
		    				{
		    					$cur_post = & get_post($cur_id);
		    					$recursive_info .= "<li>$cur_post->post_title [$cur_post->post_type]</li>";
		    				}
		    			}
		    			
		    			if(isset($set_recursive->categories))
		    			{
		    				foreach($set_recursive->categories as $cur_id)
		    				{
		    					$cur_category = & get_category($cur_id);
		    					$recursive_info .= "<li>$cur_category->name [".TXT_CATEGORY."]</li>";
		    				}
		    			}
						$recursive_info .= "</ul>";
						$content .= $recursive_info;
					}
					$content .= "</p>";
				}
			}
			else
			{
				$content = "<a href='admin.php?page=uam_usergroup'>";
				$content .= TXT_CREATE_GROUP_FIRST;
				$content .= "</a>";
			}
			
			return $content;
		}
		function add_post_column($column_name, $id)
		{			
		    if( $column_name == 'uam_access' )
		    {
		    	echo $this->get_post_info_html($id);
		    }
		}
		
		function edit_post_content($post)
		{
			echo $this->get_post_edit_info_html($post->ID, "padding:0 0 0 36px;");
		}
		
		function save_postdata($post_id)
		{
			global $wpdb;
			if(isset($_POST['accessgroups']))
				$accessgroups = $_POST['accessgroups'];
			
			$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE post_id = $post_id");
			if(isset($accessgroups))
			{
				foreach($accessgroups as $accessgroup)
				{
					$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_POST." (post_id,group_id) VALUES(".$post_id.", ".$accessgroup.")");
				}
			}
		}
		
		function save_attachmentdata($post)
		{
			global $wpdb;

			if(isset($post['ID']))
			{
				$post_id = $post['ID'];
				
				if(isset($_POST['accessgroups']))
					$accessgroups = $_POST['accessgroups'];
				
				$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE post_id = $post_id");
				if(isset($accessgroups))
				{
					foreach($accessgroups as $accessgroup)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_POST." (post_id,group_id) VALUES(".$post_id.", ".$accessgroup.")");
					}
				}
			}
			
			return $post;
		}
		
		function remove_postdata($post_id)
		{
			global $wpdb;
			$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE post_id = $post_id");
		}
		
		function add_user_columns_header($defaults)
		{
    		$defaults['uam_access'] = __('Access');
    		return $defaults;
		}
		
		function add_user_column($column_name, $id)
		{
			global $wpdb;
			
			if( $column_name == 'uam_access' )
		    {
			    $usergroups = $wpdb->get_results("	SELECT ag.groupname
													FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_USER." agtp
													WHERE agtp.user_id = ".$id."
														AND ag.ID = agtp.group_id
													GROUP BY ag.groupname", ARRAY_A);
																					
				if(isset($usergroups))
				{
					$content .= "<ul>";
					foreach($usergroups as $usergroup)
					{
						$content .= "<li>".$usergroup['groupname']."</li>";
					}
					$content .= "</ul>";
				}
				else
				{ 
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
			
			if(isset($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']))
			{
				$accessgroups = $wpdb->get_results("SELECT *
													FROM ".DB_ACCESSGROUP."
													ORDER BY groupname", ARRAY_A);
					
				?>
				<h3><?php echo TXT_GROUPS; ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="usergroups"><?php echo TXT_SET_UP_USERGROUPS; ?></label>
							</th>
							<td>
								<?php
								if(empty($cur_edit_userdata->{$wpdb->prefix."capabilities"}['administrator']))
								{
									if(isset($accessgroups))
									{
										foreach($accessgroups as $accessgroup)
										{
											$checked = $wpdb->get_results("	SELECT *
																			FROM ".DB_ACCESSGROUP_TO_USER."
																			WHERE user_id = ".$user_id."
																				AND group_id = ".$accessgroup['ID'], ARRAY_A)
									?>
											<p style="margin:6px 0;">
												<label for="uam_accesssgroup-<?php echo $accessgroup['ID'];?>" class="selectit" >
													<input type="checkbox" id="uam_accesssgroup-<?php echo $accessgroup['ID'];?>" <?php if(isset($checked)){ echo 'checked="checked"'; } ?> value="<?php echo $accessgroup['ID']; ?>" name="accessgroups[]"/>
														<?php echo $accessgroup['groupname']; ?>					
												</label>
												<?php
												$group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], "padding: 0 0 0 32px");
							
												echo $group_info_html->link;
												echo $group_info_html->content;
											echo "</p>";		
										}
									}
									else
									{
										echo "<a href='admin.php?page=uam_usergroup'>";
										echo TXT_CREATE_GROUP_FIRST;
										echo "</a>";
									}
								}
								else
								{
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
			$content .= '</td></tr><tr><th class="label"><label>'.TXT_SET_UP_USERGROUPS.'</label></th><td class="field">';
			$content .= $this->get_post_edit_info_html($post->ID, "padding:0 0 0 38px;top:-12px;");
			
			return $content;
		}
		
		function save_userdata($user_id)
		{
			global $wpdb, $current_user;
			
			$cur_userdata = get_userdata($current_user->ID);		

			if(isset($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']))
			{
				$accessgroups = $_POST['accessgroups'];
				$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE user_id = $user_id");
				if(isset($accessgroups))
				{
					foreach($accessgroups as $accessgroup)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_USER." (user_id,group_id) VALUES(".$user_id.", ".$accessgroup.")");
					}
				}
			}
		}
		
		function remove_userdata($user_id)
		{
			global $wpdb, $current_user;
			
			$cur_userdata = get_userdata($current_user->ID);		

			if(isset($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']))
				$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE user_id = $user_id");
		}
	
		function add_category_columns_header($defaults)
		{
    		$defaults['uam_access'] = __('Access');
    		return $defaults;
		}
		
		function add_category_column($column_name, $id)
		{
			global $wpdb;
			
			if( $column_name == 'uam_access' )
		    {
				$usergroups = $wpdb->get_results("	SELECT ag.groupname
													FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_CATEGORY." agtc
													WHERE agtc.category_id = ".$id."
														AND ag.ID = agtc.group_id
													GROUP BY ag.groupname", ARRAY_A);
				
				if(isset($usergroups))
				{
					$content = "<ul>";					
					foreach($usergroups as $usergroup)
					{
						$content .= "<li>".$usergroup['groupname']."</li>";
					}
					$content .= "</ul>";
				}
		    	else
				{ 
					$content  = TXT_NO_GROUP;
				}
				return $content;
		    }
		}
		
		function show_cat_edit_form($cat)
		{
			global $wpdb, $current_user;

			if(isset($cat->cat_ID))
				$cat_id = $cat->cat_ID;

			$accessgroups = $wpdb->get_results("SELECT *
												FROM ".DB_ACCESSGROUP."
												ORDER BY groupname", ARRAY_A);

			if(isset($_GET['action']))
				$action = $_GET['action'];
			else
				$action = null;
			
			if($action == 'edit')
			{
				?>
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="description"><?php echo TXT_SET_UP_USERGROUPS; ?></label>
							</th>
							<td>
								<?php
								if(isset($accessgroups))
								{
									$recursive_set = $this->get_usergroups_for_post($cat_id);
								
									foreach($accessgroups as $accessgroup)
									{
										$checked = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_CATEGORY."
																		WHERE category_id = ".$cat_id."
																			AND group_id = ".$accessgroup['ID'], ARRAY_A)
										
										//$set_recursive = $recursive_set[$accessgroup['groupname']];
													?>
										<p style="margin:6px 0;">
											<label for="uam_accesssgroup-<?php echo $accessgroup['ID'];?>" class="selectit" >
												<input type="checkbox" id="uam_accesssgroup-<?php echo $accessgroup['ID'];?>" <?php if(isset($checked)){ echo 'checked="checked"'; } ?> value="<?php echo $accessgroup['ID']; ?>" name="accessgroups[]"/>
												<?php echo $accessgroup['groupname']; ?>					
											</label>
											<?php
											$group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], "padding:0 0 0 32px;");
								
											echo $group_info_html->link;
											
											if(isset($set_recursive->posts) || isset($set_recursive->categories))
												echo '&nbsp;<a class="uam_group_lock_info_link">[LR]</a>';
											
											echo $group_info_html->content;
											
											if(isset($set_recursive->posts) || isset($set_recursive->categories))
											{
												$recursive_info = '<ul class="uam_group_lock_info" style="padding:0 0 0 32px;"><li class="uam_group_lock_info_head">'.TXT_GROUP_LOCK_INFO.':</li>';
												if(isset($set_recursive->posts))
						    					{
						    						foreach($set_recursive->posts as $cur_id)
						    						{
						    							$cur_post = & get_post($cur_id);
						    							$recursive_info .= "<li>$cur_post->post_title [$cur_post->post_type]</li>";
						    						}
						    					}
						    			
						    					if(isset($set_recursive->categories))
						    					{
						    						foreach($set_recursive->categories as $cur_id)
						    						{
						    							$cur_category = & get_category($cur_id);
						    							$recursive_info .= "<li>$cur_category->name [".TXT_CATEGORY."]</li>";
						    						}
						    					}
												$recursive_info .= "</ul>";
												echo $recursive_info;
											}
										echo "</p>";	
									}
								}
								else
								{
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
				<p class="submit" style="display:block;position:relative;">
					<input class="button-primary" type="submit" value="Update Category" name="submit"/>
				</p>
			<?php
			}
		}
		
		function add_styles()
		{
			wp_enqueue_style('UserAccessManager', UAM_URLPATH."css/uma_admin.css", false, '1.0', 'screen' );
		}
		
		function add_scripts()
		{
			wp_enqueue_script('UserAccessManager', UAM_URLPATH .'js/functions.js', array('jquery'), '1.0' );
		}

		function save_categorydata($category_id)
		{
			global $wpdb;
			
			if(isset($_POST['accessgroups']))
				$accessgroups = $_POST['accessgroups'];
			
			$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_CATEGORY." WHERE category_id = $category_id");
			if(isset($accessgroups))
			{
				foreach($accessgroups as $accessgroup)
				{
					$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_CATEGORY." (category_id,group_id) VALUES(".$category_id.", ".$accessgroup.")");
				}
			}
		}
		
		function remove_categorydata($category_id)
		{
			global $wpdb;
			
			$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_CATEGORY." WHERE category_id = $category_id");
		}
		
		function get_access($post_id = null)
		{
			global $wpdb;
			
			if(isset($this->restrictedPost[$post_id]))
				return $this->restrictedPost[$post_id];
				
			$access = null;
			$cur_id = $post_id;
			$cur_post = & get_post($cur_id);
			if(isset($cur_post->ID))
				$cur_categories = get_the_category($cur_post->ID);		
			
			$uamOptions = $this->getAdminOptions();
			
			if($this->atAdminPanel)
				$sqlCheckLocation = "ag.write_access != 'all'";
			else
				$sqlCheckLocation = "ag.read_access != 'all'";
				
			$restricted_by_categories = array();
			$restricted_by_posts = array();
			
			//check categories access
			if(isset($cur_categories))
			{
				foreach($cur_categories as $cur_category)
				{
					if($cur_post->post_type == "post" || $cur_post->post_type == "attachment")
					{
						$restricted_access_by_cat = $wpdb->get_results("	SELECT *
																			FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP." ag
																			WHERE atc.category_id = ".$cur_category->term_id."
																				AND atc.group_id = ag.ID
																				AND ".$sqlCheckLocation, ARRAY_A);
						if(isset($restricted_access_by_cat))
							$restricted_by_categories[$cur_category->term_id] = $cur_category->term_id;
						
						if($uamOptions['lock_recursive'] == 'true')
						{
							$cur_id = $cur_category->parent;
							while($cur_id != 0)
							{
								$restricted_access_by_cat = $wpdb->get_results("	SELECT *
																					FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP." ag
																					WHERE atc.category_id = ".$cur_id."
																						AND atc.group_id = ag.ID
																						AND ".$sqlCheckLocation, ARRAY_A);
									
								if(isset($cur_category->term_id) && isset($restricted_access_by_cat))
									$restricted_by_categories[$cur_category->term_id] = $cur_category->term_id;
									
								if(isset($cur_category->parent))
								{	
									$cur_id = $cur_category->parent;
									$cur_category = & get_category($cur_id);	
								}
								else
								{
									$cur_id = 0;
								}
							}
						}
					}
				}
			}
			
			//check posts access
			if(isset($cur_post->ID))
			{
				$restricted_access_by_post = $wpdb->get_results("	SELECT *
																	FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP." ag
																	WHERE atp.post_id = ".$cur_post->ID."
																		AND atp.group_id = ag.ID
																		AND ".$sqlCheckLocation, ARRAY_A);
				if(isset($restricted_access_by_post))
					$restricted_by_posts[$cur_post->ID] = $cur_post->ID;
			
				if($uamOptions['lock_recursive'] == 'true')
				{
					if(isset($cur_post->post_parent))
						$cur_id = $cur_post->post_parent;
						
					if($cur_id != 0 && isset($cur_id))
					{
						$restricted_access = $this->get_access($cur_id);

						if(isset($restricted_access->restricted_by_categories))
							$restricted_by_categories = array_unique(array_merge($restricted_by_categories, $restricted_access->restricted_by_categories));
						
						if(isset($restricted_access->restricted_by_posts))
							$restricted_by_posts = array_unique(array_merge($restricted_by_posts, $restricted_access->restricted_by_posts));
					}
				}
				elseif($uamOptions['lock_recursive'] == 'false' && $cur_post->post_type == "attachment")
				{
					if(isset($cur_post->post_parent))
						$cur_id = $cur_post->post_parent;

					if($cur_id != 0 && isset($cur_id))
					{
						$restricted_access = $this->get_access($cur_id);
	
						if(isset($restricted_access->restricted_by_categories))
							$restricted_by_categories = array_unique(array_merge($restricted_by_categories, $restricted_access->restricted_by_categories));
							
						if(isset($restricted_access->restricted_by_posts))
							$restricted_by_posts = array_unique(array_merge($restricted_by_posts, $restricted_access->restricted_by_posts));
					}
				}
			}
				
			if(isset($restricted_by_categories) && count($restricted_by_categories) != 0)
				$access->restricted_by_categories = $restricted_by_categories;
			if(isset($restricted_by_posts) && count($restricted_by_posts) != 0)
				$access->restricted_by_posts = $restricted_by_posts;

			if(empty($access))
				$access = -1;
			
			$this->restrictedPost[$post_id] = $access;
			
			return $access;
		}
		
		function check_access($post_id = null)
		{
			global $wpdb, $current_user;
			
			if(isset($this->postAccess[$post_id]))
				return $this->postAccess[$post_id];
			
			$access = $this->get_access($post_id);
			$cur_user_ip = explode(".", $_SERVER['REMOTE_ADDR']);
			
			if(isset($access->restricted_by_posts) || isset($access->restricted_by_categories))
			{
				if(is_user_logged_in())
				{
					$cur_userdata = get_userdata($current_user->ID);
					
					if(empty($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']))
					{
						if(isset($access->restricted_by_categories))
						{
							foreach($access->restricted_by_categories as $cur_cat_id)
							{
								$user_access = $wpdb->get_results("	SELECT *
																	FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP_TO_USER." atu
																	WHERE atc.category_id = ".$cur_cat_id."
																		AND atc.group_id = atu.group_id
																		AND atu.user_id = ".$current_user->ID, ARRAY_A);
								if(isset($user_access))
								{
									$this->postAccess[$post_id] = true;
									return true;
								}
									
								$access_roles = $wpdb->get_results("SELECT atr.role_name
																	FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP_TO_ROLE." atr
																	WHERE atc.category_id = ".$cur_cat_id."
																		AND atc.group_id = atr.group_id", ARRAY_A);
								
								if(isset($access_roles))
								{
									foreach($access_roles as $access_role)
									{
										if(isset($cur_userdata->wp_capabilities[$access_role['role_name']]))
										{
											$this->postAccess[$post_id] = true;
											return true;
										}
									}
								}
								
								$db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
																FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP." ag
																WHERE atc.category_id = ".$cur_cat_id."
																	AND atc.group_id = ag.ID", ARRAY_A);
								
								if($this->check_user_ip($cur_user_ip, $db_ip_ranges))
								{
									$this->postAccess[$post_id] = true;
									return true;
								}
							}
						}
						
						if(isset($access->restricted_by_posts))
						{
							foreach($access->restricted_by_posts as $cur_post_id)
							{
								$user_access = $wpdb->get_results("	SELECT *
																	FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP_TO_USER." atu
																	WHERE atp.post_id = ".$cur_post_id."
																		AND atp.group_id = atu.group_id
																		AND atu.user_id = ".$current_user->ID, ARRAY_A);
								if(isset($user_access))
								{
									$this->postAccess[$post_id] = true;
									return true;
								}
									
								$access_roles = $wpdb->get_results("SELECT atr.role_name
																	FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP_TO_ROLE." atr
																	WHERE atp.post_id = ".$cur_post_id."
																		AND atp.group_id = atr.group_id", ARRAY_A);
								if(isset($access_roles))
								{
									foreach($access_roles as $access_role)
									{
										if(isset($cur_userdata->wp_capabilities[$access_role['role_name']]))
										{
											$this->postAccess[$post_id] = true;
											return true;
										}
									}
								}
								
								$db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
																FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP." ag
																WHERE atp.post_id = ".$cur_post_id."
																	AND atp.group_id = ag.ID", ARRAY_A);
								
								if($this->check_user_ip($cur_user_ip, $db_ip_ranges))
								{
									$this->postAccess[$post_id] = true;
									return true;
								}
							}
						}
							
						if(empty($user_access))
						{
							$this->postAccess[$post_id] = false;
							return false;
						}
					}
					else
					{
						$this->postAccess[$post_id] = true;
						return true;
					}
				}
				else
				{
					if(isset($access->restricted_by_categories))
					{
						foreach($access->restricted_by_categories as $cur_cat_id)
						{
							$db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
															FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP." ag
															WHERE atc.category_id = ".$cur_cat_id."
																AND atc.group_id = ag.ID");
							
							if($this->check_user_ip($cur_user_ip, $db_ip_ranges))
							{
								$this->postAccess[$post_id] = true;
								return true;
							}
						}
					}
						
					if(isset($access->restricted_by_posts))
					{
						foreach($access->restricted_by_posts as $cur_post_id)
						{
							$db_ip_ranges = $wpdb->get_var("SELECT ag.ip_range
															FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP." ag
															WHERE atp.post_id = ".$cur_post_id."
																AND atp.group_id = ag.ID");
							
							if($this->check_user_ip($cur_user_ip, $db_ip_ranges))
							{
								$this->postAccess[$post_id] = true;
								return true;
							}
						}
					}
					
					$this->postAccess[$post_id] = false;
					return false;
				}
			}
			else
			{
				$this->postAccess[$post_id] = true;
				return true;
			}	
		}
		
		function check_user_ip($cur_user_ip, $db_ip_ranges)
		{	
			if(isset($db_ip_ranges))
			{
				$ip_ranges = explode(";", $db_ip_ranges);
					
				if(isset($ip_ranges))
				{
					foreach($ip_ranges as $ip_range)
					{
						$ip_range = explode("-", $ip_range);
						
						$range_begin = explode(".",$ip_range[0]);
						
						if(isset($ip_range[1]))
							$range_end = explode(".",$ip_range[1]);
						else
							$range_end = explode(".",$ip_range[0]);
						
						if(	$range_begin[0] <= $cur_user_ip[0] && $cur_user_ip[0] <= $range_end[0] && 
							$range_begin[1] <= $cur_user_ip[1] && $cur_user_ip[1] <= $range_end[1] && 
							$range_begin[2] <= $cur_user_ip[2] && $cur_user_ip[2] <= $range_end[2] && 
							$range_begin[3] <= $cur_user_ip[3] && $cur_user_ip[3] <= $range_end[3])
						{
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
			
			foreach($posts as $post)
			{
				if( ($uamOptions['hide_post'] == 'true' && $post->post_type == "post") || ($uamOptions['hide_page'] == 'true' && $post->post_type == "page") || $this->atAdminPanel)
				{
					if($this->check_access($post->ID))
					{
						$post->post_title .= $this->admin_output($post->ID);
						$show_posts[] = $post;
					}
				}
				else
				{
					if(!$this->check_access($post->ID))
					{
						if($post->post_type == "post")
						{
							if($uamOptions['hide_post_title'] == 'true')
								$post->post_title = $uamOptions['post_title'];
							
							$post->post_content = $uamOptions['post_content'];
							
							if($uamOptions['allow_comments_locked'] == 'false')
								$post->comment_status = 'close';
						}
						elseif($post->post_type == "page")
						{
							if($uamOptions['hide_page_title'] == 'true')
								$post->post_title = $uamOptions['page_title'];
							
							$post->post_content = $uamOptions['page_content'];
						}
					}
					$post->post_title .= $this->admin_output($post->ID);
					$show_posts[] = $post;
				}
			}
		
			$posts = $show_posts;
			
			return $posts;
		}
		
		function show_comment($comments = array())
		{
			$show_comments = null;
			$uamOptions = $this->getAdminOptions();
			
			foreach($comments as $comment)
			{
				if($uamOptions['hide_post_comment'] == 'true' || $this->atAdminPanel)
				{
					if($this->check_access($comment->comment_post_ID))
					{
						$show_comments[] = $comment;
					}
				}
				else
				{
					if(!$this->check_access($comment->comment_post_ID))
					{
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
			
			foreach($pages as $page)
			{
				if($uamOptions['hide_page'] == 'true' || $this->atAdminPanel)
				{
					if($this->check_access($page->ID))
					{
						$page->post_title .= $this->admin_output($page->ID);
						$show_pages[] = $page;
					}
				}
				else
				{
					if(!$this->check_access($page->ID))
					{
						if($uamOptions['hide_page_title'] == 'true')
							$page->post_title = $uamOptions['page_title'];
						
						$page->post_content = $uamOptions['page_content'];
					}
					$page->post_title .= $this->admin_output($page->ID);
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
					
			if(empty($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']))
			{
				$uamOptions = $this->getAdminOptions();
				if($uamOptions['hide_post'] == 'true' || $uamOptions['hide_page'] == 'true' || $this->atAdminPanel)
				{
					$args = array( 'numberposts' => -1); 
					$posts = get_posts($args);
					
					foreach($categories as $category)
					{
						$count = 0;
							
						if(isset($posts))
						{
							foreach($posts as $cur_post)
							{
								$post_cat_ids = array();
								$post_cats = get_the_category($cur_post->ID);
								foreach($post_cats as $post_cat)
								{
									$post_cat_ids[] = $post_cat->term_id;
								}
								
								if(in_array($category->term_id, $post_cat_ids) )
								{
									if( ($uamOptions['hide_post'] == 'true' && $cur_post->post_type == "post") || ($uamOptions['hide_page'] == 'true' && $cur_post->post_type == "page") )
									{
										if($this->check_access($cur_post->ID))
											$count++;
									}
									else
									{
										$count++;
									}
								}
							}
						}
						
						if($count != 0 || $uamOptions['hide_empty_categories'] == 'false')
						{
							$category->count = $count;
							$cur_show_categories[$category->term_id] = $category;
						}
						elseif($category->taxonomy == "link_category" || $category->taxonomy == "post_tag")
						{
							$show_categories[] = $category;
						}
						elseif($count == 0)
						{
							$empty_categories[$category->term_id] = $category;
						}
					}
					
					if($uamOptions['hide_empty_categories'] == 'true')
					{
						if(isset($cur_show_categories))
						{
							foreach($cur_show_categories as $cur_show_category)
							{
								$show_categories[$cur_show_category->term_id] = $cur_show_category;
								
								$cur_cat = $cur_show_category;
								while($cur_cat->parent != 0 && isset($empty_categories))
								{
									if(empty($show_categories[$cur_cat->parent]))
										$show_categories[$cur_cat->parent] = $empty_categories[$cur_cat->parent];
									
									$cur_id = $cur_cat->parent;
									$cur_cat = & get_category($cur_id);
								}
							}
						}
					}
					
					if(isset($show_categories))
						$categories = $show_categories;
					else
						$categories = null;
				}
			}
			
			return $categories;
		}
		
		function show_title($title, $post = null)
		{
			$uamOptions = $this->getAdminOptions();
			
			if(isset($post))
				$post_id = $post->ID;
			else
				$post_id = null;
				
			if(!$this->check_access($post_id) && $post != null)
			{
				if($post->post_type == "post")
					$title = $uamOptions['post_title'];
				elseif($post->post_type == "page")
					$title = $uamOptions['page_title'];
			}
			return $title;
		}
		
		function show_next_previous_post($sql)
		{
			$uamOptions = $this->getAdminOptions();

			$posts = get_posts();
			if(isset($posts))
			{
				foreach($posts as $post)
				{
					if(!$this->check_access($post->ID))
						$excluded_posts[] = $post->ID;
				}
				if(isset($excluded_posts))
				{
					$excluded_posts_str = implode(",", $excluded_posts);
					$sql .= "AND ID NOT IN($excluded_posts_str)";
				}
			}	
				
			return $sql;
		}
		
		function admin_output($post_id)
		{
			global $wpdb;
			if(!$this->atAdminPanel)
			{
				$uamOptions = $this->getAdminOptions();
				
				if($uamOptions['blog_admin_hint'] == 'true')
				{
					global $current_user;
					$cur_userdata = get_userdata($current_user->ID);
		
					$output = "";
					
					$access = $this->get_access($post_id);
					if(isset($cur_userdata->{$wpdb->prefix."capabilities"}['administrator']) && (isset($access->restricted_by_posts) || isset($access->restricted_by_categories)))
					{
						$output = "&nbsp;".$uamOptions['blog_admin_hint_text'];	
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
			
			if(isset($_GET['getfile']))
				$cur_file_id = $_GET['getfile'];
				
			if(	$uamOptions['redirect'] != 'false' && 	(	( !$this->check_access() 				&& !$this->atAdminPanel 					&& empty($cur_file_id) ) || 
															( !$this->check_access($cur_file_id) 	&& !wp_attachment_is_image($cur_file_id) 	&& isset($cur_file_id) )) )
			{
				$cur_id = null;
				$cur_post = & get_post($cur_id);
				
				if($uamOptions['redirect'] == 'blog')
					$url = get_option('siteurl');
				elseif($uamOptions['redirect'] == 'custom_page')
				{
					$post_to_go = & get_post($uamOptions['redirect_custom_page']);
					$url = $post_to_go->guid;
				}
				elseif( $uamOptions['redirect'] == 'custom_url')
					$url = $uamOptions['redirect_custom_url'];
					
				$cur_posts = $wp_query->get_posts();
				if(isset($cur_posts))
				{
					foreach($cur_posts as $cur_post)
					{
						if($this->check_access($cur_post->ID))
						{
							$post_to_show = true;
							break;
						}
					}
				}
				
				if($url != "http://".$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"] && empty($post_to_show))
				{
					header("Location: $url");
					exit;
				}
			}
			elseif(isset($_GET['getfile']))
			{
				$cur_id = $_GET['getfile'];
				$cur_post = & get_post($cur_id);
				
				if($cur_post->post_type == 'attachment' && $this->check_access($cur_post->ID))
				{
					$cur_url = explode("?", "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
					$file = str_replace($cur_url[0], "", $cur_post->guid);
					$filename = basename($file);
						
					if(file_exists($file))
					{
						$len = filesize($file);
						header('content-type: '.$cur_post->post_mime_type);
						header('content-length: '.$len);
						
						if(wp_attachment_is_image($cur_id))
						{
							readfile($file);
							exit;
						}
						else
						{
							header('content-disposition: attachment; filename='.basename($file));
							if($uamOptions['download_type'] == 'fopen')
							{
								$fp=fopen($file, 'rb');
							
								while ( ! feof($fp) )
								{
									set_time_limit(30);
									$buffer = fread($fp, 1024);
									echo $buffer;
								}
								exit;
							}
							else
							{
								readfile($file);
								exit;
							}
						}
				 	}
				 	else 
				 	{
						echo 'Error: File not found';
					}
				}
				elseif(wp_attachment_is_image($cur_id))
				{
					$cur_url = explode("?", "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
					$file = str_replace($cur_url[0], "", UAM_URLPATH .'gfx/no_access_pic.png');
					$filename = basename($file);
						
					if(file_exists($file))
					{
						$len = filesize($file);
						header('content-type: '.$cur_post->post_mime_type);
						header('content-length: '.$len);
						
						readfile($file);
						exit;
					}
					else 
				 	{
						echo 'Error: File not found';
					}
				}
			}
		}
		
		function get_file($URL, $ID)
		{
			$uamOptions = $this->getAdminOptions();		
		
			if($uamOptions['lock_file'] == 'true')
			{
				$cur_id = $ID;
				$cur_post = & get_post($cur_id);
				$cur_parent_id = $cur_post->post_parent;
				$cur_parent = & get_post($cur_parent_id);
				
				$type = explode("/", $cur_post->post_mime_type);
				$type = $type[1];
					
				$file_types = $uamOptions['locked_file_types'];
				$file_types = explode(",", $file_types);
				
				if(in_array($type, $file_types) || $uamOptions['lock_file_types'] == 'all')
				{	
					$cur_guid = get_option('siteurl');
					
					$URL = $cur_guid.'?getfile='.$cur_post->ID;
				}
			}
			return $URL;
		}
	}
}

if(class_exists("UserAccessManager"))
{
	$userAccessManager = new UserAccessManager();
}

//Initialize the admin panel
if(!function_exists("UserAccessManager_AP")) {
	function UserAccessManager_AP()
	{
		global $userAccessManager, $wp_version;
		
		$userAccessManager->atAdminPanel = true;
		
		if (!isset($userAccessManager))
		{
			return;
		}
		if (function_exists('add_menu_page'))
		{
			add_menu_page('User Access Manager', 'Access Manager', 9, 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'), UAM_URLPATH."/gfx/icon.png");
		}
		if (function_exists('add_submenu_page'))
		{
			add_submenu_page('uam_usergroup', TXT_MANAGE_GROUP, TXT_MANAGE_GROUP, 9, 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'));
			add_submenu_page('uam_usergroup', TXT_SETTINGS, TXT_SETTINGS, 9, 'uam_settings', array(&$userAccessManager, 'printAdminPage'));
		}
		if( function_exists( 'add_meta_box' )) 
		{
    		add_meta_box( 'uma_post_access', 'Access', array(&$userAccessManager, 'edit_post_content'), 'post', 'side' );
    		add_meta_box( 'uma_post_access', 'Access', array(&$userAccessManager, 'edit_post_content'), 'page', 'side' );
   		}
   		
   		//Admin actions
   		
   		$userAccessManager->update();
   		
  		add_action('manage_posts_custom_column', array(&$userAccessManager, 'add_post_column'), 10, 2);
   		add_action('manage_pages_custom_column', array(&$userAccessManager, 'add_post_column'), 10, 2);
   		add_action('manage_media_custom_column', array(&$userAccessManager, 'add_post_column'), 10, 2);
   		add_action('save_post', array(&$userAccessManager, 'save_postdata'));
   		add_action('add_attachment', array(&$userAccessManager, 'save_postdata'));
   		add_action('attachment_fields_to_save', array(&$userAccessManager, 'save_attachmentdata'));
   		add_action('delete_post', array(&$userAccessManager, 'remove_postdata'));
   		add_action('delete_attachment', array(&$userAccessManager, 'remove_postdata'));
   		
   		add_action('edit_user_profile', array(&$userAccessManager, 'show_user_profile'));
   		add_action('profile_update', array(&$userAccessManager, 'save_userdata'));
   		add_action('delete_user', array(&$userAccessManager, 'remove_userdata'));
   		
   		add_action('edit_category_form', array(&$userAccessManager, 'show_cat_edit_form'));
   		add_action('edit_category', array(&$userAccessManager, 'save_categorydata'));
   		add_action('delete_category', array(&$userAccessManager, 'remove_categorydata'));
   		
   		add_action('wp_print_scripts', array(&$userAccessManager, 'add_scripts') );
		add_action('wp_print_styles', array(&$userAccessManager, 'add_styles') );
   		
   		
   		//Admin filters
		add_filter('manage_posts_columns', array(&$userAccessManager, 'add_post_columns_header'));
		add_filter('manage_pages_columns', array(&$userAccessManager, 'add_post_columns_header'));
		
		$uamOptions = $userAccessManager->getAdminOptions();
		
		if($uamOptions['lock_file'] == 'true')
		{
			add_action('media_meta', array(&$userAccessManager, 'show_media_file'), 10 , 2);
			add_filter('manage_media_columns', array(&$userAccessManager, 'add_post_columns_header'));
		}
		
		if($wp_version >= 2.8 || $uamOptions['core_mod'] == 'true')
		{
			add_filter('manage_users_columns', array(&$userAccessManager, 'add_user_columns_header'), 10);
			add_filter('manage_users_custom_column', array(&$userAccessManager, 'add_user_column'), 10, 2);
			add_filter('manage_categories_columns', array(&$userAccessManager, 'add_category_columns_header'));
			add_filter('manage_categories_custom_column', array(&$userAccessManager, 'add_category_column'), 10, 2);
		}
	}	
}

//Actions and Filters	
if(isset($userAccessManager))
{
	add_action('init', array(&$userAccessManager, 'init'));	
	$uamOptions = $userAccessManager->getAdminOptions();
	
	//install
	if(function_exists('register_activation_hook'))
		register_activation_hook(__FILE__, array(&$userAccessManager, 'install'));
	if(function_exists('register_uninstall_hook'))
		register_uninstall_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
	elseif(function_exists('register_deactivation_hook'))
		register_deactivation_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
	
	//Actions
	add_action('admin_menu', 'UserAccessManager_AP');
		
	if($uamOptions['redirect'] != 'false' || isset($_GET['getfile']))
		add_action('template_redirect', array(&$userAccessManager, 'redirect_user'));
		
	//Filters
	add_filter('wp_get_attachment_url', array(&$userAccessManager, 'get_file'), 10, 2);
	add_filter('the_posts', array(&$userAccessManager, 'show_post'));
	add_filter('comments_array', array(&$userAccessManager, 'show_comment'));
	add_filter('get_pages', array(&$userAccessManager, 'show_page'));
	add_filter('get_terms', array(&$userAccessManager, 'show_category'));
	add_filter('get_next_post_where', array(&$userAccessManager, 'show_next_previous_post'));
	add_filter('get_previous_post_where', array(&$userAccessManager, 'show_next_previous_post'));
	add_filter('get_previous_post_where', array(&$userAccessManager, 'show_next_previous_post'));
	add_filter('the_title', array(&$userAccessManager, 'show_title'), 10, 2);
}

?>