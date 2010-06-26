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

$usergroup = new UamUserGroup($groupId);
?>

<a class="uam_group_info_link">(<?php echo TXT_INFO; ?>)</a>
<ul class="uam_group_info">
	<li class="uam_group_info_head"><?php echo TXT_GROUP_INFO; ?>:</li>
	<li><?php echo TXT_READ_ACCESS; ?>:
<?php
if ($usergroup->getReadAccess() == "all") {
    echo TXT_ALL;
} elseif ($usergroup->getReadAccess() == "group") {
    echo TXT_ONLY_GROUP_USERS;
}
?>
	</li>
	<li><?php echo TXT_WRITE_ACCESS; ?>:
<?php
if ($usergroup->getWriteAccess()  == "all") {
    echo TXT_ALL;   
} elseif ($usergroup->getWriteAccess()  == "group") {
    echo TXT_ONLY_GROUP_USERS;
}
?>
	</li>
	<li><?php count($usergroup->getPosts()) . " " . TXT_POSTS; ?></li>
	<li><?php count($usergroup->getPages()) . " " . TXT_PAGES; ?></li>
	<li><?php count($usergroup->getPages()) . " " . TXT_PAGES; ?></li>
	<li><?php count($usergroup->getCategories()) . " " . TXT_CATEGORY; ?></li>
	<li><?php count($usergroup->getUsers()) . " " . TXT_USERS; ?></li>
</ul>