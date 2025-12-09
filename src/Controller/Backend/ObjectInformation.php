<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\UserGroup\AbstractUserGroup;

class ObjectInformation
{
    protected ?string $objectType = null;
    protected int|string|null $objectId = null;
    protected int $userGroupDiff = 0;
    /**
     * @var AbstractUserGroup[]
     */
    protected array $objectUserGroups = [];

    public function setObjectType(string $objectType): ObjectInformation
    {
        $this->objectType = $objectType;
        return $this;
    }

    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    public function setObjectId(int|string|null $objectId): ObjectInformation
    {
        $this->objectId = $objectId;
        return $this;
    }

    public function getObjectId(): int|string|null
    {
        return $this->objectId;
    }

    public function setUserGroupDiff(int $userGroupDiff): ObjectInformation
    {
        $this->userGroupDiff = $userGroupDiff;
        return $this;
    }

    public function getUserGroupDiff(): int
    {
        return $this->userGroupDiff;
    }

    public function setObjectUserGroups($objectUserGroups): ObjectInformation
    {
        $this->objectUserGroups = $objectUserGroups;
        return $this;
    }

    /**
     * @return AbstractUserGroup[]
     */
    public function getObjectUserGroups(): array
    {
        return $this->objectUserGroups;
    }
}
