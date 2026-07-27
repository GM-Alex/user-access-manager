<?php

declare(strict_types=1);

namespace UserAccessManager\Tests;

use Exception;

use function in_array;
use function stream_get_wrappers;
use function stream_wrapper_register;
use function stream_wrapper_unregister;

/**
 * A stream wrapper that throws when a stream is opened for writing. It is used to cover the
 * defensive try/catch blocks around file_put_contents in the file protection handlers.
 */
class ThrowingStreamWrapper
{
    public const PROTOCOL = 'uam-throwing';

    /** @var resource|null */
    public $context;

    public static function register(): void
    {
        self::unregister();
        stream_wrapper_register(self::PROTOCOL, self::class);
    }

    public static function unregister(): void
    {
        if (in_array(self::PROTOCOL, stream_get_wrappers(), true) === true) {
            stream_wrapper_unregister(self::PROTOCOL);
        }
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- fixed stream wrapper API names

    /**
     * @throws Exception
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        throw new Exception('Unable to open stream for writing.');
    }

    public function url_stat(string $path, int $flags): bool
    {
        return false;
    }

    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
}
