<?php
/**
 * AccessHandler.php
 * 
 * The AccessHandler class file.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\Service\UserAccessManager;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Wordpress;

/**
 * The access handler class.
 * 
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */
class AccessHandler
{
    const OBJECTS_FILTERED = 'filtered';
    const OBJECTS_NONE_FILTERED = 'noneFiltered';

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
     * @var Util
     */
    protected $_oUtil;

    protected $_aGroupsForUser = null;
    protected $_aTermsAssignedToUser = null;
    protected $_aExcludedTerms = null;
    protected $_aPostsAssignedToUser = null;
    protected $_aExcludedPosts = null;
    protected $_aObjectUserGroups = array();
    protected $_aObjectAccess = array();
    protected $_aUserGroups = array();
    protected $_aPlObjects = array();
    protected $_aObjectTypes = array(
        UserAccessManager::TERM_OBJECT_TYPE => UserAccessManager::TERM_OBJECT_TYPE,
        UserAccessManager::USER_OBJECT_TYPE => UserAccessManager::USER_OBJECT_TYPE,
        UserAccessManager::ROLE_OBJECT_TYPE => UserAccessManager::ROLE_OBJECT_TYPE
    );
    protected $_aPostableTypes = array(
        UserAccessManager::POST_OBJECT_TYPE => UserAccessManager::POST_OBJECT_TYPE,
        UserAccessManager::PAGE_OBJECT_TYPE => UserAccessManager::PAGE_OBJECT_TYPE,
        UserAccessManager::ATTACHMENT_OBJECT_TYPE => UserAccessManager::ATTACHMENT_OBJECT_TYPE
    );
    protected $_aAllObjectTypes = null;
    protected $_aAllObjectTypesMap = null;
    protected $_aValidObjectTypes = array();
    
    /**
     * The constructor
     * 
     * @param Wordpress $oWrapper
     * @param Config    $oConfig
     * @param Cache     $oCache
     * @param Database  $oDatabase
     * @param Util      $oUtil
     */
    public function __construct(Wordpress $oWrapper, Config $oConfig, Cache $oCache, Database $oDatabase, Util $oUtil)
    {
        $this->_oWrapper = $oWrapper;
        $this->_oConfig = $oConfig;
        $this->_oCache = $oCache;
        $this->_oDatabase = $oDatabase;
        $this->_oUtil = $oUtil;
        $this->_aPostableTypes = array_merge($this->_aPostableTypes, $this->_oCache->getPostTypes());
        $this->_aObjectTypes = array_merge($this->_aPostableTypes, $this->_aObjectTypes, $this->_oCache->getTaxonomies());
        $this->_oWrapper->addAction('registered_post_type', array(&$this, 'registeredPostType'), 10, 2);
    }

    /**
     * used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     *
     * @param string    $sPostType  The string for the new post_type
     * @param \stdClass $oArguments The array of arguments used to create the post_type
     *
     */
    public function registeredPostType($sPostType, $oArguments)
    {
        if ($oArguments->publicly_queryable) {
            $this->_aPostableTypes[$oArguments->name] = $oArguments->name;
            $this->_aPostableTypes = array_unique($this->_aPostableTypes);
            $this->_aObjectTypes = array_merge($this->_aPostableTypes, $this->_aObjectTypes);
            $this->_aAllObjectTypes = null;
            $this->_aAllObjectTypesMap = null;
            $this->_aValidObjectTypes = null;
        }
    }

    /**
     * Checks if type is postable.
     *
     * @param string $sType
     *
     * @return bool
     */
    public function isPostableType($sType)
    {
        return isset($this->_aPostableTypes[$sType]);
    }

    /**
     * Returns the predefined object types.
     * 
     * @return array
     */
    public function getObjectTypes()
    {
        return $this->_aObjectTypes;
    }
    
    /**
     * Returns the predefined object types.
     * 
     * @return array;
     */
    public function getPostableTypes()
    {
        return $this->_aPostableTypes;
    }
    
