<?php
/**
 * UserProfileEditForm.php
 *
 * Shows the bulk edit form at the admin panel.
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
?>
<table class="form-table">
    <tbody>
    <tr>
        <th>
            <label for="user_groups"><?php echo TXT_UAM_SET_UP_USER_GROUPS; ?></label>
        </th>
        <td>
            <ul>
                <li>
                    <input type="radio" id="bulk_add" value="add" name="uam_bulk_type" checked="checked"/>
                    <label for="bulk_add" class="selectit" style="display:inline;"><?php echo TXT_UAM_ADD; ?></label>

                </li>
                <li>
                    <input type="radio" id="bulk_remove" value="remove" name="uam_bulk_type"/>
                    <label for="bulk_remove" class="selectit" style="display:inline;">Remove</label>

                </li>
                <li>
                    <input type="radio" id="bulk_overwrite" value="overwrite" name="uam_bulk_type"/>
                    <label for="bulk_overwrite" class="selectit" style="display:inline;">Overwrite</label>
                </li>
            </ul>
        </td>
        <td>
            <?php
            include 'GroupSelectionForm.php';
            ?>
        </td>
    </tr>
    </tbody>
</table>