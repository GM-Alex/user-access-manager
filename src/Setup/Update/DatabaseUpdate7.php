<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Setup\Database\DatabaseUpdate;

class DatabaseUpdate7 extends DatabaseUpdate
{
    public function getVersion(): string
    {
        return '1.6.2';
    }

    public function update(): bool
    {
        $userGroupTable = $this->database->getUserGroupTable();
        $alterQuery = "ALTER TABLE {$userGroupTable}
            MODIFY ID INT NOT NULL";

        return $this->database->query($alterQuery) !== false;
    }
}
