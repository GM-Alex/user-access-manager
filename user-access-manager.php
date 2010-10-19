<?php
/**
 * Plugin Name: User Access Manager
 * Plugin URI: http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/
 * Author URI: http://www.gm-alex.de/
 * Version: 1.1.4
 * Author: Alexander Schneider
 * Description: Manage the access to your posts, pages, categories and files.
 * 
 * user-access-manager.php
 *
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2010 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
*/

//Paths
load_plugin_textdomain(
	'user-access-manager', 
	false, 
	'user-access-manager/lang'
);

define(
	'UAM_URLPATH', 
    WP_PLUGIN_URL.'/user-access-manager/'
);

if (defined('UAM_LOCAL_DEBUG')) {
    //ONLY FOR MY LOCAL DEBUG
    define(
        'UAM_REALPATH',
        '/'.plugin_basename(dirname(__FILE__)).'/'
    );
} else {
    define(
        'UAM_REALPATH',
        WP_PLUGIN_DIR.'/'.plugin_basename(dirname(__FILE__)).'/'
    );
}


//Defines
require_once 'includes/database.define.php';
require_once 'includes/language.define.php';


//Check requirements
$stop = false;

//Check php version
$phpVersion = phpversion();

if (version_compare($phpVersion, "5.0") === -1) {
    add_action(
    	'admin_notices', 
    	create_function(
    		'', 
    		'echo \'<div id="message" class="error"><p><strong>'. 
    	    sprintf(TXT_UAM_PHP_VERSION_TO_LOW, $phpVersion). 
    		'</strong></p></div>\';'
    	)
    );
    
    $stop = true;
}

//Check wordpress version
global $wp_version;

if (version_compare($wp_version, "3.0") === -1) {
    add_action(
    	'admin_notices', 
    	create_function(
    		'', 
    		'echo \'<div id="message" class="error"><p><strong>'. 
    	    sprintf(TXT_UAM_WORDPRESS_VERSION_TO_LOW, $wp_version). 
    		'</strong></p></div>\';'
    	)
    );
    
    $stop = true;
}

//If we have a error stop plugin.
if ($stop) {
    return;
}


//Classes
require_once 'class/UserAccessManager.class.php';
require_once 'class/UamUserGroup.class.php';
require_once 'class/UamAccessHandler.class.php';

if (class_exists("UserAccessManager")) {
    $userAccessManager = new UserAccessManager();
}

