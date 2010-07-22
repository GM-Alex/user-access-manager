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
    protected $postUserGroups = array();
    protected $categoryUserGroups = array();
    protected $userUserGroups = array();
    protected $postAccess = array();
    protected $categroyAccess = array();
    protected $userGroups = array(
        'filtered' => array(),
        'noneFiltered' => array(),
    );
    
    /**
     * The consturctor
     * 
     * @param object &$userAccessManager The user access manager object.
     * 
     * @return null
     */
    function __construct(&$userAccessManager)
    {
        $this->userAccessManager = $userAccessManager;
    }
    
    /**
     * Returns the user access manager object.
     * 
     * @return object
     */
    function &getUserAccessManager()
    {
        return $this->userAccessManager;
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
        	&& !$this->checkUserAccess()
        	&& $this->getUserAccessManager()->atAdminPanel
        ) {
            global $current_user;
            
            $userGroupsForUser 
                = $this->getUserGroupsForUser($current_user->ID);
            
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
    function getUserGroups($userGroupId = null, $filter = true)
    {
        if ($filter) {
            $filterAttr = 'filtered';
        } else {
            $filterAttr = 'noneFiltered';
        }
        
        if ($userGroupId == null
            && $this->userGroups[$filterAttr] != array()
        ) {
            return $this->userGroups[$filterAttr];
        } elseif ($userGroupId != null
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
                    = new UamUserGroup(&$this, $userGroupDb['ID']);
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
    function addUserGroup($userGroup)
    {
        $this->getUserGroups();
        $this->userGroups[$userGroup->getId()] = $userGroup;
    }
    
    /**
     * Deletes a user group.
     * 
     * @param integer $userGroupId The user group id which we want to delete.
     * 
     * @return null
     */
    function deleteUserGroup($userGroupId)
    {
        if ($this->getUserGroups($userGroupId) != null) {
            $this->getUserGroups($userGroupId)->delete();
            unset($this->userGroups[$userGroupId]);
        }
    }
    
    /**
     * Returns the user groups for the given object.
     * 
     * @param integer $objectId The id of the object.
     * @param string  $type     The type for what we want the groups.
     * @param boolean $filter   Filter the groups.
     * 
     * @return array
     */
    private function _getUserGroupsForObject($objectId, $type, $filter = true)
    {
        $objectUserGroups = array();

        $userGroups = $this->getUserGroups(null, $filter);
       
        if (isset($userGroups)) {
            foreach ($userGroups as $userGroup) {
                $objectMembership = $userGroup->{$type.'IsMember'}($objectId, true);
                
                if ($objectMembership !== false) {
                    if (isset($objectMembership['byPost'])
                        || isset($objectMembership['byCategory'])
                        || isset($objectMembership['byRole'])
                    ) {
                        $userGroup->setRecursive[$objectId] = $objectMembership;
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
        
        return $objectUserGroups;
    }
    
    /**
     * Returns the user groups of the given post.
     * 
     * @param integer $postId The id of the post from which we want the groups.
     * @param boolean $filter Filter the groups.
     * 
     * @return array
     */
    function getUserGroupsForPost($postId, $filter = true)
    {
        if ($filter) {
            $filterAttr = 'filtered';
        } else {
            $filterAttr = 'noneFiltered';
        }
        
        if (isset($this->postUserGroups[$filterAttr][$postId])) {
            return $this->postUserGroups[$filterAttr][$postId];
        }
        
        $this->postUserGroups[$filterAttr][$postId] 
            = $this->_getUserGroupsForObject($postId, 'post', $filter);
            
        return $this->postUserGroups[$filterAttr][$postId];
    }
    
    /**
     * Returns the user groups of the given category.
     * 
     * @param integer $categoryId The id of the category from which 
     * 							  we want the groups.
     * @param boolean $filter     Filter the groups.
     * 
     * @return array
     */
    function getUserGroupsForCategory($categoryId, $filter = true)
    {
        if ($filter) {
            $filterAttr = 'filtered';
        } else {
            $filterAttr = 'noneFiltered';
        }
        
        if (isset($this->categoryUserGroups[$filterAttr][$categoryId])) {
            return $this->categoryUserGroups[$filterAttr][$categoryId];
        }

        $this->categoryUserGroups[$filterAttr][$categoryId] 
            = $this->_getUserGroupsForObject($categoryId, 'category', $filter);
        
        return $this->categoryUserGroups[$filterAttr][$categoryId];
    }
    
	/**
     * Returns the user groups of the given user.
     * 
     * @param integer $userId The id of the user from which we want the groups.
     * 
     * @return array
     */
    function getUserGroupsForUser($userId)
    {
        if (isset($this->userUserGroups[$userId])) {
            return $this->userUserGroups[$userId];
        }
        
        $this->userUserGroups[$userId] 
            = $this->_getUserGroupsForObject($userId, 'user', false);

        return $this->userUserGroups[$userId];
    }
    
    /**
     * Checks if the current_user has access to the given post.
     * 
     * @param integer $objectId   The id of the object.
     * @param array   $membership The group membership for the object.
     * @param string  $type       The object type which should be checked.
     * 
     * @return boolean
     */
    private function _checkAccess($objectId, $membership, $type = null)
    {
        global $current_user;
        
        $uamOptions = $this->getUserAccessManager()->getAdminOptions();
        
        if ($type == 'post') {
            $post = get_post($objectId);
            $authorId = $post->post_author;
        } else {
            $authorId = -1;
        }
        
        if ($membership == array() 
            || $this->checkUserAccess()
            || $current_user->ID == $authorId
            && $uamOptions['authors_has_access_to_own'] == 'true'
        ) {
            return true;
        }
        
        $curIp = explode(".", $_SERVER['REMOTE_ADDR']);
        
        foreach ($membership as $key => $userGroup) {            
            if ($this->checkUserIp($curIp, $userGroup->getIpRange())
                || $userGroup->userIsMember($current_user->ID)
            ) {
                return true;
            }
            
            if ($this->getUserAccessManager()->atAdminPanel 
                && $userGroup->getWriteAccess() == 'all'
            	|| !$this->getUserAccessManager()->atAdminPanel 
            	&& $userGroup->getReadAccess() == 'all'
            ) {
                unset($membership[$key]);
            }
        }
        
        if ($membership == array()) {
            return true;
        }
        
        return false;
    }
    
	/**
     * Checks if the current_user has access to the given post.
     * 
     * @param integer $postId The id of the post which we want to check.
     * 
     * @return boolean
     */
    function checkAccess($postId)
    {        
        if (isset($this->postAccess[$postId])) {
            return $this->postAccess[$postId];  
        } 

        $postMembership = $this->getUserGroupsForPost($postId, false);
        
        $this->postAccess[$postId] 
            = $this->_checkAccess($postId, $postMembership, 'post');
        
        return $this->postAccess[$postId];
    }
    
    /**
     * Checks if the current_user has access to the given category.
     * 
     * @param integer $categoryId The id of the category which we want to check.
     * 
     * @return boolean
     */
    function checkCategoryAccess($categoryId)
    {        
        if (isset($this->categroyAccess[$categoryId])) {
            return $this->categroyAccess[$categoryId];  
        } 

        $categoryMembership = $this->getUserGroupsForCategory($categoryId, false);
        
        $this->categroyAccess[$categoryId] 
            = $this->_checkAccess($categoryId, $categoryMembership);
                
        return $this->categroyAccess[$categoryId];   
    }
    
    /**
     * Checks if the given ip matches with the range.
     * 
     * @param string $curIp    The ip of the current user.
     * @param array  $ipRanges The ip ranges.
     * 
     * @return boolean
     */
    function checkUserIp($curIp, $ipRanges)
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
     * Checks the user access by user level.
     * 
     * @return boolean
     */
    function checkUserAccess()
    {
        global $current_user, $wpdb;

        $uamOptions = $this->getUserAccessManager()->getAdminOptions();
        $curUserdata = get_userdata($current_user->ID);
            
        if (!isset($curUserdata->user_level)) {
            $curUserdata->user_level = null;
        }
        
        if (isset($curUserdata->{$wpdb->prefix . "capabilities"})) {
            $capabilities = $curUserdata->{$wpdb->prefix . "capabilities"};
        } else {
            $capabilities = null;
        }
        
        $role  = is_array($capabilities) ? 
            array_keys($capabilities) : 'norole';
        $role = trim($role[0]);
        
        $orderedRoles = $this->getRolesOrdered();
        
        if ($orderedRoles[$role] >= $orderedRoles[$uamOptions['full_access_role']]
            || $role == 'administrator'
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
    function getRolesOrdered()
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
}