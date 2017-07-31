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
    function wp_parse_args($args, $defaults = '')
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

if (class_exists('\WP_Widget') === false) {
    require_once __DIR__.'/../vendor/johnpbloch/wordpress-core/wp-includes/class-wp-widget.php';
}
