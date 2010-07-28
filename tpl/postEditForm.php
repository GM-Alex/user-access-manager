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

if (isset($post->ID)) {
    $objectId = $post->ID;
} elseif (isset($id)) {
    $objectId = $id;
} elseif (isset($_GET['attachment_id'])) {
    $objectId = $_GET['attachment_id'];
} else {
    $objectId = 0;
}

$post = get_post($objectId);

global $userAccessManager;

$uamUserGroups 
    = &$userAccessManager->getAccessHandler()->getUserGroups();

if (isset($post->ID)) {
    $userGroupsForObject 
        = &$userAccessManager->getAccessHandler()->getUserGroupsForPost($post->ID);
} else {
    $userGroupsForObject = array();
}

if (count($uamUserGroups) > 0) {
    $type = 'post';
	include 'groupSelectionForm.php';
} elseif ($userAccessManager->getAccessHandler()->checkUserAccess()) {
    ?>
	<a href='admin.php?page=uam_usergroup'><?php echo TXT_CREATE_GROUP_FIRST; ?></a>
	<?php
} else {
    echo TXT_NO_GROUP_AVAILABLE;
}