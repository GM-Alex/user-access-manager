<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

/**
 * Repeats the update of the user group table id column.
 *
 * DatabaseUpdate7 was never registered, so installations that already carry its database version never ran it.
 */
class DatabaseUpdate8 extends DatabaseUpdate7
{
    public function getVersion(): string
    {
        return '1.6.3';
    }
}
