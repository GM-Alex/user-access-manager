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
    protected $objects = null;
    protected $singleObjects = null;
    private $_assignedObjects = null;
    protected $plObjects = array();
    
    /**
     * Consturtor
     * 
     * @param object  &$uamAccessHandler The access handler object.
     * @param integer $id                The id of the user group.
     * 
     * @return null
     */
    public function __construct(&$uamAccessHandler, $id = null)
    {
        $this->accessHandler = $uamAccessHandler;

        //Create default values for the objects.
        foreach ($this->getAllObjectTypes() as $objectType) {
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
    public function &getAccessHandler()
    {
        return $this->accessHandler;
    }
    
    /**
     * Deletes the user group.
     * 
     * @return null
     */
    public function delete()
    {
        if ($this->id == null) {
            return false;
        }
        
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP . " 
        	WHERE ID = $this->id LIMIT 1"
        );
        
        foreach ($this->getAllObjectTypes() as $objectType) {
            $this->_deleteObjectsFromDb($objectType);
        }
    }
    
    /**
     * Saves the user group.
     * 
     * @return null;
     */
    public function save()
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
            
            foreach ($this->getAllObjectTypes() as $objectType) {
                //Load to object
                $this->getObjectsFromType($objectType);
                //Delete form database
                $this->_deleteObjectsFromDb($objectType);
            }
        }
        
        foreach ($this->getAllObjectTypes() as $objectType) {
            foreach ($this->getObjectsFromType($objectType) as $key => $object) {
                $sql = sprintf(
                    $this->_getSqlQuery($objectType, 'insert'), 
                    trim($key)
                );
                $wpdb->query($sql);
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
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Returns the group name.
     * 
     * @return string
     */
    public function getGroupName()
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
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;
    }
    
    /**
     * Returns the group description.
     * 
     * @return string
     */
    public function getGroupDesc()
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
    public function setGroupDesc($groupDesc)
    {
        $this->groupDesc = $groupDesc;
    }
    
    /**
     * Returns the read access.
     * 
     * @return string
     */
    public function getReadAccess()
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
    public function setReadAccess($readAccess)
    {
        $this->readAccess = $readAccess;
    }
    
    /**
     * Returns the write access.
     * 
     * @return string
     */
    public function getWriteAccess()
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
    public function setWriteAccess($writeAccess)
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
    public function getIpRange($type = null)
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
    public function setIpRange($ipRange)
    {
        if (is_array($ipRange)) {
            $ipRange = implode(';', $ipRange);
        }
        
        $this->ipRange = $ipRange;
    }
    
    /**
     * Returns all objects types.
     * 
     * @return array
     */
    public function getAllObjectTypes()
    {
        return $this->getAccessHandler()->getAllObjectTypes();
    }

    
    /*
     * Meta functions.
     */
    
    /**
     * Magic method getter.
     * 
     * @param string $name      The name of the function 
     * @param array  $arguments The arguments for the function
     * 
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $uam = $this->getAccessHandler()->getUserAccessManager();

        $action = '';
        
        if ($uam->startsWith($name, 'add')) {
            $prefix = 'add';
        } elseif ($uam->startsWith($name, 'remove')) {
            $prefix = 'remove';
        } elseif ($uam->startsWith($name, 'isMember')) {
            $prefix = 'isMember';
        }
        
        $objectType = str_replace($prefix, '', $name);
        $objectType = strtolower($objectType);
        
        $objectId = $arguments[0];
        
        if ($prefix == 'add') {
            return $this->addObject(
                $objectType, 
                $objectId
            );
        } elseif ($prefix == 'remove') {
            return $this->removeObject(
                $objectType, 
                $objectId
            );
        } elseif ($prefix == 'ismember') {
            $withInfo = $arguments[1];
            
            return $this->objectIsMember(
            	$objectType, 
                $objectId,
                $withInfo
            );
        }
    }
    
    /**
     * Returns the sql query.
     * 
     * @param string $objectType The object type.
     * @param string $action     The sql action.
     * 
     * @return string
     */
    private function _getSqlQuery($objectType, $action)
    {
        $sql = '';
        
        if ($action == 'select') {
            $sql = "SELECT object_id as id
    			FROM ".DB_ACCESSGROUP_TO_OBJECT."
    			WHERE group_id = ".$this->getId()." 
    			AND object_type = '".$objectType ."'";
        } elseif ($action == 'delete') {
            $sql = "DELETE FROM ".DB_ACCESSGROUP_TO_OBJECT." 
        		WHERE group_id = ".$this->getId()." 
                AND object_type = '".$objectType ."'";
        } elseif ($action == 'insert') {
            $sql = "INSERT INTO ".DB_ACCESSGROUP_TO_OBJECT." (
            		group_id, 
            		object_id, 
            		object_type
            	) VALUES (
            		'".$this->getId()."', 
            		'%s',
            		'".$objectType."'
            	)";
        }
        
        return $sql;
    }
    
    /**
     * Adds a object of the given type.
     * 
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id.
     * 
     * @return null
     */
    public function addObject($objectType, $objectId)
    {
        if (!in_array($objectType, $this->getAllObjectTypes())) {
            return;
        }
        
        $this->getAccessHandler()->unsetUserGroupsForObject();
        $this->getObjectsFromType($objectType);
        
        $object = new stdClass;
        $object->id = $objectId;
        
        $this->objects[$objectType]['real'][$objectId] = $object;
        $this->objects[$objectType]['full'] = -1;
        
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
    public function removeObject($objectType, $objectId)
    {
        if (!in_array($objectType, $this->getAllObjectTypes())) {
            return;
        }
        
        $this->getAccessHandler()->unsetUserGroupsForObject();
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

        $dbObjects = $wpdb->get_results(
        	$this->_getSqlQuery($objectType, 'select')
        );
        
        $this->_assignedObjects[$objectType] = array();
        
        foreach ($dbObjects as $dbObject) {
            $this->_assignedObjects[$objectType][$dbObject->id] 
                = $dbObject->id;
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
    public function unsetObjects($objectType, $plusRemove = false)
    {
        if (!in_array($objectType, $this->getAllObjectTypes())) {
            return;
        }
        
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
        if (isset($this->id)) {
            global $wpdb;
        
            $wpdb->query(
            	$this->_getSqlQuery($objectType, 'delete')
            );
        }
    }
    
	/**
     * Checks if the given object is a member of the group.
     * 
     * @param string   $objectType The object type.
     * @param interger $objectId   The id of the object which should be checked.
     * @param boolean  $withInfo   If true then we return additional infos.
     * 
     * @return boolean
     */
    public function objectIsMember($objectType, $objectId, $withInfo = false)
    {
        if (!in_array($objectType, $this->getAllObjectTypes())) {
            return;
        }
        
        $object = $this->_getSingleObject($objectType, $objectId, 'full');
        
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
     * @param string $type       The return type, could be real or full.
     * 
     * @return array
     */
    function getObjectsFromType($objectType, $type = 'real')
    {
        if (!in_array($objectType, $this->getAllObjectTypes())) {
            return;
        }
        
        if ($this->id == null) {
            return array();
        }
        
        if ($type != 'real' 
            && $type != 'full'
        ) {
            return array();
        }
        
        if ($this->objects[$objectType][$type] != -1) {
            return $this->objects[$objectType][$type];
        } else {
            $this->objects[$objectType][$type] = array();
        }
        
        global $wpdb;
        
        $dbObjects = $wpdb->get_results(
        	$this->_getSqlQuery($objectType, 'select')
        );
        
        foreach ($dbObjects as $dbObject) {
            $object = $this->_getSingleObject($objectType, $dbObject->id, $type);
            
            if ($object !== null) {
                $this->objects[$objectType][$type][$object->id] = $object;
            }
        }
        
        if ($type == 'full'
        	&& $objectType != 'post'
            && $objectType != 'page'
            && $objectType != 'attachment'
            && $objectType != 'role'
        ) {
            if ($objectType == 'category') {
                $this->objects[$objectType][$type] 
                    = $this->getFullCategories($this->objects[$objectType][$type]);
            } elseif ($objectType == 'user') {
                 $this->objects[$objectType][$type] = $this->getFullUsers();
            } else {
                $plObject = $this->getAccessHandler()->getPlObject($objectType);
                $this->objects[$objectType][$type] 
                    = $plObject['reference']->{$plObject['getFullObjects']}(
                        $this->objects[$objectType][$type],
                        &$this
                    );
            }
        }
        
        return $this->objects[$objectType][$type];
    }
    
    /**
     * Returns a single object.
     * 
     * @param string   $objectType The object type.
     * @param interger $objectId   The id of the object which should be checked.
     * @param string   $type       The return type. Can be real or full.
     * 
     * @return object
     */
    private function _getSingleObject($objectType, $objectId, $type)
    {
        if (isset($this->singleObjects[$objectType][$objectId])) {
            return $this->singleObjects[$objectType][$objectId];
        }
        
        $isRecursiveMember = array();
        
        if ($type == 'full'
            && $objectType != 'role'
        ) {
            if ($objectType == 'post'
            	|| $objectType == 'page'
            	|| $objectType == 'attachment'
            ) {
                $isRecursiveMember = $this->_getFullPost($objectType, $objectId);
            } else if ($objectType == 'category') {
                $isRecursiveMember = $this->_getFullCategory($objectId);
            } else if ($objectType == 'user') {
                $isRecursiveMember = $this->_getFullUser($objectId);
            } else {
                $isRecursiveMember = $this->_getFullPlObject($objectType, $objectId);
            }
        }
        
        if ($this->_isObjectAssignedToGroup($objectType, $objectId)
            || $isRecursiveMember != array()
        ) {
            $object = new stdClass;
            $object->id = $objectId;
            
            if ($isRecursiveMember != array()) {
                $object->recursiveMember = $isRecursiveMember;
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
     * @param integer $objectId The object id.
     * 
     * @return object
     */
    private function _getFullUser($objectId)
    {
        $isRecursiveMember = array();
        
        global $wpdb;
        
        $curUserdata = get_userdata($objectId);
        
        if (isset($curUserdata->{$wpdb->prefix . "capabilities"})) {
            $capabilities = $curUserdata->{$wpdb->prefix . "capabilities"};
        } else {
            $capabilities = null;
        }
        
        $role  = is_array($capabilities) ? 
            array_keys($capabilities) : array('norole');
        
        if (array_key_exists($role[0], $this->getObjectsFromType('role'))
        ) {
            $roleObject->name = $role[0];
            
            $isRecursiveMember = array('role' => array());
            $isRecursiveMember['role'][] = $roleObject;
        }

        return $isRecursiveMember;
    }
    
    /**
     * Returns the users in the group
     * 
     * @return array
     */
    public function getFullUsers()
    {
        global $wpdb;
        
        $dbUsers = $wpdb->get_results(
        	"SELECT ID, user_nicename
    		FROM ".$wpdb->users
        );
        
        $fullUsers = array();
        
        if (isset($dbUsers)) {
            foreach ($dbUsers as $dbUser) {
                $user = $this->_getSingleObject('user', $dbUser->ID, 'full');
                
                if ($user !== null) {
                    $fullUsers[$user->id] = $user;
                }
            }
        }
        
        return $fullUsers;
    }
    

    /*
     * Group categories functions.
     */
    
    /**
     * Returns a single category.
     * 
     * @param integer $objectId The object id.
     * 
     * @return object
     */
    private function _getFullCategory($objectId)
    {
        $category = get_category($objectId);

        $isRecursiveMember = array();
        
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();

        if ($uamOptions['lock_recursive'] == 'true') {
            if (isset($category->parent)
                && !is_null($category->parent)
            ) {
                $parentCategory = $this->_getSingleObject(
                    'category',
                    $category->parent,
                    'full'
                );

                if ($parentCategory !== null) {
                    $curCategory = get_category($objectId);
                    $parentCategory->name = $curCategory->name;
                    
                    if (isset($parentCategory->recursiveMember)) {
                        $isRecursiveMember['category'][]
                            = $parentCategory;
                    } else {
                        $isRecursiveMember['category'][]
                            = $parentCategory;
                    }
                }
            }
        }

        return $isRecursiveMember;
    }
    
    /**
     * Returns the categories in the group
     * 
     * @param string $categories The real categories.
     * 
     * @return array
     */
    public function getFullCategories($categories)
    {
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();

        foreach ($categories as $category) {
            if ($category != null) {
                if ($uamOptions['lock_recursive'] == 'true') {
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
                            'child_of' => $category->id,
                            'hide_empty' => false
                        );
                        
                        $categoryChilds = get_categories($args);
                        
                        add_filter(
                        	'get_terms', 
                            array(&$userAccessManager, 'showCategory')
                        );
                        
                        
                        foreach ($categoryChilds as $categoryChild) {
                            $curCategoryChild = new stdClass();
                            $curCategoryChild->id = $categoryChild->term_id;
                            $curCategoryChild->name = $categoryChild->name;
                            
                            $curCategoryChild->recursiveMember 
                                = array('category' => array());
                            $curCategoryChild->recursiveMember['category'][] 
                                = $curCategoryChild;
                            $categories[$curCategoryChild->id] 
                                = $curCategoryChild;
                        }
                    }
                }
            
                $categories[$category->id] = $category;
            }
        }
        
        return $categories;
    }
    
    
    /*
     * Group posts functions.
     */  
    
    /**
     * Returns the membership of a single post.
     * 
     * @param string  $postType The post type needed for the intern representation.
     * @param integer $objectId The object id.
     * 
     * @return object
     */
    private function _getFullPost($postType, $objectId)
    {
        $post = get_post($objectId);
        
        $isRecursiveMember = array();
        
        $userAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $uamOptions = $userAccessManager->getAdminOptions();
        
        foreach ($this->getObjectsFromType('category', 'full') as $category) {
            if (in_category($category->id, $post->ID)) {
                $categoryObject = get_category($category->id);
                $category->name = $categoryObject->name;
                
                $isRecursiveMember['category'][] = $category;
            }
        }
        
        if (get_option('show_on_front') == 'page'
            && $post->post_parent == 0
            && $post->post_type == 'post'
            && get_option('page_for_posts') != $objectId
        ) {
            $parentId = get_option('page_for_posts');
        } else {
            $parentId = $post->post_parent;
        }

        if ($uamOptions['lock_recursive'] == 'true'
        	&& $parentId != 0   
        ) {
            $parent = get_post($parentId);
            
            $parentPost = $this->_getSingleObject(
                $parent->post_type,
                $parentId,
                'full'
            );
    
            if ($parentPost !== null) {
                $postObject = get_post($parentPost->id);
                $parentPost->name = $postObject->post_title;

                $isRecursiveMember[$parent->post_type][] = $parentPost;
            }
        }

        return $isRecursiveMember;
    }

    
    /*
     * Group pluggable objects functions.
     */
    
    /**
     * Returns a the recursive membership for a pluggable object.
     * 
     * @param string  $objectType The pluggable object type.
     * @param integer $objectId   The object id.
     * 
     * @return array
     */
    private function _getFullPlObject($objectType, $objectId)
    {
        $isRecursiveMember = array();
        
        $plObject = $this->getAccessHandler()->getPlObject($objectType);
        
        if (isset($plObject['reference'])
            && isset($plObject['getFull'])
        ) {
            $plRecMember = $plObject['reference']->{$plObject['getFull']}(
                $objectId, 
                &$this
            );
            
            if (is_array($plRecMember)) {
                $isRecursiveMember = $plRecMember;
            }
        }

        return $isRecursiveMember;
    }
}