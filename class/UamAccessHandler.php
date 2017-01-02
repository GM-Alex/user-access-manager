<?php
/**
 * UamAccessHandler.php
 * 
 * The UamUserGroup class file.
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

/**
 * The access handler class.
 * 
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */
class UamAccessHandler
{
    const OBJECTS_FILTERED = 'filtered';
    const OBJECTS_NONE_FILTERED = 'noneFiltered';

    protected $_oUserAccessManager = null;
    protected $_aObjectUserGroups = array();
    protected $_aObjectAccess = array();
    protected $_aUserGroups = array(
        self::OBJECTS_FILTERED => array(),
        self::OBJECTS_NONE_FILTERED => array(),
    );
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
    protected $_aSqlResults = array();
    protected $_aValidObjectTypes = array();
    
    /**
     * The constructor
     * 
     * @param UserAccessManager $oUserAccessManager The user access manager object.
     */
    public function __construct(UserAccessManager &$oUserAccessManager)
    {
        $this->_oUserAccessManager = $oUserAccessManager;
        $this->_aPostableTypes = array_merge($this->_aPostableTypes, $oUserAccessManager->getPostTypes());
        $this->_aObjectTypes = array_merge($this->_aPostableTypes, $this->_aObjectTypes, $oUserAccessManager->getTaxonomies());
        add_action('registered_post_type', array( &$this, 'registeredPostType'), 10, 2);
    }

