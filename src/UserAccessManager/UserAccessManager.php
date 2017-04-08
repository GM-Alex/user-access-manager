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
    protected $Php;

    /**
     * @var Wordpress
     */
    protected $Wordpress;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var ObjectHandler
     */
    protected $ObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $AccessHandler;

    /**
     * @var SetupHandler
     */
    protected $SetupHandler;

    /**
     * @var FileHandler
     */
    protected $FileHandler;

    /**
     * @var ControllerFactory
     */
    protected $ControllerFactory;

    /**
     * UserAccessManager constructor.
     *
     * @param Php               $Php
     * @param Wordpress         $Wordpress
     * @param Config            $Config
     * @param ObjectHandler     $ObjectHandler
     * @param AccessHandler     $AccessHandler
     * @param SetupHandler      $SetupHandler
     * @param ControllerFactory $ControllerFactory
     */
    public function __construct(
        Php $Php,
        Wordpress $Wordpress,
        Config $Config,
        ObjectHandler $ObjectHandler,
        AccessHandler $AccessHandler,
        SetupHandler $SetupHandler,
        ControllerFactory $ControllerFactory
    ) {
        $this->Php = $Php;
        $this->Wordpress = $Wordpress;
        $this->Config = $Config;
        $this->ObjectHandler = $ObjectHandler;
        $this->AccessHandler = $AccessHandler;
        $this->SetupHandler = $SetupHandler;
        $this->ControllerFactory = $ControllerFactory;
    }

    /**
     * Resister the administration menu.
     */
    public function registerAdminMenu()
    {
        if ($this->AccessHandler->checkUserAccess() === true) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access. Capabilities seems
             * to be the right way, but it is way difficult.
             */
            //Admin main menu
            $this->Wordpress->addMenuPage(
                'User Access Manager',
                'UAM',
                'manage_options',
                'uam_user_group',
                null,
                'div'
            );

            //Admin sub menus
            $AdminUserGroupController = $this->ControllerFactory->createAdminUserGroupController();
            $this->Wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_MANAGE_GROUP,
                TXT_UAM_MANAGE_GROUP,
                'read',
                'uam_user_group',
                [$AdminUserGroupController, 'render']
            );

            $AdminSetupController = $this->ControllerFactory->createAdminSettingsController();
            $this->Wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETTINGS,
                TXT_UAM_SETTINGS,
                'read',
                'uam_settings',
                [$AdminSetupController, 'render']
            );

            $AdminSetupController = $this->ControllerFactory->createAdminSetupController();
            $this->Wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETUP,
                TXT_UAM_SETUP,
                'read',
                'uam_setup',
                [$AdminSetupController, 'render']
            );

            $AdminAboutController = $this->ControllerFactory->createAdminAboutController();
            $this->Wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_ABOUT,
                TXT_UAM_ABOUT,
                'read',
                'uam_about',
                [$AdminAboutController, 'render']
            );

            $this->Wordpress->doAction('uam_add_sub_menu');

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
        $AdminController = $this->ControllerFactory->createAdminController();
        $this->Wordpress->addAction('admin_enqueue_scripts', [$AdminController, 'enqueueStylesAndScripts']);
        $this->Wordpress->addAction('wp_dashboard_setup', [$AdminController, 'setupAdminDashboard']);

        if ($this->Php->iniGet('safe_mode') !== '' && $this->Config->getDownloadType() === 'fopen') {
            $this->Wordpress->addAction('admin_notices', [$AdminController, 'showFOpenNotice']);
        }

        if ($this->SetupHandler->isDatabaseUpdateNecessary() === true) {
            $this->Wordpress->addAction('admin_notices', [$AdminController, 'showDatabaseNotice']);
        }

        $aTaxonomies = $this->ObjectHandler->getTaxonomies();
        $sTaxonomy = $AdminController->getRequestParameter('taxonomy');

        if ($sTaxonomy !== null) {
            $aTaxonomies[$sTaxonomy] = $sTaxonomy;
        }

        $AdminObjectController = $this->ControllerFactory->createAdminObjectController();

        if ($this->AccessHandler->checkUserAccess() === true
            || $this->Config->authorsCanAddPostsToGroups() === true
        ) {
            //Admin actions
            $this->Wordpress->addAction(
                'manage_posts_custom_column',
                [$AdminObjectController, 'addPostColumn'],
                10,
                2
            );
            $this->Wordpress->addAction(
                'manage_pages_custom_column',
                [$AdminObjectController, 'addPostColumn'],
                10,
                2
            );
            $this->Wordpress->addAction('save_post', [$AdminObjectController, 'savePostData']);
            $this->Wordpress->addAction('edit_user_profile', [$AdminObjectController, 'showUserProfile']);
            $this->Wordpress->addAction('profile_update', [$AdminObjectController, 'saveUserData']);

            $this->Wordpress->addAction('bulk_edit_custom_box', [$AdminObjectController, 'addBulkAction']);
            $this->Wordpress->addAction('create_term', [$AdminObjectController, 'saveTermData']);
            $this->Wordpress->addAction('edit_term', [$AdminObjectController, 'saveTermData']);

            //Taxonomies
            foreach ($aTaxonomies as $sTaxonomy) {
                $this->Wordpress->addAction(
                    'manage_'.$sTaxonomy.'_custom_column',
                    [$AdminObjectController, 'addTermColumn'],
                    10,
                    3
                );
                $this->Wordpress->addAction(
                    $sTaxonomy.'_add_form_fields',
                    [$AdminObjectController, 'showTermEditForm']
                );
                $this->Wordpress->addAction(
                    $sTaxonomy.'_edit_form_fields',
                    [$AdminObjectController, 'showTermEditForm']
                );
            }

            if ($this->Config->lockFile() === true) {
                $this->Wordpress->addAction(
                    'manage_media_custom_column',
                    [$AdminObjectController, 'addPostColumn'],
                    10,
                    2
                );
                $this->Wordpress->addAction('media_meta', [$AdminObjectController, 'showMediaFile'], 10, 2);
            }

            //Admin filters
            //The filter we use instead of add|edit_attachment action, reason see top
            $this->Wordpress->addFilter('attachment_fields_to_save', [$AdminObjectController, 'saveAttachmentData']);

            $this->Wordpress->addFilter('manage_posts_columns', [$AdminObjectController, 'addPostColumnsHeader']);
            $this->Wordpress->addFilter('manage_pages_columns', [$AdminObjectController, 'addPostColumnsHeader']);

            $this->Wordpress->addFilter('manage_users_columns', [$AdminObjectController, 'addUserColumnsHeader'], 10);
            $this->Wordpress->addFilter(
                'manage_users_custom_column',
                [$AdminObjectController, 'addUserColumn'],
                10,
                3
            );

            foreach ($aTaxonomies as $sTaxonomy) {
                $this->Wordpress->addFilter(
                    'manage_edit-'.$sTaxonomy.'_columns',
                    [$AdminObjectController, 'addTermColumnsHeader']
                );
            }

            if ($this->Config->lockFile() === true) {
                $this->Wordpress->addFilter('manage_media_columns', [$AdminObjectController, 'addPostColumnsHeader']);
            }

            //Admin meta boxes
            $aPostTypes = $this->ObjectHandler->getPostTypes();

            foreach ($aPostTypes as $sPostType) {
                // there is no need for a meta box for attachments if files are locked
                if ($sPostType === ObjectHandler::ATTACHMENT_OBJECT_TYPE && $this->Config->lockFile() !== true) {
                    continue;
                }

                $this->Wordpress->addMetaBox(
                    'uma_post_access',
                    TXT_UAM_COLUMN_ACCESS,
                    [$AdminObjectController, 'editPostContent'],
                    $sPostType,
                    'side'
                );
            }
        }

        //Clean up at deleting should always be done.
        $this->Wordpress->addAction('update_option_permalink_structure', [$AdminObjectController, 'updatePermalink']);
        $this->Wordpress->addAction('delete_post', [$AdminObjectController, 'removePostData']);
        $this->Wordpress->addAction('delete_attachment', [$AdminObjectController, 'removePostData']);
        $this->Wordpress->addAction('delete_user', [$AdminObjectController, 'removeUserData']);
        $this->Wordpress->addAction('delete_term', [$AdminObjectController, 'removeTermData']);

        $AdminObjectController->checkRightsToEditContent();
    }

    /**
     * Adds the actions and filers.
     */
    public function addActionsAndFilters()
    {
        $FrontendController = $this->ControllerFactory->createFrontendController();

        //Actions
        $this->Wordpress->addAction('admin_menu', [$this, 'registerAdminMenu']);
        $this->Wordpress->addAction('admin_init', [$this, 'registerAdminActionsAndFilters']);
        $this->Wordpress->addAction('registered_post_type', [$this->ObjectHandler, 'registeredPostType'], 10, 2);
        $this->Wordpress->addAction('registered_taxonomy', [$this->ObjectHandler, 'registeredTaxonomy'], 10, 3);
        $this->Wordpress->addAction('registered_post_type', [$this->Config, 'flushConfigParameters']);
        $this->Wordpress->addAction('registered_taxonomy', [$this->Config, 'flushConfigParameters']);
        $this->Wordpress->addAction('wp_enqueue_scripts', [$FrontendController, 'enqueueStylesAndScripts']);

        //Filters
        $sGetFile = $FrontendController->getRequestParameter('uamgetfile');

        if ($this->Config->getRedirect() !== false || $sGetFile !== null) {
            $this->Wordpress->addFilter('wp_headers', [$FrontendController, 'redirect'], 10, 2);
        }

        $this->Wordpress->addFilter('wp_get_attachment_thumb_url', [$FrontendController, 'getFileUrl'], 10, 2);
        $this->Wordpress->addFilter('wp_get_attachment_url', [$FrontendController, 'getFileUrl'], 10, 2);
        $this->Wordpress->addFilter('the_posts', [$FrontendController, 'showPosts']);
        $this->Wordpress->addFilter('posts_where_paged', [$FrontendController, 'showPostSql']);
        $this->Wordpress->addFilter('get_terms_args', [$FrontendController, 'getTermArguments']);
        $this->Wordpress->addFilter('wp_get_nav_menu_items', [$FrontendController, 'showCustomMenu']);
        $this->Wordpress->addFilter('comments_array', [$FrontendController, 'showComment']);
        $this->Wordpress->addFilter('the_comments', [$FrontendController, 'showComment']);
        $this->Wordpress->addFilter('get_pages', [$FrontendController, 'showPages'], 20);
        $this->Wordpress->addFilter('get_terms', [$FrontendController, 'showTerms'], 20);
        $this->Wordpress->addFilter('get_term', [$FrontendController, 'showTerm'], 20, 2);
        $this->Wordpress->addFilter('get_ancestors', [$FrontendController, 'showAncestors'], 20, 4);
        $this->Wordpress->addFilter('get_next_post_where', [$FrontendController, 'showNextPreviousPost']);
        $this->Wordpress->addFilter('get_previous_post_where', [$FrontendController, 'showNextPreviousPost']);
        $this->Wordpress->addFilter('post_link', [$FrontendController, 'cachePostLinks'], 10, 2);
        $this->Wordpress->addFilter('edit_post_link', [$FrontendController, 'showGroupMembership'], 10, 2);
        $this->Wordpress->addFilter('parse_query', [$FrontendController, 'parseQuery']);
        $this->Wordpress->addFilter('getarchives_where', [$FrontendController, 'showPostSql']);
        $this->Wordpress->addFilter('wp_count_posts', [$FrontendController, 'showPostCount'], 10, 3);
        $this->Wordpress->addFilter('wpseo_sitemap_entry', [$FrontendController, 'getWpSeoUrl'], 1, 3);
    }
}
