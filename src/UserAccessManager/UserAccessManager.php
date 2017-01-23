<?php
/**
 * UserAccessManager.php
 *
 * The UserAccessManager class file.
 *
 * PHP versions 5
 *
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\Config;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\Wrapper\Wordpress;

/**
 * The user user access manager class.
 *
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */
class UserAccessManager
{
    const VERSION = '1.3.0';
    const DB_VERSION = '1.4';

    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * @var SetupHandler
     */
    protected $_oSetupHandler;

    /**
     * @var FileHandler
     */
    protected $_oFileHandler;

    /**
     * @var ControllerFactory
     */
    protected $_oControllerFactory;

    /**
     * UserAccessManager constructor.
     *
     * @param Wordpress             $oWrapper
     * @param Config                $oConfig
     * @param ObjectHandler         $oObjectHandler
     * @param AccessHandler         $oAccessHandler
     * @param SetupHandler          $oSetupHandler
     * @param ControllerFactory     $oControllerFactory
     */
    public function __construct(
        Wordpress $oWrapper,
        Config $oConfig,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        SetupHandler $oSetupHandler,
        ControllerFactory $oControllerFactory
    )
    {
        $this->_oWrapper = $oWrapper;
        $this->_oConfig = $oConfig;
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oSetupHandler = $oSetupHandler;
        $this->_oControllerFactory = $oControllerFactory;
    }

    /**
     * Resister the administration menu.
     */
    public function registerAdminMenu()
    {
        if ($this->_oAccessHandler->checkUserAccess()) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access. Capabilities seems
             * to be the right way, but it is way difficult.
             */
            //Admin main menu
            $this->_oWrapper->addMenuPage('User Access Manager', 'UAM', 'manage_options', 'uam_user_group', null, 'div');

            //Admin sub menus
            $oAdminUserGroupController = $this->_oControllerFactory->createAdminUserGroupController();
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_MANAGE_GROUP,
                TXT_UAM_MANAGE_GROUP,
                'read',
                'uam_user_group',
                array($oAdminUserGroupController, 'render')
            );

