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
 * @copyright 2008-2010 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

if (isset($_GET['attachment_id'])) {
    $objectId = $_GET['attachment_id'];
} elseif (!isset($objectId)) {
    $objectId = 0;
}

$post = get_post($objectId);
$objectType = $post->post_type;

global $userAccessManager;

$uamUserGroups 
    = &$userAccessManager->getAccessHandler()->getUserGroups();

if (isset($objectId)) {
    $userGroupsForObject 
        = &$userAccessManager->getAccessHandler()->getUserGroupsForObject($objectType, $objectId);
} else {
    $userGroupsForObject = array();
}

if (count($uamUserGroups) > 0) {
	include 'groupSelectionForm.php';
} elseif ($userAccessManager->getAccessHandler()->checkUserAccess()) {
    ?>
	<a href='admin.php?page=uam_usergroup'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
	<?php
} else {
    echo TXT_UAM_NO_GROUP_AVAILABLE;
}