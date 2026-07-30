<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class UserGroupFactory
{
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private Database $database,
        private MainConfig $config,
        private Util $util,
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
            $this->php,
            $this->wordpress,
            $this->database,
            $this->config,
            $this->util,
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
            $this->php,
            $this->wordpress,
            $this->database,
            $this->config,
            $this->util,
            $this->objectHandler,
            $this->assignedObjectsLoader,
            $type,
            $id
        );
    }
}
