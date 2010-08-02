<?php
/**
 * UamUserGroup.class.php
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
 * The user group class.
 * 
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */

class UamUserGroup
{
    protected $accessHandler = null;
    protected $id = null;
    protected $groupName = null;
    protected $groupDesc = null;
    protected $readAccess = null;
    protected $writeAccess = null;
    protected $ipRange = null;
    protected $roles = array();
    protected $users = array(
    	'real' => -1,
        'full' => -1
    );
    protected $singleUsers = array();
    private $_assignedUsers = null;
    protected $categories = array(
    	'real' => -1,
        'full' => -1
    );
    protected $singleCategories = array();
    private $_assignedCategories = null;
    protected $posts = array(
    	'real' => -1,
        'full' => -1
    );
    protected $singlePosts = array();
    private $_assignedPosts = null;
    protected $pages = array(
    	'real' => -1,
        'full' => -1
    );
    protected $files = array(
    	'real' => -1,
        'full' => -1
    );
    protected $plObjects = array();
    protected $singlePlObjects = null;
    private $_assignedPlObjects = null;
    
    /**
     * Consturtor
     * 
     * @param object  &$uamAccessHandler The access handler object.
     * @param integer $id                The id of the user group.
     * 
     * @return null
     */
    function __construct(&$uamAccessHandler, $id = null)
    {
        $this->accessHandler = $uamAccessHandler;
        
        //Create default values for the pluggable objects.
        $plObjects = $uamAccessHandler->getPlObjects();
        
        foreach ($plObjects as $objectName => $plObject) {
            $this->plObjects[$objectName] = array(
            	'real' => -1,
                'full' => -1
            );
        }

        if ($id !== null) {
            global $wpdb;
            
            $this->id = $id;
            
            $dbUsergroup = $wpdb->get_row(
            	"SELECT *
    			FROM " . DB_ACCESSGROUP . "
    			WHERE ID = " . $this->id . "
    			LIMIT 1", 
                ARRAY_A
            );
            
            $this->groupName = $dbUsergroup['groupname'];
            $this->groupDesc = $dbUsergroup['groupdesc'];
            $this->readAccess = $dbUsergroup['read_access'];
            $this->writeAccess = $dbUsergroup['write_access'];
            $this->ipRange = $dbUsergroup['ip_range'];
        }
    }
    
    /**
     * Returns the user access handler object.
     * 
     * @return object
     */
    function &getAccessHandler()
    {
        return $this->accessHandler;
    }
    
    /**
     * Deletes the user group.
     * 
     * @return null
     */
    function delete()
    {
        if ($this->id == null) {
            return false;
        }
        
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP . " 
        	WHERE ID = $this->id LIMIT 1"
        );
        
