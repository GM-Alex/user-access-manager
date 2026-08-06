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

use UserAccessManager\Controller\Backend\ObjectMembership\ObjectController;
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
                </label>
                <span class="uam_flyout">
                    <button type="button"
                            class="uam_group_info_link uam_flyout_toggle button-link"
                            aria-expanded="false"
                            aria-haspopup="true"><?php echo TXT_UAM_INFO; ?></button>
                    <?php include 'GroupInfo.php'; ?>
                </span>
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
                    <span class="uam_flyout">
                    <button type="button"
                            class="uam_group_date uam_flyout_toggle button-link"
                            aria-expanded="false"
                            aria-haspopup="true"
                            data-empty-label="<?php echo htmlentities(TXT_UAM_GROUP_ASSIGNMENT_TIME); ?>"
                            aria-controls="<?php echo $formPrefix; ?>-date-form"><?php echo $dateText; ?></button>
                    <div class="uam_group_date_form uam_flyout_panel" id="<?php echo $formPrefix; ?>-date-form">
                        <div>
                            <label class="uam_date_label" for="<?php echo $formPrefix; ?>-fromDate">
                                <?php echo TXT_UAM_GROUP_FROM_DATE; ?>
                            </label>
                            <input type="datetime-local"
                                   id="<?php echo $formPrefix; ?>-fromDate"
                                   name="<?php echo "{$groupsFormName}[{$userGroup->getId()}][fromDate]"; ?>"
                                   value="<?php echo $dateUtil->formatDateForDatetimeInput($fromDate); ?>"/>
                        </div>
                        <div>
                            <label class="uam_date_label" for="<?php echo $formPrefix; ?>-toDate">
                                <?php echo TXT_UAM_GROUP_TO_DATE; ?>
                            </label>
                            <input type="datetime-local"
                                   id="<?php echo $formPrefix; ?>-toDate"
                                   name="<?php echo "{$groupsFormName}[{$userGroup->getId()}][toDate]"; ?>"
                                   value="<?php echo $dateUtil->formatDateForDatetimeInput($toDate); ?>"/>
                        </div>
                        <p class="uam_group_date_actions">
                            <button type="button" class="uam_clear_dates button-link button-link-delete">
                                <?php echo TXT_UAM_GROUP_REMOVE_ASSIGNMENT_TIME; ?>
                            </button>
                        </p>
                    </div>
                    </span>
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
    <div class="uam_dynamic_groups">
        <label for="uam_dynamic_groups"><?php echo TXT_UAM_ADD_DYNAMIC_GROUP; ?></label>
        <input id="uam_dynamic_groups"
               type="text"
               autocomplete="off"
               value=""
               placeholder="<?php echo htmlentities(TXT_UAM_ADD_DYNAMIC_GROUP_PLACEHOLDER); ?>"
               aria-describedby="uam_dynamic_groups_description"
               data-date-label="<?php echo htmlentities(TXT_UAM_GROUP_ASSIGNMENT_TIME); ?>"
               data-from-label="<?php echo htmlentities(TXT_UAM_GROUP_FROM_DATE); ?>"
               data-to-label="<?php echo htmlentities(TXT_UAM_GROUP_TO_DATE); ?>"
               data-clear-label="<?php echo htmlentities(TXT_UAM_GROUP_REMOVE_ASSIGNMENT_TIME); ?>">
        <p class="description" id="uam_dynamic_groups_description">
            <?php echo TXT_UAM_ADD_DYNAMIC_GROUP_DESCRIPTION; ?>
        </p>
    </div>
    <?php
}