<?php
if (function_exists('__') === false) {
    function __($sString, $sDomain = 'default')
    {
        return $sString;
    }
}

define('OBJECT', 'OBJECT');
define('ABSPATH', 'ABSPATH');

require_once __DIR__.'/unit/UserAccessManagerTestCase.php';
require_once __DIR__.'/../includes/language.php';
require_once __DIR__.'/../autoload.php';
require_once __DIR__.'/../vendor/autoload.php';