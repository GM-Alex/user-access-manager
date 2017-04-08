<?php
if (function_exists('__') === false) {
    function __($sString, $sDomain = 'default')
    {
        return $sString.'|'.$sDomain;
    }
}

define('OBJECT', 'OBJECT');
define('ABSPATH', 'ABSPATH');
define('ARRAY_A', 'ARRAY_A');

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../autoload.php';
require_once __DIR__.'/UserAccessManager/UserAccessManagerTestCase.php';
require_once __DIR__.'/UserAccessManager/Controller/DummyController.php';
require_once __DIR__.'/../includes/language.php';

if (class_exists('WP_CLI_Command') === false) {
    require_once __DIR__.'/../vendor/wp-cli/wp-cli/php/class-wp-cli-command.php';
}
