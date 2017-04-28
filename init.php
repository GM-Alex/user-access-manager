<?php
//Classes
use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileObjectFactory;
use UserAccessManager\FileHandler\FileProtectionFactory;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

function initUserAccessManger()
{
    $file = str_replace('init.php', 'user-access-manager.php', __FILE__);
    $php = new Php();
    $wordpress = new Wordpress();
    $util = new Util($php);
    $cache = new Cache();
    $configParameterFactory = new ConfigParameterFactory();
    $database = new Database($wordpress);
    $objectHandler = new ObjectHandler($wordpress, $database);
    $config = new Config($wordpress, $objectHandler, $configParameterFactory, $file);
    $fileObjectFactory = new FileObjectFactory();
    $userGroupFactory = new UserGroupFactory(
        $php,
        $wordpress,
        $database,
        $config,
        $util,
        $objectHandler
    );
    $accessHandler = new AccessHandler(
        $wordpress,
        $config,
        $cache,
        $database,
        $objectHandler,
        $util,
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
        $accessHandler,
        $userGroupFactory,
        $fileHandler,
        $fileObjectFactory,
        $setupHandler
    );

    $userAccessManager = new UserAccessManager(
        $php,
        $wordpress,
        $util,
        $cache,
        $config,
        $database,
        $objectHandler,
        $accessHandler,
        $fileHandler,
        $setupHandler,
        $userGroupFactory,
        $controllerFactory,
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
            $configParameterFactory = new ConfigParameterFactory();
            $database = new Database($wordpress);
            $objectHandler = new ObjectHandler($wordpress, $database);
            $config = new Config($wordpress, $objectHandler, $configParameterFactory, $file);

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
