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
$uamUserGroups = &$userAccessManager->getAccessHandler()->getUserGroups();
    
$objectType = 'user';

if (isset($_GET['user_id'])) {
    $objectId = $_GET['user_id'];
    $editUserData = get_userdata($objectId);
    
    $userGroupsForObject = &$userAccessManager->getAccessHandler()->getUserGroupsForObject(
        $objectType, 
        $objectId
    );
} else {
    $userGroupsForObject = array();
}

?>
<h3><?php echo TXT_UAM_GROUPS; ?></h3>
<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="usergroups"><?php echo TXT_UAM_SET_UP_USERGROUPS; ?></label>
			</th>
			<td>
<?php
if (!$userAccessManager->getAccessHandler()->userIsAdmin($objectId)) {
    include 'groupSelectionForm.php';
} else {
    echo TXT_UAM_ADMIN_HINT;
}
?>
			</td>
		</tr>
	</tbody>
</table>