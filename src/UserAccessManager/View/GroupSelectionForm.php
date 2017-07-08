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

    /**
     * @var \UserAccessManager\UserGroup\UserGroup[] $userGroups
     */
    foreach ($userGroups as $userGroup) {
        $userGroup->setIgnoreDates(true);
        $fromDate = null;
        $toDate = null;
        $isLockedRecursive = false;
        $attributes = '';

        /**
         * @var \UserAccessManager\UserGroup\AssignmentInformation $assignmentInformation
         */
        if ($userGroup->isObjectMember($objectType, $objectId, $assignmentInformation) === true) {
            $attributes .= 'checked="checked" ';
            $fromDate = $assignmentInformation->getFromDate();
            $toDate = $assignmentInformation->getToDate();
            $isLockedRecursive = $userGroup->isLockedRecursive($objectType, $objectId);
            $attributes .= ($isLockedRecursive === true) ? ' disabled="disabled"' : '';
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
                   name="<?php echo $groupsFormName; ?>[<?php echo $userGroup->getId(); ?>][id]" />
            <label for="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>" class="selectit"
                   style="display:inline;">
                <?php echo htmlentities($userGroup->getName()).($isLockedRecursive === true ? ' [LR]' : ''); ?>
                <span class="uam_group_info_link">(<?php echo TXT_UAM_INFO; ?>)</span>
                <?php include 'GroupInfo.php'; ?>
            </label>
            <?php
            $dateText = TXT_UAM_GROUP_ASSIGNMENT_TIME;

            if ($fromDate !== null) {
                $dateText = TXT_UAM_GROUP_FROM_DATE.' '.$controller->formatDate($fromDate);
            }

            if ($toDate !== null) {
                $dateText = ($dateText !== TXT_UAM_GROUP_ASSIGNMENT_TIME) ? $dateText.'<br>' : '';
                $dateText .= TXT_UAM_GROUP_TO_DATE.' '.$controller->formatDate($toDate);
            }

            if ($isLockedRecursive === false) {
                ?>
                <span class="uam_group_date"><?php echo $dateText; ?></span>
                <div class="uam_group_date_form">
                    <div>
                        <label for="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>-fromDate">
                            <?php echo TXT_UAM_GROUP_FROM_DATE; ?>
                        </label>
                        <input type="datetime-local"
                               id="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>-fromDate"
                               name="<?php echo $groupsFormName; ?>[<?php echo $userGroup->getId(); ?>][fromDate]"
                               value="<?php echo $controller->formatDateForDatetimeInput($fromDate); ?>"/>
                    </div>
                    <div>
                        <label for="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>-toDate">
                            <?php echo TXT_UAM_GROUP_TO_DATE; ?>
                        </label>
                        <input type="datetime-local"
                               id="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>-toDate"
                               name="<?php echo $groupsFormName; ?>[<?php echo $userGroup->getId(); ?>][toDate]"
                               value="<?php echo $controller->formatDateForDatetimeInput($toDate); ?>"/>
                    </div>
                </div>
                <?php
            }
            ?>
        </li>
        <?php
    }
    ?>
</ul>
<p>
    <span><label for="uam_dynamic_groups"><?php echo TXT_UAM_ADD_DYNAMIC_GROUP; ?></label></span>
    <input type="text"
           id="uam_dynamic_groups"
           class="form-input-tip ui-autocomplete-input"
           autocomplete="off"
           value=""
           role="combobox" >
</p>