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

declare(strict_types=1);

namespace UserAccessManager;

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Cache\CacheProviderFactory;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Controller\Backend\DynamicGroupsController;
use UserAccessManager\Controller\Backend\PostObjectController;
use UserAccessManager\Controller\Backend\TermObjectController;
use UserAccessManager\Controller\Backend\UserObjectController;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\File\FileObjectFactory;
use UserAccessManager\File\FileProtectionFactory;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Widget\WidgetFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class UserAccessManager
 *
 * @package UserAccessManager
 */
class UserAccessManager
{
    const VERSION = '2.2.15';
    const DB_VERSION = '1.6.1';

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
     * @var MainConfig
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
     * @var UserHandler
     */
    private $userHandler;

    /**
     * @var UserGroupHandler
     */
    private $userGroupHandler;

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
     * @var ObjectMembershipHandlerFactory
     */
    private $membershipHandlerFactory;

    /**
     * @var ControllerFactory
     */
    private $controllerFactory;

    /**
     * @var WidgetFactory
     */
    private $widgetFactory;

    /**
     * @var CacheProviderFactory
     */
    private $cacheProviderFactory;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

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
      * @param Php                            $php
     * @param Wordpress                      $wordpress
     * @param Util                           $util
     * @param Cache                          $cache
     * @param MainConfig                     $config
     * @param Database                       $database
     * @param ObjectHandler                  $objectHandler
     * @param UserHandler                    $userHandler
     * @param UserGroupHandler               $userGroupHandler
     * @param AccessHandler                  $accessHandler
     * @param FileHandler                    $fileHandler
     * @param SetupHandler                   $setupHandler
     * @param UserGroupFactory               $userGroupFactory
     * @param ObjectMembershipHandlerFactory $membershipHandlerFactory
     * @param ControllerFactory              $controllerFactory
     * @param WidgetFactory                  $widgetFactory
     * @param CacheProviderFactory           $cacheProviderFactory
     * @param ConfigFactory                  $configFactory
     * @param ConfigParameterFactory         $configParameterFactory
     * @param FileProtectionFactory          $fileProtectionFactory
     * @param FileObjectFactory              $fileObjectFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Util $util,
        Cache $cache,
        MainConfig $config,
        Database $database,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        AccessHandler $accessHandler,
        FileHandler $fileHandler,
        SetupHandler $setupHandler,
        UserGroupFactory $userGroupFactory,
        ObjectMembershipHandlerFactory $membershipHandlerFactory,
        ControllerFactory $controllerFactory,
        WidgetFactory $widgetFactory,
        CacheProviderFactory $cacheProviderFactory,
        ConfigFactory $configFactory,
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
        $this->userHandler = $userHandler;
        $this->userGroupHandler = $userGroupHandler;
        $this->accessHandler = $accessHandler;
        $this->fileHandler = $fileHandler;
        $this->setupHandler = $setupHandler;
        $this->userGroupFactory = $userGroupFactory;
        $this->membershipHandlerFactory = $membershipHandlerFactory;
        $this->controllerFactory = $controllerFactory;
        $this->widgetFactory = $widgetFactory;
        $this->cacheProviderFactory = $cacheProviderFactory;
        $this->configFactory = $configFactory;
        $this->configParameterFactory = $configParameterFactory;
        $this->fileProtectionFactory = $fileProtectionFactory;
        $this->fileObjectFactory = $fileObjectFactory;

