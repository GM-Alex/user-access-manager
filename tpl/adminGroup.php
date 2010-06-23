<?php
/**
 * adminGroup.php
 * 
 * Shows the groupmanagement page at the admin panel
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

function getPrintEditGroup($group_id = null)
{
    global $wpdb;
    $userAccessManager = new UserAccessManager();
    $uamOptions = $userAccessManager->getAdminOptions();
    
    if (isset($group_id)) {
        $group_info = $userAccessManager->get_usergroup_info($group_id);
    }
    ?>
	<form method="post" action="<?php echo reset(explode("?", $_SERVER["REQUEST_URI"])) . "?page=" . $_GET['page']; ?>">
		<input type="hidden" id="TXT_COLLAPS" name="deleteit" value="<?php echo TXT_COLLAPS; ?>" /> 
		<input type="hidden" id="TXT_EXPAND" name="deleteit" value="<?php echo TXT_EXPAND; ?>" /> 
	<?php
    if (isset($group_id)) {
        ?> 
    	<input type="hidden" value="update_group" name="action" /> 
    	<input type="hidden" value="<?php echo $group_id; ?>" name="access_group_id" />
		<?php
    } else {
        ?> 
    	<input type="hidden" value="addgroup" name="action" /> 
        <?php
    }
    ?> 
       	<input type="hidden" value="<?php echo $uamOptions['lock_recursive']; ?>" name="uam_lock_recursive" id="uam_set_lock_recursive" />
    	<table class="form-table">
    		<tbody>
    			<tr class="form-field form-required">
    				<th valign="top" scope="row"><?php echo TXT_GROUP_NAME; ?></th>
    				<td>
    					<input type="text" size="40" value="<?php
    if (isset($group_id)) {
        echo $group_info->group["groupname"];
    } 
                        ?>" id="access_group_name" name="access_group_name" /><br />
		                <?php echo TXT_GROUP_NAME_DESC; ?>
		        	</td>
				</tr>
            	<tr class="form-field form-required">
            		<th valign="top" scope="row"><?php echo TXT_GROUP_DESC; ?></th>
            		<td>
            			<input type="text" size="40" value="<?php 
    if (isset($group_id)) { 
        echo $group_info->group["groupdesc"]; 
    } 
                        ?>" id="access_group_description" name="access_group_description" /><br />
            		    <?php echo TXT_GROUP_DESC_DESC; ?>
            		</td>
            	</tr>
				<tr class="form-field form-required">
                	<th valign="top" scope="row"><?php echo TXT_GROUP_IP_RANGE; ?></th>
                	<td><input type="text" size="40" value="<?php
    if (isset($group_id)) {
        echo $group_info->group["ip_range"];
    } 
                        ?>" id="ip_range" name="ip_range" /><br />
                		<?php echo TXT_GROUP_IP_RANGE_DESC; ?>
                	</td>
                </tr>
                <tr class="form-field form-required">
                	<th valign="top" scope="row"><?php echo TXT_GROUP_READ_ACCESS; ?></th>
                	<td>
                		<select name="read_access">
                			<option value="group"
	<?php
    if (isset($group_id)) {
        if ($group_info->group["read_access"] == "group") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    						    <?php echo TXT_ONLY_GROUP_USERS ?>
    						</option>
							<option value="all"
	<?php
    if (isset($group_id)) {
        if ($group_info->group["read_access"] == "all") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    						    <?php echo TXT_ALL ?>
    						</option>
						</select><br />
	                    <?php echo TXT_GROUP_READ_ACCESS_DESC; ?>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th valign="top" scope="row"><?php echo TXT_GROUP_WRITE_ACCESS; ?></th>
					<td>
						<select name="write_access">
							<option value="group"
	<?php
    if (isset($group_id)) {
        if ($group_info->group["write_access"] == "group") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    					        <?php echo TXT_ONLY_GROUP_USERS ?>
        					</option>
    						<option value="all" 
	<?php 
    if (isset($group_id)) {
        if ($group_info->group["write_access"] == "all") {
            echo 'selected="selected"';
        }
    } 
    ?>
    					>
        					    <?php echo TXT_ALL ?>
        					</option>
						</select><br />
	                    <?php echo TXT_GROUP_WRITE_ACCESS_DESC; ?>
	            	</td>
				</tr>
				<tr>
					<th valign="top" scope="row"><?php echo TXT_GROUP_ROLE; ?></th>
					<td>
						<ul class='uam_role'>
	<?php
    global $wp_roles;
    foreach ($wp_roles->role_names as $role => $name) {
        if ($role != "administrator") {
            ?>
							<li class="selectit">
								<input id="role-<?php echo $role; ?>" type="checkbox"
			<?php
            if (isset($group_info->posts[$role])) {
                echo 'checked="checked"';
            } 
            ?>
			
								value="<?php echo $role; ?> " name="roles[]" /> 
                				<label for="role-<?php echo $role; ?>">
                			        <?php echo $role; ?>
                				</label>
							</li>
		<?php
        }
    }
    ?>
						</ul>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" value="<?php
    if (isset($group_id)) {
        echo TXT_UPDATE_GROUP;
    } else {
        echo TXT_ADD_GROUP;
    } 
            ?>" name="submit" class="button" />
		</p>
	</form>
    <?php
}

global $wpdb;

if (isset($_POST['action'])) {
    $post_action = $_POST['action'];
} else {
    $post_action = null;
}

if ($post_action == 'delgroup') {
    if (isset($_POST['delete'])) {
        $del_ids = $_POST['delete'];
    }
    if (isset($del_ids)) {
        foreach ($del_ids as $del_id) {
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP . " 
            	WHERE ID = $del_id LIMIT 1"
            );
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
            	WHERE group_id = $del_id"
            );
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
            	WHERE group_id = $del_id"
            );
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " 
            	WHERE group_id = $del_id"
            );
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_ROLE . " 
            	WHERE group_id = $del_id"
            );
        }
        ?>
        <div class="updated">
        	<p><strong><?php echo TXT_DEL_GROUP; ?></strong></p>
        </div>
        <?php
    }
}

if ($post_action == 'update_group' || $post_action == 'addgroup') {
    if ($post_action == 'addgroup') {
        $wpdb->query(
        	"INSERT INTO " . DB_ACCESSGROUP . " (
        		ID, 
        		groupname, 
        		groupdesc, 
        		read_access, 
        		write_access, 
        		ip_range
        	) 
        	VALUES (
        		NULL, 
        		'" . $_POST['access_group_name'] . "', 
        		'" . $_POST['access_group_description'] . "', 
        		'" . $_POST['read_access'] . "', 
        		'" . $_POST['write_access'] . "', 
        		'" . $_POST['ip_range'] . "'
        	)"
        );
        $group_id = $wpdb->insert_id;
    } elseif ($post_action == 'update_group') {
        $wpdb->query(
        	"UPDATE " . DB_ACCESSGROUP . "
			SET groupname = '" . $_POST['access_group_name'] . "', 
				groupdesc = '" . $_POST['access_group_description'] . "', 
				read_access = '" . $_POST['read_access'] . "', 
				write_access = '" . $_POST['write_access'] . "', 
				ip_range = '" . $_POST['ip_range'] . "'
			WHERE ID = " . $_POST['access_group_id']
        );
        
        $group_id = $_POST['access_group_id'];
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_ROLE . " 
        	WHERE group_id = " . $group_id
        );
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
        	WHERE group_id = " . $group_id
        );
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " 
        	WHERE group_id = " . $group_id
        );
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
        	WHERE group_id = " . $group_id
        );
    }
    
    if (isset($_POST['roles'])) {
        $roles = $_POST['roles'];
    } else {
        $roles = null;
    }
    
    if ($roles) {
        foreach ($roles as $role) {
            $wpdb->query(
            	"INSERT INTO " . DB_ACCESSGROUP_TO_ROLE . " (
            		group_id, 
            		role_name
            	) 
            	VALUES(
            		'" . $group_id . "', 
            		'" . $role . "'
            	)"
            );
        }
    }
    
    if ($post_action == 'addgroup') {
        ?>
        <div class="updated">
        	<p><strong><?php echo TXT_GROUP_ADDED; ?></strong></p>
        </div>
        <?php
    } elseif ($post_action == 'update_group') {
        ?>
        <div class="updated">
        	<p><strong><?php echo TXT_ACCESS_GROUP_EDIT_SUC; ?></strong></p>
        </div>
        <?php
    }
}

$accessgroups = $wpdb->get_results(
	"SELECT *
	FROM " . DB_ACCESSGROUP . "
	ORDER BY ID", ARRAY_A
);
?>
<div class=wrap>
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>"><input type="hidden" value="delgroup" name="action" />
        <h2><?php echo TXT_MANAGE_GROUP; ?></h2>
        <div class="tablenav">
            <div class="alignleft">
            	<input type="submit" class="button-secondary delete" name="deleteit" value="<?php echo TXT_DELETE; ?>" /> 
            	<input type="hidden" id="TXT_COLLAPS_ALL" name="deleteit" value="<?php echo TXT_COLLAPS_ALL; ?>" /> 
            	<input type="hidden" id="TXT_EXPAND_ALL" name="deleteit" value="<?php echo TXT_EXPAND_ALL; ?>" />
            </div>
        	<br class="clear" />
        </div>
        <br class="clear" />
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
if (isset($accessgroups)) {
    foreach ($accessgroups as $accessgroup) {
        $group_info = $this->get_usergroup_info($accessgroup['ID']);
        ?>
    		<tr class="alternate" id="group-<?php echo $accessgroup['ID']; ?>">
    			<th class="check-column" scope="row">
    				<input type="checkbox" value="<?php echo $accessgroup['ID']; ?>" name="delete[]" />
    			</th>
    			<td>
    				<strong>
    					<a href="?page=<?php echo $cur_admin_page; ?>&action=edit_group&id=<?php echo $accessgroup['ID']; ?>">
    					    <?php echo $accessgroup['groupname']; ?>
    					</a>
    				</strong>
    			</td>
    			<td><?php echo $accessgroup['groupdesc']; ?></td>
    			<td>
        <?php 
        if ($accessgroup['read_access'] == "all") {
            echo TXT_ALL;
        } elseif ($accessgroup['read_access'] == "group") {
            echo TXT_ONLY_GROUP_USERS;
        } 
        ?>
                </td>
    			<td>
		<?php
        if ($accessgroup['write_access'] == "all") {
            echo TXT_ALL;
        } elseif ($accessgroup['write_access'] == "group") {
            echo TXT_ONLY_GROUP_USERS;
        } 
        ?>
            	</td>
    			<td>
		<?php
        if (isset($accessgroup['ip_range'])) {
            echo $accessgroup['ip_range'];
        } else {
            echo TXT_NONE;
        }
        ?>
            	</td>
    			<td>
		<?php
        if (isset($group_info->posts)) {
            $expandcontent = null;
            foreach ($group_info->posts as $post) {
                $expandcontent.= "<li>" . $post->post_title . "</li>";
            }
            echo "<a class='uam_info_link'>" . count($group_info->posts) . " " . TXT_POSTS . "</a>";
            echo "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul>";
        } else {
            echo TXT_NONE;
        }
        ?>
    			</td>
    			<td>
		<?php
        if (isset($group_info->pages)) {
            $expandcontent = null;
            foreach ($group_info->pages as $page) {
                $expandcontent.= "<li>" . $page->post_title . "</li>";
            }
            echo "<a class='uam_info_link'>" . count($group_info->pages) . " " . TXT_PAGES . "</a>";
            echo "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul>";
        } else {
            echo TXT_NONE;
        }
        ?>
            	</td>
    			<td>
		<?php
        if (isset($group_info->files)) {
            $expandcontent = null;
            foreach ($group_info->files as $file) {
                $expandcontent.= "<li>" . $file->post_title . "</li>";
            }
            echo "<a class='uam_info_link'>" . count($group_info->files) . " " . TXT_FILES . "</a>";
            echo "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul>";
        } else {
            echo TXT_NONE;
        }
        ?>
                	</td>
        			<td>
		<?php
        if (isset($group_info->categories)) {
            $expandcontent = null;
            foreach ($group_info->categories as $categorie) {
                $expandcontent.= "<li>" . $categorie->cat_name . "</li>";
            }
            echo "<a class='uam_info_link'>" . count($group_info->categories) . " " . TXT_CATEGORY . "</a>";
            echo "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul>";
        } else {
            echo TXT_NONE;
        }
        ?>
        			</td>
        			<td>
		<?php
        if (isset($group_info->users)) {
            $expandcontent = null;
            foreach ($group_info->users as $user) {
                $expandcontent.= "<li>" . $user->nickname . "</li>";
            }
            echo "<a class='uam_info_link'>" . count($group_info->users) . " " . TXT_USERS . "</a>";
            echo "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul>";
        } else {
            echo TXT_NONE;
        }
        ?>
                	</td>
        			<td>
        				<a class="uam_info_link_all" href="#">
        				    <?php echo TXT_EXPAND_ALL; ?>
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
<div class="wrap">
    <h2><?php echo TXT_ADD_GROUP; ?></h2>
<?php
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = null;
}

if ($action == 'edit_group' && isset($_GET['id'])) {
    $group_id = $_GET['id'];    
    getPrintEditGroup($group_id);
} else {
    getPrintEditGroup();
}
?>
</div>
