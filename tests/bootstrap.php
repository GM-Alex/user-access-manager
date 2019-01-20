<?php
if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', __DIR__ . '/../vendor/autoload.php');
}

require_once __DIR__.'/defines.php';
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../includes/language.php';

if (class_exists('WP_CLI_Command') === false) {
    require_once __DIR__.'/../vendor/wp-cli/wp-cli/php/class-wp-cli-command.php';
}

if (class_exists('\WP_Widget') === false) {
    require_once __DIR__.'/../vendor/johnpbloch/wordpress-core/wp-includes/class-wp-widget.php';
}

\VCR\VCR::configure()->setCassettePath(__DIR__.'/fixtures/vcr');
