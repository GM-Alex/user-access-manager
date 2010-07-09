<?php

/**
 * Plugin Name: User Access Manager
 * Plugin URI: http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/
 * Author URI: http://www.gm-alex.de/
 * Version: 1.0 Beta
 * Author: Alexander Schneider
 * Description: Manage the access to your posts and pages. <strong>Note:</strong> <em> If you use already a '.htaccess' file to protect your files the plugin will <strong>overwrite</strong> the '.htaccess' file. If you activate the plugin and you have no permalinks activated your upload dir will protect by a '.htaccess' with a random password and all old media files insert in a previous post/page will not work anymore. You have to update your posts/pages (not necessary if you have permalinks activated). You can disabel the file locking and set up an other password for the '.htaccess' file at the UAM setting page.</em>
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
define(
	'UAM_URLPATH', 
    WP_PLUGIN_URL.'/user-access-manager/'
);
define(
    'UAM_REALPATH',
    '/'.plugin_basename(dirname(__FILE__))
);

//Defines
require_once 'includes/database.define.php';
require_once 'includes/language.define.php';

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
     * Creates the menu at the admin panel
     * 
     * @return null;
     */
    function userAccessManagerAP()
    {
        global $userAccessManager, 
        $uamUserGroup, 
        $uamAccessHandler, 
        $wp_version, 
        $current_user;
        
        if (!isset($userAccessManager)) {
            return;
        }
        
        $userAccessManager->atAdminPanel = true;
        $uamOptions = $userAccessManager->getAdminOptions();
        $userAccessManager->update();
        
        get_currentuserinfo();
        $cur_userdata = get_userdata($current_user->ID);
        
        if ($cur_userdata->user_level >= $uamOptions['full_access_level']) {
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
                add_submenu_page('uam_usergroup', TXT_MANAGE_GROUP, TXT_MANAGE_GROUP, 'read', 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_SETTINGS, TXT_SETTINGS, 'read', 'uam_settings', array(&$userAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_SETUP, TXT_SETUP, 'read', 'uam_setup', array(&$userAccessManager, 'printAdminPage'));
                add_submenu_page('uam_usergroup', TXT_ABOUT, TXT_ABOUT, 'read', 'uam_about', array(&$userAccessManager, 'printAdminPage'));
            }
            /**
             * --- EOF ---
             */
            
            //Admin meta boxes
            if (function_exists('add_meta_box')) {
                add_meta_box('uma_post_access', 'Access', array(&$userAccessManager, 'editPostContent'), 'post', 'side');
                add_meta_box('uma_post_access', 'Access', array(&$userAccessManager, 'editPostContent'), 'page', 'side');
            }
        
            //Admin actions
            if (function_exists('add_action')) {
                add_action('admin_print_styles', array(&$userAccessManager, 'addStyles'));
                add_action('wp_print_scripts', array(&$userAccessManager, 'addScripts'));
                
                add_action('manage_posts_custom_column', array(&$userAccessManager, 'addPostColumn'), 10, 2);
                add_action('manage_pages_custom_column', array(&$userAccessManager, 'addPostColumn'), 10, 2);
                add_action('save_post', array(&$userAccessManager, 'savePostData'));
                
                add_action('manage_media_custom_column', array(&$userAccessManager, 'addPostColumn'), 10, 2);
                add_action('add_attachment', array(&$userAccessManager, 'savePostData'));
                add_action('attachment_fields_to_save', array(&$userAccessManager, 'savePostData'));
                
                add_action('edit_user_profile', array(&$userAccessManager, 'showUserProfile'));
                add_action('profile_update', array(&$userAccessManager, 'saveUserData'));
    
                add_action('edit_category_form', array(&$userAccessManager, 'showCategoryEditForm'));
                add_action('edit_category', array(&$userAccessManager, 'saveCategoryData'));
            }
            
            //Admin filters
            if (function_exists('add_filter')) {
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
            add_action('delete_category', array(&$userAccessManager, 'removeCategoryData'));
        }
        
        $userAccessManager->noRightsToEditContent();
    }
}

if (isset($userAccessManager)) {
    load_plugin_textdomain(
    	'user-access-manager', 
    	false, 
    	dirname(plugin_basename(__FILE__))
    );
    
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
    
    if ($uamOptions['redirect'] != 'false' || isset($_GET['getfile'])) {
        add_action('template_redirect', array(&$userAccessManager, 'redirect'));
    }

    //Actions
    if (function_exists('add_action')) {
        add_action('wp_print_scripts', array(&$userAccessManager, 'addScripts'));
        add_action('wp_print_styles', array(&$userAccessManager, 'addStyles'));
        add_action('admin_menu', 'userAccessManagerAP');
    }
    
    //Filters
    if (function_exists('add_filter')) {
        add_filter('wp_get_attachment_thumb_url', array(&$userAccessManager, 'getFileUrl'), 10, 2);
        add_filter('wp_get_attachment_url', array(&$userAccessManager, 'getFileUrl'), 10, 2);
        add_filter('the_posts', array(&$userAccessManager, 'showPost'));
        add_filter('comments_array', array(&$userAccessManager, 'showComment'));
        add_filter('get_pages', array(&$userAccessManager, 'showPage'));
        add_filter('get_terms', array(&$userAccessManager, 'showCategory'));
        add_filter('get_next_post_where', array(&$userAccessManager, 'showNextPreviousPost'));
        add_filter('get_previous_post_where', array(&$userAccessManager, 'showNextPreviousPost'));
        add_filter('the_title', array(&$userAccessManager, 'showTitle'), 10, 2);
        add_filter('posts_where', array(&$userAccessManager, 'showPostSql'));
    }
}