<?php
/**
* groupInfo.php
* 
* Shows the group informations at the admim panel.
* 
* PHP versions 5
* 
* @category  UserAccessManager
* @package   UserAccessManager
* @author    Alexander Schneider <alexanderschneider85@googlemail.com>
* @copyright 2008-2013 Alexander Schneider
* @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
* @version   SVN: $Id$
* @link      http://wordpress.org/extend/plugins/user-access-manager/
*/

if (!function_exists('walkPath')) {
    /**
     * Retruns the html code for the recursive access.
     * 
     * @param mixed  $oObject     The object.
     * @param string $sObjectType The type of the object.
     * 
     * @return string
     */
    function walkPath($oObject, $sObjectType)
    {
        $sOut = $oObject->name;
        
        if (isset($oObject->recursiveMember[$sObjectType])) {
            $sOut .= '<ul>';
            
            foreach ($oObject->recursiveMember[$sObjectType] as $oRecursiveObject) {
                $sOut .= '<li>';
                $sOut .= walkPath($oRecursiveObject, $sObjectType);
                $sOut .= '</li>';
            }
            
            $sOut .= '</ul>';
        }

        return $sOut;
    }
}
?>
<div class="tooltip">
<ul class="uam_group_info">
<?php
global $oUserAccessManager;

foreach ($oUserAccessManager->getAccessHandler()->getAllObjectTypes() as $sCurObjectType) {
    if (isset($aUserGroups[$oUamUserGroup->getId()])) {
        $aRecursiveMembership = $aUserGroups[$oUamUserGroup->getId()]->getRecursiveMembershipForObjectType($sObjectType, $iObjectId, $sCurObjectType);

        if (count($aRecursiveMembership) > 0) {
            ?>
            <li  class="uam_group_info_head">
            <?php echo constant('TXT_UAM_GROUP_MEMBERSHIP_BY_'.strtoupper($sCurObjectType)); ?>:
                <ul>
            <?php
            foreach ($aRecursiveMembership as $oObject) {
                ?>
                    <li class="recusiveTree"><?php echo walkPath($oObject, $sCurObjectType); ?></li>
                <?php
            }
            ?>
                </ul>
            </li>
            <?php
        }
    }
}
?>
    <li class="uam_group_info_head"><?php echo TXT_UAM_GROUP_INFO; ?>:
        <ul>
            <li><?php echo TXT_UAM_READ_ACCESS; ?>:
<?php
if ($oUamUserGroup->getReadAccess() == "all") {
    echo TXT_UAM_ALL;
} elseif ($oUamUserGroup->getReadAccess() == "group") {
    echo TXT_UAM_ONLY_GROUP_USERS;
}
?>
            </li>
            <li><?php echo TXT_UAM_WRITE_ACCESS; ?>:
<?php
if ($oUamUserGroup->getWriteAccess()  == "all") {
    echo TXT_UAM_ALL;   
} elseif ($oUamUserGroup->getWriteAccess()  == "group") {
    echo TXT_UAM_ONLY_GROUP_USERS;
}
?>
            </li>
            <li>
                <?php echo TXT_UAM_GROUP_ROLE; ?>: <?php
if ($oUamUserGroup->getObjectsFromType('role')) {
    $sOut = '';
    
    foreach ($oUamUserGroup->getObjectsFromType('role') as $sKey => $sRole) {
        $sOut .= trim($sKey).', ';
    }
    
    echo rtrim($sOut, ', ');
} else {
    echo TXT_UAM_NONE;
}
?>
            </li>
        </ul>
    </li>
</ul>
</div>