<?php
/**
 * userProfileEditForm.php
 * 
 * Shows the additional content for the user profile edit form.
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

$userId = $_GET['user_id'];
$curUserdata = get_userdata($current_user->ID);
$editUserData = get_userdata($userId);
$uamOptions = $this->getAdminOptions();

$uamAccessHandler = new UamAccessHandler();
$userGroupsForObject = $uamAccessHandler->getUserGroupsForUser($userId);

if ($curUserdata->user_level >= $uamOptions['full_access_level']) {
    $userGroupDbs = $wpdb->get_results(
    	"SELECT *
		FROM " . DB_ACCESSGROUP . "
		ORDER BY groupname",
        ARRAY_A
    );
    ?>
	<h3><?php echo TXT_GROUPS; ?></h3>
	<table class="form-table">
		<tbody>
    		<tr>
    			<th>
    				<label for="usergroups"><?php echo TXT_SET_UP_USERGROUPS; ?></label>
    			</th>
				<td>
					<ul>
    <?php
    if (empty($editUserData->{$wpdb->prefix . "capabilities"}['administrator'])) {
        if (isset($userGroupDbs)) {
            foreach ($userGroupDbs as $userGroupDb) {
                $usergroup = new UamUserGroup($userGroupDb['ID']);
                ?>
    					<li>
    						<label for="uam_usergroup-<?php echo $usergroup->getId(); ?>" lass="selectit"> 
    							<input type="checkbox" id="uam_usergroup-<?php echo $userGroupDb['ID']; ?>"
	            <?php
                if (array_key_exists($usergroup->getId(), $userGroupsForObject)) {
                    echo 'checked="checked"';
                }
                ?>
    						value="<?php echo $usergroup->getId(); ?>" name="usergroups[]" /> 
    						<?php echo $usergroup->getGroupName(); ?>
							</label>
							<a class="uam_group_info_link">(<?php echo TXT_INFO; ?>)</a>
						
				<?php
                include 'groupInfo.php';
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
    } else {
        echo TXT_ADMIN_HINT;
    }
    ?>
					</ul>
    			</td>
    		</tr>
    	</tbody>
	</table>
    <?php
}
?>