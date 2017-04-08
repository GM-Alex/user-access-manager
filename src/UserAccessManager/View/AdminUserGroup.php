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
 * @var \UserAccessManager\Controller\AdminUserGroupController $oController
 */
if ($oController->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $oController->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

if ($oController->getRequestParameter('uam_action') === null
    || $oController->getRequestParameter('uam_action') === 'delete_user_group'
) {
    ?>
    <div class="wrap">
        <form method="post" action="<?php echo $oController->getRequestUrl(); ?>">
            <?php $oController->createNonceField('uamDeleteGroup'); ?>
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
                $sCurrentAdminPage = $oController->getRequestParameter('page');
                $aUserGroups = $oController->getUserGroups();
                foreach ($aUserGroups as $oUserGroup) {
                    ?>
                    <tr class="alternate" id="group-<?php echo $oUserGroup->getId(); ?>">
                        <th class="check-column" scope="row">
                            <label>
                                <input type="checkbox" value="<?php echo $oUserGroup->getId(); ?>" name="delete[]"/>
                            </label>
                        </th>
                        <td>
                            <strong>
                                <?php
                                $sLink = "?page={$sCurrentAdminPage}&amp;uam_action=edit_user_group"
                                    ."&amp;userGroupId={$oUserGroup->getId()}";
                                ?>
                                <a href="<?php echo $sLink; ?>">
                                    <?php echo htmlentities($oUserGroup->getName()); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo htmlentities($oUserGroup->getDescription()) ?></td>
                        <td>
                            <?php
                            if ($oUserGroup->getReadAccess() === 'all') {
                                echo TXT_UAM_ALL;
                            } elseif ($oUserGroup->getReadAccess() === 'group') {
                                echo TXT_UAM_ONLY_GROUP_USERS;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($oUserGroup->getWriteAccess() === 'all') {
                                echo TXT_UAM_ALL;
                            } elseif ($oUserGroup->getWriteAccess() === 'group') {
                                echo TXT_UAM_ONLY_GROUP_USERS;
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $aRoleNames = $oController->getRoleNames();
                            $aGroupRoles = $oUserGroup->getAssignedObjectsByType(
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
                            if ($oUserGroup->getIpRange()) {
                                ?>
                                <ul>
                                    <?php
                                    foreach ($oUserGroup->getIpRange() as $sIpRange) {
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
        if ($oController->getRequestParameter('uam_action') === 'editGroup') {
            echo TXT_UAM_EDIT_GROUP;
        } else {
            echo TXT_UAM_ADD_GROUP;
        }
        ?>
    </h2>
    <form method="post" action="<?php echo $oController->getRequestUrl(); ?>">
        <input type="hidden" value="insert_update_user_group" name="uam_action"/>
        <?php
        $oUserGroup = $oController->getUserGroup();
        $oController->createNonceField('uamInsertUpdateGroup');
        if ($oUserGroup->getId() !== null) {
            ?>
            <input type="hidden" value="<?php echo $oUserGroup->getId(); ?>" name="userGroupId"/>
            <?php
        }
        ?>
        <table class="form-table">
            <tbody>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="userGroupName"><?php echo TXT_UAM_GROUP_NAME; ?></label></th>
                <td>
                    <input type="text" size="40" value="<?php echo htmlentities($oUserGroup->getName()); ?>"
                           id="userGroupName" name="userGroupName"/><br/>
                    <?php echo TXT_UAM_GROUP_NAME_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="userGroupDescription"><?php echo TXT_UAM_GROUP_DESC; ?></label>
                </th>
                <td>
                    <input type="text" size="40" value="<?php echo htmlentities($oUserGroup->getDescription()); ?>"
                           id="userGroupDescription" name="userGroupDescription"/><br/>
                    <?php echo TXT_UAM_GROUP_DESC_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="ipRange"><?php echo TXT_UAM_GROUP_IP_RANGE; ?></label></th>
                <td><input type="text" size="40" value="<?php echo htmlentities($oUserGroup->getIpRange(true)); ?>"
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
                            if ($oUserGroup->getReadAccess() === 'group') {
                                echo 'selected="selected"';
                            }
                            ?>
                        >
                            <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
                        </option>
                        <option value="all"
                            <?php
                            if ($oUserGroup->getReadAccess() === 'all') {
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
                            if ($oUserGroup->getWriteAccess() === 'group') {
                                echo 'selected="selected"';
                            }
                            ?>
                        >
                            <?php echo TXT_UAM_ONLY_GROUP_USERS ?>
                        </option>
                        <option value="all"
                            <?php
                            if ($oUserGroup->getWriteAccess() === 'all') {
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
                        $aGroupRoles = $oUserGroup->getAssignedObjectsByType(
                            \UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                        );
                        $aRoleNames = $oController->getRoleNames();

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
            if ($oUserGroup->getId() !== null) {
                echo TXT_UAM_UPDATE_GROUP;
            } else {
                echo TXT_UAM_ADD_GROUP;
            }
            ?>" name="submit" class="button"/>
        </p>
    </form>
</div>