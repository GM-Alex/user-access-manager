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
    protected $postUserGroups = array();
    protected $postAccess = array();
    
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
        
        global $wpdb;
        
        $this->postUserGroups[$postId] = array();
        
        $accessGroups = $wpdb->get_results(
        	"SELECT ID
        	FROM " . DB_ACCESSGROUP . "
        	ORDER BY ID", ARRAY_A
        );
        
        if (isset($accessGroups)) {
            foreach ($accessGroups as $accessGroup) {
                $uamUserGroup = new UamUserGroup($accessGroup['ID']);
                
                if ($uamUserGroup->postIsMember($postId)) {
                    $this->postUserGroups[$postId][$accessGroup['ID']] 
                        = $uamUserGroup;
                }
            }
        }
        
        return $this->postUserGroups[$postId];
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
        global $current_user;
        
        if (isset($this->postAccess[$postId])) {
            return $this->postAccess[$postId];  
        } 
        
        $postMembership == $this->getUserGroupsForPost($postId);
        $userAccessManager = new UserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        $curUserdata = get_userdata($current_user->ID);
        
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