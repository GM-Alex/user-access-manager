<?php
if (isset($_POST['update_uam_settings'])) {
    foreach ($osOptions as $option => $value) {
        if (isset($_POST['uam_' . $option])) {
            if ('flash_dimenson_x' == $option || 'flash_dimenson_y' == $option || 'flash_font_name' == $option || 'flash_font_size' == $option || 'flash_font_color' == $option || 'flash_font_bold' == $option || 'flash_font_italic' == $option || 'flash_text' == $option || 'flash_text_x' == $option || 'flash_text_y' == $option || 'flash_pic' == $option || 'flash_pic_x' == $option || 'flash_pic_y' == $option) {
                if ($osOptions[$option] != $_POST['os_' . $option]) $update_xml = TRUE;
            }
            $osOptions[$option] = $_POST['uam_' . $option];
        }
    }
    if (isset($_POST['uam_lock_file'])) {
        if ($_POST['uam_lock_file'] == 'FALSE') {
            if ($uamOptions['lock_file'] != $_POST['uam_lock_file']) {
                $this->delete_htaccess_files();
            }
        } else {
            if ($uamOptions['lock_file'] != $_POST['uam_lock_file']) {
                $lock_file_changed = TRUE;
            }
        }
        $uamOptions['lock_file'] = $_POST['uam_lock_file'];
    }
    if (isset($_POST['uam_file_pass_type'])) {
        if ($uamOptions['file_pass_type'] != $_POST['uam_file_pass_type']) {
            $file_pass_changed = TRUE;
        } else {
            $file_pass_changed = FALSE;
        }
        $uamOptions['file_pass_type'] = $_POST['uam_file_pass_type'];
    }
    if (isset($_POST['uam_download_type'])) {
        $uamOptions['download_type'] = $_POST['uam_download_type'];
    }
    if (isset($_POST['uam_locked_file_types'])) {
        if ($uamOptions['locked_file_types'] != $_POST['uam_locked_file_types']) {
            $locked_file_types_changed = TRUE;
        } else {
            $locked_file_types_changed = FALSE;
        }
        $uamOptions['locked_file_types'] = $_POST['uam_locked_file_types'];
    }
    if (isset($_POST['uam_not_locked_file_types'])) {
        if ($uamOptions['not_locked_file_types'] != $_POST['uam_not_locked_file_types']) {
            $not_locked_file_types_changed = TRUE;
        } else {
            $not_locked_file_types_changed = FALSE;
        }
        $uamOptions['not_locked_file_types'] = $_POST['uam_not_locked_file_types'];
    }
    if (isset($_POST['uam_lock_file_types'])) {
        if ($uamOptions['lock_file_types'] != $_POST['uam_lock_file_types']) {
            $locked_file_types_changed = TRUE;
        }
        $uamOptions['lock_file_types'] = $_POST['uam_lock_file_types'];
    }
    update_option($this->adminOptionsName, $uamOptions);
    if (($locked_file_types_changed || $not_locked_file_types_changed || isset($lock_file_changed) || $file_pass_changed) && $uamOptions['lock_file'] != 'FALSE') {
        $this->create_htaccess();
        $this->create_htpasswd(TRUE);
    }
?>
<div class="updated">
<p><strong><?php
    echo TXT_UPDATE_SETTINGS; ?></strong></p>
</div>
    <?php
}
?>

<div class=wrap>
<form method="post" action="<?php
echo $_SERVER["REQUEST_URI"]; ?>">
<h2><?php
echo TXT_SETTINGS; ?></h2>
<h3><?php
echo TXT_POST_SETTING; ?></h3>
<p><?php
echo TXT_POST_SETTING_DESC; ?></p>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_HIDE_POST; ?></th>
			<td><label for="uam_hide_post_yes"> <input type="radio"
				id="uam_hide_post_yes" class="uam_hide_post" name="uam_hide_post"
				value="TRUE"
				<?php
