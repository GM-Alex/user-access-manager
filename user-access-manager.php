<?php
/* 
Plugin Name: User Access Manager
Plugin URI: http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/
Author URI: http://www.gm-alex.de/
Version: 0.62
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
define(DB_ACCESSGROUP, $wpdb->prefix.'uam_accessgroups');
define(DB_ACCESSGROUP_TO_POST, $wpdb->prefix.'uam_accessgroup_to_post');
define(DB_ACCESSGROUP_TO_USER, $wpdb->prefix.'uam_accessgroup_to_user');

//Lang
define(TXT_FULL_ACCESS, 'Full access');
define(TXT_FULL_ACCESS_ADMIN, 'Full access (Administrator)');
define(TXT_NO_GROUP, 'No group');
define(TXT_SET_ACCESS, 'Set access');
define(TXT_UPDATE_SETTINGS, 'Settings updated.');
define(TXT_GROUP_ADDED, 'Group was added successfully.');
define(TXT_RESET_POST,'Post(s) was reset successfully.');
define(TXT_RESET_PAGE,'Page(s) was reset successfully.');
define(TXT_RESET_USER,'User(s) was reset successfully.');
define(TXT_ACCESS_CHANGED,'Access was changed successfully.');
define(TXT_DEL_GROUP, 'Group(s) was deleted successfully.');
define(TXT_MANAGE_POST, 'Manage post access');
define(TXT_MANAGE_PAGE, 'Manage page access');
define(TXT_MANAGE_USER, 'Manage user access');
define(TXT_RESET_ACCESS, 'Reset access');
define(TXT_DATE, 'Date');
define(TXT_TITLE, 'Title');
define(TXT_GROUP_ACCESS, 'Group access');
define(TXT_FULL_ACCESS, 'Full access');
define(TXT_USERNAME, 'Username');
define(TXT_NAME, 'Name');
define(TXT_MAIL, 'E-mail');
define(TXT_ACCESS, 'Access');
define(TXT_MANAGE_USER_NOTE, '<strong>Note:</strong> An administrator has allways access to all posts/pages.');
define(TXT_DESCRIPTION, 'Description');
define(TXT_POSTS, 'Posts');
define(TXT_PAGES, 'Pages');
define(TXT_USERS, 'Users');
define(TXT_DELETE, 'Delete');
define(TXT_MANAGE_GROUP, 'Manage user access groups');
define(TXT_ADD_GROUP, 'Add access group');
define(TXT_GROUP_NAME, 'Access group name');
define(TXT_GROUP_NAME_DESC, 'The name is used to identify the access user group.');
define(TXT_GROUP_DESC, 'Access group description');
define(TXT_GROUP_DESC_DESC, 'The description of the group.');
define(TXT_SETTINGS, 'Settings');
define(TXT_TITLE_STR, 'Displayed text as title if user has no access');
define(TXT_DISPLAY_TITLE, 'Display titel if user has no access');
define(TXT_DISPLAY_TITLE_DESC, 'Selecting "No" will show the text which is defined at "'.TXT_TITLE_STR.'"');
define(TXT_DISPLAY_CONTENT, 'Content displayed if user has no access');
define(TXT_HIDE_POST, 'Hide complete posts/pages');
define(TXT_HIDE_POST_DESC, 'Selecting "Yes" will hide posts/pages if the user has no access.');
define(TXT_HIDE_PAGE_NAVI, 'Hide pages in Navigation');
define(TXT_HIDE_PAGE_NAVI_DESC, 'Selecting "Yes" will hide pages in the navigation if the user has no access.');
define(TXT_REDIRECT, 'Redirect if user has no access');
define(TXT_REDIRECT_DESC, 'Setup what happen if a user visit a post/page with no access.');
define(TXT_REDIRECT_TO_BOLG, 'To blog startpage');
define(TXT_REDIRECT_TO_PAGE, 'Custom page: ');
define(TXT_REDIRECT_TO_URL, 'Custom URL: ');
define(TXT_LOCK_RECURSIVE, 'Lock recursive');
define(TXT_LOCK_RECURSIVE_DESC, 'Selecting "Yes" will lock all child posts/pages of a post/page if a user has no access to the parent page.');
define(TXT_DOWNLOAD_TYPE, 'Download type');
define(TXT_DOWNLOAD_TYPE_DESC, 'Selecting the type for downloading. <strong>Note:</strong> For using fopen you need "safe_mode = off".');
define(TXT_NORMAL, 'Normal');
define(TXT_FOPEN, 'fopen');
define(TXT_UPDATE_SETTING, 'Update settings');
define(TXT_UPDATE_GROUP, 'Update group');
define(TXT_YES, 'Yes');
define(TXT_NO, 'No');
define(TXT_ADD, 'Add');
define(TXT_SET_POST_ACCESS, 'Set post access');
define(TXT_SET_PAGE_ACCESS, 'Set page access');
define(TXT_GROUPS, 'Access Groups');
define(TXT_CREATE_GROUP_FIRST, 'Please create a access group first.');
define(TXT_SET_USER_ACCESS, 'Set user access');


if (!class_exists("UserAccessManager"))
{
	class UserAccessManager
	{
		var $adminOptionsName = "uamAdminOptions";
		
		function UserAccessManager() 
		{ //constructor
			
		}
		
		function init()
		{
			$this->getAdminOptions();
		}
		
		function install()
		{
			// Make .htaccess file to protect data
			
			// get url
			$wud = wp_upload_dir();
			$url = $DOCUMENT_ROOT.$wud['basedir']."/";
			
			if(!file_exists($url.".htaccess"))
			{
				$areaname = "WP-Files";
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
				
				// make .htaccess and .htpasswd
				$htaccess_txt = "AuthType Basic"."\n";
				$htaccess_txt .= "AuthName \"".$areaname."\""."\n";
				$htaccess_txt .= "AuthUserFile ".$url.".htpasswd"."\n";
				$htaccess_txt .= "require valid-user"."\n";
				$htpasswd_txt .= "$user:".md5($passwort)."\n";
				
				// save files
				$htaccess= fopen($url.".htaccess", "w");
				$htpasswd= fopen($url.".htpasswd", "w");
				fputs($htaccess, $htaccess_txt);
				fputs($htpasswd, $htpasswd_txt);
				fclose($htaccess);
				fclose($htpasswd);
			}
			
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
			
			add_option("uam_db_version", $uam_db_version);
		}
		
		function uninstall()
		{
			global $wpdb;
			$wpdb->query("DROP TABLE ".DB_ACCESSGROUP.", ".DB_ACCESSGROUP_TO_POST.", ".DB_ACCESSGROUP_TO_USER);
		}
		
		//Returns an array of admin options
		function getAdminOptions() 
		{
			$uamAdminOptions = array(	'hide_title' => 'false',
										'post_title' => 'No rights!',
										'post_content' => 'Sorry no rights!',
										'hide_post' => 'false',
										'hide_page' => 'false',
										'redirect' => 'false',
										'uam_redirect_custom_page' => '',
										'uam_redirect_custom_url' => '',
										'lock_recursive' => 'true',
										'download_type' => 'fopen');
			
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
				if (isset($_POST['uam_hide_title']))
				{
					$uamOptions['hide_title'] = $_POST['uam_hide_title'];
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
				if (isset($_POST['uam_download_type']))
				{
					$uamOptions['download_type'] = $_POST['uam_download_type'];
				}
				update_option($this->adminOptionsName, $uamOptions);
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
			
			if($_POST['action'] == 'resetposts')
			{
				$reset_ids = $_POST['reset'];
				if($reset_ids)
				{
					foreach($reset_ids as $reset_id)
					{
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE post_id = $reset_id");
					}
					if($cur_admin_page == 'uam_posts')
					{
					?>
						<div class="updated"><p><strong><?php _e(TXT_RESET_POST, "UserAccessManager");?></strong></p></div> 
					<?php
					}
					elseif($cur_admin_page == 'uam_pages')
					{
					?>
						<div class="updated"><p><strong><?php _e(TXT_RESET_PAGE, "UserAccessManager");?></strong></p></div> 
					<?php
					}
				}
			}
			
			if($_POST['action'] == 'resetusers')
			{
				$reset_ids = $_POST['reset'];
				if($reset_ids)
				{
					foreach($reset_ids as $reset_id)
					{
						$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE user_id = $reset_id");
					}
					?>
						<div class="updated"><p><strong><?php _e(TXT_RESET_USER, "UserAccessManager");?></strong></p></div> 
					<?php
				}
			}
			
			if($_POST['action'] == 'addgroup_to_post')
			{
				$accessgroups = $_POST['accessgroups'];
				$post_id = $_POST['post_id'];
				$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_POST." WHERE post_id = $post_id");
				if($accessgroups)
				{
					foreach($accessgroups as $accessgroup)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_POST." (post_id,group_id) VALUES(".$post_id.", ".$accessgroup.")");
					}
				}
				?>
					<div class="updated"><p><strong><?php _e(TXT_ACCESS_CHANGED, "UserAccessManager");?></strong></p></div> 
				<?php
			}
			
			if($_POST['action'] == 'addgroup_to_user')
			{
				$accessgroups = $_POST['accessgroups'];
				$user_id = $_POST['user_id'];
				$wpdb->query("DELETE FROM ".DB_ACCESSGROUP_TO_USER." WHERE user_id = $user_id");
				if($accessgroups)
				{
					foreach($accessgroups as $accessgroup)
					{
						$wpdb->query("INSERT INTO ".DB_ACCESSGROUP_TO_USER." (user_id, group_id) VALUES(".$user_id.", ".$accessgroup.")");
					}
				}
				?>
					<div class="updated"><p><strong><?php _e(TXT_ACCESS_CHANGED, "UserAccessManager");?></strong></p></div> 
				<?php
			}
			
			if($_POST['action'] == 'update_group')
			{
				$wpdb->query("	UPDATE ".DB_ACCESSGROUP." 
								SET groupname = '".$_POST['access_group_name']."', groupdesc = '".$_POST['access_group_description']."'
								WHERE ID = ".$_POST['access_group_id']);
				?>
					<div class="updated"><p><strong><?php _e("Access group edit successfully.", "UserAccessManager");?></strong></p></div> 
				<?php
			}
			
			if(($cur_admin_page == 'uam_posts' || $cur_admin_page == 'uam_pages') AND !$action)
			{
				switch ($cur_admin_page)
				{
					case "uam_posts" :
						$posts = get_posts();
						break;
					case "uam_pages" :
						$posts = get_pages('sort_column=menu_order');
						break;
				}
				
				?>
				<div class=wrap>
					<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
						<input type="hidden" value="resetposts" name="action"/>
						<?php 
						if($cur_admin_page == 'uam_posts')
						{
						?>
							<h2><?php _e(TXT_MANAGE_POST, "UserAccessManager"); ?></h2>
						<?php
						}
						elseif($cur_admin_page == 'uam_pages')
						{
					 	?>
					 		<h2><?php _e(TXT_MANAGE_PAGE, "UserAccessManager"); ?></h2>
					 	<?php
						}
					 	?>
						<div class="tablenav">
							<div class="alignleft">
								<input type="submit" class="button-secondary delete" name="resetit" value="<?php _e(TXT_RESET_ACCESS, "UserAccessManager"); ?>"/>
							</div>
							<br class="clear"/>
						</div>
						<br class="clear"/>
						<table class="widefat">
							<thead>
								<tr class="thead">
									<th scope="col"></th>
									<th scope="col"><?php _e(TXT_DATE, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_TITLE, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_GROUP_ACCESS, "UserAccessManager"); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if($posts)
								{
									foreach($posts as $post)
									{
										$deepness = 0;
										if($post->post_parent != 0)
										{
											$cur_post = $post;
											$cur_id = $post->ID;
											while($cur_post->post_parent != 0)
											{
												$deepness++;	
												$cur_post = & get_post($cur_id = $cur_post->post_parent);
											}
										}
									?>
										<tr class="alternate author-self status-publish" id="post-<?php echo $post->ID; ?>">
		 									<th class="check-column" scope="row"><input type="checkbox" value="<?php echo $post->ID; ?>" name="reset[]"/></th>
											<td><abbr title="<?php echo $post->post_date; ?>"><?php echo $post->post_date; ?></abbr></td>
											<td>
												<strong class="row-title"><?php for($i=0; $i < $deepness; $i++){ echo "— "; } echo $post->post_title; ?></strong>
											</td>
											<td>
												<a href="?page=<?php echo $cur_admin_page; ?>&action=post_to_group&id=<?php echo $post->ID; ?>">
												<?php
												$usergroups = $wpdb->get_results("	SELECT ag.groupname
																					FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_POST." agtp
																					WHERE agtp.post_id = ".$post->ID."
																						AND ag.ID = agtp.group_id
																					GROUP BY ag.groupname", ARRAY_A);
												
												if($usergroups)
												{
													foreach($usergroups as $usergroup)
													{
														echo $usergroup['groupname']."<br />";
													}
												}
												else
												{ 
													_e(TXT_FULL_ACCESS, "UserAccessManager");
												}
												?>
												</a>
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
				<?php
			}
			elseif($cur_admin_page == 'uam_users' AND !$action)
			{
				$users = $wpdb->get_results("	SELECT ID
												FROM $wpdb->users
												ORDER BY ID", ARRAY_A);
				?>
				<div class=wrap>
					<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
						<input type="hidden" value="resetusers" name="action"/>
						<h2><?php _e(TXT_MANAGE_USER, "UserAccessManager"); ?></h2>
						<div class="tablenav">
							<div class="alignleft">
								<input type="submit" class="button-secondary delete" name="resetit" value="<?php _e(TXT_RESET_ACCESS, "UserAccessManager"); ?>"/>
							</div>
							<br class="clear"/>
						</div>
						<br class="clear"/>
						<table class="widefat">
							<thead>
								<tr class="thead">
									<th scope="col"></th>
									<th scope="col"><?php _e(TXT_USERNAME, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_NAME, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_MAIL, "UserAccessManager"); ?></th>
									<th scope="col"><?php _e(TXT_ACCESS, "UserAccessManager"); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if($users)
								{
									foreach($users as $user)
									{
										$cur_user = get_userdata($user['ID']);
									?>
										<tr class="alternate" id="user-<?php echo $cur_user->ID; ?>">
		 									<th class="check-column" scope="row">
		 										<?php 
													if($cur_user->wp_capabilities['administrator'] == 0)
													{
														?>
		 													<input type="checkbox" value="<?php echo $cur_user->ID; ?>" name="reset[]"/>
		 												<?php 
													}
												?>
		 									</th>
											<td><strong><?php echo $cur_user->nickname; ?></strong></td>
											<td><?php echo $cur_user->user_firstname." ".$cur_user->user_lastname; ?></td>
											<td><?php echo $cur_user->user_email; ?></td>
											<td>
												<?php 
													if($cur_user->wp_capabilities['administrator'] == 1)
													{
														_e(TXT_FULL_ACCESS_ADMIN, "UserAccessManager");
													}
													else
													{
													 	?>
														<a href="?page=<?php echo $cur_admin_page; ?>&action=user_to_group&id=<?php echo $cur_user->ID; ?>">
														<?php
														$usergroups = $wpdb->get_results("	SELECT ag.groupname
																							FROM ".DB_ACCESSGROUP." ag, ".DB_ACCESSGROUP_TO_USER." agtp
																							WHERE agtp.user_id = ".$cur_user->ID."
																								AND ag.ID = agtp.group_id
																							GROUP BY ag.groupname", ARRAY_A);
																				
														if($usergroups)
														{
															foreach($usergroups as $usergroup)
															{
																echo $usergroup['groupname']."<br />";
															}
														}
														else
														{ 
															_e(TXT_NO_GROUP, "UserAccessManager");
														}
														?>
														</a>
														<?php
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
					<p>
						<?php _e(TXT_MANAGE_USER_NOTE, "UserAccessManager"); ?>
					</p>
				</div>
				<?php
			}
			elseif($cur_admin_page == 'uam_usergroup' AND !$action)
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
														$cur_post = & get_post($cur_id = $post['post_id']);
														if($cur_post->post_type == 'post')
															echo "- ".$cur_post->post_title."<br />";
													}
												}
												?>
											</td>
											<td>
												<?php
												if($posts)
												{
													foreach($posts as $post)
													{
														$cur_post = & get_post($cur_id = $post['post_id']);
														if($cur_post->post_type == 'page')
															echo "- ".$cur_post->post_title."<br />";
													}
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
						<h3><?php _e(TXT_DISPLAY_TITLE, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_DISPLAY_TITLE_DESC, "UserAccessManager"); ?></p>
						<p>
							<label for="uam_hide_title_yes">
								<input type="radio" id="uam_hide_title_yes" name="uam_hide_title" value="true" <?php if ($uamOptions['hide_title'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
								<?php _e(TXT_YES, "UserAccessManager"); ?>
							</label>&nbsp;&nbsp;&nbsp;&nbsp;
							<label for="uam_hide_title_no">
								<input type="radio" id="uam_hide_title_no" name="uam_hide_title" value="false" <?php if ($uamOptions['hide_title'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/> 
								<?php _e(TXT_NO, "UserAccessManager"); ?>
							</label>
						</p>
						<h3><?php _e(TXT_TITLE_STR, "UserAccessManager"); ?></h3>
						<p>
							<input name="uam_post_title" value="<?php _e($uamOptions['post_title'], 'UserAccessManager') ?>" />
						</p>
						<h3><?php _e(TXT_DISPLAY_CONTENT, "UserAccessManager"); ?></h3>
						<textarea name="uam_post_content" style="width: 80%; height: 100px;"><?php _e(apply_filters('format_to_edit',$uamOptions['post_content']), 'UserAccessManager') ?></textarea>
						<h3><?php _e(TXT_HIDE_POST, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_HIDE_POST_DESC, "UserAccessManager"); ?></p>
						<p>
							<label for="uam_hide_post_yes">
								<input type="radio" id="uam_hide_post_yes" name="uam_hide_post" value="true" <?php if ($uamOptions['hide_post'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
								<?php _e(TXT_YES, "UserAccessManager"); ?>
							</label>&nbsp;&nbsp;&nbsp;&nbsp;
							<label for="uam_hide_post_no">
								<input type="radio" id="uam_hide_post_no" name="uam_hide_post" value="false" <?php if ($uamOptions['hide_post'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/>
								<?php _e(TXT_NO, "UserAccessManager"); ?>
							</label>
						</p>
						<h3><?php _e(TXT_HIDE_PAGE_NAVI, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_HIDE_PAGE_NAVI_DESC, "UserAccessManager"); ?></p>
						<p>
							<label for="uam_hide_page_yes">
								<input type="radio" id="uam_hide_page_yes" name="uam_hide_page" value="true" <?php if ($uamOptions['hide_page'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
								<?php _e(TXT_YES, "UserAccessManager"); ?>
							</label>&nbsp;&nbsp;&nbsp;&nbsp;
							<label for="uam_hide_page_no">
								<input type="radio" id="uam_hide_page_no" name="uam_hide_page" value="false" <?php if ($uamOptions['hide_page'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/> 
								<?php _e(TXT_NO, "UserAccessManager"); ?>
							</label>
						</p>
						<h3><?php _e(TXT_REDIRECT, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_REDIRECT_DESC, "UserAccessManager"); ?></p>
						<p>
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
						</p>
						<h3><?php _e(TXT_LOCK_RECURSIVE, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_LOCK_RECURSIVE_DESC, "UserAccessManager"); ?></p>
						<p>
							<label for="uam_lock_recursive_yes">
								<input type="radio" id="uam_lock_recursive_yes" name="uam_lock_recursive" value="true" <?php if ($uamOptions['lock_recursive'] == "true") { _e('checked="checked"', "UserAccessManager"); }?> />
								<?php _e(TXT_YES, "UserAccessManager"); ?>
							</label>&nbsp;&nbsp;&nbsp;&nbsp;
							<label for="uam_lock_recursive_no">
								<input type="radio" id="uam_lock_recursive_no" name="uam_lock_recursive" value="false" <?php if ($uamOptions['lock_recursive'] == "false") { _e('checked="checked"', "UserAccessManager"); }?>/> 
								<?php _e(TXT_NO, "UserAccessManager"); ?>
							</label>
						</p>
						<h3><?php _e(TXT_DOWNLOAD_TYPE, "UserAccessManager"); ?></h3>
						<p><?php _e(TXT_DOWNLOAD_TYPE_DESC, "UserAccessManager"); ?></p>
						<p>
							<label for="uam_download_type_normal">
								<input type="radio" id="uam_download_type_normal" name="uam_download_type" value="normal" <?php if ($uamOptions['download_type'] == "normal") { _e('checked="checked"', "UserAccessManager"); }?> />
								<?php _e(TXT_NORMAL, "UserAccessManager"); ?>
							</label>&nbsp;&nbsp;&nbsp;&nbsp;
							<label for="uam_download_type_fopen">
								<input type="radio" id="uam_download_type_fopen" name="uam_download_type" value="fopen" <?php if ($uamOptions['download_type'] == "fopen") { _e('checked="checked"', "UserAccessManager"); }?>/> 
								<?php _e(TXT_FOPEN, "UserAccessManager"); ?>
							</label>
						</p>
						<div class="submit">
							<input type="submit" name="update_uam_settings" value="<?php _e(TXT_UPDATE_SETTING, 'UserAccessManager') ?>" />
						</div>
					</form>
				</div>
				<?php
			}
			elseif($action == 'post_to_group')
			{
				$post_id = $_GET['id'];
				$accessgroups = $wpdb->get_results("SELECT *
													FROM ".DB_ACCESSGROUP."
													ORDER BY groupname", ARRAY_A);
				
				?>
				<div class=wrap>
					<form method="post" action="<?php echo reset(explode("?", $_SERVER["REQUEST_URI"]))."?page=".$_GET['page']; ?>">
						<input type="hidden" value="addgroup_to_post" name="action"/>
						<input type="hidden" value="<?php echo $post_id; ?>" name="post_id"/>
						<?php 
						if($cur_admin_page == 'uam_posts')
						{
							echo "<h2>";
							_e(TXT_SET_POST_ACCESS, "UserAccessManager");
							echo "</h2>";
						}
						elseif($cur_admin_page == 'uam_pages')
						{
							echo "<h2>";
							_e(TXT_SET_PAGE_ACCESS, "UserAccessManager");
							echo "</h2>";
						}
					 	?>
						<div class="postbox">
							<h3><?php _e(TXT_GROUPS, "UserAccessManager"); ?></h3>
							<div class="inside">
								<?php
								if($accessgroups)
								{
									foreach($accessgroups as $accessgroup)
									{
										$checked = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_POST."
																		WHERE post_id = ".$post_id."
																			AND group_id = ".$accessgroup['ID'], ARRAY_A)
										?>
										<p>
											<label class="selectit" for="comment_status">
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
								?>
							</div>
						</div>
						<p class="submit"><input type="submit" value="<?php _e(TXT_SET_ACCESS, "UserAccessManager"); ?>" name="submit" class="button"/></p>
					</form>
				</div>
				<?php
			}
			elseif($action == 'user_to_group')
			{
				$user_id = $_GET['id'];
				$accessgroups = $wpdb->get_results("SELECT *
													FROM ".DB_ACCESSGROUP."
													ORDER BY groupname", ARRAY_A);
				
				?>
				<div class=wrap>
					<form method="post" action="<?php echo reset(explode("?", $_SERVER["REQUEST_URI"]))."?page=".$_GET['page']; ?>">
						<input type="hidden" value="addgroup_to_user" name="action"/>
						<input type="hidden" value="<?php echo $user_id; ?>" name="user_id"/>
					 	<h2><?php _e(TXT_SET_USER_ACCESS, "UserAccessManager"); ?></h2>
						<div class="postbox">
							<h3><?php _e(TXT_GROUPS, "UserAccessManager"); ?></h3>
							<div class="inside">
								<?php
								if($accessgroups)
								{
									foreach($accessgroups as $accessgroup)
									{
										$checked = $wpdb->get_results("	SELECT *
																		FROM ".DB_ACCESSGROUP_TO_USER."
																		WHERE user_id = ".$user_id."
																			AND group_id = ".$accessgroup['ID'], ARRAY_A)
										?>
										<p>
											<label class="selectit" for="comment_status">
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
								?>
							</div>
						</div>
						<p class="submit"><input type="submit" value="<?php _e(TXT_SET_ACCESS, "UserAccessManager"); ?>" name="submit" class="button"/></p>
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
							</tbody>
						</table>
						<p class="submit"><input type="submit" value="<?php _e(TXT_UPDATE_GROUP, "UserAccessManager"); ?>" name="submit" class="button"/></p>
					</form>
				</div>
				<?php
			}
		}//End function printAdminPage()
		
		function check_access($post_id = null)
		{
			global $wpdb, $current_user;
			
			$cur_post =  & get_post($cur_id = $post_id);			
			
			$uamOptions = $this->getAdminOptions();
			
			if($uamOptions['lock_recursive'] == 'true')
			{
				$cur_id = $cur_post->ID;
				while($cur_id != 0)
				{
					$restricted_access = $wpdb->get_results("	SELECT *
																FROM ".DB_ACCESSGROUP_TO_POST."
																WHERE post_id = ".$cur_post->ID, ARRAY_A);
					if($restricted_access)
						break;
					else
						$cur_post = & get_post($cur_id = $cur_post->post_parent);
				}
			}
			elseif($uamOptions['lock_recursive'] == 'false')
			{
				if( $cur_post->post_type != 'attachment')
				{
					$restricted_access = $wpdb->get_results("	SELECT *
																FROM ".DB_ACCESSGROUP_TO_POST."
																WHERE post_id = ".$cur_post->ID, ARRAY_A);
				}
				else
				{
					$restricted_access = $wpdb->get_results("	SELECT *
																FROM ".DB_ACCESSGROUP_TO_POST."
																WHERE post_id = ".$cur_post->post_parent, ARRAY_A);
				}
			}
			
			if($restricted_access)
			{
				if (is_user_logged_in())
				{
					$cur_userdata = get_userdata($current_user->ID);
					
					if($cur_userdata->wp_capabilities['administrator'] != 1)
					{
						$user_access = $wpdb->get_results("	SELECT *
															FROM ".DB_ACCESSGROUP_TO_POST." atp, ".DB_ACCESSGROUP_TO_USER." atu
															WHERE atp.post_id = ".$cur_post->ID."
																AND atp.group_id = atu.group_id
																AND atu.user_id = ".$current_user->ID, ARRAY_A);
						
						if($user_access)
							return true;
						else
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
		
		function show_title($title = '')
		{
			$cur_post = & get_post($cur_id = null);
			
			$uamOptions = $this->getAdminOptions();
			
			// little hack nessesary because on single post it works on all
			if(!$this->check_access() && $title == $cur_post->post_title && $uamOptions['hide_title'] == 'true')
				$title = $uamOptions['post_title'];
			
			return $title;
		}
		
		function show_content($content = '')
		{
			$uamOptions = $this->getAdminOptions();
			
			if(!$this->check_access())
				$content = $uamOptions['post_content'];
			
			return $content;
		}
		
		function show_post($posts = array())
		{
			$no_posts = count($posts);
			
			for($i=0; $i < $no_posts; $i++)
			{
				if($this->check_access($posts[$i]->ID))
					$show_posts[] = $posts[$i];
			}
		
			$posts = $show_posts;
			
			return $posts;
		}
		
		function exclude_page($page)
		{
			$pages = get_pages();
			
			$excluded_pages = array(-1);
			
			foreach($pages as $page)
			{
				if(!$this->check_access($page->ID))
				{
					$excluded_pages[] = $page->ID;
				}
			}
			
			$page = $excluded_pages;
			
			return $page;
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
				$cur_post = & get_post($cur_id = $_GET['getfile']);
				
				if ($cur_post->post_type == 'attachment' && $this->check_access($cur_post->ID))
				{
					$allowed_ext = array (	// archives
										'zip' => 'application/zip',
										'rar' => 'application/rar',
										
										// documents
										'pdf' => 'application/pdf',
										'doc' => 'application/msword',
										'xls' => 'application/vnd.ms-excel',
										'ppt' => 'application/vnd.ms-powerpoint',
										  
										// executables
										'exe' => 'application/octet-stream',
										
										// images
										'gif' => 'image/gif',
										'png' => 'image/png',
										'jpg' => 'image/jpeg',
										'jpeg' => 'image/jpeg',
										
										// audio
										'mp3' => 'audio/mpeg',
										'wav' => 'audio/x-wav',
										
										// video
										'mpeg' => 'video/mpeg',
										'mpg' => 'video/mpeg',
										'mpe' => 'video/mpeg',
										'mov' => 'video/quicktime',
										'avi' => 'video/x-msvideo'
										);
					
					$file = str_replace("http://".$_SERVER['HTTP_HOST']."/", "", $cur_post->guid);
					$filename = basename($file);
					$fileext = strtolower(substr(strrchr($filename,"."),1));
					
					if (file_exists($file))
					{
						$len = filesize($file);
						header('content-type: '.$allowed_ext[$fileext]);
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
			$cur_post = & get_post($cur_id = $ID);
			$cur_parent = & get_post($parent_id = $cur_post->post_parent);
			if(strpos($cur_parent->guid, "?"))
				$char = "&";
			else
				$char = "?";

			$URL = $cur_parent->guid.$char.'getfile='.$cur_post->ID;
			
			return $URL;
		}
	}

} //End Class DevloungePluginSeries

if (class_exists("UserAccessManager"))
{
	$userAccessManager = new UserAccessManager();
}

//Initialize the admin panel
if (!function_exists("UserAccessManager_AP")) {
	function UserAccessManager_AP()
	{
		global $userAccessManager;
		if (!isset($userAccessManager))
		{
			return;
		}
		if (function_exists('add_menu_page'))
		{
			add_menu_page('User Access Manager', 'Access Manager', 9, 'uam_posts', array(&$userAccessManager, 'printAdminPage'));
		}
		if (function_exists('add_submenu_page'))
		{
			add_submenu_page('uam_posts', TXT_MANAGE_POST, TXT_MANAGE_POST, 9, 'uam_posts', array(&$userAccessManager, 'printAdminPage'));
			add_submenu_page('uam_posts', TXT_MANAGE_PAGE, TXT_MANAGE_PAGE, 9, 'uam_pages', array(&$userAccessManager, 'printAdminPage'));
			add_submenu_page('uam_posts', TXT_MANAGE_USER, TXT_MANAGE_USER, 9, 'uam_users', array(&$userAccessManager, 'printAdminPage'));
			add_submenu_page('uam_posts', TXT_MANAGE_GROUP, TXT_MANAGE_GROUP, 9, 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'));
			add_submenu_page('uam_posts', TXT_SETTING, TXT_SETTINGS, 9, 'uam_settings', array(&$userAccessManager, 'printAdminPage'));
		}
	}	
}

//Actions and Filters	
if (isset($userAccessManager)) 
{	
	$uamOptions = $userAccessManager->getAdminOptions();
	
	//install
	register_activation_hook(__FILE__, array(&$userAccessManager, 'install'));
	register_uninstall_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
	
	//Actions
	add_action('admin_menu', 'UserAccessManager_AP');
	
	if($uamOptions['redirect'] != 'false' || isset($_GET['getfile']))
		add_action('template_redirect', array(&$userAccessManager, 'redirect_user'), 1);
	
	//Filters
	add_filter('the_title', array(&$userAccessManager, 'show_title'), 1);
	add_filter('the_content', array(&$userAccessManager, 'show_content'), 1);
	add_filter('wp_get_attachment_url', array(&$userAccessManager, 'get_file'), 1, 2);
	
	if($uamOptions['hide_page'] == 'true')
		add_filter('wp_list_pages_excludes', array(&$userAccessManager, 'exclude_page'), 1);
		
	if($uamOptions['hide_post'] == 'true')
		add_filter('the_posts', array(&$userAccessManager, 'show_post'), 1);
}

?>