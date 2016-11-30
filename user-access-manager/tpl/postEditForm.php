<?php
/**
 * postEditFrom.php
 * 
 * Shows the setup page at the admin panel.
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

if (isset($_GET['attachment_id'])) {
    $iObjectId = $_GET['attachment_id'];
} elseif (!isset($iObjectId)) {
    $iObjectId = 0;
}

$oPost = get_post($iObjectId);
$sObjectType = $oPost->post_type;

global $oUserAccessManager;

$aUamUserGroups = $oUserAccessManager->getAccessHandler()->getUserGroups();

if (isset($iObjectId)) {
    $aUserGroupsForObject = $oUserAccessManager->getAccessHandler()->getUserGroupsForObject($sObjectType, $iObjectId);
} else {
    $aUserGroupsForObject = array();
}

if (count($aUamUserGroups) > 0) {
    include 'groupSelectionForm.php';
} elseif ($oUserAccessManager->getAccessHandler()->checkUserAccess()) {
    ?>
    <a href='admin.php?page=uam_usergroup'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
    <?php
} else {
    echo TXT_UAM_NO_GROUP_AVAILABLE;
}