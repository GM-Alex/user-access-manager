<?php
/**
 * adminSettings.php
 * 
 * Shows the setting page at the admin panel.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
global $oUserAccessManager;
$aUamOptions = $oUserAccessManager->getAdminOptions();

if (isset($_POST['update_uam_settings'])) {
    if (empty($_POST) 
        || !wp_verify_nonce($_POST['uamUpdateSettingsNonce'], 'uamUpdateSettings')
    ) {
         wp_die(TXT_UAM_NONCE_FAILURE);
    }
    
    foreach ($aUamOptions as $sOption => $sValue) {
        if (isset($_POST['uam_' . $sOption])) {
            $aUamOptions[$sOption] = $_POST['uam_' . $sOption];
        }
    }
    
    update_option($oUserAccessManager->getAdminOptionsName(), $aUamOptions);
    
    if ($_POST['uam_lock_file'] == 'false') {
        $oUserAccessManager->deleteHtaccessFiles();
    } else {
        $oUserAccessManager->createHtaccess();
        $oUserAccessManager->createHtpasswd(true);
    }
    
    do_action('uam_update_options', $aUamOptions);
    ?>
    <div class="updated">
        <p><strong><?php echo TXT_UAM_UPDATE_SETTINGS; ?></strong></p>
    </div>
    <?php
}
?>

<div class="wrap">
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <?php wp_nonce_field('uamUpdateSettings', 'uamUpdateSettingsNonce'); ?>
        <h2><?php echo TXT_UAM_SETTINGS; ?></h2>
        <h3><?php echo TXT_UAM_POST_SETTING; ?></h3>
        <p><?php echo TXT_UAM_POST_SETTING_DESC; ?></p>
<table class="form-table">
    <tbody>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_HIDE_POST; ?></th>
            <td>
                <label for="uam_hide_post_yes">
                    <input type="radio" id="uam_hide_post_yes" class="uam_hide_post" name="uam_hide_post" value="true" <?php
if ($aUamOptions['hide_post'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_hide_post_no">
                    <input type="radio" id="uam_hide_post_no" class="uam_hide_post" name="uam_hide_post" value="false" <?php
if ($aUamOptions['hide_post'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_HIDE_POST_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<table class="form-table" id="uam_post_settings">
    <tbody>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_DISPLAY_POST_TITLE; ?></th>
            <td>
                <label for="uam_hide_post_title_yes">
                    <input type="radio" id="uam_hide_post_title_yes" name="uam_hide_post_title" value="true" <?php
if ($aUamOptions['hide_post_title'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_hide_post_title_no">
                    <input type="radio" id="uam_hide_post_title_no" name="uam_hide_post_title" value="false" <?php
if ($aUamOptions['hide_post_title'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_DISPLAY_POST_TITLE_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php
echo TXT_UAM_POST_TITLE; ?></th>
            <td>
                <input name="uam_post_title" value="<?php echo $aUamOptions['post_title']; ?>" /> <br />
                <?php echo TXT_UAM_POST_TITLE_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_SHOW_POST_CONTENT_BEFORE_MORE; ?></th>
            <td>
                <label for="uam_show_post_content_before_more_yes">
                    <input type="radio" id="uam_show_post_content_before_more_yes" name="uam_show_post_content_before_more" value="true" <?php
if ($aUamOptions['show_post_content_before_more'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_show_post_content_before_more_no">
                    <input type="radio" id="uam_show_post_content_before_more_no" name="uam_show_post_content_before_more" value="false" <?php
if ($aUamOptions['show_post_content_before_more'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_SHOW_POST_CONTENT_BEFORE_MORE_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_POST_CONTENT; ?></th>
            <td>
                <textarea name="uam_post_content" style="width: 80%; height: 100px;" cols="40" rows="10"><?php
                    $sPostContent = stripslashes($aUamOptions['post_content']);
                    echo apply_filters('format_to_edit', $sPostContent);
                ?></textarea> <br />
                <?php echo TXT_UAM_POST_CONTENT_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_DISPLAY_POST_COMMENT; ?></th>
            <td>
                <label for="uam_hide_post_comment_yes">
                    <input id="uam_hide_post_comment_yes" type="radio" name="uam_hide_post_comment" value="true" <?php
if ($aUamOptions['hide_post_comment'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_hide_post_comment_no">
                    <input id="uam_hide_post_comment_no" type="radio" name="uam_hide_post_comment" value="false" <?php
if ($aUamOptions['hide_post_comment'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_DISPLAY_POST_COMMENT_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_POST_COMMENT_CONTENT; ?></th>
            <td>
                <input name="uam_post_comment_content" value="<?php echo $aUamOptions['post_comment_content']; ?>" /> <br />
                <?php echo TXT_UAM_POST_COMMENT_CONTENT_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_POST_COMMENTS_LOCKED; ?></th>
            <td>
                <label for="uam_post_comments_locked_yes">
                    <input id="uam_post_comments_locked_yes" type="radio" name="uam_post_comments_locked" value="true" <?php
if ($aUamOptions['post_comments_locked'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_post_comments_locked_no">
                    <input id="uam_post_comments_locked_no" type="radio" name="uam_post_comments_locked" value="false" <?php
if ($aUamOptions['post_comments_locked'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_POST_COMMENTS_LOCKED_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<h3><?php echo TXT_UAM_PAGE_SETTING; ?></h3>
<p><?php echo TXT_UAM_PAGE_SETTING_DESC; ?></p>
<table class="form-table">
    <tbody>
        <tr>
            <th><?php echo TXT_UAM_HIDE_PAGE; ?></th>
            <td>
                <label for="uam_hide_page_yes">
                <input type="radio" id="uam_hide_page_yes" class="uam_hide_page" name="uam_hide_page" value="true" <?php
if ($aUamOptions['hide_page'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_hide_page_no">
                    <input type="radio" id="uam_hide_page_no" class="uam_hide_page" name="uam_hide_page" value="false" <?php
if ($aUamOptions['hide_page'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_HIDE_PAGE_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<table class="form-table" id="uam_page_settings">
    <tbody>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_DISPLAY_PAGE_TITLE; ?></th>
            <td>
                <label for="uam_hide_page_title_yes">
                    <input type="radio" id="uam_hide_page_title_yes" name="uam_hide_page_title" value="true" <?php
if ($aUamOptions['hide_page_title'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_hide_page_title_no">
                    <input type="radio" id="uam_hide_page_title_no" name="uam_hide_page_title" value="false" <?php
if ($aUamOptions['hide_page_title'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_DISPLAY_PAGE_TITLE_DESC; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_PAGE_TITLE; ?></th>
            <td>
                <input name="uam_page_title" value="<?php echo $aUamOptions['page_title']; ?>" /> <br />
                <?php echo TXT_UAM_PAGE_TITLE_DESC; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_PAGE_CONTENT; ?></th>
            <td>
                <textarea name="uam_page_content" style="width: 80%; height: 100px;" cols="40" rows="10" ><?php
                    $sPageContent = stripslashes($aUamOptions['page_content']);
                    echo apply_filters('format_to_edit', $sPageContent);
                ?></textarea>
                <br />
                <?php echo TXT_UAM_PAGE_CONTENT_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_DISPLAY_PAGE_COMMENT; ?></th>
            <td>
                <label for="uam_hide_page_comment_yes">
                    <input id="uam_hide_page_comment_yes" type="radio" name="uam_hide_page_comment" value="true" <?php
if ($aUamOptions['hide_page_comment'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_hide_page_comment_no">
                    <input id="uam_hide_page_comment_no" type="radio" name="uam_hide_page_comment" value="false" <?php
if ($aUamOptions['hide_page_comment'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_DISPLAY_PAGE_COMMENT_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_PAGE_COMMENT_CONTENT; ?></th>
            <td>
                <input name="uam_page_comment_content" value="<?php echo $aUamOptions['page_comment_content']; ?>" /> <br />
                <?php echo TXT_UAM_PAGE_COMMENT_CONTENT_DESC; ?>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><?php echo TXT_UAM_PAGE_COMMENTS_LOCKED; ?></th>
            <td>
                <label for="uam_page_comments_locked_yes">
                    <input id="uam_page_comments_locked_yes" type="radio" name="uam_page_comments_locked" value="true" <?php
if ($aUamOptions['page_comments_locked'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_page_comments_locked_no">
                    <input id="uam_page_comments_locked_no" type="radio" name="uam_page_comments_locked" value="false" <?php
if ($aUamOptions['page_comments_locked'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_PAGE_COMMENTS_LOCKED_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<h3><?php echo TXT_UAM_FILE_SETTING; ?></h3>
<p><?php echo TXT_UAM_FILE_SETTING_DESC; ?></p>
<table class="form-table">
    <tbody>
        <tr>
            <th><?php echo TXT_UAM_LOCK_FILE; ?></th>
            <td>
                <label for="uam_lock_file_yes">
                    <input type="radio" id="uam_lock_file_yes" class="uam_lock_file" name="uam_lock_file" value="true" <?php
if ($aUamOptions['lock_file'] == "true") {
    echo 'checked="checked"';
}                   
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_lock_file_no">
                    <input type="radio" id="uam_lock_file_no" class="uam_lock_file" name="uam_lock_file" value="false" <?php
if ($aUamOptions['lock_file'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_LOCK_FILE_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<table class="form-table" id="uam_file_settings">
    <tbody>
<?php 
$sPermanentLinkStructure = get_option('permalink_structure');
            
if (empty($sPermanentLinkStructure)) {
    ?>
        <tr>
            <th><?php
    echo TXT_UAM_DOWNLOAD_FILE_TYPE; ?></th>
            <td>
                <label for="uam_lock_file_types_all">
                    <input type="radio" id="uam_lock_file_types_all" name="uam_lock_file_types" value="all" <?php
    if ($aUamOptions['lock_file_types'] == "all") {
        echo 'checked="checked"';
    }                   ?> />
                    <?php echo TXT_UAM_ALL; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_lock_file_types_selected">
                    <input type="radio" id="uam_lock_file_types_selected" name="uam_lock_file_types" value="selected" <?php
    if ($aUamOptions['lock_file_types'] == "selected") {
        echo 'checked="checked"';
    } 
                    ?> />
                    <?php echo TXT_UAM_SELECTED_FILE_TYPES; ?>
                </label>
                <input name="uam_locked_file_types" value="<?php echo $aUamOptions['locked_file_types']; ?>" />
                <label for="uam_lock_file_types_not_selected">
                    <input type="radio" id="uam_lock_file_types_not_selected" name="uam_lock_file_types" value="not_selected" <?php
    if ($aUamOptions['lock_file_types'] == "not_selected") {
        echo 'checked="checked"';
    } 
                    ?> />
                    <?php echo TXT_UAM_NOT_SELECTED_FILE_TYPES; ?>
                </label>
                <input name="uam_not_locked_file_types" value="<?php echo $aUamOptions['not_locked_file_types']; ?>" /> <br />
                <?php echo TXT_UAM_DOWNLOAD_FILE_TYPE_DESC; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_FILE_PASS_TYPE; ?></th>
            <td>
                <label for="uam_file_pass_type_admin">
                    <input type="radio" id="uam_file_pass_type_admin" name="uam_file_pass_type"    value="admin" <?php
    if ($aUamOptions['file_pass_type'] == "admin") {
        echo 'checked="checked"';
    } 
                    ?> />
                    <?php echo TXT_UAM_CURRENT_LOGGEDIN_ADMIN_PASS; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_file_pass_type_random">
                    <input type="radio" id="uam_file_pass_type_random" name="uam_file_pass_type" value="random" <?php
    if ($aUamOptions['file_pass_type'] == "random") {
        echo 'checked="checked"';
    } 
                    ?> />
                    <?php echo TXT_UAM_RANDOM_PASS; ?>
                </label> <br />
                <?php echo TXT_UAM_FILE_PASS_TYPE_DESC; ?>
            </td>
        </tr>
    <?php 
}
?>
        <tr>
            <th><?php echo TXT_UAM_DOWNLOAD_TYPE; ?></th>
            <td>
                <label for="uam_download_type_normal">
                    <input type="radio" id="uam_download_type_normal" name="uam_download_type" value="normal" <?php
if ($aUamOptions['download_type'] == "normal") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NORMAL; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_download_type_fopen">
                    <input type="radio" id="uam_download_type_fopen" name="uam_download_type" value="fopen" <?php
if ($aUamOptions['download_type'] == "fopen") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_FOPEN; ?>
                </label> <br />
                <?php echo TXT_UAM_DOWNLOAD_TYPE_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<h3><?php echo TXT_UAM_AUTHOR_SETTING; ?></h3>
<p><?php echo TXT_UAM_AUTHOR_SETTING_DESC; ?></p>
<table class="form-table">
    <tbody>
        <tr>
            <th><?php echo TXT_UAM_AUTHORS_HAS_ACCESS_TO_OWN; ?></th>
            <td>
                <label for="uam_authors_has_access_to_own_yes">
                    <input type="radio" id="uam_authors_has_access_to_own_yes" name="uam_authors_has_access_to_own" value="true" <?php
if ($aUamOptions['authors_has_access_to_own'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_authors_has_access_to_own_no">
                    <input type="radio" id="uam_authors_has_access_to_own_no" name="uam_authors_has_access_to_own" value="false" <?php
if ($aUamOptions['authors_has_access_to_own'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_AUTHORS_HAS_ACCESS_TO_OWN_DESC; ?></td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_AUTHORS_CAN_ADD_POSTS_TO_GROUPS; ?></th>
            <td>
                <label for="uam_authors_can_add_posts_to_groups_yes">
                    <input type="radio" id="uam_authors_can_add_posts_to_groups_yes" name="uam_authors_can_add_posts_to_groups" value="true" <?php
if ($aUamOptions['authors_can_add_posts_to_groups'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_authors_can_add_posts_to_groups_no">
                    <input type="radio" id="uam_authors_can_add_posts_to_groups_no" name="uam_authors_can_add_posts_to_groups" value="false" <?php
if ($aUamOptions['authors_can_add_posts_to_groups'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_AUTHORS_CAN_ADD_POSTS_TO_GROUPS_DESC; ?></td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_FULL_ACCESS_ROLE; ?></th>
            <td>
                <select name="uam_full_access_role">
                    <option value="administrator" <?php
if ($aUamOptions['full_access_role'] == "administrator") {
    echo 'selected="selected"';
} 
                    ?>><?php echo TXT_UAM_ADMINISTRATOR; ?></option>
                    <option value="editor" <?php
if ($aUamOptions['full_access_role'] == "editor") {
    echo 'selected="selected"';
} 
                    ?>><?php echo TXT_UAM_EDITOR; ?></option>
                    <option value="author" <?php
if ($aUamOptions['full_access_role'] == "author") {
    echo 'selected="selected"';
} 
                    ?>><?php echo TXT_UAM_AUTHOR; ?></option>
                    <option value="contributor" <?php
if ($aUamOptions['full_access_role'] == "contributor") {
    echo 'selected="selected"';
} 
                    ?>><?php echo TXT_UAM_CONTRIBUTOR; ?></option>
                    <option value="subscriber" <?php
if ($aUamOptions['full_access_role'] == "subscriber") {
    echo 'selected="selected"';
} 
                    ?>><?php echo TXT_UAM_SUBSCRIBER; ?></option>
                </select><br />
                <?php echo TXT_UAM_FULL_ACCESS_ROLE_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<h3><?php echo TXT_UAM_OTHER_SETTING; ?></h3>
<p><?php echo TXT_UAM_OTHER_SETTING_DESC; ?></p>
<table class="form-table">
    <tbody>
        <tr>
            <th><?php echo TXT_UAM_PROTECT_FEED; ?></th>
            <td>
                <label for="uam_protect_feed_yes">
                    <input type="radio" id="uam_protect_feed_yes" name="uam_protect_feed" value="true" <?php
if ($aUamOptions['protect_feed'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_protect_feed_no">
                    <input type="radio" id="uam_protect_feed_no" name="uam_protect_feed" value="false" <?php
if ($aUamOptions['protect_feed'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_PROTECT_FEED_DESC; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_HIDE_EMPTY_CATEGORIES; ?></th>
            <td>
                <label for="uam_hide_empty_categories_yes">
                    <input type="radio" id="uam_hide_empty_categories_yes" name="uam_hide_empty_categories" value="true" <?php
if ($aUamOptions['hide_empty_categories'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_hide_empty_categories_no">
                    <input type="radio" id="uam_hide_empty_categories_no" name="uam_hide_empty_categories" value="false" <?php
if ($aUamOptions['hide_empty_categories'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_HIDE_EMPTY_CATEGORIES_DESC; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_REDIRECT; ?></th>
            <td>
                <label for="uam_redirect_no">
                    <input type="radio" id="uam_redirect_no" name="uam_redirect" value="false" <?php
if ($aUamOptions['redirect'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_redirect_blog">
                    <input type="radio" id="uam_redirect_blog" name="uam_redirect" value="blog" <?php
if ($aUamOptions['redirect'] == "blog") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_REDIRECT_TO_BLOG; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_redirect_custom_page">
                    <input type="radio" id="uam_redirect_custom_page" name="uam_redirect" value="custom_page" <?php
if ($aUamOptions['redirect'] == "custom_page") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_REDIRECT_TO_PAGE; ?>
                </label>
                <select name="uam_redirect_custom_page">
<?php
$aPages = get_pages('sort_column=menu_order');
if (isset($aPages)) {
    foreach ($aPages as $oPage) {
        echo '<option value="' . $oPage->ID.'"';
        if ($aUamOptions['redirect_custom_page'] == $oPage->ID) {
            echo ' selected="selected"';
        }
        echo '>' . $oPage->post_title . '</option>';
    }
}
?>
                </select>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_redirect_custom_url">
                    <input type="radio" id="uam_redirect_custom_url" name="uam_redirect" value="custom_url" <?php
if ($aUamOptions['redirect'] == "custom_url") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_REDIRECT_TO_URL; ?>
                </label>
                <input name="uam_redirect_custom_url" value="<?php echo $aUamOptions['redirect_custom_url']; ?>" /> <br />
                <?php echo TXT_UAM_REDIRECT_DESC; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_LOCK_RECURSIVE; ?></th>
            <td>
                <label for="uam_lock_recursive_yes">
                    <input type="radio" id="uam_lock_recursive_yes" name="uam_lock_recursive" value="true" <?php
if ($aUamOptions['lock_recursive'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_lock_recursive_no">
                    <input type="radio" id="uam_lock_recursive_no" name="uam_lock_recursive" value="false" <?php
if ($aUamOptions['lock_recursive'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_LOCK_RECURSIVE_DESC; ?></td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_BLOG_ADMIN_HINT; ?></th>
            <td>
                <label for="uam_blog_admin_hint_yes">
                    <input type="radio" id="uam_blog_admin_hint_yes" name="uam_blog_admin_hint" value="true" <?php
if ($aUamOptions['blog_admin_hint'] == "true") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_YES; ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <label for="uam_blog_admin_hint_no">
                    <input type="radio" id="uam_blog_admin_hint_no" name="uam_blog_admin_hint" value="false" <?php
if ($aUamOptions['blog_admin_hint'] == "false") {
    echo 'checked="checked"';
} 
                    ?> />
                    <?php echo TXT_UAM_NO; ?>
                </label> <br />
                <?php echo TXT_UAM_BLOG_ADMIN_HINT_DESC; ?>
            </td>
        </tr>
        <tr>
            <th><?php echo TXT_UAM_BLOG_ADMIN_HINT_TEXT; ?></th>
            <td>
                <input name="uam_blog_admin_hint_text" value="<?php echo $aUamOptions['blog_admin_hint_text']; ?>" /> <br />
                <?php echo TXT_UAM_BLOG_ADMIN_HINT_TEXT_DESC; ?>
            </td>
        </tr>
    </tbody>
</table>
<div class="submit">
    <input type="submit" name="update_uam_settings" value="<?php echo TXT_UAM_UPDATE_SETTING; ?>" />
</div>
</form>
</div>