<?php
/**
 * Plugin Name: User Access Manager
 * Plugin URI: https://wordpress.org/plugins/user-access-manager/
 * Author URI: https://twitter.com/GM_Alex
 * Version: 1.2.14
 * Author: Alexander Schneider
 * Description: Manage the access to your posts, pages, categories and files.
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
 * @version   SVN: $Id$
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
        create_function(
            '',
            'echo \'<div id="message" class="error"><p><strong>'.
            sprintf(TXT_UAM_PHP_VERSION_TO_LOW, $phpVersion).
            '</strong></p></div>\';'
        )
    );

    $stop = true;
}

//Check wordpress version
global $wp_version;

if (version_compare($wp_version, '4.6') === -1) {
    add_action(
        'admin_notices',
        create_function(
            '',
            'echo \'<div id="message" class="error"><p><strong>'.
            sprintf(TXT_UAM_WORDPRESS_VERSION_TO_LOW, $wp_version).
            '</strong></p></div>\';'
        )
    );

    $stop = true;
}

//If we have a error stop plugin.
if ($stop === true) {
    return;
}

//Load language
load_plugin_textdomain('user-access-manager', false, 'user-access-manager/lang');

//Classes
use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileProtectionFactory;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

$Php = new Php();
$Wordpress = new Wordpress();
$Util = new Util($Php);
$Cache = new Cache();
$ConfigParameterFactory = new ConfigParameterFactory();
$Database = new Database($Wordpress);
$ObjectHandler = new ObjectHandler($Wordpress, $Database);
$Config = new Config($Wordpress, $ObjectHandler, $ConfigParameterFactory, __FILE__);
$UserGroupFactory = new UserGroupFactory(
    $Wordpress,
    $Database,
    $Config,
    $Util,
    $ObjectHandler
);
$AccessHandler = new AccessHandler(
    $Wordpress,
    $Config,
    $Cache,
    $Database,
    $ObjectHandler,
    $Util,
    $UserGroupFactory
);
$FileProtectionFactory = new FileProtectionFactory(
    $Php,
    $Wordpress,
    $Config,
    $Util
);
$FileHandler = new FileHandler(
    $Php,
    $Wordpress,
    $Config,
    $FileProtectionFactory
);
$SetupHandler = new SetupHandler(
    $Wordpress,
    $Database,
    $ObjectHandler,
    $FileHandler
);
$ControllerFactory = new ControllerFactory(
    $Php,
    $Wordpress,
    $Database,
    $Config,
    $Util,
    $Cache,
    $ObjectHandler,
    $AccessHandler,
    $UserGroupFactory,
    $FileHandler,
    $SetupHandler
);
$UserAccessManager = new UserAccessManager(
    $Php,
    $Wordpress,
    $Config,
    $ObjectHandler,
    $AccessHandler,
    $SetupHandler,
    $ControllerFactory
);

$Wordpress->doAction('uam_init', [
    $Wordpress,
    $Util,
    $Cache,
    $ConfigParameterFactory,
    $Database,
    $ObjectHandler,
    $Config,
    $UserGroupFactory,
    $AccessHandler,
    $FileProtectionFactory,
    $FileHandler,
    $ControllerFactory,
    $UserAccessManager
]);

//install
if (function_exists('register_activation_hook')) {
    register_activation_hook(__FILE__, [$SetupHandler, 'install']);
}

if (!function_exists("userAccessManagerUninstall")) {
    function userAccessManagerUninstall()
    {
        $Php = new Php();
        $Wordpress = new Wordpress();
        $Util = new Util($Php);
        $ConfigParameterFactory = new ConfigParameterFactory();
        $Database = new Database($Wordpress);
        $ObjectHandler = new ObjectHandler($Wordpress, $Database);
        $Config = new Config($Wordpress, $ObjectHandler, $ConfigParameterFactory, __FILE__);

        $FileProtectionFactory = new FileProtectionFactory(
            $Php,
            $Wordpress,
            $Config,
            $Util
        );
        $FileHandler = new FileHandler(
            $Php,
            $Wordpress,
            $Config,
            $FileProtectionFactory
        );
        $SetupHandler = new SetupHandler(
            $Wordpress,
            $Database,
            $ObjectHandler,
            $FileHandler
        );

        $SetupHandler->uninstall();
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
    register_deactivation_hook(__FILE__, [$SetupHandler, 'deactivate']);
}

$UserAccessManager->addActionsAndFilters();

//Add the cli interface to the known commands
if (defined('WP_CLI') && WP_CLI) {
    $CliWrapper = new \UserAccessManager\Wrapper\WordpressCli();

    $GroupCommand = new \UserAccessManager\Command\GroupCommand($CliWrapper, $AccessHandler, $UserGroupFactory);
    WP_CLI::add_command('uam groups', $GroupCommand);

    $ObjectCommand = new \UserAccessManager\Command\ObjectCommand($CliWrapper, $AccessHandler);
    WP_CLI::add_command('uam objects', $ObjectCommand);
}
