<?php

declare(strict_types=1);

namespace UserAccessManager\Access;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Wrapper\Wordpress;

class AccessHandler
{
    private ?array $excludedTerms = null;
    private ?array $excludedPosts = null;
    private array $objectAccess = [];
    private ?array $visiblePostTypes = null;

    public function __construct(
        private Wordpress $wordpress,
        private MainConfig $mainConfig,
        private Database $database,
        private ObjectHandler $objectHandler,
        private UserHandler $userHandler,
        private UserGroupHandler $userGroupHandler
    ) {
    }

    private function canManageUserGroups(): bool
    {
        return $this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === true;
    }

    private function hasAuthorAccess(string $objectType, int|string|null $objectId): bool
    {
        if ($this->mainConfig->authorsHasAccessToOwn() !== true
            || $this->objectHandler->isPostType($objectType) === false
        ) {
            return false;
        }

        $post = $this->objectHandler->getPost($objectId);

        if ($post === false) {
            return false;
        }

        $currentUserId = $this->wordpress->getCurrentUser()->ID;

        return $currentUserId !== 0 && $currentUserId === (int) $post->post_author;
    }

    private function isAdmin(?bool $isAdmin): bool
    {
        return ($isAdmin === null) ? $this->wordpress->isAdmin() : $isAdmin;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function getUserUserGroupsForObjectAccess(?bool $isAdmin = null): array
    {
        $userUserGroups = $this->userGroupHandler->getUserGroupsForUser();

        if ($this->isAdmin($isAdmin) === true) {
            $userUserGroups = array_filter(
                $userUserGroups,
                fn(AbstractUserGroup $userGroup) => $userGroup->getWriteAccess() !== 'none'
            );
        }

        return $this->wordpress->applyFilters('uam_get_user_user_groups_for_object_access', $userUserGroups, $isAdmin);
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    private function resolveObjectAccess(?string $objectType, int|string|null $objectId, bool $isAdmin): bool
    {
        if ($this->objectHandler->isValidObjectType($objectType) === false
            || $this->canManageUserGroups() === true
            || $this->hasAuthorAccess($objectType, $objectId) === true
        ) {
            return true;
        }

        $membership = $this->userGroupHandler->getUserGroupsForObject($objectType, $objectId);
        $access = $membership === []
            || array_intersect_key($membership, $this->getUserUserGroupsForObjectAccess($isAdmin)) !== [];

        if ($access === true && $this->wordpress->isUserLoggedIn() && $this->wordpress->isMultiSite()) {
            return $this->wordpress->isUserMemberOfBlog();
        }

        return $access;
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function checkObjectAccess(?string $objectType, int|string|null $objectId, ?bool $isAdmin = null): bool
    {
        $isAdmin = $this->isAdmin($isAdmin);

        if (isset($this->objectAccess[$isAdmin][$objectType][$objectId]) === false) {
            $this->objectAccess[$isAdmin][$objectType][$objectId] = $this->resolveObjectAccess(
                $objectType,
                $objectId,
                $isAdmin
            );
        }

        return $this->objectAccess[$isAdmin][$objectType][$objectId];
    }

    /**
     * $ignoredObjectTypes is matched against the assigned object type (the array value), not the object id.
     *
     * @throws UserGroupTypeException
     * @throws Exception
     */
    private function getExcludedObjects(string $type, array $ignoredObjectTypes = []): array
    {
        $excludedObjects = [];

        foreach ($this->userGroupHandler->getFullUserGroups() as $userGroup) {
            $excludedObjects += $userGroup->getAssignedObjectsByType($type);
        }

        foreach ($this->userGroupHandler->getUserGroupsForUser() as $userGroup) {
            $excludedObjects = array_diff_key($excludedObjects, $userGroup->getAssignedObjectsByType($type));
        }

        $excludedObjects = array_filter(
            $excludedObjects,
            fn($objectType) => isset($ignoredObjectTypes[$objectType]) === false
        );

        $objectIds = array_keys($excludedObjects);

        return array_combine($objectIds, $objectIds);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getExcludedTerms(): ?array
    {
        if ($this->canManageUserGroups() === true) {
            $this->excludedTerms = [];
        } elseif ($this->excludedTerms === null) {
            $this->excludedTerms = $this->getExcludedObjects(ObjectHandler::GENERAL_TERM_OBJECT_TYPE);
        }

        return $this->excludedTerms;
    }

    private function getVisiblePostTypes(): array
    {
        if ($this->visiblePostTypes !== null) {
            return $this->visiblePostTypes;
        }

        $this->visiblePostTypes = [];

        if ($this->wordpress->isAdmin() === false) {
            foreach ($this->objectHandler->getPostTypes() as $postType) {
                if ($this->mainConfig->hidePostType($postType) === false) {
                    $this->visiblePostTypes[$postType] = $postType;
                }
            }
        }

        return $this->visiblePostTypes;
    }

    private function getOwnPostIds(): array
    {
        $query = $this->database->prepare(
            "SELECT ID FROM {$this->database->getPostsTable()}
            WHERE post_author = %d",
            $this->wordpress->getCurrentUser()->ID
        );

        return array_column((array) $this->database->getResults($query), 'ID', 'ID');
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getExcludedPosts(): ?array
    {
        if ($this->canManageUserGroups() === true) {
            $this->excludedPosts = [];
        } elseif ($this->excludedPosts === null) {
            $excludedPosts = $this->getExcludedObjects(
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                $this->getVisiblePostTypes()
            );

            $this->excludedPosts = ($this->mainConfig->authorsHasAccessToOwn() === true)
                ? array_diff_key($excludedPosts, $this->getOwnPostIds())
                : $excludedPosts;
        }

        return $this->excludedPosts;
    }
}
