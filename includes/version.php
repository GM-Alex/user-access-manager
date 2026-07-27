<?php

declare(strict_types=1);

/**
 * Reads the plugin version from the plugin header, the only place where the version is maintained.
 *
 * Everything else derives from it: UserAccessManager::VERSION through this constant, the stable tag in the
 * readme through the grunt version task, and the deployed version through scripts/deploy.sh.
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
