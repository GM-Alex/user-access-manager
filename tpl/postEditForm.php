<?php
global $wpdb;
$accessgroups = $wpdb->get_results("SELECT *
									FROM ".DB_ACCESSGROUP."
									ORDER BY groupname", ARRAY_A);

$recursive_set = $this->get_usergroups_for_post($id);

if(isset($accessgroups))
{
	$content = "";
	
	foreach($accessgroups as $accessgroup)
	{
		$checked = $wpdb->get_results("	SELECT *
										FROM ".DB_ACCESSGROUP_TO_POST."
										WHERE post_id = ".$id."
										AND group_id = ".$accessgroup['ID'], ARRAY_A);
		
		$set_recursive = null;
		if(isset($recursive_set[$accessgroup['ID']]))
			$set_recursive = $recursive_set[$accessgroup['ID']];

		$content .= '<p><label for="uam_accesssgroup-'.$accessgroup['ID'].'" class="selectit" style="display:inline;" >';
		$content .= '<input type="checkbox" id="uam_accesssgroup-'.$accessgroup['ID'].'"';
		if(isset($checked) || isset($set_recursive->posts) || isset($set_recursive->categories))
			$content .= 'checked="checked"';
		if(isset($set_recursive->posts) || isset($set_recursive->categories))
			$content .= 'disabled=""';
		$content .= 'value="'.$accessgroup['ID'].'" name="accessgroups[]"/>';
		$content .= $accessgroup['groupname'];					
		$content .=	"</label>";

		$group_info_html = $this->get_usergroup_info_html($accessgroup['ID'], $style);

		$content .= $group_info_html->link;
				
		if(isset($set_recursive->posts) || isset($set_recursive->categories))
			$content .= '&nbsp;<a class="uam_group_lock_info_link">[LR]</a>';
			
		$content .= $group_info_html->content;							

		if(isset($set_recursive->posts) || isset($set_recursive->categories))
		{
			$recursive_info = '<ul class="uam_group_lock_info" ';
			if($style != null)
				$recursive_info .= " style='".$style."' ";
			$recursive_info .= '><li class="uam_group_lock_info_head">'.TXT_GROUP_LOCK_INFO.':</li>';
			
			if(isset($set_recursive->posts))
			{
				foreach($set_recursive->posts as $cur_id)
				{
					$cur_post = & get_post($cur_id);
					$recursive_info .= "<li>$cur_post->post_title [$cur_post->post_type]</li>";
				}
			}
			
			if(isset($set_recursive->categories))
			{
				foreach($set_recursive->categories as $cur_id)
				{
					$cur_category = & get_category($cur_id);
					$recursive_info .= "<li>$cur_category->name [".TXT_CATEGORY."]</li>";
				}
			}
			$recursive_info .= "</ul>";
			$content .= $recursive_info;
		}
		$content .= "</p>";
	}
}
else
{
	$content = "<a href='admin.php?page=uam_usergroup'>";
	$content .= TXT_CREATE_GROUP_FIRST;
	$content .= "</a>";
}

return $content;