<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\Wrapper\Wordpress;
use WP_User;

class UserGroupHandler
{
    /** @var null|UserGroup[] */
    private ?array $userGroups = null;
    /** @var null|DynamicUserGroup[] */
    private ?array $dynamicUserGroups = null;
    /** @var null|AbstractUserGroup[] */
    private ?array $userGroupsForUser = null;
    /** @var AbstractUserGroup[] */
    private array $objectUserGroups = [];

    public function __construct(
        private Wordpress $wordpress,
        private WordpressConfig $wordpressConfig,
        private MainConfig $mainConfig,
        private Database $database,
        private ObjectHandler $objectHandler,
        private UserHandler $userHandler,
        private UserGroupFactory $userGroupFactory
    ) {
    }

    /**
     * @return null|UserGroup[]
     * @throws UserGroupTypeException
     */
    public function getUserGroups(): ?array
    {
        if ($this->userGroups === null) {
            $this->userGroups = [];

            $query = "SELECT ID FROM {$this->database->getUserGroupTable()}";
            $userGroups = (array) $this->database->getResults($query);

            foreach ($userGroups as $userGroup) {
                $group = $this->userGroupFactory->createUserGroup($userGroup->ID);
                $this->userGroups[$group->getId()] = $group;
            }
        }

        return $this->userGroups;
    }

    /**
     * @return null|DynamicUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getDynamicUserGroups(): ?array
    {
        if ($this->dynamicUserGroups === null) {
            $this->dynamicUserGroups = [];

            $notLoggedInUserGroup = $this->userGroupFactory->createDynamicUserGroup(
                DynamicUserGroup::USER_TYPE,
                DynamicUserGroup::NOT_LOGGED_IN_USER_ID
            );
            $this->dynamicUserGroups[$notLoggedInUserGroup->getId()] = $notLoggedInUserGroup;

            $userGroupTypes = implode('\', \'', [DynamicUserGroup::ROLE_TYPE, DynamicUserGroup::USER_TYPE]);

            $query = "SELECT group_id AS id, group_type AS type
                FROM {$this->database->getUserGroupToObjectTable()}
                WHERE group_type IN ('$userGroupTypes')
                  GROUP BY group_type, group_id";

            $dynamicUserGroups = (array) $this->database->getResults($query);

            foreach ($dynamicUserGroups as $dynamicUserGroup) {
                $group = $this->userGroupFactory->createDynamicUserGroup(
                    $dynamicUserGroup->type,
                    $dynamicUserGroup->id
                );

                $this->dynamicUserGroups[$group->getId()] = $group;
            }
        }

        return $this->dynamicUserGroups;
    }

    /**
     * @return null|AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFullUserGroups(): ?array
    {
        return $this->getUserGroups() + $this->getDynamicUserGroups();
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFilteredUserGroups(): array
    {
        $userGroups = $this->getFullUserGroups();
        $userUserGroups = $this->getUserGroupsForUser() + $this->getDynamicUserGroups();
        return array_intersect_key($userGroups, $userUserGroups);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function addUserGroup(UserGroup $userGroup): void
    {
        $this->getUserGroups();
        $this->userGroups[$userGroup->getId()] = $userGroup;
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function deleteUserGroup(int|string $userGroupId): bool
    {
        $userGroups = $this->getUserGroups();

        if (isset($userGroups[$userGroupId])
            && $userGroups[$userGroupId]->delete() === true
        ) {
            unset($this->userGroups[$userGroupId]);

            return true;
        }

        return false;
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function getUserGroupsForObject(
        string $objectType,
        int|string|null $objectId,
        bool $ignoreDates = false
    ): array {
        if ($this->objectHandler->isValidObjectType($objectType) === false) {
            return [];
        }

        if (isset($this->objectUserGroups[$ignoreDates][$objectType][$objectId]) === false) {
            $objectUserGroups = [];
            $userGroups = $this->getFullUserGroups();

            foreach ($userGroups as $userGroup) {
                $userGroup->setIgnoreDates($ignoreDates);

                if ($userGroup->isObjectMember($objectType, $objectId) === true) {
                    $objectUserGroups[$userGroup->getId()] = $userGroup;
                }
            }

            $this->objectUserGroups[$ignoreDates][$objectType][$objectId] = $objectUserGroups;
        }

        return $this->objectUserGroups[$ignoreDates][$objectType][$objectId];
    }

    public function unsetUserGroupsForObject(): void
    {
        $this->objectUserGroups = [];
    }

    private function checkUserGroupAccess(UserGroup $userGroup): bool
    {
        $extraIpHeader = $this->mainConfig->getExtraIpHeader();
        $userIp = $extraIpHeader !== null ?
            $_SERVER[$extraIpHeader] ?? ($_SERVER['REMOTE_ADDR'] ?? '') :
            $_SERVER['REMOTE_ADDR'] ?? '';

        return $this->userHandler->isIpInRange($userIp, $userGroup->getIpRangeArray())
            || $this->wordpressConfig->atAdminPanel() === false && $userGroup->getReadAccess() === 'all'
            || $this->wordpressConfig->atAdminPanel() === true && $userGroup->getWriteAccess() === 'all';
    }

    /**
     * @throws UserGroupTypeException
     */
    private function assignDynamicUserGroupsForUser(WP_User $currentUser, array &$userGroupsForUser): void
    {
        $userUserGroup = $this->userGroupFactory->createDynamicUserGroup(
            DynamicUserGroup::USER_TYPE,
            $currentUser->ID
        );
        $userGroupsForUser[$userUserGroup->getId()] = $userUserGroup;
        $roles = $this->userHandler->getUserRole($currentUser);

        foreach ($roles as $role) {
            $group = $this->userGroupFactory->createDynamicUserGroup(
                DynamicUserGroup::ROLE_TYPE,
                $role
            );

            $userGroupsForUser[$group->getId()] = $group;
        }
    }

    /**
     * @return AbstractUserGroup[]|null
     * @throws UserGroupTypeException
     */
    public function getUserGroupsForUser(): ?array
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === true) {
            return $this->getUserGroups();
        }

        if ($this->userGroupsForUser === null) {
            $currentUser = $this->wordpress->getCurrentUser();
            $userGroupsForUser = $this->getUserGroupsForObject(
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                $currentUser->ID
            );

            $this->assignDynamicUserGroupsForUser($currentUser, $userGroupsForUser);
            $userGroups = $this->getUserGroups();

            foreach ($userGroups as $userGroup) {
                if (isset($userGroupsForUser[$userGroup->getId()]) === false
                    && $this->checkUserGroupAccess($userGroup) === true
                ) {
                    $userGroupsForUser[$userGroup->getId()] = $userGroup;
                }
            }

            $this->userGroupsForUser = $userGroupsForUser;
        }

        return $this->userGroupsForUser;
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFilteredUserGroupsForObject(
        string $objectType,
        int|string|null $objectId,
        bool $ignoreDates = false
    ): array {
        $userGroups = $this->getUserGroupsForObject($objectType, $objectId, $ignoreDates);
        $userUserGroups = $this->getUserGroupsForUser() + $this->getDynamicUserGroups();
        return array_intersect_key($userGroups, $userUserGroups);
    }
}
