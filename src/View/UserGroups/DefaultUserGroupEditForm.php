<?php
/**
 * DefaultUserGroupEditForm.php
 *
 * Shows the default user group edit form at the admin panel.
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
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;

?>
<form method="post" action="<?php echo $controller->getRequestUrl(); ?>">
    <input type="hidden" value="set_default_user_groups" name="uam_action"/>
    <?php $controller->createNonceField($controller::SET_DEFAULT_USER_GROUPS_NONCE); ?>
    <ul class="uam_group_selection uam_default_groups">
        <?php
        $objectType = $controller->getCurrentTabGroupSection();
        $userGroups = $controller->getUserGroups();

        /**
         * @var UserGroup[] $userGroups
         */
        foreach ($userGroups as $userGroup) {
            /**
             * @var AssignmentInformation $assignmentInformation
             */
            if ($userGroup instanceof DynamicUserGroup
                && $userGroup->getId() !== DynamicUserGroup::USER_TYPE . '|0'
            ) {
                continue;
            }

            $attributes = ($userGroup->isDefaultGroupForObjectType($objectType, $fromTime, $toTime) === true) ?
                'checked="checked"' : '';

            ?>
            <li>
                <input type="checkbox" <?php echo $attributes; ?>
                       id="defaultGroups-<?php echo $userGroup->getId(); ?>"
                       value="<?php echo $userGroup->getId(); ?>"
                       name="default_user_groups[<?php echo $userGroup->getId(); ?>][id]"/>
                <label for="defaultGroups-<?php echo $userGroup->getId(); ?>"
                       class="selectit"
                       style="display:inline;">
                    <?php echo htmlentities($userGroup->getName()); ?>
                </label>
                <div class="uam_group_date_form">
                    <div>
                        <label for="defaultGroups-<?php echo $userGroup->getId(); ?>-fromTime">
                            <?php echo TXT_UAM_GROUP_FROM_TIME; ?>
                        </label>
                        <input id="defaultGroups-<?php echo $userGroup->getId(); ?>-fromTime"
                               name="default_user_groups[<?php echo $userGroup->getId(); ?>][fromTime]"
                               class="uam_time_input"
                               value="<?php echo $fromTime; ?>"
                               placeholder="ddd-hh:mm:ss"/>
                    </div>
                    <div>
                        <label for="defaultGroups-<?php echo $userGroup->getId(); ?>-toTime">
                            <?php echo TXT_UAM_GROUP_TO_TIME; ?>
                        </label>
                        <input id="defaultGroups-<?php echo $userGroup->getId(); ?>-toTime"
                               name="default_user_groups[<?php echo $userGroup->getId(); ?>][toTime]"
                               class="uam_time_input"
                               value="<?php echo $toTime; ?>"
                               placeholder="ddd-hh:mm:ss"/>
                    </div>
                </div>
            </li>
            <?php
        }
        ?>
    </ul>
    <p class="submit">
        <input type="submit"
               value="<?php echo TXT_UAM_UPDATE_DEFAULT_USER_GROUPS; ?>"
               name="submit" class="button"/>
    </p>
</form>