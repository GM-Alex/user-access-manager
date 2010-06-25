<?php
$usergroups = $wpdb->get_results("	SELECT ag.groupname
												FROM " . DB_ACCESSGROUP . " ag, " . DB_ACCESSGROUP_TO_USER . " agtp
												WHERE agtp.user_id = " . $id . "
													AND ag.ID = agtp.group_id
												GROUP BY ag.groupname", ARRAY_A);
if (isset($usergroups)) {
    $content.= "<ul>";
    foreach ($usergroups as $usergroup) {
        $content.= "<li>" . $usergroup['groupname'] . "</li>";
    }
    $content.= "</ul>";
} else {
    $content = TXT_NO_GROUP;
}
return $content;