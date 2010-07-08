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

if (isset($id)) {
    $postId = $id;
} else if (isset($_GET['attachment_id'])) {
    $postId = $_GET['attachment_id'];
}
$post = get_post($postId);

//Do we need this anymore?
/*if ($post->post_parent != 0
    && $post->post_type == 'post'
) {
    $postId = $post->post_parent;
} else {
    $postId = $post->ID;
}*/

global $userAccessManager;
$uamUserGroups 
    = &$userAccessManager->getAccessHandler()->getUserGroups();

if (isset($post->ID)) {
    $postId = $post->ID;
    
    $userGroupsForObject 
        = &$userAccessManager->getAccessHandler()->getUserGroupsForPost($postId);
} else {
    $userGroupsForObject = array();
}

if (isset($uamUserGroups)) {
	include 'groupSelectionForm.php';
} else {
    ?>
	<a href='admin.php?page=uam_usergroup'><?php echo TXT_CREATE_GROUP_FIRST; ?></a>
	<?php
}