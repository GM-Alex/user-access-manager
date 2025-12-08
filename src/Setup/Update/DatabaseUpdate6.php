<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Setup\Database\DatabaseUpdate;
use UserAccessManager\UserGroup\UserGroup;

class DatabaseUpdate6 extends DatabaseUpdate
{
    public function getVersion(): string
    {
        return '1.6.1';
    }

    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();

        $dbGroupTypeColumn = $this->database->getVariable(
            "SHOW COLUMNS FROM {$dbAccessGroupToObject}
            LIKE 'group_type'"
        );

        if ($dbGroupTypeColumn !== 'group_type') {
            $alterQuery = "ALTER TABLE {$dbAccessGroupToObject}
                ADD group_type VARCHAR(32) NOT NULL AFTER group_id,
                ADD from_date DATETIME NULL DEFAULT NULL,
                ADD to_date DATETIME NULL DEFAULT NULL,
                MODIFY group_id VARCHAR(32) NOT NULL,
                MODIFY object_id VARCHAR(32) NOT NULL,
                MODIFY object_type VARCHAR(32) NOT NULL,
                DROP PRIMARY KEY,
                ADD PRIMARY KEY (object_id, object_type, group_id, group_type)";
        } else {
            $alterQuery = "ALTER TABLE {$dbAccessGroupToObject}
                MODIFY group_type VARCHAR(32) NOT NULL,
                MODIFY group_id VARCHAR(32) NOT NULL,
                MODIFY object_id VARCHAR(32) NOT NULL,
                MODIFY object_type VARCHAR(32) NOT NULL";
        }

        $success = $this->database->query($alterQuery) !== false;

        $update = $this->database->update(
            $dbAccessGroupToObject,
            ['group_type' => UserGroup::USER_GROUP_TYPE],
            ['group_type' => '']
        );

        return $success && $update !== false;
    }
}
