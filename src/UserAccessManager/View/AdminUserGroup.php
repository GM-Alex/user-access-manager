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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminUserGroupController $Controller
 */
if ($Controller->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $Controller->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

if ($Controller->getRequestParameter('uam_action') === null
    || $Controller->getRequestParameter('uam_action') === 'delete_user_group'
) {
    ?>
    <div class="wrap">
        <form method="post" action="<?php echo $Controller->getRequestUrl(); ?>">
            <?php $Controller->createNonceField('uamDeleteGroup'); ?>
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
                $sCurrentAdminPage = $Controller->getRequestParameter('page');
                $aUserGroups = $Controller->getUserGroups();
                foreach ($aUserGroups as $UserGroup) {
                    ?>
                    <tr class="alternate" id="group-<?php echo $UserGroup->getId(); ?>">
                        <th class="check-column" scope="row">
                            <label>
                                <input type="checkbox" value="<?php echo $UserGroup->getId(); ?>" name="delete[]"/>
                            </label>
                        </th>
                        <td>
                            <strong>
                                <?php
                                $sLink = "?page={$sCurrentAdminPage}&amp;uam_action=edit_user_group"
                                    ."&amp;userGroupId={$UserGroup->getId()}";
                                ?>
                                <a href="<?php echo $sLink; ?>">
                                    <?php echo htmlentities($UserGroup->getName()); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo htmlentities($UserGroup->getDescription()) ?></td>
                        <td>
                            <?php
                            if ($UserGroup->getReadAccess() === 'all') {
                                echo TXT_UAM_ALL;
                            } elseif ($UserGroup->getReadAccess() === 'group') {
                                echo TXT_UAM_ONLY_GROUP_USERS;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($UserGroup->getWriteAccess() === 'all') {
                                echo TXT_UAM_ALL;
                            } elseif ($UserGroup->getWriteAccess() === 'group') {
                                echo TXT_UAM_ONLY_GROUP_USERS;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $aRoleNames = $Controller->getRoleNames();
                            $aGroupRoles = $UserGroup->getAssignedObjectsByType(
                                \UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                            );

                            if (count($aGroupRoles) > 0) {
                                ?>
                                <ul style="margin: 0;">
                                    <?php
                                    foreach ($aGroupRoles as $sKey => $sRole) {
                                        ?>
                                        <li><?php echo isset($aRoleNames[$sKey]) ? $aRoleNames[$sKey] : $sKey; ?></li>
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
                            if ($UserGroup->getIpRange()) {
                                ?>
                                <ul>
                                    <?php
                                    foreach ($UserGroup->getIpRange() as $sIpRange) {
                                        ?>
                                        <li><?php echo htmlentities($sIpRange); ?></li>
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
        if ($Controller->getRequestParameter('uam_action') === 'editGroup') {
            echo TXT_UAM_EDIT_GROUP;
        } else {
            echo TXT_UAM_ADD_GROUP;
        }
        ?>
    </h2>
    <form method="post" action="<?php echo $Controller->getRequestUrl(); ?>">
        <input type="hidden" value="insert_update_user_group" name="uam_action"/>
        <?php
        $UserGroup = $Controller->getUserGroup();
        $Controller->createNonceField('uamInsertUpdateGroup');
        if ($UserGroup->getId() !== null) {
            ?>
            <input type="hidden" value="<?php echo $UserGroup->getId(); ?>" name="userGroupId"/>
            <?php
        }
        ?>
        <table class="form-table">
            <tbody>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="userGroupName"><?php echo TXT_UAM_GROUP_NAME; ?></label></th>
                <td>
                    <input type="text" size="40" value="<?php echo htmlentities($UserGroup->getName()); ?>"
                           id="userGroupName" name="userGroupName"/><br/>
                    <?php echo TXT_UAM_GROUP_NAME_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="userGroupDescription"><?php echo TXT_UAM_GROUP_DESC; ?></label>
                </th>
                <td>
                    <input type="text" size="40" value="<?php echo htmlentities($UserGroup->getDescription()); ?>"
                           id="userGroupDescription" name="userGroupDescription"/><br/>
                    <?php echo TXT_UAM_GROUP_DESC_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="ipRange"><?php echo TXT_UAM_GROUP_IP_RANGE; ?></label></th>
                <td><input type="text" size="40" value="<?php echo htmlentities($UserGroup->getIpRange(true)); ?>"
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
                            if ($UserGroup->getReadAccess() === 'group') {
                                echo 'selected="selected"';
                            }
                            ?>
                        >
                            <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
                        </option>
                        <option value="all"
                            <?php
                            if ($UserGroup->getReadAccess() === 'all') {
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
                            if ($UserGroup->getWriteAccess() === 'group') {
                                echo 'selected="selected"';
                            }
                            ?>
                        >
                            <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
                        </option>
                        <option value="all"
                            <?php
                            if ($UserGroup->getWriteAccess() === 'all') {
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
                        $aGroupRoles = $UserGroup->getAssignedObjectsByType(
                            \UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                        );
                        $aRoleNames = $Controller->getRoleNames();

                        foreach ($aRoleNames as $sRole => $sName) {
                            if ($sRole !== 'administrator') {
                                ?>
                                <li class="selectit">
                                    <input id="role-<?php echo $sRole; ?>" type="checkbox"
                                        <?php
                                        if (isset($aGroupRoles[$sRole])) {
                                            echo 'checked="checked"';
                                        }
                                        ?>
                                           value="<?php echo $sRole; ?>" name="roles[]"/>
                                    <label for="role-<?php echo $sRole; ?>">
                                        <?php echo $sName; ?>
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
            if ($UserGroup->getId() !== null) {
                echo TXT_UAM_UPDATE_GROUP;
            } else {
                echo TXT_UAM_ADD_GROUP;
            }
            ?>" name="submit" class="button"/>
        </p>
    </form>
</div>