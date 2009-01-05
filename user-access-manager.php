<?php
/* 
Plugin Name: User Access Manager
Plugin URI: http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/
Author URI: http://www.gm-alex.de/
Version: 0.7 Beta
Author: Alexander Schneider
Description: Manage the access to your posts and pages. <strong>Note:</strong> <em>If you activate the plugin your upload dir will protect by a '.htaccess' with a random password and all old downloads insert in a previous post/page will not work anymore. You have to update your posts/pages. If you use already a '.htaccess' file to protect your files the plugin will not overwrite the '.htaccess'.</em>
 
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

*/

//DB
global $wpdb;
define('DB_ACCESSGROUP', $wpdb->prefix.'uam_accessgroups');
define('DB_ACCESSGROUP_TO_POST', $wpdb->prefix.'uam_accessgroup_to_post');
define('DB_ACCESSGROUP_TO_USER', $wpdb->prefix.'uam_accessgroup_to_user');
define('DB_ACCESSGROUP_TO_CATEGORY', $wpdb->prefix.'uam_accessgroup_to_category');
define('DB_ACCESSGROUP_TO_ROLE', $wpdb->prefix.'uam_accessgroup_to_role');

//PATH
define('UAM_URLPATH', WP_CONTENT_URL.'/plugins/'.plugin_basename( dirname(__FILE__)).'/' );

//###Lang###

//---Lang Settings---
define('TXT_SETTINGS', 'Settings');

define('TXT_POST_SETTING', 'Post settings');
define('TXT_POST_SETTING_DESC', 'Set up the behaviour of locked posts');
define('TXT_POST_TITLE', 'Post title');
define('TXT_POST_TITLE_DESC', 'Displayed text as post title if user has no access');
define('TXT_DISPLAY_POST_TITLE', 'Hide post titel');
define('TXT_DISPLAY_POST_TITLE_DESC', 'Selecting "Yes" will show the text which is defined at "'.TXT_POST_TITLE.'" if user has no access.');
define('TXT_POST_CONTENT', 'Post content');
define('TXT_POST_CONTENT_DESC', 'Content displayed if user has no access');
define('TXT_HIDE_POST', 'Hide complete posts');
define('TXT_HIDE_POST_DESC', 'Selecting "Yes" will hide posts if the user has no access.');

define('TXT_PAGE_SETTING', 'Page settings');
define('TXT_PAGE_SETTING_DESC', 'Set up the behaviour of locked pages');
define('TXT_PAGE_TITLE', 'Page title');
define('TXT_PAGE_TITLE_DESC', 'Displayed text as page title if user has no access');
define('TXT_DISPLAY_PAGE_TITLE', 'Hide page titel');
define('TXT_DISPLAY_PAGE_TITLE_DESC', 'Selecting "Yes" will show the text which is defined at "'.TXT_PAGE_TITLE.'" if user has no access.');
define('TXT_PAGE_CONTENT', 'Page content');
define('TXT_PAGE_CONTENT_DESC', 'Content displayed if user has no access');
define('TXT_HIDE_PAGE', 'Hide complete pages');
define('TXT_HIDE_PAGE_DESC', 'Selecting "Yes" will hide pages if the user has no access. Pages will also hide in the navigation.');

define('TXT_FILE_SETTING', 'File settings');
define('TXT_FILE_SETTING_DESC', 'Set up the behaviour of files');
define('TXT_LOCK_FILE', 'Lock files');
define('TXT_LOCK_FILE_DESC', 'If you select "Yes" all files will locked by a .htaccess file and only users with access can download files.');
define('TXT_DOWNLOAD_FILE_TYPE', 'Locked file types');
define('TXT_DOWNLOAD_FILE_TYPE_DESC', 'Type in file types which you will lock if the post/page is locked. <b>Note:</b> If you use images, vids or something else in your posts which are directly shown there and not for download do not type these types in here, because this types will not work anymore.');
define('TXT_DOWNLOAD_TYPE', 'Download type');
define('TXT_DOWNLOAD_TYPE_DESC', 'Selecting the type for downloading. <strong>Note:</strong> For using fopen you need "safe_mode = off".');
define('TXT_NORMAL', 'Normal');
define('TXT_FOPEN', 'fopen');

define('TXT_OTHER_SETTING', 'Other settings');
define('TXT_OTHER_SETTING_DESC', 'Here you will find all other settings');
define('TXT_REDIRECT', 'Redirect user');
define('TXT_REDIRECT_DESC', 'Setup what happen if a user visit a post/page with no access.');
define('TXT_REDIRECT_TO_BOLG', 'To blog startpage');
define('TXT_REDIRECT_TO_PAGE', 'Custom page: ');
define('TXT_REDIRECT_TO_URL', 'Custom URL: ');
define('TXT_LOCK_RECURSIVE', 'Lock recursive');
define('TXT_LOCK_RECURSIVE_DESC', 'Selecting "Yes" will lock all child posts/pages of a post/page if a user has no access to the parent page.');

define('TXT_YES', 'Yes');
define('TXT_NO', 'No');

define('TXT_UPDATE_SETTING', 'Update settings');
define('TXT_UPDATE_SETTINGS', 'Settings updated.');

//---Access groups---

define('TXT_MANAGE_GROUP', 'Manage user access groups');
define('TXT_GROUP_ROLE', 'Role affiliation');
define('TXT_NAME', 'Name');
define('TXT_DESCRIPTION', 'Description');
define('TXT_POSTS', 'Posts');
define('TXT_PAGES', 'Pages');
define('TXT_CATEGORY', 'Categories');
define('TXT_USERS', 'Users');
define('TXT_DELETE', 'Delete');
define('TXT_UPDATE_GROUP', 'Update group');
define('TXT_ADD', 'Add');
define('TXT_ADD_GROUP', 'Add access group');
define('TXT_GROUP_NAME', 'Access group name');
define('TXT_GROUP_NAME_DESC', 'The name is used to identify the access user group.');
define('TXT_GROUP_DESC', 'Access group description');
define('TXT_GROUP_DESC_DESC', 'The description of the group.');
define('TXT_GROUP_ADDED', 'Group was added successfully.');
define('TXT_DEL_GROUP', 'Group(s) was deleted successfully.');
define('TXT_NONE', 'none'); 

//---Misc---
define('TXT_FULL_ACCESS', 'Full access');
define('TXT_FULL_ACCESS_ADMIN', 'Full access (Administrator)');
define('TXT_NO_GROUP', 'No group');
define('TXT_SET_ACCESS', 'Set access');

define('TXT_DATE', 'Date');
define('TXT_TITLE', 'Title');
define('TXT_GROUP_ACCESS', 'Group access');
define('TXT_FULL_ACCESS', 'Full access');
define('TXT_USERNAME', 'Username');

define('TXT_MAIL', 'E-mail');
define('TXT_ACCESS', 'Access');
define('TXT_ADMIN_HINT', '<strong>Note:</strong> An administrator has allways access to all posts/pages.');

