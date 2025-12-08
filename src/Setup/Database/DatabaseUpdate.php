<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Database;

use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Setup\Update\UpdateInterface;

abstract class DatabaseUpdate implements UpdateInterface
{
    public function __construct(
        protected Database $database,
        protected ObjectHandler $objectHandler
    ) {}
}
