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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
?>
<input type="hidden" name="uam_update_groups" value="true"/>
<ul class="uam_group_selection">
    <?php
    /**
     * @var \UserAccessManager\Controller\AdminObjectController $Controller
     */
    if (!isset($sGroupsFormName)
        || $sGroupsFormName === null
    ) {
        $sGroupsFormName = 'uam_user_groups';
    }

    $sObjectType = $Controller->getObjectType();
    $sObjectId = $Controller->getObjectId();

    /**
     * @var \UserAccessManager\UserGroup\UserGroup[] $aUserGroups
     */
    foreach ($aUserGroups as $UserGroup) {
        $sAddition = '';
        $sAttributes = '';

        /**
         * @var \UserAccessManager\UserGroup\UserGroup[] $aObjectUserGroups
         */
        if (isset($aObjectUserGroups[$UserGroup->getId()])) {
            $sAttributes .= 'checked="checked" ';

            if ($aObjectUserGroups[$UserGroup->getId()]->isLockedRecursive($sObjectType, $sObjectId)) {
                $sAttributes .= 'disabled="disabled" ';
                $sAddition .= ' [LR]';
            }
        }


        ?>
        <li>
            <input type="checkbox"
                   id="<?php echo $sGroupsFormName; ?>-<?php echo $UserGroup->getId(); ?>" <?php echo $sAttributes; ?>
                   value="<?php echo $UserGroup->getId(); ?>" name="<?php echo $sGroupsFormName; ?>[]"/>
            <label for="<?php echo $sGroupsFormName; ?>-<?php echo $UserGroup->getId(); ?>" class="selectit"
                   style="display:inline;">
                <?php echo htmlentities($UserGroup->getName()).$sAddition; ?>
            </label>
            <a class="uam_group_info_link">(<?php echo TXT_UAM_INFO; ?>)</a>
            <?php include 'GroupInfo.php'; ?>
        </li>
        <?php
    }
    ?>
</ul>