        $this->cache->setActiveCacheProvider($this->config->getActiveCacheProvider());
    }

    /**
     * @return Php
     */
    public function getPhp(): Php
    {
        return $this->php;
    }

    /**
     * @return Wordpress
     */
    public function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

    /**
     * @return Util
     */
    public function getUtil(): Util
    {
        return $this->util;
    }

    /**
     * @return Cache
     */
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * @return MainConfig
     */
    public function getConfig(): MainConfig
    {
        return $this->config;
    }

    /**
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * @return ObjectHandler
     */
    public function getObjectHandler(): ObjectHandler
    {
        return $this->objectHandler;
    }

    /**
     * @return UserHandler
     */
    public function getUserHandler(): UserHandler
    {
        return $this->userHandler;
    }

    /**
     * @return UserGroupHandler
     */
    public function getUserGroupHandler(): UserGroupHandler
    {
        return $this->userGroupHandler;
    }

    /**
     * @return AccessHandler
     */
    public function getAccessHandler(): AccessHandler
    {
        return $this->accessHandler;
    }

    /**
     * @return FileHandler
     */
    public function getFileHandler(): FileHandler
    {
        return $this->fileHandler;
    }

    /**
     * @return SetupHandler
     */
    public function getSetupHandler(): SetupHandler
    {
        return $this->setupHandler;
    }

    /**
     * @return UserGroupFactory
     */
    public function getUserGroupFactory(): UserGroupFactory
    {
        return $this->userGroupFactory;
    }

    /**
     * @return ObjectMembershipHandlerFactory
     */
    public function getObjectMembershipHandlerFactory(): ObjectMembershipHandlerFactory
    {
        return $this->membershipHandlerFactory;
    }

    /**
     * @return ControllerFactory
     */
    public function getControllerFactory(): ControllerFactory
    {
        return $this->controllerFactory;
    }

    /**
     * @return WidgetFactory
     */
    public function getWidgetFactory(): WidgetFactory
    {
        return $this->widgetFactory;
    }

    /**
     * @return CacheProviderFactory
     */
    public function getCacheProviderFactory(): CacheProviderFactory
    {
        return $this->cacheProviderFactory;
    }

    /**
     * @return ConfigFactory
     */
    public function getConfigFactory(): ConfigFactory
    {
        return $this->configFactory;
    }

    /**
     * @return ConfigParameterFactory
     */
    public function getConfigParameterFactory(): ConfigParameterFactory
    {
        return $this->configParameterFactory;
    }

    /**
     * @return FileProtectionFactory
     */
    public function getFileProtectionFactory(): FileProtectionFactory
    {
        return $this->fileProtectionFactory;
    }

    /**
     * @return FileObjectFactory
     */
    public function getFileObjectFactory(): FileObjectFactory
    {
        return $this->fileObjectFactory;
    }

    /**
     * Resister the administration menu.
     */
    public function registerAdminMenu()
    {
        if ($this->userHandler->checkUserAccess() === true) {
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
            $backendUserGroupController = $this->controllerFactory->createBackendUserGroupController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_MANAGE_GROUP,
                TXT_UAM_MANAGE_GROUP,
                'read',
                'uam_user_group',
                [$backendUserGroupController, 'render']
            );

            $backendSettingsController = $this->controllerFactory->createBackendSettingsController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETTINGS,
                TXT_UAM_SETTINGS,
                'read',
                'uam_settings',
                [$backendSettingsController, 'render']
            );

            $backendSetupController = $this->controllerFactory->createBackendSetupController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_SETUP,
                TXT_UAM_SETUP,
                'read',
                'uam_setup',
                [$backendSetupController, 'render']
            );

            $backendAboutController = $this->controllerFactory->createBackendAboutController();
            $this->wordpress->addSubmenuPage(
                'uam_user_group',
                TXT_UAM_ABOUT,
                TXT_UAM_ABOUT,
                'read',
                'uam_about',
                [$backendAboutController, 'render']
            );

            $this->wordpress->doAction('uam_add_sub_menu');

            /**
             * --- EOF ---
             */
        }
    }

    /**
     * Adds the backend filters.
      * @param DynamicGroupsController $dynamicGroupController
     * @param PostObjectController    $postObjectController
     * @param TermObjectController    $termObjectController
     * @param UserObjectController    $userObjectController
     * @param array                   $taxonomies
     */
    private function addAdminActions(
        DynamicGroupsController $dynamicGroupController,
        PostObjectController $postObjectController,
        TermObjectController $termObjectController,
        UserObjectController $userObjectController,
        array $taxonomies
    ) {
        $this->wordpress->addAction(
            'manage_posts_custom_column',
            [$postObjectController, 'addPostColumn'],
            10,
            2
        );
        $this->wordpress->addAction(
            'manage_pages_custom_column',
            [$postObjectController, 'addPostColumn'],
            10,
            2
        );
        $this->wordpress->addAction('save_post', [$postObjectController, 'savePostData']);
        $this->wordpress->addAction('add_attachment', [$postObjectController, 'addAttachment']);
        $this->wordpress->addAction('edit_user_profile', [$userObjectController, 'showUserProfile']);
        $this->wordpress->addAction('user_new_form', [$userObjectController, 'showUserProfile']);
        $this->wordpress->addAction('user_register', [$userObjectController, 'saveUserData']);
        $this->wordpress->addAction('profile_update', [$userObjectController, 'saveUserData']);

        $this->wordpress->addAction('bulk_edit_custom_box', [$postObjectController, 'addBulkAction']);
        $this->wordpress->addAction('create_term', [$termObjectController, 'saveTermData']);
        $this->wordpress->addAction('edit_term', [$termObjectController, 'saveTermData']);

        //Taxonomies
        foreach ($taxonomies as $taxonomy) {
            $this->wordpress->addAction(
                'manage_'.$taxonomy.'_custom_column',
                [$termObjectController, 'addTermColumn'],
                10,
                3
            );
            $this->wordpress->addAction(
                $taxonomy.'_add_form_fields',
                [$termObjectController, 'showTermEditForm']
            );
            $this->wordpress->addAction(
                $taxonomy.'_edit_form_fields',
                [$termObjectController, 'showTermEditForm']
            );
        }

        if ($this->config->lockFile() === true) {
            $this->wordpress->addAction(
                'manage_media_custom_column',
                [$postObjectController, 'addPostColumn'],
                10,
                2
            );
            $this->wordpress->addAction(
                'attachment_fields_to_edit',
                [$postObjectController, 'showMediaFile'],
                10,
                2
            );
            $this->wordpress->addAction(
                'wp_ajax_save-attachment-compat',
                [$postObjectController, 'saveAjaxAttachmentData'],
                1,
                9
            );
        }

        //Admin ajax actions
        $this->wordpress->addAction(
            'wp_ajax_uam-get-dynamic-group',
            [$dynamicGroupController, 'getDynamicGroupsForAjax']
        );
    }

    /**
     * Adds the admin filters.
      * @param PostObjectController $postObjectController
     * @param TermObjectController $termObjectController
     * @param UserObjectController $userObjectController
     * @param array                $taxonomies
     */
    private function addAdminFilters(
        PostObjectController $postObjectController,
        TermObjectController $termObjectController,
        UserObjectController $userObjectController,
        array $taxonomies
    ) {
        //The filter we use instead of add|edit_attachment action, reason see top
        $this->wordpress->addFilter('attachment_fields_to_save', [$postObjectController, 'saveAttachmentData']);

        $this->wordpress->addFilter('manage_posts_columns', [$postObjectController, 'addPostColumnsHeader']);
        $this->wordpress->addFilter('manage_pages_columns', [$postObjectController, 'addPostColumnsHeader']);

        $this->wordpress->addFilter('manage_users_columns', [$userObjectController, 'addUserColumnsHeader']);
        $this->wordpress->addFilter(
            'manage_users_custom_column',
            [$userObjectController, 'addUserColumn'],
            10,
            3
        );

        foreach ($taxonomies as $taxonomy) {
            $this->wordpress->addFilter(
                'manage_edit-'.$taxonomy.'_columns',
                [$termObjectController, 'addTermColumnsHeader']
            );
        }

        if ($this->config->lockFile() === true) {
            $this->wordpress->addFilter('manage_media_columns', [$postObjectController, 'addPostColumnsHeader']);
        }
    }

    /**
     * Adds the admin meta boxes.
      * @param PostObjectController $postObjectController
     */
    private function addAdminMetaBoxes(PostObjectController $postObjectController)
    {
        $postTypes = $this->objectHandler->getPostTypes();

        foreach ($postTypes as $postType) {
            // there is no need for a meta box for attachments if files are locked
            if ($postType === ObjectHandler::ATTACHMENT_OBJECT_TYPE && $this->config->lockFile() !== true) {
                continue;
            }

            $this->wordpress->addMetaBox(
                'uam_post_access',
                TXT_UAM_COLUMN_ACCESS,
                [$postObjectController, 'editPostContent'],
                $postType,
                'side'
            );
        }
    }

    /**
     * Register the admin actions and filters
     * @throws UserGroup\UserGroupTypeException
     */
    public function registerAdminActionsAndFilters()
    {
        $backendController = $this->controllerFactory->createBackendController();
        $this->wordpress->addAction('admin_enqueue_scripts', [$backendController, 'enqueueStylesAndScripts']);
        $this->wordpress->addAction('wp_dashboard_setup', [$backendController, 'setupAdminDashboard']);
        $this->wordpress->addAction('admin_notices', [$backendController, 'showAdminNotice']);

        $taxonomies = $this->objectHandler->getTaxonomies();
        $taxonomy = $backendController->getRequestParameter('taxonomy');

        if ($taxonomy !== null) {
            $taxonomies[$taxonomy] = $taxonomy;
        }

        $objectController = $this->controllerFactory->createBackendObjectController();
        $postObjectController = $this->controllerFactory->createBackendPostObjectController();
        $termObjectController = $this->controllerFactory->createBackendTermObjectController();
        $userObjectController = $this->controllerFactory->createBackendUserObjectController();
        $dynamicGroupController = $this->controllerFactory->createBackendDynamicGroupsController();

        if ($this->userHandler->checkUserAccess() === true
            || $this->config->authorsCanAddPostsToGroups() === true
        ) {
            //Admin actions
            $this->addAdminActions(
                $dynamicGroupController,
                $postObjectController,
                $termObjectController,
                $userObjectController,
                $taxonomies
            );

            //Admin filters
            $this->addAdminFilters(
                $postObjectController,
                $termObjectController,
                $userObjectController,
                $taxonomies
            );

            //Admin meta boxes
            $this->addAdminMetaBoxes($postObjectController);
        }

        //Clean up at deleting should always be done.
        $this->wordpress->addAction('delete_post', [$postObjectController, 'removePostData']);
        $this->wordpress->addAction('delete_attachment', [$postObjectController, 'removePostData']);
        $this->wordpress->addAction('delete_user', [$userObjectController, 'removeUserData']);
        $this->wordpress->addAction('delete_term', [$termObjectController, 'removeTermData']);

        $objectController->checkRightsToEditContent();
    }

    /**
     * Adds the actions and filers.
     */
    public function addActionsAndFilters()
    {
        //Actions
        $this->wordpress->addAction('admin_menu', [$this, 'registerAdminMenu']);
        $this->wordpress->addAction('admin_init', [$this, 'registerAdminActionsAndFilters']);
        $this->wordpress->addAction('registered_post_type', [$this->objectHandler, 'registeredPostType'], 10, 2);
        $this->wordpress->addAction('registered_taxonomy', [$this->objectHandler, 'registeredTaxonomy'], 10, 3);
        $this->wordpress->addAction('registered_post_type', [$this->config, 'flushConfigParameters']);
        $this->wordpress->addAction('registered_taxonomy', [$this->config, 'flushConfigParameters']);

        // General frontend controller
        $frontendController = $this->controllerFactory->createFrontendController();

        $this->wordpress->addAction('wp_enqueue_scripts', [$frontendController, 'enqueueStylesAndScripts']);
        $this->wordpress->addFilter('get_ancestors', [$frontendController, 'showAncestors'], 20, 4);
        $this->wordpress->addFilter('wpseo_sitemap_entry', [$frontendController, 'getWpSeoUrl'], 1, 3);

        // Post controller
        $frontendPostController = $this->controllerFactory->createFrontendPostController();

        $this->wordpress->addFilter('posts_pre_query', [$frontendPostController, 'postsPreQuery'], 10, 2);
        $this->wordpress->addFilter('the_posts', [$frontendPostController, 'showPosts'], 9);
        $this->wordpress->addFilter('get_attached_file', [$frontendPostController, 'getAttachedFile'], 10, 2);
        $this->wordpress->addFilter('posts_where_paged', [$frontendPostController, 'showPostSql']);
        $this->wordpress->addFilter('comments_array', [$frontendPostController, 'showComment']);
        $this->wordpress->addFilter('the_comments', [$frontendPostController, 'showComment']);
        $this->wordpress->addFilter('get_pages', [$frontendPostController, 'showPages'], 20);
        $this->wordpress->addFilter('get_next_post_where', [$frontendPostController, 'showNextPreviousPost']);
        $this->wordpress->addFilter('get_previous_post_where', [$frontendPostController, 'showNextPreviousPost']);
        $this->wordpress->addFilter('edit_post_link', [$frontendPostController, 'showEditLink'], 10, 2);
        $this->wordpress->addFilter('parse_query', [$frontendPostController, 'parseQuery']);
        $this->wordpress->addFilter('getarchives_where', [$frontendPostController, 'showPostSql']);
        $this->wordpress->addFilter('wp_count_posts', [$frontendPostController, 'showPostCount'], 10, 3);

        // Short code controller
        $frontendShortCodeController = $this->controllerFactory->createFrontendShortCodeController();

        $this->wordpress->addShortCode('LOGIN_FORM', [$frontendShortCodeController, 'loginFormShortCode']); // Legacy
        $this->wordpress->addShortCode('uam_login_form', [$frontendShortCodeController, 'loginFormShortCode']);
        $this->wordpress->addShortCode('uam_public', [$frontendShortCodeController, 'publicShortCode']);
        $this->wordpress->addShortCode('uam_private', [$frontendShortCodeController, 'privateShortCode']);

        // Term controller
        $frontendTermController = $this->controllerFactory->createFrontendTermController();

        $this->wordpress->addFilter('get_terms_args', [$frontendTermController, 'getTermArguments']);
        $this->wordpress->addFilter('get_terms', [$frontendTermController, 'showTerms'], 20);
        $this->wordpress->addFilter('get_term', [$frontendTermController, 'showTerm'], 20, 2);
        $this->wordpress->addFilter('wp_get_nav_menu_items', [$frontendTermController, 'showCustomMenu']);

        // Redirect controller
        $frontendRedirectController = $this->controllerFactory->createFrontendRedirectController();

        $this->wordpress->addFilter('wp_get_attachment_thumb_url', [$frontendRedirectController, 'getFileUrl'], 10, 2);
        $this->wordpress->addFilter('wp_get_attachment_url', [$frontendRedirectController, 'getFileUrl'], 10, 2);
        $this->wordpress->addFilter('post_link', [$frontendRedirectController, 'cachePostLinks'], 10, 2);

        $getFile = $frontendController->getRequestParameter('uamgetfile');

        if ($this->config->getRedirect() !== false || $getFile !== null) {
            $this->wordpress->addFilter('wp_headers', [$frontendRedirectController, 'redirect'], 10, 2);
        }

        $testXSendFile = $frontendController->getRequestParameter('testXSendFile');

        if ($testXSendFile !== null) {
            $this->wordpress->addFilter('wp_headers', [$frontendRedirectController, 'testXSendFile'], 10, 2);
        }

        // Admin object controller
        $backendCacheController = $this->controllerFactory->createBackendCacheController();

        $this->wordpress->addFilter('clean_term_cache', [$backendCacheController, 'invalidateTermCache']);
        $this->wordpress->addFilter('clean_object_term_cache', [$backendCacheController, 'invalidateTermCache']);
        $this->wordpress->addFilter('clean_post_cache', [$backendCacheController, 'invalidatePostCache']);
        $this->wordpress->addFilter('clean_attachment_cache', [$backendCacheController, 'invalidatePostCache']);

        // Widgets
        $this->wordpress->addAction('widgets_init', function () {
            $this->wordpress->registerWidget($this->widgetFactory->createLoginWidget());
        });
    }
}