if ($uamOptions['hide_post'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_hide_post_no"> <input type="radio" id="uam_hide_post_no"
				class="uam_hide_post" name="uam_hide_post" value="FALSE"
				<?php
if ($uamOptions['hide_post'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_HIDE_POST_DESC; ?></td>
		</tr>
	</tbody>
</table>
<table class="form-table" id="uam_post_settings">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_DISPLAY_POST_TITLE; ?></th>
			<td><label for="uam_hide_post_title_yes"> <input type="radio"
				id="uam_hide_post_title_yes" name="uam_hide_post_title" value="TRUE"
				<?php
if ($uamOptions['hide_post_title'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_hide_post_title_no"> <input type="radio"
				id="uam_hide_post_title_no" name="uam_hide_post_title" value="FALSE"
				<?php
if ($uamOptions['hide_post_title'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_DISPLAY_POST_TITLE_DESC; ?></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_POST_TITLE; ?></th>
			<td><input name="uam_post_title"
				value="<?php
echo $uamOptions['post_title']; ?>" /> <br />
				<?php
echo TXT_POST_TITLE_DESC; ?></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_SHOW_POST_CONTENT_BEFORE_MORE; ?></th>
			<td><label for="uam_show_post_content_before_more_yes"> <input
				type="radio" id="uam_hide_post_title_yes"
				name="uam_show_post_content_before_more" value="TRUE"
				<?php
if ($uamOptions['show_post_content_before_more'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_show_post_content_before_more_no"> <input type="radio"
				id="uam_hide_post_title_no" name="uam_show_post_content_before_more"
				value="FALSE"
				<?php
if ($uamOptions['show_post_content_before_more'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_SHOW_POST_CONTENT_BEFORE_MORE_DESC; ?></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_POST_CONTENT; ?></th>
			<td><textarea name="uam_post_content"
				style="width: 80%; height: 100px;"><?php
echo apply_filters('format_to_edit', $uamOptions['post_content']); ?></textarea>
			<br />
			<?php
echo TXT_POST_CONTENT_DESC; ?></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_DISPLAY_POST_COMMENT; ?></th>
			<td><label for="uam_hide_post_comment_yes"> <input type="radio"
				name="uam_hide_post_comment" value="TRUE"
				<?php
if ($uamOptions['hide_post_comment'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_hide_post_comment_no"> <input type="radio"
				name="uam_hide_post_comment" value="FALSE"
				<?php
if ($uamOptions['hide_post_comment'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_DISPLAY_POST_COMMENT_DESC; ?></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_POST_COMMENT_CONTENT; ?></th>
			<td><input name="uam_post_comment_content"
				value="<?php
echo $uamOptions['post_comment_content']; ?>" /> <br />
				<?php
echo TXT_POST_COMMENT_CONTENT_DESC; ?></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_ALLOW_COMMENTS_LOCKED; ?></th>
			<td><label for="uam_allow_comments_locked_yes"> <input type="radio"
				name="uam_allow_comments_locked" value="TRUE"
				<?php
if ($uamOptions['allow_comments_locked'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_allow_comments_locked_no"> <input type="radio"
				name="uam_allow_comments_locked" value="FALSE"
				<?php
if ($uamOptions['allow_comments_locked'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_ALLOW_COMMENTS_LOCKED_DESC; ?></td>
		</tr>
	</tbody>
</table>
<h3><?php
echo TXT_PAGE_SETTING; ?></h3>
<p><?php
echo TXT_PAGE_SETTING_DESC; ?></p>
<table class="form-table">
	<tbody>
		<tr>
			<th><?php
echo TXT_HIDE_PAGE; ?></th>
			<td><label for="uam_hide_page_yes"> <input type="radio"
				id="uam_hide_page_yes" class="uam_hide_page" name="uam_hide_page"
				value="TRUE"
				<?php
if ($uamOptions['hide_page'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_hide_page_no"> <input type="radio" id="uam_hide_page_no"
				class="uam_hide_page" name="uam_hide_page" value="FALSE"
				<?php
if ($uamOptions['hide_page'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_HIDE_PAGE_DESC; ?></td>
		</tr>
	</tbody>
</table>
<table class="form-table" id="uam_page_settings">
	<tbody>
		<tr valign="top">
			<th scope="row"><?php
echo TXT_DISPLAY_PAGE_TITLE; ?></th>
			<td><label for="uam_hide_page_title_yes"> <input type="radio"
				id="uam_hide_page_title_yes" name="uam_hide_page_title" value="TRUE"
				<?php
if ($uamOptions['hide_page_title'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_hide_page_title_no"> <input type="radio"
				id="uam_hide_page_title_no" name="uam_hide_page_title" value="FALSE"
				<?php
if ($uamOptions['hide_page_title'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_DISPLAY_PAGE_TITLE_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_PAGE_TITLE; ?></th>
			<td><input name="uam_page_title"
				value="<?php
echo $uamOptions['page_title']; ?>" /> <br />
				<?php
echo TXT_PAGE_TITLE_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_PAGE_CONTENT; ?></th>
			<td><textarea name="uam_page_content"
				style="width: 80%; height: 100px;"><?php
echo apply_filters('format_to_edit', $uamOptions['page_content']); ?></textarea>
			<br />
			<?php
echo TXT_PAGE_CONTENT_DESC; ?></td>
		</tr>
	</tbody>
</table>
<h3><?php
echo TXT_FILE_SETTING; ?></h3>
<p><?php
echo TXT_FILE_SETTING_DESC; ?></p>
<table class="form-table">
	<tbody>
		<tr>
			<th><?php
echo TXT_LOCK_FILE; ?></th>
			<td><label for="uam_lock_file_yes"> <input type="radio"
				id="uam_lock_file_yes" class="uam_lock_file" name="uam_lock_file"
				value="TRUE"
				<?php
if ($uamOptions['lock_file'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_lock_file_no"> <input type="radio" id="uam_lock_file_no"
				class="uam_lock_file" name="uam_lock_file" value="FALSE"
				<?php
if ($uamOptions['lock_file'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_LOCK_FILE_DESC; ?></td>
		</tr>
	</tbody>
</table>
<table class="form-table" id="uam_file_settings">
	<tbody>
		<tr>
			<th><?php
echo TXT_DOWNLOAD_FILE_TYPE; ?></th>
			<td><label for="uam_lock_file_types_all"> <input type="radio"
				id="uam_lock_file_types_all" name="uam_lock_file_types" value="all"
				<?php
if ($uamOptions['lock_file_types'] == "all") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_ALL; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_lock_file_types_selected"> <input type="radio"
				id="uam_lock_file_types_selected" name="uam_lock_file_types"
				value="selected"
				<?php
if ($uamOptions['lock_file_types'] == "selected") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_SELECTED_FILE_TYPES; ?> </label><input
				name="uam_locked_file_types"
				value="<?php
echo $uamOptions['locked_file_types']; ?>" /> <label
				for="uam_lock_file_types_not_selected"> <input type="radio"
				id="uam_lock_file_types_not_selected" name="uam_lock_file_types"
				value="not_selected"
				<?php
if ($uamOptions['lock_file_types'] == "not_selected") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NOT_SELECTED_FILE_TYPES; ?> </label><input
				name="uam_not_locked_file_types"
				value="<?php
echo $uamOptions['not_locked_file_types']; ?>" /> <br />
				<?php
echo TXT_DOWNLOAD_FILE_TYPE_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_FILE_PASS_TYPE; ?></th>
			<td><label for="uam_file_pass_type_admin"> <input type="radio"
				id="uam_file_pass_type_admin" name="uam_file_pass_type"
				value="admin"
				<?php
if ($uamOptions['file_pass_type'] == "admin") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_CURRENT_LOGGEDIN_ADMIN_PASS; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp;
			<label for="uam_file_pass_type_random"> <input type="radio"
				id="uam_file_pass_type_random" name="uam_file_pass_type"
				value="random"
				<?php
if ($uamOptions['file_pass_type'] == "random") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_RANDOM_PASS; ?> </label> <br />
				<?php
echo TXT_FILE_PASS_TYPE_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_DOWNLOAD_TYPE; ?></th>
			<td><label for="uam_download_type_normal"> <input type="radio"
				id="uam_download_type_normal" name="uam_download_type"
				value="normal"
				<?php
if ($uamOptions['download_type'] == "normal") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NORMAL; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_download_type_fopen"> <input type="radio"
				id="uam_download_type_fopen" name="uam_download_type" value="fopen"
				<?php
if ($uamOptions['download_type'] == "fopen") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_FOPEN; ?> </label> <br />
				<?php
echo TXT_DOWNLOAD_TYPE_DESC; ?></td>
		</tr>
	</tbody>
</table>
<h3><?php
echo TXT_OTHER_SETTING; ?></h3>
<p><?php
echo TXT_OTHER_SETTING_DESC; ?></p>
<table class="form-table">
	<tbody>
		<tr>
			<th><?php
echo TXT_PROTECT_FEED; ?></th>
			<td><label for="uam_protect_feed_yes"> <input type="radio"
				id="uam_protect_feed_yes" name="uam_protect_feed" value="TRUE"
				<?php
if ($uamOptions['protect_feed'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_protect_feed_no"> <input type="radio"
				id="uam_protect_feed_no" name="uam_protect_feed" value="FALSE"
				<?php
if ($uamOptions['protect_feed'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_PROTECT_FEED_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_HIDE_EMPTY_CATEGORIES; ?></th>
			<td><label for="uam_hide_empty_categories_yes"> <input type="radio"
				id="uam_hide_empty_categories_yes" name="uam_hide_empty_categories"
				value="TRUE"
				<?php
if ($uamOptions['hide_empty_categories'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_hide_empty_categories_no"> <input type="radio"
				id="uam_hide_empty_categories_no" name="uam_hide_empty_categories"
				value="FALSE"
				<?php
if ($uamOptions['hide_empty_categories'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_HIDE_EMPTY_CATEGORIES_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_REDIRECT; ?></th>
			<td><label for="uam_redirect_no"> <input type="radio"
				id="uam_redirect_no" name="uam_redirect" value="FALSE"
				<?php
if ($uamOptions['redirect'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_redirect_blog"> <input type="radio" id="uam_redirect_blog"
				name="uam_redirect" value="blog"
				<?php
if ($uamOptions['redirect'] == "blog") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_REDIRECT_TO_BOLG; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp;
			<label for="uam_redirect_custom_page"> <input type="radio"
				id="uam_redirect_custom_p" name="uam_redirect" value="custom_page"
				<?php
if ($uamOptions['redirect'] == "custom_page") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_REDIRECT_TO_PAGE; ?> </label><select
				name="uam_redirect_custom_page">
				<?php
$pages = get_pages('sort_column=menu_order');
if (isset($pages)) {
    foreach ($pages as $page) {
        echo '<option value="' . $page->ID;
        if ($uamOptions['redirect_custom_page'] == $page->ID) echo ' selected = "selected"';
        echo '>' . $page->post_title . '</option>';
    }
}
?></select>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_redirect_custom_url"> <input type="radio"
				id="uam_redirect_custom_u" name="uam_redirect" value="custom_url"
				<?php
if ($uamOptions['redirect'] == "custom_url") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_REDIRECT_TO_URL; ?> </label><input
				name="uam_redirect_custom_url"
				value="<?php
echo $uamOptions['redirect_custom_url']; ?>" /> <br />
				<?php
echo TXT_REDIRECT_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_LOCK_RECURSIVE; ?></th>
			<td><label for="uam_lock_recursive_yes"> <input type="radio"
				id="uam_lock_recursive_yes" name="uam_lock_recursive" value="TRUE"
				<?php
if ($uamOptions['lock_recursive'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_lock_recursive_no"> <input type="radio"
				id="uam_lock_recursive_no" name="uam_lock_recursive" value="FALSE"
				<?php
if ($uamOptions['lock_recursive'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_LOCK_RECURSIVE_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_BLOG_ADMIN_HINT; ?></th>
			<td><label for="uam_blog_admin_hint_yes"> <input type="radio"
				id="uam_blog_admin_hint_yes" name="uam_blog_admin_hint" value="TRUE"
				<?php
if ($uamOptions['blog_admin_hint'] == "TRUE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_blog_admin_hint_no"> <input type="radio"
				id="uam_blog_admin_hint_no" name="uam_blog_admin_hint" value="FALSE"
				<?php
if ($uamOptions['blog_admin_hint'] == "FALSE") {
    echo 'checked="checked"';
} ?> />
				<?php
echo TXT_NO; ?> </label> <br />
				<?php
echo TXT_BLOG_ADMIN_HINT_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_BLOG_ADMIN_HINT_TEXT; ?></th>
			<td><input name="uam_blog_admin_hint_text"
				value="<?php
echo $uamOptions['blog_admin_hint_text']; ?>" /> <br />
				<?php
echo TXT_BLOG_ADMIN_HINT_TEXT_DESC; ?></td>
		</tr>
		<tr>
			<th><?php
echo TXT_FULL_ACCESS_LEVEL; ?></th>
			<td><input name="uam_full_access_level"
				value="<?php
echo $uamOptions['full_access_level']; ?>" /> <br />
				<?php
echo TXT_FULL_ACCESS_LEVEL_DESC; ?></td>
		</tr>
		<?php
global $wp_version;
if ($wp_version < 2.8) {
?>
		<tr>
			<th><?php
    echo TXT_CORE_MOD; ?></th>
			<td><label for="uam_core_mod_yes"> <input type="radio"
				id="uam_core_mod_yes" name="uam_core_mod" value="TRUE"
				<?php
    if ($uamOptions['core_mod'] == "TRUE") {
        echo 'checked="checked"';
    } ?> />
				<?php
    echo TXT_YES; ?> </label>&nbsp;&nbsp;&nbsp;&nbsp; <label
				for="uam_core_mod_no"> <input type="radio" id="uam_core_mod_no"
				name="uam_core_mod" value="FALSE"
				<?php
    if ($uamOptions['core_mod'] == "FALSE") {
        echo 'checked="checked"';
    } ?> />
				<?php
    echo TXT_NO; ?> </label> <br />
				<?php
    echo TXT_CORE_MOD_DESC; ?></td>
		</tr>
		<?php
}
?>
	</tbody>
</table>
<div class="submit"><input type="submit" name="update_uam_settings"
	value="<?php
echo TXT_UPDATE_SETTING; ?>" /></div>
</form>
</div>
