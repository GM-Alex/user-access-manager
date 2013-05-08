<?php
/**
 * objectColumn.php
 * 
 * Shows the setup page at the admin panel.
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

global $oUserAccessManager;

$aUamUserGroups = $oUserAccessManager->getAccessHandler()->getUsergroupsForObject($sObjectType, $iObjectId);
$aUserGroupsForObject = $aUamUserGroups;
$aUamUserGroupsFull = $oUserAccessManager->getAccessHandler()->getUsergroupsForObject($sObjectType, $iObjectId, false);
$iGroupDiff = count($aUamUserGroupsFull) - count($aUamUserGroups);

if ($aUamUserGroups != array()) {
    ?>
	<ul>
    <?php
    foreach ($aUamUserGroups as $oUamUserGroup) {
        ?> 
    	<li>
    	    <a class="uam_group_info_link">
    		    <?php echo $oUamUserGroup->getGroupName(); ?>
    		</a>
        <?php
        include 'groupInfo.php';
        ?> 
        </li>
        <?php
    }
    ?>
	</ul>
    <?php
} elseif ($iGroupDiff > 0) {
    echo TXT_UAM_MEMBER_OF_OTHER_GROUPS;
} else {
    echo TXT_UAM_FULL_ACCESS;
}