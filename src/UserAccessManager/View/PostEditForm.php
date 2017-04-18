<?php
/**
 * PostEditForm.php
 *
 * Shows the post edit form at the admin panel.
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
$userGroups = $controller->getFilteredUserGroups();

if (count($userGroups) > 0) {
    include 'GroupSelectionForm.php';
} elseif ($controller->checkUserAccess()) {
    ?>
    <a href='?page=uam_user_group'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
    <?php
} else {
    echo TXT_UAM_NO_GROUP_AVAILABLE;
}