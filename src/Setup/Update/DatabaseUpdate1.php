<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Setup\Database\DatabaseUpdate;

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

    private function getObjectSelectQuery(
        string $objectType,
        string $userGroupToPost,
        string $userGroupToCategory,
        string $userGroupToUser,
        string $userGroupToRole
    ): ?string {
        $addition = '';

        if ($this->objectHandler->isPostType($objectType) === true) {
            $dbIdName = 'post_id';
            $database = $userGroupToPost . ', ' . $this->database->getPostsTable();
            $addition = " WHERE post_id = ID AND post_type = '$objectType'";
        } elseif ($objectType === 'category') {
            $dbIdName = 'category_id';
            $database = $userGroupToCategory;
        } elseif ($objectType === 'user') {
            $dbIdName = 'user_id';
            $database = $userGroupToUser;
        } elseif ($objectType === 'role') {
            $dbIdName = 'role_name';
            $database = $userGroupToRole;
        } else {
            return null;
        }

        return "SELECT $dbIdName AS id, group_id AS groupId FROM $database $addition";
    }

    private function updateToUserGroupToObjectTableUpdate(): bool
    {
        $prefix = $this->database->getPrefix();

        $charsetCollate = $this->database->getCharset();
        $userGroupToObject = $prefix . 'uam_accessgroup_to_object';
        $userGroupToPost = $prefix . 'uam_accessgroup_to_post';
        $userGroupToUser = $prefix . 'uam_accessgroup_to_user';
        $userGroupToCategory = $prefix . 'uam_accessgroup_to_category';
        $userGroupToRole = $prefix . 'uam_accessgroup_to_role';

        $alterQuery = "ALTER TABLE '$userGroupToObject'
            CHANGE 'object_id' 'object_id' VARCHAR(64) $charsetCollate";
        $success = $this->database->query($alterQuery) !== false;

        if ($success === false) {
            return false;
        }

        $objectTypes = $this->objectHandler->getObjectTypes();

        foreach ($objectTypes as $objectType) {
            $query = $this->getObjectSelectQuery(
                $objectType,
                $userGroupToPost,
                $userGroupToCategory,
                $userGroupToUser,
                $userGroupToRole
            );

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

        $dropQuery = "DROP TABLE $userGroupToPost,
            $userGroupToUser,
            $userGroupToCategory,
            $userGroupToRole";

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
