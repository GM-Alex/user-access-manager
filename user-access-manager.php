<?php
/**
 * Plugin Name: User Access Manager
 * Plugin URI: http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/
 * Author URI: http://www.gm-alex.de/
 * Version: 1.2.5.0
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
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
*/

//Paths
load_plugin_textdomain('user-access-manager', false, 'user-access-manager/lang');
define('UAM_URLPATH', WP_PLUGIN_URL.'/user-access-manager/');

if (defined('UAM_LOCAL_DEBUG')) {
    define('UAM_REALPATH', plugin_basename(dirname(__FILE__)).'/'); //ONLY FOR MY LOCAL DEBUG
} else {
    define('UAM_REALPATH', WP_PLUGIN_DIR.'/'.plugin_basename(dirname(__FILE__)).'/');
}


//Defines
require_once 'includes/database.define.php';
require_once 'includes/language.define.php';


//Check requirements
$blStop = false;

//Check php version
$sPhpVersion = phpversion();

if (version_compare($sPhpVersion, "5.0") === -1) {
    add_action(
    	'admin_notices', 
    	create_function(
    		'', 
    		'echo \'<div id="message" class="error"><p><strong>'. 
    	    sprintf(TXT_UAM_PHP_VERSION_TO_LOW, $sPhpVersion).
    		'</strong></p></div>\';'
    	)
    );
    
    $blStop = true;
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
    
    $blStop = true;
}

//If we have a error stop plugin.
if ($blStop) {
    return;
}


//Classes
require_once 'class/UserAccessManager.class.php';
require_once 'class/UamUserGroup.class.php';
require_once 'class/UamAccessHandler.class.php';

