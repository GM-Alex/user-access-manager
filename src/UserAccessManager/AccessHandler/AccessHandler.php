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
 * @version   SVN: $Id$
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
    protected $oWordpress;

    /**
     * @var Config
     */
    protected $oConfig;

    /**
     * @var Cache
     */
    protected $oCache;

    /**
     * @var Database
     */
    protected $oDatabase;

    /**
     * @var ObjectHandler
     */
    protected $oObjectHandler;

    /**
     * @var Util
     */
    protected $oUtil;

    /**
     * @var UserGroupFactory
     */
    protected $oUserGroupFactory;

    /**
     * @var array
     */
    protected $aUserGroups = null;

    /**
     * @var array
     */
    protected $aFilteredUserGroups = null;

    protected $aUserGroupsForUser = null;
    protected $aTermsAssignedToUser = null;
    protected $aExcludedTerms = null;
    protected $aPostsAssignedToUser = null;
    protected $aExcludedPosts = null;
    protected $aObjectUserGroups = [];
    protected $aObjectAccess = [];

    /**
     * The constructor
     *
     * @param Wordpress        $oWordpress
     * @param Config           $oConfig
     * @param Cache            $oCache
     * @param Database         $oDatabase
     * @param ObjectHandler    $oObjectHandler
     * @param Util             $oUtil
     * @param UserGroupFactory $oUserGroupFactory
     */
    public function __construct(
        Wordpress $oWordpress,
        Config $oConfig,
        Cache $oCache,
        Database $oDatabase,
        ObjectHandler $oObjectHandler,
        Util $oUtil,
        UserGroupFactory $oUserGroupFactory
    ) {
        $this->oWordpress = $oWordpress;
        $this->oConfig = $oConfig;
        $this->oCache = $oCache;
        $this->oDatabase = $oDatabase;
        $this->oObjectHandler = $oObjectHandler;
        $this->oUtil = $oUtil;
        $this->oUserGroupFactory = $oUserGroupFactory;
    }

    /**
     * Returns all user groups or one requested by the user group id.
     *
     * @return UserGroup[]
     */
    public function getUserGroups()
    {
        if ($this->aUserGroups === null) {
            $this->aUserGroups = [];

            $sQuery = "SELECT ID FROM {$this->oDatabase->getUserGroupTable()}";
            $aUserGroupsDb = (array)$this->oDatabase->getResults($sQuery);

            foreach ($aUserGroupsDb as $aUserGroupDb) {
                $this->aUserGroups[$aUserGroupDb->ID] = $this->oUserGroupFactory->createUserGroup($aUserGroupDb->ID);
            }
        }

        return $this->aUserGroups;
    }

    /**
     * Returns the user groups filtered by the user user groups.
     *
     * @return UserGroup[]
     */
    public function getFilteredUserGroups()
    {
        $aUserGroups = $this->getUserGroups();
        $aUserUserGroups = $this->getUserGroupsForUser();
        return array_intersect_key($aUserGroups, $aUserUserGroups);
    }

    /**
     * Adds a user group.
     *
     * @param UserGroup $oUserGroup The user group which we want to add.
     */
    public function addUserGroup(UserGroup $oUserGroup)
    {
        $this->getUserGroups();
        $this->aUserGroups[$oUserGroup->getId()] = $oUserGroup;
        $this->aFilteredUserGroups = null;
    }

    /**
     * Deletes a user group.
     *
     * @param integer $iUserGroupId The user group _iId which we want to delete.
     *
     * @return bool
     */
    public function deleteUserGroup($iUserGroupId)
    {
        $aUserGroups = $this->getUserGroups();

        if (isset($aUserGroups[$iUserGroupId])
            && $aUserGroups[$iUserGroupId]->delete() === true
        ) {
            unset($this->aUserGroups[$iUserGroupId]);
            $this->aFilteredUserGroups = null;

            return true;
        }

        return false;
    }

    /**
     * Returns the user groups for the given object.
     *
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The _iId of the object.
     *
     * @return UserGroup[]
     */
    public function getUserGroupsForObject($sObjectType, $iObjectId)
    {
        if ($this->oObjectHandler->isValidObjectType($sObjectType) === false) {
            return [];
        } elseif (isset($this->aObjectUserGroups[$sObjectType]) === false) {
            $this->aObjectUserGroups[$sObjectType] = [];
        }

        if (isset($this->aObjectUserGroups[$sObjectType][$iObjectId]) === false) {
            $sCacheKey = $this->oCache->generateCacheKey(
                'getUserGroupsForObject',
                $sObjectType,
                $iObjectId
            );
            $aObjectUserGroups = $this->oCache->getFromCache($sCacheKey);

            if ($aObjectUserGroups !== null) {
                $this->aObjectUserGroups[$sObjectType][$iObjectId] = $aObjectUserGroups;
            } else {
                $aObjectUserGroups = [];
                $aUserGroups = $this->getUserGroups();

                foreach ($aUserGroups as $oUserGroup) {
                    if ($oUserGroup->isObjectMember($sObjectType, $iObjectId) === true) {
                        $aObjectUserGroups[$oUserGroup->getId()] = $oUserGroup;
                    }
                }

                $this->oCache->addToCache($sCacheKey, $aObjectUserGroups);
            }

            $this->aObjectUserGroups[$sObjectType][$iObjectId] = $aObjectUserGroups;
        }

        return $this->aObjectUserGroups[$sObjectType][$iObjectId];
    }

    /**
     * Unset the user groups for _aObjects.
     */
    public function unsetUserGroupsForObject()
    {
        $this->aObjectUserGroups = [];
    }

    /**
     * Converts the ip to an integer.
     *
     * @param array $aIp
     *
     * @return int
     */
    protected function calculateIp(array $aIp)
    {
        return ($aIp[0] << 24) + ($aIp[1] << 16) + ($aIp[2] << 8) + $aIp[3];
    }

    /**
     * Checks if the given ip matches with the range.
     *
     * @param string $sCurrentIp The ip of the current user.
     * @param array  $aIpRanges  The ip ranges.
     *
     * @return boolean
     */
    public function isIpInRange($sCurrentIp, array $aIpRanges)
    {
        $aCurrentIp = explode('.', $sCurrentIp);
        $iCurIp = $this->calculateIp($aCurrentIp);

        foreach ($aIpRanges as $sIpRange) {
            $aIpRange = explode('-', $sIpRange);
            $aRangeBegin = explode('.', $aIpRange[0]);
            $aRangeEnd = isset($aIpRange[1]) ? explode('.', $aIpRange[1]) : explode('.', $aIpRange[0]);

            if (count($aRangeBegin) === 4 && count($aRangeEnd) === 4) {
                $iRangeBegin = $this->calculateIp($aRangeBegin);
                $iRangeEnd = $this->calculateIp($aRangeEnd);

                if ($iRangeBegin <= $iCurIp && $iCurIp <= $iRangeEnd) {
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

        if ($this->aUserGroupsForUser === null) {
            $oCurrentUser = $this->oWordpress->getCurrentUser();
            $aUserGroupsForUser = $this->getUserGroupsForObject(
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                $oCurrentUser->ID
            );

            $aUserGroups = $this->getUserGroups();

            foreach ($aUserGroups as $oUserGroup) {
                if (isset($aUserGroupsForUser[$oUserGroup->getId()]) === false
                    && ($this->isIpInRange($_SERVER['REMOTE_ADDR'], $oUserGroup->getIpRange())
                        || $this->oConfig->atAdminPanel() === false && $oUserGroup->getReadAccess() === 'all'
                        || $this->oConfig->atAdminPanel() === true && $oUserGroup->getWriteAccess() === 'all')
                ) {
                    $aUserGroupsForUser[$oUserGroup->getId()] = $oUserGroup;
                }
            }

            $this->aUserGroupsForUser = $aUserGroupsForUser;
        }

        return $this->aUserGroupsForUser;
    }

    /**
     * Returns the user groups for the object filtered by the user user groups.
     *
     * @param string $sObjectType
     * @param int    $iObjectId
     *
     * @return UserGroup[]
     */
    public function getFilteredUserGroupsForObject($sObjectType, $iObjectId)
    {
        $aUserGroups = $this->getUserGroupsForObject($sObjectType, $iObjectId);
        $aUserUserGroups = $this->getUserGroupsForUser();
        return array_intersect_key($aUserGroups, $aUserUserGroups);
    }

    /**
     * Return the role of the user.
     *
     * @param \WP_User $oUser The user id.
     *
     * @return array
     */
    protected function getUserRole(\WP_User $oUser)
    {
        if (isset($oUser->{$this->oDatabase->getPrefix().'capabilities'})) {
            $aCapabilities = (array)$oUser->{$this->oDatabase->getPrefix().'capabilities'};
        } else {
            $aCapabilities = [];
        }

        return (count($aCapabilities) > 0) ? array_keys($aCapabilities) : [UserGroup::NONE_ROLE];
    }

    /**
     * Checks the user access by user level.
     *
     * @param bool|string $mAllowedCapability If set check also for the capability.
     *
     * @return boolean
     */
    public function checkUserAccess($mAllowedCapability = false)
    {
        $oCurrentUser = $this->oWordpress->getCurrentUser();

        if ($this->oWordpress->isSuperAdmin($oCurrentUser->ID) === true
            || $mAllowedCapability !== false && $oCurrentUser->has_cap($mAllowedCapability) === true
        ) {
            return true;
        }

        $aRoles = $this->getUserRole($oCurrentUser);
        $aRolesMap = array_flip($aRoles);

        $aOrderedRoles = [UserGroup::NONE_ROLE, 'subscriber', 'contributor', 'author', 'editor', 'administrator'];
        $aOrderedRolesMap = array_flip($aOrderedRoles);

        $aUserRoles = array_intersect_key($aOrderedRolesMap, $aRolesMap);
        $iRightsLevel = (count($aUserRoles) > 0) ? end($aUserRoles) : -1;
        $sFullAccessRole = $this->oConfig->getFullAccessRole();

        return (
            isset($aOrderedRolesMap[$sFullAccessRole]) === true && $iRightsLevel >= $aOrderedRolesMap[$sFullAccessRole]
            || isset($aRolesMap['administrator']) === true
        );
    }

    /**
     * Checks if the user is an admin user
     *
     * @param integer $iUserId The user id.
     *
     * @return boolean
     */
    public function userIsAdmin($iUserId)
    {
        $oUser = $this->oObjectHandler->getUser($iUserId);
        $aRoles = $this->getUserRole($oUser);
        $aRolesMap = array_flip($aRoles);

        return (isset($aRolesMap['administrator']) || $this->oWordpress->isSuperAdmin($iUserId));
    }

    /**
     * Checks if the current_user has access to the given post.
     *
     * @param string  $sObjectType The object type which should be checked.
     * @param integer $iObjectId   The _iId of the object.
     *
     * @return boolean
     */
    public function checkObjectAccess($sObjectType, $iObjectId)
    {
        if ($this->oObjectHandler->isValidObjectType($sObjectType) === false) {
            return true;
        } elseif (isset($this->aObjectAccess[$sObjectType]) === false) {
            $this->aObjectAccess[$sObjectType] = [];
        }

        if (isset($this->aObjectAccess[$sObjectType][$iObjectId]) === false) {
            $blAccess = false;
            $oCurrentUser = $this->oWordpress->getCurrentUser();

            if ($this->checkUserAccess('manage_user_groups') === true) {
                $blAccess = true;
            } elseif ($this->oConfig->authorsHasAccessToOwn() === true
                && $this->oObjectHandler->isPostType($sObjectType)
            ) {
                $oPost = $this->oObjectHandler->getPost($iObjectId);
                $blAccess = ($oPost !== false && $oCurrentUser->ID === (int)$oPost->post_author);
            }

            if ($blAccess === false) {
                $aMembership = $this->getUserGroupsForObject($sObjectType, $iObjectId);

                if (count($aMembership) > 0) {
                    $aUserUserGroups = $this->getUserGroupsForUser();

                    foreach ($aMembership as $iUserGroupId => $oUserGroup) {
                        if (isset($aUserUserGroups[$iUserGroupId]) === true) {
                            $blAccess = true;
                            break;
                        }
                    }
                } else {
                    $blAccess = true;
                }
            }

            $this->aObjectAccess[$sObjectType][$iObjectId] = $blAccess;
        }

        return $this->aObjectAccess[$sObjectType][$iObjectId];
    }

    /**
     * Returns the excluded terms for a user.
     *
     * @return array
     */
    public function getExcludedTerms()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->aExcludedTerms = [];
        }

        if ($this->aExcludedTerms === null) {
            $aExcludedTerms = [];
            $aUserGroups = $this->getUserGroups();

            $aUserUserGroups = $this->getUserGroupsForUser();

            foreach ($aUserGroups as $oUserGroups) {
                $aExcludedTerms += $oUserGroups->getFullTerms();
            }

            foreach ($aUserUserGroups as $oUserGroups) {
                $aExcludedTerms = array_diff_key($aExcludedTerms, $oUserGroups->getFullTerms());
            }

            $aTermIds = array_keys($aExcludedTerms);
            $this->aExcludedTerms = array_combine($aTermIds, $aTermIds);
        }

        return $this->aExcludedTerms;
    }

    /**
     * Returns the excluded posts.
     *
     * @return array
     */
    public function getExcludedPosts()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->aExcludedPosts = [];
        }

        if ($this->aExcludedPosts === null) {
            $aExcludedPosts = [];
            $aUserGroups = $this->getUserGroups();

            $aUserUserGroups = $this->getUserGroupsForUser();

            foreach ($aUserGroups as $oUserGroups) {
                $aExcludedPosts += $oUserGroups->getFullPosts();
            }

            foreach ($aUserUserGroups as $oUserGroups) {
                $aExcludedPosts = array_diff_key($aExcludedPosts, $oUserGroups->getFullPosts());
            }

            if ($this->oWordpress->isAdmin() === false) {
                $aNoneHiddenPostTypes = [];
                $aPostTypes = $this->oObjectHandler->getPostTypes();

                foreach ($aPostTypes as $sPostType) {
                    if ($this->oConfig->hidePostType($sPostType) === false) {
                        $aNoneHiddenPostTypes[$sPostType] = $sPostType;
                    }
                }

                foreach ($aExcludedPosts as $iPostId => $sType) {
                    if (isset($aNoneHiddenPostTypes[$sType])) {
                        unset($aExcludedPosts[$iPostId]);
                    }
                }
            }

            $aPostIds = array_keys($aExcludedPosts);
            $this->aExcludedPosts = array_combine($aPostIds, $aPostIds);
        }

        return $this->aExcludedPosts;
    }
}
