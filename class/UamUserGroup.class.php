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
    protected $id = null;
    protected $groupName = null;
    protected $groupDesc = null;
    protected $readAccess = null;
    protected $writeAccess = null;
    protected $ipRange = null;
    protected $roles = array();
    protected $users = array(
    	'real' => array(),
        'full' => array(),
    );
    protected $categories = array(
    	'real' => array(),
        'full' => array(),
    );
    protected $posts = array(
    	'real' => array(),
        'full' => array(),
    );
    protected $pages = array(
    	'real' => array(),
        'full' => array(),
    );
    protected $files = array(
    	'real' => array(),
        'full' => array(),
    );
    
    /**
     * Consturtor
     * 
     * @param integer $id The id of the user group.
     * 
     * @return null
     */
    function __construct($id = null)
    {
        if ($id !== null) {
            global $wpdb;
            
            $this->id = $id;
            
            $dbUsergroup = $wpdb->get_results(
            	"SELECT *
    			FROM " . DB_ACCESSGROUP . "
    			WHERE group_id = " . $this->id . "
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
     * Deletes the user group.
     * 
     * @return null
     */
    function delete()
    {
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
        
        foreach ($allPosts as $postKey => $post) {
            $wpdb->query(
            	"INSERT INTO " . DB_ACCESSGROUP_TO_POST . " (
            		group_id, 
            		post_id
            	) 
            	VALUES(
            		'" . $this->id . "', 
            		'" . $postKey . "'
            	)"
            );
        }
    }
    
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
     * @return array
     */
    function getIpRange()
    {
        $ipArray = explode(';', $this->ipRange);
        return $ipArray;
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
    
    /**
     * Returns the users in the group
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getUsers($type = 'real')
    {
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return null;
        }
        
        if ($this->users[$type] != array()) {
            return $this->users[$type];
        }
        
        global $wpdb;
        
        $dbUsers = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_USER . "
			WHERE group_id = " . $this->id . "
			ORDER BY user_id", 
            ARRAY_A
        );
        
        if (isset($dbUsers)) {
            foreach ($dbUsers as $dbUser) {
                $this->users[$type][$dbUser['user_id']] 
                    = get_userdata($dbUser['user_id']);
            }
        }
        
        if ($type == 'full') {
            $wpUsers = $wpdb->get_results(
            	"SELECT ID, user_nicename
    			FROM $wpdb->users 
    			ORDER BY user_nicename", 
                ARRAY_A
            );
            
            if (isset($wpUsers)) {
                foreach ($wpUsers as $wpUser) {
                    $curUserdata = get_userdata($wpUser['ID']);
                    $capabilities = $curUserdata->{$wpdb->prefix . "capabilities"};
                    $role = is_array($capabilities) ? array_keys($capabilities) : 'norole';
                    
                    if (array_key_exists($role[0], $this->getRoles())
                    ) {
                        $this->users[$wpUser['ID']] = $curUserdata;
                    }
                }
            }
        }
        
        return $this->users[$type];
    }
    
    /**
     * Adds a user to the user group.
     * 
     * @param integer $userID The user id which should be added.
     * 
     * @return null
     */
    function addUser($userID)
    {
        $this->getUsers();
        $this->users['real'][$userID] = get_userdata($userID);
        $this->users['full'] = array();
    }
    
    /**
     * Removes a user from the user group.
     * 
     * @param integer $userID The user id which should be removed.
     * 
     * @return null
     */
    function removeUser($userID)
    {
        $this->getUsers();
        unset($this->users['real'][$userID]);
        $this->users['full'] = array();
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
    function deleteUsersFromDb()
    {
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
        	WHERE group_id = $this->id"
        );
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
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return null;
        }
        
        if ($this->categories[$type] != array()) {
            return $this->categories[$type];
        }
        
        global $wpdb;
        $userAccessManager = new UserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        $dbCategories = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_CATEGORY . "
			WHERE group_id = " . $this->id . "
			ORDER BY category_id",
            ARRAY_A
        );
        
        if (isset($dbCategories)) {
            foreach ($dbCategories as $dbCategorie) {
                $curCategory = get_category($dbCategorie['category_id']);
                
                if ($uamOptions['lock_recursive'] == 'true' && $type == 'full') {
                    $subCategories 
                        = get_categories('child_of=' . $dbCategorie['category_id']);
                    
                    if (isset($subCategories)) {
                        foreach ($subCategories as $curCategory) {
                            $curCategory->rlByCategory[$dbCategorie['category_id']] 
                                = $dbCategorie['category_id'];
                            $this->categories[$curCategory->term_id] 
                                = $curCategory;
                        }
                    }
                }
                
                $this->categories[$type][$dbCategorie['category_id']] = $curCategory;
            }
        }
        
        return $this->categories[$type];
    }
    
    /**
     * Adds a category to the category group.
     * 
     * @param integer $categoryID The category id which should be added.
     * 
     * @return null
     */
    function addCategory($categoryID)
    {
        $this->getCategories();
        $this->categories['real'][$categoryID] = get_categorydata($categoryID);
        $this->categories['full'] = array();
    }
    
    /**
     * Removes a category from the category group.
     * 
     * @param integer $categoryID The category id which should be removed.
     * 
     * @return null
     */
    function removeCategory($categoryID)
    {
        $this->getCategories();
        unset($this->categories['real'][$categoryID]);
        $this->categories['full'] = array();
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
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " 
        	WHERE group_id = $this->id"
        );
    }
    
    /**
     * Returns the roles in the group
     * 
     * @return array
     */
    function getRoles()
    {
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
                $this->roles[$dbRole['role_name']] = $dbRole;
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
        $this->roles[$roleName] = get_roledata($roleID);
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
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_ROLE . " 
        	WHERE group_id = $this->id"
        );
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
            return null;
        }
        
        if ($postType == 'file') {
            $wpType = 'attachment';
        } else {
            $wpType = $postType;
        }
        
        if ($this->{$postType.'s'}[$type] != array()) {
            return $this->{$postType.'s'}[$type];
        }
        
        $args = array('numberposts' => - 1, 'post_type' => $wpType);
        $posts = get_posts($args);
        
        if (isset($posts)) {
            foreach ($posts as $post) {
                $count = $wpdb->get_var(
                	"SELECT COUNT(*)
					FROM " . DB_ACCESSGROUP_TO_POST . "
        			WHERE group_id = " . $this->id . "
        				AND post_id".$post->ID,
                    ARRAY_A
                );

                $isRecursiveMember = false;
                
                if ($type == 'full') {
                    foreach (get_the_category($post->ID) as $category) {
                        if (array_key_exists($category->cat_ID, $this->getCategories('full'))) {
                            $isRecursiveMember = true;
                            break;
                        }
                    }
                    
                    if ($postType == 'page' && !$isRecursiveMember) {
                        $tmpPost = $post;
                        
                        while ($tmpPost->child_of != 0) {
                            if ($this->postIsMember($tmpPost->child_of)) {
                                $isRecursiveMember = true;
                                break;
                            }
                            
                            $tmpPost = get_post($tmpPost->child_of);
                        }
                    }
                }
                
                if ($count > 0 || $isRecursiveMember) {
                    $this->{$postType.'s'}[$type][$post->ID] = $post;
                }
            }
        }
        
        
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
        global $wpdb;
        
        if ($postType == 'all') {
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
            	WHERE group_id = $this->id"
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
        if ($this->pages != array()) {
            return $this->posts;
        }
        
        return $this->_getPostByType('post', $type);
    }
    
    /**
     * Adds a post to the post group.
     * 
     * @param integer $postID The post id which should be added.
     * 
     * @return null
     */
    function addPost($postID)
    {
        $this->getPosts();
        $this->posts['real'][$postID] = get_postdata($postID);
        $this->posts['full'] = array();
    }
    
    /**
     * Removes a post from the post group.
     * 
     * @param integer $postID The post id which should be removed.
     * 
     * @return null
     */
    function removePost($postID)
    {
        $this->getPosts();
        unset($this->posts['real'][$postID]);
        $this->posts['full'] = array();
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
     * Returns the pages in the group
     * 
     * @param string $type The return type. Can be real or full.
     * 
     * @return array
     */
    function getPages($type = 'real')
    {
        if ($this->pages != array()) {
            return $this->pages;
        }
        
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
        $this->pages['real'][$pageID] = get_pagedata($pageID);
        $this->pages['full'] = array();
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
        $this->pages['full'] = array();
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
        if ($this->pages != array()) {
            return $this->files;
        }
        
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
        $this->files['real'][$fileID] = get_filedata($fileID);
        $this->files['full'] = array();
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
        $this->files['full'] = array();
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
        );;
    }
    
    /**
     * Checks if the given post is a member of the group.
     * 
     * @param interger $postId The id of the post which should be checked.
     * 
     * @return boolean
     */
    function postIsMember($postId)
    {
        $count = $wpdb->get_var(
        	"SELECT COUNT(*)
			FROM " . DB_ACCESSGROUP_TO_POST . "
			WHERE group_id = " . $this->id . "
				AND post_id".$postId,
            ARRAY_A
        );
        
        if ($count > 0) {
            return true;
        }
        
        foreach (get_the_category($postId) as $category) {
            if (array_key_exists($category->cat_ID, $this->getCategories('full'))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Checks if the given user is a member of the group.
     * 
     * @param interger $userId The id of the user which should be checked.
     * 
     * @return boolean
     */
    function userIsMember($userId)
    {
        global $wpdb;
        
        $count = $wpdb->get_var(
        	"SELECT COUNT(*)
			FROM " . DB_ACCESSGROUP_TO_USER . "
			WHERE group_id = " . $this->id . "
				AND user_id".$userId,
            ARRAY_A
        );
        
        if ($count > 0) {
            return true;
        }
        
        $curUserdata = get_userdata($userId);
        $capabilities = $curUserdata->{$wpdb->prefix . "capabilities"};
        $role = is_array($capabilities) ? array_keys($capabilities) : 'norole';
        
        if (array_key_exists($role[0], $this->getRoles())) {
            return true;
        }
        
        return false;
    }
}