<?php
global $wpdb, $current_user;

if (isset($cat->cat_ID)) {
    $cat_id = $cat->cat_ID;
}

$accessgroups = $wpdb->get_results("SELECT *
									FROM " . DB_ACCESSGROUP . "
									ORDER BY groupname", ARRAY_A);
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = null;
}

if ($action == 'edit') {
?>
<table class="form-table">
	<tbody>
		<tr>
			<th><label for="description"><?php
            echo TXT_SET_UP_USERGROUPS; ?></label>
		</th>
		<td><?php
            if (isset($accessgroups)) {
                $recursive_set = $this->getUsergroupsForPost($cat_id);
                foreach ($accessgroups as $accessgroup) {
                    $checked = $wpdb->get_results("	SELECT *
																	FROM " . DB_ACCESSGROUP_TO_CATEGORY . "
																	WHERE category_id = " . $cat_id . "
																		AND group_id = " . $accessgroup['ID'], ARRAY_A)

                    //$set_recursive = $recursive_set[$accessgroup['groupname']];
                    
?>
		<p style="margin: 6px 0;"><label
			for="uam_accesssgroup-<?php
                    echo $accessgroup['ID']; ?>"
			class="selectit"> <input type="checkbox"
			id="uam_accesssgroup-<?php
                    echo $accessgroup['ID']; ?>"
			<?php
                    if (isset($checked)) {
                        echo 'checked="checked"';
                    } ?>
			value="<?php
                    echo $accessgroup['ID']; ?>" name="accessgroups[]" /> <?php
                    echo $accessgroup['groupname']; ?>
		</label> <?php
                    $group_info_html = $this->getUserGroupInfoHtml($accessgroup['ID'], "padding:0 0 0 32px;");
                    echo $group_info_html->link;
                    if (isset($set_recursive->posts) || isset($set_recursive->categories)) echo '&nbsp;<a class="uam_group_lock_info_link">[LR]</a>';
                    echo $group_info_html->content;
                    if (isset($set_recursive->posts) || isset($set_recursive->categories)) {
                        $recursive_info = '<ul class="uam_group_lock_info" style="padding:0 0 0 32px;"><li class="uam_group_lock_info_head">' . TXT_GROUP_LOCK_INFO . ':</li>';
                        if (isset($set_recursive->posts)) {
                            foreach ($set_recursive->posts as $curId) {
                                $curPost = & get_post($curId);
                                $recursive_info.= "<li>$curPost->post_title [$curPost->post_type]</li>";
                            }
                        }
                        if (isset($set_recursive->categories)) {
                            foreach ($set_recursive->categories as $curId) {
                                $cur_category = & get_category($curId);
                                $recursive_info.= "<li>$cur_category->name [" . TXT_CATEGORY . "]</li>";
                            }
                        }
                        $recursive_info.= "</ul>";
                        echo $recursive_info;
                    }
                    echo "</p>";
                }
            } else {
                echo "<a href='admin.php?page=uam_usergroup'>";
                echo TXT_CREATE_GROUP_FIRST;
                echo "</a>";
            }
?>
			
			</td>
		</tr>
	</tbody>
</table>
<style type="text/css">
.submit {
	display: none;
	position: relative;
}
</style>
<p class="submit" style="display: block; position: relative;"><input
	class="button-primary" type="submit" value="Update Category"
	name="submit" /></p>
			<?php
        }