<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;

abstract class DatabaseUpdate implements UpdateInterface
{
    public function __construct(
        protected Database $database,
        protected ObjectHandler $objectHandler
    ) {
    }
}
