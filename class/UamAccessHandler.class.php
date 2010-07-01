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
    protected $userGroups = array();
    
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
     * Returns all user groups or one requested by the user group id.
     * 
     * @param integer $userGroupId The id of the single user group 
     * 							   which should be returned.
     * 
     * @return array|object
     */
    function &getUserGroups($userGroupId = null)
    {
        if ($userGroupId == null
            && $this->userGroups != array()
        ) {
            return $this->userGroups;
        } elseif ($userGroupId != null
                  && $this->userGroups != array()
        ) {
            if (isset($this->userGroups[$userGroupId])) {
                return $this->userGroups[$userGroupId];
            } else {
                return null;
            }
        }
        
        $this->userGroups = array();
        
        global $wpdb;

        $userGroupsDb = $wpdb->get_results(
        	"SELECT ID
        	FROM " . DB_ACCESSGROUP . "
        	ORDER BY ID", ARRAY_A
        );
        
        if (isset($userGroupsDb)) {
            foreach ($userGroupsDb as $userGroupDb) {
                $this->userGroups[$userGroupDb['ID']] 
                    = new UamUserGroup(&$this, $userGroupDb['ID']);
            }
        }
        
        if ($userGroupId == null) {
            return $this->userGroups;
        } elseif ($userGroupId != null) {
            if (isset($this->userGroups[$userGroupId])) {
                return $this->userGroups[$userGroupId];
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
     * Returns the user groups of the given post.
     * 
     * @param integer $postId The id of the post from which we want the groups.
     * 
     * @return array
     */
    function getUserGroupsForPost($postId)
    {
        if (isset($this->postUserGroups[$postId])) {
            return $this->postUserGroups[$postId];
        }
        
        $this->postUserGroups[$postId] = array();

        $userGroups = $this->getUserGroups();
       
        if (isset($userGroups)) {
            foreach ($userGroups as $userGroup) {
                $postMembership = $userGroup->postIsMember($postId, true);
                
                if ($postMembership !== false) {
                    if (is_array($postMembership)) {
                        $userGroup->setRecursive = $postMembership;
                    }

                    $this->postUserGroups[$postId][$userGroup->getId()] 
                        = $userGroup;
                }
            }
        }

        return $this->postUserGroups[$postId];
    }
    
    /**
     * Returns the user groups of the given category.
     * 
     * @param integer $categoryId The id of the category from which 
     * 							  we want the groups.
     * 
     * @return array
     */
    function getUserGroupsForCategory($categoryId)
    {
        if (isset($this->categoryUserGroups[$categoryId])) {
            return $this->categoryUserGroups[$categoryId];
        }
        
        $this->categoryUserGroups[$categoryId] = array();
        
        $userGroups = $this->getUserGroups();
        
        if (isset($userGroups)) {
            foreach ($userGroups as $userGroup) {
                $categoryMembership = $userGroup->categoryIsMember($categoryId, true);
                
                if ($categoryMembership !== false) {
                    if (is_array($categoryMembership)) {
                        $userGroup->setRecursive = $categoryMembership;
                    }

                    $this->categoryUserGroups[$categoryId][$userGroup->getId()] 
                        = $userGroup;
                }
            }
        }
        
        return $this->categoryUserGroups[$categoryId];
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
        
        $this->userUserGroups[$userId] = array();
        
        $userGroups = $this->getUserGroups();
        
        if (isset($userGroups)) {
            foreach ($userGroups as $userGroup) {
                if ($userGroup->userIsMember($userId)) {
                    $this->userUserGroups[$userId][$userGroup->getId()] 
                        = $userGroup;
                }
            }
        }

        return $this->userUserGroups[$userId];
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

        global $current_user;
        $postMembership = $this->getUserGroupsForPost($postId);
     
        $userAccessManager = new UserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        $curUserdata = get_userdata($current_user->ID);
        
        if (!isset($curUserdata->user_level)) {
            $curUserdata->user_level = null;
        }
        
        if ($postMembership == array() 
            || $curUserdata->user_level >= $uamOptions['full_access_level']
        ) {
            $this->postAccess[$postId] = true;
        } else {
            if (is_user_logged_in()) {
                $curIp = explode(".", $_SERVER['REMOTE_ADDR']);
                
                foreach ($postMembership as $userGroup) {
                    if ($this->checkUserIp($curIp, $userGroup->getIpRange())
                        || $userGroup->userIsMember($current_user->ID)
                    ) {
                        $this->postAccess[$postId] = true;
                        break;
                    }
                }
            } else {
                $this->postAccess[$postId] = false;
            }
        }
        
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

        global $current_user;
        $categoryMembership = $this->getUserGroupsForCategory($categoryId);
     
        $userAccessManager = new UserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        $curUserdata = get_userdata($current_user->ID);
        
        if (!isset($curUserdata->user_level)) {
            $curUserdata->user_level = null;
        }
        
        if ($categoryMembership == array() 
            || $curUserdata->user_level >= $uamOptions['full_access_level']
        ) {
            $this->categroyAccess[$categoryId] = true;
        } else {
            if (is_user_logged_in()) {
                $curIp = explode(".", $_SERVER['REMOTE_ADDR']);
                
                foreach ($categoryMembership as $userGroup) {
                    if ($this->checkUserIp($curIp, $userGroup->getIpRange())
                        || $userGroup->userIsMember($current_user->ID)
                    ) {
                        $this->categroyAccess[$categoryId] = true;
                        break;
                    }
                }
            } else {
                $this->categroyAccess[$categoryId] = false;
            }
        }
        
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
}