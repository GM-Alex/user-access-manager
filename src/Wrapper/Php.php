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
     * @param int  $length
     * @param bool $strong
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
        return @set_time_limit($seconds);
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
     * @param mixed  $controller
     * @param string $file
     *
     * @throws \Exception
     */
    public function includeFile(&$controller, $file)
    {
        if ($controller === null) {
            throw new \Exception('Controller is required');
        }

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

    /**
     * @see file_put_contents()
     *
     * @param string $filename
     * @param mixed  $data
     * @param int    $flags
     * @param null   $context
     *
     * @return bool|int
     */
    public function filePutContents($filename, $data, $flags = 0, $context = null)
    {
        return file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * @see igbinary_serialize()
     *
     * @param mixed $value
     *
     * @return string
     */
    public function igbinarySerialize($value)
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        return igbinary_serialize($value);
    }

    /**
     * @see igbinary_unserialize()
     *
     * @param string $key
     *
     * @return mixed
     */
    public function igbinaryUnserialize($key)
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        return igbinary_unserialize($key);
    }

    /**
     * @see mkdir()
     *
     * @param string   $pathname
     * @param int      $mode
     * @param bool     $recursive
     * @param resource $context
     *
     * @return bool
     */
    public function mkdir($pathname, $mode = 0777, $recursive = false, $context = null)
    {
        return ($context !== null) ?
            mkdir($pathname, $mode, $recursive, $context) : mkdir($pathname, $mode, $recursive);
    }

    /**
     * @see connection_status()
     *
     * @return int
     */
    public function connectionStatus()
    {
        return connection_status();
    }

    /**
     * @see fclose()
     *
     * @param resource $handle
     *
     * @return bool
     */
    public function fClose($handle)
    {
        return fclose($handle);
    }
}
