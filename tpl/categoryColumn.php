<?php
global $wpdb;

    $usergroups = $wpdb->get_results("SELECT ag.groupname
										FROM " . DB_ACCESSGROUP . " ag, " . DB_ACCESSGROUP_TO_CATEGORY . " agtc
										WHERE agtc.category_id = " . $id . "
											AND ag.ID = agtc.group_id
										GROUP BY ag.groupname", ARRAY_A);
    if (isset($usergroups)) {
        $content = "<ul>";
        foreach ($usergroups as $usergroup) {
            $content.= "<li>" . $usergroup['groupname'] . "</li>";
        }
        $content.= "</ul>";
    } else {
        $content = TXT_NO_GROUP;
    }
    return $content;