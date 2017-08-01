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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Util;

use UserAccessManager\Wrapper\Php;

/**
 * Class Util
 *
 * @package UserAccessManager\Util
 */
class Util
{
    /**
     * @var Php
     */
    private $php;

    /**
     * Util constructor.
     *
     * @param Php $php
     */
    public function __construct(Php $php)
    {
        $this->php = $php;
    }

    /**
     * Checks if a string starts with the given needle.
     *
     * @param string $haystack The haystack.
     * @param string $needle   The needle.
     *
     * @return bool
     */
    public function startsWith($haystack, $needle)
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }

    /**
     * Checks if a string ends with the given needle.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public function endsWith($haystack, $needle)
    {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Generates and returns a random password.
     *
     * @param int $length
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getRandomPassword($length = 32)
    {
        $bytes = $this->php->opensslRandomPseudoBytes($length + 1, $strong);

        if ($bytes !== false && $strong === true) {
            return substr(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode($bytes)), 0, $length);
        } else {
            throw new \Exception('Unable to generate secure token from OpenSSL.');
        }
    }

    /**
     * Returns the current url.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        if (isset($_SERVER['REQUEST_URI']) === false) {
            $serverRequestUri = $_SERVER['PHP_SELF'];
        } else {
            $serverRequestUri = $_SERVER['REQUEST_URI'];
        }

        $secure = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] === 'on') ? 's' : '';
        $protocols = explode('/', strtolower($_SERVER['SERVER_PROTOCOL']));
        $protocol = $protocols[0].$secure;
        $port = ((int)$_SERVER['SERVER_PORT'] === 80) ? '' : (':'.$_SERVER['SERVER_PORT']);

        return $protocol.'://'.$_SERVER['SERVER_NAME'].$port.$serverRequestUri;
    }
}
