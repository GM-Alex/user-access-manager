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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminObjectController $this
 */
$aUserGroups = $this->getUserGroups();
$aObjectUserGroups = $this->getObjectUserGroups();

?>
<table class="form-table">
    <tbody>
    <tr>
        <th>
            <label for="description"><?php echo TXT_UAM_SET_UP_USERGROUPS; ?></label>
        </th>
        <td>
            <?php
            if (count($aUserGroups) > 0) {
                include 'GroupSelectionForm.php';
            } elseif ($this->checkUserAccess()) {
                ?>
                <a href='admin.php?page=uam_user_group'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
                <?php
            } else {
                echo TXT_UAM_NO_GROUP_AVAILABLE;
            }
            ?>
        </td>
    </tr>
    </tbody>
</table>