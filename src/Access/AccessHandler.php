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
    private ?array $noneHiddenPostTypes = null;

    public function __construct(
        private Wordpress $wordpress,
        private MainConfig $mainConfig,
        private Database $database,
        private ObjectHandler $objectHandler,
        private UserHandler $userHandler,
        private UserGroupHandler $userGroupHandler
    ) {
    }

    private function hasAuthorAccess(string $objectType, int|string $objectId): bool
    {
        if ($this->mainConfig->authorsHasAccessToOwn() === true
            && $this->objectHandler->isPostType($objectType)
        ) {
            $post = $this->objectHandler->getPost($objectId);
            return $post !== false
                && $this->wordpress->getCurrentUser()->ID === (int) $post->post_author;
        }

        return false;
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
                function (AbstractUserGroup $userGroup) {
                    return $userGroup->getWriteAccess() !== 'none';
                }
            );
        }

        return $this->wordpress->applyFilters('uam_get_user_user_groups_for_object_access', $userUserGroups, $isAdmin);
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function checkObjectAccess(?string $objectType, int|string|null $objectId, ?bool $isAdmin = null): bool
    {
        $isAdmin = $this->isAdmin($isAdmin);

        if (isset($this->objectAccess[$isAdmin][$objectType][$objectId]) === false) {
            if ($this->objectHandler->isValidObjectType($objectType) === false
                || $this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === true
                || $this->hasAuthorAccess($objectType, $objectId) === true
            ) {
                $access = true;
            } else {
                $membership = $this->userGroupHandler->getUserGroupsForObject($objectType, $objectId);
                $access = $membership === []
                    || array_intersect_key($membership, $this->getUserUserGroupsForObjectAccess($isAdmin)) !== [];

                if ($access && $this->wordpress->isUserLoggedIn() && $this->wordpress->isMultiSite()) {
                    $access = $this->wordpress->isUserMemberOfBlog();
                }
            }

            $this->objectAccess[$isAdmin][$objectType][$objectId] = $access;
        }

        return $this->objectAccess[$isAdmin][$objectType][$objectId];
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    private function getExcludedObjects(string $type, array $filterTypesMap = []): array
    {
        $excludedObjects = [];
        $userGroups = $this->userGroupHandler->getFullUserGroups();

        foreach ($userGroups as $userGroup) {
            $excludedObjects += $userGroup->getAssignedObjectsByType($type);
        }

        $userUserGroups = $this->userGroupHandler->getUserGroupsForUser();

        foreach ($userUserGroups as $userGroup) {
            $excludedObjects = array_diff_key($excludedObjects, $userGroup->getAssignedObjectsByType($type));
        }

        if ($filterTypesMap !== []) {
            $excludedObjects = array_filter(
                $excludedObjects,
                function ($element) use ($filterTypesMap) {
                    return isset($filterTypesMap[$element]) === false;
                }
            );
        }

        $objectIds = array_keys($excludedObjects);
        return array_combine($objectIds, $objectIds);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getExcludedTerms(): ?array
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY)) {
            $this->excludedTerms = [];
        }

        if ($this->excludedTerms === null) {
            $this->excludedTerms = $this->getExcludedObjects(ObjectHandler::GENERAL_TERM_OBJECT_TYPE);
        }

        return $this->excludedTerms;
    }

    private function getNoneHiddenPostTypes(): ?array
    {
        if ($this->noneHiddenPostTypes === null) {
            $this->noneHiddenPostTypes = [];

            if ($this->wordpress->isAdmin() === false) {
                $postTypes = $this->objectHandler->getPostTypes();

                foreach ($postTypes as $postType) {
                    if ($this->mainConfig->hidePostType($postType) === false) {
                        $this->noneHiddenPostTypes[$postType] = $postType;
                    }
                }
            }
        }

        return $this->noneHiddenPostTypes;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getExcludedPosts(): ?array
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY)) {
            $this->excludedPosts = [];
        }

        if ($this->excludedPosts === null) {
            $noneHiddenPostTypes = $this->getNoneHiddenPostTypes();
            $excludedPosts = $this->getExcludedObjects(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $noneHiddenPostTypes);

            if ($this->mainConfig->authorsHasAccessToOwn() === true) {
                $query = $this->database->prepare(
                    "SELECT ID FROM {$this->database->getPostsTable()}
                    WHERE post_author = %d",
                    $this->wordpress->getCurrentUser()->ID
                );

                $ownPosts = array_filter(
                    (array) $this->database->getResults($query),
                    function ($ownPost) {
                        return isset($ownPost->ID);
                    }
                );
                $ownPostIds = [];

                foreach ($ownPosts as $ownPost) {
                    $ownPostIds[$ownPost->ID] = $ownPost->ID;
                }

                $excludedPosts = array_diff_key($excludedPosts, $ownPostIds);
            }

            $this->excludedPosts = $excludedPosts;
        }

        return $this->excludedPosts;
    }
}
