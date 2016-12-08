<?php
/**
 * categoryEditForm.php
 * 
 * Shows the setup page at the admin panel.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

global $oUserAccessManager;
$aUamUserGroups = $oUserAccessManager->getAccessHandler()->getUserGroups();

$iObjectId = null;
$sObjectType = 'category';

if (isset($_GET['tag_ID'])) {
    $iObjectId = $_GET['tag_ID'];
    
    $aUserGroupsForObject = $oUserAccessManager->getAccessHandler()->getUserGroupsForObject(
        $sObjectType,
        $iObjectId
    );
} else {
    $aUserGroupsForObject = array();
}
    
?>
<table class="form-table">
    <tbody>
        <tr>
            <th>
                <label for="description"><?php echo TXT_UAM_SET_UP_USERGROUPS; ?></label>
            </th>
            <td>
<?php
if (count($aUamUserGroups) > 0) {
    include 'groupSelectionForm.php';
} elseif ($oUserAccessManager->getAccessHandler()->checkUserAccess()) {
    ?>
    <a href='admin.php?page=uam_usergroup'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
    <?php
} else {
    echo TXT_UAM_NO_GROUP_AVAILABLE;
}
?>
            </td>
        </tr>
    </tbody>
</table>