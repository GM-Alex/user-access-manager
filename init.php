<?php
//Classes
use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Cache\CacheProviderFactory;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Widget\WidgetFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileObjectFactory;
use UserAccessManager\FileHandler\FileProtectionFactory;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\UserHandler\UserHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

function initUserAccessManger()
{
    $file = str_replace('init.php', 'user-access-manager.php', __FILE__);
    $php = new Php();
    $wordpress = new Wordpress();
    $util = new Util($php);
    $configFactory = new ConfigFactory($wordpress);
    $configParameterFactory = new ConfigParameterFactory();
    $cacheProviderFactory = new CacheProviderFactory($php, $wordpress, $util, $configFactory, $configParameterFactory);
    $cache = new Cache($wordpress, $cacheProviderFactory);
    $database = new Database($wordpress);
    $assignmentInformationFactory = new AssignmentInformationFactory();
    $membershipHandlerFactory = new ObjectMembershipHandlerFactory(
        $php,
        $wordpress,
        $database,
        $assignmentInformationFactory
    );
    $objectHandler = new ObjectHandler($php, $wordpress, $database, $cache, $membershipHandlerFactory);
    $config = new MainConfig($wordpress, $objectHandler, $cache, $configParameterFactory, $file);
    $fileObjectFactory = new FileObjectFactory();
    $formFactory = new FormFactory();
    $formHelper = new FormHelper($php, $wordpress, $config, $formFactory);

    $userGroupFactory = new UserGroupFactory(
        $php,
        $wordpress,
        $database,
        $config,
        $util,
        $objectHandler,
        $assignmentInformationFactory
    );
    $userHandler = new UserHandler(
        $wordpress,
        $config,
        $database,
        $objectHandler
    );
    $accessHandler = new AccessHandler(
        $wordpress,
        $config,
        $database,
        $objectHandler,
        $userHandler,
        $userGroupFactory
    );
    $fileProtectionFactory = new FileProtectionFactory(
        $php,
        $wordpress,
        $config,
        $util
    );
    $fileHandler = new FileHandler(
        $php,
        $wordpress,
        $config,
        $fileProtectionFactory
    );
    $setupHandler = new SetupHandler(
        $wordpress,
        $database,
        $objectHandler,
        $fileHandler
    );
    $controllerFactory = new ControllerFactory(
        $php,
        $wordpress,
        $database,
        $config,
        $util,
        $cache,
        $objectHandler,
        $userHandler,
        $accessHandler,
        $userGroupFactory,
        $fileHandler,
        $fileObjectFactory,
        $setupHandler,
        $formFactory,
        $formHelper
    );
    $widgetFactory = new WidgetFactory($php, $wordpress, $config);

    $userAccessManager = new UserAccessManager(
        $php,
        $wordpress,
        $util,
        $cache,
        $config,
        $database,
        $objectHandler,
        $userHandler,
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
            $assignmentInformationFactory = new AssignmentInformationFactory();
            $membershipHandlerFactory = new ObjectMembershipHandlerFactory(
                $php,
                $wordpress,
                $database,
                $assignmentInformationFactory
            );
            $objectHandler = new ObjectHandler($php, $wordpress, $database, $cache, $membershipHandlerFactory);
            $config = new MainConfig($wordpress, $objectHandler, $cache, $configParameterFactory, $file);

            $fileProtectionFactory = new FileProtectionFactory(
                $php,
                $wordpress,
                $config,
                $util
            );
            $fileHandler = new FileHandler(
                $php,
                $wordpress,
                $config,
                $fileProtectionFactory
            );
            $setupHandler = new SetupHandler(
                $wordpress,
                $database,
                $objectHandler,
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

        $groupCommand = new \UserAccessManager\Command\GroupCommand($cliWrapper, $accessHandler, $userGroupFactory);
        \WP_CLI::add_command('uam groups', $groupCommand);

        $objectCommand = new \UserAccessManager\Command\ObjectCommand($cliWrapper, $accessHandler);
        \WP_CLI::add_command('uam objects', $objectCommand);
    }

    return $userAccessManager;
}
