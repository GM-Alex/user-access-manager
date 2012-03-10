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
 * @copyright 2008-2010 Alexander Schneider
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
    protected $userAccessManager = null;
    protected $objectUserGroups = array();
    protected $objectAccess = array();
    protected $userGroups = array(
        'filtered' => array(),
        'noneFiltered' => array(),
    );
    protected $plObjects = array();
    protected $objectTypes = array(
        'category',
        'user',
        'role',
    );
    protected $postableTypes = array(
        'post',
        'page',
        'attachment',
    );
    protected $allObjectTypes = null;
    protected $sqlResults = array();
    
    /**
     * The consturctor
     * 
     * @param object $userAccessManager The user access manager object.
     * 
     * @return null
     */
    public function __construct($userAccessManager)
    {
        $this->userAccessManager = $userAccessManager;
        
        $postTypes = get_post_types(array(), 'objects');
        
        foreach ($postTypes as $postType) {
            if ($postType->publicly_queryable) {
                $this->postableTypes[] = $postType->name;
            }
        }
        
        $this->objectTypes = array_merge($this->postableTypes, $this->objectTypes);
    }
    
    /**
     * Returns the user access manager object.
     * 
     * @return object
     */
    public function &getUserAccessManager()
    {
        return $this->userAccessManager;
    }
    
    /**
     * Returns the predfined object types.
     * 
     * @return array
     */
    public function getObjectTypes()
    {
        return $this->objectTypes;
    }
    
    /**
     * Returns the predfined object types.
     * 
     * @return array();
     */
    public function getPostableTypes()
    {
        return $this->postableTypes;
    }
    
    /**
     * Returns all objects types.
     * 
     * @return array
     */
    public function getAllObjectTypes()
    {
        if (isset($this->allObjectTypes)) {
            return $this->allObjectTypes;
        }
        
        $plObjects = $this->getPlObjects();

        $this->allObjectTypes = array_merge(
            $this->objectTypes,
            array_keys($plObjects)
        );
        
        return $this->allObjectTypes;
    }
    
    /**
     * Magic method getter.
     * 
     * @param string $name      The name of the function 
     * @param array  $arguments The arguments for the function
     * 
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        echo $name;
        exit;
        
        $uam = $this->getUserAccessManager();

        $action = '';
        
        if ($uam->startsWith($name, 'getUserGroupsFor')) {
            $prefix = 'getUserGroupsFor';
        } elseif ($uam->startsWith($name, 'checkAccessFor')) {
            $prefix = 'checkAccessFor';
        }
        
        $objectType = str_replace($prefix, '', $name);
        $objectType = strtolower($objectType);
        
        $objectId = $arguments[0];

        if ($prefix == 'getUserGroupsFor') {
            return $this->getUserGroupsForObject(
                $objectType, 
                $objectId
            );
        } elseif ($prefix == 'checkAccessFor') {
            return $this->checkObjectAccess(
                $objectType, 
                $objectId
            );
        }
    }
    
    /**
     * Filter the user groups of an object if authors_can_add_posts_to_groups
     * option is enabled
     * 
     * @param array $userGroups The user groups.
     * 
     * @return array
     */
    private function _filterUserGroups($userGroups)
    {
        $uamOptions = $this->getUserAccessManager()->getAdminOptions();
        
        if ($uamOptions['authors_can_add_posts_to_groups'] == 'true'
        	&& !$this->checkUserAccess('manage_user_groups')
        	&& $this->getUserAccessManager()->atAdminPanel()
        ) {
            global $current_user;
            //Force user infos
            wp_get_current_user();
            
            $userGroupsForUser 
                = $this->getUserGroupsForObject('user', $current_user->ID);
            
            foreach ($userGroups as $key => $uamUserGroup) {
                if (!array_key_exists($uamUserGroup->getId(), $userGroupsForUser)) {
                    unset($userGroups[$key]);
                }
            }
        }
        
        return $userGroups;
    }
    
    /**
     * Returns all user groups or one requested by the user group id.
     * 
     * @param integer $userGroupId The id of the single user group 
     * 							   which should be returned.
     * @param boolean $filter      Filter the groups.
     * 
     * @return array|object
     */
    public function getUserGroups($userGroupId = null, $filter = true)
    {
        if ($filter) {
            $filterAttr = 'filtered';
        } else {
            $filterAttr = 'noneFiltered';
        }
        
        if ($userGroupId === null
            && $this->userGroups[$filterAttr] != array()
        ) {
            return $this->userGroups[$filterAttr];
        } elseif ($userGroupId !== null
                  && $this->userGroups[$filterAttr] != array()
        ) {
            if (isset($this->userGroups[$filterAttr][$userGroupId])) {
                return $this->userGroups[$filterAttr][$userGroupId];
            } else {
                return null;
            }
        }
        
        $this->userGroups[$filterAttr] = array();
        
        global $wpdb;

        $userGroupsDb = $wpdb->get_results(
        	"SELECT ID
        	FROM " . DB_ACCESSGROUP . "
        	ORDER BY ID", ARRAY_A
        );
        
        if (isset($userGroupsDb)) {
            foreach ($userGroupsDb as $userGroupDb) {
                $this->userGroups[$filterAttr][$userGroupDb['ID']] 
                    = new UamUserGroup($this, $userGroupDb['ID']);
            }
        }
        
        //Filter the user groups
        if ($filter) {
            $this->userGroups[$filterAttr] 
                = $this->_filterUserGroups($this->userGroups[$filterAttr]);
        }
        
        if ($userGroupId == null) {
            return $this->userGroups[$filterAttr];
        } elseif ($userGroupId != null) {
            if (isset($this->userGroups[$filterAttr][$userGroupId])) {
                return $this->userGroups[$filterAttr][$userGroupId];
            } else {
                return null;
            }
        }
    }
    
    /**
     * Adds a user group.
     * 
     * @param object $userGroup The user group which we want to add.
     * 
     * @return null
     */
    public function addUserGroup($userGroup)
    {
        $this->getUserGroups();
        $this->userGroups['noneFiltered'][$userGroup->getId()] = $userGroup;
        $this->userGroups['filtered'] = array();
    }
    
    /**
     * Deletes a user group.
     * 
     * @param integer $userGroupId The user group id which we want to delete.
     * 
     * @return null
     */
    public function deleteUserGroup($userGroupId)
    {
        if ($this->getUserGroups($userGroupId) != null) {
            $this->getUserGroups($userGroupId)->delete();
            unset($this->userGroups['noneFiltered'][$userGroupId]);
            $this->userGroups['filtered'] = array();
        }
    }
    
    /**
     * Returns the user groups for the given object.
     * 
     * @param string  $objectType The object type.
     * @param integer $objectId   The id of the object.
     * @param boolean $filter     Filter the groups.
     * 
     * @return array
     */
    public function getUserGroupsForObject($objectType, $objectId, $filter = true)
    {
        if (!in_array($objectType, $this->getAllObjectTypes())) {
            return array();
        }
        
        if ($objectType == 'user') {
            $filter = false;
        }
        
        if ($filter) {
            $filterAttr = 'filtered';
        } else {
            $filterAttr = 'noneFiltered';
        }
        
        if (isset($this->objectUserGroups[$objectType][$filterAttr][$objectId])) {
            return $this->objectUserGroups[$objectType][$filterAttr][$objectId];
        }
        
        $objectUserGroups = array();

        $userGroups = $this->getUserGroups(null, $filter);
        
        $plObject = false;
        
        $postableTypes = $this->getPostableTypes();

        if (!in_array($objectType, $postableTypes)) {
            $plObject = true;
        }
       
        $curIp = explode(".", $_SERVER['REMOTE_ADDR']);
        
        if (isset($userGroups)) {
            foreach ($userGroups as $userGroup) {
                $objectMembership = $userGroup->objectIsMember(
                    $objectType, 
                    $objectId, 
                    true
                );

                if ($objectMembership !== false
                    || $objectType == 'user'
                    && $this->checkUserIp($curIp, $userGroup->getIpRange())
                ) {
                    if (is_array($objectMembership)) {
                        $userGroup->setRecursive[$objectType][$objectId] 
                            = $objectMembership;
                    }

                    $objectUserGroups[$userGroup->getId()] 
                        = $userGroup;
                }
            }
        }
        
        //Filter the user groups
        if ($filter) {
            $objectUserGroups = $this->_filterUserGroups($objectUserGroups);
        }
        
        $this->objectUserGroups[$objectType][$filterAttr][$objectId] 
            = $objectUserGroups;
        
        return $this->objectUserGroups[$objectType][$filterAttr][$objectId];
    }
    
    /**
     * Unsets the usergroups for objects.
     * 
     * @return null
     */
    public function unsetUserGroupsForObject()
    {
        $this->objectUserGroups = array();
    }
    
    /**
     * Checks if the current_user has access to the given post.
     * 
     * @param string  $objectType The object type which should be checked.
     * @param integer $objectId   The id of the object.
     * 
     * @return boolean
     */
    public function checkObjectAccess($objectType, $objectId)
    {
        if (!in_array($objectType, $this->getAllObjectTypes())) {
            return true;
        }
        
        if (isset($this->objectAccess[$objectType][$objectId])) {
            return $this->objectAccess[$objectType][$objectId];  
        }
        
        global $current_user;
        //Force user infos
        wp_get_current_user();
        
        $postableTypes = $this->getPostableTypes();

        if (in_array($objectType, $postableTypes)) {
            $post = get_post($objectId);
            $authorId = $post->post_author;
        } else {
            $authorId = -1;
        }
        
        $uamOptions = $this->getUserAccessManager()->getAdminOptions();
        $membership = $this->getUserGroupsForObject($objectType, $objectId, false);
        
        if ($membership == array() 
            || $this->checkUserAccess('manage_user_groups')
            || $current_user->ID == $authorId
            && $uamOptions['authors_has_access_to_own'] == 'true'
        ) {
            return $this->objectAccess[$objectType][$objectId] = true;
        }
        
        $curIp = explode(".", $_SERVER['REMOTE_ADDR']);
        
        foreach ($membership as $key => $userGroup) {            
            if ($this->checkUserIp($curIp, $userGroup->getIpRange())
                || $userGroup->objectIsMember('user', $current_user->ID)
            ) {
                return $this->objectAccess[$objectType][$objectId] = true;
                break;
            }
            
            if ($this->getUserAccessManager()->atAdminPanel()
                && $userGroup->getWriteAccess() == 'all'
            	|| !$this->getUserAccessManager()->atAdminPanel() 
            	&& $userGroup->getReadAccess() == 'all'
            ) {
                unset($membership[$key]);
            }
        }
        
        if ($membership == array()) {
            return $this->objectAccess[$objectType][$objectId] = true;
        }
        
        return $this->objectAccess[$objectType][$objectId] = false;
    }
    
    
    /*
     * SQL functions.
     */
    
    /**
     * Returns the usergroups for the current user as sql string.
     * 
     * @return string
     */
    private function _getUserGroupsForUserAsSqlString()
    {
        if (isset($this->sqlResults['groupsForUser'])) {
            return $this->sqlResults['groupsForUser'];
        }
        
        global $current_user;
        //Force user infos
        wp_get_current_user();
        
        $userUserGroups = $this->getUserGroupsForObject(
            'user',
            $current_user->ID, 
            false
        );
        
        $userUserGroupArray = array();
        
        foreach ($userUserGroups as $userUserGroup) {
            $userUserGroupArray[] = $userUserGroup->getId();
        }
        
        if ($userUserGroupArray !== array()) {
            $userUserGroupString = implode(', ', $userUserGroupArray);
        } else {
            $userUserGroupString = "''";
        }
        
        $this->sqlResults['groupsForUser'] = $userUserGroupString;
        
        return $this->sqlResults['groupsForUser'];
    }
    
    /**
     * Returns the categories assigned to the user.
     * 
     * @return array
     */
    public function getCategoriesForUser()
    {
        global $wpdb;
        
        if (isset($this->sqlResults['categoriesAssignedToUser'])) {
            return $this->sqlResults['categoriesAssignedToUser'];
        }
        
        $userUserGroupString = $this->_getUserGroupsForUserAsSqlString();
        
        $categoriesAssignedToUserSql = "
        	SELECT igc.object_id  
    		FROM ".DB_ACCESSGROUP_TO_OBJECT." AS igc
    		WHERE igc.object_type = 'category'
    		AND igc.group_id IN (".$userUserGroupString.")";
        
        $this->sqlResults['categoriesAssignedToUser'] 
            = $wpdb->get_col($categoriesAssignedToUserSql);

        return $this->sqlResults['categoriesAssignedToUser'];
    }
    
    /**
     * Returns the posts assigned to the user.
     * 
     * @return array
     */
    public function getPostsForUser()
    {
        global $wpdb;
        
        if (isset($this->sqlResults['postsAssignedToUser'])) {
            return $this->sqlResults['postsAssignedToUser'];
        }
        
        $userUserGroupString = $this->_getUserGroupsForUserAsSqlString();
        
        $postAssignedToUserSql = "
        	SELECT igp.object_id  
        	FROM ".DB_ACCESSGROUP_TO_OBJECT." AS igp
        	WHERE igp.object_type = 'post'
            AND igp.group_id IN (".$userUserGroupString.")";
        
        $this->sqlResults['postsAssignedToUser'] 
            = $wpdb->get_col($postAssignedToUserSql);
            
        return $this->sqlResults['postsAssignedToUser'];
    }
    
 	/**
     * Returns the excluded posts.
     * 
     * @return array
     */
    public function getExcludedPosts()
    {
        global $wpdb;
        
        if ($this->checkUserAccess('manage_user_groups')) {
            $this->sqlResults['excludedPosts'] = array();
        }
        
        if (isset($this->sqlResults['excludedPosts'])) {
            return $this->sqlResults['excludedPosts'];
        }
        
        if ($this->getUserAccessManager()->atAdminPanel()) {
            $accessType = "write";
        } else {
            $accessType = "read";
        }

        $categoriesAssignedToUser = $this->getCategoriesForUser();
            
        if ($categoriesAssignedToUser !== array()) {
            $categoriesAssignedToUserString 
                = implode(', ', $categoriesAssignedToUser);
        } else {
            $categoriesAssignedToUserString = "''";
        }
        
        $postAssignedToUser = $this->getPostsForUser();
        
        if ($postAssignedToUser !== array()) {
            $postAssignedToUserString 
                = implode(', ', $postAssignedToUser);
        } else {
            $postAssignedToUserString = "''";
        }
        
        $postSql = "SELECT DISTINCT p.ID 
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
    			AND iag.".$accessType."_access != 'all'
    			AND gc.object_id  NOT IN (".$categoriesAssignedToUserString.")
    		) AND p.ID NOT IN (".$postAssignedToUserString.")
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
    		AND ag.".$accessType."_access != 'all'
    		AND gp.object_id  NOT IN (".$postAssignedToUserString.") 
    		AND tt.term_id NOT IN (".$categoriesAssignedToUserString.")";
        
        $this->sqlResults['excludedPosts'] = $wpdb->get_col($postSql);
        
        return $this->sqlResults['excludedPosts'];
    }
    
    
    /*
     * Other functions
     */
    
    /**
     * Checks if the given ip matches with the range.
     * 
     * @param string $curIp    The ip of the current user.
     * @param array  $ipRanges The ip ranges.
     * 
     * @return boolean
     */
    public function checkUserIp($curIp, $ipRanges)
    {
        if (isset($ipRanges)) {            
            foreach ($ipRanges as $ipRange) {
                $ipRange = explode("-", $ipRange);
                $rangeBegin = explode(".", $ipRange[0]);
                
                if (isset($ipRange[1])) { 
                    $rangeEnd = explode(".", $ipRange[1]);  
                } else {
                    $rangeEnd = explode(".", $ipRange[0]);
                }
                
                if ($rangeBegin[0] <= $curIp[0] 
                    && $curIp[0] <= $rangeEnd[0] 
                    && $rangeBegin[1] <= $curIp[1] 
                    && $curIp[1] <= $rangeEnd[1] 
                    && $rangeBegin[2] <= $curIp[2] 
                    && $curIp[2] <= $rangeEnd[2] 
                    && $rangeBegin[3] <= $curIp[3] 
                    && $curIp[3] <= $rangeEnd[3]
                ) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Return the role of the user.
     * 
     * @param integer $userId The user id.
     * 
     * @return string|null
     */
    private function _getUserRole($userId)
    {
        global $wpdb;
        
        $curUserdata = get_userdata($userId);
        
        if (!isset($curUserdata->user_level)) {
            $curUserdata->user_level = null;
        }
        
        if (isset($curUserdata->{$wpdb->prefix . "capabilities"})) {
            $capabilities = $curUserdata->{$wpdb->prefix . "capabilities"};
        } else {
            $capabilities = null;
        }
        
        $role  = (is_array($capabilities) && count($capabilities) > 0) ? array_keys($capabilities) : array('norole');
            
        return trim($role[0]);
    }
    
    /**
     * Checks if the user is an admin user
     * 
     * @param integer $userId The user id.
     * 
     * @return boolean
     */
    public function userIsAdmin($userId)
    {
        $role = $this->_getUserRole($userId);
        
        if ($role == 'administrator'
            || is_super_admin($userId)
        ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Checks the user access by user level.
     * 
     * @return boolean
     */
    public function checkUserAccess($allowedCapability = false)
    {
        global $current_user;
        //Force user infos
        wp_get_current_user();
        
        $uamOptions = $this->getUserAccessManager()->getAdminOptions();
        
        $role = $this->_getUserRole($current_user->ID);
        $orderedRoles = $this->getRolesOrdered();
        
        if (isset($orderedRoles[$role])
            && $orderedRoles[$role] >= $orderedRoles[$uamOptions['full_access_role']]
            || $role == 'administrator'
            || is_super_admin($current_user->ID)
            || ($allowedCapability && $current_user->has_cap($allowedCapability))
        ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Returns the roles as assoziative array.
     * 
     * @return array
     */
    public function getRolesOrdered()
    {
        $orderedRoles = array(
            'norole' => 0,
            'subscriber' => 1,
            'contributor' => 2,
            'author' => 3,
        	'editor' => 4,
            'administrator' => 5
        );
        
        return $orderedRoles;
    }
    
    /**
     * Registers object that should be handelt by the user access manager.
     * 
     * @param array $object The object which you want to register.
     * 
     * @return boolean
     */
    public function registerPlObject($object)
    {
        if (!isset($object['name'])
            || !isset($object['reference'])
            || !isset($object['getFull'])
            || !isset($object['getFullObjects'])
        ) {
            return false;
        }
        
        $this->plObjects[$object['name']] = $object;
        
        return true;
    }
    
    /**
     * Returns a registerd pluggable object.
     * 
     * @param string $objectName The name of the object which should be returned.
     * 
     * @return array
     */
    public function getPlObject($objectName)
    {
        if (isset($this->plObjects[$objectName])) {
            return $this->plObjects[$objectName];
        }
        
        return array();
    }
    
    /**
     * Returns all registerd pluggable objects.
     * 
     * @return array
     */
    public function getPlObjects()
    {
        return $this->plObjects;
    }
}