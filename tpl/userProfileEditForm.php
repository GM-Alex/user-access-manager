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
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

global $oUserAccessManager, $wpdb;
$aUamUserGroups = $oUserAccessManager->getAccessHandler()->getUserGroups();
    
$sObjectType = 'user';

if (isset($_GET['user_id'])) {
    $iObjectId = $_GET['user_id'];
    $oEditUserData = get_userdata($iObjectId);
    
    $aUserGroupsForObject = $oUserAccessManager->getAccessHandler()->getUserGroupsForObject(
        $sObjectType,
        $iObjectId
    );
} else {
    $aUserGroupsForObject = array();
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
if (!$oUserAccessManager->getAccessHandler()->userIsAdmin($iObjectId)) {
    include 'groupSelectionForm.php';
} else {
    echo TXT_UAM_ADMIN_HINT;
}
?>
            </td>
        </tr>
    </tbody>
</table>