<?php
spl_autoload_register(function ($className) {
    $className = ltrim($className, '\\');
    $lastNamespacePosition = strrpos($className, '\\');
    $fileName = dirname(__FILE__).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;

    if ($lastNamespacePosition !== false) {
        $sNamespace = substr($className, 0, $lastNamespacePosition);
        $className = substr($className, $lastNamespacePosition + 1);
        $fileName .= str_replace('\\', DIRECTORY_SEPARATOR, $sNamespace).DIRECTORY_SEPARATOR;
    }

    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className).'.php';

    if (file_exists($fileName)) {
        /** @noinspection PhpIncludeInspection */
        require_once $fileName;
    }
});
