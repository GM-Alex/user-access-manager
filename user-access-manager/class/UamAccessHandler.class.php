<?php
/**
 * UamAccessHandler.class.php
 * 
 * The UamUserGroup class file.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
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
    protected $_oUserAccessManager = null;
    protected $_aObjectUserGroups = array();
    protected $_aObjectAccess = array();
    protected $_aUserGroups = array(
        'filtered' => array(),
        'noneFiltered' => array(),
    );
    protected $_aPlObjects = array();
    protected $_aObjectTypes = array(
        'category',
        'user',
        'role',
    );
    protected $_aPostableTypes = array(
        'post',
        'page',
        'attachment',
    );
    protected $_aPostableTypesMap = array();
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

        $this->_aPostableTypes = array_merge($this->_aPostableTypes, get_post_types(array('publicly_queryable' => true), 'names'));
        $this->_aPostableTypes = array_unique($this->_aPostableTypes);

        $this->_aPostableTypesMap = array_flip($this->_aPostableTypes);
        
        $this->_aObjectTypes = array_merge($this->_aPostableTypes, $this->_aObjectTypes);
        add_action( 'registered_post_type', array( &$this, 'registered_post_type'), 10, 2);
    }

    /**
     * used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     *
     * @param string    $post_type The string for the new post_type
     * @param stdClass  $oArgs     The array of arguments used to create the post_type
     *
     */
    public function registered_post_type($post_type, $oArgs)
    {
        if ($oArgs->publicly_queryable) {
            $this->_aPostableTypes[] = $oArgs->name;
            $this->_aPostableTypes = array_unique($this->_aPostableTypes);
            $this->_aPostableTypesMap = array_flip($this->_aPostableTypes);
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
        return isset($this->_aPostableTypesMap[$sType]);
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
        $aUamOptions = $this->getUserAccessManager()->getAdminOptions();
        
        if ($aUamOptions['authors_can_add_posts_to_groups'] == 'true'
            && !$this->checkUserAccess('manage_user_groups')
            && $this->getUserAccessManager()->atAdminPanel()
        ) {
            $oCurrentUser = $this->getUserAccessManager()->getCurrentUser();
            $aUserGroupsForUser = $this->getUserGroupsForObject('user', $oCurrentUser->ID);
            
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
        if ($blFilter) {
            $sFilterAttr = 'filtered';
        } else {
            $sFilterAttr = 'noneFiltered';
        }
        
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

        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $aUserGroupsDb = $wpdb->get_results(
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
     * 
     * @return null
     */
    public function addUserGroup($oUserGroup)
    {
        $this->getUserGroups();
        $this->_aUserGroups['noneFiltered'][$oUserGroup->getId()] = $oUserGroup;
        $this->_aUserGroups['filtered'] = array();
    }
    
    /**
     * Deletes a user group.
     * 
     * @param integer $iUserGroupId The user group _iId which we want to delete.
     * 
     * @return null
     */
    public function deleteUserGroup($iUserGroupId)
    {
        if ($this->getUserGroups($iUserGroupId) != null) {
            $this->getUserGroups($iUserGroupId)->delete();
            unset($this->_aUserGroups['noneFiltered'][$iUserGroupId]);
            $this->_aUserGroups['filtered'] = array();
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
        
        if ($sObjectType == 'user') {
            $blFilter = false;
        }
        
        if ($blFilter) {
            $sFilterAttr = 'filtered';
        } else {
            $sFilterAttr = 'noneFiltered';
        }

        if (isset($this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId])) {
            return $this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId];
        }

        $sCacheKey = 'getUserGroupsForObject|'.$sObjectType.'|'.$sFilterAttr.'|'.$iObjectId;
        $oUserAccessManager = $this->getUserAccessManager();
        $aObjectUserGroups = $oUserAccessManager->getFromCache($sCacheKey);

        if ($aObjectUserGroups !== null) {
            $this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId] = $aObjectUserGroups;
        } else {
            $aObjectUserGroups = array();
            $aUserGroups = $this->getUserGroups(null, $blFilter);

            $aCurIp = explode(".", $_SERVER['REMOTE_ADDR']);

            if (isset($aUserGroups)) {
                foreach ($aUserGroups as $oUserGroup) {
                    $mObjectMembership = $oUserGroup->objectIsMember($sObjectType, $iObjectId, true);

                    if ($mObjectMembership !== false
                        || $sObjectType == 'user' && $this->checkUserIp($aCurIp, $oUserGroup->getIpRange())
                    ) {
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
        return $this->_aObjectUserGroups[$sObjectType][$sFilterAttr][$iObjectId];
    }
    
    /**
     * Unset the user groups for _aObjects.
     * 
     * @return null
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
        
        if (isset($this->_aObjectAccess[$sObjectType][$iObjectId])) {
            return $this->_aObjectAccess[$sObjectType][$iObjectId];
        }

        $oCurrentUser = $this->getUserAccessManager()->getCurrentUser();

        if ($this->isPostableType($sObjectType)) {
            $oPost = $this->getUserAccessManager()->getPost($iObjectId);
            $sAuthorId = $oPost->post_author;
        } else {
            $sAuthorId = -1;
        }
        
        $aUamOptions = $this->getUserAccessManager()->getAdminOptions();
        $aMembership = $this->getUserGroupsForObject($sObjectType, $iObjectId, false);
        
        if ($aMembership == array()
            || $this->checkUserAccess('manage_user_groups')
            || $oCurrentUser->ID == $sAuthorId
            && $aUamOptions['authors_has_access_to_own'] == 'true'
        ) {
            return $this->_aObjectAccess[$sObjectType][$iObjectId] = true;
        }
        
        $aCurIp = explode(".", $_SERVER['REMOTE_ADDR']);
        
        foreach ($aMembership as $sKey => $oUserGroup) {
            if ($this->checkUserIp($aCurIp, $oUserGroup->getIpRange())
                || $oUserGroup->objectIsMember('user', $oCurrentUser->ID)
            ) {
                return $this->_aObjectAccess[$sObjectType][$iObjectId] = true;
            }
            
            if ($this->getUserAccessManager()->atAdminPanel() && $oUserGroup->getWriteAccess() == 'all'
                || !$this->getUserAccessManager()->atAdminPanel() && $oUserGroup->getReadAccess() == 'all'
            ) {
                unset($aMembership[$sKey]);
            }
        }
        
        if ($aMembership == array()) {
            return $this->_aObjectAccess[$sObjectType][$iObjectId] = true;
        }
        
        return $this->_aObjectAccess[$sObjectType][$iObjectId] = false;
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
        if (isset($this->_aSqlResults['groupsForUser'])) {
            return $this->_aSqlResults['groupsForUser'];
        }

        $oCurrentUser = $this->getUserAccessManager()->getCurrentUser();
        $aUserUserGroups = $this->getUserGroupsForObject('user', $oCurrentUser->ID, false);
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
        return $this->_aSqlResults['groupsForUser'];
    }
    
    /**
     * Returns the categories assigned to the user.
     * 
     * @return array
     */
    public function getCategoriesForUser()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        
        if (isset($this->_aSqlResults['categoriesAssignedToUser'])) {
            return $this->_aSqlResults['categoriesAssignedToUser'];
        }
        
        $sUserUserGroups = $this->_getUserGroupsForUserAsSqlString();
        
        $sCategoriesAssignedToUserSql = "
            SELECT igc.object_id
            FROM ".DB_ACCESSGROUP_TO_OBJECT." AS igc
            WHERE igc.object_type = 'category'
            AND igc.group_id IN (".$sUserUserGroups.")";
        
        $this->_aSqlResults['categoriesAssignedToUser'] = $wpdb->get_col($sCategoriesAssignedToUserSql);
        return $this->_aSqlResults['categoriesAssignedToUser'];
    }
    
    /**
     * Returns the posts assigned to the user.
     * 
     * @return array
     */
    public function getPostsForUser()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        
        if (isset($this->_aSqlResults['postsAssignedToUser'])) {
            return $this->_aSqlResults['postsAssignedToUser'];
        }
        
        $sUserUserGroup = $this->_getUserGroupsForUserAsSqlString();
        $sPostableTypes = "'".implode("','", $this->getPostableTypes())."'";
        
        $sPostAssignedToUserSql = "
            SELECT igp.object_id
            FROM ".DB_ACCESSGROUP_TO_OBJECT." AS igp
            WHERE igp.object_type IN (".$sPostableTypes.")
            AND igp.group_id IN (".$sUserUserGroup.")";
        
        $this->_aSqlResults['postsAssignedToUser'] = $wpdb->get_col($sPostAssignedToUserSql);
        return $this->_aSqlResults['postsAssignedToUser'];
    }
    
     /**
     * Returns the excluded posts.
     * 
     * @return array
     */
    public function getExcludedPosts()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->_aSqlResults['excludedPosts'] = array();
        }
        
        if (isset($this->_aSqlResults['excludedPosts'])) {
            return $this->_aSqlResults['excludedPosts'];
        }
        
        if ($this->getUserAccessManager()->atAdminPanel()) {
            $sAccessType = "write";
        } else {
            $sAccessType = "read";
        }

        $aCategoriesAssignedToUser = $this->getCategoriesForUser();
            
        if ($aCategoriesAssignedToUser !== array()) {
            $sCategoriesAssignedToUser = implode(', ', $aCategoriesAssignedToUser);
        } else {
            $sCategoriesAssignedToUser = "''";
        }
        
        $aPostAssignedToUser = $this->getPostsForUser();
        
        if ($aPostAssignedToUser !== array()) {
            $sPostAssignedToUser = implode(', ', $aPostAssignedToUser);
        } else {
            $sPostAssignedToUser = "''";
        }
        
        $sPostSql = "SELECT DISTINCT p.ID
            FROM $wpdb->posts AS p
            INNER JOIN $wpdb->term_relationships AS tr
                ON p.ID = tr.object_id
            INNER JOIN $wpdb->term_taxonomy tt
                ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = 'category' 
            AND tt.term_id IN (
                SELECT gc.object_id
                FROM ".DB_ACCESSGROUP." iag
                INNER JOIN ".DB_ACCESSGROUP_TO_OBJECT." AS gc
                    ON iag.id = gc.group_id
                WHERE gc.object_type = 'category'
                AND iag.".$sAccessType."_access != 'all'
                AND gc.object_id  NOT IN (".$sCategoriesAssignedToUser.")
            ) AND p.ID NOT IN (".$sPostAssignedToUser.")
            UNION
            SELECT DISTINCT gp.object_id
            FROM ".DB_ACCESSGROUP." AS ag
            INNER JOIN ".DB_ACCESSGROUP_TO_OBJECT." AS gp
                ON ag.id = gp.group_id
            INNER JOIN $wpdb->term_relationships AS tr
                ON gp.object_id  = tr.object_id
            INNER JOIN $wpdb->term_taxonomy tt
                ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE gp.object_type = 'post'
            AND ag.".$sAccessType."_access != 'all'
            AND gp.object_id  NOT IN (".$sPostAssignedToUser.")
            AND tt.term_id NOT IN (".$sCategoriesAssignedToUser.")";
        
        $this->_aSqlResults['excludedPosts'] = $wpdb->get_col($sPostSql);
        return $this->_aSqlResults['excludedPosts'];
    }
    
    
    /*
     * Other functions
     */
    
    /**
     * Checks if the given ip matches with the range.
     * 
     * @param array $aCurIp    The ip of the current user.
     * @param array $aIpRanges The ip ranges.
     * 
     * @return boolean
     */
    public function checkUserIp($aCurIp, $aIpRanges)
    {
        if (isset($aIpRanges)) {
            foreach ($aIpRanges as $aIpRange) {
                $aIpRange = explode("-", $aIpRange);
                $aRangeBegin = explode(".", $aIpRange[0]);
                
                if (isset($aIpRange[1])) {
                    $aRangeEnd = explode(".", $aIpRange[1]);
                } else {
                    $aRangeEnd = explode(".", $aIpRange[0]);
                }

                $iCurIp = ($aCurIp[0] << 24) + ($aCurIp[1] << 16) + ($aCurIp[2] << 8) + $aCurIp[3];
                $iRangeBegin = ($aRangeBegin[0] << 24) + ($aRangeBegin[1] << 16) + ($aRangeBegin[2] << 8) + $aRangeBegin[3];
                $iRangeEnd = ($aRangeEnd[0] << 24) + ($aRangeEnd[1]  << 16) + ($aRangeEnd[2]   << 8) + $aRangeEnd[3];

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
     * @param integer $iUserId The user _iId.
     * 
     * @return array
     */
    protected function _getUserRole($iUserId)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        
        $oUserData = get_userdata($iUserId);
        
        if (!empty($oUserData->user_level) && !isset($oUserData->user_level)) {
            $oUserData->user_level = null;
        }
        
        if (isset($oUserData->{$wpdb->prefix . "capabilities"})) {
            $aCapabilities = $oUserData->{$wpdb->prefix . "capabilities"};
        } else {
            $aCapabilities = array();
        }
        
        $aRoles = (is_array($aCapabilities) && count($aCapabilities) > 0) ? array_keys($aCapabilities) : array('norole');
        return $aRoles;
    }
    
    /**
     * Checks if the user is an admin user
     * 
     * @param integer $iUserId The user _iId.
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
        $aUamOptions = $this->getUserAccessManager()->getAdminOptions();
        
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

        if ($iRightsLevel >= $aOrderedRoles[$aUamOptions['full_access_role']]
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
     * Registers object that should be handelt by the user access manager.
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