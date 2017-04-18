<?php
/**
 * UserProfileEditForm.php
 *
 * Shows the user profile edit form at the admin panel.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminObjectController $controller
 */
$userGroups = $controller->getFilteredUserGroups();

?>
<h3><?php echo TXT_UAM_GROUPS; ?></h3>
<table class="form-table">
    <tbody>
    <tr>
        <th>
            <label for="user_groups"><?php echo TXT_UAM_SET_UP_USER_GROUPS; ?></label>
        </th>
        <td>
            <?php
            if ($controller->isCurrentUserAdmin() === false) {
                include 'GroupSelectionForm.php';
            } else {
                echo TXT_UAM_ADMIN_HINT;
            }
            ?>
        </td>
    </tr>
    </tbody>
</table>