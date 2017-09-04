<?php

namespace UserAccessManager\Setup\Update;

class Update5 extends Update implements UpdateInterface
{
    /**
     * Returns the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.5.1';
    }

    /**
     * Executes the update.
     *
     * @return bool
     */
    public function update()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "SELECT object_id AS objectId, object_type AS objectType, group_id AS groupId
            FROM {$dbAccessGroupToObject}
            WHERE general_object_type = ''";

        $dbObjects = (array)$this->database->getResults($query);
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
