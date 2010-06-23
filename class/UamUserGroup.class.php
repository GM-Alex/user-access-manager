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
        
        //TODO add the rest
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
    function setGroupName($groupDesc)
    {
        $this->groupDesc = $groupDesc;
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
}