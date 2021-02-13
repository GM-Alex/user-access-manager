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
 * @var UserGroupController $controller
 */

use UserAccessManager\Controller\Backend\UserGroupController;

if ($controller->hasUpdateMessage() === true) {
    ?>
    <div class="updated">
        <p><strong><?php echo $controller->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

$currentGroup = $controller->getCurrentTabGroup();
$editUserGroup = $controller->getUserGroup()->getId() !== null;
$userGroupOverview = $currentGroup === UserGroupController::GROUP_USER_GROUPS;
$title = '';

if ($editUserGroup === false) {
    $title = TXT_UAM_MANAGE_GROUP;
} else {
    $title = TXT_UAM_EDIT_GROUP;
}
?>
<div class="wrap">
    <h2><?php echo $title; ?></h2>
    <div class="uam_sidebar">
        <?php include 'InfoBox.php'; ?>
    </div>
    <div class="uam_main">
        <?php
        if ($editUserGroup === false) {
            include 'TabList.php';

            if ($userGroupOverview === true) {
                include 'UserGroups/UserGroupList.php';
            }
        }

        if ($userGroupOverview === false) {
            include 'UserGroups/DefaultUserGroupEditForm.php';
        } else {
            if ($controller->getUserGroup()->getId() === null) {
                ?>
                <h2><?php echo TXT_UAM_ADD_GROUP; ?></h2>
                <?php
            }
            include 'UserGroups/UserGroupEditForm.php';
        }
        ?>
    </div>
</div>
