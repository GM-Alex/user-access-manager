<?php
if (function_exists('__') === false) {
    function __($string, $domain = 'default')
    {
        return $string.'|'.$domain;
    }
}

define('OBJECT', 'OBJECT');
define('ABSPATH', 'ABSPATH');
define('ARRAY_A', 'ARRAY_A');