    /**
     * Returns all objects types.
     * 
     * @return array
     */
    public function getAllObjectTypes()
    {
        if ($this->_aAllObjectTypes === null) {
            $aPlObjects = $this->getPlObjects();

            $this->_aAllObjectTypes = array_merge(
                $this->_aObjectTypes,
                array_keys($aPlObjects)
            );
        }
        
        return $this->_aAllObjectTypes;
    }

    /**
     * Returns all objects types as map.
     *
     * @return array
     */
    public function getAllObjectTypesMap()
    {
        if ($this->_aAllObjectTypesMap === null) {
            $this->_aAllObjectTypesMap = array_flip($this->getAllObjectTypes());
        }

        return $this->_aAllObjectTypesMap;
    }
    
    /**
     * Magic method getter.
     * 
     * @param string $sName      The name of the function
     * @param array  $aArguments The arguments for the function
     * 
     * @return mixed
     */
    public function __call($sName, $aArguments)
    {
        $sPrefix = null;

        if ($this->_oUtil->startsWith($sName, 'getUserGroupsFor')) {
            $sPrefix = 'getUserGroupsFor';
        } elseif ($this->_oUtil->startsWith($sName, 'checkAccessFor')) {
            $sPrefix = 'checkAccessFor';
        }

        if ($sPrefix !== null) {
            $sObjectType = str_replace($sPrefix, '', $sName);
            $sObjectType = strtolower($sObjectType);

            $iObjectId = $aArguments[0];

            if ($sPrefix === 'getUserGroupsFor') {
                return $this->getUserGroupsForObject($sObjectType, $iObjectId);
            } elseif ($sPrefix === 'checkAccessFor') {
                return $this->checkObjectAccess($sObjectType, $iObjectId);
            }
        }

        return null;
    }
    
    /**
     * Filter the user groups of an object if authors_can_add_posts_to_groups
     * option is enabled
     * 
     * @param UserGroup[] $aUserGroups The user groups.
     * 
     * @return array
     */
    protected function _filterUserGroups($aUserGroups)
    {
        if ($this->_oConfig->authorsCanAddPostsToGroups() === true
            && $this->_oConfig->atAdminPanel()
            && !$this->checkUserAccess('manage_user_groups')
        ) {
            $oCurrentUser = $this->_oWrapper->getCurrentUser();
            $aUserGroupsForUser = $this->getUserGroupsForObject(UserAccessManager::USER_OBJECT_TYPE, $oCurrentUser->ID);
            
            foreach ($aUserGroups as $sKey => $oUamUserGroup) {
                if (!isset($aUserGroupsForUser[$oUamUserGroup->getId()])) {
                    unset($aUserGroups[$sKey]);
                }
            }
        }
        
        return $aUserGroups;
    }

    /**
     * Checks if the object type is a valid one.
     *
     * @param string $sObjectType The object type to check.
     *
     * @return boolean
     */
    public function isValidObjectType($sObjectType)
    {
        if (!isset($this->_aValidObjectTypes[$sObjectType])) {
            $aObjectTypesMap = $this->getAllObjectTypesMap();

            if (isset($aObjectTypesMap[$sObjectType])) {
                $this->_aValidObjectTypes[$sObjectType] = true;
            } else {
                $this->_aValidObjectTypes[$sObjectType] = false;
            }
        }

        return $this->_aValidObjectTypes[$sObjectType];
    }
    
