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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Controller\AdminSetupController;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileObjectFactory;
use UserAccessManager\FileHandler\FileProtectionFactory;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class UserAccessManager
 *
 * @package UserAccessManager
 */
class UserAccessManager
{
    const VERSION = '2.0.13';
    const DB_VERSION = '1.5.1';

    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var SetupHandler
     */
    private $setupHandler;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

    /**
     * @var ControllerFactory
     */
    private $controllerFactory;

    /**
     * @var ConfigParameterFactory
     */
    private $configParameterFactory;

    /**
     * @var FileProtectionFactory
     */
    private $fileProtectionFactory;

    /**
     * @var FileObjectFactory
     */
    private $fileObjectFactory;
    
    /**
     * UserAccessManager constructor.
     *
     * @param Php                    $php
     * @param Wordpress              $wordpress
     * @param Util                   $util
     * @param Cache                  $cache
     * @param Config                 $config
     * @param Database               $database
     * @param ObjectHandler          $objectHandler
     * @param AccessHandler          $accessHandler
     * @param FileHandler            $fileHandler
     * @param SetupHandler           $setupHandler
     * @param UserGroupFactory       $userGroupFactory
     * @param ControllerFactory      $controllerFactory
     * @param ConfigParameterFactory $configParameterFactory
     * @param FileProtectionFactory  $fileProtectionFactory
     * @param FileObjectFactory      $fileObjectFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Util $util,
        Cache $cache,
        Config $config,
        Database $database,
        ObjectHandler $objectHandler,
        AccessHandler $accessHandler,
        FileHandler $fileHandler,
        SetupHandler $setupHandler,
        UserGroupFactory $userGroupFactory,
        ControllerFactory $controllerFactory,
        ConfigParameterFactory $configParameterFactory,
        FileProtectionFactory $fileProtectionFactory,
        FileObjectFactory $fileObjectFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->util = $util;
        $this->cache = $cache;
        $this->config = $config;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->accessHandler = $accessHandler;
        $this->fileHandler = $fileHandler;
        $this->setupHandler = $setupHandler;
        $this->userGroupFactory = $userGroupFactory;
        $this->controllerFactory = $controllerFactory;
        $this->configParameterFactory = $configParameterFactory;
        $this->fileProtectionFactory = $fileProtectionFactory;
        $this->fileObjectFactory = $fileObjectFactory;
    }

    /**
     * @return Php
     */
    public function getPhp()
    {
        return $this->php;
    }

    /**
     * @return Wordpress
     */
    public function getWordpress()
    {
        return $this->wordpress;
    }

    /**
     * @return Util
     */
    public function getUtil()
    {
        return $this->util;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return ObjectHandler
     */
    public function getObjectHandler()
    {
        return $this->objectHandler;
    }

    /**
     * @return AccessHandler
     */
    public function getAccessHandler()
    {
        return $this->accessHandler;
    }

    /**
     * @return FileHandler
     */
    public function getFileHandler()
    {
        return $this->fileHandler;
    }

    /**
     * @return SetupHandler
     */
    public function getSetupHandler()
    {
        return $this->setupHandler;
    }

    /**
     * @return UserGroupFactory
     */
    public function getUserGroupFactory()
    {
        return $this->userGroupFactory;
    }

    /**
     * @return ControllerFactory
     */
    public function getControllerFactory()
    {
        return $this->controllerFactory;
    }

    /**
     * @return ConfigParameterFactory
     */
    public function getConfigParameterFactory()
    {
        return $this->configParameterFactory;
    }

    /**
     * @return FileProtectionFactory
     */
    public function getFileProtectionFactory()
    {
        return $this->fileProtectionFactory;
    }

    /**
     * @return FileObjectFactory
     */
    public function getFileObjectFactory()
    {
        return $this->fileObjectFactory;
    }

    /**
     * Resister the administration menu.
     */
    public function registerAdminMenu()
    {
        if ($this->accessHandler->checkUserAccess() === true) {
            //TODO
            /**
             * --- BOF ---
             * Not the best way to handle full user access. Capabilities seems
             * to be the right way, but it is way difficult.
             */
            //Admin main menu
            $this->wordpress->addMenuPage(
                'User Access Manager',
                'UAM',
                'manage_options',
                'uam_user_group',
                null,
                'div'
            );

            //Admin sub menus
            $adminUserGroupController = $this->controllerFactory->createAdminUserGroupController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_MANAGE_GROUP,
                TXT_UAM_MANAGE_GROUP,
                'read',
                'uam_user_group',
                [$adminUserGroupController, 'render']
            );

            $adminSetupController = $this->controllerFactory->createAdminSettingsController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETTINGS,
                TXT_UAM_SETTINGS,
                'read',
                'uam_settings',
                [$adminSetupController, 'render']
            );

            $adminSetupController = $this->controllerFactory->createAdminSetupController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETUP,
                TXT_UAM_SETUP,
                'read',
                'uam_setup',
                [$adminSetupController, 'render']
            );

