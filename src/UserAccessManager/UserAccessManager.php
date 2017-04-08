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
use UserAccessManager\Wrapper\Php;
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
     * @var Php
     */
    protected $oPhp;

    /**
     * @var Wordpress
     */
    protected $oWordpress;

    /**
     * @var Config
     */
    protected $oConfig;

    /**
     * @var ObjectHandler
     */
    protected $oObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $oAccessHandler;

    /**
     * @var SetupHandler
     */
    protected $oSetupHandler;

    /**
     * @var FileHandler
     */
    protected $oFileHandler;

    /**
     * @var ControllerFactory
     */
    protected $oControllerFactory;

    /**
     * UserAccessManager constructor.
     *
     * @param Php               $oPhp
     * @param Wordpress         $oWordpress
     * @param Config            $oConfig
     * @param ObjectHandler     $oObjectHandler
     * @param AccessHandler     $oAccessHandler
     * @param SetupHandler      $oSetupHandler
     * @param ControllerFactory $oControllerFactory
     */
    public function __construct(
        Php $oPhp,
        Wordpress $oWordpress,
        Config $oConfig,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        SetupHandler $oSetupHandler,
        ControllerFactory $oControllerFactory
    ) {
        $this->oPhp = $oPhp;
        $this->oWordpress = $oWordpress;
        $this->oConfig = $oConfig;
        $this->oObjectHandler = $oObjectHandler;
        $this->oAccessHandler = $oAccessHandler;
        $this->oSetupHandler = $oSetupHandler;
        $this->oControllerFactory = $oControllerFactory;
    }

    /**
     * Resister the administration menu.
     */
    public function registerAdminMenu()
    {
        if ($this->oAccessHandler->checkUserAccess() === true) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access. Capabilities seems
             * to be the right way, but it is way difficult.
             */
            //Admin main menu
            $this->oWordpress->addMenuPage(
                'User Access Manager',
                'UAM',
                'manage_options',
                'uam_user_group',
                null,
                'div'
            );

            //Admin sub menus
            $oAdminUserGroupController = $this->oControllerFactory->createAdminUserGroupController();
            $this->oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_MANAGE_GROUP,
                TXT_UAM_MANAGE_GROUP,
                'read',
                'uam_user_group',
                [$oAdminUserGroupController, 'render']
            );

            $oAdminSetupController = $this->oControllerFactory->createAdminSettingsController();
            $this->oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETTINGS,
                TXT_UAM_SETTINGS,
                'read',
                'uam_settings',
                [$oAdminSetupController, 'render']
            );

            $oAdminSetupController = $this->oControllerFactory->createAdminSetupController();
            $this->oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETUP,
                TXT_UAM_SETUP,
                'read',
                'uam_setup',
                [$oAdminSetupController, 'render']
            );

            $oAdminAboutController = $this->oControllerFactory->createAdminAboutController();
            $this->oWordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_ABOUT,
                TXT_UAM_ABOUT,
                'read',
                'uam_about',
                [$oAdminAboutController, 'render']
            );

            $this->oWordpress->doAction('uam_add_sub_menu');

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
        $oAdminController = $this->oControllerFactory->createAdminController();
        $this->oWordpress->addAction('admin_enqueue_scripts', [$oAdminController, 'enqueueStylesAndScripts']);
        $this->oWordpress->addAction('wp_dashboard_setup', [$oAdminController, 'setupAdminDashboard']);

        if ($this->oPhp->iniGet('safe_mode') !== '' && $this->oConfig->getDownloadType() === 'fopen') {
            $this->oWordpress->addAction('admin_notices', [$oAdminController, 'showFOpenNotice']);
        }

        if ($this->oSetupHandler->isDatabaseUpdateNecessary() === true) {
            $this->oWordpress->addAction('admin_notices', [$oAdminController, 'showDatabaseNotice']);
        }

        $aTaxonomies = $this->oObjectHandler->getTaxonomies();
        $sTaxonomy = $oAdminController->getRequestParameter('taxonomy');

        if ($sTaxonomy !== null) {
            $aTaxonomies[$sTaxonomy] = $sTaxonomy;
        }

        $oAdminObjectController = $this->oControllerFactory->createAdminObjectController();

        if ($this->oAccessHandler->checkUserAccess() === true
            || $this->oConfig->authorsCanAddPostsToGroups() === true
        ) {
            //Admin actions
            $this->oWordpress->addAction(
                'manage_posts_custom_column',
                [$oAdminObjectController, 'addPostColumn'],
                10,
                2
            );
            $this->oWordpress->addAction(
                'manage_pages_custom_column',
                [$oAdminObjectController, 'addPostColumn'],
                10,
                2
            );
            $this->oWordpress->addAction('save_post', [$oAdminObjectController, 'savePostData']);
            $this->oWordpress->addAction('edit_user_profile', [$oAdminObjectController, 'showUserProfile']);
            $this->oWordpress->addAction('profile_update', [$oAdminObjectController, 'saveUserData']);

            $this->oWordpress->addAction('bulk_edit_custom_box', [$oAdminObjectController, 'addBulkAction']);
            $this->oWordpress->addAction('create_term', [$oAdminObjectController, 'saveTermData']);
            $this->oWordpress->addAction('edit_term', [$oAdminObjectController, 'saveTermData']);

            //Taxonomies
            foreach ($aTaxonomies as $sTaxonomy) {
                $this->oWordpress->addAction(
                    'manage_'.$sTaxonomy.'_custom_column',
                    [$oAdminObjectController, 'addTermColumn'],
                    10,
                    3
                );
                $this->oWordpress->addAction(
                    $sTaxonomy.'_add_form_fields',
                    [$oAdminObjectController, 'showTermEditForm']
                );
                $this->oWordpress->addAction(
                    $sTaxonomy.'_edit_form_fields',
                    [$oAdminObjectController, 'showTermEditForm']
                );
            }

            if ($this->oConfig->lockFile() === true) {
                $this->oWordpress->addAction(
                    'manage_media_custom_column',
                    [$oAdminObjectController, 'addPostColumn'],
                    10,
                    2
                );
                $this->oWordpress->addAction('media_meta', [$oAdminObjectController, 'showMediaFile'], 10, 2);
            }

            //Admin filters
            //The filter we use instead of add|edit_attachment action, reason see top
            $this->oWordpress->addFilter('attachment_fields_to_save', [$oAdminObjectController, 'saveAttachmentData']);

            $this->oWordpress->addFilter('manage_posts_columns', [$oAdminObjectController, 'addPostColumnsHeader']);
            $this->oWordpress->addFilter('manage_pages_columns', [$oAdminObjectController, 'addPostColumnsHeader']);

            $this->oWordpress->addFilter('manage_users_columns', [$oAdminObjectController, 'addUserColumnsHeader'], 10);
            $this->oWordpress->addFilter(
                'manage_users_custom_column',
                [$oAdminObjectController, 'addUserColumn'],
                10,
                3
            );

            foreach ($aTaxonomies as $sTaxonomy) {
                $this->oWordpress->addFilter(
                    'manage_edit-'.$sTaxonomy.'_columns',
                    [$oAdminObjectController, 'addTermColumnsHeader']
                );
            }

            if ($this->oConfig->lockFile() === true) {
                $this->oWordpress->addFilter('manage_media_columns', [$oAdminObjectController, 'addPostColumnsHeader']);
            }

            //Admin meta boxes
            $aPostTypes = $this->oObjectHandler->getPostTypes();

            foreach ($aPostTypes as $sPostType) {
                // there is no need for a meta box for attachments if files are locked
                if ($sPostType === ObjectHandler::ATTACHMENT_OBJECT_TYPE && $this->oConfig->lockFile() !== true) {
                    continue;
                }

                $this->oWordpress->addMetaBox(
                    'uma_post_access',
                    TXT_UAM_COLUMN_ACCESS,
                    [$oAdminObjectController, 'editPostContent'],
                    $sPostType,
                    'side'
                );
            }
        }

        //Clean up at deleting should always be done.
        $this->oWordpress->addAction('update_option_permalink_structure', [$oAdminObjectController, 'updatePermalink']);
        $this->oWordpress->addAction('delete_post', [$oAdminObjectController, 'removePostData']);
        $this->oWordpress->addAction('delete_attachment', [$oAdminObjectController, 'removePostData']);
        $this->oWordpress->addAction('delete_user', [$oAdminObjectController, 'removeUserData']);
        $this->oWordpress->addAction('delete_term', [$oAdminObjectController, 'removeTermData']);

        $oAdminObjectController->checkRightsToEditContent();
    }

    /**
     * Adds the actions and filers.
     */
    public function addActionsAndFilters()
    {
        $oFrontendController = $this->oControllerFactory->createFrontendController();

        //Actions
        $this->oWordpress->addAction('admin_menu', [$this, 'registerAdminMenu']);
        $this->oWordpress->addAction('admin_init', [$this, 'registerAdminActionsAndFilters']);
        $this->oWordpress->addAction('registered_post_type', [$this->oObjectHandler, 'registeredPostType'], 10, 2);
        $this->oWordpress->addAction('registered_taxonomy', [$this->oObjectHandler, 'registeredTaxonomy'], 10, 3);
        $this->oWordpress->addAction('registered_post_type', [$this->oConfig, 'flushConfigParameters']);
        $this->oWordpress->addAction('registered_taxonomy', [$this->oConfig, 'flushConfigParameters']);
        $this->oWordpress->addAction('wp_enqueue_scripts', [$oFrontendController, 'enqueueStylesAndScripts']);

        //Filters
        $sGetFile = $oFrontendController->getRequestParameter('uamgetfile');

        if ($this->oConfig->getRedirect() !== false || $sGetFile !== null) {
            $this->oWordpress->addFilter('wp_headers', [$oFrontendController, 'redirect'], 10, 2);
        }

        $this->oWordpress->addFilter('wp_get_attachment_thumb_url', [$oFrontendController, 'getFileUrl'], 10, 2);
        $this->oWordpress->addFilter('wp_get_attachment_url', [$oFrontendController, 'getFileUrl'], 10, 2);
        $this->oWordpress->addFilter('the_posts', [$oFrontendController, 'showPosts']);
        $this->oWordpress->addFilter('posts_where_paged', [$oFrontendController, 'showPostSql']);
        $this->oWordpress->addFilter('get_terms_args', [$oFrontendController, 'getTermArguments']);
        $this->oWordpress->addFilter('wp_get_nav_menu_items', [$oFrontendController, 'showCustomMenu']);
        $this->oWordpress->addFilter('comments_array', [$oFrontendController, 'showComment']);
        $this->oWordpress->addFilter('the_comments', [$oFrontendController, 'showComment']);
        $this->oWordpress->addFilter('get_pages', [$oFrontendController, 'showPages'], 20);
        $this->oWordpress->addFilter('get_terms', [$oFrontendController, 'showTerms'], 20);
        $this->oWordpress->addFilter('get_term', [$oFrontendController, 'showTerm'], 20, 2);
        $this->oWordpress->addFilter('get_ancestors', [$oFrontendController, 'showAncestors'], 20, 4);
        $this->oWordpress->addFilter('get_next_post_where', [$oFrontendController, 'showNextPreviousPost']);
        $this->oWordpress->addFilter('get_previous_post_where', [$oFrontendController, 'showNextPreviousPost']);
        $this->oWordpress->addFilter('post_link', [$oFrontendController, 'cachePostLinks'], 10, 2);
        $this->oWordpress->addFilter('edit_post_link', [$oFrontendController, 'showGroupMembership'], 10, 2);
        $this->oWordpress->addFilter('parse_query', [$oFrontendController, 'parseQuery']);
        $this->oWordpress->addFilter('getarchives_where', [$oFrontendController, 'showPostSql']);
        $this->oWordpress->addFilter('wp_count_posts', [$oFrontendController, 'showPostCount'], 10, 3);
        $this->oWordpress->addFilter('wpseo_sitemap_entry', [$oFrontendController, 'getWpSeoUrl'], 1, 3);
    }
}
