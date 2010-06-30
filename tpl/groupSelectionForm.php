<?php
?>
<ul>
<?php 
foreach ($uamUserGroups as $uamUserGroup) {
	?>
	<li>
		<label for="uam_usergroup-<?php echo $uamUserGroup->getId(); ?>" class="selectit" style="display:inline;" >
			<input type="checkbox" id="uam_usergroup-<?php echo $uamUserGroup->getId(); ?>"
	<?php
    if (array_key_exists($uamUserGroup->getId(), $userGroupsForObject)) {
        echo 'checked="checked"';
    }
    
	if (isset($uamUserGroup->setRecursive)) {
		echo 'disabled=""';
	}
    ?>
			value="<?php echo $uamUserGroup->getId(); ?>" name="usergroups[]"/>
			<?php echo $uamUserGroup->getGroupName(); ?>
		</label>
		<a class="uam_group_info_link">(<?php echo TXT_INFO; ?>)</a>
	<?php 

	include 'groupInfo.php';
	if (isset($setRecursive->posts) 
	    || isset($setRecursive->categories)
	) {
	    ?>
		&nbsp;<a class="uam_group_lock_info_link">[LR]</a>
		<?php 
	}

	if (isset($setRecursive->posts) 
	    || isset($setRecursive->categories)
	) {
	    ?>
		<ul class="uam_group_lock_info">
			<li class="uam_group_lock_info_head"><?php echo TXT_GROUP_LOCK_INFO; ?></li>
		<?php
		
		if (isset($setRecursive->posts)) {
			foreach ($setRecursive->posts as $postId) {
				$post = & get_post($postId);
				?>
				<li><?php echo $post->post_title; ?> [<?php echo $post->post_type; ?>]</li>
				<?php
			}
		}
		
		if (isset($setRecursive->categories)) {
			foreach ($setRecursive->categories as $categoryId) {
				$category = & get_category($categoryId);
				?>
				<li><?php echo $category->name; ?> [<?php echo TXT_CATEGORY; ?>]</li>
				<?php
			}
		}
		?>
		</ul>
		<?php
	}
	?>
	</li>
	<?php
}
?>
</ul>