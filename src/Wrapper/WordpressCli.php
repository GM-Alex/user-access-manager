<?php

declare(strict_types=1);

namespace UserAccessManager\Wrapper;

use WP_CLI;
use WP_CLI\ExitException;
use WP_CLI\Formatter;
use WP_Error;

class WordpressCli
{
    /**
     * @see WP_CLI::success
     */
    public function success(string $message)
    {
        return WP_CLI::success($message);
    }

    /**
     * @throws ExitException
     */
    public function error(WP_Error|string $message, bool|int $exit = true)
    {
        return WP_CLI::error($message, $exit);
    }

    /**
     * @see WP_CLI::line
     */
    public function line(string $message = ''): void
    {
        WP_CLI::line($message);
    }

    /**
     * @see Formatter
     */
    public function createFormatter(
        array &$assocArguments,
        array $fields = null,
        bool|string $prefix = false
    ): Formatter {
        return new Formatter($assocArguments, $fields, $prefix);
    }
}
