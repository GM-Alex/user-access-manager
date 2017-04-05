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
    protected $_oWordpress;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var Cache
     */
    protected $_oCache;

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * @var UserGroupFactory
     */
    protected $_oUserGroupFactory;

    /**
     * @var array
     */
    protected $_aUserGroups = null;

    /**
     * @var array
     */
    protected $_aFilteredUserGroups = null;

    protected $_aUserGroupsForUser = null;
    protected $_aTermsAssignedToUser = null;
    protected $_aExcludedTerms = null;
    protected $_aPostsAssignedToUser = null;
    protected $_aExcludedPosts = null;
    protected $_aObjectUserGroups = [];
    protected $_aObjectAccess = [];

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
    )
    {
        $this->_oWordpress = $oWordpress;
        $this->_oConfig = $oConfig;
        $this->_oCache = $oCache;
        $this->_oDatabase = $oDatabase;
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oUtil = $oUtil;
        $this->_oUserGroupFactory = $oUserGroupFactory;
    }

    /**
     * Returns all user groups or one requested by the user group id.
     *
     * @return UserGroup[]
     */
    public function getUserGroups()
    {
        if ($this->_aUserGroups === null) {
            $this->_aUserGroups = [];

            $sQuery = "SELECT ID FROM {$this->_oDatabase->getUserGroupTable()}";
            $aUserGroupsDb = (array)$this->_oDatabase->getResults($sQuery);

            foreach ($aUserGroupsDb as $aUserGroupDb) {
                $this->_aUserGroups[$aUserGroupDb->ID] = $this->_oUserGroupFactory->createUserGroup($aUserGroupDb->ID);
            }
        }

        return $this->_aUserGroups;
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
        $this->_aUserGroups[$oUserGroup->getId()] = $oUserGroup;
        $this->_aFilteredUserGroups = null;
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
            unset($this->_aUserGroups[$iUserGroupId]);
            $this->_aFilteredUserGroups = null;

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
        if ($this->_oObjectHandler->isValidObjectType($sObjectType) === false) {
            return [];
        } elseif (isset($this->_aObjectUserGroups[$sObjectType]) === false) {
            $this->_aObjectUserGroups[$sObjectType] = [];
        }

        if (isset($this->_aObjectUserGroups[$sObjectType][$iObjectId]) === false) {
            $sCacheKey = $this->_oCache->generateCacheKey(
                'getUserGroupsForObject',
                $sObjectType,
                $iObjectId
            );
            $aObjectUserGroups = $this->_oCache->getFromCache($sCacheKey);

            if ($aObjectUserGroups !== null) {
                $this->_aObjectUserGroups[$sObjectType][$iObjectId] = $aObjectUserGroups;
            } else {
                $aObjectUserGroups = [];
                $aUserGroups = $this->getUserGroups();

                foreach ($aUserGroups as $oUserGroup) {
                    if ($oUserGroup->isObjectMember($sObjectType, $iObjectId) === true) {
                        $aObjectUserGroups[$oUserGroup->getId()] = $oUserGroup;
                    }
                }

                $this->_oCache->addToCache($sCacheKey, $aObjectUserGroups);
            }

            $this->_aObjectUserGroups[$sObjectType][$iObjectId] = $aObjectUserGroups;
        }

        return $this->_aObjectUserGroups[$sObjectType][$iObjectId];
    }

    /**
     * Unset the user groups for _aObjects.
     */
    public function unsetUserGroupsForObject()
    {
        $this->_aObjectUserGroups = [];
    }

    /**
     * Converts the ip to an integer.
     *
     * @param array $aIp
     *
     * @return int
     */
    protected function _calculateIp(array $aIp)
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
        $iCurIp = $this->_calculateIp($aCurrentIp);

        foreach ($aIpRanges as $sIpRange) {
            $aIpRange = explode('-', $sIpRange);
            $aRangeBegin = explode('.', $aIpRange[0]);
            $aRangeEnd = isset($aIpRange[1]) ? explode('.', $aIpRange[1]) : explode('.', $aIpRange[0]);

            if (count($aRangeBegin) === 4 && count($aRangeEnd) === 4) {
                $iRangeBegin = $this->_calculateIp($aRangeBegin);
                $iRangeEnd = $this->_calculateIp($aRangeEnd);

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

        if ($this->_aUserGroupsForUser === null) {
            $oCurrentUser = $this->_oWordpress->getCurrentUser();
            $aUserGroupsForUser = $this->getUserGroupsForObject(
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                $oCurrentUser->ID
            );

            $aUserGroups = $this->getUserGroups();

            foreach ($aUserGroups as $oUserGroup) {
                if (isset($aUserGroupsForUser[$oUserGroup->getId()]) === false
                    && ($this->isIpInRange($_SERVER['REMOTE_ADDR'], $oUserGroup->getIpRange())
                        || $this->_oConfig->atAdminPanel() === false && $oUserGroup->getReadAccess() === 'all'
                        || $this->_oConfig->atAdminPanel() === true && $oUserGroup->getWriteAccess() === 'all')
                ) {
                    $aUserGroupsForUser[$oUserGroup->getId()] = $oUserGroup;
                }
            }

            $this->_aUserGroupsForUser = $aUserGroupsForUser;
        }

        return $this->_aUserGroupsForUser;
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
    protected function _getUserRole(\WP_User $oUser)
    {
        if (isset($oUser->{$this->_oDatabase->getPrefix().'capabilities'})) {
            $aCapabilities = (array)$oUser->{$this->_oDatabase->getPrefix().'capabilities'};
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
        $oCurrentUser = $this->_oWordpress->getCurrentUser();

        if ($this->_oWordpress->isSuperAdmin($oCurrentUser->ID) === true
            || $mAllowedCapability !== false && $oCurrentUser->has_cap($mAllowedCapability) === true
        ) {
            return true;
        }

        $aRoles = $this->_getUserRole($oCurrentUser);
        $aRolesMap = array_flip($aRoles);

        $aOrderedRoles = [UserGroup::NONE_ROLE, 'subscriber', 'contributor', 'author', 'editor', 'administrator'];
        $aOrderedRolesMap = array_flip($aOrderedRoles);

        $aUserRoles = array_intersect_key($aOrderedRolesMap, $aRolesMap);
        $iRightsLevel = (count($aUserRoles) > 0) ? end($aUserRoles) : -1;
        $sFullAccessRole = $this->_oConfig->getFullAccessRole();

        return (isset($aOrderedRolesMap[$sFullAccessRole]) === true && $iRightsLevel >= $aOrderedRolesMap[$sFullAccessRole]
            || isset($aRolesMap['administrator']) === true);
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
        $oUser = $this->_oObjectHandler->getUser($iUserId);
        $aRoles = $this->_getUserRole($oUser);
        $aRolesMap = array_flip($aRoles);

        return (isset($aRolesMap['administrator']) || $this->_oWordpress->isSuperAdmin($iUserId));
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
        if ($this->_oObjectHandler->isValidObjectType($sObjectType) === false) {
            return true;
        } elseif (isset($this->_aObjectAccess[$sObjectType]) === false) {
            $this->_aObjectAccess[$sObjectType] = [];
        }

        if (isset($this->_aObjectAccess[$sObjectType][$iObjectId]) === false) {
            $blAccess = false;
            $oCurrentUser = $this->_oWordpress->getCurrentUser();

            if ($this->checkUserAccess('manage_user_groups') === true) {
                $blAccess = true;
            } elseif ($this->_oConfig->authorsHasAccessToOwn() === true
                && $this->_oObjectHandler->isPostType($sObjectType)
            ) {
                $oPost = $this->_oObjectHandler->getPost($iObjectId);
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

            $this->_aObjectAccess[$sObjectType][$iObjectId] = $blAccess;
        }

        return $this->_aObjectAccess[$sObjectType][$iObjectId];
    }

    /**
     * Returns the excluded terms for a user.
     *
     * @return array
     */
    public function getExcludedTerms()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->_aExcludedTerms = [];
        }

        if ($this->_aExcludedTerms === null) {
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
            $this->_aExcludedTerms = array_combine($aTermIds, $aTermIds);
        }

        return $this->_aExcludedTerms;
    }

    /**
     * Returns the excluded posts.
     *
     * @return array
     */
    public function getExcludedPosts()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->_aExcludedPosts = [];
        }

        if ($this->_aExcludedPosts === null) {
            $aExcludedPosts = [];
            $aUserGroups = $this->getUserGroups();

            $aUserUserGroups = $this->getUserGroupsForUser();

            foreach ($aUserGroups as $oUserGroups) {
                $aExcludedPosts += $oUserGroups->getFullPosts();
            }

            foreach ($aUserUserGroups as $oUserGroups) {
                $aExcludedPosts = array_diff_key($aExcludedPosts, $oUserGroups->getFullPosts());
            }

            if ($this->_oWordpress->isAdmin() === false) {
                $aNoneHiddenPostTypes = [];
                $aPostTypes = $this->_oObjectHandler->getPostTypes();

                foreach ($aPostTypes as $sPostType) {
                    if ($this->_oConfig->hidePostType($sPostType) === false) {
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
            $this->_aExcludedPosts = array_combine($aPostIds, $aPostIds);
        }

        return $this->_aExcludedPosts;
    }
}