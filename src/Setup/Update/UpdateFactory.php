<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;

class UpdateFactory
{
    public function __construct(
        protected Database $database,
        protected ObjectHandler $objectHandler
    ) {
    }

    /**
     * @return UpdateInterface[]
     */
    public function getDatabaseUpdates(): array
    {
        return [
            new DatabaseUpdate1($this->database, $this->objectHandler),
            new DatabaseUpdate2($this->database, $this->objectHandler),
            new DatabaseUpdate3($this->database, $this->objectHandler),
            new DatabaseUpdate4($this->database, $this->objectHandler),
            new DatabaseUpdate5($this->database, $this->objectHandler),
            new DatabaseUpdate6($this->database, $this->objectHandler)
        ];
    }
}
