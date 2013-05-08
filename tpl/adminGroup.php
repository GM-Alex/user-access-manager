<?php
/**
 * adminGroup.php
 * 
 * Shows the group management page at the admin panel.
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
 * Inserts or update a user group.
 * 
 * @param integer $iUserGroupId The _iId of the user group.
 * 
 * @return null
 */
function insertUpdateGroup($iUserGroupId)
{
    global $oUserAccessManager;
    
    if (empty($_POST) || !wp_verify_nonce($_POST['uamInsertUpdateGroupNonce'], 'uamInsertUpdateGroup')) {
         wp_die(TXT_UAM_NONCE_FAILURE);
    }
    
    if ($iUserGroupId != null) {
        $uamUserGroup = $oUserAccessManager->getAccessHandler()->getUserGroups($iUserGroupId);
    } else {
        $uamUserGroup = new UamUserGroup($oUserAccessManager->getAccessHandler(), null);
    }

    $uamUserGroup->setGroupName($_POST['userGroupName']);
	$uamUserGroup->setGroupDesc($_POST['userGroupDescription']);
	$uamUserGroup->setReadAccess($_POST['readAccess']);
	$uamUserGroup->setWriteAccess($_POST['writeAccess']);
	$uamUserGroup->setIpRange($_POST['ipRange']);
        
    if (isset($_POST['roles'])) {
        $roles = $_POST['roles'];
    } else {
        $roles = null;
    }

    $uamUserGroup->unsetObjects('role', true);
    
    if ($roles) {
        foreach ($roles as $role) {
            $uamUserGroup->addObject('role', $role);
        }
    }
    
    $uamUserGroup->save();
    
    $oUserAccessManager->getAccessHandler()->addUserGroup($uamUserGroup);
}

/**
 * Prints the group formular.
 * 
 * @param integer $groupId The given group _iId.
 * 
 * @return null
 */
