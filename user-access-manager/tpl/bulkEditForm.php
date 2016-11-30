<?php
/**
 * bulkEditForm.php
 * 
 * Shows the bulk edit form at the admin panel.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

global $oUserAccessManager;
$aUamUserGroups = $oUserAccessManager->getAccessHandler()->getUserGroups();
$aUserGroupsForObject = array();
$sObjectType = null;
$iObjectId = null;
?>
<table class="form-table">
    <tbody>
    <tr>
        <th>
            <label for="usergroups"><?php echo TXT_UAM_SET_UP_USERGROUPS; ?></label>
        </th>
        <td>
            <ul>
                <li>
                    <input type="radio" id="bulk_add" value="add" name="uam_bulk_type" checked="checked" />
                    <label for="bulk_add" class="selectit" style="display:inline;"><?php echo TXT_UAM_ADD; ?></label>

                </li>
                <li>
                    <input type="radio" id="bulk_remove" value="remove" name="uam_bulk_type" />
                    <label for="bulk_remove" class="selectit" style="display:inline;">Remove</label>

                </li>
                <li>
                    <input type="radio" id="bulk_overwrite" value="overwrite" name="uam_bulk_type" />
                    <label for="bulk_overwrite" class="selectit" style="display:inline;">Overwrite</label>
                </li>
            </ul>
        </td>
        <td>
            <?php
            include 'groupSelectionForm.php';
            ?>
        </td>
    </tr>
    </tbody>
</table>