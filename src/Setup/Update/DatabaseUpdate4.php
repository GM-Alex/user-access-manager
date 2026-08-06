<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Object\ObjectHandler;

class DatabaseUpdate4 extends DatabaseUpdate
{
    public function getVersion(): string
    {
        return '1.4.1';
    }

    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $alterQuery = "ALTER TABLE {$dbAccessGroupToObject}
            ADD general_object_type VARCHAR(64) NOT NULL AFTER object_id";

        $success = $this->database->query($alterQuery) !== false;

        $generalTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $objectTypeConditions = [
            ObjectHandler::GENERAL_POST_OBJECT_TYPE => "object_type IN ('post', 'page', 'attachment')",
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => "object_type = 'role'",
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => "object_type = 'user'",
            $generalTermType => "object_type = 'term'"
        ];

        foreach ($objectTypeConditions as $generalObjectType => $condition) {
            $query = "UPDATE {$dbAccessGroupToObject}
                SET general_object_type = '$generalObjectType'
                WHERE $condition";

            $success = $success && $this->database->query($query) !== false;
        }

        $query = "UPDATE $dbAccessGroupToObject AS gto
            LEFT JOIN {$this->database->getTermTaxonomyTable()} AS tt 
              ON gto.object_id = tt.term_id
            SET gto.object_type = tt.taxonomy
            WHERE gto.general_object_type = '$generalTermType'";

        return $success && $this->database->query($query) !== false;
    }
}
