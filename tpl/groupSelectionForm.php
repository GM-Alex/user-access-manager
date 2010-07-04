<?php
?>
<ul class="uam_group_selection">
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

	if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive)) {
		echo 'disabled=""';
	}
    ?>
			value="<?php echo $uamUserGroup->getId(); ?>" name="usergroups[]" /><?php 
	echo $uamUserGroup->getGroupName();
	
	if (isset($uamUserGroup->setRecursive)) {
		echo ' [LR]';
	}
		?></label>
		<a class="uam_group_info_link">(<?php echo TXT_INFO; ?>)</a>
		<?php include 'groupInfo.php'; ?>
	</li>
	<?php
}
?>
</ul>