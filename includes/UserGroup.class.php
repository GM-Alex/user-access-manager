<?php
/**
 * userGroup.class.php
 * 
 * The user group class
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2010 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
class UserGroup
{
    protected $id;
    protected $users = array();
    protected $categories = array();
    protected $roles = array();
    protected $posts = array();
    protected $pages = array();
    protected $files = array();
    
    /**
     * Consturtor
     * 
     * @param  $id
     * @return null
     */
    function __construct($id) {
        $this->id = $id;
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
        
        $db_users = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP_TO_USER . "
			WHERE group_id = " . $this->id . "
			ORDER BY user_id", 
            ARRAY_A
        );
        
        if (isset($db_users)) {
            $expandcontent = null;
            foreach ($db_users as $db_user) {
                $this->users[$db_user['user_id']] = get_userdata($db_user['user_id']);
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
                if ($cur_userdata->user_level >= $uamOptions['full_access_level']) {
                    $this->users[$wp_user['ID']] = $cur_userdata;
                } elseif (isset($db_roles) && $cur_userdata->user_level < $uamOptions['full_access_level']) {
                    foreach ($db_roles as $db_role) {
                        if (isset($cur_userdata->{$wpdb->prefix . "capabilities"}[$db_role['role_name']])) {
                            $this->users[$wp_user['ID']] = $cur_userdata;
                            break;
                        }
                    }
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
                $this->categories[$db_categorie['category_id']] = $cur_category;
                
                if ($uamOptions['lock_recursive'] == 'true') {
                    $cur_categories = get_categories('child_of=' . $db_categorie['category_id']);
                    if (isset($cur_categories)) {
                        foreach ($cur_categories as $cur_category) {
                            $cur_category->recursive_lock_by_category[$db_categorie['category_id']] = $db_categorie['category_id'];
                            $this->categories[$cur_category->term_id] = $cur_category;
                        }
                    }
                }
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
     * Returns the posts in the group
     * 
     * @return array
     */
    function getPosts()
    {
        if ($this->posts != array()) {
            return $this->posts;
        }
        
        $args = array('numberposts' => - 1, 'post_type' => 'any');
        $posts = get_posts($args);
        
        if (isset($posts)) {
            foreach ($posts as $post) {
                $groupinfo = $this->getUsergroupsForPost($post->ID);
                if (isset($groupinfo[$groupid])) {
                    if ($post->post_type == 'post') {
                        $this->posts[$post->ID] = $post;
                    } elseif ($post->post_type == 'page') {
                        $this->pages[$post->ID] = $post;
                    } elseif ($post->post_type == 'attachment') {
                        $this->files[$post->ID] = $post;
                    }
                }
            }
        }
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
    }
    
    function getUsergroupInfo($groupid)
    {
        global $wpdb;
        $uamOptions = $this->getAdminOptions();
        $cur_group = $wpdb->get_results(
        	"SELECT *
			FROM " . DB_ACCESSGROUP . "
			WHERE ID = " . $groupid,
            ARRAY_A
        );
        
        $info->group = $cur_group[0];
        
        $args = array('numberposts' => - 1, 'post_type' => 'any');
        $posts = get_posts($args);
        
        if (isset($posts)) {
            foreach ($posts as $post) {
                $groupinfo = $this->getUsergroupsForPost($post->ID);
                if (isset($groupinfo[$groupid])) {
                    if ($post->post_type == 'post') {
                        $info->posts[$post->ID] = $post;
                    } elseif ($post->post_type == 'page') {
                        $info->pages[$post->ID] = $post;
                    } elseif ($post->post_type == 'attachment') {
                        $info->files[$post->ID] = $post;
                    }
                }
            }
        }
        
        $args = array('numberposts' => - 1, 'post_type' => 'attachment');
        $files = get_posts($args);
        
        if (isset($files)) {
            foreach ($files as $file) {
                $groupinfo = $this->getUsergroupsForPost($file->ID);
                if (isset($groupinfo[$groupid])) {
                    $info->files[$file->ID] = $file;
                } 
            }
        }

        return $info;
    }
}