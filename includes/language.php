<?php
/**
 * language.php
 *
 * The language definitions.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

// --- Error Messages ---
define('TXT_UAM_PHP_VERSION_TO_LOW', __('Sorry you need at least PHP version 5.4 to use the User Access Manager. Your current PHP version is %s. See <a href="https://github.com/GM-Alex/user-access-manager/wiki/Troubleshoot"/>https://github.com/GM-Alex/user-access-manager/wiki/Troubleshoot</a> for more information.', 'user-access-manager'));
define('TXT_UAM_WORDPRESS_VERSION_TO_LOW', __('Sorry you need at least Wordpress version 3.0 to use the User Access Manager. Your current Wordpress version is %s.', 'user-access-manager'));
/** @noinspection HtmlUnknownTarget */
define('TXT_UAM_NEED_DATABASE_UPDATE', __('Please update the database of the User Access Manager. <a href="%s">Click here to proceed</a>', 'user-access-manager'));

// --- Multiple use ---
define('TXT_UAM_ALL', __('all', 'user-access-manager'));
define('TXT_UAM_ONLY_GROUP_USERS', __('only group users', 'user-access-manager'));
define('TXT_UAM_YES', __('Yes', 'user-access-manager'));
define('TXT_UAM_NO', __('No', 'user-access-manager'));


// --- Setting Page ---
define('TXT_UAM_SETTINGS', __('Settings', 'user-access-manager'));

