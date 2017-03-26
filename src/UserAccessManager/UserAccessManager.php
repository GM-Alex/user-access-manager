<?php
/**
 * UserAccessManager.php
 *
 * The UserAccessManager class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
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
 * Class UserAccessManager
 *
 * @package UserAccessManager
 */
class UserAccessManager
{
    const VERSION = '2.0.0';
    const DB_VERSION = '1.5';

    /**
     * @var Wordpress
     */
    protected $_oWordpress;

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
     * @param Wordpress         $oWordpress
     * @param Config            $oConfig
     * @param ObjectHandler     $oObjectHandler
     * @param AccessHandler     $oAccessHandler
     * @param SetupHandler      $oSetupHandler
     * @param ControllerFactory $oControllerFactory
     */
    public function __construct(
        Wordpress $oWordpress,
        Config $oConfig,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        SetupHandler $oSetupHandler,
        ControllerFactory $oControllerFactory
    )
    {
        $this->_oWordpress = $oWordpress;
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
        if ($this->_oAccessHandler->checkUserAccess() === true) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access. Capabilities seems
             * to be the right way, but it is way difficult.
             */
            //Admin main menu
            $this->_oWordpress->addMenuPage('User Access Manager', 'UAM', 'manage_options', 'uam_user_group', null, 'div');

            //Admin sub menus
            $oAdminUserGroupController = $this->_oControllerFactory->createAdminUserGroupController();
            $this->_oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_MANAGE_GROUP,
                TXT_UAM_MANAGE_GROUP,
                'read',
                'uam_user_group',
                [$oAdminUserGroupController, 'render']
            );