        $this->_deleteRolesFromDb();
        $this->_deletePostByTypeFromDb('all');
        $this->_deleteCategoriesFromDb();
        $this->_deleteUsersFromDb();
    }
    
    /**
     * Saves the user group.
     * 
     * @return null;
     */
    function save()
    {
        global $wpdb;
        
        if ($this->id == null) {
            $wpdb->query(
            	"INSERT INTO " . DB_ACCESSGROUP . " (
            		ID, 
            		groupname, 
            		groupdesc, 
            		read_access, 
            		write_access, 
            		ip_range
            	) 
            	VALUES (
            		NULL, 
            		'" . $this->groupName . "', 
            		'" . $this->groupDesc . "', 
            		'" . $this->readAccess . "', 
            		'" . $this->writeAccess . "', 
            		'" . $this->ipRange . "'
            	)"
            );
            
            $this->id = $wpdb->insert_id;
        } else {
            $wpdb->query(
            	"UPDATE " . DB_ACCESSGROUP . "
    			SET groupname = '" . $this->groupName . "', 
    				groupdesc = '" . $this->groupDesc . "', 
    				read_access = '" . $this->readAccess . "', 
    				write_access = '" . $this->writeAccess . "', 
    				ip_range = '" . $this->ipRange . "'
    			WHERE ID = " . $this->id
            );
            
            $this->getPosts();
            $this->getPages();
            $this->getFiles();
            $this->getRoles();
            $this->getCategories();
            $this->getUsers();
            
            $this->_deletePostByTypeFromDb('all');
            $this->_deleteRolesFromDb();
            $this->_deleteCategoriesFromDb();
            $this->_deleteUsersFromDb();
            foreach ($this->getPlObjects() as $objectName => $objects) {
                $this->_deletePlObjectFromDb($objectName);
            }
        }
        
        foreach ($this->getUsers() as $userKey => $user) {
            $wpdb->query(
            	"INSERT INTO " . DB_ACCESSGROUP_TO_USER . " (
            		group_id, 
            		user_id
            	) 
            	VALUES(
            		'" . $this->id . "', 
            		'" . $userKey . "'
            	)"
            );
        }
        
        foreach ($this->getCategories() as $categoryKey => $category) {
            $wpdb->query(
            	"INSERT INTO " . DB_ACCESSGROUP_TO_CATEGORY . " (
            		group_id, 
            		category_id
            	) 
            	VALUES(
            		'" . $this->id . "', 
            		'" . $categoryKey . "'
            	)"
            );
        }
        
        foreach ($this->getRoles() as $roleKey => $role) {
            $wpdb->query(
            	"INSERT INTO " . DB_ACCESSGROUP_TO_ROLE . " (
            		group_id, 
            		role_name
            	) 
            	VALUES(
            		'" . $this->id . "', 
            		'" . $roleKey . "'
            	)"
            );
        }
        
        $allPosts 
            = array_merge($this->getPosts(), $this->getPages(), $this->getFiles());
            
        foreach ($allPosts as $post) {
            $wpdb->query(
            	"INSERT INTO " . DB_ACCESSGROUP_TO_POST . " (
            		group_id, 
            		post_id
            	) 
            	VALUES(
            		'" . $this->id . "', 
            		'" . $post->ID . "'
            	)"
            );
        }

        foreach ($this->getPlObjects() as $objectName => $objects) {
            foreach ($objects as $objectKey => $object) {
                $wpdb->query(
                	"INSERT INTO " . DB_ACCESSGROUP_TO_OBJECT . " (
                		group_id, 
                		object_id,
                		object_type
                	) 
                	VALUES(
                		'" . $this->id . "', 
                		'" . $objectKey . "',
                		'" . $objectName . "'
                	)"
                );
            }
        }
    }
    
    
    /*
     * Primary values.
     */
    
    /**
     * Returns the group id.
     * 
     * @return integer
     */
    function getId()
    {
        return $this->id;
    }
    
    /**
     * Returns the group name.
     * 
     * @return string
     */
    function getGroupName()
    {
        return $this->groupName;
    }
    
    /**
     * Sets the group name.
     * 
     * @param string $groupName The new group name.
     * 
     * @return null
     */
    function setGroupName($groupName)
    {
        $this->groupName = $groupName;
    }
    
    /**
     * Returns the group description.
     * 
     * @return string
     */
    function getGroupDesc()
    {
        return $this->groupDesc;
    }
    
    /**
     * Sets the group description.
     * 
     * @param string $groupDesc The new group description.
     * 
     * @return null
     */
    function setGroupDesc($groupDesc)
    {
        $this->groupDesc = $groupDesc;
    }
    
    /**
     * Returns the read access.
     * 
     * @return string
     */
    function getReadAccess()
    {
        return $this->readAccess;
    }
    
    /**
     * Sets the read access.
     * 
     * @param string $readAccess The read access.
     * 
     * @return null
     */
    function setReadAccess($readAccess)
    {
        $this->readAccess = $readAccess;
    }
    
    /**
     * Returns the write access.
     * 
     * @return string
     */
    function getWriteAccess()
    {
        return $this->writeAccess;
    }
    
    /**
     * Sets the write access.
     * 
     * @param string $writeAccess The wirte access.
     * 
     * @return null
     */
    function setWriteAccess($writeAccess)
    {
        $this->writeAccess = $writeAccess;
    }
    
    /**
     * Returns the ip range.
     * 
     * @param string $type The return type.
     * 
     * @return array
     */
    function getIpRange($type = null)
    {
        if ($type == 'string') {
            return $this->ipRange;
        }
        
        $ipRange = explode(';', $this->ipRange);
        
        if ($ipRange[0] == null) {
            return null;
        }
        
        return $ipRange;
    }
    
    /**
     * Sets the ip range.
     * 
     * @param string $ipRange The new ip range.
     * 
     * @return null
     */
    function setIpRange($ipRange)
    {
        if (is_array($ipRange)) {
            $ipRange = implode(';', $ipRange);
        }
        
        $this->ipRange = $ipRange;
    }
    
    
    /*
     * Group roles functions.
     */
    
    /**
     * Returns the roles in the group
     * 
     * @return array
     */
    function getRoles()
    {
        if ($this->id == null) {
            return array();
        }
        
        if ($this->roles != array()) {
            return $this->roles;
        }
        
        global $wpdb;
        
        $dbRoles = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_ROLE . "
			WHERE group_id = " . $this->id, 
            ARRAY_A
        );
        
        if (isset($dbRoles)) {
            foreach ($dbRoles as $dbRole) {
                $this->roles[trim($dbRole['role_name'])] = $dbRole;
            }
        }
        
        return $this->roles;
    }
    
    /**
     * Adds a role to the role group.
     * 
     * @param integer $roleName The role name which should be added.
     * 
     * @return null
     */
    function addRole($roleName)
    {
        $this->getRoles();
        
        /**
         * $this->roles[$roleName] = &get_role($roleName);
         * Makes trouble, but why? 
         * Error: "Notice: Only variable references should be returned by reference"
         */

        $this->roles[$roleName] = array('role_name' => $roleName);
    }
    
    /**
     * Removes a role from the role group.
     * 
     * @param integer $roleName The role name which should be removed.
     * 
     * @return null
     */
    function removeRole($roleName)
    {        
        $this->getRoles();
        unset($this->roles[$roleName]);
    }
    
    /**
     * Unsets the roles.
     * 
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    function unsetRoles($plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deleteRolesFromDb();
        }

        $this->roles = array();
    }
    
    /**
     * Removes all roles from the user group.
     * 
     * @return null
     */
    private function _deleteRolesFromDb()
    {
        if ($this->id == null) {
            return false;
        }
        
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_ROLE . " 
        	WHERE group_id = ".$this->id
        );
    }
    
    
    /*
     * Group users functions.
     */
    
    /**
     * Returns the assigned users.
     * 
     * @return array
     */
    private function _getAssignedUsers()
    {
        if ($this->_assignedUsers !== null) {
            return $this->_assignedUsers;
        }

        global $wpdb;
        
        $dbUsers = $wpdb->get_results(
        	"SELECT user_id
			FROM " . DB_ACCESSGROUP_TO_USER . "
			WHERE group_id = " . $this->id
        );
        
        $this->_assignedUsers = array();
        
        foreach ($dbUsers as $dbUser) {
            $this->_assignedUsers[$dbUser->user_id] = $dbUser->user_id;
        }
        
        return $this->_assignedUsers;
    }
    
	/**
     * Checks it the user is assigned to the group.
     * 
     * @param integer $userId The user id.
     * 
     * @return boolean
     */
    private function _isUserAssignedToGroup($userId)
    {
        if (array_key_exists($userId, $this->_getAssignedUsers())) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Returns a single user.
     * 
     * @param object $user The user.
     * @param string $type The return type. Can be real or full.
     * 
     * @return object
     */
    private function _getSingleUser($user, $type)
    {
        if (!isset($user->ID)) {
            return null;
        }
        
        if (isset($this->singleUsers[$user->ID])) {
            return $this->singleUsers[$user->ID];
        }
        
        $isRecursiveMember = array();
        
        if ($type == 'full') {
            global $wpdb;
            
            $curUserdata = get_userdata($user->ID);
            
            if (isset($curUserdata->{$wpdb->prefix . "capabilities"})) {
                $capabilities = $curUserdata->{$wpdb->prefix . "capabilities"};
            } else {
                $capabilities = null;
            }
            
            $role  = is_array($capabilities) ? 
                array_keys($capabilities) : array('norole');
            
            if (array_key_exists($role[0], $this->getRoles())
            ) {
                $isRecursiveMember
                    = array('byRole' => array());
                $isRecursiveMember['byRole'][] 
                    = $role[0];
            }
        }

        if ($this->_isUserAssignedToGroup($user->ID)
            || $isRecursiveMember != array()
        ) {
            if ($isRecursiveMember != array()) {
                $user->recursiveMember = $isRecursiveMember;
            }
            
            $this->singleUsers[$user->ID] = $user;
        } else {
            $this->singleUsers[$user->ID] = null;
        }

        return $this->singleUsers[$user->ID];
    }
    
    /**
     * Returns the users in the group
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getUsers($type = 'real')
    {
        if ($this->id == null) {
            return array();
        }
        
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return array();
        }
        
        if ($this->users[$type] != -1) {
            return $this->users[$type];
        } else {
            $this->users[$type] = array();
        }
        
        global $wpdb;
        
        if ($type == 'real') {
            $dbUsers = $wpdb->get_results(
            	"SELECT user_id as ID
    			FROM " . DB_ACCESSGROUP_TO_USER . "
    			WHERE group_id = " . $this->id
            );
        } elseif ($type == 'full') {
            $dbUsers = $wpdb->get_results(
            	"SELECT ID, user_nicename
    			FROM ".$wpdb->users
            );
        }
            
        if (isset($dbUsers)) {
            foreach ($dbUsers as $dbUser) {
                $user = $this->_getSingleUser($dbUser, $type);
                
                if ($user !== null) {
                    $this->users[$type][$user->ID] = $user;
                }
            }
        }
        
        return $this->users[$type];
    }
    
    /**
     * Adds a user to the user group.
     * 
     * @param integer $userId The user id which should be added.
     * 
     * @return null
     */
    function addUser($userId)
    {
        $this->getUsers();
        $this->users['real'][$userId] = get_userdata($userId);
        $this->users['full'] = -1;
    }
    
    /**
     * Removes a user from the user group.
     * 
     * @param integer $userId The user id which should be removed.
     * 
     * @return null
     */
    function removeUser($userId)
    {
        $this->getUsers();
        unset($this->users['real'][$userId]);
        $this->users['full'] = -1;
    }
    
    /**
     * Unsets the users.
     * 
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    function unsetUsers($plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deleteUsersFromDb();
        }

        $this->users = array(
    		'real' => array(),
        	'full' => array(),
        );
    }
    
    /**
     * Removes all users from the user group.
     * 
     * @return null
     */
    private function _deleteUsersFromDb()
    {
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
        	WHERE group_id = ".$this->id
        );
    }
    
    /**
     * Checks if the given user is a member of the group.
     * 
     * @param interger $userId   The id of the user which should be checked.
     * @param boolean  $withInfo If true then we return additional infos.
     * 
     * @return boolean
     */
    function userIsMember($userId, $withInfo = false)
    {
        $user->ID = $userId;
        $user = $this->_getSingleUser($user, 'full');
        
        if ($user !== null) {
            if (isset($user->recursiveMember)
                && $withInfo
            ) {
                return $user->recursiveMember;
            }
            
            return true;
        }
        
        return false;
    }
    
    
    /*
     * Group categories functions.
     */
    
    /**
     * Returns the assigned categories.
     * 
     * @return array
     */
    private function _getAssignedCategories()
    {
        if ($this->_assignedCategories !== null) {
            return $this->_assignedCategories;
        }

        global $wpdb;
        
        $dbCategories = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_CATEGORY . "
			WHERE group_id = " . $this->id
        );
        
        $this->_assignedCategories = array();
        
        foreach ($dbCategories as $dbCategory) {
            $this->_assignedCategories[$dbCategory->category_id] 
                = $dbCategory->category_id;
        }
        
        return $this->_assignedCategories;
    }
    
	/**
     * Checks it the category is assigned to the group.
     * 
     * @param integer $categoryId The category id.
     * 
     * @return boolean
     */
    private function _isCategoryAssignedToGroup($categoryId)
    {
        if (array_key_exists($categoryId, $this->_getAssignedCategories())) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Returns a single category.
     * 
     * @param object $category The category.
     * @param string $type     The return type. Can be real or full.
     * 
     * @return object
     */
    private function _getSingleCategory($category, $type)
    {
        if (!isset($category->term_id)) {
            return null;
        }
        
        if (isset($this->singleCategories[$category->term_id])) {
            return $this->singleCategories[$category->term_id];
        }
        
        $isRecursiveMember = array();
        
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        if ($uamOptions['lock_recursive'] == 'true'
            && $type == 'full'
        ) {
            if ($category->parent != 0) {
                $parentCategory = get_category($category->parent);
                
                $parentCategory = $this->_getSingleCategory(
                    $parentCategory,
                    $type
                );

                if ($parentCategory !== null) {
                    if (isset($parentCategory->recursiveMember)) {
                        $isRecursiveMember['byCategory'][]
                            = $parentCategory;
                    } else {
                        $isRecursiveMember['byCategory'][]
                            = $parentCategory->term_id;
                    }
                }
            }
        }

        if ($this->_isCategoryAssignedToGroup($category->term_id)
            || $isRecursiveMember != array()
        ) {
            if ($isRecursiveMember != array()) {
                $category->recursiveMember = $isRecursiveMember;
            }
            
            $this->singleCategories[$category->term_id] = $category;
        } else {
            $this->singleCategories[$category->term_id] = null;
        }

        return $this->singleCategories[$category->term_id];
    }
    
    /**
     * Returns the categories in the group
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getCategories($type = 'real')
    {
        if ($this->id == null) {
            return array();
        }
        
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return array();
        }
        
        if ($this->categories[$type] != -1) {
            return $this->categories[$type];
        } else {
            $this->categories[$type] = array();
        }
        
        global $wpdb;
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        $dbCategories = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_CATEGORY . "
			WHERE group_id = " . $this->id,
            ARRAY_A
        );
        
        if (isset($dbCategories)) {
            foreach ($dbCategories as $dbCategorie) {
                $category = get_category($dbCategorie['category_id']);
                
                if ($category != null) {
                    if ($uamOptions['lock_recursive'] == 'true' 
                        && $type == 'full'
                    ) {
                        //We have to remove the filter to get all categories
                        $removeSucc = remove_filter(
                        	'get_terms', 
                            array(
                                $this->getAccessHandler()->getUserAccessManager(), 
                                'showCategory'
                            )
                        );
                        
                        if ($removeSucc) {
                            $args = array(
                                'child_of' => $category->term_id,
                                'hide_empty' => false
                            );
                            
                            $categoryChilds = get_categories($args);
                            
                            add_filter(
                            	'get_terms', 
                                array(&$userAccessManager, 'showCategory')
                            );
                            
                            foreach ($categoryChilds as $categoryChild) {
                                $categoryChild->recursiveMember 
                                    = array('byCategory' => array());
                                $categoryChild->recursiveMember['byCategory'][] 
                                    = $category->term_id;
                                $this->categories[$type][$categoryChild->term_id] 
                                    = $categoryChild;
                            }
                        }
                    }
                
                    $this->categories[$type][$category->term_id] = $category;
                }
            }
        }
        
        return $this->categories[$type];
    }
    
    /**
     * Adds a category to the user group.
     * 
     * @param integer $categoryId The category id which should be added.
     * 
     * @return null
     */
    function addCategory($categoryId)
    {
        $this->getCategories();
        $this->categories['real'][$categoryId] = get_category($categoryId);
        $this->categories['full'] = -1;
    }
    
    /**
     * Removes a category from the user group.
     * 
     * @param integer $categoryId The category id which should be removed.
     * 
     * @return null
     */
    function removeCategory($categoryId)
    {
        $this->getCategories();
        unset($this->categories['real'][$categoryId]);
        $this->categories['full'] = -1;
    }
    
    /**
     * Unsets the categories.
     * 
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    function unsetCategories($plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deleteCategoriesFromDb();
        }
        
        $this->categories = array(
    		'real' => array(),
        	'full' => array(),
        );
    }
    
    /**
     * Removes all categories from the user group.
     * 
     * @return null
     */
    private function _deleteCategoriesFromDb()
    {
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " 
        	WHERE group_id = ".$this->id
        );
    }
    
	/**
     * Checks if the given post is a member of the group.
     * 
     * @param interger $categoryId The id of the category which should be checked.
     * @param boolean  $withInfo   If true then we return additional infos.
     * 
     * @return boolean
     */
    function categoryIsMember($categoryId, $withInfo = false)
    {
        $category = get_category($categoryId);
        $category = $this->_getSingleCategory($category, 'full');
        
        if ($category !== null) {
            if (isset($category->recursiveMember)
                && $withInfo
            ) {
                return $category->recursiveMember;
            }
            
            return true;
        }
        
        return false;
    }
    
    
    /*
     * Group posts functions.
     */
    
    /**
     * Returns the assigned posts.
     * 
     * @return array
     */
    private function _getAssignedPosts()
    {
        if ($this->_assignedPosts !== null) {
            return $this->_assignedPosts;
        }

        global $wpdb;
        
        $dbPosts = $wpdb->get_results(
        	"SELECT post_id
			FROM " . DB_ACCESSGROUP_TO_POST . "
			WHERE group_id = " . $this->id
        );
        
        $this->_assignedPosts = array();
        
        foreach ($dbPosts as $dbPost) {
            $this->_assignedPosts[$dbPost->post_id] = $dbPost->post_id;
        }
        
        return $this->_assignedPosts;
    }
    
    /**
     * Checks it the post is assigned to the group.
     * 
     * @param integer $postId The post id.
     * 
     * @return boolean
     */
    private function _isPostAssignedToGroup($postId)
    {
        if (array_key_exists($postId, $this->_getAssignedPosts())) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Returns the membership of a single post.
     * 
     * @param object $post     The post object.
     * @param string $type     The return type.
     * @param string $postType The post type needed for the intern representation.
     * 
     * @return object
     */
    function _getSinglePost($post, $type, $postType)
    {
        if (!isset($post->ID)) {
            return null;
        }
        
        if (isset($this->singlePosts[$post->ID])) {
            return $this->singlePosts[$post->ID];
        }
        
        $isRecursiveMember = array();
        
        $userAccessManager = &$this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        if ($type == 'full') {
            foreach ($this->getCategories('full') as $category) {
                if (in_category($category->cat_ID, $post->ID)) { 
                    $isRecursiveMember['byCategory'][] = $category->cat_ID;
                    //break;
                }
            }

            if (($postType == 'page'
            	|| $postType == 'file')
            	&& $uamOptions['lock_recursive'] == 'true'
            ) {
                if ($post->post_parent != 0) {
                    $parentPost = get_post($post->post_parent);
                    
                    $parentPost = $this->_getSinglePost(
                        $parentPost,
                        $type, 
                        $parentPost->post_type
                    );

                    if ($parentPost !== null) {
                        if (isset($parentPost->recursiveMember)) {
                            $isRecursiveMember['byPost'][]
                                = $parentPost;
                        } else {
                            $isRecursiveMember['byPost'][]
                                = $parentPost->ID;
                        }
                    }
                }
            }
        }

        if ($this->_isPostAssignedToGroup($post->ID)
            || $isRecursiveMember != array()
        ) {
            if ($isRecursiveMember != array()) {
                $post->recursiveMember = $isRecursiveMember;
            }
            $this->singlePosts[$post->ID] = $post;
        } else {
            $this->singlePosts[$post->ID] = null;
        }

        return $this->singlePosts[$post->ID];
    }
    
    /**
     * Returns the posts by the given type in the group
     * 
     * @param string $postType The type of the post.
     * @param string $type     The return type. Can be real or full.
     * 
     * @return array
     */
    private function _getPostByType($postType, $type)
    {
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return array();
        }
        
        if ($postType == 'file') {
            $wpType = 'attachment';
        } else {
            $wpType = $postType;
        }
        
        if ($this->{$postType.'s'}[$type] != -1) {
            return $this->{$postType.'s'}[$type];
        } else {
            $this->{$postType.'s'}[$type] = array();
        }
        
        global $wpdb;

        $posts = $wpdb->get_results(
        	"SELECT ID, post_parent
			FROM $wpdb->posts
			WHERE post_type = '".$wpType."'"
        );
        
        if (isset($posts)) {
            foreach ($posts as $post) {
                $post = $this->_getSinglePost($post, $type, $postType);
                
                if ($post !== null) {
                    $this->{$postType.'s'}[$type][$post->ID] = $post;
                }
            }
        }
        
        return $this->{$postType.'s'}[$type];
    }
    
    /**
     * Removes all post of all types from the user group.
     * 
     * @param string $postType The type which should be deleted.
     * 
     * @return null;
     */
    private function _deletePostByTypeFromDb($postType)
    {
        if ($this->id == null) {
            return false;
        }
        
        global $wpdb;
        
        if ($postType == 'all') {
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
            	WHERE group_id = ".$this->id
            );
        } else {
            if ($type == 'post') {
                $curPostTypes = $this->getPosts();
            } elseif ($type == 'page') {
                $curPostTypes = $this->getPages();
            } elseif ($type == 'file') {
                $curPostTypes = $this->getFiles();
            }
            
            foreach ($curPostTypes as $id => $post) {
                $wpdb->query(
                	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
                	WHERE group_id = ".$this->id."
                        AND post_id = ".$id."
                    LIMIT 1"
                );
            }
        }
    }
    
    /**
     * Returns the pages in the group
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getPosts($type = 'real')
    {
        return $this->_getPostByType('post', $type);
    }
    
    /**
     * Adds a post to the user group.
     * 
     * @param integer $postId The post id which should be added.
     * 
     * @return null
     */
    function addPost($postId)
    {
        $this->getPosts();
        $this->posts['real'][$postId] = get_post($postId);
        $this->posts['full'] = -1;
    }
    
    /**
     * Removes a post from the user group.
     * 
     * @param integer $postId The post id which should be removed.
     * 
     * @return null
     */
    function removePost($postId)
    {
        $this->getPosts();
        unset($this->posts['real'][$postId]);
        $this->posts['full'] = -1;
    }
    
    /**
     * Unsets the posts.
     * 
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    function unsetPosts($plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deletePostByTypeFromDb('post');
        }
        
        $this->posts  = array(
    		'real' => array(),
        	'full' => array(),
        );;
    }
    
    /**
     * Returns the pages in the group.
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getPages($type = 'real')
    {
        return $this->_getPostByType('page', $type);
    }
    
    /**
     * Adds a page to the page group.
     * 
     * @param integer $pageID The page id which should be added.
     * 
     * @return null
     */
    function addPage($pageID)
    {
        $this->getPages();
        $this->pages['real'][$pageID] = get_post($pageID);
        $this->pages['full'] = -1;
    }
    
    /**
     * Removes a page from the page group.
     * 
     * @param integer $pageID The page id which should be removed.
     * 
     * @return null
     */
    function removePage($pageID)
    {
        $this->getPages();
        unset($this->pages['real'][$pageID]);
        $this->pages['full'] = -1;
    }
    
    /**
     * Unsets the pages.
     * 
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    function unsetPages($plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deletePostByTypeFromDb('page');
        }
        
        $this->pages  = array(
    		'real' => array(),
        	'full' => array(),
        );;
    }
    
    /**
     * Returns the files in the group
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getFiles($type = 'real')
    {
        return $this->_getPostByType('file', $type);
    }
    
    /**
     * Adds a file to the file group.
     * 
     * @param integer $fileID The file id which should be added.
     * 
     * @return null
     */
    function addFile($fileID)
    {
        $this->getFiles();
        $this->files['real'][$fileID] = get_post($fileID);
        $this->files['full'] = -1;
    }
    
    /**
     * Removes a file from the file group.
     * 
     * @param integer $fileID The file id which should be removed.
     * 
     * @return null
     */
    function removeFile($fileID)
    {
        $this->getFiles();
        unset($this->files['real'][$fileID]);
        $this->files['full'] = -1;
    }
    
    /**
     * Unsets the files.
     * 
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    function unsetFiles($plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deletePostByTypeFromDb('file');
        }
        
        $this->files = array(
    		'real' => array(),
        	'full' => array(),
        );
    }
    
    /**
     * Checks if the given post is a member of the group.
     * 
     * @param interger $postId   The id of the post which should be checked.
     * @param boolean  $withInfo If true then we return additional infos.
     * 
     * @return boolean
     */
    function postIsMember($postId, $withInfo = false)
    {
        $post = get_post($postId);
        
        if ($post->post_type == 'attachment') {
            $postType = 'file';
        } else {
            $postType = $post->post_type;
        }
        
        $post = $this->_getSinglePost($post, 'full', $postType);
        
        if ($post !== null) {
            if (isset($post->recursiveMember)
                && $withInfo
            ) {
                return $post->recursiveMember;
            }
            
            return true;
        }
        
        return false;
    }
    
    /*
     * Group pluggable objects functions.
     */
    
    /**
     * Returns the assigned pluggable object.
     * 
     * @param string $objectName The name of the object.
     * 
     * @return array
     */
    private function _getAssignedPlObjects($objectName)
    {
        if (isset($this->_assignedPlObjects[$objectName])) {
            return $this->_assignedPlObjects[$objectName];
        }

        global $wpdb;
        
        $dbObjects = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_OBJECT . "
			WHERE group_id = " . $this->id . "
                AND object_type = '" . $objectName ."'"
        );
        
        $this->_assignedPlObjects[$objectName] = array();
        
        foreach ($dbObjects as $dbObject) {
            $this->_assignedPlObjects[$objectName][$dbObject->object_id] 
                = $dbObject->object_id;
        }
        
        return $this->_assignedPlObjects[$objectName];
    }
    
	/**
     * Checks it the category is assigned to the group.
     * 
     * @param string  $objectName The name of the object.
     * @param integer $objectId   The object id.
     * 
     * @return boolean
     */
    private function _isPlObjectAssignedToGroup($objectName, $objectId)
    {
        if (array_key_exists($objectId, $this->_getAssignedPlObjects($objectName))) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Returns a single pluggable object.
     * 
     * @param object  $objectName The pluggable object.
     * @param integer $objectId   The object id.
     * @param string  $type       The return type. Can be real or full.
     * 
     * @return object
     */
    private function _getSinglePlObject($objectName, $objectId, $type = 'full')
    {
        if (!isset($objectId)) {
            return null;
        }
        
        if (isset($this->singlePlObjects[$objectName][$objectId])) {
            return $this->singlePlObjects[$objectName][$objectId];
        }
        
        //TODO recursive member
        $isRecursiveMember = array();
        
        $userAccessManager = &$this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        $plObjectStuff = $this->getAccessHandler()->getPlObject($objectName);

        if ($type == 'full') {
            $plObject
                = $plObjectStuff['reference']->{$plObjectStuff['getFull']}(
                    $objectName
                );
        } else {
            $plObject
                = $plObjectStuff['reference']->{$plObjectStuff['getObject']}(
                    $objectId
                );
        }

        if ($this->_isPostAssignedToGroup($objectId)
            || $isRecursiveMember != array()
        ) {
            if ($isRecursiveMember != array()) {
                $plObject->recursiveMember = $isRecursiveMember;
            }
            $this->singlePlObjects[$objectName][$objectId] = $plObject;
        } else {
            $this->singlePlObjects[$objectName][$objectId] = null;
        }

        return $this->singlePlObjects[$objectName][$objectId];
    }
    
    /**
     * Adds a pluggable object to the user group.
     * 
     * @param string  $objectName The name of the object.
     * @param integer $objectId   The object id which should be added.
     * 
     * @return null
     */
    function addPlObject($objectName, $objectId)
    {
        $this->getPlObjects();
        $plObject = $this->getAccessHandler()->getPlObject($objectName);
        $this->plObjects[$objectName]['real'][$objectId] 
            = $plObject['reference']->{$plObject['getObject']}($objectId);
        $this->plObjects[$objectName]['full'] = -1;
    }
    
    /**
     * Removes a pluggable from the user group.
     * 
     * @param string  $objectName The name of the object.
     * @param integer $objectId   The object id which should be removed.
     * 
     * @return null
     */
    function removePlObject($objectName, $objectId)
    {
        $this->getPlObjects();
        unset($this->plObjects[$objectName]['real'][$objectId]);
        $this->plObjects[$objectName]['full'] = -1;
    }
    
    /**
     * Unsets the categories.
     * 
     * @param string  $objectName The name of the object.
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    function unsetPlObject($objectName, $plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deletePlObjectFromDb($objectName);
        }
        
        $this->plObjects[$objectName] = array(
    		'real' => array(),
        	'full' => array(),
        );
    }
    
    /**
     * Returns the pluggable objects in the group for the given object type.
     * 
     * @param string $objectName The name of the object.
     * @param string $type       The return type. Can be real or full.
     * 
     * @return array
     */
    function getPlObjectsByObjectType($objectName, $type = 'real')
    {
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return array();
        }
        
        if ($this->plObjects[$objectName][$type] != -1) {
            return $this->plObjects[$objectName][$type];
        } else {
            $this->plObjects[$objectName][$type] = array();
        }
        
        global $wpdb;

        $plObjects = $this->_getAssignedPlObjects($objectName);
        
        if (isset($plObjects)) {
            foreach ($plObjects as $plObjectId) {
                $plObject = $this->_getSinglePlObject(
                    $objectName, 
                    $plObjectId, 
                    $type
                );
                
                if ($plObject !== null) {
                    $this->plObjects[$objectName][$type][$plObjectId] 
                        = $plObject;
                }
            }
        }
        
        return $this->plObjects[$objectName][$type];
    }
    
    /**
     * Returns the pluggable objects in the group.
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getPlObjects($type = 'real')
    {
        $allPlObjects = array(); 
        
        foreach ($this->plObjects as $objectName => $content) {
            $allPlObjects[$objectName] 
                = $this->getPlObjectsByObjectType($objectName, $type); 
        }
        
        return $allPlObjects;
    }
    
    /**
     * Removes all categories from the user group.
     * 
     * @param string $objectName The name of the object.
     * 
     * @return null
     */
    private function _deletePlObjectFromDb($objectName)
    {
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_OBJECT . " 
        	WHERE group_id = " . $this->id . "
            	AND object_type = '" . $objectName . "'"
        );
    }
    
	/**
     * Checks if the given post is a member of the group.
     * 
     * @param string   $objectName The name of the object.
     * @param interger $objectId   The id of the object which should be checked.
     * @param boolean  $withInfo   If true then we return additional infos.
     * 
     * @return boolean
     */
    function plObjectIsMember($objectName, $objectId, $withInfo = false)
    {
        $plObject = $this->_getSinglePlObject($objectName, $objectId, 'full');
        
        if ($objectName !== null) {
            if (isset($plObject->recursiveMember)
                && $withInfo
            ) {
                return $plObject->recursiveMember;
            }
            
            return true;
        }
        
        return false;
    }
}