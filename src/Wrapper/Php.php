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

declare(strict_types=1);

namespace UserAccessManager\Wrapper;

use Exception;

/**
 * Class Php
 *
 * @package UserAccessManager\Wrapper
 */
class Php
{
    /**
     * @param string $functionName
     * @return bool
     * @see function_exists()
     */
    public function functionExists(string $functionName): bool
    {
        return function_exists($functionName);
    }

    /**
     * @param int $startIndex
     * @param int $numberOfElements
     * @param mixed $value
     * @return array
     * @see array_fill()
     */
    public function arrayFill(int $startIndex, int $numberOfElements, $value): array
    {
        return array_fill($startIndex, $numberOfElements, $value);
    }

    /**
     * @param int $length
     * @param null|bool $strong
     * @return false|string
     * @see openssl_random_pseudo_bytes()
     */
    public function opensslRandomPseudoBytes(int $length, ?bool &$strong = false)
    {
        return openssl_random_pseudo_bytes($length, $strong);
    }

    /**
     * @param string $filename
     * @param null $context
     * @return bool
     * @see unlink()
     */
    public function unlink(string $filename, $context = null): bool
    {
        return ($context !== null) ? unlink($filename, $context) : unlink($filename);
    }

    /**
     * @param string $variableName
     * @return int|string
     * @see ini_get()
     */
    public function iniGet(string $variableName)
    {
        return ini_get($variableName);
    }

    /**
     * @param int $seconds
     * @return bool
     * @see set_time_limit()
     */
    public function setTimeLimit(int $seconds): bool
    {
        return @set_time_limit($seconds);
    }

    /**
     * @param resource $handle
     * @param int $length
     * @return bool|string
     * @see fread()
     */
    public function fread($handle, int $length)
    {
        return fread($handle, $length);
    }

    /**
     * @param mixed $controller
     * @param string $file
     * @throws Exception
     */
    public function includeFile($controller, string $file)
    {
        if ($controller === null) {
            throw new Exception('Controller is required');
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
     * @param string $filename
     * @param mixed $data
     * @param int $flags
     * @param null $context
     * @return bool|int
     * @see file_put_contents()
     */
    public function filePutContents(string $filename, $data, $flags = 0, $context = null)
    {
        return file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * @param mixed $value
     * @return string
     * @see igbinary_serialize()
     */
    public function igbinarySerialize($value): string
    {
        return igbinary_serialize($value);
    }

    /**
     * @param string $key
     * @return mixed
     * @see igbinary_unserialize()
     */
    public function igbinaryUnserialize(string $key)
    {
        return igbinary_unserialize($key);
    }

    /**
     * @param string $pathname
     * @param int $mode
     * @param bool $recursive
     * @param null $context
     * @return bool
     * @see mkdir()
     */
    public function mkdir(string $pathname, $mode = 0777, $recursive = false, $context = null): bool
    {
        return ($context !== null) ?
            mkdir($pathname, $mode, $recursive, $context) : mkdir($pathname, $mode, $recursive);
    }

    /**
     * @return int
     * @see connection_status()
     */
    public function connectionStatus(): int
    {
        return connection_status();
    }

    /**
     * @param resource $handle
     * @return bool
     * @see fclose()
     */
    public function fClose($handle): bool
    {
        return fclose($handle);
    }
}
