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
 * @copyright 2008-2010 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

global $wpdb, $current_user;

$userGroupsDb = $wpdb->get_results(
	"SELECT *
	FROM " . DB_ACCESSGROUP . "
	ORDER BY groupname", 
    ARRAY_A
);

if (isset($_GET['tag_ID'])) {
    $categoryId = $_GET['tag_ID'];
}

$uamAccessHandler = new UamAccessHandler();
$userGroupsForObject = $uamAccessHandler->getUserGroupsForCategory($categoryId);

?>
<table class="form-table">
	<tbody>
		<tr>
			<th>
				<label for="description"><?php echo TXT_SET_UP_USERGROUPS; ?></label>
			</th>
			<td>
				<ul>
<?php
if (isset($userGroupsDb)) {
	foreach ($userGroupsDb as $userGroupDb) {
		$usergroup = new UamUserGroup($userGroupDb['ID']);
		?>
            		<li>            		
            			<label for="uam_usergroup-<?php echo $usergroup->getId(); ?>" class="selectit" style="display:inline;" >
            				<input type="checkbox" id="uam_usergroup-<?php echo $usergroup->getId(); ?>"
		<?php
	    if (array_key_exists($usergroup->getId(), $userGroupsForObject)) {
            echo 'checked="checked"';
        }
		/*if(isset($set_recursive->posts) || isset($set_recursive->categories))
			$content .= 'disabled=""';*/
        ?>
            				value="<?php echo $usergroup->getId(); ?>" name="usergroups[]"/>
            				<?php echo $usergroup->getGroupName(); ?>
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
							<li><?php echo$cur_post->post_title; ?> [<?php echo $post->post_type; ?>]</li>
					<?php
				}
			}
			
			if (isset($setRecursive->categories)) {
				foreach ($setRecursive->categories as $categoryId) {
					$category = & get_category($categoryId);
					?>
							<li><?php echo$category->name; ?> [<?php echo TXT_CATEGORY; ?>]</li>
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
} else {
    ?>        

                    <li>
            			<a href='admin.php?page=uam_usergroup'><?php echo TXT_CREATE_GROUP_FIRST; ?></a>
            		</li>
	<?php
}
?>
				</ul>
			</td>
		</tr>
	</tbody>
</table>
<?php
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = null;
}

if ($action != 'edit') {
	?>
	<style type="text/css">
        .submit {
        	display: none;
        	position: relative;
        }
    </style>
    <p class="submit" style="display: block; position: relative;">
    	<input class="button" type="submit" value="Add New Category" name="submit" />
	</p>
	<?php
}