    /**
     * Returns all user groups or one requested by the user group id.
     *
     * @param boolean $blFilter     Filter the groups.
     * 
     * @return UserGroup[]
     */
    public function getUserGroups($blFilter = true)
    {
        $sFilterAttr = ($blFilter === true) ? self::OBJECTS_FILTERED :  self::OBJECTS_NONE_FILTERED;

        if (!isset($this->_aUserGroups[$sFilterAttr])) {
            $this->_aUserGroups[$sFilterAttr] = array();

            $aUserGroupsDb = (array)$this->_oDatabase->getResults(
                "SELECT ID
                FROM {$this->_oDatabase->getUserGroupTable()}
                ORDER BY ID", ARRAY_A
            );


            foreach ($aUserGroupsDb as $aUserGroupDb) {
                $this->_aUserGroups[$sFilterAttr][$aUserGroupDb['ID']] = new UserGroup($this, $aUserGroupDb['ID']);
            }

            //Filter the user groups
            if ($blFilter === true) {
                $this->_aUserGroups[$sFilterAttr] = $this->_filterUserGroups($this->_aUserGroups[$sFilterAttr]);
            }
        }

        return $this->_aUserGroups[$sFilterAttr];
    }
    
    /**
     * Adds a user group.
     * 
     * @param UserGroup $oUserGroup The user group which we want to add.
     */
    public function addUserGroup($oUserGroup)
    {
        $this->getUserGroups();
        $this->_aUserGroups[self::OBJECTS_NONE_FILTERED][$oUserGroup->getId()] = $oUserGroup;
        unset($this->_aUserGroups[self::OBJECTS_FILTERED]);
    }
    
    /**
     * Deletes a user group.
     * 
     * @param integer $iUserGroupId The user group _iId which we want to delete.
     */
    public function deleteUserGroup($iUserGroupId)
    {
        $aUserGroups = $this->getUserGroups();

        if (isset($aUserGroups[$iUserGroupId])) {
            $aUserGroups[$iUserGroupId]->delete();
            unset($this->_aUserGroups[self::OBJECTS_NONE_FILTERED][$iUserGroupId]);
            unset($this->_aUserGroups[self::OBJECTS_FILTERED]);
        }
    }
    
