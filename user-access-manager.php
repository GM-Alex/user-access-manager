<?php
/**
 * Plugin Name: User Access Manager
 * Plugin URI: https://wordpress.org/plugins/user-access-manager/
 * Author URI: https://twitter.com/GM_Alex
 * Version: 1.2.12
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
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
*/

//Paths
load_plugin_textdomain('user-access-manager', false, 'user-access-manager/lang');
define('UAM_URLPATH', plugins_url('', __FILE__).'/');
define('UAM_REALPATH', WP_PLUGIN_DIR.'/'.plugin_basename(dirname(__FILE__)).'/');


//Defines
require_once 'includes/database.define.php';
require_once 'includes/language.define.php';


//Check requirements
$blStop = false;

//Check php version
$sPhpVersion = phpversion();

if (version_compare($sPhpVersion, '5.3') === -1) {
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

if (version_compare($wp_version, '3.4') === -1) {
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
require_once 'class/UamConfig.php';
require_once 'class/UserAccessManager.php';
require_once 'class/UamUserGroup.php';
require_once 'class/UamAccessHandler.php';

if (class_exists("UserAccessManager")) {
    $oUserAccessManager = new UserAccessManager();
}

//Initialize the admin panel
if (!function_exists("userAccessManagerAP")) {
    /**
     * Creates the filters and actions for the admin panel
     */
    function userAccessManagerAP()
    {
        /**
         * @var UserAccessManager $oUserAccessManager
         */
        global $oUserAccessManager;
        $oCurrentUser = $oUserAccessManager->getCurrentUser();

        if (!isset($oUserAccessManager)) {
            return;
        }

        $oUserAccessManager->setAtAdminPanel();
        $oConfig = $oUserAccessManager->getConfig();

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

        $oUamAccessHandler = $oUserAccessManager->getAccessHandler();
        $aTaxonomies = $oUserAccessManager->getPostTypes();

        if (isset($_POST['taxonomy'])) {
            $aTaxonomies[$_POST['taxonomy']] = $_POST['taxonomy'];
        } elseif (isset($_GET['taxonomy'])) {
            $aTaxonomies[$_GET['taxonomy']] = $_GET['taxonomy'];
        }

        if ($oUamAccessHandler->checkUserAccess()
            || $oConfig->authorsCanAddPostsToGroups() === true
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

                add_action('bulk_edit_custom_box', array($oUserAccessManager, 'addBulkAction'));
                add_action('create_term', array($oUserAccessManager, 'saveTermData'));
                add_action('edit_term', array($oUserAccessManager, 'saveTermData'));

                //Taxonomies
                foreach ($aTaxonomies as $sTaxonomy) {
                    add_action('manage_'.$sTaxonomy.'_custom_column', array($oUserAccessManager, 'addTermColumn'), 10, 3);
                    add_action($sTaxonomy.'_add_form_fields', array($oUserAccessManager, 'showTermEditForm'));
                    add_action($sTaxonomy.'_edit_form_fields', array($oUserAccessManager, 'showTermEditForm'));
                }
            }

            //Admin filters
            if (function_exists('add_filter')) {
                //The filter we use instead of add|edit_attachment action, reason see top
                add_filter('attachment_fields_to_save', array($oUserAccessManager, 'saveAttachmentData'));

                add_filter('manage_posts_columns', array($oUserAccessManager, 'addPostColumnsHeader'));
                add_filter('manage_pages_columns', array($oUserAccessManager, 'addPostColumnsHeader'));

                add_filter('manage_users_columns', array($oUserAccessManager, 'addUserColumnsHeader'), 10);
                add_filter('manage_users_custom_column', array($oUserAccessManager, 'addUserColumn'), 10, 3);

                foreach ($aTaxonomies as $sTaxonomy) {
                    add_filter('manage_edit-'.$sTaxonomy.'_columns', array($oUserAccessManager, 'addTermColumnsHeader'));
                }
            }

            if ($oConfig->lockFile() === true) {
                add_action('media_meta', array($oUserAccessManager, 'showMediaFile'), 10, 2);
                add_filter('manage_media_columns', array($oUserAccessManager, 'addPostColumnsHeader'));
            }
        }

        //Clean up at deleting should always be done.
        if (function_exists('add_action')) {
            add_action('update_option_permalink_structure', array($oUserAccessManager, 'updatePermalink'));
            add_action('wp_dashboard_setup', array($oUserAccessManager, 'setupAdminDashboard'));
            add_action('delete_post', array($oUserAccessManager, 'removePostData'));
            add_action('delete_attachment', array($oUserAccessManager, 'removePostData'));
            add_action('delete_user', array($oUserAccessManager, 'removeUserData'));
            add_action('delete_term', array($oUserAccessManager, 'removeTermData'));
        }
        
        $oUserAccessManager->noRightsToEditContent();
    }
}

if (!function_exists("userAccessManagerAPMenu")) {
    /**
     * Creates the menu at the admin panel
     */
    function userAccessManagerAPMenu()
    {
        /**
         * @var UserAccessManager $oUserAccessManager
         */
        global $oUserAccessManager;
        $oCurrentUser = $oUserAccessManager->getCurrentUser();
        
        if (!isset($oUserAccessManager)) {
            return;
        }
        
        $oConfig = $oUserAccessManager->getConfig();
        
        if (ini_get('safe_mode')
            && $oConfig->getDownloadType() === 'fopen'
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
        
        get_userdata($oCurrentUser->ID);
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
                add_menu_page('User Access Manager', 'UAM', 'manage_options', 'uam_usergroup', array($oUserAccessManager, 'printAdminPage'), 'div');
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
            || $oConfig->authorsCanAddPostsToGroups() === true
        ) {
            //Admin meta boxes
            if (function_exists('add_meta_box')) {
                $aPostableTypes = $oUamAccessHandler->getPostableTypes();
                
                foreach ($aPostableTypes as $sPostableType) {
                    add_meta_box('uma_post_access', 'Access', array($oUserAccessManager, 'editPostContent'), $sPostableType, 'side');
                }
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
    $oConfig = $oUserAccessManager->getConfig();
    
    if ($oConfig->getRedirect() !== 'false' || isset($_GET['uamgetfile'])) {
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
        add_filter('the_posts', array($oUserAccessManager, 'showPosts'));
        add_filter('posts_where_paged', array($oUserAccessManager, 'showPostSql'));
        add_filter('get_terms_args', array($oUserAccessManager, 'getTermArguments'));
        add_filter('wp_get_nav_menu_items', array($oUserAccessManager, 'showCustomMenu'));
        add_filter('comments_array', array($oUserAccessManager, 'showComment'));
        add_filter('the_comments', array($oUserAccessManager, 'showComment'));
        add_filter('get_pages', array($oUserAccessManager, 'showPages'), 20);
        add_filter('get_terms', array($oUserAccessManager, 'showTerms'), 20, 2);
        add_filter('get_term', array($oUserAccessManager, 'showTerm'), 20, 2);
        add_filter('get_ancestors', array($oUserAccessManager, 'showAncestors'), 20, 4);
        add_filter('get_next_post_where', array($oUserAccessManager, 'showNextPreviousPost'));
        add_filter('get_previous_post_where', array($oUserAccessManager, 'showNextPreviousPost'));
        add_filter('post_link', array($oUserAccessManager, 'cachePostLinks'), 10, 2);
        add_filter('edit_post_link', array($oUserAccessManager, 'showGroupMembership'), 10, 2);
        add_filter('parse_query', array($oUserAccessManager, 'parseQuery'));
        add_filter('getarchives_where', array($oUserAccessManager, 'showPostSql'));
        add_filter('wp_count_posts', array($oUserAccessManager, 'showPostCount'), 10, 2);
        add_filter('wpseo_sitemap_entry', array($oUserAccessManager, 'wpSeoUrl'), 1, 3); // Yaost Sitemap Plugin
    }
}

//Add the cli interface to the known commands
if (defined('WP_CLI') && WP_CLI) {
    include __DIR__.'/includes/wp-cli.php';
}