<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Setup\Database\DatabaseUpdate;

class DatabaseUpdate2 extends DatabaseUpdate
{
    public function getVersion(): string
    {
        return '1.2';
    }

    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "ALTER TABLE `$dbAccessGroupToObject`
            CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
            CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

        return $this->database->query($query) !== false;
    }
}
