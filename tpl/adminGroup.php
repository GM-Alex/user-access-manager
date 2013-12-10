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
        $oUamUserGroup = $oUserAccessManager->getAccessHandler()->getUserGroups($iUserGroupId);
    } else {
        $oUamUserGroup = new UamUserGroup($oUserAccessManager->getAccessHandler(), null);
    }

    $oUamUserGroup->setGroupName($_POST['userGroupName']);
	$oUamUserGroup->setGroupDesc($_POST['userGroupDescription']);
	$oUamUserGroup->setReadAccess($_POST['readAccess']);
	$oUamUserGroup->setWriteAccess($_POST['writeAccess']);
	$oUamUserGroup->setIpRange($_POST['ipRange']);
        
    if (isset($_POST['roles'])) {
        $aRoles = $_POST['roles'];
    } else {
        $aRoles = null;
    }

    $oUamUserGroup->unsetObjects('role', true);
    
    if ($aRoles) {
        foreach ($aRoles as $role) {
            $oUamUserGroup->addObject('role', $role);
        }
    }
    
    $oUamUserGroup->save();
    
    $oUserAccessManager->getAccessHandler()->addUserGroup($oUamUserGroup);
}

/**
 * Prints the group form.
 * 
 * @param integer $sGroupId The given group _iId.
 * 
 * @return null
 */
function getPrintEditGroup($sGroupId = null)
{
    global $oUserAccessManager;
    $oUamUserGroup = $oUserAccessManager->getAccessHandler()->getUserGroups($sGroupId);
    ?>
	<form method="post" action="<?php 
	    echo reset(
	        explode("?", $_SERVER["REQUEST_URI"])
	    ) . "?page=" . $_GET['page']; 
	?>">
	<?php
	wp_nonce_field('uamInsertUpdateGroup', 'uamInsertUpdateGroupNonce');
	
    if (isset($sGroupId)) {
        ?> 
    	<input type="hidden" value="updateGroup" name="action" /> 
    	<input type="hidden" value="<?php echo $sGroupId; ?>" name="userGroupId" />
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
    if (isset($sGroupId)) {
        echo $oUamUserGroup->getGroupName();
    } 
                        ?>" id="userGroupName" name="userGroupName" /><br />
		                <?php echo TXT_UAM_GROUP_NAME_DESC; ?>
		        	</td>
				</tr>
            	<tr class="form-field form-required">
            		<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_DESC; ?></th>
            		<td>
            			<input type="text" size="40" value="<?php 
    if (isset($sGroupId)) {
        echo $oUamUserGroup->getGroupDesc();
    } 
                        ?>" id="userGroupDescription" name="userGroupDescription" /><br />
            		    <?php echo TXT_UAM_GROUP_DESC_DESC; ?>
            		</td>
            	</tr>
				<tr class="form-field form-required">
                	<th valign="top" scope="row"><?php echo TXT_UAM_GROUP_IP_RANGE; ?></th>
                	<td><input type="text" size="40" value="<?php
    if (isset($sGroupId)) {
        echo $oUamUserGroup->getIpRange('string');
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
    if (isset($sGroupId)) {
        if ($oUamUserGroup->getReadAccess() == "group") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    						    <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
    						</option>
							<option value="all"
	<?php
    if (isset($sGroupId)) {
        if ($oUamUserGroup->getReadAccess() == "all") {
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
    if (isset($sGroupId)) {
        if ($oUamUserGroup->getWriteAccess() == "group") {
            echo 'selected="selected"';
        }
    } 
    ?>
    						>
    					        <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
        					</option>
    						<option value="all" 
	<?php 
    if (isset($sGroupId)) {
        if ($oUamUserGroup->getWriteAccess() == "all") {
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
    
    if (isset($sGroupId)) {
        $aGroupRoles = $oUamUserGroup->getObjectsFromType('role');
    }
    
    foreach ($wp_roles->role_names as $role => $name) {
        if ($role != "administrator") {
            ?>
							<li class="selectit">
								<input id="role-<?php echo $role; ?>" type="checkbox"
			<?php
			
            if (isset($aGroupRoles[$role])) {
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
    if (isset($sGroupId)) {
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
    $sCurAdminPage = isset($_GET['page']) ? $_GET['page'] : '';
    
    global $oUserAccessManager;
    $aUamUserGroups = $oUserAccessManager->getAccessHandler()->getUserGroups();
    
    if (isset($aUamUserGroups)) {
        foreach ($aUamUserGroups as $oUamUserGroup) {
            ?>
        		<tr class="alternate" id="group-<?php echo $oUamUserGroup->getId(); ?>">
        			<th class="check-column" scope="row">
        				<input type="checkbox" value="<?php echo $oUamUserGroup->getId(); ?>" name="delete[]" />
        			</th>
        			<td>
        				<strong>
        					<a href="?page=<?php echo $sCurAdminPage; ?>&amp;action=editGroup&amp;id=<?php echo $oUamUserGroup->getId(); ?>">
        					    <?php echo $oUamUserGroup->getGroupName(); ?>
        					</a>
        				</strong>
        			</td>
        			<td><?php echo $oUamUserGroup->getGroupDesc() ?></td>
        			<td>
            <?php 
            if ($oUamUserGroup->getReadAccess() == "all") {
                echo TXT_UAM_ALL;
            } elseif ($oUamUserGroup->getReadAccess() == "group") {
                echo TXT_UAM_ONLY_GROUP_USERS;
            } 
            ?>
                    </td>
        			<td>
    		<?php
            if ($oUamUserGroup->getWriteAccess() == "all") {
                echo TXT_UAM_ALL;
            } elseif ($oUamUserGroup->getWriteAccess() == "group") {
                echo TXT_UAM_ONLY_GROUP_USERS;
            } 
            ?>
                	</td>
                	        			<td>
    		<?php
            if ($oUamUserGroup->getObjectsFromType('role')) {
                ?>
                		<ul>
                <?php
                foreach ($oUamUserGroup->getObjectsFromType('role') as $sKey => $sRole) {
                    ?>
                			<li>
                    <?php
                    echo $sKey;
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
            if ($oUamUserGroup->getIpRange()) {
                ?>
                		<ul>
                <?php
                foreach ($oUamUserGroup->getIpRange() as $sIpRange) {
                    ?>
                			<li>
                    <?php
                    echo $sIpRange;
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