//Initialize the admin panel
if (!function_exists("userAccessManagerAP")) {
    /**
     * Creates the filters and actions for the admin panel
     * 
     * @return null;
     */
    function userAccessManagerAP()
    {
        global $userAccessManager,
        $current_user;
        
        if (!isset($userAccessManager)) {
            return;
        }
        
        $userAccessManager->setAtAdminPanel();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        if ($userAccessManager->isDatabaseUpdateNecessary()) {
            $link = 'admin.php?page=uam_setup';
            
            add_action(
            	'admin_notices', 
            	create_function(
            		'', 
            		'echo \'<div id="message" class="error"><p><strong>'. 
            	    sprintf(TXT_UAM_NEED_DATABASE_UPDATE, $link). 
            		'</strong></p></div>\';'
            	)
            );
        }
        
        get_currentuserinfo();
        $curUserdata = get_userdata($current_user->ID);
        $uamAccessHandler = $userAccessManager->getAccessHandler();
        
        if ($uamAccessHandler->checkUserAccess()
            || $uamOptions['authors_can_add_posts_to_groups'] == 'true'
        ) {
            //Admin actions
            if (function_exists('add_action')) {
                add_action('admin_print_styles', array(&$userAccessManager, 'addStyles'));
                add_action('wp_print_scripts', array(&$userAccessManager, 'addScripts'));
                
                add_action('manage_posts_custom_column', array(&$userAccessManager, 'addPostColumn'), 10, 2);
                add_action('manage_pages_custom_column', array(&$userAccessManager, 'addPostColumn'), 10, 2);
                add_action('save_post', array(&$userAccessManager, 'savePostData'));
                
                add_action('manage_media_custom_column', array(&$userAccessManager, 'addPostColumn'), 10, 2);
                
                //Actions are only called when the attachment content is modified so we can't use it.
                //add_action('add_attachment', array(&$userAccessManager, 'savePostData'));
                //add_action('edit_attachment', array(&$userAccessManager, 'savePostData'));
                
                add_action('edit_user_profile', array(&$userAccessManager, 'showUserProfile'));
                add_action('profile_update', array(&$userAccessManager, 'saveUserData'));
    
                add_action('edit_category_form', array(&$userAccessManager, 'showCategoryEditForm'));
                add_action('create_category', array(&$userAccessManager, 'saveCategoryData'));
                add_action('edit_category', array(&$userAccessManager, 'saveCategoryData'));
            }
            
            //Admin filters
            if (function_exists('add_filter')) {
                //The filter we use instead of add|edit_attachment action, reason see top
                add_filter('attachment_fields_to_save', array(&$userAccessManager, 'saveAttachmentData'));
                
                add_filter('manage_posts_columns', array(&$userAccessManager, 'addPostColumnsHeader'));
                add_filter('manage_pages_columns', array(&$userAccessManager, 'addPostColumnsHeader'));
                
                add_filter('manage_users_columns', array(&$userAccessManager, 'addUserColumnsHeader'), 10);
                add_filter('manage_users_custom_column', array(&$userAccessManager, 'addUserColumn'), 10, 3);
                
                add_filter('manage_edit-category_columns', array(&$userAccessManager, 'addCategoryColumnsHeader'));
                add_filter('manage_category_custom_column', array(&$userAccessManager, 'addCategoryColumn'), 10, 3);
            }
            
            if ($uamOptions['lock_file'] == 'true') {
                add_action('media_meta', array(&$userAccessManager, 'showMediaFile'), 10, 2);
                add_filter('manage_media_columns', array(&$userAccessManager, 'addPostColumnsHeader'));
            }
        }
        
        //Clean up at deleting should be always done.
        if (function_exists('add_action')) {
            add_action('update_option_permalink_structure', array(&$userAccessManager, 'updatePermalink'));
            add_action('wp_dashboard_setup', array(&$userAccessManager, 'setupAdminDashboard'));
            add_action('delete_post', array(&$userAccessManager, 'removePostData'));
            add_action('delete_attachment', array(&$userAccessManager, 'removePostData'));
            add_action('delete_user', array(&$userAccessManager, 'removeUserData'));
            add_action('delete_category', array(&$userAccessManager, 'removeCategoryData'), 10, 2);
        }
        
        $userAccessManager->noRightsToEditContent();
    }
}