    /**
     * used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     *
     * @param string    $sPostType The string for the new post_type
     * @param stdClass  $oArgs     The array of arguments used to create the post_type
     *
     */
    public function registeredPostType($sPostType, $oArgs)
    {
        if ($oArgs->publicly_queryable) {
            $this->_aPostableTypes[$oArgs->name] = $oArgs->name;
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
     * Returns the user access manager object.
     * 
     * @return UserAccessManager
     */
    public function &getUserAccessManager()
    {
        return $this->_oUserAccessManager;
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
        $oUserAccessManager = $this->getUserAccessManager();

        if ($oUserAccessManager->startsWith($sName, 'getUserGroupsFor')) {
            $sPrefix = 'getUserGroupsFor';
        } elseif ($oUserAccessManager->startsWith($sName, 'checkAccessFor')) {
            $sPrefix = 'checkAccessFor';
        }

        if (isset($sPrefix)) {
            $sObjectType = str_replace($sPrefix, '', $sName);
            $sObjectType = strtolower($sObjectType);

            $iObjectId = $aArguments[0];

            if ($sPrefix == 'getUserGroupsFor') {
                return $this->getUserGroupsForObject($sObjectType, $iObjectId);
            } elseif ($sPrefix == 'checkAccessFor') {
                return $this->checkObjectAccess($sObjectType, $iObjectId);
            }
        }

        return null;
    }
    
    /**
     * Filter the user groups of an object if authors_can_add_posts_to_groups
     * option is enabled
     * 
     * @param UamUserGroup[] $aUserGroups The user groups.
     * 
     * @return array
     */
    protected function _filterUserGroups($aUserGroups)
    {
        $oConfig = $this->getUserAccessManager()->getConfig();
        
        if ($oConfig->authorsCanAddPostsToGroups() === true
            && !$this->checkUserAccess('manage_user_groups')
            && $this->getUserAccessManager()->atAdminPanel()
        ) {
            $oCurrentUser = $this->getUserAccessManager()->getCurrentUser();
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
     * @param integer $iUserGroupId The id of the single user group which should be returned.
     * @param boolean $blFilter     Filter the groups.
     * 
     * @return UamUserGroup[]|UamUserGroup
     */
    public function getUserGroups($iUserGroupId = null, $blFilter = true)
    {
        $sFilterAttr = ($blFilter === true) ? self::OBJECTS_FILTERED :  self::OBJECTS_NONE_FILTERED;

        if ($iUserGroupId === null
            && $this->_aUserGroups[$sFilterAttr] != array()
        ) {
            return $this->_aUserGroups[$sFilterAttr];
        } elseif ($iUserGroupId !== null
            && $this->_aUserGroups[$sFilterAttr] != array()
        ) {
            if (isset($this->_aUserGroups[$sFilterAttr][$iUserGroupId])) {
                return $this->_aUserGroups[$sFilterAttr][$iUserGroupId];
            } else {
                return null;
            }
        }
        
        $this->_aUserGroups[$sFilterAttr] = array();

        $oDatabase = $this->getUserAccessManager()->getDatabase();

        $aUserGroupsDb = $oDatabase->get_results(
            "SELECT ID
            FROM " . DB_ACCESSGROUP . "
            ORDER BY ID", ARRAY_A
        );
        
        if (isset($aUserGroupsDb)) {
            foreach ($aUserGroupsDb as $aUserGroupDb) {
                $this->_aUserGroups[$sFilterAttr][$aUserGroupDb['ID']] = new UamUserGroup($this, $aUserGroupDb['ID']);
            }
        }
        
        //Filter the user groups
        if ($blFilter) {
            $this->_aUserGroups[$sFilterAttr] = $this->_filterUserGroups($this->_aUserGroups[$sFilterAttr]);
        }
        
        if ($iUserGroupId == null) {
            if (isset($this->_aUserGroups[$sFilterAttr])) {
                return $this->_aUserGroups[$sFilterAttr];
            }

            return array();
        } else {
            if (isset($this->_aUserGroups[$sFilterAttr][$iUserGroupId])) {
                return $this->_aUserGroups[$sFilterAttr][$iUserGroupId];
            }

            return null;
        }
    }
    
    /**
     * Adds a user group.
     * 
     * @param UamUserGroup $oUserGroup The user group which we want to add.
     */
    public function addUserGroup($oUserGroup)
    {
        $this->getUserGroups();
        $this->_aUserGroups[self::OBJECTS_NONE_FILTERED][$oUserGroup->getId()] = $oUserGroup;
        $this->_aUserGroups[self::OBJECTS_FILTERED] = array();
    }
    
    /**
     * Deletes a user group.
     * 
     * @param integer $iUserGroupId The user group _iId which we want to delete.
     */
    public function deleteUserGroup($iUserGroupId)
    {
        if ($this->getUserGroups($iUserGroupId) != null) {
            $this->getUserGroups($iUserGroupId)->delete();
            unset($this->_aUserGroups[self::OBJECTS_NONE_FILTERED][$iUserGroupId]);
            $this->_aUserGroups[self::OBJECTS_FILTERED] = array();
        }
    }
    
    /**
     * Returns the user groups for the given object.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The _iId of the object.
     * @param boolean $blFilter    Filter the groups.
     * 
     * @return UamUserGroup[]
     */
    public function getUserGroupsForObject($sObjectType, $iObjectId, $blFilter = true)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return array();
        }

        $blFilter = ($sObjectType === UserAccessManager::USER_OBJECT_TYPE) ? false : $blFilter;
        $sFilterAttr = ($blFilter === true) ? self::OBJECTS_FILTERED : self::OBJECTS_NONE_FILTERED;

        if (!isset($this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId])) {
            $sCacheKey = 'getUserGroupsForObject|' . $sObjectType . '|' . $sFilterAttr . '|' . $iObjectId;
            $oUserAccessManager = $this->getUserAccessManager();
            $aObjectUserGroups = $oUserAccessManager->getFromCache($sCacheKey);

            if ($aObjectUserGroups !== null) {
                $this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId] = $aObjectUserGroups;
            } else {
                $aObjectUserGroups = array();
                $aUserGroups = $this->getUserGroups(null, $blFilter);

                if (is_array($aUserGroups)) {
                    foreach ($aUserGroups as $oUserGroup) {
                        $mObjectMembership = $oUserGroup->objectIsMember($sObjectType, $iObjectId, true);

                        if ($mObjectMembership !== false) {
                            if (is_array($mObjectMembership)) {
                                $oUserGroup->setRecursiveMembership($sObjectType, $iObjectId, $mObjectMembership);
                            }

                            $aObjectUserGroups[$oUserGroup->getId()] = $oUserGroup;
                        }
                    }
                }

                //Filter the user groups
                if ($blFilter) {
                    $aObjectUserGroups = $this->_filterUserGroups($aObjectUserGroups);
                }

                $oUserAccessManager->addToCache($sCacheKey, $aObjectUserGroups);
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
            $oCurrentUser = $this->getUserAccessManager()->getCurrentUser();

            if ($this->isPostableType($sObjectType)) {
                $oPost = $this->getUserAccessManager()->getPost($iObjectId);
                $sAuthorId = $oPost->post_author;
            } else {
                $sAuthorId = -1;
            }

            $oConfig = $this->getUserAccessManager()->getConfig();
            $aMembership = $this->getUserGroupsForObject($sObjectType, $iObjectId, false);

            if ($aMembership == array()
                || $this->checkUserAccess('manage_user_groups')
                || $oCurrentUser->ID === $sAuthorId && $oConfig->authorsHasAccessToOwn() === true
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
                    } elseif ($this->getUserAccessManager()->atAdminPanel() && $oUserGroup->getWriteAccess() == 'all'
                        || !$this->getUserAccessManager()->atAdminPanel() && $oUserGroup->getReadAccess() == 'all'
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
    
    
    /*
     * SQL functions.
     */
    
    /**
     * Returns the user groups for the current user as sql string.
     * 
     * @return string
     */
    protected function _getUserGroupsForUserAsSqlString()
    {
        if (!isset($this->_aSqlResults['groupsForUser'])) {
            $oCurrentUser = $this->getUserAccessManager()->getCurrentUser();
            $aUserUserGroups = $this->getUserGroupsForObject(UserAccessManager::USER_OBJECT_TYPE, $oCurrentUser->ID, false);
            $aUserUserGroupIds = array();

            foreach ($aUserUserGroups as $oUserUserGroup) {
                $aUserUserGroupIds[] = $oUserUserGroup->getId();
            }

            if ($aUserUserGroupIds !== array()) {
                $sUserUserGroups = implode(', ', $aUserUserGroupIds);
            } else {
                $sUserUserGroups = "''";
            }

            $this->_aSqlResults['groupsForUser'] = $sUserUserGroups;
        }

        return $this->_aSqlResults['groupsForUser'];
    }
    
    /**
     * Returns the categories assigned to the user.
     * 
     * @return array
     */
    public function getTermsForUser()
    {
        $oDatabase = $this->getUserAccessManager()->getDatabase();
        
        if (!isset($this->_aSqlResults['termsAssignedToUser'])) {
            $sUserUserGroups = $this->_getUserGroupsForUserAsSqlString();
            $sTermType = UserAccessManager::TERM_OBJECT_TYPE;

            $sTermsAssignedToUserSql = "
                SELECT igc.object_id
                FROM " . DB_ACCESSGROUP_TO_OBJECT . " AS igc
                WHERE igc.object_type = '{$sTermType}'
                AND igc.group_id IN ({$sUserUserGroups})";

            $this->_aSqlResults['termsAssignedToUser'] = $oDatabase->get_col($sTermsAssignedToUserSql);
        }

        return $this->_aSqlResults['termsAssignedToUser'];
    }

    /**
     * Returns the excluded terms for a user.
     *
     * @return array
     */
    public function getExcludedTerms()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->_aSqlResults['excludedTerms'] = array();
        }

        if (!isset($this->_aSqlResults['excludedTerms'])) {
            $oDatabase = $this->getUserAccessManager()->getDatabase();
            $sTermType = UserAccessManager::TERM_OBJECT_TYPE;
            $sAccessType = ($this->getUserAccessManager()->atAdminPanel() === true) ? 'write' : 'read';
            $aCategoriesAssignedToUser = $this->getTermsForUser();
            $sCategoriesAssignedToUser = ($aCategoriesAssignedToUser !== array()) ? implode(', ', $aCategoriesAssignedToUser) : "''";

            $sTermSql = "SELECT agto.object_id
                FROM " . DB_ACCESSGROUP_TO_OBJECT . " agto
                LEFT JOIN " . DB_ACCESSGROUP . " AS ag
                  ON agto.group_id = ag.id 
                WHERE agto.object_type = '{$sTermType}'
                  AND agto.object_id NOT IN ({$sCategoriesAssignedToUser})
                  AND ag.{$sAccessType}_access != 'all'";

            $this->_aSqlResults['excludedTerms'] = $oDatabase->get_col($sTermSql);
        }

        return $this->_aSqlResults['excludedTerms'];
    }

    /**
     * Returns the posts assigned to the user.
     * 
     * @return array
     */
    public function getPostsForUser()
    {
        if (!isset($this->_aSqlResults['postsAssignedToUser'])) {
            $oDatabase = $this->getUserAccessManager()->getDatabase();
            $sUserUserGroup = $this->_getUserGroupsForUserAsSqlString();
            $sPostableTypes = "'" . implode("','", $this->getPostableTypes()) . "'";

            $sPostAssignedToUserSql = "
                SELECT igp.object_id
                FROM " . DB_ACCESSGROUP_TO_OBJECT . " AS igp
                WHERE igp.object_type IN ({$sPostableTypes})
                AND igp.group_id IN ({$sUserUserGroup})";

            $this->_aSqlResults['postsAssignedToUser'] = $oDatabase->get_col($sPostAssignedToUserSql);
        }

        return $this->_aSqlResults['postsAssignedToUser'];
    }
    
     /**
     * Returns the excluded posts.
     * 
     * @return array
     */
    public function getExcludedPosts()
    {
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->_aSqlResults['excludedPosts'] = array(
                'all' => array()
            );
        }
        
        if (!isset($this->_aSqlResults['excludedPosts'])) {
            $oDatabase = $this->getUserAccessManager()->getDatabase();
            $oUserAccessManager = $this->getUserAccessManager();

            $sAccessType = ($oUserAccessManager->atAdminPanel()) ? 'write' : 'read';

            $aCategoriesAssignedToUser = $this->getTermsForUser();
            $sCategoriesAssignedToUser = ($aCategoriesAssignedToUser !== array()) ?
                implode(', ', $aCategoriesAssignedToUser) : null;

            $aPostAssignedToUser = $this->getPostsForUser();
            $sPostAssignedToUser = ($aPostAssignedToUser !== array()) ? implode(', ', $aPostAssignedToUser) : null;

            $sTermType = UserAccessManager::TERM_OBJECT_TYPE;
            $aPostableTypes = $this->getPostableTypes();

            if (!$oUserAccessManager->atAdminPanel()) {
                $oConfig = $oUserAccessManager->getConfig();

                foreach ($aPostableTypes as $sKey =>$sType) {
                    if ($oConfig->hideObjectType($sType) === false) {
                        unset($aPostableTypes[$sKey]);
                    }
                }
            }

            $sPostableTypes = "'" . implode("','", $aPostableTypes) . "'";

            $sTermSql = "SELECT gc.object_id
                    FROM " . DB_ACCESSGROUP . " iag
                    INNER JOIN " . DB_ACCESSGROUP_TO_OBJECT . " AS gc
                      ON iag.id = gc.group_id
                    WHERE gc.object_type = '{$sTermType}'
                      AND iag.{$sAccessType}_access != 'all'";

            if ($sCategoriesAssignedToUser !== null) {
                $sTermSql .= " AND gc.object_id NOT IN ({$sCategoriesAssignedToUser})";
            }

            $sObjectQuery = "SELECT DISTINCT gp.object_id AS id, gp.object_type AS type
                FROM " . DB_ACCESSGROUP . " AS ag
                INNER JOIN " . DB_ACCESSGROUP_TO_OBJECT . " AS gp
                  ON ag.id = gp.group_id
                LEFT JOIN {$oDatabase->term_relationships} AS tr
                  ON gp.object_id  = tr.object_id
                LEFT JOIN {$oDatabase->term_taxonomy} tt
                  ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE gp.object_type IN ({$sPostableTypes})
                  AND ag.{$sAccessType}_access != 'all'";

            if ($sPostAssignedToUser !== null) {
                $sObjectQuery .= "AND gp.object_id NOT IN ({$sPostAssignedToUser})";
            }

            if ($sCategoriesAssignedToUser !== null) {
                $sObjectQuery .= "AND (tt.term_id NOT IN ({$sCategoriesAssignedToUser}) OR tt.term_id IS NULL)";
            }

            $aObjectResult = $oDatabase->get_results($sObjectQuery);

            $sPostQuery = "SELECT DISTINCT p.ID AS id, post_type AS type
                FROM {$oDatabase->posts} AS p
                INNER JOIN {$oDatabase->term_relationships} AS tr
                  ON p.ID = tr.object_id
                INNER JOIN {$oDatabase->term_taxonomy} AS tt
                  ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE p.post_type != 'revision'
                  AND p.post_type IN ({$sPostableTypes})
                  AND tt.taxonomy = 'category' 
                  AND tt.term_id IN ({$sTermSql})";

            if ($sPostAssignedToUser !== null) {
                $sPostQuery .= " AND p.ID NOT IN ({$sPostAssignedToUser})";
            }

            $aPostResult = $oDatabase->get_results($sPostQuery);
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
                                    if ($sPostType == $sType) {
                                        $aExcludedPosts[$sType][$iPostId] = $iPostId;
                                    }
                                }
                            }
                        }
                    }

                    $aExcludedPosts['all'] = $aExcludedPosts['all'] + $aExcludedPosts[$sType];
                }
            }

            $this->_aSqlResults['excludedPosts'] = $aExcludedPosts;
        }

        return $this->_aSqlResults['excludedPosts'];
    }
    
    
    /*
     * Other functions
     */
    
    /**
     * Checks if the given ip matches with the range.
     * 
     * @param array $aCurrentIp    The ip of the current user.
     * @param array $aIpRanges The ip ranges.
     * 
     * @return boolean
     */
    public function checkUserIp($aCurrentIp, $aIpRanges)
    {
        if (isset($aIpRanges)) {
            foreach ($aIpRanges as $sIpRange) {
                $aIpRange = explode('-', $sIpRange);
                $aRangeBegin = explode('.', $aIpRange[0]);
                $aRangeEnd = isset($aIpRange[1]) ? explode('.', $aIpRange[1]) : explode('.', $aIpRange[0]);

                if (count($aRangeBegin) === 4 && count($aRangeEnd) === 4) {
                    $iCurIp = ($aCurrentIp[0] << 24) + ($aCurrentIp[1] << 16) + ($aCurrentIp[2] << 8) + $aCurrentIp[3];
                    $iRangeBegin = ($aRangeBegin[0] << 24) + ($aRangeBegin[1] << 16) + ($aRangeBegin[2] << 8) + $aRangeBegin[3];
                    $iRangeEnd = ($aRangeEnd[0] << 24) + ($aRangeEnd[1] << 16) + ($aRangeEnd[2] << 8) + $aRangeEnd[3];

                    if ($iRangeBegin <= $iCurIp && $iCurIp <= $iRangeEnd) {
                        return true;
                    }
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
        $oDatabase = $this->getUserAccessManager()->getDatabase();
        $oUserData = $this->getUserAccessManager()->getUser($iUserId);
        
        if (!empty($oUserData->user_level) && !isset($oUserData->user_level)) {
            $oUserData->user_level = null;
        }
        
        if (isset($oUserData->{$oDatabase->prefix . "capabilities"})) {
            $aCapabilities = $oUserData->{$oDatabase->prefix . "capabilities"};
        } else {
            $aCapabilities = array();
        }
        
        $aRoles = (is_array($aCapabilities) && count($aCapabilities) > 0) ? array_keys($aCapabilities) : array('norole');
        return $aRoles;
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
        
        if (isset($aRolesMap['administrator']) || is_super_admin($iUserId)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks the user access by user level.
     *
     * @param bool|string $sAllowedCapability If true check also for the capability.
     *
     * @return boolean
     */
    public function checkUserAccess($sAllowedCapability = false)
    {
        $oCurrentUser = $this->getUserAccessManager()->getCurrentUser();
        $oConfig = $this->getUserAccessManager()->getConfig();
        
        $aRoles = $this->_getUserRole($oCurrentUser->ID);
        $aRolesMap = array_keys($aRoles);
        $aOrderedRoles = $this->getRolesOrdered();
        $iRightsLevel = 0;

        foreach ($aRoles as $sRole) {
            if (isset($aOrderedRoles[$sRole])
                && $aOrderedRoles[$sRole] > $iRightsLevel
            ) {
                $iRightsLevel = $aOrderedRoles[$sRole];
            }
        }

        $sFullAccessRole = $oConfig->getFullAccessRole();

        if ($iRightsLevel >= $aOrderedRoles[$sFullAccessRole]
            || isset($aRolesMap['administrator'])
            || is_super_admin($oCurrentUser->ID)
            || ($sAllowedCapability && $oCurrentUser->has_cap($sAllowedCapability))
        ) {
            return true;
        }
        
        return false;
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