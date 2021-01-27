<?php
if (function_exists('__') === false) {
    function __($string, $domain = 'default'): string
    {
        return $string.'|'.$domain;
    }
}

if (function_exists('wp_parse_args') === false) {
    function wp_parse_str($string, &$array)
    {
        parse_str($string, $array);

        if (get_magic_quotes_gpc()) {
            $array = stripslashes_deep($array);
        }
    }
}

if (function_exists('wp_parse_args') === false) {
    function wp_parse_args($args, $defaults = ''): array
    {
        if (is_object($args) === true) {
            $r = get_object_vars($args);
        } elseif (is_array($args) === true) {
            $r =& $args;
        } else {
            wp_parse_str($args, $r);
        }

        return (is_array($defaults) === true) ? array_merge((array)$defaults, $r) : $r;
    }
}

define('OBJECT', 'OBJECT');
define('ABSPATH', 'ABSPATH');
define('ARRAY_A', 'ARRAY_A');
