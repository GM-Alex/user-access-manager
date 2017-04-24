<?php
/**
 * Plugin Name: User Access Manager
 * Plugin URI: https://wordpress.org/plugins/user-access-manager/
 * Author URI: https://twitter.com/GM_Alex
 * Version: 2.0.3
 * Author: Alexander Schneider
 * Description: Manage the access to your posts, pages, categories and files.
 * Text Domain: user-access-manager
 *
 * user-access-manager.php
 *
 * The the user access manager main file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
//Defines
require_once 'includes/language.php';
require_once 'autoload.php';

//Check requirements
$stop = false;

//Check php version
$phpVersion = phpversion();

if (version_compare($phpVersion, '5.4') === -1) {
    add_action(
        'admin_notices',
        function () {
            echo '<div id="message" class="error"><p><strong>'
                .sprintf(TXT_UAM_PHP_VERSION_TO_LOW, phpversion())
                .'</strong></p></div>';
        }
    );

    $stop = true;
}

//Check wordpress version
global $wp_version;

if (version_compare($wp_version, '4.6') === -1) {
    add_action(
        'admin_notices',
        function () use ($wp_version) {
            echo '<div id="message" class="error"><p><strong>'
                .sprintf(TXT_UAM_WORDPRESS_VERSION_TO_LOW, $wp_version)
                .'</strong></p></div>';
        }
    );

    $stop = true;
}

//If we have a error stop plugin.
if ($stop === true) {
    return;
}

//Load language
$locale = apply_filters('plugin_locale', get_locale(), 'user-access-manager');
load_textdomain('user-access-manager', WP_LANG_DIR.'/user-access-manager/user-access-manager-'.$locale.'.mo');
load_plugin_textdomain('user-access-manager', false, plugin_basename(dirname(__FILE__)).'/languages');

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

global $userAccessManager;

function initUserAccessManger()
{
    $php = new Php();
    $wordpress = new Wordpress();
    $util = new Util($php);
    $cache = new Cache();
    $configParameterFactory = new ConfigParameterFactory();
    $database = new Database($wordpress);
    $objectHandler = new ObjectHandler($wordpress, $database);
    $config = new Config($wordpress, $objectHandler, $configParameterFactory, __FILE__);
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
    if (function_exists('register_activation_hook')) {
        register_activation_hook(__FILE__, [$setupHandler, 'install']);
    }

    if (!function_exists("userAccessManagerUninstall")) {
        function userAccessManagerUninstall()
        {
            $php = new Php();
            $wordpress = new Wordpress();
            $util = new Util($php);
            $configParameterFactory = new ConfigParameterFactory();
            $database = new Database($wordpress);
            $objectHandler = new ObjectHandler($wordpress, $database);
            $config = new Config($wordpress, $objectHandler, $configParameterFactory, __FILE__);

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
    }

    //uninstall
    if (function_exists('register_uninstall_hook')) {
        register_uninstall_hook(__FILE__, 'userAccessManagerUninstall');
    } elseif (function_exists('register_deactivation_hook')) {
        //Fallback
        register_deactivation_hook(__FILE__, 'userAccessManagerUninstall');
    }

    //deactivation
    if (function_exists('register_deactivation_hook')) {
        register_deactivation_hook(__FILE__, [$setupHandler, 'deactivate']);
    }

    $userAccessManager->addActionsAndFilters();

    //Add the cli interface to the known commands
    if (defined('WP_CLI') && WP_CLI) {
        $cliWrapper = new \UserAccessManager\Wrapper\WordpressCli();

        $groupCommand = new \UserAccessManager\Command\GroupCommand($cliWrapper, $accessHandler, $userGroupFactory);
        \WP_CLI::add_command('uam groups', $groupCommand);

        $objectCommand = new \UserAccessManager\Command\ObjectCommand($cliWrapper, $accessHandler);
        \WP_CLI::add_command('uam objects', $objectCommand);
    }
}

initUserAccessManger();
