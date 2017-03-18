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
    protected $_oWrapper;

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
    protected $_aObjectUserGroups = array();
    protected $_aObjectAccess = array();

    /**
     * The constructor
     *
     * @param Wordpress        $oWrapper
     * @param Config           $oConfig
     * @param Cache            $oCache
     * @param Database         $oDatabase
     * @param ObjectHandler    $oObjectHandler
     * @param Util             $oUtil
     * @param UserGroupFactory $oUserGroupFactory
     */
    public function __construct(
        Wordpress $oWrapper,
        Config $oConfig,
        Cache $oCache,
        Database $oDatabase,
        ObjectHandler $oObjectHandler,
        Util $oUtil,
        UserGroupFactory $oUserGroupFactory
    )
    {
        $this->_oWrapper = $oWrapper;
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
            $this->_aUserGroups = array();

            $sQuery = "SELECT ID FROM {$this->_oDatabase->getUserGroupTable()}";
            $aUserGroupsDb = (array)$this->_oDatabase->getResults($sQuery);

            foreach ($aUserGroupsDb as $aUserGroupDb) {
                $this->_aUserGroups[$aUserGroupDb->ID] = $this->_oUserGroupFactory->createUserGroup($aUserGroupDb->ID);
            }
        }

        return $this->_aUserGroups;
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
            return array();
        } elseif (isset($this->_aObjectUserGroups[$sObjectType]) === false) {
            $this->_aObjectUserGroups[$sObjectType] = array();
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
                $aObjectUserGroups = array();
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
        $this->_aObjectUserGroups = array();
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
        if ($this->_aUserGroupsForUser === null) {
            $oCurrentUser = $this->_oWrapper->getCurrentUser();
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
            $aCapabilities = array();
        }

        return (count($aCapabilities) > 0) ? array_keys($aCapabilities) : array(UserGroup::NONE_ROLE);
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
        $oCurrentUser = $this->_oWrapper->getCurrentUser();

        if ($this->_oWrapper->isSuperAdmin($oCurrentUser->ID) === true
            || $mAllowedCapability !== false && $oCurrentUser->has_cap($mAllowedCapability)
        ) {
            return true;
        }

        $aRoles = $this->_getUserRole($oCurrentUser);
        $aRolesMap = array_flip($aRoles);
        $aOrderedRoles = array(
            UserGroup::NONE_ROLE => 0,
            'subscriber' => 1,
            'contributor' => 2,
            'author' => 3,
            'editor' => 4,
            'administrator' => 5
        );
        $iRightsLevel = 0;

        foreach ($aRoles as $sRole) {
            if (isset($aOrderedRoles[$sRole]) && $aOrderedRoles[$sRole] > $iRightsLevel) {
                $iRightsLevel = $aOrderedRoles[$sRole];
            }
        }

        $sFullAccessRole = $this->_oConfig->getFullAccessRole();

        return (isset($aOrderedRoles[$sFullAccessRole]) && $iRightsLevel >= $aOrderedRoles[$sFullAccessRole]
            || isset($aRolesMap['administrator']));
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

        return (isset($aRolesMap['administrator']) || $this->_oWrapper->isSuperAdmin($iUserId));
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
            $this->_aObjectAccess[$sObjectType] = array();
        }

        if (isset($this->_aObjectAccess[$sObjectType][$iObjectId]) === false) {
            $blAccess = false;
            $oCurrentUser = $this->_oWrapper->getCurrentUser();

            if ($this->checkUserAccess('manage_user_groups') === true) {
                $blAccess = true;
            } elseif ($this->_oConfig->authorsHasAccessToOwn() === true
                && $this->_oObjectHandler->isPostType($sObjectType)
            ) {
                $oPost = $this->_oObjectHandler->getPost($iObjectId);
                $sAuthorId = ($oPost !== false) ? $oPost->post_author : -1;
                $blAccess = ($oCurrentUser->ID === $sAuthorId);
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
            $this->_aExcludedTerms = array();
        }

        if ($this->_aExcludedTerms === null) {
            $aExcludedTerms = [];
            $aUserGroups = $this->getUserGroups();

            $aUserUserGroups = $this->getUserGroupsForUser();

            foreach ($aUserGroups as $oUserGroups) {
                $aExcludedTerms += $oUserGroups->getFullPosts();
            }

            foreach ($aUserUserGroups as $oUserGroups) {
                $aExcludedTerms = array_diff_key($aExcludedTerms, $oUserGroups->getFullPosts());
            }

            $this->_aExcludedTerms = $aExcludedTerms;
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
            $this->_aExcludedPosts = array();
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

            $this->_aExcludedPosts = $aExcludedPosts;
        }

        return $this->_aExcludedPosts;
    }
}