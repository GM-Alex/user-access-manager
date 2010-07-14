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
    protected $categories = array(
    	'real' => -1,
        'full' => -1
    );
    protected $posts = array(
    	'real' => -1,
        'full' => -1
    );
    protected $pages = array(
    	'real' => -1,
        'full' => -1
    );
    protected $files = array(
    	'real' => -1,
        'full' => -1
    );
    
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
                    $role  = is_array($capabilities) ? 
                        array_keys($capabilities) : 'norole';
                    
                    if (array_key_exists($role[0], $this->getRoles())
                    ) {
                        $curUserdata->recursiveMember 
                            = array('byRole' => array());
                        $curUserdata->recursiveMember['byRole'][] 
                            = $role[0];
                        
                        $this->users[$type][$wpUser['ID']] = $curUserdata;
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
        $this->users['full'] = -1;
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
			WHERE group_id = " . $this->id . "
			ORDER BY category_id",
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
     * Adds a category to the category group.
     * 
     * @param integer $categoryID The category id which should be added.
     * 
     * @return null
     */
    function addCategory($categoryID)
    {
        $this->getCategories();
        $this->categories['real'][$categoryID] = get_category($categoryID);
        $this->categories['full'] = -1;
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
    
    /**
     * Checks it the post is assigned to the group.
     * 
     * @param integer $postId The post id.
     * 
     * @return boolean
     */
    private function _isPostAssignedToGroup($postId)
    {    
        global $wpdb;
        
        $count = $wpdb->get_var(
        	"SELECT COUNT(*)
			FROM " . DB_ACCESSGROUP_TO_POST . "
			WHERE group_id = " . $this->id . "
				AND post_id = ".$postId
        );
        
        if ($count > 0) {
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
        $isRecursiveMember = array();
        
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        if ($type == 'full') {
            foreach (get_the_category($post->ID) as $category) {
                if (array_key_exists($category->cat_ID, $this->getCategories('full'))) {
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
            
            return $post;
        }
        
        return null;
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
            || $this->id == null
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
        
        $posts = $this->getAccessHandler()->getFullPost();
        
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
     * Adds a post to the post group.
     * 
     * @param integer $postID The post id which should be added.
     * 
     * @return null
     */
    function addPost($postID)
    {
        $this->getPosts();
        $this->posts['real'][$postID] = get_post($postID);
        $this->posts['full'] = -1;
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
     * Returns the pages in the group
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

        if ($post->post_type == 'post') {
            $posts = $this->getPosts('full');
        } elseif ($post->post_type == 'page') {
            $posts = $this->getPages('full');
        } elseif ($post->post_type == 'attachment') {
            $posts = $this->getFiles('full');
        } elseif ($post->post_type == 'comment') {
            //TODO add comment support
            $posts = array();
        } else {
            $posts = array();
        }
        
        if (array_key_exists($post->ID, $posts)) {
            if (isset($posts[$post->ID]->recursiveMember)
                && $withInfo
            ) {
                return $posts[$post->ID]->recursiveMember;
            }
            
            return true;
        }
        
        return false;
    }
    
	/**
     * Checks if the given post is a member of the group.
     * 
     * @param interger $categoryId The id of the post which should be checked.
     * @param boolean  $withInfo   If true then we return additional infos.
     * 
     * @return boolean
     */
    function categoryIsMember($categoryId, $withInfo = false)
    {
        $categories = $this->getCategories('full');
        
        if (array_key_exists($categoryId, $categories)) {
            if (isset($categories[$categoryId]->recursiveMember)
                && $withInfo
            ) {
                return $categories[$categoryId]->recursiveMember;
            }
            
            return true;
        }
        
        return false;
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
        $users = $this->getUsers('full');
        
        if (array_key_exists($userId, $users)) {
            if (isset($users[$userId]->recursiveMember)
                && $withInfo
            ) {
                return $users[$userId]->recursiveMember;
            }
            
            return true;
        }
        
        return false;
    }
}