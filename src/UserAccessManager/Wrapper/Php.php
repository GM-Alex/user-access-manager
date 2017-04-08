<?php
/**
 * Php.php
 *
 * The Php class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Wrapper;

use UserAccessManager\Controller\Controller;

/**
 * Class Php
 *
 * @package UserAccessManager\Wrapper
 */
class Php
{
    /**
     * @see function_exists()
     *
     * @param string $sFunctionName
     *
     * @return bool
     */
    public function functionExists($sFunctionName)
    {
        return function_exists($sFunctionName);
    }

    /**
     * @see openssl_random_pseudo_bytes()
     *
     * @param $iLength
     * @param $blStrong
     *
     * @return string
     */
    public function opensslRandomPseudoBytes($iLength, &$blStrong)
    {
        return openssl_random_pseudo_bytes($iLength, $blStrong);
    }

    /**
     * @see unlink()
     *
     * @param string   $sFilename
     * @param resource $rContext
     *
     * @return bool
     */
    public function unlink($sFilename, $rContext = null)
    {
        return unlink($sFilename, $rContext);
    }

    /**
     * @see ini_get()
     *
     * @param string $sVariableName
     *
     * @return string
     */
    public function iniGet($sVariableName)
    {
        return ini_get($sVariableName);
    }

    /**
     * @see set_time_limit()
     *
     * @param int $iSeconds
     *
     * @return bool
     */
    public function setTimeLimit($iSeconds)
    {
        return set_time_limit($iSeconds);
    }

    /**
     * @param Controller $oController
     * @param string     $sFile
     */
    public function includeFile(Controller &$oController, $sFile)
    {
        /** @noinspection PhpIncludeInspection */
        include $sFile;
    }
}