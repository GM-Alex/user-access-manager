<?php

declare(strict_types=1);

namespace UserAccessManager\Wrapper;

use Exception;
use JetBrains\PhpStorm\NoReturn;

class Php
{
    /**
     * @see function_exists()
     */
    public function functionExists(string $functionName): bool
    {
        return function_exists($functionName);
    }

    /**
     * @see array_fill()
     */
    public function arrayFill(int $startIndex, int $numberOfElements, mixed $value): array
    {
        return array_fill($startIndex, $numberOfElements, $value);
    }

    /**
     * @see openssl_random_pseudo_bytes()
     */
    public function opensslRandomPseudoBytes(int $length, ?bool &$strong = false): bool|string
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
     * @see ini_get()
     */
    public function iniGet(string $variableName): int|string|false
    {
        return ini_get($variableName);
    }

    /**
     * @see set_time_limit()
     */
    public function setTimeLimit(int $seconds): bool
    {
        return @set_time_limit($seconds);
    }

    /**
     * @see fread()
     */
    public function fread($handle, int $length): false|string
    {
        return fread($handle, $length);
    }

    /**
     * @throws Exception
     */
    public function includeFile($controller, string $file): void
    {
        if ($controller === null) {
            throw new Exception('Controller is required');
        }

        include $file;
    }

    /**
     * @see exit()
     */
    #[NoReturn]
    public function callExit(): void
    {
        exit;
    }

    /**
     * @see file_put_contents()
     */
    public function filePutContents(string $filename, mixed $data, int $flags = 0, $context = null): false|int
    {
        return file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * @see igbinary_serialize()
     */
    public function igbinarySerialize(mixed $value): ?string
    {
        return igbinary_serialize($value);
    }

    /**
     * @see igbinary_unserialize()
     */
    public function igbinaryUnserialize(string $key): mixed
    {
        return igbinary_unserialize($key);
    }

    /**
     * @see mkdir()
     */
    public function mkdir(string $pathname, int $mode = 0777, bool $recursive = false, $context = null): bool
    {
        return ($context !== null)
            ? mkdir($pathname, $mode, $recursive, $context)
            : mkdir($pathname, $mode, $recursive);
    }

    /**
     * @see connection_status()
     */
    public function connectionStatus(): int
    {
        return connection_status();
    }

    /**
     * @see fclose()
     */
    public function fClose($handle): bool
    {
        return fclose($handle);
    }
}
