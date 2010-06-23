<?php

/**
 Plugin Name: User Access Manager
 Plugin URI: http://www.gm-alex.de/projects/wordpress/plugins/user-access-manager/
 Author URI: http://www.gm-alex.de/
 Version: 0.9.1.3
 Author: Alexander Schneider
 Description: Manage the access to your posts and pages. <strong>Note:</strong> <em>If you activate the plugin your upload dir will protect by a '.htaccess' with a random password and all old media files insert in a previous post/page will not work anymore. You have to update your posts/pages. If you use already a '.htaccess' file to protect your files the plugin will <strong>overwrite</strong> the '.htaccess'. You can disabel the file locking and set up an other password for the '.htaccess' file at the UAM setting page.</em>
 * 
 * user-access-manager.php
 * 
 * Uses an Image by: Everaldo Coelho - http://www.everaldo.com/
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

//DB
global $wpdb;
require_once 'includes/UserAccessManager.class.php';
require_once 'includes/UamUserGroup.class.php';
define('DB_ACCESSGROUP', $wpdb->prefix . 'uam_accessgroups');
define('DB_ACCESSGROUP_TO_POST', $wpdb->prefix . 'uam_accessgroup_to_post');
define('DB_ACCESSGROUP_TO_USER', $wpdb->prefix . 'uam_accessgroup_to_user');
define('DB_ACCESSGROUP_TO_CATEGORY', $wpdb->prefix . 'uam_accessgroup_to_category');
define('DB_ACCESSGROUP_TO_ROLE', $wpdb->prefix . 'uam_accessgroup_to_role');

//PATH
/*define(
	'UAM_URLPATH', 
    WP_CONTENT_URL.'/plugins/'.plugin_basename( dirname(__FILE__)).'/' 
);*/
define(
	'UAM_URLPATH', 
    WP_CONTENT_URL . '/plugins/user-access-manager/'
); //Localhost DEBUG

if (class_exists("UserAccessManager")) {
    $userAccessManager = new UserAccessManager();
}

