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
namespace UserAccessManager\AccessHandler;

use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Util\Util;
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
     * @var Config
     */
    private $config;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

    /**
     * @var null|array
     */
    private $userGroups = null;

    /**
     * @var null|array
     */
    private $filteredUserGroups = null;

    /**
     * @var null|array
     */
    private $userGroupsForUser = null;

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
    private $objectUserGroups = [];

    /**
     * @var array
     */
    private $objectAccess = [];

    /**
     * The constructor
     *
     * @param Wordpress        $wordpress
     * @param Config           $config
     * @param Cache            $cache
     * @param Database         $database
     * @param ObjectHandler    $objectHandler
     * @param Util             $util
     * @param UserGroupFactory $userGroupFactory
     */
    public function __construct(
        Wordpress $wordpress,
        Config $config,
        Cache $cache,
        Database $database,
        ObjectHandler $objectHandler,
        Util $util,
        UserGroupFactory $userGroupFactory
    ) {
        $this->wordpress = $wordpress;
        $this->config = $config;
        $this->cache = $cache;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->util = $util;
        $this->userGroupFactory = $userGroupFactory;
    }

    /**
     * Returns all user groups or one requested by the user group id.
     *
     * @return UserGroup[]
     */
    public function getUserGroups()
    {
        if ($this->userGroups === null) {
            $this->userGroups = [];

            $query = "SELECT ID FROM {$this->database->getUserGroupTable()}";
            $userGroupsDb = (array)$this->database->getResults($query);

            foreach ($userGroupsDb as $userGroupDb) {
                $this->userGroups[$userGroupDb->ID] = $this->userGroupFactory->createUserGroup($userGroupDb->ID);
            }
        }

        return $this->userGroups;
    }

    /**
     * Returns the user groups filtered by the user user groups.
     *
     * @return UserGroup[]
     */
    public function getFilteredUserGroups()
    {
        $userGroups = $this->getUserGroups();
        $userUserGroups = $this->getUserGroupsForUser();
        return array_intersect_key($userGroups, $userUserGroups);
    }

    /**
     * Adds a user group.
     *
     * @param UserGroup $userGroup The user group which we want to add.
     */
    public function addUserGroup(UserGroup $userGroup)
    {
        $this->getUserGroups();
        $this->userGroups[$userGroup->getId()] = $userGroup;
        $this->filteredUserGroups = null;
    }

    /**
     * Deletes a user group.
     *
     * @param integer $userGroupId The user group _iId which we want to delete.
     *
     * @return bool
     */
    public function deleteUserGroup($userGroupId)
    {
        $userGroups = $this->getUserGroups();

        if (isset($userGroups[$userGroupId])
            && $userGroups[$userGroupId]->delete() === true
        ) {
            unset($this->userGroups[$userGroupId]);
            $this->filteredUserGroups = null;

            return true;
        }

        return false;
    }

    /**
     * Returns the user groups for the given object.
     *
     * @param string  $objectType The object type.
     * @param integer $objectId   The _iId of the object.
     *
     * @return UserGroup[]
     */
    public function getUserGroupsForObject($objectType, $objectId)
    {
        if ($this->objectHandler->isValidObjectType($objectType) === false) {
            return [];
        } elseif (isset($this->objectUserGroups[$objectType]) === false) {
            $this->objectUserGroups[$objectType] = [];
        }

        if (isset($this->objectUserGroups[$objectType][$objectId]) === false) {
            $cacheKey = $this->cache->generateCacheKey(
                'getUserGroupsForObject',
                $objectType,
                $objectId
            );
            $objectUserGroups = $this->cache->getFromCache($cacheKey);

            if ($objectUserGroups !== null) {
                $this->objectUserGroups[$objectType][$objectId] = $objectUserGroups;
            } else {
                $objectUserGroups = [];
                $userGroups = $this->getUserGroups();

                foreach ($userGroups as $userGroup) {
                    if ($userGroup->isObjectMember($objectType, $objectId) === true) {
                        $objectUserGroups[$userGroup->getId()] = $userGroup;
                    }
                }

                $this->cache->addToCache($cacheKey, $objectUserGroups);
            }

            $this->objectUserGroups[$objectType][$objectId] = $objectUserGroups;
        }

        return $this->objectUserGroups[$objectType][$objectId];
    }

    /**
     * Unset the user groups for _aObjects.
     */
    public function unsetUserGroupsForObject()
    {
        $this->objectUserGroups = [];
    }

    /**
     * Converts the ip to an integer.
     *
     * @param string $ip
     *
     * @return int
     */
    private function calculateIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return base_convert(ip2long($ip), 10, 2);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return false;
        }

        $packedIp = inet_pton($ip);
        $bits = 15; // 16 x 8 bit = 128bit (ipv6)
        $binaryIp = '';

        while ($bits >= 0) {
            $binaryIp = sprintf('%08b', (ord($packedIp[$bits]))).$binaryIp;
            $bits--;
        }

        return $binaryIp;
    }

    /**
     * Checks if the given ip matches with the range.
     *
     * @param string $currentIp The ip of the current user.
     * @param array  $ipRanges  The ip ranges.
     *
     * @return bool
     */
    public function isIpInRange($currentIp, array $ipRanges)
    {
        $currentIp = $this->calculateIp($currentIp);

        if ($currentIp !== false) {
            foreach ($ipRanges as $ipRange) {
                $ipRange = explode('-', $ipRange);
                $rangeBegin = $ipRange[0];
                $rangeEnd = isset($ipRange[1]) ? $ipRange[1] : $ipRange[0];
                $rangeBegin = $this->calculateIp($rangeBegin);
                $rangeEnd = $this->calculateIp($rangeEnd);

                if ($rangeBegin !== false && $rangeEnd !== false
                    && $rangeBegin <= $currentIp && $currentIp <= $rangeEnd
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the user groups for the user.
     *
     * @return UserGroup[]
     */
    public function getUserGroupsForUser()
    {
        if ($this->checkUserAccess('manage_user_groups') === true) {
            return $this->getUserGroups();
        }

        if ($this->userGroupsForUser === null) {
            $currentUser = $this->wordpress->getCurrentUser();
            $userGroupsForUser = $this->getUserGroupsForObject(
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                $currentUser->ID
            );

            $userGroups = $this->getUserGroups();

            foreach ($userGroups as $userGroup) {
                if (isset($userGroupsForUser[$userGroup->getId()]) === false
                    && ($this->isIpInRange($_SERVER['REMOTE_ADDR'], $userGroup->getIpRangeArray())
                        || $this->config->atAdminPanel() === false && $userGroup->getReadAccess() === 'all'
                        || $this->config->atAdminPanel() === true && $userGroup->getWriteAccess() === 'all')
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
     *
     * @param string $objectType
     * @param int    $objectId
     *
     * @return UserGroup[]
     */
    public function getFilteredUserGroupsForObject($objectType, $objectId)
    {
        $userGroups = $this->getUserGroupsForObject($objectType, $objectId);
        $userUserGroups = $this->getUserGroupsForUser();
        return array_intersect_key($userGroups, $userUserGroups);
    }

    /**
     * Return the role of the user.
     *
     * @param \WP_User|false $user The user.
     *
     * @return array
     */
    private function getUserRole($user)
    {
        if ($user instanceof \WP_User && isset($user->{$this->database->getPrefix().'capabilities'}) === true) {
            $capabilities = (array)$user->{$this->database->getPrefix().'capabilities'};
        } else {
            $capabilities = [];
        }

        return (count($capabilities) > 0) ? array_keys($capabilities) : [UserGroup::NONE_ROLE];
    }

    /**
     * Checks the user access by user level.
     *
     * @param bool|string $allowedCapability If set check also for the capability.
     *
     * @return bool
     */
    public function checkUserAccess($allowedCapability = false)
    {
        $currentUser = $this->wordpress->getCurrentUser();

        if ($this->wordpress->isSuperAdmin($currentUser->ID) === true
            || $allowedCapability !== false && $currentUser->has_cap($allowedCapability) === true
        ) {
            return true;
        }

        $roles = $this->getUserRole($currentUser);
        $rolesMap = array_flip($roles);

        $orderedRoles = [UserGroup::NONE_ROLE, 'subscriber', 'contributor', 'author', 'editor', 'administrator'];
        $orderedRolesMap = array_flip($orderedRoles);

        $userRoles = array_intersect_key($orderedRolesMap, $rolesMap);
        $rightsLevel = (count($userRoles) > 0) ? end($userRoles) : -1;
        $fullAccessRole = $this->config->getFullAccessRole();

        return (isset($orderedRolesMap[$fullAccessRole]) === true && $rightsLevel >= $orderedRolesMap[$fullAccessRole]
            || isset($rolesMap['administrator']) === true
        );
    }

    /**
     * Checks if the user is an admin user
     *
     * @param integer $userId The user id.
     *
     * @return bool
     */
    public function userIsAdmin($userId)
    {
        $user = $this->objectHandler->getUser($userId);
        $roles = $this->getUserRole($user);
        $rolesMap = array_flip($roles);

        return (isset($rolesMap['administrator']) === true || $this->wordpress->isSuperAdmin($userId) === true);
    }

    /**
     * Checks if the current_user has access to the given post.
     *
     * @param string  $objectType The object type which should be checked.
     * @param integer $objectId   The id of the object.
     *
     * @return bool
     */
    public function checkObjectAccess($objectType, $objectId)
    {
        if ($this->objectHandler->isValidObjectType($objectType) === false) {
            return true;
        } elseif (isset($this->objectAccess[$objectType]) === false) {
            $this->objectAccess[$objectType] = [];
        }

        if (isset($this->objectAccess[$objectType][$objectId]) === false) {
            $access = false;
            $currentUser = $this->wordpress->getCurrentUser();

            if ($this->checkUserAccess('manage_user_groups') === true) {
                $access = true;
            } elseif ($this->config->authorsHasAccessToOwn() === true
                && $this->objectHandler->isPostType($objectType)
            ) {
                $post = $this->objectHandler->getPost($objectId);
                $access = ($post !== false && $currentUser->ID === (int)$post->post_author);
            }

            if ($access === false) {
                $membership = $this->getUserGroupsForObject($objectType, $objectId);

                if (count($membership) > 0) {
                    $userUserGroups = $this->getUserGroupsForUser();

                    foreach ($membership as $userGroupId => $userGroup) {
                        if (isset($userUserGroups[$userGroupId]) === true) {
                            $access = true;
                            break;
                        }
                    }
                } else {
                    $access = true;
                }
            }

            $this->objectAccess[$objectType][$objectId] = $access;
        }

        return $this->objectAccess[$objectType][$objectId];
    }

    /**
     * Returns the excluded terms for a user.
     *
     * @return array
     */
    public function getExcludedTerms()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->excludedTerms = [];
        }

        if ($this->excludedTerms === null) {
            $excludedTerms = [];
            $userGroups = $this->getUserGroups();

            $userUserGroups = $this->getUserGroupsForUser();

            foreach ($userGroups as $userGroup) {
                $excludedTerms += $userGroup->getFullTerms();
            }

            foreach ($userUserGroups as $userGroup) {
                $excludedTerms = array_diff_key($excludedTerms, $userGroup->getFullTerms());
            }

            $termIds = array_keys($excludedTerms);
            $this->excludedTerms = array_combine($termIds, $termIds);
        }

        return $this->excludedTerms;
    }

    /**
     * Returns the excluded posts.
     *
     * @return array
     */
    public function getExcludedPosts()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->excludedPosts = [];
        }

        if ($this->excludedPosts === null) {
            $excludedPosts = [];
            $userGroups = $this->getUserGroups();

            $userUserGroups = $this->getUserGroupsForUser();

            foreach ($userGroups as $userGroup) {
                $excludedPosts += $userGroup->getFullPosts();
            }

            foreach ($userUserGroups as $userGroup) {
                $excludedPosts = array_diff_key($excludedPosts, $userGroup->getFullPosts());
            }

            if ($this->config->authorsHasAccessToOwn() === true) {
                $query = $this->database->prepare(
                    "SELECT ID
                    FROM {$this->database->getPostsTable()}
                    WHERE post_author = %d",
                    $this->wordpress->getCurrentUser()->ID
                );

                $ownPosts = $this->database->getResults($query);
                $ownPostIds = [];

                foreach ($ownPosts as $ownPost) {
                    $ownPostIds[$ownPost->ID] = $ownPost->ID;
                }

                $excludedPosts = array_diff_key($excludedPosts, $ownPostIds);
            }

            if ($this->wordpress->isAdmin() === false) {
                $noneHiddenPostTypes = [];
                $postTypes = $this->objectHandler->getPostTypes();

                foreach ($postTypes as $postType) {
                    if ($this->config->hidePostType($postType) === false) {
                        $noneHiddenPostTypes[$postType] = $postType;
                    }
                }

                foreach ($excludedPosts as $postId => $type) {
                    if (isset($noneHiddenPostTypes[$type]) === true) {
                        unset($excludedPosts[$postId]);
                    }
                }
            }

            $postIds = array_keys($excludedPosts);
            $this->excludedPosts = array_combine($postIds, $postIds);
        }

        return $this->excludedPosts;
    }
}
