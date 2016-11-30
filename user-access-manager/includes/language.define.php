<?php
/**
 * language.define.php
 * 
 * Defines needed for the language
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

// --- Error Messages ---
define('TXT_UAM_PHP_VERSION_TO_LOW', __('Sorry you need at least PHP version 5.0 to use the User Access Manager. Your current PHP version is %s.', 'user-access-manager'));
define('TXT_UAM_WORDPRESS_VERSION_TO_LOW', __('Sorry you need at least Wordpress version 3.0 to use the User Access Manager. Your current Wordpress version is %s.', 'user-access-manager'));
define('TXT_UAM_NEED_DATABASE_UPDATE', __('Please update the database of the User Access Manager. <a href="%s">Click here to proceed</a>', 'user-access-manager'));
define('TXT_UAM_FOPEN_WITHOUT_SAVEMODE_OFF', __('You setup fopen as download type for file at the User Access Manager settings, but for this option safe_mode=off is required. Please change the settings.', 'user-access-manager'));

// --- Multiple use ---
define('TXT_UAM_ALL', __('all', 'user-access-manager'));
define('TXT_UAM_ONLY_GROUP_USERS', __('only group users', 'user-access-manager'));
define('TXT_UAM_YES', __('Yes', 'user-access-manager'));
define('TXT_UAM_NO', __('No', 'user-access-manager'));


// --- Setting Page ---
define('TXT_UAM_SETTINGS', __('Settings', 'user-access-manager'));

// --- Setting Page -> post settings ---
define('TXT_UAM_POST_SETTING', __('Post settings', 'user-access-manager'));
define('TXT_UAM_POST_SETTING_DESC', __('Set up the behaviour of locked posts', 'user-access-manager'));
define('TXT_UAM_POST_TITLE', __('Post title', 'user-access-manager'));
define('TXT_UAM_POST_TITLE_DESC', __('Displayed text as post title if user has no access', 'user-access-manager'));
define('TXT_UAM_DISPLAY_POST_TITLE', __('Hide post title', 'user-access-manager'));
define('TXT_UAM_DISPLAY_POST_TITLE_DESC', sprintf(__('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'), TXT_UAM_POST_TITLE));
define('TXT_UAM_POST_CONTENT', __('Post content', 'user-access-manager'));
define('TXT_UAM_POST_CONTENT_DESC', __('Content displayed if user has no access. You can add an login-form by adding the keyword <strong>[LOGIN_FORM]</strong>. This form will shown on single posts, otherwise a link will shown.', 'user-access-manager'));
define('TXT_UAM_SHOW_POST_CONTENT_BEFORE_MORE', __('Show post content before &lt;!--more--&gt; tag', 'user-access-manager'));
define('TXT_UAM_SHOW_POST_CONTENT_BEFORE_MORE_DESC', sprintf(__('Shows the post content before the &lt;!--more--&gt; tag and after that the defined text at "%s". If no &lt;!--more--&gt; is set he defined text at "%s" will shown.', 'user-access-manager'), TXT_UAM_POST_CONTENT, TXT_UAM_POST_CONTENT));
define('TXT_UAM_HIDE_POST', __('Hide complete posts', 'user-access-manager'));
define('TXT_UAM_HIDE_POST_DESC', __('Selecting "Yes" will hide posts if the user has no access.', 'user-access-manager'));
define('TXT_UAM_POST_COMMENT_CONTENT', __('Post comment text', 'user-access-manager'));
define('TXT_UAM_POST_COMMENT_CONTENT_DESC', __('Displayed text as post comment text if user has no access', 'user-access-manager'));
define('TXT_UAM_DISPLAY_POST_COMMENT', __('Hide post comments', 'user-access-manager'));
define('TXT_UAM_DISPLAY_POST_COMMENT_DESC', sprintf(__('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'), TXT_UAM_POST_COMMENT_CONTENT));
define('TXT_UAM_POST_COMMENTS_LOCKED', __('Allow post comments', 'user-access-manager'));
define('TXT_UAM_POST_COMMENTS_LOCKED_DESC', __('Selecting "yes" allows users to comment on locked posts', 'user-access-manager'));

// --- Setting Page -> page settings ---
define('TXT_UAM_PAGE_SETTING', __('Page settings', 'user-access-manager'));
define('TXT_UAM_PAGE_SETTING_DESC', __('Set up the behaviour of locked pages', 'user-access-manager'));
define('TXT_UAM_PAGE_TITLE', __('Page title', 'user-access-manager'));
define('TXT_UAM_PAGE_TITLE_DESC', __('Displayed text as page title if user has no access', 'user-access-manager'));
define('TXT_UAM_DISPLAY_PAGE_TITLE', __('Hide page title', 'user-access-manager'));
define('TXT_UAM_DISPLAY_PAGE_TITLE_DESC', sprintf(__('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'), TXT_UAM_POST_TITLE));
define('TXT_UAM_PAGE_CONTENT', __('Page content', 'user-access-manager'));
define('TXT_UAM_PAGE_CONTENT_DESC', __('Content displayed if user has no access. You can add an login-form by adding the keyword <strong>[LOGIN_FORM]</strong>. This form will shown on single pages, otherwise a link will shown.', 'user-access-manager'));
define('TXT_UAM_HIDE_PAGE', __('Hide complete pages', 'user-access-manager'));
define('TXT_UAM_HIDE_PAGE_DESC', __('Selecting "Yes" will hide pages if the user has no access. Pages will also hide in the navigation.', 'user-access-manager'));
define('TXT_UAM_PAGE_COMMENT_CONTENT', __('Page comment text', 'user-access-manager'));
define('TXT_UAM_PAGE_COMMENT_CONTENT_DESC', __('Displayed text as page comment text if user has no access', 'user-access-manager'));
define('TXT_UAM_DISPLAY_PAGE_COMMENT', __('Hide page comments', 'user-access-manager'));
define('TXT_UAM_DISPLAY_PAGE_COMMENT_DESC', sprintf(__('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'), TXT_UAM_PAGE_COMMENT_CONTENT));
define('TXT_UAM_PAGE_COMMENTS_LOCKED', __('Allow page comments', 'user-access-manager'));
define('TXT_UAM_PAGE_COMMENTS_LOCKED_DESC', __('Selecting "yes" allows users to comment on locked pages', 'user-access-manager'));

// --- Setting Page -> file settings ---
define('TXT_UAM_FILE_SETTING', __('File settings', 'user-access-manager'));
define('TXT_UAM_FILE_SETTING_DESC', __('Set up the behaviour of files', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE', __('Lock files', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_DESC', __('If you select "Yes" all files will locked by a .htaccess file and only users with access can download files. <br/><strong style="color:red;">Note: If you activate this option the plugin will overwrite a \'.htaccess\' file at the upload folder, if you use already one to protect your files. Also if you have no permalinks activated your upload dir will protect by a \'.htaccess\' with a random password and all old media files insert in a previous post/page will not work anymore. You have to update your posts/pages (not necessary if you have permalinks activated).</strong>', 'user-access-manager'));
define('TXT_UAM_SELECTED_FILE_TYPES', __('File types to lock: ', 'user-access-manager'));
define('TXT_UAM_NOT_SELECTED_FILE_TYPES', __('File types not to lock: ', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_FILE_TYPE', __('Locked file types', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_FILE_TYPE_DESC', __('Lock all files, type in file types which you will lock if the post/page is locked or define file types which will not be locked. <strong>Note:</strong> If you have no problems use all to get the maximum security.', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE', __('.htaccess password', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE_DESC', __('Set up the password for the .htaccess access. This password is only needed if you need a direct access to your files.', 'user-access-manager'));
define('TXT_UAM_RANDOM_PASS', __('Use a random generated pass word.', 'user-access-manager'));
define('TXT_UAM_CURRENT_LOGGEDIN_ADMIN_PASS', __('Use the password of the current logged in admin.', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE', __('Download type', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_DESC', __('Selecting the type for downloading. <strong>Note:</strong> For using fopen you need "safe_mode = off".', 'user-access-manager'));
define('TXT_UAM_NORMAL', __('Normal', 'user-access-manager'));
define('TXT_UAM_FOPEN', __('fopen', 'user-access-manager'));

// --- Setting Page -> editor settings ---
define('TXT_UAM_AUTHOR_SETTING', __('Authors settings', 'user-access-manager'));
define('TXT_UAM_AUTHOR_SETTING_DESC', __('Here you will find the settings for authors', 'user-access-manager'));
define('TXT_UAM_AUTHORS_HAS_ACCESS_TO_OWN', __('Authors always has access to own posts/pages', 'user-access-manager'));
define('TXT_UAM_AUTHORS_HAS_ACCESS_TO_OWN_DESC', __('If "Yes" is selected author will always have full access to their own posts or pages.', 'user-access-manager'));
define('TXT_UAM_AUTHORS_CAN_ADD_POSTS_TO_GROUPS', __('Authors can add content to their own groups', 'user-access-manager'));
define('TXT_UAM_AUTHORS_CAN_ADD_POSTS_TO_GROUPS_DESC', __('If "Yes" is selected author are able to restrict the content by adding it to their groups.', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE', __('Minimum user role with full access', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE_DESC', __('All user with a role equal or higher to this has full access.', 'user-access-manager'));
define('TXT_UAM_ADMINISTRATOR', __('Administrator', 'user-access-manager'));
define('TXT_UAM_EDITOR', __('Editor', 'user-access-manager'));
define('TXT_UAM_AUTHOR', __('Author', 'user-access-manager'));
define('TXT_UAM_CONTRIBUTOR', __('Contributor', 'user-access-manager'));
define('TXT_UAM_SUBSCRIBER', __('Subscriber', 'user-access-manager'));

// --- Setting Page -> other settings ---
define('TXT_UAM_OTHER_SETTING', __('Other settings', 'user-access-manager'));
define('TXT_UAM_OTHER_SETTING_DESC', __('Here you will find all other settings', 'user-access-manager'));
define('TXT_UAM_PROTECT_FEED', __('Protect Feed', 'user-access-manager'));
define('TXT_UAM_PROTECT_FEED_DESC', __('Selecting "Yes" will also protect your feed entries.', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_CATEGORIES', __('Hide empty categories', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_CATEGORIES_DESC', __('Selecting "Yes" will hide empty categories which are containing only empty childes or no childes.', 'user-access-manager'));
define('TXT_UAM_REDIRECT', __('Redirect user', 'user-access-manager'));
define('TXT_UAM_REDIRECT_DESC', __('Setup what happen if a user visit a post/page with no access.', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_BLOG', __('To blog startpage', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_PAGE', __('Custom page: ', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_URL', __('Custom URL: ', 'user-access-manager'));
define('TXT_UAM_LOCK_RECURSIVE', __('Lock recursive', 'user-access-manager'));
define('TXT_UAM_LOCK_RECURSIVE_DESC', __('Selecting "Yes" will lock all child posts/pages of a post/page if a user has no access to the parent page. Note: Setting this option to "No" could result in display errors relating to the hierarchy.', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_TEXT', __('Admin hint text', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_TEXT_DESC', __('The text which will shown behind the post/page.', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT', __('Show admin hint at Posts', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_DESC', sprintf(__('Selecting "Yes" will show the defined text at "%s" behind the post/page to an logged in admin to show him which posts/pages are locked if he visits his blog.', 'user-access-manager'), TXT_UAM_BLOG_ADMIN_HINT_TEXT));

// --- Setting Page -> update message ---
define('TXT_UAM_UPDATE_SETTING', __('Update settings', 'user-access-manager'));
define('TXT_UAM_UPDATE_SETTINGS', __('Settings updated.', 'user-access-manager'));


// --- User groups page ---
define('TXT_UAM_MANAGE_GROUP', __('Manage user user groups', 'user-access-manager'));
define('TXT_UAM_GROUP_ROLE', __('Role affiliation', 'user-access-manager'));
define('TXT_UAM_NAME', __('Name', 'user-access-manager'));
define('TXT_UAM_DESCRIPTION', __('Description', 'user-access-manager'));
define('TXT_UAM_READ_ACCESS', __('Read access', 'user-access-manager'));
define('TXT_UAM_WRITE_ACCESS', __('Write access', 'user-access-manager'));
define('TXT_UAM_DELETE', __('Delete', 'user-access-manager'));
define('TXT_UAM_UPDATE_GROUP', __('Update group', 'user-access-manager'));
define('TXT_UAM_ADD', __('Add', 'user-access-manager'));
define('TXT_UAM_ADD_GROUP', __('Add user group', 'user-access-manager'));
define('TXT_UAM_EDIT_GROUP', __('Edit user group', 'user-access-manager'));
define('TXT_UAM_GROUP_NAME', __('User group name', 'user-access-manager'));
define('TXT_UAM_GROUP_NAME_DESC', __('The name is used to identify the access user group.', 'user-access-manager'));
define('TXT_UAM_GROUP_DESC', __('User group description', 'user-access-manager'));
define('TXT_UAM_GROUP_DESC_DESC', __('The description of the group.', 'user-access-manager'));
define('TXT_UAM_GROUP_IP_RANGE', __('IP range', 'user-access-manager'));
define('TXT_UAM_GROUP_IP_RANGE_DESC', __('Type in the IP ranges of users which are join these groups by their IP address without login. Set ranges like "BEGIN"-"END", separate ranges by using ";", single IPs are also allowed. Example: 192.168.0.1-192.168.0.10;192.168.0.20-192.168.0.30', 'user-access-manager'));
define('TXT_UAM_GROUP_READ_ACCESS', __('Read access', 'user-access-manager'));
define('TXT_UAM_GROUP_READ_ACCESS_DESC', __('The read access.', 'user-access-manager'));
define('TXT_UAM_GROUP_WRITE_ACCESS', __('Write access', 'user-access-manager'));
define('TXT_UAM_GROUP_WRITE_ACCESS_DESC', __('The write access.', 'user-access-manager'));
define('TXT_UAM_GROUP_ADDED', __('Group was added successfully.', 'user-access-manager'));
define('TXT_UAM_GROUP_NAME_ERROR', __('Group name can not be empty.', 'user-access-manager'));
define('TXT_UAM_DEL_GROUP', __('Group(s) was deleted successfully.', 'user-access-manager'));
define('TXT_UAM_NONE', __('none', 'user-access-manager')); 
define('TXT_UAM_ACCESS_GROUP_EDIT_SUC', __('User group edit successfully.', 'user-access-manager'));
define('TXT_UAM_IP_RANGE', __('IP range', 'user-access-manager'));


// --- Setup page ---
define('TXT_UAM_SETUP', __('Setup', 'user-access-manager'));
define('TXT_UAM_RESET_UAM', __('Reset User Access Manager', 'user-access-manager'));
define('TXT_UAM_RESET_UAM_DESC', __('Warning: The reset of the User Access Manager can not be undone. All settings and user groups will permanently lost.', 'user-access-manager'));
define('TXT_UAM_RESET', __('reset', 'user-access-manager'));
define('TXT_UAM_UPDATE_UAM_DB', __('Update User Access Manager database', 'user-access-manager'));
define('TXT_UAM_UPDATE_UAM_DB_DESC', __('Updates the database of the User Access Manager. Please backup your database before you perform the update.', 'user-access-manager'));
define('TXT_UAM_UPDATE', __('update', 'user-access-manager'));
define('TXT_UAM_UAM_RESET_SUC', __('User Access Manager was reset successfully', 'user-access-manager'));
define('TXT_UAM_UAM_DB_UPDATE_SUC', __('User Access Manager database was updated successfully', 'user-access-manager'));
define('TXT_UAM_UPDATE_BLOG', __('Update current blog', 'user-access-manager'));
define('TXT_UAM_UPDATE_NETWORK', __('Update network wide', 'user-access-manager'));


// --- About page ---
define('TXT_UAM_ABOUT', __('About', 'user-access-manager'));

// --- About page -> support ---
define('TXT_UAM_HOW_TO_SUPPORT', __('How to support me?', 'user-access-manager'));
define('TXT_UAM_SEND_REPORTS', __('<strong>Send me bug reports, bug fixes, code modifications or your ideas.</strong><br/>Help me to improve the plugin.', 'user-access-manager'));
define('TXT_UAM_MAKE_TRANSLATION', __('<strong>Make a translation of the plugin.</strong><br/>The give other users more comfort help me to translate it to all languages.', 'user-access-manager'));
define('TXT_UAM_DONATE', __('<strong>Donate via paypal</strong>', 'user-access-manager'));
define('TXT_UAM_PLACE_LINK', __('<strong>Place a link to the plugin in your blog/webpage.</strong>', 'user-access-manager'));

// --- About page -> thanks ---
define('TXT_UAM_THANKS', __('Thanks', 'user-access-manager'));
define('TXT_UAM_SPECIAL_THANKS', __('Special thanks go out to my wife for giving me the time to develop this plugin.', 'user-access-manager'));
define('TXT_UAM_THANKS_TO', __('I would like to thank all the guys which has helped me with this plugin:', 'user-access-manager'));
define('TXT_UAM_THANKS_OTHERS', __('all beta testers and all others I forgot', 'user-access-manager'));


// --- Edit forms ---
define('TXT_UAM_FULL_ACCESS', __('Full access', 'user-access-manager'));
define('TXT_UAM_MEMBER_OF_OTHER_GROUPS', __('Member of other user groups', 'user-access-manager'));
define('TXT_UAM_ADMIN_HINT', __('<strong>Note:</strong> An administrator has always access to all posts/pages.', 'user-access-manager'));
define('TXT_UAM_CREATE_GROUP_FIRST', __('Please create a user group first.', 'user-access-manager'));
define('TXT_UAM_NO_GROUP_AVAILABLE', __('No user group available.', 'user-access-manager'));
define('TXT_UAM_NO_RIGHTS', __('You have no rights to access this content.', 'user-access-manager'));
define('TXT_UAM_GROUPS', __('User Groups', 'user-access-manager'));
define('TXT_UAM_SET_UP_USERGROUPS', __('Set up user groups', 'user-access-manager'));
define('TXT_UAM_NONCE_FAILURE', __('Sorry, your nonce did not verify.'));

// --- Group info ---
define('TXT_UAM_INFO', __('Info', 'user-access-manager'));
define('TXT_UAM_GROUP_INFO', __('Group info', 'user-access-manager'));
define('TXT_UAM_GROUP_MEMBERSHIP_BY_POST', __('Group membership given by posts', 'user-access-manager'));
define('TXT_UAM_GROUP_MEMBERSHIP_BY_PAGE', __('Group membership given by pages', 'user-access-manager'));
define('TXT_UAM_GROUP_MEMBERSHIP_BY_CATEGORY', __('Group membership given by categories', 'user-access-manager'));
define('TXT_UAM_GROUP_MEMBERSHIP_BY_ROLE', __('Group membership given by role', 'user-access-manager'));
define('TXT_UAM_ASSIGNED_GROUPS', __('Assigned groups', 'user-access-manager'));


// --- File access ---
define('TXT_UAM_FILEINFO_DB_ERROR', __('Opening file info database failed.', 'user-access-manager'));
define('TXT_UAM_FILE_NOT_FOUND_ERROR', __('Error: File not found.', 'user-access-manager'));