function getPrintEditGroup($groupId = null)
{
    global $oUserAccessManager;
    $uamUserGroup = $oUserAccessManager->getAccessHandler()->getUserGroups($groupId);
    ?>
	<form method="post" action="<?php 
	    echo reset(
	        explode("?", $_SERVER["REQUEST_URI"])
	    ) . "?page=" . $_GET['page']; 
	?>">
	<?php
	wp_nonce_field('uamInsertUpdateGroup', 'uamInsertUpdateGroupNonce');
	
    if (isset($groupId)) {
        ?> 
    	<input type="hidden" value="updateGroup" name="action" /> 
    	<input type="hidden" value="<?php echo $groupId; ?>" name="userGroupId" />
		<?php
    } else {
        ?> 
    	<input type="hidden" value="addGroup" name="action" /> 
        <?php
    }
    ?>
    	<table class="form-table">
    		<tbody>
    			<tr class="form-field form-required">
    				<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_NAME; ?></th>
    				<td>
    					<input type="text" size="40" value="<?php
    if (isset($groupId)) {
        echo $uamUserGroup->getGroupName();
    } 
                        ?>" id="userGroupName" name="userGroupName" /><br />
		                <?php echo TXT_UAM_GROUP_NAME_DESC; ?>
		        	</td>
				</tr>
            	<tr class="form-field form-required">
            		<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_DESC; ?></th>
            		<td>
            			<input type="text" size="40" value="<?php 
    if (isset($groupId)) { 
        echo $uamUserGroup->getGroupDesc(); 
    } 
                        ?>" id="userGroupDescription" name="userGroupDescription" /><br />
            		    <?php echo TXT_UAM_GROUP_DESC_DESC; ?>
            		</td>
            	</tr>
				<tr class="form-field form-required">
                	<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_IP_RANGE; ?></th>
                	<td><input type="text" size="40" value="<?php
    if (isset($groupId)) {
        echo $uamUserGroup->getIpRange('string');
    } 
                        ?>" id="ipRange" name="ipRange" /><br />
                		<?php echo TXT_UAM_GROUP_IP_RANGE_DESC; ?>
                	</td>
                </tr>
                <tr class="form-field form-required">
                	<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_READ_ACCESS; ?></th>
                	<td>
                		<select name="readAccess">
                			<option value="group"
	<?php
    if (isset($groupId)) {
        if ($uamUserGroup->getReadAccess() == "group") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    						    <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
    						</option>
							<option value="all"
	<?php
    if (isset($groupId)) {
        if ($uamUserGroup->getReadAccess() == "all") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    						    <?php echo TXT_UAM_ALL ?>
    						</option>
						</select><br />
	                    <?php echo TXT_UAM_GROUP_READ_ACCESS_DESC; ?>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_WRITE_ACCESS; ?></th>
					<td>
						<select name="writeAccess">
							<option value="group"
	<?php
    if (isset($groupId)) {
        if ($uamUserGroup->getWriteAccess() == "group") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    					        <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
        					</option>
    						<option value="all" 
	<?php 
    if (isset($groupId)) {
        if ($uamUserGroup->getWriteAccess() == "all") {
            echo 'selected="selected"';
        }
    } 
    ?>
    					>
        					    <?php echo TXT_UAM_ALL ?>
        					</option>
						</select><br />
	                    <?php echo TXT_UAM_GROUP_WRITE_ACCESS_DESC; ?>
	            	</td>
				</tr>
				<tr>
					<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_ROLE; ?></th>
					<td>
						<ul class='uam_role'>
	<?php
    global $wp_roles;
    
    if (isset($groupId)) {
        $groupRoles = $uamUserGroup->getObjectsFromType('role');
    }
    
    foreach ($wp_roles->role_names as $role => $name) {
        if ($role != "administrator") {
            ?>
							<li class="selectit">
								<input id="role-<?php echo $role; ?>" type="checkbox"
			<?php
			
            if (isset($groupRoles[$role])) {
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
    if (isset($groupId)) {
        echo TXT_UAM_UPDATE_GROUP;
    } else {
        echo TXT_UAM_ADD_GROUP;
    } 
            ?>" name="submit" class="button" />
		</p>
	</form>
    <?php
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = null;
}

if ($action == 'editGroup' && isset($_GET['id'])) {
    $editGroup = true;
} else {
    $editGroup = false;
}

if (isset($_POST['action'])) {
    $postAction = $_POST['action'];
} else {
    $postAction = null;
}

if ($postAction == 'delgroup') {
    if (empty($_POST) 
        || !wp_verify_nonce($_POST['uamDeleteGroupNonce'], 'uamDeleteGroup')
    ) {
         wp_die(TXT_UAM_NONCE_FAILURE);
    }
    
    if (isset($_POST['delete'])) {
        $delIds = $_POST['delete'];
    }

    if (isset($delIds)) {
        global $oUserAccessManager;
        
        foreach ($delIds as $delId) {
            $oUserAccessManager->getAccessHandler()->deleteUserGroup($delId);
        }
        ?>
        <div class="updated">
        	<p><strong><?php echo TXT_UAM_DEL_GROUP; ?></strong></p>
        </div>
        <?php
    }
}

if (($postAction == 'updateGroup' || $postAction == 'addGroup') 
    && !empty($_POST['userGroupName'])
) {
    if (!isset($_POST['userGroupId'])) {
        $_POST['userGroupId'] = null;
    }
    
    insertUpdateGroup($_POST['userGroupId']);
    
    if ($postAction == 'addGroup') {
        ?>
        <div class="updated">
        	<p><strong><?php echo TXT_UAM_GROUP_ADDED; ?></strong></p>
        </div>
        <?php
    } elseif ($postAction == 'updateGroup') {
        ?>
        <div class="updated">
        	<p><strong><?php echo TXT_UAM_ACCESS_GROUP_EDIT_SUC; ?></strong></p>
        </div>
        <?php
    }
} elseif (($postAction == 'updateGroup' || $postAction == 'addGroup') 
         && empty($_POST['userGroupName'])) {
    ?>
    <div class="error">
    	<p><strong><?php echo TXT_UAM_GROUP_NAME_ERROR; ?></strong></p>
    </div>
    <?php
}

if (!$editGroup) {
    ?>
    <div class="wrap">
        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
            <?php wp_nonce_field('uamDeleteGroup', 'uamDeleteGroupNonce'); ?>
        	<input type="hidden" value="delgroup" name="action" />
            <h2><?php echo TXT_UAM_MANAGE_GROUP; ?></h2>
            <div class="tablenav">
                <div class="alignleft">
                	<input type="submit" class="button-secondary delete" name="deleteit" value="<?php echo TXT_UAM_DELETE; ?>" /> 
                </div>
            	<br class="clear" />
            </div>
            <br class="clear" />
            <table class="widefat">
            	<thead>
            		<tr class="thead">
            			<th scope="col"></th>
            			<th scope="col"><?php echo TXT_UAM_NAME; ?></th>
            			<th scope="col"><?php echo TXT_UAM_DESCRIPTION; ?></th>
            			<th scope="col"><?php echo TXT_UAM_READ_ACCESS; ?></th>
            			<th scope="col"><?php echo TXT_UAM_WRITE_ACCESS; ?></th>
            			<th scope="col"><?php echo TXT_UAM_GROUP_ROLE; ?></th>
            			<th scope="col"><?php echo TXT_UAM_IP_RANGE; ?></th>
            		</tr>
            	</thead>
        	<tbody>
    <?php
    if (isset($_GET['page'])) {
        $curAdminPage = $_GET['page'];
    }
    
    global $oUserAccessManager;
    $aUamUserGroups = $oUserAccessManager->getAccessHandler()->getUserGroups();
    
    if (isset($aUamUserGroups)) {
        foreach ($aUamUserGroups as $uamUserGroup) {
            ?>
        		<tr class="alternate" id="group-<?php echo $uamUserGroup->getId(); ?>">
        			<th class="check-column" scope="row">
        				<input type="checkbox" value="<?php echo $uamUserGroup->getId(); ?>" name="delete[]" />
        			</th>
        			<td>
        				<strong>
        					<a href="?page=<?php echo $curAdminPage; ?>&amp;action=editGroup&amp;id=<?php echo $uamUserGroup->getId(); ?>">
        					    <?php echo $uamUserGroup->getGroupName(); ?>
        					</a>
        				</strong>
        			</td>
        			<td><?php echo $uamUserGroup->getGroupDesc() ?></td>
        			<td>
            <?php 
            if ($uamUserGroup->getReadAccess() == "all") {
                echo TXT_UAM_ALL;
            } elseif ($uamUserGroup->getReadAccess() == "group") {
                echo TXT_UAM_ONLY_GROUP_USERS;
            } 
            ?>
                    </td>
        			<td>
    		<?php
            if ($uamUserGroup->getWriteAccess() == "all") {
                echo TXT_UAM_ALL;
            } elseif ($uamUserGroup->getWriteAccess() == "group") {
                echo TXT_UAM_ONLY_GROUP_USERS;
            } 
            ?>
                	</td>
                	        			<td>
    		<?php
            if ($uamUserGroup->getObjectsFromType('role')) {
                ?>
                		<ul>
                <?php
                foreach ($uamUserGroup->getObjectsFromType('role') as $key => $role) {
                    ?>
                			<li>
                    <?php
                    echo $key;
                    ?>
                			</li>
                    <?php
                }
                ?>
                		</ul>
                <?php
            } else {
                echo TXT_UAM_NONE;
            }
            ?>
                	</td>
        			<td>
    		<?php
            if ($uamUserGroup->getIpRange()) {
                ?>
                		<ul>
                <?php
                foreach ($uamUserGroup->getIpRange() as $ipRange) {
                    ?>
                			<li>
                    <?php
                    echo $ipRange;
                    ?>
                			</li>
                    <?php
                }
                ?>
                		</ul>
                <?php
            } else {
                echo TXT_UAM_NONE;
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
    <?php 
}
?>
<div class="wrap">
    <h2>
<?php


if ($editGroup) {
    echo TXT_UAM_EDIT_GROUP;
} else {
    echo TXT_UAM_ADD_GROUP;
}
?>
	</h2>
<?php 
if ($editGroup) {
    $groupId = $_GET['id'];    
    getPrintEditGroup($groupId);
} else {
    getPrintEditGroup();
}
?>
</div>