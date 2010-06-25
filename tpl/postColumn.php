<?php
$usergroups = $this->getUsergroupsForPost($id);
if (isset($usergroups) && $usergroups != null) {
    $output = "<ul>";
    foreach ($usergroups as $usergroup) {
        $output.= "<li><a class='uma_user_access_group'>" . $usergroup->name . "</a>";
        $output.= "<ul class='uma_user_access_group_from'>";
        if (isset($usergroup->itself)) $output.= "<li>" . TXT_ITSELF . "</li>";
        if (isset($usergroup->posts)) {
            foreach ($usergroup->posts as $curId) {
                $curPost = & get_post($curId);
                $output.= "<li>$curPost->post_title [$curPost->post_type]</li>";
            }
        }
        if (isset($usergroup->categories)) {
            foreach ($usergroup->categories as $curId) {
                $cur_category = & get_category($curId);
                $output.= "<li>$cur_category->name [category]</li>";
            }
        }
        $output = substr($output, 0, -2);
        $output.= "</ul></li>";
    }
    $output.= "</ul>";
} else {
    $output = TXT_FULL_ACCESS;
}
return $output;