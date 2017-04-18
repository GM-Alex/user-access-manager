<?php
/**
 * TermEditForm.php
 *
 * Shows the term edit form at the admin panel.
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
<table class="form-table">
    <tbody>
    <tr>
        <th>
            <label for="description"><?php echo TXT_UAM_SET_UP_USER_GROUPS; ?></label>
        </th>
        <td>
            <?php
            if (count($userGroups) > 0) {
                include 'GroupSelectionForm.php';
            } elseif ($controller->checkUserAccess()) {
                ?>
                <a href='?page=uam_user_group'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
                <?php
            } else {
                echo TXT_UAM_NO_GROUP_AVAILABLE;
            }
            ?>
        </td>
    </tr>
    </tbody>
</table>