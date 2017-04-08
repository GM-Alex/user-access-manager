<?php
require_once __DIR__.'/defines.php';
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../autoload.php';
require_once __DIR__.'/UserAccessManager/UserAccessManagerTestCase.php';
require_once __DIR__.'/UserAccessManager/Controller/DummyController.php';
require_once __DIR__.'/../includes/language.php';

if (class_exists('WP_CLI_Command') === false) {
    require_once __DIR__.'/../vendor/wp-cli/wp-cli/php/class-wp-cli-command.php';
}
