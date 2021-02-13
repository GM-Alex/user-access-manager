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

use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;

?>
    <input type="hidden" name="uam_update_groups" value="1"/>
    <ul class="uam_group_selection">
        <?php
        /**
         * @var ObjectController $controller
         */

        $groupsFormName = $controller->getGroupsFormName();
        $objectType = $controller->getObjectInformation()->getObjectType();
        $objectId = $controller->getObjectInformation()->getObjectId();
        $userGroups = $controller->getFilteredUserGroups();
        $controller->sortUserGroups($userGroups);
        $dateUtil = $controller->getDateUtil();

        /**
         * @var UserGroup[] $userGroups
         */
        foreach ($userGroups as $userGroup) {
            $userGroup->setIgnoreDates(true);
            $fromDate = null;
            $toDate = null;
            $isLockedRecursive = false;
            $attributes = '';

            /**
             * @var AssignmentInformation $assignmentInformation
             */
            if ($userGroup->isObjectMember($objectType, $objectId, $assignmentInformation) === true) {
                $attributes .= 'checked="checked" ';
                $fromDate = $assignmentInformation->getFromDate();
                $toDate = $assignmentInformation->getToDate();
                $isLockedRecursive = $userGroup->isLockedRecursive($objectType, $objectId);
                $attributes .= ($isLockedRecursive === true) ? ' disabled="disabled"' : '';
            } elseif ($controller->isNewObject()
                && $userGroup->isDefaultGroupForObjectType($objectType, $fromTime, $toTime) === true
            ) {
                $attributes .= 'checked="checked" ';
                $fromDate = $dateUtil->getDateFromTime($fromTime);
                $toDate = $dateUtil->getDateFromTime($toTime);
            } elseif ($userGroup instanceof DynamicUserGroup
                && $userGroup->getId() !== DynamicUserGroup::USER_TYPE . '|0'
            ) {
                continue;
            }

            ?>
            <li>
                <input type="checkbox"
                       id="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>" <?php echo $attributes; ?>
                       value="<?php echo $userGroup->getId(); ?>"
                       name="<?php echo $groupsFormName; ?>[<?php echo $userGroup->getId(); ?>][id]"/>
                <label for="<?php echo $groupsFormName; ?>-<?php echo $userGroup->getId(); ?>" class="selectit"
                       style="display:inline;">
                    <?php echo htmlentities($userGroup->getName()) . ($isLockedRecursive === true ? ' [LR]' : ''); ?>
                    <span class="uam_group_info_link">(<?php echo TXT_UAM_INFO; ?>)</span>
                    <?php include 'GroupInfo.php'; ?>
                </label>
                <?php
                $dateText = TXT_UAM_GROUP_ASSIGNMENT_TIME;

                if ($fromDate !== null) {
                    $dateText = TXT_UAM_GROUP_FROM_DATE . ' ' . $dateUtil->formatDate($fromDate);
                }

                if ($toDate !== null) {
                    $dateText = ($dateText !== TXT_UAM_GROUP_ASSIGNMENT_TIME) ? $dateText . '<br>' : '';
                    $dateText .= TXT_UAM_GROUP_TO_DATE . ' ' . $dateUtil->formatDate($toDate);
                }

                if ($isLockedRecursive === false) {
                    $formPrefix = $groupsFormName . '-' . $userGroup->getId();
                    ?>
                    <span class="uam_group_date"><?php echo $dateText; ?></span>
                    <div class="uam_group_date_form">
                        <div>
                            <label class="uam_date_label" for="<?php echo $formPrefix; ?>-fromDate-date">
                                <?php echo TXT_UAM_GROUP_FROM_DATE; ?>
                            </label>
                            <input type="date"
                                   id="<?php echo $formPrefix ?>-fromDate-date"
                                   name="<?php echo "{$groupsFormName}[{$userGroup->getId()}][fromDate][date]"; ?>"
                                   value="<?php echo $dateUtil->formatDateForDateInput($fromDate); ?>"/>
                            <label for="<?php echo $formPrefix; ?>-fromDate-time">@</label>
                            <input type="time"
                                   id="<?php echo $formPrefix; ?>-fromDate-time"
                                   name="<?php echo "{$groupsFormName}[{$userGroup->getId()}][fromDate][time]"; ?>"
                                   value="<?php echo $dateUtil->formatDateForTimeInput($fromDate); ?>"/>
                        </div>
                        <div>
                            <label class="uam_date_label" for="<?php echo $formPrefix; ?>-toDate-date">
                                <?php echo TXT_UAM_GROUP_TO_DATE; ?>
                            </label>
                            <input type="date"
                                   id="<?php echo $formPrefix ?>-toDate-date"
                                   name="<?php echo "{$groupsFormName}[{$userGroup->getId()}][toDate][date]"; ?>"
                                   value="<?php echo $dateUtil->formatDateForDateInput($toDate); ?>"/>
                            <label for="<?php echo $formPrefix; ?>-toDate-time">@</label>
                            <input type="time"
                                   id="<?php echo $formPrefix; ?>-toDate-time"
                                   name="<?php echo "{$groupsFormName}[{$userGroup->getId()}][toDate][time]"; ?>"
                                   value="<?php echo $dateUtil->formatDateForTimeInput($toDate); ?>"/>
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
<?php
if ($controller->getObjectInformation()->getObjectType() !==
    ObjectHandler::GENERAL_USER_OBJECT_TYPE
    && $controller->checkUserAccess() === true
) {
    ?>
    <p>
        <span><label for="uam_dynamic_groups"><?php echo TXT_UAM_ADD_DYNAMIC_GROUP; ?></label></span>
        <input id="uam_dynamic_groups"
               class="form-input-tip ui-autocomplete-input"
               autocomplete="off"
               value=""
               role="combobox">
    </p>
    <?php
}