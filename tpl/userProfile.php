<?php
/**
 * userProfile.php
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
$cur_edit_userdata = get_userdata($userId);
$uamOptions = $this->getAdminOptions();

if ($curUserdata->user_level >= $uamOptions['full_access_level']) {
    $accessgroups = $wpdb->get_results(
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
    <?php
    if (empty($cur_edit_userdata->{$wpdb->prefix . "capabilities"}['administrator'])) {
        if (isset($accessgroups)) {
            foreach ($accessgroups as $accessgroup) {
                $checked = $wpdb->get_results(
                	"SELECT *
					FROM " . DB_ACCESSGROUP_TO_USER . "
					WHERE user_id = " . $userId . "
					AND group_id = " . $accessgroup['ID'], 
                    ARRAY_A
                );
                ?>
					<p style="margin: 6px 0;">
						<label for="uam_accesssgroup-<?php echo $accessgroup['ID']; ?>" lass="selectit"> 
							<input type="checkbox" id="uam_accesssgroup-<?php echo $accessgroup['ID']; ?>"
	            <?php
                if (isset($checked)) {
                    echo 'checked="checked"';
                } 
                ?>
						value="<?php echo $accessgroup['ID']; ?>" name="accessgroups[]" /> 
						<?php echo $accessgroup['groupname']; ?>
						</label>
				<?php
                $group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], "padding: 0 0 0 32px");
                echo $group_info_html->link;
                echo $group_info_html->content;
                ?>
                	</p>
                <?php 
            }
        } else {
                ?>
                <a href='admin.php?page=uam_usergroup'><?php echo TXT_CREATE_GROUP_FIRST; ?></a>
                <?php 
        }
    } else {
        echo TXT_ADMIN_HINT;
    }
    ?>

    			</td>
    		</tr>
    	</tbody>
	</table>
    <?php
}