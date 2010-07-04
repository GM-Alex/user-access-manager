<?php
/**
* groupInfo.php
* 
* Shows the group informations at the admim panel.
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
?>
<div class="tooltip">
<ul class="uam_group_info">
<?php 
if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive['byCategory'])) {
    ?>
	<li  class="uam_group_info_head">
		<?php echo TXT_GROUP_MEMBERSHIP_BY_CATEGORIES; ?>:
		<ul>
	<?php
	foreach ($userGroupsForObject[$uamUserGroup->getId()]->setRecursive['byCategory'] as $categoryId) {
	    $category = get_category($categoryId);
	    ?>
	    	<li><?php echo $category->name; ?></li>
	    <?php
	}
	?>
		</ul>
	</li>
    <?php 
}
?>
<?php 
if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive['byPost'])) {
    ?>
	<li  class="uam_group_info_head">
		<?php echo TXT_GROUP_MEMBERSHIP_BY_POSTS; ?>:
		<ul>
	<?php 
	foreach ($userGroupsForObject[$uamUserGroup->getId()]->setRecursive['byPost'] as $postId) {
	}
	    $post = get_post($postId);
	    ?>
	    	<li><?php echo $post->post_title; ?></li>
	    <?php
	?>
		</ul>
	</li>
    <?php 
}
?>
	<li class="uam_group_info_head"><?php echo TXT_GROUP_INFO; ?>:
		<ul>
			<li><?php echo TXT_READ_ACCESS; ?>:
<?php
if ($uamUserGroup->getReadAccess() == "all") {
    echo TXT_ALL;
} elseif ($uamUserGroup->getReadAccess() == "group") {
    echo TXT_ONLY_GROUP_USERS;
}
?>
			</li>
			<li><?php echo TXT_WRITE_ACCESS; ?>:
<?php
if ($uamUserGroup->getWriteAccess()  == "all") {
    echo TXT_ALL;   
} elseif ($uamUserGroup->getWriteAccess()  == "group") {
    echo TXT_ONLY_GROUP_USERS;
}
?>
        	</li>
        	<li>
        	    <?php echo TXT_GROUP_ROLE; ?>: <?php
if ($uamUserGroup->getRoles()) {
    $out = '';
    
    foreach ($uamUserGroup->getRoles() as $role) {
        $out .= trim($role['role_name']).', ';
    }
    
    echo rtrim($out, ', ');
} else {
    echo TXT_NONE;
}
?>
        	</li>
        	<li><?php echo count($uamUserGroup->getPosts()) . " " . TXT_POSTS; ?></li>
        	<li><?php echo count($uamUserGroup->getPages()) . " " . TXT_PAGES; ?></li>
        	<li><?php echo count($uamUserGroup->getCategories()) . " " . TXT_CATEGORIES; ?></li>
        	<li><?php echo count($uamUserGroup->getUsers()) . " " . TXT_USERS; ?></li>
		</ul>
	</li>
</ul>
</div>