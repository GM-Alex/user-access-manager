<?php
/**
 * Util.php
 *
 * The Util class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Util;

/**
 * Class Util
 *
 * @package UserAccessManager\Util
 */
class Util
{
    /**
     * Checks if a string starts with the given needle.
     *
     * @param string $sHaystack The haystack.
     * @param string $sNeedle   The needle.
     *
     * @return boolean
     */
    public function startsWith($sHaystack, $sNeedle)
    {
        return $sNeedle === '' || strpos($sHaystack, $sNeedle) === 0;
    }

    /**
     * Checks if a string ends with the given needle.
     *
     * @param string $sHaystack
     * @param string $sNeedle
     *
     * @return bool
     */
    public function endsWith($sHaystack, $sNeedle)
    {
        return $sNeedle === '' || substr($sHaystack, -strlen($sNeedle)) === $sNeedle;
    }

    /**
     * Generates and returns a random password.
     *
     * @param int $iLength
     *
     * @return string
     */
    public function getRandomPassword($iLength = 32)
    {
        //create password
        $aChars = array();

        // Numbers
        for ($iCount = 48; $iCount < 58; $iCount++) {
            $aChars[] = chr($iCount);
        }

        // Small chars
        for ($iCount = 97; $iCount < 122; $iCount++) {
            $aChars[] = chr($iCount);
        }

        // Capitals
        for ($iCount = 65; $iCount < 90; $iCount++) {
            $aChars[] = chr($iCount);
        }

        mt_srand((double)microtime() * 1000000);
        $sPassword = '';

        for ($iCount = 1; $iCount <= $iLength; $iCount++) {
            $iRandomNumber = mt_rand(0, count($aChars) - 1);
            $sPassword .= $aChars[$iRandomNumber];
        }

        return $sPassword;
    }

    /**
     * Returns the current url.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            $sServerRequestUri = $_SERVER['PHP_SELF'];
        } else {
            $sServerRequestUri = $_SERVER['REQUEST_URI'];
        }

        $sSecure = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $aProtocols = explode('/', strtolower($_SERVER['SERVER_PROTOCOL']));
        $sProtocol = $aProtocols[0].$sSecure;
        $sPort = ((int)$_SERVER['SERVER_PORT'] === 80) ? '' : (':'.$_SERVER['SERVER_PORT']);

        return $sProtocol.'://'.$_SERVER['SERVER_NAME'].$sPort.$sServerRequestUri;
    }
}