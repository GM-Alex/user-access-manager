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
* @copyright 2008-2010 Alexander Schneider
* @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
* @version   SVN: $Id$
* @link      http://wordpress.org/extend/plugins/user-access-manager/
*/

if (!function_exists('walkPath')) {
    /**
     * Retruns the html code for the recursive access.
     * 
     * @param mixed  $object     The object.
     * @param string $objectType The type of the object.
     * 
     * @return string
     */
    function walkPath($object, $objectType)
    {
        $out = $object->name;
        
        if (isset($object->recursiveMember[$objectType])) {            
            $out .= '<ul>';
            
            foreach ($object->recursiveMember[$objectType] as $recursiveObject) {
                $out .= '<li>';
                $out .= walkPath($recursiveObject, $objectType);
                $out .= '</li>';
            }
            
            $out .= '</ul>';
    	}
    	
    	return $out;
    }
}
?>
<div class="tooltip">
<ul class="uam_group_info">
<?php
global $userAccessManager;

foreach ($userAccessManager->getAccessHandler()->getAllObjectTypes() as $curObjectType) {
    if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectType][$objectId][$curObjectType])) {
        ?>
		<li  class="uam_group_info_head">
		<?php echo constant('TXT_UAM_GROUP_MEMBERSHIP_BY_'.strtoupper($curObjectType)); ?>:
			<ul>
	    <?php
	    foreach ($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectType][$objectId][$curObjectType] as $object) {
	        ?>
	    		<li class="recusiveTree"><?php echo walkPath($object, $curObjectType); ?></li>
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
if ($uamUserGroup->getReadAccess() == "all") {
    echo TXT_UAM_ALL;
} elseif ($uamUserGroup->getReadAccess() == "group") {
    echo TXT_UAM_ONLY_GROUP_USERS;
}
?>
			</li>
			<li><?php echo TXT_UAM_WRITE_ACCESS; ?>:
<?php
if ($uamUserGroup->getWriteAccess()  == "all") {
    echo TXT_UAM_ALL;   
} elseif ($uamUserGroup->getWriteAccess()  == "group") {
    echo TXT_UAM_ONLY_GROUP_USERS;
}
?>
        	</li>
        	<li>
        	    <?php echo TXT_UAM_GROUP_ROLE; ?>: <?php
if ($uamUserGroup->getObjectsFromType('role')) {
    $out = '';
    
    foreach ($uamUserGroup->getObjectsFromType('role') as $key => $role) {
        $out .= trim($key).', ';
    }
    
    echo rtrim($out, ', ');
} else {
    echo TXT_UAM_NONE;
}
?>
        	</li>
		</ul>
	</li>
</ul>
</div>