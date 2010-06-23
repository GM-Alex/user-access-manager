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
 * @author    Alexander Schneider <author@example.com>
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
            
            $db_usergroup = $wpdb->get_results(
            	"SELECT *
    			FROM " . DB_ACCESSGROUP . "
    			WHERE group_id = " . $this->id . "
    			LIMIT 1", 
                ARRAY_A
            );
            
            $this->groupName = $db_usergroup['groupname'];
            $this->groupDesc = $db_usergroup['groupdesc'];
            $this->readAccess = $db_usergroup['read_access'];
            $this->writeAccess = $db_usergroup['write_access'];
            $this->ipRange = $db_usergroup['ip_range']; 
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
    
    function save()
    {
        
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
        
        $db_users = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_USER . "
			WHERE group_id = " . $this->id . "
			ORDER BY user_id", 
            ARRAY_A
        );
        
        if (isset($db_users)) {
            foreach ($db_users as $db_user) {
                $this->users[$db_user['user_id']] 
                    = get_userdata($db_user['user_id']);
            }
        }
        
        $wp_users = $wpdb->get_results(
        	"SELECT ID, user_nicename
			FROM $wpdb->users 
			ORDER BY user_nicename", 
            ARRAY_A
        );
        
        if (isset($wp_users)) {
            foreach ($wp_users as $wp_user) {
                $cur_userdata = get_userdata($wp_user['ID']);
                $capabilities = $cur_userdata->{$wpdb->prefix . "capabilities"};
                $role 
                    = is_array($capabilities) ? array_keys($capabilities) : 'norole';
                
                if ($cur_userdata->user_level >= $uamOptions['full_access_level']
                    || array_key_exists($role[0], $this->getRoles())
                ) {
                    $this->users[$wp_user['ID']] = $cur_userdata;
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
        
        $db_categories = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_CATEGORY . "
			WHERE group_id = " . $this->id . "
			ORDER BY category_id",
            ARRAY_A
        );
        
        if (isset($db_categories)) {
            foreach ($db_categories as $db_categorie) {
                $cur_category = get_category($db_categorie['category_id']);
                
                
                if ($uamOptions['lock_recursive'] == 'true') {
                    $sub_categories 
                        = get_categories('child_of=' . $db_categorie['category_id']);
                    
                    if (isset($sub_categories)) {
                        foreach ($sub_categories as $cur_category) {
                            $cur_category->recursive_lock_by_category[$db_categorie['category_id']] 
                                = $db_categorie['category_id'];
                            $this->categories[$cur_category->term_id] 
                                = $cur_category;
                        }
                    }
                }
                
                $this->categories[$db_categorie['category_id']] = $cur_category;
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
        
        $db_roles = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_ROLE . "
			WHERE group_id = " . $this->id, 
            ARRAY_A
        );
        
        if (isset($db_roles)) {
            foreach ($db_roles as $db_role) {
                $this->roles[$db_role['role_name']] = $db_role;
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