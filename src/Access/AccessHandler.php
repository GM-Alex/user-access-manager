<?php
/**
 * AccessHandler.php
 *
 * The AccessHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Access;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AccessHandler
 *
 * @package UserAccessManager\AccessHandler
 */
class AccessHandler
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var MainConfig
     */
    private $mainConfig;

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
     * @var UserGroupHandler
     */
    private $userGroupHandler;

    /**
     * @var null|array
     */
    private $excludedTerms = null;

    /**
     * @var null|array
     */
    private $excludedPosts = null;

    /**
     * @var array
     */
    private $objectAccess = [];

    /**
     * @var null|array
     */
    private $noneHiddenPostTypes = null;

    /**
     * AccessHandler constructor.
     *
     * @param Wordpress        $wordpress
     * @param MainConfig       $mainConfig
     * @param Database         $database
     * @param ObjectHandler    $objectHandler
     * @param UserHandler      $userHandler
     * @param UserGroupHandler $userGroupHandler
     */
    public function __construct(
        Wordpress $wordpress,
        MainConfig $mainConfig,
        Database $database,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler
    ) {
        $this->wordpress = $wordpress;
        $this->mainConfig = $mainConfig;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->userHandler = $userHandler;
        $this->userGroupHandler = $userGroupHandler;
    }

    /**
     * Checks it the user has access because he is the author.
     *
     * @param string $objectType
     * @param string $objectId
     *
     * @return bool
     */
    private function hasAuthorAccess($objectType, $objectId)
    {
        if ($this->mainConfig->authorsHasAccessToOwn() === true
            && $this->objectHandler->isPostType($objectType)
        ) {
            $currentUser = $this->wordpress->getCurrentUser();
            $post = $this->objectHandler->getPost($objectId);
            return ($post !== false && $currentUser->ID === (int)$post->post_author);
        }

        return false;
    }

    /**
     * Checks if the is admin value is set if not grabs it from the wordpress function.
     *
     * @param null|bool $isAdmin
     *
     * @return bool
     */
    private function isAdmin($isAdmin)
    {
        return ($isAdmin === null) ? $this->wordpress->isAdmin() : $isAdmin;
    }

    /**
     * Returns the user user groups filtered by the write access.
     *
     * @param null|bool $isAdmin If set we force the admin mode.
     *
     * @return AbstractUserGroup[]
     */
    private function getUserUserGroupsForObjectAccess($isAdmin = null)
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
     * Checks if the current_user has access to the given post.
     *
     * @param string    $objectType The object type which should be checked.
     * @param int       $objectId   The id of the object.
     * @param null|bool $isAdmin    If set we force the admin mode.
     *
     * @return bool
     */
    public function checkObjectAccess($objectType, $objectId, $isAdmin = null)
    {
        $isAdmin = $this->isAdmin($isAdmin);
        $admin = $isAdmin === true ? 'admin' : 'noAdmin';

        if (isset($this->objectAccess[$admin][$objectType][$objectId]) === false) {
            if ($this->objectHandler->isValidObjectType($objectType) === false
                || $this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === true
                || $this->hasAuthorAccess($objectType, $objectId) === true
            ) {
                $access = true;
            } else {
                $membership = $this->userGroupHandler->getUserGroupsForObject($objectType, $objectId);
                $access = $membership === []
                    || array_intersect_key($membership, $this->getUserUserGroupsForObjectAccess($isAdmin)) !== [];
            }

            $this->objectAccess[$admin][$objectType][$objectId] = $access;
        }

        return $this->objectAccess[$admin][$objectType][$objectId];
    }

    /**
     * Returns the excluded objects.
     *
     * @param string $type
     * @param array  $filterTypesMap
     *
     * @return array
     */
    private function getExcludedObjects($type, array $filterTypesMap = [])
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
     * Returns the excluded terms for a user.
     *
     * @return array
     */
    public function getExcludedTerms()
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY)) {
            $this->excludedTerms = [];
        }

        if ($this->excludedTerms === null) {
            $this->excludedTerms = $this->getExcludedObjects(ObjectHandler::GENERAL_TERM_OBJECT_TYPE);
        }

        return $this->excludedTerms;
    }

    /**
     * Returns the none hidden post types map.
     *
     * @return array
     */
    private function getNoneHiddenPostTypes()
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
     * Returns the excluded posts.
     *
     * @return array
     */
    public function getExcludedPosts()
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY)) {
            $this->excludedPosts = [];
        }

        if ($this->excludedPosts === null) {
            $noneHiddenPostTypes = $this->getNoneHiddenPostTypes();
            $excludedPosts = $this->getExcludedObjects(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $noneHiddenPostTypes);

            if ($this->mainConfig->authorsHasAccessToOwn() === true) {
                $query = $this->database->prepare(
                    "SELECT ID
                    FROM {$this->database->getPostsTable()}
                    WHERE post_author = %d",
                    $this->wordpress->getCurrentUser()->ID
                );

                $ownPosts = (array)$this->database->getResults($query);
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
