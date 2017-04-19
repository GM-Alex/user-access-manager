<?php
/**
 * ObjectColumn.php
 *
 * Shows the object column at the admin panel.
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
$objectUserGroups = $controller->getObjectUserGroups();
$userGroupDiff = $controller->getUserGroupDiff();

if (count($objectUserGroups) > 0 || $userGroupDiff > 0) {
    ?>
    <ul>
        <?php
        foreach ($objectUserGroups as $userGroup) {
            ?>
            <li>
                <a class="uam_group_info_link">
                    <?php echo htmlentities($userGroup->getName()); ?>
                </a>
                <?php
                include 'GroupInfo.php';
                ?>
            </li>
            <?php
        }

        if ($userGroupDiff > 0) {
            ?>
            <li><?php echo sprintf(TXT_UAM_MEMBER_OF_OTHER_GROUPS, $userGroupDiff);?></li>
            <?php
        }
        ?>
    </ul>
    <?php
} else {
    echo TXT_UAM_FULL_ACCESS;
}