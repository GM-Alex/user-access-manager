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
if ($controller->hasUpdateMessage() === true) {
    ?>
    <div class="updated">
        <p><strong><?php echo $controller->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

$currentGroup = $controller->getCurrentTabGroup();

if ($controller->getRequestParameter('uam_action') !== 'edit_user_group') {
    ?>
    <div class="wrap">
        <h2><?php echo TXT_UAM_MANAGE_GROUP; ?></h2>
        <?php include 'TabList.php'; ?>
    </div>
    <?php
    if ($currentGroup !== \UserAccessManager\Controller\AdminUserGroupController::GROUP_DEFAULT_USER_GROUPS) {
        include 'UserGroups/UserGroupList.php';
    }
}

if ($currentGroup === \UserAccessManager\Controller\AdminUserGroupController::GROUP_DEFAULT_USER_GROUPS) {
    include 'UserGroups/DefaultUserGroupEditForm.php';
} else {
    include 'UserGroups/UserGroupEditForm.php';
}