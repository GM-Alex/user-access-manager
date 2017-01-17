<?php
/**
 * adminGroup.php
 *
 * Shows the group management page at the admin panel.
 *
 * PHP versions 5
 *
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminUserGroupController $this
 */
if ($this->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $this->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

if ($this->getRequestParameter('uam_action') === null) {
    ?>
    <div class="wrap">
        <form method="post" action="<?php echo $this->getRequestUrl(); ?>">
            <?php $this->createNonceField('uamDeleteGroup'); ?>
            <input type="hidden" value="delete_user_group" name="uam_action"/>
            <h2><?php echo TXT_UAM_MANAGE_GROUP; ?></h2>
            <div class="tablenav">
                <div class="alignleft">
                    <input type="submit" class="button-secondary delete" name="deleteit"
                           value="<?php echo TXT_UAM_DELETE; ?>"/>
                </div>
                <br class="clear"/>
            </div>
            <br class="clear"/>
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
                $sCurrentAdminPage = $this->getRequestParameter('page');
                $aUserGroups = $this->getUserGroups();
                foreach ($aUserGroups as $oUserGroup) {
                    ?>
                    <tr class="alternate" id="group-<?php echo $oUserGroup->getId(); ?>">
                        <th class="check-column" scope="row">
                            <input type="checkbox" value="<?php echo $oUserGroup->getId(); ?>" name="delete[]"/>
                        </th>
                        <td>
                            <strong>
                                <a href="?page=<?php echo $sCurrentAdminPage; ?>&amp;uam_action=edit_user_group&amp;userGroupId=<?php echo $oUserGroup->getId(); ?>">
                                    <?php echo htmlentities($oUserGroup->getGroupName()); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo htmlentities($oUserGroup->getGroupDesc()) ?></td>
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
                            $aObjectTypes = $oUserGroup->getObjectsFromType(\UserAccessManager\ObjectHandler\ObjectHandler::ROLE_OBJECT_TYPE);
                            if (count($aObjectTypes) > 0) {
                                ?>
                                <ul>
                                    <?php
                                    foreach ($aObjectTypes as $sKey => $sRole) {
                                        ?>
                                        <li><?php echo $sKey; ?></li>
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
        </form>
    </div>
    <?php
}
?>
<div class="wrap">
    <h2>
        <?php
        if ($this->getRequestParameter('uam_action') === 'editGroup') {
            echo TXT_UAM_EDIT_GROUP;
        } else {
            echo TXT_UAM_ADD_GROUP;
        }
        ?>
    </h2>
    <form method="post" action="<?php echo $this->getRequestUrl(); ?>">
        <input type="hidden" value="insert_update_user_group" name="uam_action"/>
        <?php
        $oUserGroup = $this->getUserGroup();
        $this->createNonceField('uamInsertUpdateGroup');
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
                    <input type="text" size="40" value="<?php echo htmlentities($oUserGroup->getGroupName()); ?>"
                           id="userGroupName" name="userGroupName"/><br/>
                    <?php echo TXT_UAM_GROUP_NAME_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="userGroupDescription"><?php echo TXT_UAM_GROUP_DESC; ?></label>
                </th>
                <td>
                    <input type="text" size="40" value="<?php echo htmlentities($oUserGroup->getGroupDesc()); ?>"
                           id="userGroupDescription" name="userGroupDescription"/><br/>
                    <?php echo TXT_UAM_GROUP_DESC_DESC; ?>
                </td>
            </tr>
            <tr class="form-field form-required">
                <th valign="top" scope="row"><label for="ipRange"><?php echo TXT_UAM_GROUP_IP_RANGE; ?></label></th>
                <td><input type="text" size="40" value="<?php echo htmlentities($oUserGroup->getIpRange('string')); ?>"
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
                        $aGroupRoles = $oUserGroup->getObjectsFromType(\UserAccessManager\ObjectHandler\ObjectHandler::ROLE_OBJECT_TYPE);
                        $aRoleNames = $this->getRoleNames();

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
                                           value="<?php echo $sRole; ?> " name="roles[]"/>
                                    <label for="role-<?php echo $sRole; ?>">
                                        <?php echo $sRole; ?>
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