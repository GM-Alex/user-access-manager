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
