<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

/**
 * Repeats DatabaseUpdate7, which was never registered and so never ran.
 */
class DatabaseUpdate8 extends DatabaseUpdate7
{
    public function getVersion(): string
    {
        return '1.6.3';
    }
}
