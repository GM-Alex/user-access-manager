<?php

use VCR\VCR;
use function WP_CLI\Utils\load_dependencies;

ini_set('memory_limit', '-1');
set_time_limit(0);

if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', __DIR__ . '/../vendor/autoload.php');
}

if (!defined('$_SESSION')) {
    global $_SESSION;
    $_SESSION = [];
}

require_once __DIR__.'/defines.php';
$vendorDir = __DIR__.'/../vendor';
require_once $vendorDir . '/autoload.php';
require_once __DIR__.'/../includes/language.php';

if (class_exists('\WP_Post_Type') === false) {
    class WP_Post_Type {}
}

if (class_exists('\wpdb') === false) {
    class wpdb {}
}

if (class_exists('\WP_Widget') === false) {
    require_once __DIR__.'/../vendor/johnpbloch/wordpress-core/wp-includes/class-wp-widget.php';
}

if (!defined('WP_CLI_ROOT')) {
    define('WP_CLI_ROOT', $vendorDir . '/wp-cli/wp-cli');
}

include WP_CLI_ROOT . '/php/utils.php';
include WP_CLI_ROOT . '/php/dispatcher.php';
include WP_CLI_ROOT . '/php/class-wp-cli.php';
include WP_CLI_ROOT . '/php/class-wp-cli-command.php';

load_dependencies();

VCR::configure()->setCassettePath(__DIR__.'/fixtures/vcr');
