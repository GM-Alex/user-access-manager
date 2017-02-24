<?php
/**
 * GroupInfo.php
 *
 * Shows the user group info at the admin panel.
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
if (!function_exists('walkPath')) {
    /**
     * Returns the html code for the recursive access.
     *
     * @param \UserAccessManager\UserGroup\UserGroup $oUserGroup
     * @param int                                    $iObjectId
     * @param string                                 $sObjectType The type of the object.
     *
     * @return string
     */
    function walkPath($oUserGroup, $iObjectId, $sObjectType)
    {
        $sOut = "$sObjectType: $iObjectId";

        $aRecursiveMembership = $oUserGroup->getRecursiveMembershipForObject($sObjectType, $iObjectId, $sObjectType);

        if (count($aRecursiveMembership) > 0) {
            $sOut .= '<ul>';

            foreach ($aRecursiveMembership as $iRecursiveObjectId) {
                $sOut .= '<li>';
                $sOut .= walkPath($oUserGroup, $iRecursiveObjectId, $sObjectType);
                $sOut .= '</li>';
            }

            $sOut .= '</ul>';
        }

        return $sOut;
    }
}
?>
<div class="uam_tooltip">
    <ul class="uam_group_info">
        <?php
        $aAllObjectTypes = $this->getAllObjectTypes();
        $sObjectType = $this->getObjectType();
        $sObjectId = $this->getObjectId();

        foreach ($aAllObjectTypes as $sObjectType) {
            $aRecursiveMembership = $oUserGroup->getRecursiveMembershipForObject($sObjectType, $sObjectId, $sObjectType);

            if (count($aRecursiveMembership) > 0) {
                ?>
                <li class="uam_group_info_head">
                    <?php echo constant('TXT_UAM_GROUP_MEMBERSHIP_BY_'.strtoupper($sObjectType)); ?>:
                    <ul>
                        <?php
                        foreach ($aRecursiveMembership as $iObjectId) {
                            ?>
                            <li class="recursiveTree"><?php echo walkPath($oUserGroup, $iObjectId, $sObjectType); ?></li>
                            <?php
                        }
                        ?>
                    </ul>
                </li>
                <?php
            }
        }
        ?>
        <li class="uam_group_info_head"><?php echo TXT_UAM_GROUP_INFO; ?>:
            <ul>
                <li><?php echo TXT_UAM_READ_ACCESS; ?>:
                    <?php
                    if ($oUserGroup->getReadAccess() === "all") {
                        echo TXT_UAM_ALL;
                    } elseif ($oUserGroup->getReadAccess() === "group") {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    }
                    ?>
                </li>
                <li><?php echo TXT_UAM_WRITE_ACCESS; ?>:
                    <?php
                    if ($oUserGroup->getWriteAccess() === "all") {
                        echo TXT_UAM_ALL;
                    } elseif ($oUserGroup->getWriteAccess() === "group") {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    }
                    ?>
                </li>
                <li>
                    <?php
                    $sContent = TXT_UAM_GROUP_ROLE.': ';
                    $aRoleNames = $this->getRoleNames();
                    $aGroupRoles = $oUserGroup->getObjectsByType(\UserAccessManager\ObjectHandler\ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

                    if (count($aGroupRoles) > 0) {
                        $aCleanGroupRoles = array();

                        foreach ($aGroupRoles as $sKey => $sRole) {
                            $aCleanGroupRoles[] = isset($aRoleNames[$sKey]) ? $aRoleNames[$sKey] : $sKey;
                        }

                        $sContent .= implode(', ', $aCleanGroupRoles);
                    } else {
                        $sContent .= TXT_UAM_NONE;
                    }

                    echo $sContent;
                    ?>
                </li>
            </ul>
        </li>
    </ul>
</div>