<?php

declare(strict_types=1);

/**
 * Reads the plugin version from the plugin header, the only place where it is maintained.
 */

if (defined('UAM_VERSION') === false) {
    $pluginFile = fopen(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'user-access-manager.php', 'r');
    $pluginHeader = '';

    if ($pluginFile !== false) {
        $pluginHeader = (string) fread($pluginFile, 8192);
        fclose($pluginFile);
    }

    define(
        'UAM_VERSION',
        preg_match('/^[ \t\/*#@]*Version:(.*)$/mi', $pluginHeader, $versionMatches) === 1 ?
            trim($versionMatches[1]) : ''
    );

    unset($pluginFile, $pluginHeader, $versionMatches);
}
