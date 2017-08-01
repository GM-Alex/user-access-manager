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

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\DynamicUserGroup;
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
    const MANAGE_USER_GROUPS_CAPABILITY = 'manage_user_groups';

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var MainConfig
     */
    private $config;

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
     * @var null|UserGroup[]
     */
    private $userGroups = null;

    /**
     * @var null|DynamicUserGroup[]
     */
    private $dynamicUserGroups = null;

    /**
     * @var null|UserGroup[]
     */
    private $filteredUserGroups = null;

    /**
     * @var null|UserGroup[]
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
     * @param MainConfig       $config
     * @param Database         $database
     * @param ObjectHandler    $objectHandler
     * @param Util             $util
     * @param UserGroupFactory $userGroupFactory
     */
    public function __construct(
        Wordpress $wordpress,
        MainConfig $config,
        Database $database,
        ObjectHandler $objectHandler,
        Util $util,
        UserGroupFactory $userGroupFactory
    ) {
        $this->wordpress = $wordpress;
        $this->config = $config;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->util = $util;
        $this->userGroupFactory = $userGroupFactory;
    }

    /**
     * Returns all user groups.
     *
     * @return UserGroup[]
     */
    public function getUserGroups()
    {
        if ($this->userGroups === null) {
            $this->userGroups = [];

            $query = "SELECT ID FROM {$this->database->getUserGroupTable()}";
            $userGroups = (array)$this->database->getResults($query);

            foreach ($userGroups as $userGroup) {
                $group = $this->userGroupFactory->createUserGroup($userGroup->ID);
                $this->userGroups[$group->getId()] = $group;
            }
        }

        return $this->userGroups;
    }

    /**
     * Returns all dynamic user groups.
     *
     * @return null|DynamicUserGroup[]
     */
    public function getDynamicUserGroups()
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

            $dynamicUserGroups = (array)$this->database->getResults($query);

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
     * @return AbstractUserGroup[]
     */
    public function getFullUserGroups()
    {
        return $this->getUserGroups() + $this->getDynamicUserGroups();
    }

    /**
     * Returns the user groups filtered by the user user groups.
     *
     * @return AbstractUserGroup[]
     */
    public function getFilteredUserGroups()
    {
        $userGroups = $this->getFullUserGroups();
        $userUserGroups = $this->getUserGroupsForUser() + $this->getDynamicUserGroups();
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
     * @param string  $objectType  The object type.
     * @param integer $objectId    The id of the object.
     * @param bool    $ignoreDates If true we ignore the dates for the object assignment.
     *
     * @return UserGroup[]
     */
    public function getUserGroupsForObject($objectType, $objectId, $ignoreDates = false)
    {
        if ($this->objectHandler->isValidObjectType($objectType) === false) {
            return [];
        } elseif (isset($this->objectUserGroups[$objectType]) === false) {
            $this->objectUserGroups[$objectType] = [];
        }

        if ($ignoreDates === true || isset($this->objectUserGroups[$objectType][$objectId]) === false) {
            $objectUserGroups = [];
            $userGroups = $this->getFullUserGroups();

            foreach ($userGroups as $userGroup) {
                $userGroup->setIgnoreDates($ignoreDates);

                if ($userGroup->isObjectMember($objectType, $objectId) === true) {
                    $objectUserGroups[$userGroup->getId()] = $userGroup;
                }
            }

            if ($ignoreDates === true) {
                return $objectUserGroups;
            }

            $this->objectUserGroups[$objectType][$objectId] = $objectUserGroups;
        }

        return $this->objectUserGroups[$objectType][$objectId];
    }

    /**
     * Unset the object user groups.
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
        if ($this->checkUserAccess(self::MANAGE_USER_GROUPS_CAPABILITY) === true) {
            return $this->getUserGroups();
        }

        if ($this->userGroupsForUser === null) {
            $currentUser = $this->wordpress->getCurrentUser();
            $userGroupsForUser = $this->getUserGroupsForObject(
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                $currentUser->ID
            );

            $userUserGroup = $this->userGroupFactory->createDynamicUserGroup(
                DynamicUserGroup::USER_TYPE,
                $currentUser->ID
            );
            $userGroupsForUser[$userUserGroup->getId()] = $userUserGroup;

            $roles = $this->getUserRole($currentUser);

            foreach ($roles as $role) {
                $group = $this->userGroupFactory->createDynamicUserGroup(
                    DynamicUserGroup::ROLE_TYPE,
                    $role
                );

                $userGroupsForUser[$group->getId()] = $group;
            }

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
     * @param bool   $ignoreDates
     *
     * @return AbstractUserGroup[]
     */
    public function getFilteredUserGroupsForObject($objectType, $objectId, $ignoreDates = false)
    {
        $userGroups = $this->getUserGroupsForObject($objectType, $objectId, $ignoreDates);
        $userUserGroups = $this->getUserGroupsForUser() + $this->getDynamicUserGroups();
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
        if (isset($this->objectAccess[$objectType]) === false) {
            $this->objectAccess[$objectType] = [];
        }

        if (isset($this->objectAccess[$objectType][$objectId]) === false) {
            $access = false;
            $currentUser = $this->wordpress->getCurrentUser();

            if ($this->objectHandler->isValidObjectType($objectType) === false) {
                $access = true;
            } else {
                if ($this->checkUserAccess(self::MANAGE_USER_GROUPS_CAPABILITY) === true) {
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
        if ($this->checkUserAccess(self::MANAGE_USER_GROUPS_CAPABILITY)) {
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
        if ($this->checkUserAccess(self::MANAGE_USER_GROUPS_CAPABILITY)) {
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