            $oAdminSetupController = $this->_oControllerFactory->createAdminSettingController();
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETTINGS,
                TXT_UAM_SETTINGS,
                'read',
                'uam_settings',
                array($oAdminSetupController, 'render')
            );

            $oAdminSetupController = $this->_oControllerFactory->createAdminSetupController();
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETUP,
                TXT_UAM_SETUP,
                'read',
                'uam_setup',
                array($oAdminSetupController, 'render')
            );

            $oAdminAboutController = $this->_oControllerFactory->createAdminAboutController();
            $this->_oWrapper->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_ABOUT,
                TXT_UAM_ABOUT,
                'read',
                'uam_about',
                array($oAdminAboutController, 'render')
            );

            $this->_oWrapper->doAction('uam_add_submenu');

            /**
             * --- EOF ---
             */
        }
    }

    /**
     * Register the admin actions and filters
     */
    public function registerAdminActionsAndFilters()
    {
        $oAdminController = $this->_oControllerFactory->createAdminController();
        $this->_oWrapper->addAction('admin_enqueue_scripts', array($oAdminController, 'enqueueStylesAndScripts'));
        $this->_oWrapper->addAction('wp_dashboard_setup', array($oAdminController, 'setupAdminDashboard'));

        if (ini_get('safe_mode') && $this->_oConfig->getDownloadType() === 'fopen') {
            $this->_oWrapper->addAction('admin_notices', array($oAdminController, 'showFOpenNotice'));
        }
        
        if ($this->_oSetupHandler->isDatabaseUpdateNecessary() === true) {
            $this->_oWrapper->addAction('admin_notices', array($oAdminController, 'showDatabaseNotice'));
        }

        $aTaxonomies = $this->_oObjectHandler->getPostTypes();

        if (isset($_POST['taxonomy'])) {
            $aTaxonomies[$_POST['taxonomy']] = $_POST['taxonomy'];
        } elseif (isset($_GET['taxonomy'])) {
            $aTaxonomies[$_GET['taxonomy']] = $_GET['taxonomy'];
        }

        $oAdminObjectController = $this->_oControllerFactory->createAdminObjectController();

        if ($this->_oAccessHandler->checkUserAccess() 
            || $this->_oConfig->authorsCanAddPostsToGroups() === true
        ) {
            //Admin actions
            $this->_oWrapper->addAction('manage_posts_custom_column', array($oAdminObjectController, 'addPostColumn'), 10, 2);
            $this->_oWrapper->addAction('manage_pages_custom_column', array($oAdminObjectController, 'addPostColumn'), 10, 2);
            $this->_oWrapper->addAction('save_post', array($oAdminObjectController, 'savePostData'));
            $this->_oWrapper->addAction('edit_user_profile', array($oAdminObjectController, 'showUserProfile'));
            $this->_oWrapper->addAction('profile_update', array($oAdminObjectController, 'saveUserData'));

            $this->_oWrapper->addAction('bulk_edit_custom_box', array($oAdminObjectController, 'addBulkAction'));
            $this->_oWrapper->addAction('create_term', array($oAdminObjectController, 'saveTermData'));
            $this->_oWrapper->addAction('edit_term', array($oAdminObjectController, 'saveTermData'));

            //Taxonomies
            foreach ($aTaxonomies as $sTaxonomy) {
                $this->_oWrapper->addAction('manage_'.$sTaxonomy.'_custom_column', array($oAdminObjectController, 'addTermColumn'), 10, 3);
                $this->_oWrapper->addAction($sTaxonomy.'_add_form_fields', array($oAdminObjectController, 'showTermEditForm'));
                $this->_oWrapper->addAction($sTaxonomy.'_edit_form_fields', array($oAdminObjectController, 'showTermEditForm'));
            }

            if ($this->_oConfig->lockFile() === true) {
                $this->_oWrapper->addAction('manage_media_custom_column', array($oAdminObjectController, 'addPostColumn'), 10, 2);
                $this->_oWrapper->addAction('media_meta', array($oAdminObjectController, 'showMediaFile'), 10, 2);
            }

            //Admin filters
            //The filter we use instead of add|edit_attachment action, reason see top
            $this->_oWrapper->addFilter('attachment_fields_to_save', array($oAdminObjectController, 'saveAttachmentData'));

            $this->_oWrapper->addFilter('manage_posts_columns', array($oAdminObjectController, 'addPostColumnsHeader'));
            $this->_oWrapper->addFilter('manage_pages_columns', array($oAdminObjectController, 'addPostColumnsHeader'));

            $this->_oWrapper->addFilter('manage_users_columns', array($oAdminObjectController, 'addUserColumnsHeader'), 10);
            $this->_oWrapper->addFilter('manage_users_custom_column', array($oAdminObjectController, 'addUserColumn'), 10, 3);

            foreach ($aTaxonomies as $sTaxonomy) {
                $this->_oWrapper->addFilter('manage_edit-'.$sTaxonomy.'_columns', array($oAdminObjectController, 'addTermColumnsHeader'));
            }

            if ($this->_oConfig->lockFile() === true) {
                $this->_oWrapper->addFilter('manage_media_columns', array($oAdminObjectController, 'addPostColumnsHeader'));
            }
        }

        //Clean up at deleting should always be done.
        $this->_oWrapper->addAction('update_option_permalink_structure', array($oAdminObjectController, 'updatePermalink'));
        $this->_oWrapper->addAction('delete_post', array($oAdminObjectController, 'removePostData'));
        $this->_oWrapper->addAction('delete_attachment', array($oAdminObjectController, 'removePostData'));
        $this->_oWrapper->addAction('delete_user', array($oAdminObjectController, 'removeUserData'));
        $this->_oWrapper->addAction('delete_term', array($oAdminObjectController, 'removeTermData'));

        if ($this->_oAccessHandler->checkUserAccess()
            || $this->_oConfig->authorsCanAddPostsToGroups() === true
        ) {
            //Admin meta boxes
            $aPostableTypes = $this->_oObjectHandler->getPostableTypes();

            foreach ($aPostableTypes as $sPostableType) {
                // there is no need for a metabox for attachments if files are locked
                if ($sPostableType === 'attachment' && $this->_oConfig->lockFile() !== true) {
                    continue;
                }

                $this->_oWrapper->addMetaBox(
                    'uma_post_access',
                    __('Access', 'user-access-manager'),
                    array($oAdminObjectController, 'editPostContent'),
                    $sPostableType,
                    'side'
                );
            }
        }

        $oAdminObjectController->noRightsToEditContent();
    }

    /**
     * Adds the actions and filers.
     */
    public function addActionsAndFilters()
    {
        $oFrontendController = $this->_oControllerFactory->createFrontendController();

        //Actions
        $this->_oWrapper->addAction('admin_menu', array($this, 'registerAdminMenu'));
        $this->_oWrapper->addAction('admin_init', array($this, 'registerAdminActionsAndFilters'));
        //$this->_oWrapper->addAction('registered_post_type', array(&$this, 'registeredPostType'), 10, 2); //TODO object handler
        $this->_oWrapper->addAction('wp_enqueue_scripts', array($oFrontendController, 'enqueueStylesAndScripts'));

        //Filters
        if ($this->_oConfig->getRedirect() !== false || isset($_GET['uamgetfile'])) {
            $this->_oWrapper->addFilter('wp_headers', array($oFrontendController, 'redirect'), 10, 2);
        }

        $this->_oWrapper->addFilter('wp_get_attachment_thumb_url', array($oFrontendController, 'getFileUrl'), 10, 2);
        $this->_oWrapper->addFilter('wp_get_attachment_url', array($oFrontendController, 'getFileUrl'), 10, 2);
        $this->_oWrapper->addFilter('the_posts', array($oFrontendController, 'showPosts'));
        $this->_oWrapper->addFilter('posts_where_paged', array($oFrontendController, 'showPostSql'));
        $this->_oWrapper->addFilter('get_terms_args', array($oFrontendController, 'getTermArguments'));
        $this->_oWrapper->addFilter('wp_get_nav_menu_items', array($oFrontendController, 'showCustomMenu'));
        $this->_oWrapper->addFilter('comments_array', array($oFrontendController, 'showComment'));
        $this->_oWrapper->addFilter('the_comments', array($oFrontendController, 'showComment'));
        $this->_oWrapper->addFilter('get_pages', array($oFrontendController, 'showPages'), 20);
        $this->_oWrapper->addFilter('get_terms', array($oFrontendController, 'showTerms'), 20, 2);
        $this->_oWrapper->addFilter('get_term', array($oFrontendController, 'showTerm'), 20, 2);
        $this->_oWrapper->addFilter('get_ancestors', array($oFrontendController, 'showAncestors'), 20, 4);
        $this->_oWrapper->addFilter('get_next_post_where', array($oFrontendController, 'showNextPreviousPost'));
        $this->_oWrapper->addFilter('get_previous_post_where', array($oFrontendController, 'showNextPreviousPost'));
        $this->_oWrapper->addFilter('post_link', array($oFrontendController, 'cachePostLinks'), 10, 2);
        $this->_oWrapper->addFilter('edit_post_link', array($oFrontendController, 'showGroupMembership'), 10, 2);
        $this->_oWrapper->addFilter('parse_query', array($oFrontendController, 'parseQuery'));
        $this->_oWrapper->addFilter('getarchives_where', array($oFrontendController, 'showPostSql'));
        $this->_oWrapper->addFilter('wp_count_posts', array($oFrontendController, 'showPostCount'), 10, 2);
        $this->_oWrapper->addFilter('wpseo_sitemap_entry', array($oFrontendController, 'wpSeoUrl'), 1, 3); // Yaost Sitemap Plugin
    }
}
