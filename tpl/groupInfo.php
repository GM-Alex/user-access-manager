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
     * @param mixed  $object The object.
     * @param string $type   The type of the object.
     * 
     * @return string
     */
    function walkPath($object, $type)
    {
        $out = '';
        
        if (is_object($object)) {            
            if ($type == 'post') {
                $post = get_post($object->ID);
            	$out = $post->post_title;
    	    } elseif ($type == 'category') {
    	        $category = get_category($object->cat_id);
        	    $out = $category->name;
    	    }
            
    	    
            if (isset($object->recursiveMember['byPost'])) {
                $out .= '<ul>';
                foreach ($object->recursiveMember['byPost'] as $post) {
                    $out .= '<li>';
                    $out .= walkPath($post, 'post');
                    $out .= '</li>';
                }
                $out .= '</ul>';
            }
            
            if (isset($object->recursiveMember['byCategory'])) {
                $out .= '<ul>';
                foreach ($object->recursiveMember['byCategory'] as $category) {
                    $out .= '<li>';
                    $out .= walkPath($category, 'category');
                    $out .= '</li>';
                }
                $out .= '</ul>';
            }
    	} else {
    	    if ($type == 'post') {
                $post = get_post($object);
            	$out = $post->post_title;
    	    } elseif ($type == 'category') {
    	        $category = get_category($object);
        	    $out = $category->name;
    	    }
    	}
    	
    	return $out;
    }
}
?>
<div class="tooltip">
<ul class="uam_group_info">
<?php 
if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectId]['byCategory'])) {
    ?>
	<li  class="uam_group_info_head">
		<?php echo TXT_GROUP_MEMBERSHIP_BY_CATEGORIES; ?>:
		<ul>
	<?php
	foreach ($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectId]['byCategory'] as $category) {
	    ?>
	    	<li class="recusiveTree"><?php echo walkPath($category, 'category'); ?></li>
	    <?php
	}
	?>
		</ul>
	</li>
    <?php 
}
?>
<?php 
if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectId]['byPost'])) {
    ?>
	<li  class="uam_group_info_head">
		<?php echo TXT_GROUP_MEMBERSHIP_BY_POSTS; ?>:
		<ul>
	<?php 
	foreach ($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectId]['byPost'] as $post) {
	    ?>
	    	<li class="recusiveTree"><?php echo walkPath($post, 'post'); ?></li>
	    <?php
	}
	?>
		</ul>
	</li>
    <?php 
}
?>
<?php 
if (isset($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectId]['byRole'])) {
    ?>
	<li  class="uam_group_info_head">
		<?php echo TXT_GROUP_MEMBERSHIP_BY_ROLE; ?>:
		<ul>
	<?php 
	foreach ($userGroupsForObject[$uamUserGroup->getId()]->setRecursive[$objectId]['byRole'] as $role) {
	    ?>
	    	<li><?php echo $role; ?></li>
	    <?php
    }
	?>
		</ul>
	</li>
    <?php 
}
?>
	<li class="uam_group_info_head"><?php echo TXT_GROUP_INFO; ?>:
		<ul>
			<li><?php echo TXT_READ_ACCESS; ?>:
<?php
if ($uamUserGroup->getReadAccess() == "all") {
    echo TXT_ALL;
} elseif ($uamUserGroup->getReadAccess() == "group") {
    echo TXT_ONLY_GROUP_USERS;
}
?>
			</li>
			<li><?php echo TXT_WRITE_ACCESS; ?>:
<?php
if ($uamUserGroup->getWriteAccess()  == "all") {
    echo TXT_ALL;   
} elseif ($uamUserGroup->getWriteAccess()  == "group") {
    echo TXT_ONLY_GROUP_USERS;
}
?>
        	</li>
        	<li>
        	    <?php echo TXT_GROUP_ROLE; ?>: <?php
if ($uamUserGroup->getRoles()) {
    $out = '';
    
    foreach ($uamUserGroup->getRoles() as $role) {
        $out .= trim($role['role_name']).', ';
    }
    
    echo rtrim($out, ', ');
} else {
    echo TXT_NONE;
}
?>
        	</li>
		</ul>
	</li>
</ul>
</div>