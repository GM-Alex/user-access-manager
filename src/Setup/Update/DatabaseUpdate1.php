<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

class DatabaseUpdate1 extends DatabaseUpdate
{
    public function getVersion(): string
    {
        return '1.0';
    }

    private function updateToUserGroupTableUpdate(string $userGroupTable): bool
    {
        $alterQuery = "ALTER TABLE {$userGroupTable}
            ADD read_access TINYTEXT NOT NULL DEFAULT '', 
            ADD write_access TINYTEXT NOT NULL DEFAULT '', 
            ADD ip_range MEDIUMTEXT NULL DEFAULT ''";

        $this->database->query($alterQuery);

        $updateQuery = "UPDATE $userGroupTable SET read_access = 'group', write_access = 'group'";
        $success = $this->database->query($updateQuery) !== false;

        $selectQuery = "SHOW columns FROM $userGroupTable LIKE 'ip_range'";
        $dbIpRange = (string) $this->database->getVariable($selectQuery);

        if ($dbIpRange !== 'ip_range') {
            $alterQuery = "ALTER TABLE $userGroupTable ADD ip_range MEDIUMTEXT NULL DEFAULT ''";
            $success = $this->database->query($alterQuery) !== false;
        }

        return $success;
    }

    /**
     * @param string[] $legacyTables Legacy per-object-type tables, keyed by object type.
     */
    private function getObjectSelectQuery(string $objectType, array $legacyTables): ?string
    {
        if ($this->objectHandler->isPostType($objectType) === true) {
            $source = $legacyTables['post'] . ', ' . $this->database->getPostsTable();

            return "SELECT post_id AS id, group_id AS groupId FROM $source"
                . " WHERE post_id = ID AND post_type = '$objectType'";
        }

        $idColumns = [
            'category' => 'category_id',
            'user' => 'user_id',
            'role' => 'role_name'
        ];

        if (isset($idColumns[$objectType]) === false) {
            return null;
        }

        return "SELECT {$idColumns[$objectType]} AS id, group_id AS groupId FROM {$legacyTables[$objectType]}";
    }

    private function updateToUserGroupToObjectTableUpdate(): bool
    {
        $prefix = $this->database->getPrefix();
        $charsetCollate = $this->database->getCharset();
        $userGroupToObject = $prefix . 'uam_accessgroup_to_object';
        $legacyTables = [
            'post' => $prefix . 'uam_accessgroup_to_post',
            'user' => $prefix . 'uam_accessgroup_to_user',
            'category' => $prefix . 'uam_accessgroup_to_category',
            'role' => $prefix . 'uam_accessgroup_to_role'
        ];

        $alterQuery = "ALTER TABLE '$userGroupToObject'
            CHANGE 'object_id' 'object_id' VARCHAR(64) $charsetCollate";
        $success = $this->database->query($alterQuery) !== false;

        if ($success === false) {
            return false;
        }

        foreach ($this->objectHandler->getObjectTypes() as $objectType) {
            $query = $this->getObjectSelectQuery($objectType, $legacyTables);

            if ($query === null) {
                continue;
            }

            $dbObjects = (array) $this->database->getResults($query);

            foreach ($dbObjects as $dbObject) {
                $insert = $this->database->insert(
                    $userGroupToObject,
                    [
                        'group_id' => $dbObject->groupId,
                        'object_id' => $dbObject->id,
                        'object_type' => $objectType
                    ],
                    [
                        '%d',
                        '%d',
                        '%s'
                    ]
                );
                $success = $success && $insert !== false;
            }
        }

        $dropQuery = 'DROP TABLE ' . implode(', ', $legacyTables);

        return $success && $this->database->query($dropQuery) !== false;
    }

    public function update(): bool
    {
        $success = true;
        $userGroupTable = $this->database->getUserGroupTable();
        $dbUserGroup = $this->database->getVariable("SHOW TABLES LIKE '$userGroupTable'");

        if ($dbUserGroup === $userGroupTable) {
            $success = $this->updateToUserGroupTableUpdate($userGroupTable);
        }

        return $success && $this->updateToUserGroupToObjectTableUpdate();
    }
}