if (class_exists("UserAccessManager")) {
    $oUserAccessManager = new UserAccessManager();
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
        global $oUserAccessManager;
        $oCurrentUser = $oUserAccessManager->getCurrentUser();
        
        if (!isset($oUserAccessManager)) {
            return;
        }
        
        $oUserAccessManager->setAtAdminPanel();
        $aUamOptions = $oUserAccessManager->getAdminOptions();
        
        if ($oUserAccessManager->isDatabaseUpdateNecessary()) {
            $sLink = 'admin.php?page=uam_setup';
            
            add_action(
            	'admin_notices', 
            	create_function(
            		'', 
            		'echo \'<div id="message" class="error"><p><strong>'. 
            	    sprintf(TXT_UAM_NEED_DATABASE_UPDATE, $sLink).
            		'</strong></p></div>\';'
            	)
            );
        }
        
        get_currentuserinfo();
        $oCurUserData = get_userdata($oCurrentUser->ID);
        $oUamAccessHandler = $oUserAccessManager->getAccessHandler();
        
        if ($oUamAccessHandler->checkUserAccess()
            || $aUamOptions['authors_can_add_posts_to_groups'] == 'true'
        ) {
            //Admin actions
            if (function_exists('add_action')) {
                add_action('admin_print_styles', array($oUserAccessManager, 'addStyles'));
                add_action('wp_print_scripts', array($oUserAccessManager, 'addScripts'));
                
                add_action('manage_posts_custom_column', array($oUserAccessManager, 'addPostColumn'), 10, 2);
                add_action('manage_pages_custom_column', array($oUserAccessManager, 'addPostColumn'), 10, 2);
                add_action('save_post', array($oUserAccessManager, 'savePostData'));
                
                add_action('manage_media_custom_column', array($oUserAccessManager, 'addPostColumn'), 10, 2);
                
                //Actions are only called when the attachment content is modified so we can't use it.
                //add_action('add_attachment', array($oUserAccessManager, 'savePostData'));
                //add_action('edit_attachment', array($oUserAccessManager, 'savePostData'));
                
                add_action('edit_user_profile', array($oUserAccessManager, 'showUserProfile'));
                add_action('profile_update', array($oUserAccessManager, 'saveUserData'));
    
                add_action('edit_category_form', array($oUserAccessManager, 'showCategoryEditForm'));
                add_action('create_category', array($oUserAccessManager, 'saveCategoryData'));
                add_action('edit_category', array($oUserAccessManager, 'saveCategoryData'));

                add_action('bulk_edit_custom_box', array($oUserAccessManager, 'addBulkAction'));
            }
            
            //Admin filters
            if (function_exists('add_filter')) {
                //The filter we use instead of add|edit_attachment action, reason see top
                add_filter('attachment_fields_to_save', array($oUserAccessManager, 'saveAttachmentData'));
                
                add_filter('manage_posts_columns', array($oUserAccessManager, 'addPostColumnsHeader'));
                add_filter('manage_pages_columns', array($oUserAccessManager, 'addPostColumnsHeader'));
                
                add_filter('manage_users_columns', array($oUserAccessManager, 'addUserColumnsHeader'), 10);
                add_filter('manage_users_custom_column', array($oUserAccessManager, 'addUserColumn'), 10, 3);
                
                add_filter('manage_edit-category_columns', array($oUserAccessManager, 'addCategoryColumnsHeader'));
                add_filter('manage_category_custom_column', array($oUserAccessManager, 'addCategoryColumn'), 10, 3);
            }
            
            if ($aUamOptions['lock_file'] == 'true') {
                add_action('media_meta', array($oUserAccessManager, 'showMediaFile'), 10, 2);
                add_filter('manage_media_columns', array($oUserAccessManager, 'addPostColumnsHeader'));
            }
        }
        
        //Clean up at deleting should be always done.
        if (function_exists('add_action')) {
            add_action('update_option_permalink_structure', array($oUserAccessManager, 'updatePermalink'));
            add_action('wp_dashboard_setup', array($oUserAccessManager, 'setupAdminDashboard'));
            add_action('delete_post', array($oUserAccessManager, 'removePostData'));
            add_action('delete_attachment', array($oUserAccessManager, 'removePostData'));
            add_action('delete_user', array($oUserAccessManager, 'removeUserData'));
            add_action('delete_category', array($oUserAccessManager, 'removeCategoryData'), 10, 2);
        }
        
        $oUserAccessManager->noRightsToEditContent();
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
        global $oUserAccessManager;
        $oCurrentUser = $oUserAccessManager->getCurrentUser();
        
        if (!isset($oUserAccessManager)) {
            return;
        }
        
        $aUamOptions = $oUserAccessManager->getAdminOptions();
        
        if (ini_get('safe_mode') 
            && $aUamOptions['download_type'] == 'fopen'
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
        
        $oCurUserData = get_userdata($oCurrentUser->ID);
        $oUamAccessHandler = $oUserAccessManager->getAccessHandler();
        
        if ($oUamAccessHandler->checkUserAccess()) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access capabilities seems 
             * to be the right way, but it is way difficult.
             */
            
            //Admin main menu
            if (function_exists('add_menu_page')) {
                add_menu_page('User Access Manager', 'UAM', 'read', 'uam_usergroup', array($oUserAccessManager, 'printAdminPage'), 'div');
            }
            
            //Admin sub menus
            if (function_exists('add_submenu_page')) {
                add_submenu_page('uam_usergroup', TXT_UAM_MANAGE_GROUP, TXT_UAM_MANAGE_GROUP, 'read', 'uam_usergroup', array($oUserAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_UAM_SETTINGS, TXT_UAM_SETTINGS, 'read', 'uam_settings', array($oUserAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_UAM_SETUP, TXT_UAM_SETUP, 'read', 'uam_setup', array($oUserAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_UAM_ABOUT, TXT_UAM_ABOUT, 'read', 'uam_about', array($oUserAccessManager, 'printAdminPage'));
                
                do_action('uam_add_submenu');
            }
            /**
             * --- EOF ---
             */
        }
        
        if ($oUamAccessHandler->checkUserAccess()
            || $aUamOptions['authors_can_add_posts_to_groups'] == 'true'
        ) {
            //Admin meta boxes
            if (function_exists('add_meta_box')) {
                $aPostableTypes = $oUamAccessHandler->getPostableTypes();
                
                foreach ($aPostableTypes as $sPostableType) {
                    add_meta_box('uma_post_access', 'Access', array($oUserAccessManager, 'editPostContent'), $sPostableType, 'side');
                }
                
                /*add_meta_box('uma_post_access', 'Access', array($oUserAccessManager, 'editPostContent'), 'post', 'side');
                add_meta_box('uma_post_access', 'Access', array($oUserAccessManager, 'editPostContent'), 'page', 'side');*/
            }
        }
    }
}

if (!function_exists("userAccessManagerUninstall")) {
    function userAccessManagerUninstall() {
        $oUserAccessManager = new UserAccessManager();
        $oUserAccessManager->uninstall();
    }
}

if (isset($oUserAccessManager)) {
    //install
    if (function_exists('register_activation_hook')) {
        register_activation_hook(__FILE__, array($oUserAccessManager, 'install'));
    }
    
    //uninstall
    if (function_exists('register_uninstall_hook')) {
        register_uninstall_hook(__FILE__, 'userAccessManagerUninstall');
    } elseif (function_exists('register_deactivation_hook')) {
        //Fallback
        register_deactivation_hook(__FILE__, array($oUserAccessManager, 'uninstall'));
    }
    
    //deactivation
    if (function_exists('register_deactivation_hook')) {
        register_deactivation_hook(__FILE__, array($oUserAccessManager, 'deactivate'));
    }
    
    //Redirect
    $aUamOptions = $oUserAccessManager->getAdminOptions();
    
    if ($aUamOptions['redirect'] != 'false' || isset($_GET['uamgetfile'])) {
        add_filter('wp_headers', array($oUserAccessManager, 'redirect'), 10, 2);
    }

    //Actions
    if (function_exists('add_action')) {
        add_action('wp_print_scripts', array($oUserAccessManager, 'addScripts'));
        add_action('wp_print_styles', array($oUserAccessManager, 'addStyles'));
        add_action('admin_init', 'userAccessManagerAP');
        add_action('admin_menu', 'userAccessManagerAPMenu');
    }
    
    //Filters
    if (function_exists('add_filter')) {
        add_filter('wp_get_attachment_thumb_url', array($oUserAccessManager, 'getFileUrl'), 10, 2);
        add_filter('wp_get_attachment_url', array($oUserAccessManager, 'getFileUrl'), 10, 2);
        add_filter('the_posts', array($oUserAccessManager, 'showPost'));
        add_filter('posts_where_paged', array($oUserAccessManager, 'showPostSql'));
        add_filter('wp_get_nav_menu_items', array($oUserAccessManager, 'showCustomMenu'));
        add_filter('comments_array', array($oUserAccessManager, 'showComment'));
        add_filter('the_comments', array($oUserAccessManager, 'showComment'));
        add_filter('get_pages', array($oUserAccessManager, 'showPage'));
        add_filter('get_terms', array($oUserAccessManager, 'showTerms'), 10, 2);
        add_filter('get_next_post_where', array($oUserAccessManager, 'showNextPreviousPost'));
        add_filter('get_previous_post_where', array($oUserAccessManager, 'showNextPreviousPost'));
        add_filter('post_link', array($oUserAccessManager, 'cachePostLinks'), 10, 2);
        add_filter('edit_post_link', array($oUserAccessManager, 'showGroupMembership'), 10, 2);
        add_filter('parse_query', array($oUserAccessManager, 'parseQuery'));
        add_filter('getarchives_where', array($oUserAccessManager, 'showPostSql'));
    }
}