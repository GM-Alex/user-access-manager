<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Wrapper\Wordpress;

class UserGroupFactory
{
    public function __construct(
        private Wordpress $wordpress,
        private Database $database,
        private MainConfig $config,
        private ObjectHandler $objectHandler,
        private AssignedObjectsLoader $assignedObjectsLoader
    ) {
    }

    /**
     * @throws UserGroupTypeException
     */
    public function createUserGroup(int|string|null $id = null): UserGroup
    {
        return new UserGroup(
            $this->wordpress,
            $this->database,
            $this->config,
            $this->objectHandler,
            $this->assignedObjectsLoader,
            $id
        );
    }

    /**
     * @throws UserGroupTypeException
     */
    public function createUserGroupFromDatabaseRow(object $databaseUserGroup): UserGroup
    {
        $userGroup = $this->createUserGroup();
        $userGroup->assignDatabaseValues($databaseUserGroup);

        return $userGroup;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function createDynamicUserGroup(string $type, int|string $id): DynamicUserGroup
    {
        return new DynamicUserGroup(
            $this->wordpress,
            $this->database,
            $this->config,
            $this->objectHandler,
            $this->assignedObjectsLoader,
            $type,
            $id
        );
    }
}
