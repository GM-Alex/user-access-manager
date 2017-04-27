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
 * @version   SVN: $id$
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
     * @param string $functionName
     *
     * @return bool
     */
    public function functionExists($functionName)
    {
        return function_exists($functionName);
    }

    /**
     * @see array_fill()
     *
     * @param int   $startIndex
     * @param int   $numberOfElements
     * @param mixed $value
     *
     * @return array
     */
    public function arrayFill($startIndex, $numberOfElements, $value)
    {
        return array_fill($startIndex, $numberOfElements, $value);
    }

    /**
     * @see openssl_random_pseudo_bytes()
     *
     * @param $length
     * @param $strong
     *
     * @return string
     */
    public function opensslRandomPseudoBytes($length, &$strong)
    {
        return openssl_random_pseudo_bytes($length, $strong);
    }

    /**
     * @see unlink()
     *
     * @param string   $filename
     * @param resource $context
     *
     * @return bool
     */
    public function unlink($filename, $context = null)
    {
        return ($context !== null) ? unlink($filename, $context) : unlink($filename);
    }

    /**
     * @see ini_get()
     *
     * @param string $variableName
     *
     * @return string
     */
    public function iniGet($variableName)
    {
        return ini_get($variableName);
    }

    /**
     * @see set_time_limit()
     *
     * @param int $seconds
     *
     * @return bool
     */
    public function setTimeLimit($seconds)
    {
        return set_time_limit($seconds);
    }

    /**
     * @see fread()
     *
     * @param resource $handle
     * @param int      $length
     *
     * @return bool|string
     */
    public function fread($handle, $length)
    {
        return fread($handle, $length);
    }

    /**
     * @param Controller $controller
     * @param string     $file
     */
    public function includeFile(Controller &$controller, $file)
    {
        /** @noinspection PhpIncludeInspection */
        include $file;
    }

    /**
     * @see exit()
     */
    public function callExit()
    {
        exit;
    }
}
