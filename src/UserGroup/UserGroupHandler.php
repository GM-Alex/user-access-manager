<?php
/**
 * UserGroupHandler.php
 *
 * The UserGroupHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\Wrapper\Wordpress;
use WP_User;

/**
 * Class UserGroupHandler
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupHandler
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var WordpressConfig
     */
    private $wordpressConfig;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var UserHandler
     */
    private $userHandler;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

    /**
     * @var null|UserGroup[]
     */
    private $userGroups = null;

    /**
     * @var null|DynamicUserGroup[]
     */
    private $dynamicUserGroups = null;

    /**
     * @var null|AbstractUserGroup[]
     */
    private $userGroupsForUser = null;

    /**
     * @var array
     */
    private $objectUserGroups = [];

    public function __construct(
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        Database $database,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        UserGroupFactory $userGroupFactory
    ) {
        $this->wordpress = $wordpress;
        $this->wordpressConfig = $wordpressConfig;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->userHandler = $userHandler;
        $this->userGroupFactory = $userGroupFactory;
    }

    /**
     * Returns all user groups.
     * @return UserGroup[]
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
     * Returns all dynamic user groups.
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
                WHERE group_type IN ('{$userGroupTypes}')
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
     * Returns the full user groups
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFullUserGroups(): ?array
    {
        return $this->getUserGroups() + $this->getDynamicUserGroups();
    }

    /**
     * Returns the user groups filtered by the user user groups.
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
     * Adds a user group.
     * @param UserGroup $userGroup
     * @throws UserGroupTypeException
     */
    public function addUserGroup(UserGroup $userGroup)
    {
        $this->getUserGroups();
        $this->userGroups[$userGroup->getId()] = $userGroup;
    }

    /**
     * Deletes a user group.
     * @param string|int $userGroupId
     * @return bool
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function deleteUserGroup($userGroupId): bool
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
     * Returns the user groups for the given object.
     * @param string $objectType The object type.
     * @param int|string $objectId The id of the object.
     * @param bool $ignoreDates If true we ignore the dates for the object assignment.
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function getUserGroupsForObject(string $objectType, $objectId, bool $ignoreDates = false): array
    {
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

    /**
     * Unset the object user groups.
     */
    public function unsetUserGroupsForObject()
    {
        $this->objectUserGroups = [];
    }

    /**
     * Checks if the current user is in the ip range or if the user group is public.
     * @param UserGroup $userGroup
     * @return bool
     */
    private function checkUserGroupAccess(UserGroup $userGroup): bool
    {
        $userIp = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

        return $this->userHandler->isIpInRange($userIp, $userGroup->getIpRangeArray())
            || $this->wordpressConfig->atAdminPanel() === false && $userGroup->getReadAccess() === 'all'
            || $this->wordpressConfig->atAdminPanel() === true && $userGroup->getWriteAccess() === 'all';
    }

    /**
     * Assigns the dynamic user groups to the user user groups.
     * @param WP_User $currentUser
     * @param array $userGroupsForUser
     * @throws UserGroupTypeException
     */
    private function assignDynamicUserGroupsForUser(WP_User $currentUser, array &$userGroupsForUser)
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
     * Returns the user groups for the user.
     * @return AbstractUserGroup[]
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
     * Returns the user groups for the object filtered by the user user groups.
     * @param string $objectType
     * @param string|int $objectId
     * @param bool $ignoreDates
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFilteredUserGroupsForObject(string $objectType, $objectId, bool $ignoreDates = false): array
    {
        $userGroups = $this->getUserGroupsForObject($objectType, $objectId, $ignoreDates);
        $userUserGroups = $this->getUserGroupsForUser() + $this->getDynamicUserGroups();
        return array_intersect_key($userGroups, $userUserGroups);
    }
}
