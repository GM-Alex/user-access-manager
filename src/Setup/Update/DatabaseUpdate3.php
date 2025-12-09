<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Setup\Database\DatabaseUpdate;

class DatabaseUpdate3 extends DatabaseUpdate
{
    public function getVersion(): string
    {
        return '1.3';
    }

    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $generalTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $update = $this->database->update(
            $dbAccessGroupToObject,
            ['object_type' => $generalTermType],
            ['object_type' => 'category']
        );

        return $update !== false;
    }
}
