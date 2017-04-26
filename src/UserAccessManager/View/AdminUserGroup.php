<?php
/**
 * AdminUserGroup.php
 *
 * Shows the user group page at the admin panel.
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
 * @var \UserAccessManager\Controller\AdminUserGroupController $controller
 */
if ($controller->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $controller->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

if ($controller->getRequestParameter('uam_action') === null
    || $controller->getRequestParameter('uam_action') === 'delete_user_group'
) {
    ?>
    <div class="wrap">
        <form method="post" action="<?php echo $controller->getRequestUrl(); ?>">
            <?php $controller->createNonceField('uamDeleteGroup'); ?>
            <input type="hidden" value="delete_user_group" name="uam_action"/>
            <h2><?php echo TXT_UAM_MANAGE_GROUP; ?></h2>
            <div class="tablenav">
                <div class="alignleft">
                    <input type="submit" class="button-secondary delete" name="deleteit"
                           value="<?php echo TXT_UAM_DELETE; ?>"/>
                </div>
                <br class="clear"/>
            </div>
            <table class="widefat">
                <thead>
                <tr class="thead">
                    <th scope="col"></th>
                    <th scope="col"><?php echo TXT_UAM_NAME; ?></th>
                    <th scope="col"><?php echo TXT_UAM_DESCRIPTION; ?></th>
                    <th scope="col"><?php echo TXT_UAM_READ_ACCESS; ?></th>
                    <th scope="col"><?php echo TXT_UAM_WRITE_ACCESS; ?></th>
                    <th scope="col"><?php echo TXT_UAM_GROUP_ROLE; ?></th>
                    <th scope="col"><?php echo TXT_UAM_IP_RANGE; ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $currentAdminPage = $controller->getRequestParameter('page');
                $userGroups = $controller->getUserGroups();
                foreach ($userGroups as $userGroup) {
                    ?>
                    <tr class="alternate" id="group-<?php echo $userGroup->getId(); ?>">
                        <th class="check-column" scope="row">
                            <label>
                                <input type="checkbox" value="<?php echo $userGroup->getId(); ?>" name="delete[]"/>
                            </label>
                        </th>
                        <td>
                            <strong>
                                <?php
                                $link = "?page={$currentAdminPage}&amp;uam_action=edit_user_group"
                                    ."&amp;userGroupId={$userGroup->getId()}";
                                ?>
                                <a href="<?php echo $link; ?>">
                                    <?php echo htmlentities($userGroup->getName()); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo htmlentities($userGroup->getDescription()) ?></td>
                        <td>
                            <?php
                            if ($userGroup->getReadAccess() === 'all') {
                                echo TXT_UAM_ALL;
                            } elseif ($userGroup->getReadAccess() === 'group') {
                                echo TXT_UAM_ONLY_GROUP_USERS;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($userGroup->getWriteAccess() === 'all') {
                                echo TXT_UAM_ALL;
                            } elseif ($userGroup->getWriteAccess() === 'group') {
                                echo TXT_UAM_ONLY_GROUP_USERS;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $roleNames = $controller->getRoleNames();
                            $groupRoles = $userGroup->getAssignedObjectsByType(
                                \UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                            );

                            if (count($groupRoles) > 0) {
                                ?>
                                <ul style="margin: 0;">
                                    <?php
                                    foreach ($groupRoles as $key => $role) {
                                        ?>
                                        <li><?php echo isset($roleNames[$key]) ? $roleNames[$key] : $key; ?></li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                                <?php
                            } else {
                                echo TXT_UAM_NONE;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $ipRanges = $userGroup->getIpRangeArray();

                            if (count($ipRanges) > 0) {
                                ?>
                                <ul>
                                    <?php
                                    foreach ($ipRanges as $ipRange) {
                                        ?>
                                        <li><?php echo htmlentities($ipRange); ?></li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                                <?php
                            } else {
                                echo TXT_UAM_NONE;
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <div class="tablenav">
                <div class="alignleft">
                    <input type="submit" class="button-secondary delete" name="deleteit"
                           value="<?php echo TXT_UAM_DELETE; ?>"/>
                </div>
                <br class="clear"/>
            </div>
        </form>
    </div>
    <?php
}
?>
<div class="wrap">
    <h2>
        <?php
        if ($controller->getRequestParameter('uam_action') === 'editGroup') {
            echo TXT_UAM_EDIT_GROUP;
        } else {
            echo TXT_UAM_ADD_GROUP;
        }
        ?>
    </h2>
    <form method="post" action="<?php echo $controller->getRequestUrl(); ?>">
        <input type="hidden" value="insert_update_user_group" name="uam_action"/>
        <?php
        $userGroup = $controller->getUserGroup();
        $controller->createNonceField('uamInsertUpdateGroup');
        if ($userGroup->getId() !== null) {
            ?>
            <input type="hidden" value="<?php echo $userGroup->getId(); ?>" name="userGroupId"/>
            <?php
        }
        ?>
        <table class="form-table">
            <tbody>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="userGroupName"><?php echo TXT_UAM_GROUP_NAME; ?></label></th>
                <td>
                    <input type="text" size="40" value="<?php echo htmlentities($userGroup->getName()); ?>"
                           id="userGroupName" name="userGroupName"/><br/>
                    <?php echo TXT_UAM_GROUP_NAME_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="userGroupDescription"><?php echo TXT_UAM_GROUP_DESC; ?></label>
                </th>
                <td>
                    <input type="text" size="40" value="<?php echo htmlentities($userGroup->getDescription()); ?>"
                           id="userGroupDescription" name="userGroupDescription"/><br/>
                    <?php echo TXT_UAM_GROUP_DESC_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="ipRange"><?php echo TXT_UAM_GROUP_IP_RANGE; ?></label></th>
                <td><input type="text" size="40" value="<?php echo htmlentities($userGroup->getIpRange()); ?>"
                           id="ipRange" name="ipRange"/><br/>
                    <?php echo TXT_UAM_GROUP_IP_RANGE_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="readAccess"><?php echo TXT_UAM_GROUP_READ_ACCESS; ?></label>
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
                            <?php echo TXT_UAM_ALL ?>
                        </option>
                    </select><br/>
                    <?php echo TXT_UAM_GROUP_READ_ACCESS_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="writeAccess"><?php echo TXT_UAM_GROUP_WRITE_ACCESS; ?></label>
                </th>
                <td>
                    <select id="writeAccess" name="writeAccess">
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
                            <?php echo TXT_UAM_ALL ?>
                        </option>
                    </select><br/>
                    <?php echo TXT_UAM_GROUP_WRITE_ACCESS_DESC; ?>
                </td>
            </tr>
            <tr>
                <th valign="top" scope="row"><?php echo TXT_UAM_GROUP_ROLE; ?></th>
                <td>
                    <ul class='uam_role'>
                        <?php
                        $groupRoles = $userGroup->getAssignedObjectsByType(
                            \UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
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
</div>