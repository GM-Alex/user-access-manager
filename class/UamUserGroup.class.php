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
    protected $objectTypes = array(
        'post',
        'page',
        'file',
        'category',
        'user'
    );
    protected $objects = null;
    protected $singleObjects = null;
    private $_assignedObjects = null;
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

        //Create default values for the objects.
        $plObjects = $uamAccessHandler->getPlObjects();
        
        $allTypes = array_merge(
            $this->objectTypes,
            array_keys($plObjects)
        );
        
        foreach ($allTypes as $objectType) {
            $this->objects[$objectType] = array(
            	'real' => -1,
                'full' => -1
            );
            $this->_assignedObjects[$objectType] = null;
        }

        if ($id !== null) {
            global $wpdb;
            
            $this->id = $id;
            
            $dbUsergroup = $wpdb->get_row(
            	"SELECT *
    			FROM ".DB_ACCESSGROUP."
    			WHERE ID = ".$this->getId()."
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
        $this->_deleteObjectsFromDb('user');
        $this->_deleteObjectsFromDb('allPostTypes');
        $this->_deleteObjectsFromDb('category');
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
            
            $this->_deleteObjectsFromDb('user');
            $this->_deleteObjectsFromDb('allPostTypes');
            $this->_deleteObjectsFromDb('category');
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
     * Meta functions.
     */
    
    /**
     * Adds a object of the given type.
     * 
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id.
     * @param object  $object     The object.
     * 
     * @return null
     */
    private function _addObject($objectType, $objectId, $object)
    {
        $this->getObjectsFromType($objectType);
        
        $this->objects[$objectType]['real'][$objectId] = $object;
        $this->objects[$objectType]['full'] = -1;
        
        $this->singleObjects[$objectType][$objectId] = $object;
        $this->_assignedObjects[$objectType][$objectId] = $objectId;
    }
    
    /**
     * Removes a object of the given type.
     * 
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id.
     * 
     * @return null
     */
    private function _removeObject($objectType, $objectId)
    {
        $this->getObjectsFromType($objectType);
        
        unset($this->objects[$objectType]['real'][$objectId]);
        $this->objects[$objectType]['full'] = -1;
        
        unset($this->singleObjects[$objectType][$objectId]);
        unset($this->_assignedObjects[$objectType][$objectId]);
    }
    
    /**
     * Returns the assigned objects.
     * 
     * @param string $objectType The object type.
     * 
     * @return array
     */
    private function _getAssignedObjects($objectType)
    {
        if ($this->_assignedObjects[$objectType] !== null) {
            return $this->_assignedObjects[$objectType];
        }

        global $wpdb;
        
        if ($objectType == 'post'
        	|| $objectType == 'page'
        	|| $objectType == 'file'
        ) {
            $dbIdName = 'post_id';
            $database = DB_ACCESSGROUP_TO_POST;
        } elseif ($objectType == 'category') {
            $dbIdName = 'category_id';
            $database = DB_ACCESSGROUP_TO_CATEGORY;
        } elseif ($objectType == 'user') {
            $dbIdName = 'user_id';
            $database = DB_ACCESSGROUP_TO_USER;
        }
        
        $dbObjects = $wpdb->get_results(
        	"SELECT ".$dbIdName."
			FROM ".$database."
			WHERE group_id = ".$this->getId()
        );
        
        $this->_assignedObjects[$objectType] = array();
        
        foreach ($dbObjects as $dbObject) {
            $this->_assignedObjects[$objectType][$dbObject->{$dbIdName}] 
                = $dbObject->{$dbIdName};
        }
        
        return $this->_assignedObjects[$objectType];
    }
    
    /**
     * Checks if the object is assigned to the group.
     * 
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id.
     * 
     * @return boolean
     */
    private function _isObjectAssignedToGroup($objectType, $objectId)
    {        
        return array_key_exists(
            $objectId, 
            $this->_getAssignedObjects($objectType)
        );
    }
    
    /**
     * Unsets the ojects.
     * 
     * @param string  $objectType The object type.
     * @param boolean $plusRemove If true also database entrys will remove.
     * 
     * @return null;
     */
    private function _unsetObjects($objectType, $plusRemove = false)
    {
        if ($plusRemove) {
            $this->_deleteObjectsFromDb($objectType);
        }
        
        $this->objects[$objectType] = array(
    		'real' => array(),
        	'full' => array(),
        );
    }
    
    /**
     * Removes all objects from the user group.
     * 
     * @param string $objectType The object type.
     * 
     * @return null
     */
    private function _deleteObjectsFromDb($objectType)
    {
        global $wpdb;
        
        if ($objectType == 'allPostTypes') {
            $database = DB_ACCESSGROUP_TO_POST;
        } elseif ($objectType == 'category') {
            $database = DB_ACCESSGROUP_TO_CATEGORY;
        } elseif ($objectType == 'user') {
            $database = DB_ACCESSGROUP_TO_USER;
        }
        
        $wpdb->query(
        	"DELETE FROM ".$database." 
        	WHERE group_id = ".$this->getId()
        );
    }
    
	/**
     * Checks if the given object is a member of the group.
     * 
     * @param string   $objectType The object type.
     * @param interger $objectId   The id of the object which should be checked.
     * @param object   $object     The object.
     * @param boolean  $withInfo   If true then we return additional infos.
     * 
     * @return boolean
     */
    private function _objectIsMember($objectType, $objectId, $object, $withInfo = false)
    {
        $object = $this->_getSingleObject($objectType, $objectId, $object, 'full');
        
        if ($object !== null) {
            if (isset($object->recursiveMember)
                && $withInfo
            ) {
                return $object->recursiveMember;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Returns all objects of the given type.
     * 
     * @param string $objectType The object type.
     * 
     * @return array
     */
    private function _getObjectsFromType($objectType)
    {
        if ($objectType = 'post'
            || $objectType = 'page'
            || $objectType = 'file'
        ) {
            return $this->_getPostByType($objectType);
        } else if ($objectType = 'category') {
            return $this->getCategories();
        } else if ($objectType = 'user') {
            return $this->getUsers();
        }
        
        return array();
    }
    
    /**
     * Returns a single object.
     * 
     * @param string   $objectType The object type.
     * @param interger $objectId   The id of the object which should be checked.
     * @param object   $object     The object.
     * @param string   $type       The return type. Can be real or full.
     * 
     * @return object
     */
    private function _getSingleObject($objectType, $objectId, $object, $type)
    { 
        if (isset($this->singleObjects[$objectType][$objectId])) {
            return $this->singleObjects[$objectType][$objectId];
        }
        
        $isRecursiveMember = array();
        
        if ($type == 'full') {
            if ($objectType == 'post'
            	|| $objectType == 'page'
            	|| $objectType == 'file'
            ) {
                $isRecursiveMember = $this->_getFullPost($object, $objectType);
            } else if ($objectType == 'category') {
                $isRecursiveMember = $this->_getFullCategory($object);
            } else if ($objectType == 'user') {
                $isRecursiveMember = $this->_getFullUser($object);
            } else {
                
            }
        }

        if ($this->_isObjectAssignedToGroup($objectType, $objectId)
            || $isRecursiveMember != array()
        ) {
            if ($isRecursiveMember != array()) {
                $user->recursiveMember = $isRecursiveMember;
            }
            
            $this->singleObjects[$objectType][$objectId] = $object;
        } else {
            $this->singleObjects[$objectType][$objectId] = null;
        }

        return $this->singleObjects[$objectType][$objectId];
    }

    
    /*
     * Group users functions.
     */
    
    /**
     * Returns a single user.
     * 
     * @param object $user The user.
     * 
     * @return object
     */
    private function _getFullUser($user)
    {
        $isRecursiveMember = array();
        
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

        return $isRecursiveMember;
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
        
        if ($this->objects['user'][$type] != -1) {
            return $this->objects['user'][$type];
        } else {
            $this->objects['user'][$type] = array();
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
                    $this->objects['user'][$type][$user->ID] = $user;
                }
            }
        }
        
        return $this->objects['user'][$type];
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
        $this->_addObject('user', $userId, get_userdata($userId));
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
        $this->_removeObject('user', $userId);
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
        return $this->_objectIsMember(
        	'user', 
            $userId, 
            $user, 
            $withInfo
        );
    }
    
    /*
     * Group categories functions.
     */
    
    /**
     * Returns a single category.
     * 
     * @param object $category The category.
     * 
     * @return object
     */
    private function _getFullCategory($category)
    {
        if (!is_object($category)) {
            return array();
        }
        
        $isRecursiveMember = array();
        
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        if ($uamOptions['lock_recursive'] == 'true') {
            if ($category->parent != 0) {
                $parentCategory = get_category($category->parent);
                
                $parentCategory = $this->_getSingleObject(
                    'category',
                    $parentCategory->term_id,
                    $parentCategory,
                    'full'
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

        return $isRecursiveMember;
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
        if ($this->getId() == null) {
            return array();
        }
        
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return array();
        }
        
        if ($this->objects['category'][$type] != -1) {
            return $this->objects['category'][$type];
        } else {
            $this->objects['category'][$type] = array();
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
                
                    $this->objects['category'][$type][$category->term_id] 
                        = $category;
                }
            }
        }
        
        return $this->objects['category'][$type];
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
        $this->_addObject('category', $categoryId, get_category($categoryId));
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
        $this->_removeObject('category', $categoryId);
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
        return $this->_objectIsMember(
        	'category', 
            $categoryId, 
            get_category($categoryId), 
            $withInfo
        );
    }
    
    
    /*
     * Group posts functions.
     */  
    
    /**
     * Returns the membership of a single post.
     * 
     * @param object $post     The post object.
     * @param string $postType The post type needed for the intern representation.
     * 
     * @return object
     */
    function _getFullPost($post, $postType)
    {
        $isRecursiveMember = array();
        
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        foreach ($this->getCategories('full') as $category) {
            if (in_category($category->cat_ID, $post->ID)) { 
                $isRecursiveMember['byCategory'][] = $category->cat_ID;
            }
        }

        if (($postType == 'page'
        	|| $postType == 'file')
        	&& $uamOptions['lock_recursive'] == 'true'
        ) {
            if ($post->post_parent != 0) {
                $parentPost = get_post($post->post_parent);
                
                $parentPost = $this->_getSingleObject(
                    $postType,
                    $parentPost->ID,
                    $parentPost,
                    'full'
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

        return $isRecursiveMember;
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
        
        if ($this->objects[$postType][$type] != -1) {
            return $this->objects[$postType][$type];
        } else {
            $this->objects[$postType][$type] = array();
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
                    $this->objects[$postType][$type][$post->ID] = $post;
                }
            }
        }
        
        return $this->objects[$postType][$type];
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
        $this->_addObject('post', $postId, get_post($postId));
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
        $this->_removeObject('post', $postId);
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
        $this->_addObject('page', $pageID, get_post($pageID));
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
        $this->_removeObject('page', $pageID);
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
        $this->_addObject('file', $fileID, get_post($fileID));
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
        $this->_removeObject('file', $fileID);
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
        
        return $this->_objectIsMember(
        	$postType, 
            $postId, 
            $post, 
            $withInfo
        );
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
			WHERE group_id = " . $this->getId() . "
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
     * @param string  $objectName The pluggable object name.
     * @param object  $plObject   The pluggable object.
     * @param integer $objectId   The object id.
     * @param string  $type       The return type. Can be real or full.
     * 
     * @return object
     */
    private function _getSinglePlObject($objectName, $plObject, $objectId, $type = 'full')
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
            /*$plObject
                = $plObjectStuff['reference']->{$plObjectStuff['getFull']}(
                    $objectName
                );*/
        }

        if ($this->_isPlObjectAssignedToGroup($objectName, $objectId)
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
        $plObjectStuff = $this->getAccessHandler()->getPlObject($objectName);
        
        $this->plObjects[$objectName]['real'][$objectId] 
            = $plObjectStuff['reference']->{$plObjectStuff['getObject']}($objectId);
        $this->singlePlObjects[$objectName][$objectId]
            = $plObjectStuff['reference']->{$plObjectStuff['getObject']}($objectId);
        $this->plObjects[$objectName]['full'] = -1;
        $this->_assignedPlObjects[$objectName][$objectId] = $objectId;
        
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
        unset($this->singlePlObjects[$objectName][$objectId]);
        $this->plObjects[$objectName]['full'] = -1;
        unset($this->_assignedPlObjects[$objectName][$objectId]);
        
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
        $plObjectStuff = $this->getAccessHandler()->getPlObject($objectName);
        $plObject = $plObjectStuff['reference']->{$plObjectStuff['getObject']}(
            $objectName
        );
        
        $plObject = $this->_getSinglePlObject(
            $objectName, 
            $plObject, 
            $objectId, 
            'full'
        );
        
        if ($plObject !== null) {
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