<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

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
            MODIFY ID INT NOT NULL AUTO_INCREMENT";

        return $this->database->query($alterQuery) !== false;
    }
}