define('TXT_SET_POST_ACCESS', 'Set post access');
define('TXT_SET_PAGE_ACCESS', 'Set page access');
define('TXT_GROUPS', 'Access Groups');
define('TXT_CREATE_GROUP_FIRST', 'Please create a access group first.');
define('TXT_SET_USER_ACCESS', 'Set user access');

define('TXT_SET_UP_USERGROUPS', 'Set up usergroups');


if (!class_exists("UserAccessManager"))
{
	class UserAccessManager
	{
		var $adminOptionsName = "uamAdminOptions";
		var $atAdminPanel = false;
		
		function UserAccessManager() 
		{ //constructor
			
		}
		
		function init()
		{
			$this->getAdminOptions();
		}
		
		function install()
		{
			$this->create_htaccess();
			$this->create_htpasswd();
			
			global $wpdb;
			$uam_db_version = "1.0";
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
		
		function uninstall()
		{
			global $wpdb;
			$wpdb->query("DROP TABLE ".DB_ACCESSGROUP.", ".DB_ACCESSGROUP_TO_POST.", ".DB_ACCESSGROUP_TO_USER);
		}
		
		function create_htaccess($create_new = false)
		{
			// Make .htaccess file to protect data
			
			// get url
			$wud = wp_upload_dir();
			$url = $DOCUMENT_ROOT.$wud['basedir']."/";
			
			if(!file_exists($url.".htaccess") || $create_new)
			{
				$areaname = "WP-Files";
				
				$uamOptions = $this->getAdminOptions();
				
				$file_types = $uamOptions['locked_file_types'];
				
				$file_types = str_replace(",", "|", $file_types);
				
				// make .htaccess and .htpasswd
				$htaccess_txt = "<FilesMatch '\.(".$file_types.")'>\n";
				$htaccess_txt .= "AuthType Basic"."\n";
				$htaccess_txt .= "AuthName \"".$areaname."\""."\n";
				$htaccess_txt .= "AuthUserFile ".$url.".htpasswd"."\n";
				$htaccess_txt .= "require valid-user"."\n";
				$htaccess_txt .= "</FilesMatch>\n";

				
				// save files
				$htaccess= fopen($url.".htaccess", "w");
				fwrite($htaccess, $htaccess_txt);
				fclose($htaccess);
			}
		}
		
		function create_htpasswd($create_new = false)
		{
			// get url
			$wud = wp_upload_dir();
			$url = $DOCUMENT_ROOT.$wud['basedir']."/";
			
			if(!file_exists($url.".htpasswd") || $create_new)
			{
				$user = "admin";
				
				# create password
			    $array = array();
			
			    $length = 10;
				$capitals = true;
				
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
			    }
			    
			    // make .htpasswd
			    $htpasswd_txt .= "$user:".md5($password)."\n";
				
				// save file
				$htpasswd= fopen($url.".htpasswd", "w");
				fwrite($htpasswd, $htpasswd_txt);
				fclose($htpasswd);
			}
		}
		
		function delete_htaccess_files()
		{
			$wud = wp_upload_dir();
			$url = $DOCUMENT_ROOT.$wud['basedir']."/";
			unlink($url.".htaccess");
			unlink($url.".htpasswd");
		}
		
		//Returns an array of admin options
		function getAdminOptions() 
		{
			$uamAdminOptions = array(	'hide_post_title' => 'false',
										'post_title' => 'No rights!',
										'post_content' => 'Sorry no rights!',
										'hide_post' => 'false',
										'hide_page_title' => 'false',
										'page_title' => 'No rights!',
										'page_content' => 'Sorry you have no rights to view this page!',
										'hide_page' => 'false',
										'redirect' => 'false',
										'uam_redirect_custom_page' => '',
										'uam_redirect_custom_url' => '',
										'lock_recursive' => 'true',
										'lock_file' => 'true',
										'download_type' => 'fopen',
										'locked_file_types' => 'zip,rar,tar,gz,bz2');
			
			$uamOptions = get_option($this->adminOptionsName);
			if (!empty($uamOptions)) {
				foreach ($uamOptions as $key => $option)
					$uamAdminOptions[$key] = $option;
			}				
			update_option($this->adminOptionsName, $uamAdminOptions);
			return $uamAdminOptions;
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
				
				update_option($this->adminOptionsName, $uamOptions);
				
				if(($locked_file_types_changed || $lock_file_changed) && $uamOptions['lock_file'] != 'false')
				{
					$this->create_htaccess(true);
					$this->create_htpasswd(true);
				}
				?>
					<div class="updated"><p><strong><?php _e(TXT_UPDATE_SETTINGS, "UserAccessManager");?></strong></p></div>
				<?php
			} 
			
			$cur_admin_page = $_GET['page'];
			$action = $_GET['action'];

			if($_POST['action'] == 'addgroup')
			{
				$wpdb->query("INSERT INTO ".DB_ACCESSGROUP." (ID, groupname, groupdesc) VALUES(NULL, '".$_POST['access_group_name']."', '".$_POST['access_group_description']."')");
				?>
					<div class="updated"><p><strong><?php _e(TXT_GROUP_ADDED, "UserAccessManager");?></strong></p></div> 
				<?php
			}
			
			if($_POST['action'] == 'delgroup')
			{
				$del_ids = $_POST['delete'];
				if($del_ids)
				{
					foreach($del_ids as $del_id)
					{
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP." WHERE ID = $del_id LIMIT 1");
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE group_id = $del_id LIMIT 1");
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE group_id = $del_id LIMIT 1");
					}
					?>
						<div class="updated"><p><strong><?php _e(TXT_DEL_GROUP, "UserAccessManager");?></strong></p></div> 
					<?php
				}
			}
			
			if($_POST['action'] == 'update_group')
			{
				$wpdb->query("	UPDATE ".DB_ACCESSGROUP." 
								SET groupname = '".$_POST['access_group_name']."', groupdesc = '".$_POST['access_group_description']."'
								WHERE ID = ".$_POST['access_group_id']);
				
				$roles = $_POST['roles'];
				if($roles)
				{
					$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_ROLE." WHERE group_id = ".$_POST['access_group_id']);
					foreach($roles as $role)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_ROLE." (group_id, role_name) VALUES('".$_POST['access_group_id']."', '".$role."')");
					}
				}
				?>
					<div class="updated"><p><strong><?php _e("Access group edit successfully.", "UserAccessManager");?></strong></p></div> 
				<?php
			}
			
			if($cur_admin_page == 'uam_usergroup' AND !$action)
			{
				$accessgroups = $wpdb->get_results("SELECT *
													FROM ".DB_ACCESSGROUP."
													ORDER BY ID", ARRAY_A);
				?>
				<div class=wrap>
					<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
						<input type="hidden" value="delgroup" name="action"/>
						<h2><?php _e(TXT_MANAGE_GROUP, "UserAccessManager"); ?></h2>
						<div class="tablenav">
							<div class="alignleft">
								<input type="submit" class="button-secondary delete" name="deleteit" value="<?php _e(TXT_DELETE, "UserAccessManager"); ?>"/>
							</div>
							<br class="clear"/>
						</div>
						<br class="clear"/>
						<table class="widefat">
							<thead>
								<tr class="thead">
									<th scope="col"></th>
									<th scope="col"><?php _e(TXT_NAME, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_DESCRIPTION, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_POSTS, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_PAGES, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_CATEGORY, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_USERS, "UserAccessManager"); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if($accessgroups)
								{
									foreach($accessgroups as $accessgroup)
									{
										$users = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_USER."
																		WHERE group_id = ".$accessgroup['ID']."
																		ORDER BY user_id", ARRAY_A);
										$posts = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_POST."
																		WHERE group_id = ".$accessgroup['ID']."
																		ORDER BY post_id", ARRAY_A);
										$categories = $wpdb->get_results("	SELECT *
																			FROM ".DB_ACCESSGROUP_TO_CATEGORY."
																			WHERE group_id = ".$accessgroup['ID']."
																			ORDER BY category_id", ARRAY_A);
									?>
										<tr class="alternate" id="group-<?php echo $accessgroup['ID']; ?>">
		 									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $accessgroup['ID']; ?>" name="delete[]"/></th>
											<td><strong><a href="?page=<?php echo $cur_admin_page; ?>&action=edit_group&id=<?php echo $accessgroup['ID']; ?>"><?php echo $accessgroup['groupname']; ?></a></strong></td>
											<td><?php echo $accessgroup['groupdesc']; ?></td>
											<td>
												<?php
												if($posts)
												{
													foreach($posts as $post)
													{
														$cur_id = $post['post_id'];
														$cur_post = & get_post($cur_id);
														if($cur_post->post_type == 'post')
															echo "- ".$cur_post->post_title."<br />";
													}
												}
												else
												{
													_e(TXT_NONE, "UserAccessManager");
												}
												?>
											</td>
											<td>
												<?php
												if($posts)
												{
													foreach($posts as $post)
													{
														$cur_id = $post['post_id'];
														$cur_post = & get_post($cur_id);
														if($cur_post->post_type == 'page')
															echo "- ".$cur_post->post_title."<br />";
													}
												}
												else
												{
													_e(TXT_NONE, "UserAccessManager");
												}
												?>
											</td>
											<td>
												<?php
												if($categories)
												{
													foreach($categories as $categorie)
													{
														$cur_cat = get_category($categorie['category_id']);
														echo "- ".$cur_cat->cat_name."<br />";
													}
												}
												else
												{
													_e(TXT_NONE, "UserAccessManager");
												}
												?>
											</td>
											<td>
												<?php
												if($users)
												{
													foreach($users as $user)
													{
														$cur_user = get_userdata($user['user_id']);
														echo "- ".$cur_user->nickname."<br />";
													}
												}
												else
												{
													_e(TXT_NONE, "UserAccessManager");
												}
												?>
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
					<h2><?php _e(TXT_ADD_GROUP, "UserAccessManager"); ?></h2>
					<div id="ajax-response"/>
					<form class="add:the-list: validate" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post" id="addgroup" name="addgroup">
						<input type="hidden" value="addgroup" name="action"/>
						<table class="form-table">
							<tbody>
								<tr class="form-field form-required">
									<th valign="top" scope="row"><label for="group_name"><?php _e(TXT_GROUP_NAME, "UserAccessManager"); ?>e</label></th>
									<td><input type="text" aria-required="true" size="40" value="" id="access_group_name" name="access_group_name"/><br/>
					            	<?php _e(TXT_GROUP_NAME_DESC, "UserAccessManager"); ?></td>
					            	
								</tr>
								<tr class="form-field form-required">
									<th valign="top" scope="row"><label for="group_name"><?php _e(TXT_GROUP_DESC, "UserAccessManager"); ?></label></th>
									<td><input type="text" aria-required="true" size="40" value="" id="access_group_description" name="access_group_description"/><br/>
					            	<?php _e(TXT_GROUP_DESC_DESC, "UserAccessManager"); ?></td>
					            </tr>
					            <tr class="form-field form-required">
					            	<th valign="top" scope="row"><label for="group_role"><?php _e(TXT_GROUP_ROLE, "UserAccessManager"); ?></label></th>
									<td>
						            	<?php
						               	global $wp_roles;
	   		
	   									foreach($wp_roles->role_names as $role => $name)
										{
											?>
											<label class="selectit">
												<input type="checkbox" value="<?php echo $role; ?>" name="roles[]"/>
													<?php echo $role ?>
											</label><br />
											<?php 
										}
	   								 	?>
   								 	</td>
   								 </tr>
							</tbody>
						</table>
						<p class="submit"><input type="submit" value="<?php _e(TXT_ADD, "UserAccessManager"); ?>" name="submit" class="button"/></p>
					</form>
				</div>
				<?php
			}
			elseif($cur_admin_page == 'uam_settings' AND !$action)
			{
				?>
				<div class=wrap>
					<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
						<h2><?php _e(TXT_SETTINGS, "UserAccessManager"); ?></h2>
						<h3><?php _e(TXT_POST_SETTING, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_POST_SETTING_DESC, "UserAccessManager"); ?></p>
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row">
										<?php _e(TXT_POST_TITLE, "UserAccessManager"); ?>
									</th>
									<td>
										<input name="uam_post_title" value="<?php _e($uamOptions['post_title'], 'UserAccessManager') ?>" />
										<br />
										<?php _e(TXT_POST_TITLE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php _e(TXT_DISPLAY_POST_TITLE, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_hide_post_title_yes">
											<input type="radio" id="uam_hide_post_title_yes" name="uam_hide_post_title" value="true" <?php if ($uamOptions['hide_post_title'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_YES, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_post_title_no">
											<input type="radio" id="uam_hide_post_title_no" name="uam_hide_post_title" value="false" <?php if ($uamOptions['hide_post_title'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/> 
											<?php _e(TXT_NO, "UserAccessManager"); ?>
										</label>
										<br />
										<?php _e(TXT_DISPLAY_POST_TITLE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php _e(TXT_POST_CONTENT, "UserAccessManager"); ?>
									</th>
									<td>
										<textarea name="uam_post_content" style="width: 80%; height: 100px;"><?php _e(apply_filters('format_to_edit',$uamOptions['post_content']), 'UserAccessManager') ?></textarea>
										<br />
										<?php _e(TXT_POST_CONTENT_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php _e(TXT_HIDE_POST, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_hide_post_yes">
											<input type="radio" id="uam_hide_post_yes" name="uam_hide_post" value="true" <?php if ($uamOptions['hide_post'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_YES, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_post_no">
											<input type="radio" id="uam_hide_post_no" name="uam_hide_post" value="false" <?php if ($uamOptions['hide_post'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/>
											<?php _e(TXT_NO, "UserAccessManager"); ?>
										</label>
										<br />
										<?php _e(TXT_HIDE_POST_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
							</tbody>
						</table>
						<h3><?php _e(TXT_PAGE_SETTING, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_PAGE_SETTING_DESC, "UserAccessManager"); ?></p>
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<?php _e(TXT_PAGE_TITLE, "UserAccessManager"); ?>
									</th>
									<td>
										<input name="uam_page_title" value="<?php _e($uamOptions['page_title'], 'UserAccessManager') ?>" />
										<br />
										<?php _e(TXT_PAGE_TITLE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">
										<?php _e(TXT_DISPLAY_PAGE_TITLE, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_hide_page_title_yes">
											<input type="radio" id="uam_hide_page_title_yes" name="uam_hide_page_title" value="true" <?php if ($uamOptions['hide_page_title'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_YES, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_page_title_no">
											<input type="radio" id="uam_hide_page_title_no" name="uam_hide_page_title" value="false" <?php if ($uamOptions['hide_page_title'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/> 
											<?php _e(TXT_NO, "UserAccessManager"); ?>
										</label>
										<br />
										<?php _e(TXT_DISPLAY_PAGE_TITLE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php _e(TXT_PAGE_CONTENT, "UserAccessManager"); ?>
									</th>
									<td>
										<textarea name="uam_page_content" style="width: 80%; height: 100px;"><?php _e(apply_filters('format_to_edit',$uamOptions['page_content']), 'UserAccessManager') ?></textarea>
										<br />
										<?php _e(TXT_PAGE_CONTENT_DESC, "UserAccessManager"); ?>									
									</td>
								</tr>
								<tr>
									<th>
										<?php _e(TXT_HIDE_PAGE, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_hide_page_yes">
											<input type="radio" id="uam_hide_page_yes" name="uam_hide_page" value="true" <?php if ($uamOptions['hide_page'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_YES, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_hide_page_no">
											<input type="radio" id="uam_hide_page_no" name="uam_hide_page" value="false" <?php if ($uamOptions['hide_page'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/>
											<?php _e(TXT_NO, "UserAccessManager"); ?>
										</label>
										<br />
										<?php _e(TXT_HIDE_PAGE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
							</tbody>
						</table>
						<h3><?php _e(TXT_FILE_SETTING, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_FILE_SETTING_DESC, "UserAccessManager"); ?></p>
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<?php _e(TXT_LOCK_FILE, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_lock_file_yes">
											<input type="radio" id="uam_lock_file_yes" name="uam_lock_file" value="true" <?php if ($uamOptions['lock_file'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_YES, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_lock_file_no">
											<input type="radio" id="uam_lock_file_no" name="uam_lock_file" value="false" <?php if ($uamOptions['lock_file'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/> 
											<?php _e(TXT_NO, "UserAccessManager"); ?>
										</label>
										<br />
										<?php _e(TXT_LOCK_FILE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php _e(TXT_DOWNLOAD_FILE_TYPE, "UserAccessManager"); ?>
									</th>
									<td>
										<input name="uam_locked_file_types" value="<?php _e($uamOptions['locked_file_types'], 'UserAccessManager') ?>" />
										<br />
										<?php _e(TXT_DOWNLOAD_FILE_TYPE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php _e(TXT_DOWNLOAD_TYPE, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_download_type_normal">
											<input type="radio" id="uam_download_type_normal" name="uam_download_type" value="normal" <?php if ($uamOptions['download_type'] == "normal") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_NORMAL, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_download_type_fopen">
											<input type="radio" id="uam_download_type_fopen" name="uam_download_type" value="fopen" <?php if ($uamOptions['download_type'] == "fopen") { _e('checked="checked"', "UserAccessManager"); }?>/> 
											<?php _e(TXT_FOPEN, "UserAccessManager"); ?>
										</label>
										<br />
										<?php _e(TXT_DOWNLOAD_TYPE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
							</tbody>
						</table>
						<h3><?php _e(TXT_OTHER_SETTING, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_OTHER_SETTING_DESC, "UserAccessManager"); ?></p>
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<?php _e(TXT_REDIRECT, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_redirect_no">
											<input type="radio" id="uam_redirect_no" name="uam_redirect" value="false" <?php if ($uamOptions['redirect'] == "false") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_NO, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_redirect_blog">
											<input type="radio" id="uam_redirect_blog" name="uam_redirect" value="blog" <?php if ($uamOptions['redirect'] == "blog") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_REDIRECT_TO_BOLG, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_redirect_custom_page">
											<input type="radio" id="uam_redirect_custom_p" name="uam_redirect" value="custom_page" <?php if ($uamOptions['redirect'] == "custom_page") { _e('checked="checked"', "UserAccessManager"); }?>/> 
											<?php _e(TXT_REDIRECT_TO_PAGE, "UserAccessManager"); ?> 
										</label><input name="uam_redirect_custom_page" value="<?php _e($uamOptions['redirect_custom_page'], 'UserAccessManager') ?>" />&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_redirect_custom_url">
											<input type="radio" id="uam_redirect_custom_u" name="uam_redirect" value="custom_url" <?php if ($uamOptions['redirect'] == "custom_url") { _e('checked="checked"', "UserAccessManager"); }?>/> 
											<?php _e(TXT_REDIRECT_TO_URL, "UserAccessManager"); ?> 
										</label><input name="uam_redirect_custom_url" value="<?php _e($uamOptions['redirect_custom_url'], 'UserAccessManager') ?>" />
										<br />
										<?php _e(TXT_REDIRECT_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php _e(TXT_LOCK_RECURSIVE, "UserAccessManager"); ?>
									</th>
									<td>
										<label for="uam_lock_recursive_yes">
											<input type="radio" id="uam_lock_recursive_yes" name="uam_lock_recursive" value="true" <?php if ($uamOptions['lock_recursive'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
											<?php _e(TXT_YES, "UserAccessManager"); ?>
										</label>&nbsp;&nbsp;&nbsp;&nbsp;
										<label for="uam_lock_recursive_no">
											<input type="radio" id="uam_lock_recursive_no" name="uam_lock_recursive" value="false" <?php if ($uamOptions['lock_recursive'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/> 
											<?php _e(TXT_NO, "UserAccessManager"); ?>
										</label>
										<br />
										<?php _e(TXT_LOCK_RECURSIVE_DESC, "UserAccessManager"); ?>
									</td>
								</tr>
							</tbody>
						</table>
						<div class="submit">
							<input type="submit" name="update_uam_settings" value="<?php _e(TXT_UPDATE_SETTING, 'UserAccessManager') ?>" />
						</div>
					</form>
				</div>
				<?php
			}
			elseif($action == 'edit_group')
			{
				$group_id = $_GET['id'];
				$accessgroup = $wpdb->get_row("	SELECT *
												FROM ".DB_ACCESSGROUP."
												WHERE ID = ".$group_id."
												LIMIT 1", ARRAY_A);
				?>
				<div class=wrap>
					<form method="post" action="<?php echo reset(explode("?", $_SERVER["REQUEST_URI"]))."?page=".$_GET['page']; ?>">
						<input type="hidden" value="update_group" name="action"/>
						<input type="hidden" value="<?php echo $group_id; ?>" name="access_group_id"/>
					 	<table class="form-table">
							<tbody>
								<tr class="form-field form-required">
									<th valign="top" scope="row"><label for="group_name"><?php _e(TXT_GROUP_NAME, "UserAccessManager"); ?></label></th>
									<td><input type="text" aria-required="true" size="40" value="<?php echo $accessgroup["groupname"];?>" id="access_group_name" name="access_group_name"/><br/>
					            	<?php _e(TXT_GROUP_NAME_DESC, "UserAccessManager"); ?></td>
								</tr>
								<tr class="form-field form-required">
									<th valign="top" scope="row"><label for="group_name"><?php _e(TXT_GROUP_DESC, "UserAccessManager"); ?></label></th>
									<td><input type="text" aria-required="true" size="40" value="<?php echo $accessgroup["groupdesc"];?>" id="access_group_description" name="access_group_description"/><br/>
					            	<?php _e(TXT_GROUP_DESC_DESC, "UserAccessManager"); ?></td>
					            </tr>
					            <tr class="form-field form-required">
					            	<th valign="top" scope="row"><label for="group_role"><?php _e(TXT_GROUP_ROLE, "UserAccessManager"); ?></label></th>
									<td>
						            	<?php
						               	global $wp_roles;
	   		
	   									foreach($wp_roles->role_names as $role => $name)
										{
											$checked = $wpdb->get_results("	SELECT *
																			FROM ".DB_ACCESSGROUP_TO_ROLE."
																			WHERE role_name = '".$role."'
																				AND group_id = ".$group_id, ARRAY_A)
											?>
											<label class="selectit">
												<input type="checkbox" <?php if($checked){ echo 'checked="checked"'; } ?>value="<?php echo $role; ?>" name="roles[]"/>
													<?php echo $role ?>
											</label><br />
											<?php 
										}
	   								 	?>
   								 	</td>
   								 </tr>
							</tbody>
						</table>
						<p class="submit"><input type="submit" value="<?php _e(TXT_UPDATE_GROUP, "UserAccessManager"); ?>" name="submit" class="button"/></p>
					</form>
				</div>
				<?php
			}
		}//End function printAdminPage()
		
		function get_usergroups_for_post($ID)
		{
			global $wpdb;
			
			$access = $this->get_access($ID);
			
			$post_usergroups = array();
			
			if($access->restricted_by_posts || $access->restricted_by_categories)
			{
				if($access->restricted_by_posts)
				{
					foreach($access->restricted_by_posts as $cur_id)
					{
						$usergroups = $wpdb->get_results("	SELECT ag.groupname
															FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_POST." agtp
															WHERE agtp.post_id = ".$cur_id."
																AND ag.ID = agtp.group_id
															GROUP BY ag.groupname", ARRAY_A);
			   		
						if($usergroups)
						{
							foreach($usergroups as $usergroup)
							{
								$cur_usergroup = $post_usergroups[$usergroup['groupname']];
								
								$cur_usergroup->name = $usergroup['groupname'];
			
								if($cur_id != $ID)
								{
									$posts = $cur_usergroup->posts;
									
									$lock_post = & get_post($cur_id);
									$posts[] = $lock_post->ID;
									$cur_usergroup->posts = $posts;
								}
								else
								{
									$cur_usergroup->itself = true;
								}
								
								$post_usergroups[$usergroup['groupname']] = $cur_usergroup;
							}
						}	
					}
				}
				
				if($access->restricted_by_categories)
				{
					foreach($access->restricted_by_categories as $cur_id)
					{
						$usergroups = $wpdb->get_results("	SELECT ag.groupname
															FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_CATEGORY." agtc
															WHERE agtc.category_id = ".$cur_id."
																AND ag.ID = agtc.group_id
															GROUP BY ag.groupname", ARRAY_A);
			   		
						if($usergroups)
						{
							foreach($usergroups as $usergroup)
							{
								$cur_usergroup = $post_usergroups[$usergroup['groupname']];
								
								$cur_usergroup->name = $usergroup['groupname'];

								$categories = $cur_usergroup->categories;
									
								$lock_cat = & get_category($cur_id);
								$categories[] = $lock_cat->term_id;
								$cur_usergroup->categories = $categories;
								
								$post_usergroups[$usergroup['groupname']] = $cur_usergroup;
							}
						}			
					}
				}
			}
			
			return $post_usergroups;
		}
		
		function add_post_columns_header($defaults)
		{
    		$defaults['access'] = __('Access');
    		return $defaults;
		}
		
		function add_post_column($column_name, $id)
		{			
		    if( $column_name == 'access' )
		    {
		    	$usergroups = $this->get_usergroups_for_post($id);
		    	if($usergroups)
		    	{
		    		foreach($usergroups as $usergroup)
		    		{
		    			$output = "- ".$usergroup->name. "";
		    			$output .= " (Set by: ";
		    			
		    			if($usergroup->itself)
		    				$output .= "itself, ";
		    			
		    			if($usergroup->posts)
		    			{
		    				foreach($usergroup->posts as $cur_id)
		    				{
		    					$cur_post = & get_post($cur_id);
		    					$output .= "$cur_post->post_title [$cur_post->post_type], ";
		    				}
		    			}
		    			
		    			if($usergroup->categories)
		    			{
		    				foreach($usergroup->categories as $cur_id)
		    				{
		    					$cur_category = & get_category($cur_id);
		    					$output .= "$cur_category->name [category], ";
		    				}
		    			}
						
		    			$output = substr($output, 0, -2);
		    			
		    			$output .= ")<br />";
		    			echo $output;
		    		}
		    	}
		    	else
				{ 
					_e(TXT_FULL_ACCESS, "UserAccessManager");
				}
		    }
		}
		
		function edit_post_content($post)
		{
			global $wpdb;
			$accessgroups = $wpdb->get_results("SELECT *
												FROM ".DB_ACCESSGROUP."
												ORDER BY groupname", ARRAY_A);
			if($accessgroups)
			{
				foreach($accessgroups as $accessgroup)
				{
					$checked = $wpdb->get_results("	SELECT *
													FROM ".DB_ACCESSGROUP_TO_POST."
													WHERE post_id = ".$post->ID."
													AND group_id = ".$accessgroup['ID'], ARRAY_A)
					?>
					<p>
						<label class="selectit" for="uam_accesss">
							<input type="checkbox" <?php if($checked){ echo 'checked="checked"'; } ?>value="<?php echo $accessgroup['ID']; ?>" name="accessgroups[]"/>
							<?php echo $accessgroup['groupname'] ?>
						</label>
					</p>
					<?php 
				}
			}
			else
			{
				echo "<p>";
				_e(TXT_CREATE_GROUP_FIRST, "UserAccessManager");
				echo "</p>";
			}
		}
		
		function save_postdata($post_id)
		{
			global $wpdb;
			$accessgroups = $_POST['accessgroups'];
			$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE post_id = $post_id");
			if($accessgroups)
			{
				foreach($accessgroups as $accessgroup)
				{
					$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_POST." (post_id,group_id) VALUES(".$post_id.", ".$accessgroup.")");
				}
			}
		}
		
		function remove_postdata($post_id)
		{
			global $wpdb;
			$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE post_id = $post_id");
		}
		
		function add_user_columns_header($defaults)
		{
    		$defaults['access'] = __('Access');
    		return $defaults;
		}
		
		function add_user_column($column_name, $id)
		{
			global $wpdb;
			
			if( $column_name == 'access' )
		    {
			    $usergroups = $wpdb->get_results("	SELECT ag.groupname
													FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_USER." agtp
													WHERE agtp.user_id = ".$id."
														AND ag.ID = agtp.group_id
													GROUP BY ag.groupname", ARRAY_A);
																					
				if($usergroups)
				{
					foreach($usergroups as $usergroup)
					{
						$content .= $usergroup['groupname']."<br />";
					}
				}
				else
				{ 
					$content  = TXT_NO_GROUP;
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
			
			if($cur_userdata->wp_capabilities['administrator'] == 1)
			{
				$accessgroups = $wpdb->get_results("SELECT *
													FROM ".DB_ACCESSGROUP."
													ORDER BY groupname", ARRAY_A);
					
				?>
				<h3><?php _e(TXT_GROUPS, "UserAccessManager"); ?></h3>
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="usergroups"><?php _e(TXT_SET_UP_USERGROUPS, "UserAccessManager"); ?></label>
							</th>
							<td>
								<?php
								if($cur_edit_userdata->wp_capabilities['administrator'] != 1)
								{
									if($accessgroups)
									{
										foreach($accessgroups as $accessgroup)
										{
											$checked = $wpdb->get_results("	SELECT *
																			FROM ".DB_ACCESSGROUP_TO_USER."
																			WHERE user_id = ".$user_id."
																				AND group_id = ".$accessgroup['ID'], ARRAY_A)
									?>
											<label class="selectit">
												<input type="checkbox" <?php if($checked){ echo 'checked="checked"'; } ?>value="<?php echo $accessgroup['ID']; ?>" name="accessgroups[]"/>
													<?php echo $accessgroup['groupname'] ?>
											</label><br />
											<?php 
										}
									}
									else
									{
										_e(TXT_CREATE_GROUP_FIRST, "UserAccessManager");
									}
								}
								else
								{
									_e(TXT_ADMIN_HINT, "UserAccessManager");
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>
				<?php
			}
		}
		
		function save_userdata($user_id)
		{
			global $wpdb, $current_user;
			
			$cur_userdata = get_userdata($current_user->ID);		

			if($cur_userdata->wp_capabilities['administrator'] == 1)
			{
				$accessgroups = $_POST['accessgroups'];
				$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE user_id = $user_id");
				if($accessgroups)
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

			if($cur_userdata->wp_capabilities['administrator'] == 1)
				$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE user_id = $user_id");
		}
	
		function add_category_columns_header($defaults)
		{
    		$defaults['access'] = __('Access');
    		return $defaults;
		}
		
		function add_category_column($column_name, $id)
		{
			global $wpdb;
			
			if( $column_name == 'access' )
		    {
				$usergroups = $wpdb->get_results("	SELECT ag.groupname
													FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_CATEGORY." agtc
													WHERE agtc.category_id = ".$id."
														AND ag.ID = agtc.group_id
													GROUP BY ag.groupname", ARRAY_A);
				
				if($usergroups)
				{						
					foreach($usergroups as $usergroup)
					{
						$content .= "- ".$usergroup['groupname']."<br />";
					}
				}
		    	else
				{ 
					$content  = TXT_NO_GROUP;
				}
				return $content;
		    }
		}
		
		function show_cat_add_form($cat)
		{
			global $wpdb, $current_user;

			$cat_id = $cat->cat_ID;

			$accessgroups = $wpdb->get_results("SELECT *
												FROM ".DB_ACCESSGROUP."
												ORDER BY groupname", ARRAY_A);
					
			?>
			<div class="form-field">
				<label for="usergroups"><?php _e(TXT_SET_UP_USERGROUPS, "UserAccessManager"); ?></label>
				<?php
				if($accessgroups)
				{
					foreach($accessgroups as $accessgroup)
					{
						$checked = $wpdb->get_results("	SELECT *
														FROM ".DB_ACCESSGROUP_TO_CATEGORY."
														WHERE category_id = ".$cat_id."
															AND group_id = ".$accessgroup['ID'], ARRAY_A)
						?>
						<label class="selectit">
							<input type="checkbox" <?php if($checked){ echo 'checked="checked"'; } ?>value="<?php echo $accessgroup['ID']; ?>" name="accessgroups[]"/>
								<?php echo $accessgroup['groupname'] ?>
						</label>
						<?php 
					}
				}
				else
				{
					_e(TXT_CREATE_GROUP_FIRST, "UserAccessManager");
				}
				?>
			</div>
			<?php
		}
		
		function show_cat_edit_form($cat)
		{
			global $wpdb, $current_user;

			$cat_id = $cat->cat_ID;

			$accessgroups = $wpdb->get_results("SELECT *
												FROM ".DB_ACCESSGROUP."
												ORDER BY groupname", ARRAY_A);
					
			?>
			<table class="form-table">
				<tbody>
					<tr>
						<th>
							<label for="description"><?php _e(TXT_SET_UP_USERGROUPS, "UserAccessManager"); ?></label>
						</th>
						<td>
							<?php
							if($accessgroups)
							{
								foreach($accessgroups as $accessgroup)
								{
									$checked = $wpdb->get_results("	SELECT *
																	FROM ".DB_ACCESSGROUP_TO_CATEGORY."
																	WHERE category_id = ".$cat_id."
																		AND group_id = ".$accessgroup['ID'], ARRAY_A)
												?>
									<label class="selectit">
										<input type="checkbox" <?php if($checked){ echo 'checked="checked"'; } ?>value="<?php echo $accessgroup['ID']; ?>" name="accessgroups[]"/>
											<?php echo $accessgroup['groupname'] ?>
									</label><br />
									<?php 
								}
							}
							else
							{
								_e(TXT_CREATE_GROUP_FIRST, "UserAccessManager");
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}
		
		function add_head_content()
		{
			wp_enqueue_style('UserAccessManager', UAM_URLPATH."css/uma_admin.css" , false, '1.0.0', 'screen');
		}

		function save_categorydata($category_id)
		{
			global $wpdb;
			
			$accessgroups = $_POST['accessgroups'];
			$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_CATEGORY." WHERE category_id = $category_id");
			if($accessgroups)
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
		
		function get_access($post_id)
		{
			global $wpdb;
			
			$cur_id = $post_id;
			$cur_post = & get_post($cur_id);
			$cur_categories = get_the_category($cur_id);		
			
			$uamOptions = $this->getAdminOptions();
			
			
			//check categories access
			foreach($cur_categories as $cur_category)
			{
				if($uamOptions['lock_recursive'] == 'true')
				{
					$cur_id = $cur_category->term_id;
					while($cur_id != 0)
					{
						$restricted_access_by_cat = $wpdb->get_results("	SELECT *
																			FROM ".DB_ACCESSGROUP_TO_CATEGORY."
																			WHERE category_id = ".$cur_category->term_id, ARRAY_A);
						if($restricted_access_by_cat)
							$restricted_by_categories[] = $cur_category->term_id;
						
						$cur_id = $cur_category->parent;
						$cur_category = get_the_category($cur_id);	
					}
				}
				elseif($uamOptions['lock_recursive'] == 'false')
				{
					$restricted_access_by_cat = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_CATEGORY."
																		WHERE category_id = ".$cur_category->term_id, ARRAY_A);
					if($restricted_access_by_cat)
						$restricted_by_categories[] = $cur_category->term_id;
				}
			}

			//check posts access
			if($uamOptions['lock_recursive'] == 'true')
			{
				$cur_id = $cur_post->ID;
				while($cur_id != 0)
				{
					$restricted_access_by_post = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_POST."
																		WHERE post_id = ".$cur_post->ID, ARRAY_A);
					if($restricted_access_by_post)
						$restricted_by_posts[] = $cur_post->ID;
					
					$cur_id = $cur_post->post_parent;
					$cur_post = & get_post($cur_id);
				}
			}
			elseif($uamOptions['lock_recursive'] == 'false')
			{
				if( $cur_post->post_type != 'attachment')
				{
					$restricted_access_by_post = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_POST."
																		WHERE post_id = ".$cur_post->ID, ARRAY_A);
				}
				else
				{
					$restricted_access_by_post = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_POST."
																		WHERE post_id = ".$cur_post->post_parent, ARRAY_A);
				}
				if($restricted_access_by_post)
						$restricted_by_posts[] = $cur_post->ID;
			}
			
			$access->restricted_by_categories = $restricted_by_categories;
			$access->restricted_by_posts = $restricted_by_posts;
			
			return $access;
		}
		
		function check_access($post_id = null)
		{
			global $wpdb, $current_user;
			
			$access = $this->get_access($post_id);
			
			if($access->restricted_by_posts || $access->restricted_by_categories)
			{
				if(is_user_logged_in())
				{
					$cur_userdata = get_userdata($current_user->ID);
					
					if($cur_userdata->wp_capabilities['administrator'] != 1)
					{
						if($access->restricted_by_categories)
						{
							foreach($access->restricted_by_categories as $cur_cat_id)
							{
								$user_access = $wpdb->get_results("	SELECT *
																	FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP_TO_USER." atu
																	WHERE atc.category_id = ".$cur_cat_id."
																		AND atc.group_id = atu.group_id
																		AND atu.user_id = ".$current_user->ID, ARRAY_A);
								if($user_access)
									return true;
									
								$access_roles = $wpdb->get_results("SELECT atr.role_name
																	FROM ".DB_ACCESSGROUP_TO_CATEGORY." atc, ".DB_ACCESSGROUP_TO_ROLE." atr
																	WHERE atc.category_id = ".$cur_cat_id."
																		AND atc.group_id = atr.group_id", ARRAY_A);
								
								if($access_roles)
								{
									foreach($access_roles as $access_role)
									{
										if($cur_userdata->wp_capabilities[$access_role['role_name']] == 1)
											return true;
									}
								}
							}
						}
						
						if($access->restricted_by_posts)
						{
							foreach($access->restricted_by_posts as $cur_post_id)
							{
								$user_access = $wpdb->get_results("	SELECT *
																	FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP_TO_USER." atu
																	WHERE atp.post_id = ".$cur_post_id."
																		AND atp.group_id = atu.group_id
																		AND atu.user_id = ".$current_user->ID, ARRAY_A);
								if($user_access)
									return true;
									
								$access_roles = $wpdb->get_results("SELECT atr.role_name
																	FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP_TO_ROLE." atr
																	WHERE atp.post_id = ".$cur_post_id."
																		AND atp.group_id = atr.group_id", ARRAY_A);
								if($access_roles)
								{
									foreach($access_roles as $access_role)
									{
										if($cur_userdata->wp_capabilities[$access_role['role_name']] == 1)
											return true;
									}
								}
							}
						}
							
						if(!$user_access)
							return false;
					}
					else
					{
						return true;
					}
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}	
		}
		
		function show_post($posts = array())
		{
			$no_posts = count($posts);
			$uamOptions = $this->getAdminOptions();
			
			for($i=0; $i < $no_posts; $i++)
			{
				if( ($uamOptions['hide_post'] == 'true' && $posts[$i]->post_type == "post") || ($uamOptions['hide_page'] == 'true' && $posts[$i]->post_type == "page") )
				{
					if($this->check_access($posts[$i]->ID))
					{
						$posts[$i]->post_title .= $this->admin_output($posts[$i]->ID);
						$show_posts[] = $posts[$i];
					}
				}
				else
				{
					if(!$this->check_access($posts[$i]->ID))
					{
						if($posts[$i]->post_type == "post")
						{
							if($uamOptions['hide_post_title'] == 'true')
								$posts[$i]->post_title = $uamOptions['post_title'];
							
							$posts[$i]->post_content = $uamOptions['post_content'];
						}
						elseif($posts[$i]->post_type == "page")
						{
							if($uamOptions['hide_page_title'] == 'true')
								$posts[$i]->post_title = $uamOptions['page_title'];
							
							$posts[$i]->post_content = $uamOptions['page_content'];
						}
					}
					$posts[$i]->post_title .= $this->admin_output($posts[$i]->ID);
					$show_posts[] = $posts[$i];
				}
				
			}
		
			$posts = $show_posts;
			
			return $posts;
		}
	
		function show_page($pages = array())
		{
			$no_pages = count($pages);
			$uamOptions = $this->getAdminOptions();
			
			for($i=0; $i < $no_pages; $i++)
			{
				if($uamOptions['hide_page'] == 'true')
				{
					if($this->check_access($pages[$i]->ID))
					{
						$pages[$i]->post_title .= $this->admin_output($pages[$i]->ID);
						$show_pages[] = $pages[$i];
					}
				}
				else
				{
					if(!$this->check_access($pages[$i]->ID))
					{
						if($uamOptions['hide_page_title'] == 'true')
							$pages[$i]->post_title = $uamOptions['page_title'];
						
						$pages[$i]->post_content = $uamOptions['page_content'];
					}
					$pages[$i]->post_title .= $this->admin_output($pages[$i]->ID);
					$show_pages[] = $pages[$i];
				}
				
			}
		
			$pages = $show_pages;
			
			return $pages;
		}
		
		function show_category($categories)
		{
			if(!$this->atAdminPanel)
			{
				$uamOptions = $this->getAdminOptions();
				if($uamOptions['hide_post'] == 'true' || $uamOptions['hide_page'] == 'true')
				{
					$args = array( 'numberposts' => -1); 
					$posts = get_posts($args);
					
					foreach($categories as $category)
					{
						$count = 0;
							
						if($posts)
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
						
						if($count != 0)
						{
							$category->count = $count;
							$show_categories[] = $category;
						}
						
						$categories = $show_categories;
					}
				}
			}
				
			return $categories;
		}
		
		function show_next_previous_post($sql)
		{
			$uamOptions = $this->getAdminOptions();
			//if($uamOptions['hide_post'] == 'true')
			//{
				$posts = get_posts();
				foreach($posts as $post)
				{
					if(!$this->check_access($post->ID))
						$excluded_posts[] = $post->ID;
				}
				if($excluded_posts)
				{
					$excluded_posts_str = implode(",", $excluded_posts);
					$sql .= "AND ID NOT IN($excluded_posts_str)";
				}
			//}	
			return $sql;
		}
		
		function admin_output($post_id)
		{
			if(!$this->atAdminPanel)
			{
				global $current_user;
				$cur_userdata = get_userdata($current_user->ID);
	
				$access = $this->get_access($post_id);
				if($cur_userdata->wp_capabilities['administrator'] == 1 && ($access->restricted_by_posts || $access->restricted_by_categories))
				{
					$output = "&nbsp;[L]";	
				}
				return $output;
			}
		}
	
		function redirect_user()
		{
			global $wp_query;
			
			if(!$this->check_access() && $uamOptions['redirect'] != 'false')
			{
				$uamOptions = $this->getAdminOptions();				
				
				if($uamOptions['redirect'] == 'blog' || $uamOptions['redirect'] == 'custom_page')
				{
					$host  = $_SERVER['HTTP_HOST'];
					$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
					
					if($uamOptions['redirect'] == 'blog')
						$extra = '';
					elseif($uamOptions['redirect'] == 'custom_page')
						$extra = $uamOptions['redirect_custom_page'];
						
					$url = "http://".$host.$uri."/".$extra;
				}
				elseif( $uamOptions['redirect'] == 'custom_url')
				{
					$url = $uamOptions['redirect_custom_url'];
				}
				
				
				$cur_posts = $wp_query->get_posts();

				$post_to_show = false;
				
				foreach($cur_posts as $cur_post)
				{
					if($this->check_access())
					{
						$post_to_show = true;
						break;
					}
				}
				
				if($url != "http://".$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"] && !$post_to_show)
				{
					header("Location: $url");
					exit;
				}
			}
			elseif(isset($_GET['getfile']))
			{
				$cur_id = $_GET['getfile'];
				$cur_post = & get_post($cur_id);
				
				if ($cur_post->post_type == 'attachment' && $this->check_access($cur_post->ID))
				{
					$file = str_replace("http://".$_SERVER['HTTP_HOST']."/", "", $cur_post->guid);
					$filename = basename($file);
					
					if (file_exists($file))
					{
						$len = filesize($file);
						header('content-type: '.$cur_post->post_mime_type);
						header('content-length: '.$len);
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
				
				if(in_array($type, $file_types))
				{
					if(strpos($cur_parent->guid, "?"))
						$char = "&";
					else
						$char = "?";
	
					$URL = $cur_parent->guid.$char.'getfile='.$cur_post->ID;
				}
			}
			return $URL;
		}
	}
}

if (class_exists("UserAccessManager"))
{
	$userAccessManager = new UserAccessManager();
}

//Initialize the admin panel
if (!function_exists("UserAccessManager_AP")) {
	function UserAccessManager_AP()
	{
		global $userAccessManager;
		
		$userAccessManager->atAdminPanel = true;
		
		if (!isset($userAccessManager))
		{
			return;
		}
		if (function_exists('add_menu_page'))
		{
			add_menu_page('User Access Manager', 'Access Manager', 9, 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'));
		}
		if (function_exists('add_submenu_page'))
		{
			add_submenu_page('uam_usergroup', TXT_MANAGE_GROUP, TXT_MANAGE_GROUP, 9, 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'));
			add_submenu_page('uam_usergroup', TXT_SETTING, TXT_SETTINGS, 9, 'uam_settings', array(&$userAccessManager, 'printAdminPage'));
		}
		if( function_exists( 'add_meta_box' )) 
		{
    		add_meta_box( 'uma_post_access', 'Access', array(&$userAccessManager, 'edit_post_content'), 'post', 'side' );
    		add_meta_box( 'uma_post_access', 'Access', array(&$userAccessManager, 'edit_post_content'), 'page', 'side' );
   		}
   		
   		//Admin actions
   		add_action('manage_posts_custom_column', array(&$userAccessManager, 'add_post_column'), 9, 2);
   		add_action('manage_pages_custom_column', array(&$userAccessManager, 'add_post_column'), 9, 2);
   		add_action('save_post', array(&$userAccessManager, 'save_postdata'), 9);
   		add_action('delete_post', array(&$userAccessManager, 'remove_postdata'), 9);
   		
   		add_action('edit_user_profile', array(&$userAccessManager, 'show_user_profile'), 9);
   		add_action('profile_update', array(&$userAccessManager, 'save_userdata'), 9);
   		add_action('delete_user', array(&$userAccessManager, 'remove_userdata'), 9);
   		
   		//add_action('edit_category_add_form_before_button', array(&$userAccessManager, 'show_cat_add_form'), 9);
   		add_action('edit_category_edit_form_before_button', array(&$userAccessManager, 'show_cat_edit_form'), 9);
   		//add_action('created_term', array(&$userAccessManager, 'save_categorydata'), 9);
   		add_action('edit_category', array(&$userAccessManager, 'save_categorydata'), 9);
   		add_action('delete_category', array(&$userAccessManager, 'remove_categorydata'), 9);
   		
   		//Admin filters
		add_filter('manage_posts_columns', array(&$userAccessManager, 'add_post_columns_header'), 9);
		add_filter('manage_pages_columns', array(&$userAccessManager, 'add_post_columns_header'), 9);
		add_filter('manage_users_columns', array(&$userAccessManager, 'add_user_columns_header'), 10);
		add_filter('manage_users_custom_column', array(&$userAccessManager, 'add_user_column'), 10, 2);
		add_filter('manage_categories_columns', array(&$userAccessManager, 'add_category_columns_header'), 9);
		add_filter('manage_categories_custom_column', array(&$userAccessManager, 'add_category_column'), 10, 2);
	}	
}

//Actions and Filters	
if (isset($userAccessManager))
{	
	$uamOptions = $userAccessManager->getAdminOptions();
	
	//install
	register_activation_hook(__FILE__, array(&$userAccessManager, 'install'));
	if(function_exists('register_uninstall_hook'))
		register_uninstall_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
	elseif(function_exists('register_deactivation_hook'))
		register_deactivation_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
	
	//Actions
	add_action('admin_menu', 'UserAccessManager_AP');

	add_action('wp_head', array(&$userAccessManager, 'add_head_content'), 1);
		
	if($uamOptions['redirect'] != 'false' || isset($_GET['getfile']))
		add_action('template_redirect', array(&$userAccessManager, 'redirect_user'), 1);
		
	//Filters
	add_filter('wp_get_attachment_url', array(&$userAccessManager, 'get_file'), 1, 2);	
	add_filter('the_posts', array(&$userAccessManager, 'show_post'), 1);
	add_filter('get_pages', array(&$userAccessManager, 'show_page'), 1);
	add_filter('get_terms', array(&$userAccessManager, 'show_category'), 1);
	add_filter('get_next_post_where', array(&$userAccessManager, 'show_next_previous_post'), 1);
	add_filter('get_previous_post_where', array(&$userAccessManager, 'show_next_previous_post'), 1);
}

?>