            $oAdminSetupController = $this->_oControllerFactory->createAdminSettingsController();
            $this->_oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETTINGS,
                TXT_UAM_SETTINGS,
                'read',
                'uam_settings',
                [$oAdminSetupController, 'render']
            );

            $oAdminSetupController = $this->_oControllerFactory->createAdminSetupController();
            $this->_oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETUP,
                TXT_UAM_SETUP,
                'read',
                'uam_setup',
                [$oAdminSetupController, 'render']
            );

            $oAdminAboutController = $this->_oControllerFactory->createAdminAboutController();
            $this->_oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_ABOUT,
                TXT_UAM_ABOUT,
                'read',
                'uam_about',
                [$oAdminAboutController, 'render']
            );

            $this->_oWordpress->doAction('uam_add_sub_menu');

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
        $this->_oWordpress->addAction('admin_enqueue_scripts', [$oAdminController, 'enqueueStylesAndScripts']);
        $this->_oWordpress->addAction('wp_dashboard_setup', [$oAdminController, 'setupAdminDashboard']);

        if (ini_get('safe_mode') === true && $this->_oConfig->getDownloadType() === 'fopen') {
            $this->_oWordpress->addAction('admin_notices', [$oAdminController, 'showFOpenNotice']);
        }

        if ($this->_oSetupHandler->isDatabaseUpdateNecessary() === true) {
            $this->_oWordpress->addAction('admin_notices', [$oAdminController, 'showDatabaseNotice']);
        }

        $aTaxonomies = $this->_oObjectHandler->getTaxonomies();
        $sTaxonomy = $oAdminController->getRequestParameter('taxonomy');

        if ($sTaxonomy !== null) {
            $aTaxonomies[$sTaxonomy] = $sTaxonomy;
        }

        $oAdminObjectController = $this->_oControllerFactory->createAdminObjectController();

        if ($this->_oAccessHandler->checkUserAccess() === true
            || $this->_oConfig->authorsCanAddPostsToGroups() === true
        ) {
            //Admin actions
            $this->_oWordpress->addAction('manage_posts_custom_column', [$oAdminObjectController, 'addPostColumn'], 10, 2);
            $this->_oWordpress->addAction('manage_pages_custom_column', [$oAdminObjectController, 'addPostColumn'], 10, 2);
            $this->_oWordpress->addAction('save_post', [$oAdminObjectController, 'savePostData']);
            $this->_oWordpress->addAction('edit_user_profile', [$oAdminObjectController, 'showUserProfile']);
            $this->_oWordpress->addAction('profile_update', [$oAdminObjectController, 'saveUserData']);

            $this->_oWordpress->addAction('bulk_edit_custom_box', [$oAdminObjectController, 'addBulkAction']);
            $this->_oWordpress->addAction('create_term', [$oAdminObjectController, 'saveTermData']);
            $this->_oWordpress->addAction('edit_term', [$oAdminObjectController, 'saveTermData']);

            //Taxonomies
            foreach ($aTaxonomies as $sTaxonomy) {
                $this->_oWordpress->addAction('manage_'.$sTaxonomy.'_custom_column', [$oAdminObjectController, 'addTermColumn'], 10, 3);
                $this->_oWordpress->addAction($sTaxonomy.'_add_form_fields', [$oAdminObjectController, 'showTermEditForm']);
                $this->_oWordpress->addAction($sTaxonomy.'_edit_form_fields', [$oAdminObjectController, 'showTermEditForm']);
            }

            if ($this->_oConfig->lockFile() === true) {
                $this->_oWordpress->addAction('manage_media_custom_column', [$oAdminObjectController, 'addPostColumn'], 10, 2);
                $this->_oWordpress->addAction('media_meta', [$oAdminObjectController, 'showMediaFile'], 10, 2);
            }

            //Admin filters
            //The filter we use instead of add|edit_attachment action, reason see top
            $this->_oWordpress->addFilter('attachment_fields_to_save', [$oAdminObjectController, 'saveAttachmentData']);

            $this->_oWordpress->addFilter('manage_posts_columns', [$oAdminObjectController, 'addPostColumnsHeader']);
            $this->_oWordpress->addFilter('manage_pages_columns', [$oAdminObjectController, 'addPostColumnsHeader']);

            $this->_oWordpress->addFilter('manage_users_columns', [$oAdminObjectController, 'addUserColumnsHeader'], 10);
            $this->_oWordpress->addFilter('manage_users_custom_column', [$oAdminObjectController, 'addUserColumn'], 10, 3);

            foreach ($aTaxonomies as $sTaxonomy) {
                $this->_oWordpress->addFilter('manage_edit-'.$sTaxonomy.'_columns', [$oAdminObjectController, 'addTermColumnsHeader']);
            }

            if ($this->_oConfig->lockFile() === true) {
                $this->_oWordpress->addFilter('manage_media_columns', [$oAdminObjectController, 'addPostColumnsHeader']);
            }

            //Admin meta boxes
            $aPostTypes = $this->_oObjectHandler->getPostTypes();

            foreach ($aPostTypes as $sPostType) {
                // there is no need for a meta box for attachments if files are locked
                if ($sPostType === ObjectHandler::ATTACHMENT_OBJECT_TYPE && $this->_oConfig->lockFile() !== true) {
                    continue;
                }

                $this->_oWordpress->addMetaBox(
                    'uma_post_access',
                    TXT_UAM_COLUMN_ACCESS,
                    [$oAdminObjectController, 'editPostContent'],
                    $sPostType,
                    'side'
                );
            }
        }

        //Clean up at deleting should always be done.
        $this->_oWordpress->addAction('update_option_permalink_structure', [$oAdminObjectController, 'updatePermalink']);
        $this->_oWordpress->addAction('delete_post', [$oAdminObjectController, 'removePostData']);
        $this->_oWordpress->addAction('delete_attachment', [$oAdminObjectController, 'removePostData']);
        $this->_oWordpress->addAction('delete_user', [$oAdminObjectController, 'removeUserData']);
        $this->_oWordpress->addAction('delete_term', [$oAdminObjectController, 'removeTermData']);

        $oAdminObjectController->checkRightsToEditContent();
    }

    /**
     * Adds the actions and filers.
     */
    public function addActionsAndFilters()
    {
        $oFrontendController = $this->_oControllerFactory->createFrontendController();

        //Actions
        $this->_oWordpress->addAction('admin_menu', [$this, 'registerAdminMenu']);
        $this->_oWordpress->addAction('admin_init', [$this, 'registerAdminActionsAndFilters']);
        $this->_oWordpress->addAction('registered_post_type', [$this->_oObjectHandler, 'registeredPostType'], 10, 2);
        $this->_oWordpress->addAction('registered_taxonomy', [$this->_oObjectHandler, 'registeredTaxonomy'], 10, 3);
        $this->_oWordpress->addAction('registered_post_type', [$this->_oConfig, 'flushConfigParameters']);
        $this->_oWordpress->addAction('registered_taxonomy', [$this->_oConfig, 'flushConfigParameters']);
        $this->_oWordpress->addAction('wp_enqueue_scripts', [$oFrontendController, 'enqueueStylesAndScripts']);

        //Filters
        $sGetFile = $oFrontendController->getRequestParameter('uamgetfile');

        if ($this->_oConfig->getRedirect() !== false || $sGetFile !== null) {
            $this->_oWordpress->addFilter('wp_headers', [$oFrontendController, 'redirect'], 10, 2);
        }

        $this->_oWordpress->addFilter('wp_get_attachment_thumb_url', [$oFrontendController, 'getFileUrl'], 10, 2);
        $this->_oWordpress->addFilter('wp_get_attachment_url', [$oFrontendController, 'getFileUrl'], 10, 2);
        $this->_oWordpress->addFilter('the_posts', [$oFrontendController, 'showPosts']);
        $this->_oWordpress->addFilter('posts_where_paged', [$oFrontendController, 'showPostSql']);
        $this->_oWordpress->addFilter('get_terms_args', [$oFrontendController, 'getTermArguments']);
        $this->_oWordpress->addFilter('wp_get_nav_menu_items', [$oFrontendController, 'showCustomMenu']);
        $this->_oWordpress->addFilter('comments_array', [$oFrontendController, 'showComment']);
        $this->_oWordpress->addFilter('the_comments', [$oFrontendController, 'showComment']);
        $this->_oWordpress->addFilter('get_pages', [$oFrontendController, 'showPages'], 20);
        $this->_oWordpress->addFilter('get_terms', [$oFrontendController, 'showTerms'], 20);
        $this->_oWordpress->addFilter('get_term', [$oFrontendController, 'showTerm'], 20, 2);
        $this->_oWordpress->addFilter('get_ancestors', [$oFrontendController, 'showAncestors'], 20, 4);
        $this->_oWordpress->addFilter('get_next_post_where', [$oFrontendController, 'showNextPreviousPost']);
        $this->_oWordpress->addFilter('get_previous_post_where', [$oFrontendController, 'showNextPreviousPost']);
        $this->_oWordpress->addFilter('post_link', [$oFrontendController, 'cachePostLinks'], 10, 2);
        $this->_oWordpress->addFilter('edit_post_link', [$oFrontendController, 'showGroupMembership'], 10, 2);
        $this->_oWordpress->addFilter('parse_query', [$oFrontendController, 'parseQuery']);
        $this->_oWordpress->addFilter('getarchives_where', [$oFrontendController, 'showPostSql']);
        $this->_oWordpress->addFilter('wp_count_posts', [$oFrontendController, 'showPostCount'], 10, 3);
        $this->_oWordpress->addFilter('wpseo_sitemap_entry', [$oFrontendController, 'wpSeoUrl'], 1, 3); // Yaost Sitemap Plugin
    }
}
