<?php
/**
 * UserGroupEditForm.php
 *
 * Shows the user group edit form at the admin panel.
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
 * @var UserGroupController $controller
 */

use UserAccessManager\Controller\Backend\UserGroupController;
use UserAccessManager\Object\ObjectHandler;

$userGroup = $controller->getUserGroup();
?>
<form method="post" action="<?php echo $controller->getRequestUrl(); ?>">
    <input type="hidden" value="insert_update_user_group" name="uam_action"/>
    <?php
    $controller->createNonceField($controller::INSERT_UPDATE_GROUP_NONCE);

    if ($userGroup->getId() !== null) {
        ?>
        <input type="hidden" value="<?php echo $userGroup->getId(); ?>" name="userGroupId"/>
        <?php
    }
    ?>
    <table class="form-table">
        <tbody>
        <tr class="form-field form-required">
            <th valign="top"><label for="userGroupName"><?php echo TXT_UAM_GROUP_NAME; ?></label></th>
            <td>
                <input size="40" value="<?php echo htmlentities($userGroup->getName()); ?>"
                       id="userGroupName" name="userGroupName"/><br/>
                <?php echo TXT_UAM_GROUP_NAME_DESC; ?>
            </td>
        </tr>
        <tr class="form-field form-required">
            <th valign="top"><label for="userGroupDescription"><?php echo TXT_UAM_GROUP_DESC; ?></label>
            </th>
            <td>
                <input size="40" value="<?php echo htmlentities($userGroup->getDescription()); ?>"
                       id="userGroupDescription" name="userGroupDescription"/><br/>
                <?php echo TXT_UAM_GROUP_DESC_DESC; ?>
            </td>
        </tr>
        <tr class="form-field form-required">
            <th valign="top"><label for="ipRange"><?php echo TXT_UAM_GROUP_IP_RANGE; ?></label></th>
            <td><input size="40" value="<?php echo htmlentities($userGroup->getIpRange()); ?>"
                       id="ipRange" name="ipRange"/><br/>
                <?php echo TXT_UAM_GROUP_IP_RANGE_DESC; ?>
            </td>
        </tr>
        <tr class="form-field form-required">
            <th valign="top"><label for="readAccess"><?php echo TXT_UAM_GROUP_READ_ACCESS; ?></label>
            </th>
            <td>
                <select id="readAccess" name="readAccess">
                    <option value="group"
                        <?php
                        if ($userGroup->getReadAccess() === 'group') {
                            echo 'selected="selected"';
                        }
                        ?>
                    >
                        <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
                    </option>
                    <option value="all"
                        <?php
                        if ($userGroup->getReadAccess() === 'all') {
                            echo 'selected="selected"';
                        }
                        ?>
                    >
                        <?php echo TXT_UAM_ALL_USERS ?>
                    </option>
                </select><br/>
                <?php echo TXT_UAM_GROUP_READ_ACCESS_DESC; ?>
            </td>
        </tr>
        <tr class="form-field form-required">
            <th valign="top"><label for="writeAccess"><?php echo TXT_UAM_GROUP_WRITE_ACCESS; ?></label>
            </th>
            <td>
                <select id="writeAccess" name="writeAccess">
                    <option value="none"
                        <?php
                        if ($userGroup->getWriteAccess() === 'none') {
                            echo 'selected="selected"';
                        }
                        ?>
                    >
                        <?php echo TXT_UAM_NONE ?>
                    </option>
                    <option value="group"
                        <?php
                        if ($userGroup->getWriteAccess() === 'group') {
                            echo 'selected="selected"';
                        }
                        ?>
                    >
                        <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
                    </option>
                    <option value="all"
                        <?php
                        if ($userGroup->getWriteAccess() === 'all') {
                            echo 'selected="selected"';
                        }
                        ?>
                    >
                        <?php echo TXT_UAM_ALL_USERS ?>
                    </option>
                </select><br/>
                <?php echo TXT_UAM_GROUP_WRITE_ACCESS_DESC; ?>
            </td>
        </tr>
        <tr>
            <th valign="top"><?php echo TXT_UAM_GROUP_ROLE; ?></th>
            <td>
                <ul class='uam_role'>
                    <?php
                    $groupRoles = $userGroup->getAssignedObjectsByType(
                        ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                    );
                    $roleNames = $controller->getRoleNames();

                    foreach ($roleNames as $role => $name) {
                        if ($role !== 'administrator') {
                            ?>
                            <li class="selectit">
                                <input id="role-<?php echo $role; ?>" type="checkbox"
                                    <?php
                                    if (isset($groupRoles[$role]) === true) {
                                        echo 'checked="checked"';
                                    }
                                    ?>
                                       value="<?php echo $role; ?>" name="roles[]"/>
                                <label for="role-<?php echo $role; ?>">
                                    <?php echo $name; ?>
                                </label>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </td>
        </tr>
        </tbody>
    </table>
    <p class="submit">
        <input type="submit" value="<?php
        if ($userGroup->getId() !== null) {
            echo TXT_UAM_UPDATE_GROUP;
        } else {
            echo TXT_UAM_ADD_GROUP;
        }
        ?>" name="submit" class="button"/>
    </p>
</form>
