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
define('TXT_UAM_PHP_VERSION_TO_LOW', __('Sorry you need at least PHP version 7.2 to use the User Access Manager. Your current PHP version is %s. See <a href="https://github.com/GM-Alex/user-access-manager/wiki/Troubleshoot">https://github.com/GM-Alex/user-access-manager/wiki/Troubleshoot</a> for more information.', 'user-access-manager'));
define('TXT_UAM_WORDPRESS_VERSION_TO_LOW', __('Sorry you need at least Wordpress version 3.0 to use the User Access Manager. Your current Wordpress version is %s.', 'user-access-manager'));
/** @noinspection HtmlUnknownTarget */
define('TXT_UAM_NEED_DATABASE_UPDATE', __('Please update the database of the User Access Manager. <a href="%s">Click here to proceed</a>', 'user-access-manager'));
define('TXT_UAM_ERROR', __('The following error occurred: %s', 'user-access-manager'));

// --- Multiple use ---
define('TXT_UAM_ALL', __('All', 'user-access-manager'));
define('TXT_UAM_ALL_USERS', __('All users (group and none group users)', 'user-access-manager'));
define('TXT_UAM_ONLY_GROUP_USERS', __('Only group users', 'user-access-manager'));
define('TXT_UAM_NONE', __('None', 'user-access-manager'));
define('TXT_UAM_YES', __('Yes', 'user-access-manager'));
define('TXT_UAM_NO', __('No', 'user-access-manager'));


// --- Setting Page ---
define('TXT_UAM_SETTINGS', __('Settings', 'user-access-manager'));
define('TXT_UAM_SETTINGS_GROUP_SECTION_DEFAULT', __('Default', 'user-access-manager'));

