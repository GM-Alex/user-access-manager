<?php
/**
 * GroupSelectionFrom.php
 *
 * Shows the group selection form at the admin panel.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
?>
<input type="hidden" name="uam_update_groups" value="1"/>
<ul class="uam_group_selection">
    <?php
    /**
     * @var \UserAccessManager\Controller\AdminObjectController $controller
     */

    $groupsFormName = $controller->getGroupsFormName();
    $objectType = $controller->getObjectType();
    $objectId = $controller->getObjectId();
    $userGroups = $controller->getFilteredUserGroups();
    $objectUserGroups = $controller->getObjectUserGroups();

    /**
     * @var \UserAccessManager\UserGroup\UserGroup[] $userGroups
     */
    foreach ($userGroups as $userGroup) {
        $addition = '';
        $attributes = '';

        /**
         * @var \UserAccessManager\UserGroup\UserGroup[] $objectUserGroups
         */
        if (isset($objectUserGroups[$userGroup->getId()]) === true) {
            $attributes .= 'checked="checked" ';

            if ($objectUserGroups[$userGroup->getId()]->isLockedRecursive($objectType, $objectId) === true) {
                $attributes .= 'disabled="disabled" ';
                $addition .= ' [LR]';
            }
        } elseif ($userGroup instanceof \UserAccessManager\UserGroup\DynamicUserGroup
            && $userGroup->getId() !== \UserAccessManager\UserGroup\DynamicUserGroup::USER_TYPE.'|0'
        ) {
            continue;
        }

        ?>
        <li>
            <input type="checkbox"
                   id="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>" <?php echo $attributes; ?>
                   value="<?php echo $userGroup->getId(); ?>"
                   name="<?php echo $groupsFormName; ?>[<?php echo $userGroup->getId(); ?>]" />
            <label for="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>" class="selectit"
                   style="display:inline;">
                <?php echo htmlentities($userGroup->getName()).$addition; ?>
            </label>
            <a class="uam_group_info_link">(<?php echo TXT_UAM_INFO; ?>)</a>
            <?php include 'GroupInfo.php'; ?>
            <input type="datetime-local"
                   name="<?php echo $groupsFormName; ?>[<?php echo $userGroup->getId(); ?>][fromDate]" />
            <input type="datetime-local"
                   name="<?php echo $groupsFormName; ?>[<?php echo $userGroup->getId(); ?>][toDate]" />
        </li>
        <?php
    }
    ?>
</ul>
<p>
    <span><?php echo TXT_UAM_ADD_DYNAMIC_GROUP; ?></span>
    <input type="text"
           id="uam_dynamic_groups"
           class="form-input-tip ui-autocomplete-input"
           autocomplete="off"
           value=""
           role="combobox" >
</p>