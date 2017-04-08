<?php
/**
 * GroupInfo.php
 *
 * Shows the user group info at the admin panel.
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
 * @var \UserAccessManager\Controller\AdminObjectController $Controller
 */
?>
<div class="uam_tooltip">
    <ul class="uam_group_info">
        <?php
        $aRecursiveMembership = $Controller->getRecursiveMembership($UserGroup);

        foreach ($aRecursiveMembership as $sObjectType => $aObjects) {
            $sTypeKey = 'TXT_UAM_GROUP_TYPE_'.strtoupper($sObjectType);
            $sType = defined($sTypeKey) ? strtolower(constant($sTypeKey)) : $sObjectType;
            ?>
            <li class="uam_group_info_head">
                <?php echo sprintf(TXT_UAM_GROUP_MEMBERSHIP_BY, $sType); ?>:
                <ul>
                    <?php
                    foreach ($aObjects as $sObjectName) {
                        ?>
                        <li class="recursiveTree"><?php echo $sObjectName; ?></li>
                        <?php
                    }
                    ?>
                </ul>
            </li>
            <?php
        }
        ?>
        <li class="uam_group_info_head"><?php echo TXT_UAM_GROUP_INFO; ?>:
            <ul>
                <li><?php echo TXT_UAM_READ_ACCESS; ?>:
                    <?php
                    if ($UserGroup->getReadAccess() === "all") {
                        echo TXT_UAM_ALL;
                    } elseif ($UserGroup->getReadAccess() === "group") {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    }
                    ?>
                </li>
                <li><?php echo TXT_UAM_WRITE_ACCESS; ?>:
                    <?php
                    if ($UserGroup->getWriteAccess() === "all") {
                        echo TXT_UAM_ALL;
                    } elseif ($UserGroup->getWriteAccess() === "group") {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    }
                    ?>
                </li>
                <li>
                    <?php
                    $sContent = TXT_UAM_GROUP_ROLE.': ';
                    $aRoleNames = $Controller->getRoleNames();
                    $aGroupRoles = $UserGroup->getAssignedObjectsByType(
                        \UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                    );

                    if (count($aGroupRoles) > 0) {
                        $aCleanGroupRoles = [];

                        foreach ($aGroupRoles as $sKey => $sRole) {
                            $aCleanGroupRoles[] = isset($aRoleNames[$sKey]) ? $aRoleNames[$sKey] : $sKey;
                        }

                        $sContent .= implode(', ', $aCleanGroupRoles);
                    } else {
                        $sContent .= TXT_UAM_NONE;
                    }

                    echo $sContent;
                    ?>
                </li>
            </ul>
        </li>
    </ul>
</div>