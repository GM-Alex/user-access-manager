<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use Exception;
use UserAccessManager\Setup\Database\DatabaseUpdate;

class DatabaseUpdate5 extends DatabaseUpdate
{
    public function getVersion(): string
    {
        return '1.5.1';
    }

    /**
     * @throws Exception
     */
    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "SELECT object_id AS objectId, object_type AS objectType, group_id AS groupId
            FROM {$dbAccessGroupToObject}
            WHERE general_object_type = ''";

        $dbObjects = (array) $this->database->getResults($query);
        $success = true;

        foreach ($dbObjects as $dbObject) {
            $update = $this->database->update(
                $dbAccessGroupToObject,
                ['general_object_type' => $this->objectHandler->getGeneralObjectType($dbObject->objectType)],
                [
                    'object_id' => $dbObject->objectId,
                    'group_id' => $dbObject->groupId,
                    'object_type' => $dbObject->objectType
                ]
            );
            $success = $success && ($update !== false);
        }

        return $success;
    }
}
