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
if (isset($uamUserGroup->setRecursive['byCategory'])) {
    ?>
	<li  class="uam_group_info_head">
		<?php echo TXT_GROUP_MEMBERSHIP_BY_CATEGORIES; ?>:
		<ul>
	<?php
	foreach ($uamUserGroup->setRecursive['byCategory'] as $categoryId) {
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
if (isset($uamUserGroup->setRecursive['byPost'])) {
    ?>
	<li  class="uam_group_info_head">
		<?php echo TXT_GROUP_MEMBERSHIP_BY_POSTS; ?>:
		<ul>
	<?php 
	foreach ($isRecursiveMember['byPost'] as $postId) {
	}
	    $post = get_post($postId);
	    ?>
	    	<li><?php echo $post->name; ?></li>
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
        	<li><?php echo count($uamUserGroup->getPosts()) . " " . TXT_POSTS; ?></li>
        	<li><?php echo count($uamUserGroup->getPages()) . " " . TXT_PAGES; ?></li>
        	<li><?php echo count($uamUserGroup->getPages()) . " " . TXT_PAGES; ?></li>
        	<li><?php echo count($uamUserGroup->getCategories()) . " " . TXT_CATEGORIES; ?></li>
        	<li><?php echo count($uamUserGroup->getUsers()) . " " . TXT_USERS; ?></li>
		</ul>
	</li>
</ul>
</div>