if (!function_exists("userAccessManagerAPMenu")) {
    /**
     * Creates the menu at the admin panel
     * 
     * @return null;
     */
    function userAccessManagerAPMenu()
    {
        global $userAccessManager,
        $current_user;
        
        if (!isset($userAccessManager)) {
            return;
        }
        
        $uamOptions = $userAccessManager->getAdminOptions();
        
        if (ini_get('safe_mode') 
            && $uamOptions['download_type'] == 'fopen'
        ) {
            add_action(
            	'admin_notices', 
            	create_function(
            		'', 
            		'echo \'<div id="message" class="error"><p><strong>'. 
            	    TXT_UAM_FOPEN_WITHOUT_SAVEMODE_OFF. 
            		'</strong></p></div>\';'
            	)
            );
        }
        
        $curUserdata = get_userdata($current_user->ID);
        $uamAccessHandler = $userAccessManager->getAccessHandler();
        
        if ($uamAccessHandler->checkUserAccess()) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access capabilities seems 
             * to be the right way, but it is way difficult.
             */
            
            //Admin main menu
            if (function_exists('add_menu_page')) {
                add_menu_page('User Access Manager', 'UAM', 'read', 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'), 'div');
            }
            
            //Admin sub menus
            if (function_exists('add_submenu_page')) {
                add_submenu_page('uam_usergroup', TXT_UAM_MANAGE_GROUP, TXT_UAM_MANAGE_GROUP, 'read', 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_UAM_SETTINGS, TXT_UAM_SETTINGS, 'read', 'uam_settings', array(&$userAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_UAM_SETUP, TXT_UAM_SETUP, 'read', 'uam_setup', array(&$userAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_UAM_ABOUT, TXT_UAM_ABOUT, 'read', 'uam_about', array(&$userAccessManager, 'printAdminPage'));
                
                do_action('uam_add_submenu');
            }
            /**
             * --- EOF ---
             */
        }
        
        if ($uamAccessHandler->checkUserAccess()
            || $uamOptions['authors_can_add_posts_to_groups'] == 'true'
        ) {
            //Admin meta boxes
            if (function_exists('add_meta_box')) {
                add_meta_box('uma_post_access', 'Access', array(&$userAccessManager, 'editPostContent'), 'post', 'side');
                add_meta_box('uma_post_access', 'Access', array(&$userAccessManager, 'editPostContent'), 'page', 'side');
            }
        }
    }
}

if (isset($userAccessManager)) {    
    //install
    if (function_exists('register_activation_hook')) {
        register_activation_hook(__FILE__, array(&$userAccessManager, 'install'));
    }
    
    //uninstall
    if (function_exists('register_uninstall_hook')) {
        register_uninstall_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
    } elseif (function_exists('register_deactivation_hook')) {
        //Fallback
        register_deactivation_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
    }
    
    //deactivation
    if (function_exists('register_deactivation_hook')) {
        register_deactivation_hook(__FILE__, array(&$userAccessManager, 'deactivate'));
    }
    
    //Redirect
    $uamOptions = $userAccessManager->getAdminOptions();
    
    if ($uamOptions['redirect'] != 'false' || isset($_GET['uamgetfile'])) {
        add_filter('wp_headers', array(&$userAccessManager, 'redirect'), 10, 2);
    }

    //Actions
    if (function_exists('add_action')) {
        add_action('wp_print_scripts', array(&$userAccessManager, 'addScripts'));
        add_action('wp_print_styles', array(&$userAccessManager, 'addStyles'));
        add_action('admin_init', 'userAccessManagerAP');
        add_action('admin_menu', 'userAccessManagerAPMenu');
    }
    
    //Filters
    if (function_exists('add_filter')) {
        add_filter('wp_get_attachment_thumb_url', array(&$userAccessManager, 'getFileUrl'), 10, 2);
        add_filter('wp_get_attachment_url', array(&$userAccessManager, 'getFileUrl'), 10, 2);
        add_filter('the_posts', array(&$userAccessManager, 'showPost'));
        add_filter('posts_where_paged', array(&$userAccessManager, 'showPostSql'));
        add_filter('wp_get_nav_menu_items', array(&$userAccessManager, 'showCustomMenu'));
        add_filter('comments_array', array(&$userAccessManager, 'showComment'));
        add_filter('get_pages', array(&$userAccessManager, 'showPage'));
        add_filter('get_terms', array(&$userAccessManager, 'showTerms'), 10, 2);
        add_filter('get_next_post_where', array(&$userAccessManager, 'showNextPreviousPost'));
        add_filter('get_previous_post_where', array(&$userAccessManager, 'showNextPreviousPost'));
        add_filter('post_link', array(&$userAccessManager, 'cachePostLinks'), 10, 2);
        add_filter('edit_post_link', array(&$userAccessManager, 'showGroupMembership'), 10, 2);
        add_filter('parse_query', array(&$userAccessManager, 'parseQuery'));
        add_filter('getarchives_where', array(&$userAccessManager, 'showPostSql'));
    }
}