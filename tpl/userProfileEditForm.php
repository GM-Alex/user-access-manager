<?php
/**
 * userProfileEditForm.php
 * 
 * Shows the additional content for the user profile edit form.
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

global $userAccessManager, $wpdb;
$uamUserGroups 
    = &$userAccessManager->getAccessHandler()->getUserGroups();

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $editUserData = get_userdata($userId);
    
    $userGroupsForObject 
        = &$userAccessManager->getAccessHandler()->getUserGroupsForUser($userId);
} else {
    $userGroupsForObject = array();
}

?>
<h3><?php echo TXT_GROUPS; ?></h3>
<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="usergroups"><?php echo TXT_SET_UP_USERGROUPS; ?></label>
			</th>
			<td>
				<ul>
<?php
if (empty($editUserData->{$wpdb->prefix . "capabilities"}['administrator'])) {
    if (isset($uamUserGroups)) {
        foreach ($uamUserGroups as $uamUserGroup) {
            ?>
					<li>
						<label for="uam_usergroup-<?php echo $uamUserGroup->getId(); ?>" lass="selectit"> 
							<input type="checkbox" id="uam_usergroup-<?php echo $uamUserGroup->getId(); ?>"
            <?php
            if (array_key_exists($uamUserGroup->getId(), $userGroupsForObject)) {
                echo 'checked="checked"';
            }
        	if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive)) {
        		echo 'disabled=""';
        	} 
            ?>
							value="<?php echo $uamUserGroup->getId(); ?>" name="usergroups[]" /> 
						<?php echo $uamUserGroup->getGroupName(); ?>
						</label>
						<a class="uam_group_info_link">(<?php echo TXT_INFO; ?>)</a>
					
			<?php
            include 'groupInfo.php';
            ?>
            		</li>
            <?php
        }
    } else {
            ?>
            		<li>
            			<a href='admin.php?page=uam_usergroup'><?php echo TXT_CREATE_GROUP_FIRST; ?></a>
            		</li>
            <?php 
    }
} else {
    echo TXT_ADMIN_HINT;
}
?>
				</ul>
			</td>
		</tr>
	</tbody>
</table>