// --- Setting Page -> object settings ---
define('TXT_UAM_POST_TYPES_SECTION_SELECTION_SETTING', __('Object type', 'user-access-manager'));
define('TXT_UAM_POST_TYPES_SETTING', __('Post type settings', 'user-access-manager'));
define('TXT_UAM_TAXONOMIES_SETTING', __('Taxonomies settings', 'user-access-manager'));
define('TXT_UAM_DEFAULT_SETTING', __('Default settings', 'user-access-manager'));
define('TXT_UAM_DEFAULT_SETTING_DESC', __('Set up the behaviour if the object is locked', 'user-access-manager'));
define('TXT_UAM_DEFAULT_TITLE', __('Title', 'user-access-manager'));
define('TXT_UAM_DEFAULT_TITLE_DESC', __('Displayed text as title if user has no access', 'user-access-manager'));
define('TXT_UAM_HIDE_DEFAULT_TITLE', __('Hide title', 'user-access-manager'));
define('TXT_UAM_HIDE_DEFAULT_TITLE_DESC', __('Selecting "Yes" will show the text which is defined at "Title" if user has no access.', 'user-access-manager'));
define('TXT_UAM_DEFAULT_CONTENT', __('Content', 'user-access-manager'));
define('TXT_UAM_DEFAULT_CONTENT_DESC', __('Content displayed if user has no access. You can add an login-form by adding the keyword <strong>[LOGIN_FORM]</strong>. This form will shown on single %s, otherwise a link will shown.', 'user-access-manager'));
define('TXT_UAM_HIDE_DEFAULT', __('Hide complete', 'user-access-manager'));
define('TXT_UAM_HIDE_DEFAULT_DESC', __('Selecting "Yes" will hide %s if the user has no access.', 'user-access-manager'));
define('TXT_UAM_DEFAULT_COMMENT_CONTENT', __('Comment text', 'user-access-manager'));
define('TXT_UAM_DEFAULT_COMMENT_CONTENT_DESC', __('Displayed text as comment text if user has no access', 'user-access-manager'));
define('TXT_UAM_HIDE_DEFAULT_COMMENT', __('Hide comments', 'user-access-manager'));
define('TXT_UAM_HIDE_DEFAULT_COMMENT_DESC', __('Selecting "Yes" will show the text which is defined at "%s comment text" if user has no access.', 'user-access-manager'));
define('TXT_UAM_DEFAULT_COMMENTS_LOCKED', __('Lock comments', 'user-access-manager'));
define('TXT_UAM_DEFAULT_COMMENTS_LOCKED_DESC', __('Selecting "yes" allows users to comment even if the content is locked', 'user-access-manager'));
define('TXT_UAM_SHOW_DEFAULT_CONTENT_BEFORE_MORE', __('Show content before &lt;!--more--&gt; tag', 'user-access-manager'));
define('TXT_UAM_SHOW_DEFAULT_CONTENT_BEFORE_MORE_DESC', __('Shows the content before the &lt;!--more--&gt; tag and after that the defined text at "%s content". If no &lt;!--more--&gt; is set the defined text at "%s content" will shown.', 'user-access-manager')); //TODO
define('TXT_UAM_OBJECT_USE_DEFAULT', __('Use default settings for %s', 'user-access-manager'));
define('TXT_UAM_OBJECT_USE_DEFAULT_DESC', __('If selected the settings form the default type will be used.', 'user-access-manager'));
define('TXT_UAM_OBJECT_SETTING', __('%s settings', 'user-access-manager'));
define('TXT_UAM_OBJECT_SETTING_DESC', __('Set up the behaviour if the %s is locked', 'user-access-manager'));
define('TXT_UAM_OBJECT_TITLE', __('%s title', 'user-access-manager'));
define('TXT_UAM_OBJECT_TITLE_DESC', __('Displayed text as %s title if user has no access', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT_TITLE', __('Hide %s title', 'user-access-manager'));
define('TXT_UAM_HIDE_OBJECT_TITLE_DESC', __('Selecting "Yes" will show the text which is defined at "%s title" if user has no access.', 'user-access-manager'));
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
define('TXT_UAM_NO_ACCESS_IMAGE_TYPE', __('No access image type', 'user-access-manager'));
define('TXT_UAM_NO_ACCESS_IMAGE_TYPE_DESC', __('If you choose "Custom", please specify the full path to your image.', 'user-access-manager'));
define('TXT_UAM_NO_ACCESS_IMAGE_TYPE_DEFAULT', __('Default', 'user-access-manager'));
define('TXT_UAM_NO_ACCESS_IMAGE_TYPE_CUSTOM', __('Custom', 'user-access-manager'));
define('TXT_UAM_USE_CUSTOM_FILE_HANDLING_FILE', __('Use custom file handling file', 'user-access-manager'));
define('TXT_UAM_USE_CUSTOM_FILE_HANDLING_FILE_DESC', __('Selecting "Yes" will allow you to use your own config file.', 'user-access-manager'));
define('TXT_UAM_CUSTOM_FILE_HANDLING_FILE', __('Custom file handling file', 'user-access-manager'));
define('TXT_UAM_CUSTOM_FILE_HANDLING_FILE_DESC', __('Edit this content if you are using the custom file handling file setting.', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_DESC', __('If you select "Yes" all files will locked by a .htaccess file and only users with access can download files. <br/><strong style="color:red;">Note: If you activate this option the plugin will overwrite a \'.htaccess\' file at the upload folder, if you use already one to protect your files. Also if you have no permalinks activated your upload dir will protect by a \'.htaccess\' with a random password and all old media files insert in a previous post/page will not work anymore. You have to update your posts/pages (not necessary if you have permalinks activated).</strong>', 'user-access-manager'));
define('TXT_UAM_LOCKED_DIRECTORY_TYPE', __('Locked directory type', 'user-access-manager'));
define('TXT_UAM_LOCKED_DIRECTORY_TYPE_DESC', __('"Wordpress" will only lock files handled by the wordpress media manager (recommended), "All" will lock all files at the upload directory, "Custom" will use a custom string.', 'user-access-manager'));
define('TXT_UAM_LOCKED_DIRECTORY_TYPE_WORDPRESS', __('Wordpress', 'user-access-manager'));
define('TXT_UAM_LOCKED_DIRECTORY_TYPE_ALL', __('All', 'user-access-manager'));
define('TXT_UAM_LOCKED_DIRECTORY_TYPE_CUSTOM', __('Custom', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_TYPES', __('Locked file types', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_TYPES_DESC', __('Lock all files, type in file types which you will lock if the post/page is locked or define file types which will not be locked. <strong>Note:</strong> If you have no problems use all to get the maximum security.', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_TYPES_ALL', __('All', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_TYPES_SELECTED', __('File types to lock: ', 'user-access-manager'));
define('TXT_UAM_LOCK_FILE_TYPES_NOT_SELECTED', __('File types not to lock: ', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE', __('.htaccess password', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE_DESC', __('Set up the password for the .htaccess access. This password is only needed if you need a direct access to your files.', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE_RANDOM', __('Use a random generated password.', 'user-access-manager'));
define('TXT_UAM_FILE_PASS_TYPE_USER', __('Use the password of the current logged in admin.', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE', __('Download type', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_DESC', __('Selecting the type for downloading. <strong>Note:</strong> For using fopen you need "safe_mode = off".', 'user-access-manager'));
define('TXT_UAM_INLINE_FILES', __('Inline file types', 'user-access-manager'));
define('TXT_UAM_INLINE_FILES_DESC', __('These files (comma separated) will be shown within the browser window and not downloaded (images are always inline).', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_NORMAL', __('Normal', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_FOPEN', __('fopen', 'user-access-manager'));
define('TXT_UAM_DOWNLOAD_TYPE_XSENDFILE', __('XSendfile', 'user-access-manager'));

// --- Setting Page -> editor settings ---
define('TXT_UAM_AUTHOR_SETTING', __('Authors settings', 'user-access-manager'));
define('TXT_UAM_AUTHOR_SETTING_DESC', __('Here you will find the settings for authors', 'user-access-manager'));
define('TXT_UAM_AUTHORS_HAS_ACCESS_TO_OWN', __('Authors always has access to own posts/pages', 'user-access-manager'));
define('TXT_UAM_AUTHORS_HAS_ACCESS_TO_OWN_DESC', __('If "Yes" is selected author will always have full access to their own posts or pages.', 'user-access-manager'));
define('TXT_UAM_AUTHORS_CAN_ADD_POSTS_TO_GROUPS', __('Authors can add content to their own groups', 'user-access-manager'));
define('TXT_UAM_AUTHORS_CAN_ADD_POSTS_TO_GROUPS_DESC', __('If "Yes" is selected author are able to restrict the content by adding it to their groups.', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE', __('Minimum user role with full access', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE_DESC', __('All user with a role equal or higher to this has full access.', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE_ADMINISTRATOR', __('Administrator', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE_EDITOR', __('Editor', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE_AUTHOR', __('Author', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE_CONTRIBUTOR', __('Contributor', 'user-access-manager'));
define('TXT_UAM_FULL_ACCESS_ROLE_SUBSCRIBER', __('Subscriber', 'user-access-manager'));

// --- Settings Page -> taxonomies ---
define('TXT_UAM_TAXONOMIES_SECTION_SELECTION_SETTING', __('Object type', 'user-access-manager'));
define('TXT_UAM_TAXONOMY_SETTING', __('Taxonomy settings', 'user-access-manager'));
define('TXT_UAM_TAXONOMY_SETTING_DESC', __('Set up the behaviour if a taxonomy is locked', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_DEFAULT', __('Hide empty', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_DEFAULT_DESC', __('Selecting "Yes" will hide empty taxonomies which are containing only empty childes or no childes.', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_OBJECT', __('Hide empty %s', 'user-access-manager'));
define('TXT_UAM_HIDE_EMPTY_OBJECT_DESC', __('Selecting "Yes" will hide empty %s which are containing only empty childes or no childes.', 'user-access-manager'));

// --- Settings Page -> cache ---
define('TXT_UAM_CACHE_SECTION_SELECTION_SETTING', __('Active caching method', 'user-access-manager'));
define('TXT_UAM_CACHE_SETTING', __('Cache settings', 'user-access-manager'));
define('TXT_UAM_NONE_SETTING', __('Cache deactivated', 'user-access-manager'));
define('TXT_UAM_NONE_SETTING_DESC', __('The cache is currently deactivated.', 'user-access-manager'));
define('TXT_UAM_FILESYSTEMCACHEPROVIDER_SETTING', __('File system cache', 'user-access-manager'));
define('TXT_UAM_FILESYSTEMCACHEPROVIDER_SETTING_DESC', __('This cache uses the file system for the cache.', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_PATH', __('Path', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_PATH_DESC', __('File system path to store the cache files.', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_METHOD', __('Method', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_METHOD_DESC', __('The caching method which should be used.', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_METHOD_SERIALIZE', __('PHP serialize', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_METHOD_IGBINARY', __('PHP igbinary (igbinary required)', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_METHOD_JSON', __('Json', 'user-access-manager'));
define('TXT_UAM_FS_CACHE_METHOD_VAR_EXPORT', __('PHP var_export', 'user-access-manager'));

// --- Setting Page -> other settings ---
define('TXT_UAM_OTHER_SETTING', __('Other settings', 'user-access-manager'));
define('TXT_UAM_OTHER_SETTING_DESC', __('Here you will find all other settings', 'user-access-manager'));
define('TXT_UAM_PROTECT_FEED', __('Protect Feed', 'user-access-manager'));
define('TXT_UAM_PROTECT_FEED_DESC', __('Selecting "Yes" will also protect your feed entries.', 'user-access-manager'));
define('TXT_UAM_REDIRECT', __('Redirect user', 'user-access-manager'));
define('TXT_UAM_REDIRECT_DESC', __('Setup what happen if a user visit a post/page with no access.', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_BLOG', __('To blog start page', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_LOGIN', __('To login page (wp-admin)', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_PAGE', __('Custom page: ', 'user-access-manager'));
define('TXT_UAM_REDIRECT_TO_URL', __('Custom URL: ', 'user-access-manager'));
define('TXT_UAM_LOCK_RECURSIVE', __('Lock recursive', 'user-access-manager'));
define('TXT_UAM_LOCK_RECURSIVE_DESC', __('Selecting "Yes" will lock all child posts/pages of a post/page if a user has no access to the parent page. Note: Setting this option to "No" could result in display errors relating to the hierarchy.', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_TEXT', __('Admin hint text', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_TEXT_DESC', __('The text which will shown behind the post/page.', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT', __('Show admin hint at Posts', 'user-access-manager'));
define('TXT_UAM_BLOG_ADMIN_HINT_DESC', sprintf(__('Selecting "Yes" will show the defined text at "%s" behind the post/page to an logged in admin to show him which posts/pages are locked if he visits his blog.', 'user-access-manager'), TXT_UAM_BLOG_ADMIN_HINT_TEXT));
define('TXT_UAM_SHOW_ASSIGNED_GROUPS', __('Show assigned groups', 'user-access-manager'));
define('TXT_UAM_SHOW_ASSIGNED_GROUPS_DESC', __('Show assigned groups next to the edit link', 'user-access-manager'));
define('TXT_UAM_HIDE_EDIT_LINK_ON_NO_ACCESS', __('Hide edit link on no access', 'user-access-manager'));
define('TXT_UAM_HIDE_EDIT_LINK_ON_NO_ACCESS_DESC', __('Hides the edit link if the user has no write access.', 'user-access-manager'));

// --- Setting Page -> default values ---
define('TXT_UAM_SETTING_DEFAULT_NO_RIGHTS', __('No rights!', 'user-access-manager'));
define('TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_ENTRY', __('Sorry you have no rights to view this entry!', 'user-access-manager'));
define('TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_COMMENTS', __('Sorry no rights to view comments!', 'user-access-manager'));

// --- Setting Page -> update message ---
define('TXT_UAM_UPDATE_SETTING', __('Update settings', 'user-access-manager'));
define('TXT_UAM_UPDATE_SETTINGS', __('Settings updated.', 'user-access-manager'));


// --- User groups page ---
define('TXT_UAM_MANAGE_GROUP', __('Manage user groups', 'user-access-manager'));
define('TXT_UAM_USER_GROUPS_SETTING', __('User groups', 'user-access-manager'));
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
define('TXT_UAM_USER_GROUP_EDIT_SUCCESS', __('User group edit successfully.', 'user-access-manager'));
define('TXT_UAM_IP_RANGE', __('IP range', 'user-access-manager'));
define('TXT_UAM_DEFAULT_USER_GROUPS_SETTING', __('Default user groups', 'user-access-manager'));
define('TXT_UAM_DEFAULT_USER_GROUPS_SECTION_SELECTION_SETTING', __('Default user groups for object type', 'user-access-manager'));
define('TXT_UAM_UPDATE_DEFAULT_USER_GROUPS', __('Update default user groups', 'user-access-manager'));
define('TXT_UAM_SET_DEFAULT_USER_GROUP_SUCCESS', __('Default user groups updated', 'user-access-manager'));
define('TXT_UAM_POST_TYPE', __('Post Type', 'user-access-manager'));
define('TXT_UAM_TAXONOMY_TYPE', __('Taxonomy', 'user-access-manager'));


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
define('TXT_UAM_UAM_DB_UPDATE_FAILURE', __('Failure on User Access Manager database update', 'user-access-manager'));
define('TXT_UAM_UPDATE_BLOG', __('Update current blog', 'user-access-manager'));
define('TXT_UAM_UPDATE_NETWORK', __('Update network wide', 'user-access-manager'));
define('TXT_UAM_UPDATE_BACKUP', __('Backup the uam database tables', 'user-access-manager'));
define('TXT_UAM_REPAIR_DATABASE', __('Repair the database', 'user-access-manager'));
define('TXT_UAM_REPAIR_DATABASE_DESCRIPTION', __('Try to repair the database.', 'user-access-manager'));
define('TXT_UAM_REPAIR_DATABASE_REPAIR_NOW', __('repair now', 'user-access-manager'));
define('TXT_UAM_REPAIR_DATABASE_SUCCESS', __('Database repair successfull', 'user-access-manager'));
define('TXT_UAM_DATABASE_BROKEN', __('<b style="color:red;">Your UAM database seems broken. You should try to repair it.</b>', 'user-access-manager'));
define('TXT_UAM_DATABASE_OK', __('<b style="color:green;">Your UAM database seems to be in good condition.</b>', 'user-access-manager'));
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
define('TXT_UAM_SEND_REPORTS_HEAD', __('Help me to improve the plugin', 'user-access-manager'));
define('TXT_UAM_SEND_REPORTS', __('Send me bug reports, bug fixes, pull requests or your ideas. See: <a href="https://github.com/GM-Alex/user-access-manager">https://github.com/GM-Alex/user-access-manager</a>', 'user-access-manager'));
define('TXT_UAM_CREATE_TRANSLATION_HEAD', __('Create a translation of the plugin', 'user-access-manager'));
define('TXT_UAM_CREATE_TRANSLATION', __('Give other users more comfort help me to translate it to all languages. See: <a href="https://translate.wordpress.org/projects/wp-plugins/user-access-manager">https://translate.wordpress.org/projects/wp-plugins/user-access-manager</a>', 'user-access-manager'));
define('TXT_UAM_DONATE_HEAD', __('Donate via PayPal', 'user-access-manager'));
define('TXT_UAM_SUPPORT_ME_ON_STEADY_HEAD', __('Support me on Steady', 'user-access-manager'));
define('TXT_UAM_SPREAD_THE_WORD_HEAD', __('Spread the word', 'user-access-manager'));
define('TXT_UAM_SPREAD_THE_WORD', __('Write about the plugin and place a link to the plugin in your blog/website.', 'user-access-manager'));

// --- About page -> thanks ---
define('TXT_UAM_THANKS', __('Thanks', 'user-access-manager'));
define('TXT_UAM_STEADY_BE_THE_FIRST', __('Be the first one supporting me on steady!', 'user-access-manager'));
define('TXT_UAM_TOP_SUPPORTERS', __('Top supporters', 'user-access-manager'));
define('TXT_UAM_SUPPORTERS', __('Supporters', 'user-access-manager'));
define('TXT_UAM_SPECIAL_THANKS', __('Special thanks', 'user-access-manager'));
define('TXT_UAM_SPECIAL_THANKS_FIRST', __('My wife for giving me the time to develop this plugin', 'user-access-manager'));
define('TXT_UAM_SPECIAL_THANKS_LAST', __('All testers and all others I forgot', 'user-access-manager'));


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
define('TXT_UAM_LOGIN_FORM_LOGOUT', __('Logout', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_WELCOME_MESSAGE', __('Welcome, %s!', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_REGISTER', __('Register', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_LOST_PASSWORD', __('Lost your password?', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_LOST_AND_FOUND_PASSWORD', __('Password Lost and Found', 'user-access-manager'));
define('TXT_UAM_LOGIN_FORM_REMEMBER_ME', __('Remember me', 'user-access-manager'));


// --- User group ---
define('TXT_UAM_GROUP_ASSIGNMENT_TIME', __('Setup time based group assignment', 'user-access-manager'));
define('TXT_UAM_GROUP_FROM_DATE', __('From', 'user-access-manager'));
define('TXT_UAM_GROUP_TO_DATE', __('To', 'user-access-manager'));
define('TXT_UAM_GROUP_FROM_TIME', __('From', 'user-access-manager'));
define('TXT_UAM_GROUP_TO_TIME', __('To', 'user-access-manager'));


// --- Dynamic user groups ---
define('TXT_UAM_USER', __('User', 'user-access-manager'));
define('TXT_UAM_ROLE', __('Role', 'user-access-manager'));
define('TXT_UAM_ADD_DYNAMIC_NOT_LOGGED_IN_USERS', __('Not logged in users', 'user-access-manager'));
define('TXT_UAM_ADD_DYNAMIC_GROUP', __('Add dynamic groups', 'user-access-manager'));

// --- Login widget ---
define('TXT_UAM_LOGIN_WIDGET_TITLE', __('UAM login widget', 'user-access-manager'));
define('TXT_UAM_LOGIN_WIDGET_DESC', __('User Access Manager login widget for users.', 'user-access-manager'));

// --- Info bar ---
define('TXT_UAM_INFO_BOX_UAM_PRO_HEAD', __('Get User Access Manager Pro!', 'user-access-manager'));
define('TXT_UAM_INFO_BOX_UAM_PRO_CONTENT', __('You want all the features? Guess what? You are already using the Pro version, because there is none. So it would be nice if you support me and become a supporter at steady, <b>especially if you use the plugin on a commercial site</b>. This will keep me motivated to do the support and spend my free time for the plugin. ;)', 'user-access-manager'));
define('TXT_UAM_INFO_BOX_DOCUMENTATION_HEAD', __('Need help?', 'user-access-manager'));
define('TXT_UAM_INFO_BOX_DOCUMENTATION_CONTENT', __('You got stuck using the User access manager? See <a href="https://github.com/GM-Alex/user-access-manager/wiki">https://github.com/GM-Alex/user-access-manager/wiki</a>', 'user-access-manager'));
