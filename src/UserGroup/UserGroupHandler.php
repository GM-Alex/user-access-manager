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
     * @return UserGroup[]
     * @throws UserGroupTypeException
     */
    public function getUserGroups(): array
    {
        return $this->userGroups ??= $this->loadUserGroups();
    }

    /**
     * @return UserGroup[]
     * @throws UserGroupTypeException
     */
    private function loadUserGroups(): array
    {
        $query = "SELECT * FROM {$this->database->getUserGroupTable()}";
        $userGroups = [];

        foreach ((array) $this->database->getResults($query) as $databaseUserGroup) {
            $userGroup = $this->userGroupFactory->createUserGroupFromDatabaseRow($databaseUserGroup);
            $userGroups[$userGroup->getId()] = $userGroup;
        }

        return $userGroups;
    }

    /**
     * @return DynamicUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getDynamicUserGroups(): array
    {
        return $this->dynamicUserGroups ??= $this->loadDynamicUserGroups();
    }

    /**
     * @return DynamicUserGroup[]
     * @throws UserGroupTypeException
     */
    private function loadDynamicUserGroups(): array
    {
        $notLoggedInUserGroup = $this->userGroupFactory->createDynamicUserGroup(
            DynamicUserGroup::USER_TYPE,
            DynamicUserGroup::NOT_LOGGED_IN_USER_ID
        );
        $dynamicUserGroups = [$notLoggedInUserGroup->getId() => $notLoggedInUserGroup];

        $userGroupTypes = implode('\', \'', [DynamicUserGroup::ROLE_TYPE, DynamicUserGroup::USER_TYPE]);

        $query = "SELECT group_id AS id, group_type AS type
                FROM {$this->database->getUserGroupToObjectTable()}
                WHERE group_type IN ('$userGroupTypes')
                  GROUP BY group_type, group_id";

        foreach ((array) $this->database->getResults($query) as $databaseUserGroup) {
            $userGroup = $this->userGroupFactory->createDynamicUserGroup(
                $databaseUserGroup->type,
                $databaseUserGroup->id
            );

            $dynamicUserGroups[$userGroup->getId()] = $userGroup;
        }

        return $dynamicUserGroups;
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFullUserGroups(): array
    {
        return $this->getUserGroups() + $this->getDynamicUserGroups();
    }

    /**
     * Reduces the given user groups to those the current user is allowed to see.
     *
     * @param AbstractUserGroup[] $userGroups
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    private function filterByUserGroupsOfUser(array $userGroups): array
    {
        return array_intersect_key($userGroups, $this->getUserGroupsForUser() + $this->getDynamicUserGroups());
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFilteredUserGroups(): array
    {
        return $this->filterByUserGroupsOfUser($this->getFullUserGroups());
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

        if (isset($userGroups[$userGroupId]) === false || $userGroups[$userGroupId]->delete() === false) {
            return false;
        }

        unset($this->userGroups[$userGroupId]);

        return true;
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

            foreach ($this->getFullUserGroups() as $userGroup) {
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

    private function getUserIp(): string
    {
        $extraIpHeader = $this->mainConfig->getExtraIpHeader();

        return ($extraIpHeader !== null && isset($_SERVER[$extraIpHeader]) === true) ?
            (string) $_SERVER[$extraIpHeader] : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private function checkUserGroupAccess(UserGroup $userGroup): bool
    {
        return $this->userHandler->isIpInRange($this->getUserIp(), $userGroup->getIpRangeArray())
            || $this->wordpressConfig->atAdminPanel() === false && $userGroup->getReadAccess() === 'all'
            || $this->wordpressConfig->atAdminPanel() === true && $userGroup->getWriteAccess() === 'all';
    }

    /**
     * @param AbstractUserGroup[] $userGroupsForUser
     * @throws UserGroupTypeException
     */
    private function assignDynamicUserGroupsForUser(WP_User $currentUser, array &$userGroupsForUser): void
    {
        $userUserGroup = $this->userGroupFactory->createDynamicUserGroup(
            DynamicUserGroup::USER_TYPE,
            $currentUser->ID
        );
        $userGroupsForUser[$userUserGroup->getId()] = $userUserGroup;

        foreach ($this->userHandler->getUserRole($currentUser) as $role) {
            $roleUserGroup = $this->userGroupFactory->createDynamicUserGroup(DynamicUserGroup::ROLE_TYPE, $role);
            $userGroupsForUser[$roleUserGroup->getId()] = $roleUserGroup;
        }
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getUserGroupsForUser(): array
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === true) {
            return $this->getUserGroups();
        }

        return $this->userGroupsForUser ??= $this->loadUserGroupsForUser();
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    private function loadUserGroupsForUser(): array
    {
        $currentUser = $this->wordpress->getCurrentUser();
        $userGroupsForUser = $this->getUserGroupsForObject(
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            $currentUser->ID
        );

        $this->assignDynamicUserGroupsForUser($currentUser, $userGroupsForUser);

        foreach ($this->getUserGroups() as $userGroup) {
            if (isset($userGroupsForUser[$userGroup->getId()]) === false
                && $this->checkUserGroupAccess($userGroup) === true
            ) {
                $userGroupsForUser[$userGroup->getId()] = $userGroup;
            }
        }

        return $userGroupsForUser;
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
        return $this->filterByUserGroupsOfUser($this->getUserGroupsForObject($objectType, $objectId, $ignoreDates));
    }
}
