<?php
/**
 * postColumn.php
 * 
 * Shows the setup page at the admin panel.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2010 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

$objectId = $id;
global $userAccessManager;

$uamUserGroups 
    = $userAccessManager->getAccessHandler()->getUsergroupsForPost($objectId);
$userGroupsForObject = &$uamUserGroups;
$uamUserGroupsFull
    = $userAccessManager->getAccessHandler()->getUsergroupsForCategory($objectId, false);
$groupDiff = count($uamUserGroupsFull) - count($uamUserGroups);

if ($uamUserGroups != Array()) {
    ?>
	<ul>
    <?php
    foreach ($uamUserGroups as $uamUserGroup) {
        ?> 
    	<li>
    	    <a class="uam_group_info_link">
    		    <?php echo $uamUserGroup->getGroupName(); ?>
    		</a>
        <?php
        $type = 'post'; 
        include 'groupInfo.php';
        ?> 
        </li>
        <?php
    }
    ?>
	</ul>
    <?php
} elseif ($groupDiff > 0) {
    echo TXT_MEMBER_OF_OTHER_GROUPS;
} else {
    echo TXT_FULL_ACCESS;
}