if (class_exists("UamUserGroup")) {
    $uamUserGroup = new UamUserGroup(1);  
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
        global $userAccessManager, $uamUserGroup, $wp_version, $current_user, $wpdb;
        $userAccessManager->atAdminPanel = true;
        
        print_r($uamUserGroup->getRoles());
        print_r($uamUserGroup->getUsers());
        
        $uamOptions = $userAccessManager->getAdminOptions();
        if (!isset($userAccessManager)) {
            return;
        }
        
        if (function_exists('add_menu_page')) {
            add_menu_page('User Access Manager', 'UAM', 9, 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'), UAM_URLPATH . "gfx/icon.png");
        }
        
        if (function_exists('add_submenu_page')) {
            add_submenu_page('uam_usergroup', TXT_MANAGE_GROUP, TXT_MANAGE_GROUP, 9, 'uam_usergroup', array(&$userAccessManager, 'printAdminPage'));
            add_submenu_page('uam_usergroup', TXT_SETTINGS, TXT_SETTINGS, 9, 'uam_settings', array(&$userAccessManager, 'printAdminPage'));
            add_submenu_page('uam_usergroup', TXT_SETUP, TXT_SETUP, 9, 'uam_setup', array(&$userAccessManager, 'printAdminPage'));
        }
        
        if (function_exists('add_meta_box')) {
            get_currentuserinfo();
            $cur_userdata = get_userdata($current_user->ID);
            if ($cur_userdata->user_level == $uamOptions['full_access_level']) {
                add_meta_box('uma_post_access', 'Access', array(&$userAccessManager, 'edit_post_content'), 'post', 'side');
                add_meta_box('uma_post_access', 'Access', array(&$userAccessManager, 'edit_post_content'), 'page', 'side');
            }
        }

        //Admin actions
        $userAccessManager->update();
        add_action('manage_posts_custom_column', array(&$userAccessManager, 'add_post_column'), 10, 2);
        add_action('manage_pages_custom_column', array(&$userAccessManager, 'add_post_column'), 10, 2);
        add_action('manage_media_custom_column', array(&$userAccessManager, 'add_post_column'), 10, 2);
        add_action('save_post', array(&$userAccessManager, 'save_postdata'));
        add_action('add_attachment', array(&$userAccessManager, 'save_postdata'));
        add_action('attachment_fields_to_save', array(&$userAccessManager, 'save_attachmentdata'));
        add_action('delete_post', array(&$userAccessManager, 'remove_postdata'));
        add_action('delete_attachment', array(&$userAccessManager, 'remove_postdata'));
        add_action('edit_user_profile', array(&$userAccessManager, 'show_user_profile'));
        add_action('profile_update', array(&$userAccessManager, 'save_userdata'));
        add_action('delete_user', array(&$userAccessManager, 'remove_userdata'));
        add_action('edit_category_form', array(&$userAccessManager, 'show_cat_edit_form'));
        add_action('edit_category', array(&$userAccessManager, 'save_categorydata'));
        add_action('delete_category', array(&$userAccessManager, 'remove_categorydata'));
        add_action('wp_print_scripts', array(&$userAccessManager, 'add_scripts'));
        add_action('wp_print_styles', array(&$userAccessManager, 'add_styles'));

        //Admin filters
        add_filter('manage_posts_columns', array(&$userAccessManager, 'add_post_columns_header'));
        add_filter('manage_pages_columns', array(&$userAccessManager, 'add_post_columns_header'));
        $uamOptions = $userAccessManager->getAdminOptions();
        
        if ($uamOptions['lock_file'] == 'true') {
            add_action('media_meta', array(&$userAccessManager, 'show_media_file'), 10, 2);
            add_filter('manage_media_columns', array(&$userAccessManager, 'add_post_columns_header'));
        }
        
        if ($wp_version >= 2.8 || $uamOptions['core_mod'] == 'true') {
            add_filter('manage_users_columns', array(&$userAccessManager, 'add_user_columns_header'), 10);
            add_filter('manage_users_custom_column', array(&$userAccessManager, 'add_user_column'), 10, 2);
            add_filter('manage_categories_columns', array(&$userAccessManager, 'add_category_columns_header'));
            add_filter('manage_categories_custom_column', array(&$userAccessManager, 'add_category_column'), 10, 2);
        }
    }
}

//Actions and Filters
if (isset($userAccessManager)) {
    add_action('init', array(&$userAccessManager, 'init'));
    $uamOptions = $userAccessManager->getAdminOptions();

    //install
    if (function_exists('register_activation_hook')) {
        register_activation_hook(__FILE__, array(&$userAccessManager, 'install'));
    }
    
    if (function_exists('register_uninstall_hook')) {
        register_uninstall_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
    } elseif (function_exists('register_deactivation_hook')) {
        register_deactivation_hook(__FILE__, array(&$userAccessManager, 'uninstall'));
    }
    
    if (function_exists('register_deactivation_hook')) {
        register_deactivation_hook(__FILE__, array(&$userAccessManager, 'deactivate'));
    }

    //Actions
    add_action('admin_menu', 'userAccessManagerAP');
    if ($uamOptions['redirect'] != 'false' || isset($_GET['getfile'])) {
        add_action('template_redirect', array(&$userAccessManager, 'redirect_user'));
    }

    //Filters
    add_filter('wp_get_attachment_thumb_url', array(&$userAccessManager, 'get_file'), 10, 2);
    add_filter('wp_get_attachment_url', array(&$userAccessManager, 'get_file'), 10, 2);
    add_filter('the_posts', array(&$userAccessManager, 'show_post'));
    add_filter('comments_array', array(&$userAccessManager, 'show_comment'));
    add_filter('get_pages', array(&$userAccessManager, 'show_page'));
    add_filter('get_terms', array(&$userAccessManager, 'show_category'));
    add_filter('get_next_post_where', array(&$userAccessManager, 'show_next_previous_post'));
    add_filter('get_previous_post_where', array(&$userAccessManager, 'show_next_previous_post'));
    add_filter('get_previous_post_where', array(&$userAccessManager, 'show_next_previous_post'));
    add_filter('the_title', array(&$userAccessManager, 'show_title'), 10, 2);
    add_filter('posts_where', array(&$userAccessManager, 'show_post_sql'));
}
?>
