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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminObjectController $controller
 */
?>
<div class="uam_tooltip">
    <ul class="uam_group_info">
        <?php
        $recursiveMembership = $controller->getRecursiveMembership($userGroup);

        foreach ($recursiveMembership as $recursiveObjectType => $objects) {
            $typeKey = 'TXT_UAM_GROUP_TYPE_'.strtoupper($recursiveObjectType);
            $type = defined($typeKey) ? strtolower(constant($typeKey)) : $recursiveObjectType;
            ?>
            <li class="uam_group_info_head">
                <?php echo sprintf(TXT_UAM_GROUP_MEMBERSHIP_BY, $type); ?>:
                <ul>
                    <?php
                    foreach ($objects as $objectName) {
                        ?>
                        <li class="recursiveTree"><?php echo $objectName; ?></li>
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
                    if ($userGroup->getReadAccess() === "all") {
                        echo TXT_UAM_ALL;
                    } elseif ($userGroup->getReadAccess() === "group") {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    }
                    ?>
                </li>
                <li><?php echo TXT_UAM_WRITE_ACCESS; ?>:
                    <?php
                    if ($userGroup->getWriteAccess() === "all") {
                        echo TXT_UAM_ALL;
                    } elseif ($userGroup->getWriteAccess() === "group") {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    }
                    ?>
                </li>
                <li>
                    <?php
                    $content = TXT_UAM_GROUP_ROLE.': ';
                    $roleNames = $controller->getRoleNames();
                    $groupRoles = $userGroup->getAssignedObjectsByType(
                        \UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                    );

                    if (count($groupRoles) > 0) {
                        $cleanGroupRoles = [];

                        foreach ($groupRoles as $key => $role) {
                            $cleanGroupRoles[] = isset($roleNames[$key]) ? $roleNames[$key] : $key;
                        }

                        $content .= implode(', ', $cleanGroupRoles);
                    } else {
                        $content .= TXT_UAM_NONE;
                    }

                    echo $content;
                    ?>
                </li>
            </ul>
        </li>
    </ul>
</div>