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
    protected $users = array();
    protected $categories = array();
    protected $roles = array();
    protected $posts = array();
    protected $pages = array();
    protected $files = array();
    
    /**
     * Consturtor
     * 
     * @param integer $id The id of the usergroup
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
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
        	WHERE group_id = $this->id"
        );
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
        	WHERE group_id = $this->id"
        );
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " 
        	WHERE group_id = $this->id"
        );
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP_TO_ROLE . " 
        	WHERE group_id = $this->id"
        );
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
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_ROLE . " 
            	WHERE group_id = " . $this->id
            );
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_POST . " 
            	WHERE group_id = " . $this->id
            );
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_CATEGORY . " 
            	WHERE group_id = " . $this->id
            );
            
            $wpdb->query(
            	"DELETE FROM " . DB_ACCESSGROUP_TO_USER . " 
            	WHERE group_id = " . $this->id
            );
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
            		category_id
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
        $ipArray = explode($this->ipRange);
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
     * @return array
     */
    function getUsers()
    {
        if ($this->users != array()) {
            return $this->users;
        }
        
        global $wpdb;
        $userAccessManager = new UserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        $dbUsers = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_USER . "
			WHERE group_id = " . $this->id . "
			ORDER BY user_id", 
            ARRAY_A
        );
        
        if (isset($dbUsers)) {
            foreach ($dbUsers as $dbUser) {
                $this->users[$dbUser['user_id']] 
                    = get_userdata($dbUser['user_id']);
            }
        }
        
        $wpUsers = $wpdb->get_results(
        	"SELECT ID, user_nicename
			FROM $wpdb->users 
			ORDER BY user_nicename", 
            ARRAY_A
        );
        
        if (isset($wpUsers)) {
            foreach ($wpUsers as $wpUser) {
                $cur_userdata = get_userdata($wpUser['ID']);
                $capabilities = $cur_userdata->{$wpdb->prefix . "capabilities"};
                $role 
                    = is_array($capabilities) ? array_keys($capabilities) : 'norole';
                
                if ($cur_userdata->user_level >= $uamOptions['full_access_level']
                    || array_key_exists($role[0], $this->getRoles())
                ) {
                    $this->users[$wpUser['ID']] = $cur_userdata;
                }
            }
        }
        
        return $this->users;
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
        $this->users[$userID] = get_userdata($userID);
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
        unset($this->users[$userID]);
    }
    
    /**
     * Returns the categories in the group
     * 
     * @return array
     */
    function getCategories()
    {
        if ($this->categories != array()) {
            return $this->categories;
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
                
                
                if ($uamOptions['lock_recursive'] == 'true') {
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
                
                $this->categories[$dbCategorie['category_id']] = $curCategory;
            }
        }
        
        return $this->categories;
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
        $this->getCategorys();
        $this->categorys[$categoryID] = get_categorydata($categoryID);
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
        $this->getCategorys();
        unset($this->categorys[$categoryID]);
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
     * Returns the posts by the given type in the group
     * 
     * @param string $type The type of the post
     * 
     * @return array
     */
    private function _getPostByType($type)
    {
        if ($this->posts != array()) {
            return $this->posts;
        }

        global $wpdb;
        
        if ($type == 'file') {
            $wpType = 'attachment';
        } else {
            $wpType = $type;
        }
        
        $args = array('numberposts' => - 1, 'post_type' => $wpType);
        $posts = get_posts($args);
        
        if (isset($posts)) {
            foreach ($posts as $post) {
                $count = $wpdb->get_var(
                	"SELECT COUNT(*)
					FROM " . DB_ACCESSGROUP_TO_POST . "
        			WHERE group_id = " . $this->id . "
        			ORDER BY category_id",
                    ARRAY_A
                );

                if ($count > 0) {
                    $this->{$type.'s'}[$post->ID] = $post;
                }
            }
        }
    }
    
    /**
     * Returns the pages in the group
     * 
     * @return array
     */
    function getPosts()
    {
        if ($this->pages != array()) {
            return $this->posts;
        }
        
        return $this->_getPostByType('post');
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
        $this->posts[$postID] = get_postdata($postID);
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
        unset($this->posts[$postID]);
    }
    
    /**
     * Returns the pages in the group
     * 
     * @return array
     */
    function getPages()
    {
        if ($this->pages != array()) {
            return $this->pages;
        }
        
        return $this->_getPostByType('page');
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
        $this->pages[$pageID] = get_pagedata($pageID);
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
        unset($this->pages[$pageID]);
    }
    
    /**
     * Returns the files in the group
     * 
     * @return array
     */
    function getFiles()
    {
        if ($this->pages != array()) {
            return $this->files;
        }
        
        return $this->_getPostByType('file');
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
        $this->files[$fileID] = get_filedata($fileID);
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
        unset($this->files[$fileID]);
    }
}