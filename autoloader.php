<?php
/**
 * The autoloader function.
 *
 * @param string $sClassName
 */
function autoload($sClassName)
{
    $sClassName = ltrim($sClassName, '\\');
    $iLastNamespacePosition = strrpos($sClassName, '\\');
    $sFileName = dirname(__FILE__).DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;

    if ($iLastNamespacePosition !== false) {
        $sNamespace = substr($sClassName, 0, $iLastNamespacePosition);
        $sClassName = substr($sClassName, $iLastNamespacePosition + 1);
        $sFileName .= str_replace('\\', DIRECTORY_SEPARATOR, $sNamespace).DIRECTORY_SEPARATOR;
    }

    $sFileName .= str_replace('_', DIRECTORY_SEPARATOR, $sClassName).'.php';

    if (file_exists($sFileName)) {
        /** @noinspection PhpIncludeInspection */
        require_once $sFileName;
    }
}

spl_autoload_register('autoload');