    /**
     * Returns the user groups for the given object.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The _iId of the object.
     * @param boolean $blFilter    Filter the groups.
     * 
     * @return UserGroup[]
     */
    public function getUserGroupsForObject($sObjectType, $iObjectId, $blFilter = true)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return array();
        }

        $blFilter = ($sObjectType === UserAccessManager::USER_OBJECT_TYPE) ? false : $blFilter;
        $sFilterAttr = ($blFilter === true) ? self::OBJECTS_FILTERED : self::OBJECTS_NONE_FILTERED;

        if (!isset($this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId])) {
            $sCacheKey = $this->_oCache->generateCacheKey(
                'getUserGroupsForObject',
                $sObjectType,
                $sFilterAttr,
                $iObjectId
            );
            $aObjectUserGroups = $this->_oCache->getFromCache($sCacheKey);

            if ($aObjectUserGroups !== null) {
                $this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId] = $aObjectUserGroups;
            } else {
                $aObjectUserGroups = array();
                $aUserGroups = $this->getUserGroups($blFilter);

                foreach ($aUserGroups as $oUserGroup) {
                    $mObjectMembership = $oUserGroup->objectIsMember($sObjectType, $iObjectId, true);

                    if ($mObjectMembership !== false) {
                        if (is_array($mObjectMembership)) {
                            $oUserGroup->setRecursiveMembership($sObjectType, $iObjectId, $mObjectMembership);
                        }

                        $aObjectUserGroups[$oUserGroup->getId()] = $oUserGroup;
                    }
                }

                //Filter the user groups
                if ($blFilter) {
                    $aObjectUserGroups = $this->_filterUserGroups($aObjectUserGroups);
                }

                $this->_oCache->addToCache($sCacheKey, $aObjectUserGroups);
            }

            $this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId] = $aObjectUserGroups;
        }

        return $this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId];
    }
    
    /**
     * Unset the user groups for _aObjects.
     */
    public function unsetUserGroupsForObject()
    {
        $this->_aObjectUserGroups = array();
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
        if (!$this->isValidObjectType($sObjectType)) {
            return true;
        }
        
        if (!isset($this->_aObjectAccess[$sObjectType][$iObjectId])) {
            $this->_aObjectAccess[$sObjectType][$iObjectId] = false;
            $oCurrentUser = $this->_oWrapper->getCurrentUser();

            if ($this->isPostableType($sObjectType)) {
                $oPost = $this->_oCache->getPost($iObjectId);
                $sAuthorId = $oPost->post_author;
            } else {
                $sAuthorId = -1;
            }

            $aMembership = $this->getUserGroupsForObject($sObjectType, $iObjectId, false);

            if ($aMembership == array()
                || $this->checkUserAccess('manage_user_groups')
                || $oCurrentUser->ID === $sAuthorId && $this->_oConfig->authorsHasAccessToOwn() === true
            ) {
                $this->_aObjectAccess[$sObjectType][$iObjectId] = true;
            } else {
                $aCurrentIp = explode('.', $_SERVER['REMOTE_ADDR']);

                foreach ($aMembership as $sKey => $oUserGroup) {
                    if ($oUserGroup->objectIsMember(UserAccessManager::USER_OBJECT_TYPE, $oCurrentUser->ID)
                        || $this->checkUserIp($aCurrentIp, $oUserGroup->getIpRange())
                    ) {
                        $this->_aObjectAccess[$sObjectType][$iObjectId] = true;
                        break;
                    } elseif ($this->_oConfig->atAdminPanel() && $oUserGroup->getWriteAccess() === 'all'
                        || !$this->_oConfig->atAdminPanel() && $oUserGroup->getReadAccess() === 'all'
                    ) {
                        unset($aMembership[$sKey]);
                    }
                }

                if ($aMembership == array()) {
                    $this->_aObjectAccess[$sObjectType][$iObjectId] = true;
                }
            }
        }
        
        return $this->_aObjectAccess[$sObjectType][$iObjectId];
    }

    /**
     * Returns the user groups for the user.
     *
     * @return UserGroup[]
     */
    protected function _getUserGroupsForUser()
    {
        if (!isset($this->_aGroupsForUser)) {
            $oCurrentUser = $this->_oWrapper->getCurrentUser();
            $aUserGroupsForUser = $this->getUserGroupsForObject(UserAccessManager::USER_OBJECT_TYPE, $oCurrentUser->ID, false);
            $aCurrentIp = explode('.', $_SERVER['REMOTE_ADDR']);
            $aUserGroups = $this->getUserGroups();

            foreach ($aUserGroups as $oUserGroup) {
                if (!isset($aUserUserGroupIds[$oUserGroup->getId()])
                    && $this->checkUserIp($aCurrentIp, $oUserGroup->getIpRange())
                ) {
                    $aUserGroupsForUser[$oUserGroup->getId()] = $oUserGroup;
                }
            }

            $this->_aGroupsForUser = $aUserGroupsForUser;
        }

        return $this->_aGroupsForUser;
    }
    
    /*
     * SQL functions.
     */
    
    /**
     * Returns the categories assigned to the user.
     * 
     * @return array
     */
    public function getTermsForUser()
    {
        if ($this->_aTermsAssignedToUser === null) {
            $aUserUserGroups = $this->_getUserGroupsForUser();
            $sUserUserGroups = $this->_oDatabase->generateSqlIdList(array_keys($aUserUserGroups));
            $sTermType = UserAccessManager::TERM_OBJECT_TYPE;

            $sTermsAssignedToUserSql = "
                SELECT igc.object_id
                FROM {$this->_oDatabase->getUserGroupToObjectTable()} AS igc
                WHERE igc.object_type = '{$sTermType}'
                AND igc.group_id IN ({$sUserUserGroups})";

            $this->_aTermsAssignedToUser  = $this->_oDatabase->getColumn($sTermsAssignedToUserSql);
        }

        return $this->_aTermsAssignedToUser;
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
            $sTermType = UserAccessManager::TERM_OBJECT_TYPE;
            $sAccessType = ($this->_oConfig->atAdminPanel() === true) ? 'write' : 'read';
            $aCategoriesAssignedToUser = $this->getTermsForUser();
            $sCategoriesAssignedToUser = $this->_oDatabase->generateSqlIdList($aCategoriesAssignedToUser);

            $sTermSql = "SELECT agto.object_id
                FROM {$this->_oDatabase->getUserGroupToObjectTable()} gto
                LEFT JOIN {$this->_oDatabase->getUserGroupTable()} AS g
                  ON gto.group_id = g.id 
                WHERE gto.object_type = '{$sTermType}'
                  AND gto.object_id NOT IN ({$sCategoriesAssignedToUser})
                  AND g.{$sAccessType}_access != 'all'";

            $this->_aExcludedTerms = $this->_oDatabase->getColumn($sTermSql);
        }

        return $this->_aExcludedTerms;
    }

    /**
     * Returns the posts assigned to the user.
     * 
     * @return array
     */
    public function getPostsForUser()
    {
        if ($this->_aPostsAssignedToUser === null) {
            $aUserUserGroups = $this->_getUserGroupsForUser();
            $sUserUserGroups = $this->_oDatabase->generateSqlIdList(array_keys($aUserUserGroups));
            $sPostableTypes = '\''.implode('\', \'', $this->getPostableTypes()).'\'';

            $sPostAssignedToUserSql = "
                SELECT object_id
                FROM {$this->_oDatabase->getUserGroupToObjectTable()}
                WHERE object_type IN ({$sPostableTypes})
                AND group_id IN ({$sUserUserGroups})";

            $this->_aPostsAssignedToUser = $this->_oDatabase->getColumn($sPostAssignedToUserSql);
        }

        return $this->_aPostsAssignedToUser;
    }

    /**
     * Returns the excluded user objects.
     *
     * @param string $sAccessType
     * @param string $sPostableTypes
     * @param string $sCategoriesAssignedToUser
     * @param string $sPostAssignedToUser
     *
     * @return array
     */
    protected function _getExcludedUserObjects(
        $sAccessType,
        $sPostableTypes,
        $sCategoriesAssignedToUser,
        $sPostAssignedToUser
    )
    {
        $sUserGroupTable = $this->_oDatabase->getUserGroupTable();
        $sUserGroupToObjectTable = $this->_oDatabase->getUserGroupToObjectTable();
        $sTermRelationshipsTable = $this->_oDatabase->getTermRelationshipsTable();
        $sTermTaxonomyTable = $this->_oDatabase->getTermTaxonomyTable();

        $sObjectQuery = "SELECT DISTINCT gp.object_id AS id, gp.object_type AS type
                FROM {$sUserGroupTable} AS ag
                INNER JOIN {$sUserGroupToObjectTable} AS gp
                  ON ag.id = gp.group_id
                LEFT JOIN {$sTermRelationshipsTable} AS tr
                  ON gp.object_id  = tr.object_id
                LEFT JOIN {$sTermTaxonomyTable} tt
                  ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE gp.object_type IN ({$sPostableTypes})
                  AND ag.{$sAccessType}_access != 'all'";

        if ($sPostAssignedToUser !== null) {
            $sObjectQuery .= "AND gp.object_id NOT IN ({$sPostAssignedToUser})";
        }

        if ($sCategoriesAssignedToUser !== null) {
            $sObjectQuery .= "AND (tt.term_id NOT IN ({$sCategoriesAssignedToUser}) OR tt.term_id IS NULL)";
        }

        return (array)$this->_oDatabase->getResults($sObjectQuery);
    }

    /**
     * Returns the excluded user posts.
     *
     * @param string $sAccessType
     * @param string $sPostableTypes
     * @param string $sCategoriesAssignedToUser
     * @param string $sPostAssignedToUser
     *
     * @return array
     */
    protected function _getExcludedUserPosts(
        $sAccessType,
        $sPostableTypes,
        $sCategoriesAssignedToUser,
        $sPostAssignedToUser
    )
    {
        $sTermType = UserAccessManager::TERM_OBJECT_TYPE;
        $sPostTable = $this->_oDatabase->getPostsTable();
        $sUserGroupTable = $this->_oDatabase->getUserGroupTable();
        $sUserGroupToObjectTable = $this->_oDatabase->getUserGroupToObjectTable();
        $sTermRelationshipsTable = $this->_oDatabase->getTermRelationshipsTable();
        $sTermTaxonomyTable = $this->_oDatabase->getTermTaxonomyTable();

        $sTermSql = "SELECT gc.object_id
                    FROM {$sUserGroupTable} iag
                    INNER JOIN {$sUserGroupToObjectTable} AS gc
                      ON iag.id = gc.group_id
                    WHERE gc.object_type = '{$sTermType}'
                      AND iag.{$sAccessType}_access != 'all'";

        if ($sCategoriesAssignedToUser !== null) {
            $sTermSql .= " AND gc.object_id NOT IN ({$sCategoriesAssignedToUser})";
        }

        $sPostQuery = "SELECT DISTINCT p.ID AS id, post_type AS type
                FROM {$sPostTable} AS p
                INNER JOIN {$sTermRelationshipsTable} AS tr
                  ON p.ID = tr.object_id
                INNER JOIN {$sTermTaxonomyTable} AS tt
                  ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE p.post_type != 'revision'
                  AND p.post_type IN ({$sPostableTypes})
                  AND tt.taxonomy = 'category' 
                  AND tt.term_id IN ({$sTermSql})";

        if ($sPostAssignedToUser !== null) {
            $sPostQuery .= " AND p.ID NOT IN ({$sPostAssignedToUser})";
        }

        return (array)$this->_oDatabase->getResults($sPostQuery);
    }
    
     /**
     * Returns the excluded posts.
     * 
     * @return array
     */
    public function getExcludedPosts()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->_aExcludedPosts = array(
                'all' => array()
            );
        }
        
        if ($this->_aExcludedPosts === null) {
            $sAccessType = ($this->_oConfig->atAdminPanel() === true) ? 'write' : 'read';

            $aCategoriesAssignedToUser = $this->getTermsForUser();
            $sCategoriesAssignedToUser = ($aCategoriesAssignedToUser !== array()) ?
                implode(', ', $aCategoriesAssignedToUser) : null;

            $aPostAssignedToUser = $this->getPostsForUser();
            $sPostAssignedToUser = ($aPostAssignedToUser !== array()) ? implode(', ', $aPostAssignedToUser) : null;

            $aPostableTypes = $this->getPostableTypes();

            if (!$this->_oConfig->atAdminPanel()) {
                foreach ($aPostableTypes as $sKey =>$sType) {
                    if ($this->_oConfig->hideObjectType($sType) === false) {
                        unset($aPostableTypes[$sKey]);
                    }
                }
            }

            $sPostableTypes = '\''.implode('\', \'', $aPostableTypes).'\'';

            $aObjectResult = $this->_getExcludedUserObjects(
                $sAccessType,
                $sPostableTypes,
                $sCategoriesAssignedToUser,
                $sPostAssignedToUser
            );
            $aPostResult = $this->_getExcludedUserPosts(
                $sAccessType,
                $sPostableTypes,
                $sCategoriesAssignedToUser,
                $sPostAssignedToUser
            );
            $aResult = array_merge($aObjectResult, $aPostResult);

            $aExcludedPosts = array(
                'all' => array()
            );

            foreach ($aResult as $oExcludedPost) {
                if (!isset($aExcludedPosts[$oExcludedPost->type])) {
                    $aExcludedPosts[$oExcludedPost->type] = array();
                }

                $aExcludedPosts[$oExcludedPost->type][$oExcludedPost->id] = $oExcludedPost->id;
            }

            $aPostTreeMap = $oUserAccessManager->getPostTreeMap();

            foreach ($aExcludedPosts as $sType => $aIds) {
                if ($sType !== 'all') {
                    if ($oUserAccessManager->isPostTypeHierarchical($sType)) {
                        foreach ($aIds as $iId) {
                            if (isset($aPostTreeMap[$iId])) {
                                foreach ($aPostTreeMap[$iId] as $iPostId => $sPostType) {
                                    if ($sPostType === $sType) {
                                        $aExcludedPosts[$sType][$iPostId] = $iPostId;
                                    }
                                }
                            }
                        }
                    }

                    $aExcludedPosts['all'] = $aExcludedPosts['all'] + $aExcludedPosts[$sType];
                }
            }

            $this->_aExcludedPosts = $aExcludedPosts;
        }

        return $this->_aExcludedPosts;
    }
    
    
    /*
     * Other functions
     */

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
     * @param array $aCurrentIp    The ip of the current user.
     * @param array $aIpRanges The ip ranges.
     * 
     * @return boolean
     */
    public function checkUserIp(array $aCurrentIp, array $aIpRanges)
    {
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
     * Return the role of the user.
     * 
     * @param integer $iUserId The user id.
     * 
     * @return array
     */
    protected function _getUserRole($iUserId)
    {
        $oUserData = $this->_oCache->getUser($iUserId);
        
        if (!empty($oUserData->user_level) && !isset($oUserData->user_level)) {
            $oUserData->user_level = null;
        }
        
        if (isset($oUserData->{$this->_oDatabase->getPrefix().'capabilities'})) {
            $aCapabilities = (array)$oUserData->{$this->_oDatabase->getPrefix().'capabilities'};
        } else {
            $aCapabilities = array();
        }
        
        return (count($aCapabilities) > 0) ? array_keys($aCapabilities) : array('norole');
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
        $aRoles = $this->_getUserRole($iUserId);
        $aRolesMap = array_keys($aRoles);
        
        return (isset($aRolesMap['administrator']) || $this->_oWrapper->isSuperAdmin($iUserId));
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
        
        $aRoles = $this->_getUserRole($oCurrentUser->ID);
        $aRolesMap = array_keys($aRoles);
        $aOrderedRoles = $this->getRolesOrdered();
        $iRightsLevel = 0;

        foreach ($aRoles as $sRole) {
            if (isset($aOrderedRoles[$sRole]) && $aOrderedRoles[$sRole] > $iRightsLevel) {
                $iRightsLevel = $aOrderedRoles[$sRole];
            }
        }

        $sFullAccessRole = $this->_oConfig->getFullAccessRole();

        return ($iRightsLevel >= $aOrderedRoles[$sFullAccessRole]
            || isset($aRolesMap['administrator'])
            || $this->_oWrapper->isSuperAdmin($oCurrentUser->ID)
            || ($mAllowedCapability !== true && $oCurrentUser->has_cap($mAllowedCapability))
        );
    }
    
    /**
     * Returns the roles as associative array.
     * 
     * @return array
     */
    public function getRolesOrdered()
    {
        $aOrderedRoles = array(
            'norole' => 0,
            'subscriber' => 1,
            'contributor' => 2,
            'author' => 3,
            'editor' => 4,
            'administrator' => 5
        );
        
        return $aOrderedRoles;
    }
    
    /**
     * Registers object that should be handel by the user access manager.
     * 
     * @param array $oObject The object which you want to register.
     * 
     * @return boolean
     */
    public function registerPlObject($oObject)
    {
        if (!isset($oObject['name']) || !isset($oObject['reference'])
            || !isset($oObject['getFull']) || !isset($oObject['getFullObjects'])
        ) {
            return false;
        }
        
        $this->_aPlObjects[$oObject['name']] = $oObject;
        
        return true;
    }
    
    /**
     * Returns a registered pluggable object.
     * 
     * @param string $sObjectName The name of the object which should be returned.
     * 
     * @return array
     */
    public function getPlObject($sObjectName)
    {
        if (isset($this->_aPlObjects[$sObjectName])) {
            return $this->_aPlObjects[$sObjectName];
        }
        
        return array();
    }
    
    /**
     * Returns all registered pluggable objects.
     * 
     * @return array
     */
    public function getPlObjects()
    {
        return $this->_aPlObjects;
    }
}