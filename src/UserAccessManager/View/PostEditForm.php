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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminObjectController $this
 */
$aUserGroups = $this->getUserGroups();
$aObjectUserGroups = $this->getObjectUserGroups();

if (count($aUserGroups) > 0) {
    include 'GroupSelectionForm.php';
} elseif ($this->checkUserAccess()) {
    ?>
    <a href='admin.php?page=uam_user_group'><?php echo TXT_UAM_CREATE_GROUP_FIRST; ?></a>
    <?php
} else {
    echo TXT_UAM_NO_GROUP_AVAILABLE;
}