// --- Setting Page -> object settings ---
define('TXT_UAM_OBJECT_SETTING', __('%s settings', 'user-access-manager'));
define('TXT_UAM_OBJECT_SETTING_DESC', __('Set up the behaviour if the %s is locked', 'user-access-manager'));
define('TXT_UAM_OBJECT_TITLE', __('%s title', 'user-access-manager'));
define('TXT_UAM_OBJECT_TITLE_DESC', __('Displayed text as %s title if user has no access', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT_TITLE', __('Hide %s title', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT_TITLE_DESC', __('Selecting "Yes" will show the text which is defined at "%s" if user has no access.', 'user-access-manager'));
define('TXT_UAM_OBJECT_CONTENT', __('%s content', 'user-access-manager'));
define('TXT_UAM_OBJECT_CONTENT_DESC', __('Content displayed if user has no access. You can add an login-form by adding the keyword <strong>[LOGIN_FORM]</strong>. This form will shown on single %s, otherwise a link will shown.', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT', __('Hide complete %s', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT_DESC', __('Selecting "Yes" will hide %s if the user has no access.', 'user-access-manager'));
define('TXT_UAM_OBJECT_COMMENT_CONTENT', __('%s comment text', 'user-access-manager'));
define('TXT_UAM_OBJECT_COMMENT_CONTENT_DESC', __('Displayed text as %s comment text if user has no access', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT_COMMENT', __('Hide %s comments', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT_COMMENT_DESC', __('Selecting "Yes" will show the text which is defined at "%s comment text" if user has no access.', 'user-access-manager'));
define('TXT_UAM_OBJECT_COMMENTS_LOCKED', __('Lock %s comments', 'user-access-manager'));
define('TXT_UAM_OBJECT_COMMENTS_LOCKED_DESC', __('Selecting "yes" also locks comments on locked %s', 'user-access-manager'));
define('TXT_UAM_SHOW_OBJECT_CONTENT_BEFORE_MORE', __('Show %s content before &lt;!--more--&gt; tag', 'user-access-manager'));
define('TXT_UAM_SHOW_OBJECT_CONTENT_BEFORE_MORE_DESC', __('Shows the %s content before the &lt;!--more--&gt; tag and after that the defined text at "%s content". If no &lt;!--more--&gt; is set the defined text at "%s content" will shown.', 'user-access-manager')); //TODO

// --- Setting Page -> file settings ---
define('TXT_UAM_FILE_SETTING', __('File settings', 'user-access-manager'));
define('TXT_UAM_FILE_SETTING_DESC', __('Set up the behaviour of files', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE', __('Lock files', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_DESC', __('If you select "Yes" all files will locked by a .htaccess file and only users with access can download files. <br/><strong style="color:red;">Note: If you activate this option the plugin will overwrite a \'.htaccess\' file at the upload folder, if you use already one to protect your files. Also if you have no permalinks activated your upload dir will protect by a \'.htaccess\' with a random password and all old media files insert in a previous post/page will not work anymore. You have to update your posts/pages (not necessary if you have permalinks activated).</strong>', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_TYPES', __('Locked file types', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_TYPES_DESC', __('Lock all files, type in file types which you will lock if the post/page is locked or define file types which will not be locked. <strong>Note:</strong> If you have no problems use all to get the maximum security.', 'user-access-manager'));
define('TXT_UAM_LOCKED_FILE_TYPES', __('File types to lock: ', 'user-access-manager'));
define('TXT_UAM_NOT_LOCKED_FILE_TYPES', __('File types not to lock: ', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE', __('.htaccess password', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE_DESC', __('Set up the password for the .htaccess access. This password is only needed if you need a direct access to your files.', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE_RANDOM', __('Use a random generated password.', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE_USER', __('Use the password of the current logged in admin.', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE', __('Download type', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_DESC', __('Selecting the type for downloading. <strong>Note:</strong> For using fopen you need "safe_mode = off".', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_NORMAL', __('Normal', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_FOPEN', __('fopen', 'user-access-manager'));

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

// --- Settings Page -> taxonomies ---
define('TXT_UAM_TAXONOMY_SETTING', __('Taxonomy settings', 'user-access-manager'));
define('TXT_UAM_TAXONOMY_SETTING_DESC', __('Set up the behaviour if a taxonomy is locked', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_OBJECT', __('Hide empty %s', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_OBJECT_DESC', __('Selecting "Yes" will hide empty %s which are containing only empty childes or no childes.', 'user-access-manager'));

// --- Setting Page -> other settings ---
define('TXT_UAM_OTHER_SETTING', __('Other settings', 'user-access-manager'));
define('TXT_UAM_OTHER_SETTING_DESC', __('Here you will find all other settings', 'user-access-manager'));
define('TXT_UAM_PROTECT_FEED', __('Protect Feed', 'user-access-manager'));
define('TXT_UAM_PROTECT_FEED_DESC', __('Selecting "Yes" will also protect your feed entries.', 'user-access-manager'));
define('TXT_UAM_REDIRECT', __('Redirect user', 'user-access-manager'));
define('TXT_UAM_REDIRECT_DESC', __('Setup what happen if a user visit a post/page with no access.', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_BLOG', __('To blog start page', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_PAGE', __('Custom page: ', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_URL', __('Custom URL: ', 'user-access-manager'));
define('TXT_UAM_LOCK_RECURSIVE', __('Lock recursive', 'user-access-manager'));
define('TXT_UAM_LOCK_RECURSIVE_DESC', __('Selecting "Yes" will lock all child posts/pages of a post/page if a user has no access to the parent page. Note: Setting this option to "No" could result in display errors relating to the hierarchy.', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_TEXT', __('Admin hint text', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_TEXT_DESC', __('The text which will shown behind the post/page.', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT', __('Show admin hint at Posts', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_DESC', sprintf(__('Selecting "Yes" will show the defined text at "%s" behind the post/page to an logged in admin to show him which posts/pages are locked if he visits his blog.', 'user-access-manager'), TXT_UAM_BLOG_ADMIN_HINT_TEXT));

// --- Setting Page -> default values ---
define('TXT_UAM_SETTING_DEFAULT_NO_RIGHTS', __('No rights!', 'user-access-manager'));
define('TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_ENTRY', __('Sorry you have no rights to view this entry!', 'user-access-manager'));
define('TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_COMMENTS', __('Sorry no rights to view comments!', 'user-access-manager'));

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
define('TXT_UAM_DELETE_GROUP', __('Group(s) was deleted successfully.', 'user-access-manager'));
define('TXT_UAM_NONE', __('none', 'user-access-manager'));
define('TXT_UAM_ACCESS_GROUP_EDIT_SUCCESS', __('User group edit successfully.', 'user-access-manager'));
define('TXT_UAM_IP_RANGE', __('IP range', 'user-access-manager'));


// --- Setup page ---
define('TXT_UAM_SETUP', __('Setup', 'user-access-manager'));
define('TXT_UAM_SETUP_DANGER_ZONE', __('Danger Zone', 'user-access-manager'));
define('TXT_UAM_RESET_UAM', __('Reset User Access Manager', 'user-access-manager'));
define('TXT_UAM_RESET_UAM_DESCRIPTION', __('Type \'reset\' in the input field to reset the User Access Manager.', 'user-access-manager'));
define('TXT_UAM_RESET_UAM_DESC_WARNING', __('Warning: The reset of the User Access Manager can not be undone. All settings and user groups will permanently lost.', 'user-access-manager'));
define('TXT_UAM_RESET', __('reset now', 'user-access-manager'));
define('TXT_UAM_UPDATE_UAM_DB', __('Update User Access Manager database', 'user-access-manager'));
define('TXT_UAM_UPDATE_UAM_DB_DESCRIPTION', __('Updates the database of the User Access Manager. Please backup your database before you perform the update.', 'user-access-manager'));
define('TXT_UAM_UPDATE', __('update now', 'user-access-manager'));
define('TXT_UAM_UAM_RESET_SUCCESS', __('User Access Manager was reset successfully', 'user-access-manager'));
define('TXT_UAM_UAM_DB_UPDATE_SUCCESS', __('User Access Manager database was updated successfully', 'user-access-manager'));
define('TXT_UAM_UPDATE_BLOG', __('Update current blog', 'user-access-manager'));
define('TXT_UAM_UPDATE_NETWORK', __('Update network wide', 'user-access-manager'));
define('TXT_UAM_UPDATE_BACKUP', __('Backup the uam database tables', 'user-access-manager'));
define('TXT_UAM_REVERT_DATABASE', __('Revert the database', 'user-access-manager'));
define('TXT_UAM_REVERT_DATABASE_DESCRIPTION', __('Choose a backup to revert the database to this user access manager database version. <b>Note: The user access manager database version differs from the user access manager version.</b>', 'user-access-manager'));
define('TXT_UAM_REVERT_DATABASE_REVERT_NOW', __('revert now', 'user-access-manager'));
define('TXT_UAM_REVERT_DATABASE_SUCCESS', __('Revert successfull', 'user-access-manager'));
define('TXT_UAM_DELETE_DATABASE_BACKUP', __('Delete a database backup', 'user-access-manager'));
define('TXT_UAM_DELETE_DATABASE_BACKUP_DESCRIPTION', __('Choose a backup to delete. <b>Note: That cannot be undone.</b>', 'user-access-manager'));
define('TXT_UAM_DELETE_DATABASE_BACKUP_DELETE_NOW', __('delete now', 'user-access-manager'));
define('TXT_UAM_DELETE_DATABASE_BACKUP_SUCCESS', __('Backup deleted successfully', 'user-access-manager'));


// --- About page ---
define('TXT_UAM_ABOUT', __('About', 'user-access-manager'));

// --- About page -> support ---
define('TXT_UAM_HOW_TO_SUPPORT', __('How to support me?', 'user-access-manager'));
define('TXT_UAM_SEND_REPORTS', __('<strong>Send me bug reports, bug fixes, code modifications or your ideas.</strong><br/>Help me to improve the plugin.', 'user-access-manager'));
define('TXT_UAM_MAKE_TRANSLATION', __('<strong>Make a translation of the plugin.</strong><br/>The give other users more comfort help me to translate it to all languages.', 'user-access-manager'));
define('TXT_UAM_DONATE', __('<strong>Donate via PayPal</strong>', 'user-access-manager'));
define('TXT_UAM_PLACE_LINK', __('<strong>Place a link to the plugin in your blog/website.</strong>', 'user-access-manager'));

// --- About page -> thanks ---
define('TXT_UAM_THANKS', __('Thanks', 'user-access-manager'));
define('TXT_UAM_SPECIAL_THANKS', __('Special thanks go out to my wife for giving me the time to develop this plugin.', 'user-access-manager'));
define('TXT_UAM_THANKS_TO', __('I would like to thank all the guys which has helped me with this plugin:', 'user-access-manager'));
define('TXT_UAM_THANKS_OTHERS', __('all beta testers and all others I forgot', 'user-access-manager'));


// --- Columns ---
define('TXT_UAM_COLUMN_ACCESS', __('Access', 'user-access-manager'));
define('TXT_UAM_COLUMN_USER_GROUPS', __('UAM User Groups', 'user-access-manager'));


// --- Edit forms ---
define('TXT_UAM_FULL_ACCESS', __('Full access', 'user-access-manager'));
define('TXT_UAM_MEMBER_OF_OTHER_GROUPS', __('Member of %s other user groups', 'user-access-manager'));
define('TXT_UAM_ADMIN_HINT', __('<strong>Note:</strong> An administrator has always access to all posts/pages.', 'user-access-manager'));
define('TXT_UAM_CREATE_GROUP_FIRST', __('Please create a user group first.', 'user-access-manager'));
define('TXT_UAM_NO_GROUP_AVAILABLE', __('No user group available.', 'user-access-manager'));
define('TXT_UAM_NO_RIGHTS_TITLE', __('No rights', 'user-access-manager'));
define('TXT_UAM_NO_RIGHTS_MESSAGE', __('You have no rights to access this content.', 'user-access-manager'));
define('TXT_UAM_GROUPS', __('User Groups', 'user-access-manager'));
define('TXT_UAM_SET_UP_USER_GROUPS', __('Set up user groups', 'user-access-manager'));
define('TXT_UAM_NONCE_FAILURE_TITLE', __('Nonce error', 'user-access-manager'));
define('TXT_UAM_NONCE_FAILURE_MESSAGE', __('Sorry, your nonce did not verify.', 'user-access-manager'));

// --- Group info ---
define('TXT_UAM_INFO', __('Info', 'user-access-manager'));
define('TXT_UAM_GROUP_INFO', __('Group info', 'user-access-manager'));
define('TXT_UAM_GROUP_MEMBERSHIP_BY', __('Group membership given by %s', 'user-access-manager'));
define('TXT_UAM_ASSIGNED_GROUPS', __('Assigned groups', 'user-access-manager'));
define('TXT_UAM_GROUP_TYPE__ROLE_', __('Role', 'user-access-manager'));
define('TXT_UAM_GROUP_TYPE__USER_', __('User', 'user-access-manager'));
define('TXT_UAM_GROUP_TYPE__TERM_', __('Term', 'user-access-manager'));
define('TXT_UAM_GROUP_TYPE__POST_', __('Post', 'user-access-manager'));


// --- File access ---
define('TXT_UAM_FILE_INFO_DB_ERROR', __('Opening file info database failed.', 'user-access-manager'));
define('TXT_UAM_FILE_NOT_FOUND_ERROR_TITLE', __('Error: File not found.', 'user-access-manager'));
define('TXT_UAM_FILE_NOT_FOUND_ERROR_MESSAGE', __('The file you are looking for wasn\'t found.', 'user-access-manager'));


// --- Login form ---
define('TXT_UAM_LOGIN_FORM_USERNAME', __('Username', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_PASSWORD', __('Password', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_LOGIN', __('Login', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_REGISTER', __('Register', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_LOST_PASSWORD', __('Lost your password?', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_LOST_AND_FOUND_PASSWORD', __('Password Lost and Found', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_REMEMBER_ME', __('Remember me', 'user-access-manager'));
