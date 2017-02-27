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
$blStop = false;

//Check php version
$sPhpVersion = phpversion();

if (version_compare($sPhpVersion, '5.4') === -1) {
    add_action(
        'admin_notices',
        create_function(
            '',
            'echo \'<div id="message" class="error"><p><strong>'.
            sprintf(TXT_UAM_PHP_VERSION_TO_LOW, $sPhpVersion).
            '</strong></p></div>\';'
        )
    );

    $blStop = true;
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

    $blStop = true;
}

//If we have a error stop plugin.
if ($blStop === true) {
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
use UserAccessManager\Wrapper\Wordpress;

$oWrapper = new Wordpress();
$oUtil = new Util();
$oCache = new Cache();
$oConfigParameterFactory = new ConfigParameterFactory();
$oDatabase = new Database($oWrapper);
$oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
$oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, __FILE__);
$oUserGroupFactory = new UserGroupFactory(
    $oWrapper,
    $oDatabase,
    $oConfig,
    $oUtil,
    $oObjectHandler
);
$oAccessHandler = new AccessHandler(
    $oWrapper,
    $oConfig,
    $oCache,
    $oDatabase,
    $oObjectHandler,
    $oUtil,
    $oUserGroupFactory
);
$oFileProtectionFactory = new FileProtectionFactory(
    $oWrapper,
    $oConfig,
    $oUtil
);
$oFileHandler = new FileHandler(
    $oWrapper,
    $oConfig,
    $oFileProtectionFactory
);
$oSetupHandler = new SetupHandler(
    $oWrapper,
    $oDatabase,
    $oObjectHandler,
    $oFileHandler
);
$oControllerFactory = new ControllerFactory(
    $oWrapper,
    $oDatabase,
    $oConfig,
    $oUtil,
    $oCache,
    $oObjectHandler,
    $oAccessHandler,
    $oUserGroupFactory,
    $oFileHandler,
    $oSetupHandler
);
$oUserAccessManager = new UserAccessManager(
    $oWrapper,
    $oConfig,
    $oObjectHandler,
    $oAccessHandler,
    $oSetupHandler,
    $oControllerFactory
);

$oWrapper->doAction('uam_init', array(
    $oWrapper,
    $oUtil,
    $oCache,
    $oConfigParameterFactory,
    $oDatabase,
    $oObjectHandler,
    $oConfig,
    $oUserGroupFactory,
    $oAccessHandler,
    $oFileProtectionFactory,
    $oFileHandler,
    $oControllerFactory,
    $oUserAccessManager
));

//install
if (function_exists('register_activation_hook')) {
    register_activation_hook(__FILE__, array($oSetupHandler, 'install'));
}

if (!function_exists("userAccessManagerUninstall")) {
    function userAccessManagerUninstall()
    {
        $oWrapper = new Wordpress();
        $oUtil = new Util();
        $oConfigParameterFactory = new ConfigParameterFactory();
        $oDatabase = new Database($oWrapper);
        $oObjectHandler = new ObjectHandler($oWrapper, $oDatabase);
        $oConfig = new Config($oWrapper, $oObjectHandler, $oConfigParameterFactory, __FILE__);

        $oFileProtectionFactory = new FileProtectionFactory(
            $oWrapper,
            $oConfig,
            $oUtil
        );
        $oFileHandler = new FileHandler(
            $oWrapper,
            $oConfig,
            $oFileProtectionFactory
        );
        $oSetupHandler = new SetupHandler(
            $oWrapper,
            $oDatabase,
            $oObjectHandler,
            $oFileHandler
        );

        $oSetupHandler->uninstall();
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
    register_deactivation_hook(__FILE__, array($oSetupHandler, 'deactivate'));
}

$oUserAccessManager->addActionsAndFilters();

//Add the cli interface to the known commands
if (defined('WP_CLI') && WP_CLI) {
    $oCliWrapper = new \UserAccessManager\Wrapper\WordpressCli();

    $oGroupCommand = new \UserAccessManager\Command\GroupCommand($oCliWrapper, $oAccessHandler, $oUserGroupFactory);
    WP_CLI::add_command('uam groups', $oGroupCommand);

    $oObjectCommand = new \UserAccessManager\Command\ObjectCommand($oCliWrapper, $oAccessHandler);
    WP_CLI::add_command('uam objects', $oObjectCommand);
}