            $adminAboutController = $this->controllerFactory->createAdminAboutController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_ABOUT,
                TXT_UAM_ABOUT,
                'read',
                'uam_about',
                [$adminAboutController, 'render']
            );

            $this->wordpress->doAction('uam_add_sub_menu');

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
        $adminController = $this->controllerFactory->createAdminController();
        $this->wordpress->addAction('admin_enqueue_scripts', [$adminController, 'enqueueStylesAndScripts']);
        $this->wordpress->addAction('wp_dashboard_setup', [$adminController, 'setupAdminDashboard']);
        $updateAction = $adminController->getRequestParameter('uam_update_db');

        if ($this->setupHandler->isDatabaseUpdateNecessary() === true
            && $updateAction !== AdminSetupController::UPDATE_BLOG
            && $updateAction !== AdminSetupController::UPDATE_NETWORK
        ) {
            $this->wordpress->addAction('admin_notices', [$adminController, 'showDatabaseNotice']);
        }

        $taxonomies = $this->objectHandler->getTaxonomies();
        $taxonomy = $adminController->getRequestParameter('taxonomy');

        if ($taxonomy !== null) {
            $taxonomies[$taxonomy] = $taxonomy;
        }

        $adminObjectController = $this->controllerFactory->createAdminObjectController();

        if ($this->accessHandler->checkUserAccess() === true
            || $this->config->authorsCanAddPostsToGroups() === true
        ) {
            //Admin actions
            $this->wordpress->addAction(
                'manage_posts_custom_column',
                [$adminObjectController, 'addPostColumn'],
                10,
                2
            );
            $this->wordpress->addAction(
                'manage_pages_custom_column',
                [$adminObjectController, 'addPostColumn'],
                10,
                2
            );
            $this->wordpress->addAction('save_post', [$adminObjectController, 'savePostData']);
            $this->wordpress->addAction('edit_user_profile', [$adminObjectController, 'showUserProfile']);
            $this->wordpress->addAction('profile_update', [$adminObjectController, 'saveUserData']);

            $this->wordpress->addAction('bulk_edit_custom_box', [$adminObjectController, 'addBulkAction']);
            $this->wordpress->addAction('create_term', [$adminObjectController, 'saveTermData']);
            $this->wordpress->addAction('edit_term', [$adminObjectController, 'saveTermData']);

            //Taxonomies
            foreach ($taxonomies as $taxonomy) {
                $this->wordpress->addAction(
                    'manage_'.$taxonomy.'_custom_column',
                    [$adminObjectController, 'addTermColumn'],
                    10,
                    3
                );
                $this->wordpress->addAction(
                    $taxonomy.'_add_form_fields',
                    [$adminObjectController, 'showTermEditForm']
                );
                $this->wordpress->addAction(
                    $taxonomy.'_edit_form_fields',
                    [$adminObjectController, 'showTermEditForm']
                );
            }

            if ($this->config->lockFile() === true) {
                $this->wordpress->addAction(
                    'manage_media_custom_column',
                    [$adminObjectController, 'addPostColumn'],
                    10,
                    2
                );
                $this->wordpress->addAction(
                    'attachment_fields_to_edit',
                    [$adminObjectController, 'showMediaFile'],
                    10,
                    2
                );
                $this->wordpress->addAction(
                    'wp_ajax_save-attachment-compat',
                    [$adminObjectController, 'saveAjaxAttachmentData'],
                    1,
                    9
                );
            }

            //Admin filters
            //The filter we use instead of add|edit_attachment action, reason see top
            $this->wordpress->addFilter('attachment_fields_to_save', [$adminObjectController, 'saveAttachmentData']);

            $this->wordpress->addFilter('manage_posts_columns', [$adminObjectController, 'addPostColumnsHeader']);
            $this->wordpress->addFilter('manage_pages_columns', [$adminObjectController, 'addPostColumnsHeader']);

            $this->wordpress->addFilter('manage_users_columns', [$adminObjectController, 'addUserColumnsHeader'], 10);
            $this->wordpress->addFilter(
                'manage_users_custom_column',
                [$adminObjectController, 'addUserColumn'],
                10,
                3
            );

            foreach ($taxonomies as $taxonomy) {
                $this->wordpress->addFilter(
                    'manage_edit-'.$taxonomy.'_columns',
                    [$adminObjectController, 'addTermColumnsHeader']
                );
            }

            if ($this->config->lockFile() === true) {
                $this->wordpress->addFilter('manage_media_columns', [$adminObjectController, 'addPostColumnsHeader']);
            }

            //Admin meta boxes
            $postTypes = $this->objectHandler->getPostTypes();

            foreach ($postTypes as $postType) {
                // there is no need for a meta box for attachments if files are locked
                if ($postType === ObjectHandler::ATTACHMENT_OBJECT_TYPE && $this->config->lockFile() !== true) {
                    continue;
                }

                $this->wordpress->addMetaBox(
                    'uma_post_access',
                    TXT_UAM_COLUMN_ACCESS,
                    [$adminObjectController, 'editPostContent'],
                    $postType,
                    'side'
                );
            }
        }

        //Clean up at deleting should always be done.
        $this->wordpress->addAction('update_option_permalink_structure', [$adminObjectController, 'updatePermalink']);
        $this->wordpress->addAction('delete_post', [$adminObjectController, 'removePostData']);
        $this->wordpress->addAction('delete_attachment', [$adminObjectController, 'removePostData']);
        $this->wordpress->addAction('delete_user', [$adminObjectController, 'removeUserData']);
        $this->wordpress->addAction('delete_term', [$adminObjectController, 'removeTermData']);

        $adminObjectController->checkRightsToEditContent();
    }

    /**
     * Adds the actions and filers.
     */
    public function addActionsAndFilters()
    {
        $frontendController = $this->controllerFactory->createFrontendController();

        //Actions
        $this->wordpress->addAction('admin_menu', [$this, 'registerAdminMenu']);
        $this->wordpress->addAction('admin_init', [$this, 'registerAdminActionsAndFilters']);
        $this->wordpress->addAction('registered_post_type', [$this->objectHandler, 'registeredPostType'], 10, 2);
        $this->wordpress->addAction('registered_taxonomy', [$this->objectHandler, 'registeredTaxonomy'], 10, 3);
        $this->wordpress->addAction('registered_post_type', [$this->config, 'flushConfigParameters']);
        $this->wordpress->addAction('registered_taxonomy', [$this->config, 'flushConfigParameters']);
        $this->wordpress->addAction('wp_enqueue_scripts', [$frontendController, 'enqueueStylesAndScripts']);

        //Filters
        $getFile = $frontendController->getRequestParameter('uamgetfile');

        if ($this->config->getRedirect() !== false || $getFile !== null) {
            $this->wordpress->addFilter('wp_headers', [$frontendController, 'redirect'], 10, 2);
        }

        $this->wordpress->addFilter('wp_get_attachment_thumb_url', [$frontendController, 'getFileUrl'], 10, 2);
        $this->wordpress->addFilter('wp_get_attachment_url', [$frontendController, 'getFileUrl'], 10, 2);
        $this->wordpress->addFilter('posts_pre_query', [$frontendController, 'postsPreQuery'], 10, 2);
        $this->wordpress->addFilter('the_posts', [$frontendController, 'showPosts'], 9);
        $this->wordpress->addFilter('get_attached_file', [$frontendController, 'getAttachedFile'], 10, 2);
        $this->wordpress->addFilter('the_content', [$frontendController, 'showContent']);
        $this->wordpress->addFilter('posts_where_paged', [$frontendController, 'showPostSql']);
        $this->wordpress->addFilter('get_terms_args', [$frontendController, 'getTermArguments']);
        $this->wordpress->addFilter('wp_get_nav_menu_items', [$frontendController, 'showCustomMenu']);
        $this->wordpress->addFilter('comments_array', [$frontendController, 'showComment']);
        $this->wordpress->addFilter('the_comments', [$frontendController, 'showComment']);
        $this->wordpress->addFilter('get_pages', [$frontendController, 'showPages'], 20);
        $this->wordpress->addFilter('get_terms', [$frontendController, 'showTerms'], 20);
        $this->wordpress->addFilter('get_term', [$frontendController, 'showTerm'], 20, 2);
        $this->wordpress->addFilter('get_ancestors', [$frontendController, 'showAncestors'], 20, 4);
        $this->wordpress->addFilter('get_next_post_where', [$frontendController, 'showNextPreviousPost']);
        $this->wordpress->addFilter('get_previous_post_where', [$frontendController, 'showNextPreviousPost']);
        $this->wordpress->addFilter('post_link', [$frontendController, 'cachePostLinks'], 10, 2);
        $this->wordpress->addFilter('edit_post_link', [$frontendController, 'showGroupMembership'], 10, 2);
        $this->wordpress->addFilter('parse_query', [$frontendController, 'parseQuery']);
        $this->wordpress->addFilter('getarchives_where', [$frontendController, 'showPostSql']);
        $this->wordpress->addFilter('wp_count_posts', [$frontendController, 'showPostCount'], 10, 3);
        $this->wordpress->addFilter('wpseo_sitemap_entry', [$frontendController, 'getWpSeoUrl'], 1, 3);
    }
}
