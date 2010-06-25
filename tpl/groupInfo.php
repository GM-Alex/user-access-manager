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

$link = '<a class="uam_group_info_link">(' . TXT_INFO . ')</a>';
$group_info = $this->get_usergroup_info($group_id);
$content = "<ul class='uam_group_info'";

if ($style != null) {
    $content.= " style='" . $style . "' ";   
}

$content.= "><li class='uam_group_info_head'>" . TXT_GROUP_INFO . ":</li>";
$content.= "<li>" . TXT_READ_ACCESS . ": ";

if ($group_info->group['read_access'] == "all") {
    $content.= TXT_ALL;
} elseif ($group_info->group['read_access'] == "group") {
    $content.= TXT_ONLY_GROUP_USERS;
}

$content.= "</li>";
$content.= "<li>" . TXT_WRITE_ACCESS . ": ";

if ($group_info->group['write_access'] == "all") {
    $content.= TXT_ALL;   
} elseif ($group_info->group['write_access'] == "group") {
    $content.= TXT_ONLY_GROUP_USERS;
}

$content.= "</li>";

if (isset($group_info->posts)) {
    $expandcontent = null;

    foreach ($group_info->posts as $post) {
        $expandcontent.= "<li>" . $post->post_title . "</li>";
    }

    $content.= "<li><a class='uam_info_link'>" . count($group_info->posts) . " " . TXT_POSTS . "</a>";
    $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
} else {
    $content.= "<li>" . TXT_NONE . " " . TXT_POSTS . "</li>";
}

if (isset($group_info->pages)) {
    $expandcontent = null;
    
    foreach ($group_info->pages as $page) {
        $expandcontent.= "<li>" . $page->post_title . "</li>";
    }

    $content.= "<li><a class='uam_info_link'>" . count($group_info->pages) . " " . TXT_PAGES . "</a>";
    $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
} else {
    $content.= "<li>" . TXT_NONE . " " . TXT_PAGES . "</li>";
}

if (isset($group_info->categories)) {
    $expandcontent = null;
    
    foreach ($group_info->categories as $categorie) {
        $expandcontent.= "<li>" . $categorie->cat_name . "</li>";
    }
    
    $content.= "<li><a class='uam_info_link'>" . count($group_info->categories) . " " . TXT_CATEGORY . "</a>";
    $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
} else {
    $content.= "<li>" . TXT_NONE . " " . TXT_CATEGORY . "</li>";
}

if (isset($group_info->users)) {
    $expandcontent = null;

    foreach ($group_info->users as $user) {
        $expandcontent.= "<li>" . $user->nickname . "</li>";
    }

    $content.= "<li><a class='uam_info_link'>" . count($group_info->users) . " " . TXT_USERS . "</a>";
    $content.= "<ul class='uam_info_content expand_deactive'>" . $expandcontent . "</ul></li>";
} else {
    $content.= "<li>" . TXT_NONE . " " . TXT_USERS . "</li>";
}

$content.= "</ul>";

$result->link = $link;
$result->content = $content;
return $result;