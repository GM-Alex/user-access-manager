<?php
/**
 * UserColumn.php
 *
 * Shows the user column at the admin panel.
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
 * @var ObjectController $controller
 */

use UserAccessManager\Controller\Backend\ObjectMembership\ObjectController;

$objectUserGroups = $controller->getObjectInformation()->getObjectUserGroups();
$controller->sortUserGroups($objectUserGroups);

if (count($objectUserGroups) > 0) {
    ?>
    <ul>
        <?php
        foreach ($objectUserGroups as $userGroup) {
            ?>
            <li>
                <span class="uam_flyout">
                    <button type="button"
                            class="uam_group_info_link uam_flyout_toggle button-link"
                            aria-expanded="false"
                            aria-haspopup="true"><?php echo htmlentities($userGroup->getName()); ?></button>
                <?php include 'GroupInfo.php'; ?>
                </span>
            </li>
            <?php
        }
        ?>
    </ul>
    <?php
} else {
    if (!$controller->isCurrentUserAdmin()) {
        echo TXT_UAM_NONE;
    } else {
        echo TXT_UAM_ADMIN_HINT;
    }
}