<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Wrapper\Wordpress;

class DynamicUserGroup extends AbstractUserGroup
{
    public const USER_TYPE = 'user';
    public const ROLE_TYPE = 'role';
    public const NOT_LOGGED_IN_USER_ID = 0;

    /**
     * @throws UserGroupTypeException
     */
    public function __construct(
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        ObjectHandler $objectHandler,
        AssignedObjectsLoader $assignedObjectsLoader,
        protected ?string $type,
        int|string $id
    ) {
        parent::__construct($wordpress, $database, $config, $objectHandler, $assignedObjectsLoader, $id);

        if ($this->type !== self::USER_TYPE && $this->type !== self::ROLE_TYPE) {
            throw new UserGroupTypeException('Invalid dynamic group type.');
        }
    }

    public function getId(): string
    {
        return $this->type . '|' . $this->id;
    }

    public function getName(): string
    {
        return $this->name ??= ($this->type === self::ROLE_TYPE) ? $this->createRoleName() : $this->createUserName();
    }

    private function createRoleName(): string
    {
        $roles = $this->wordpress->getRoles()->roles;

        return TXT_UAM_ROLE . ': ' . ($roles[$this->id]['name'] ?? $this->id);
    }

    private function createUserName(): string
    {
        if ((int) $this->id === self::NOT_LOGGED_IN_USER_ID) {
            return TXT_UAM_ADD_DYNAMIC_NOT_LOGGED_IN_USERS;
        }

        $userData = $this->wordpress->getUserData($this->id);
        $userName = ($userData !== false) ? "$userData->display_name ($userData->user_login)" : '';

        return TXT_UAM_USER . ": $userName";
    }

    /**
     * @throws UserGroupAssignmentException
     * @throws Exception
     */
    public function addObject(string $objectType, int|string|null $objectId, $fromDate = null, $toDate = null): bool
    {
        if ($this->objectHandler->getGeneralObjectType($objectType) === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            throw new UserGroupAssignmentException('Dynamic user groups can\'t be assigned to user.');
        }

        return parent::addObject($objectType, $objectId, $fromDate, $toDate);
    }
}
