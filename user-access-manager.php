<?php
/**
 * Plugin Name: User Access Manager
 * Plugin URI: https://wordpress.org/plugins/user-access-manager/
 * Author URI: https://twitter.com/GM_Alex
 * Version: 2.0.13
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

$basePath = __DIR__.DIRECTORY_SEPARATOR;
//Load language
require_once $basePath.'includes'.DIRECTORY_SEPARATOR.'language.php';
$locale = apply_filters('plugin_locale', get_locale(), 'user-access-manager');
load_textdomain('user-access-manager', WP_LANG_DIR.'/user-access-manager/user-access-manager-'.$locale.'.mo');
load_plugin_textdomain('user-access-manager', false, plugin_basename(dirname(__FILE__)).'/languages');

//--- Check requirements ---

//Check php version
if (version_compare(phpversion(), '5.4') === -1) {
    add_action(
        'admin_notices',
        function () {
            echo '<div id="message" class="error"><p><strong>'
                .sprintf(TXT_UAM_PHP_VERSION_TO_LOW, phpversion())
                .'</strong></p></div>';
        }
    );

    return;
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

    return;
}

//Defines
require_once $basePath.'autoload.php';
require_once $basePath.'init.php';

global $userAccessManager;
$userAccessManager = initUserAccessManger();
