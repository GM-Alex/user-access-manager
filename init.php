<?php
//Classes
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Cache\CacheProviderFactory;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Controller\Backend\ObjectInformationFactory;
use UserAccessManager\Widget\WidgetFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\File\FileObjectFactory;
use UserAccessManager\File\FileProtectionFactory;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\Setup\Database\DatabaseObjectFactory;
use UserAccessManager\Setup\Database\DatabaseHandler;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\Setup\Update\UpdateFactory;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\UserGroup\UserGroupAssignmentHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Util\DateUtil;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

function initUserAccessManger()
{
    $file = str_replace('init.php', 'user-access-manager.php', __FILE__);
    $php = new Php();
    $wordpress = new Wordpress();
    $util = new Util($php);
    $dateUtil = new DateUtil($wordpress);
    $configFactory = new ConfigFactory($wordpress);
    $configParameterFactory = new ConfigParameterFactory();
    $cacheProviderFactory = new CacheProviderFactory($php, $wordpress, $util, $configFactory, $configParameterFactory);
    $cache = new Cache($wordpress, $cacheProviderFactory);
    $database = new Database($wordpress);
    $objectMapHandler = new ObjectMapHandler($database, $cache);
    $assignmentInformationFactory = new AssignmentInformationFactory();
    $membershipHandlerFactory = new ObjectMembershipHandlerFactory(
        $php,
        $wordpress,
        $database,
        $objectMapHandler,
        $assignmentInformationFactory
    );
    $objectHandler = new ObjectHandler($php, $wordpress, $membershipHandlerFactory);
    $wordpressConfig = new WordpressConfig($wordpress, $file);
    $mainConfig = new MainConfig($wordpress, $objectHandler, $cache, $configParameterFactory);
    $fileObjectFactory = new FileObjectFactory();
    $formFactory = new FormFactory();
    $formHelper = new FormHelper($php, $wordpress, $mainConfig, $formFactory);

    $userGroupFactory = new UserGroupFactory(
        $php,
        $wordpress,
        $database,
        $mainConfig,
        $util,
        $objectHandler,
        $assignmentInformationFactory
    );
    $userHandler = new UserHandler(
        $wordpress,
        $mainConfig,
        $database,
        $objectHandler
    );
    $userGroupHandler = new UserGroupHandler(
        $wordpress,
        $wordpressConfig,
        $database,
        $objectHandler,
        $userHandler,
        $userGroupFactory
    );
    $accessHandler = new AccessHandler(
        $wordpress,
        $mainConfig,
        $database,
        $objectHandler,
        $userHandler,
        $userGroupHandler
    );
    $fileProtectionFactory = new FileProtectionFactory(
        $php,
        $wordpress,
        $wordpressConfig,
        $mainConfig,
        $util
    );
    $fileHandler = new FileHandler(
        $php,
        $wordpress,
        $wordpressConfig,
        $mainConfig,
        $fileProtectionFactory
    );
    $updateFactory = new UpdateFactory($database, $objectHandler);
    $databaseObjectFactory = new DatabaseObjectFactory();
    $databaseHandler = new DatabaseHandler($wordpress, $database, $databaseObjectFactory, $updateFactory);
    $setupHandler = new SetupHandler(
        $wordpress,
        $database,
        $databaseHandler,
        $mainConfig,
        $fileHandler
    );
    $objectInformationFactory = new ObjectInformationFactory();
    $userGroupAssignmentHandler = new UserGroupAssignmentHandler(
        $dateUtil,
        $userHandler,
        $userGroupHandler,
        $userGroupFactory
    );
    $controllerFactory = new ControllerFactory(
        $php,
        $wordpress,
        $database,
        $wordpressConfig,
        $mainConfig,
        $util,
        $dateUtil,
        $cache,
        $objectHandler,
        $objectMapHandler,
        $userHandler,
        $userGroupHandler,
        $userGroupFactory,
        $userGroupAssignmentHandler,
        $accessHandler,
        $fileHandler,
        $fileObjectFactory,
        $setupHandler,
        $formFactory,
        $formHelper,
        $objectInformationFactory
    );
    $widgetFactory = new WidgetFactory($php, $wordpress, $wordpressConfig);

    $userAccessManager = new UserAccessManager(
        $php,
        $wordpress,
        $util,
        $cache,
        $mainConfig,
        $database,
        $objectHandler,
        $userHandler,
        $userGroupHandler,
        $accessHandler,
        $fileHandler,
        $setupHandler,
        $userGroupFactory,
        $membershipHandlerFactory,
        $controllerFactory,
        $widgetFactory,
        $cacheProviderFactory,
        $configFactory,
        $configParameterFactory,
        $fileProtectionFactory,
        $fileObjectFactory
    );

    $wordpress->doAction('uam_init', [$userAccessManager]);

    //install
    if (function_exists('register_activation_hook') === true) {
        register_activation_hook($file, [$setupHandler, 'install']);
    }

    //uninstall
    if (function_exists('register_uninstall_hook')) {
        function uninstallUserAccessManager()
        {
            $file = str_replace('init.php', 'user-access-manager.php', __FILE__);
            $php = new Php();
            $wordpress = new Wordpress();
            $util = new Util($php);
            $database = new Database($wordpress);
            $configFactory = new ConfigFactory($wordpress);
            $configParameterFactory = new ConfigParameterFactory();
            $cacheProviderFactory = new CacheProviderFactory(
                $php,
                $wordpress,
                $util,
                $configFactory,
                $configParameterFactory
            );
            $cache = new Cache($wordpress, $cacheProviderFactory);
            $objectMapHandler = new ObjectMapHandler($database, $cache);
            $assignmentInformationFactory = new AssignmentInformationFactory();
            $membershipHandlerFactory = new ObjectMembershipHandlerFactory(
                $php,
                $wordpress,
                $database,
                $objectMapHandler,
                $assignmentInformationFactory
            );
            $objectHandler = new ObjectHandler($php, $wordpress, $membershipHandlerFactory);
            $wordpressConfig = new WordpressConfig($wordpress, $file);
            $mainConfig = new MainConfig($wordpress, $objectHandler, $cache, $configParameterFactory);

            $fileProtectionFactory = new FileProtectionFactory(
                $php,
                $wordpress,
                $wordpressConfig,
                $mainConfig,
                $util
            );
            $fileHandler = new FileHandler(
                $php,
                $wordpress,
                $wordpressConfig,
                $mainConfig,
                $fileProtectionFactory
            );
            $updateFactory = new UpdateFactory($database, $objectHandler);
            $databaseObjectFactory = new DatabaseObjectFactory();
            $databaseHandler = new DatabaseHandler($wordpress, $database, $databaseObjectFactory, $updateFactory);
            $setupHandler = new SetupHandler(
                $wordpress,
                $database,
                $databaseHandler,
                $mainConfig,
                $fileHandler
            );

            $setupHandler->uninstall();
        }

        register_uninstall_hook($file, 'uninstallUserAccessManager');
    }

    //deactivation
    if (function_exists('register_deactivation_hook')) {
        register_deactivation_hook($file, [$setupHandler, 'deactivate']);
    }

    $userAccessManager->addActionsAndFilters();

    //Add the cli interface to the known commands
    if (defined('WP_CLI') === true && WP_CLI === true) {
        $cliWrapper = new \UserAccessManager\Wrapper\WordpressCli();

        $groupCommand = new \UserAccessManager\Command\GroupCommand($cliWrapper, $userGroupHandler, $userGroupFactory);
        $objectCommand = new \UserAccessManager\Command\ObjectCommand($cliWrapper, $userGroupHandler);

        try {
            \WP_CLI::add_command('uam groups', $groupCommand);
            \WP_CLI::add_command('uam objects', $objectCommand);
        } catch (Exception $exception) {
            // Do nothing
        }
    }

    return $userAccessManager;
}
