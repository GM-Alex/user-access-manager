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
 * @copyright 2008-2013 Alexander Schneider
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
    protected $_oAccessHandler = null;
    protected $_iId = null;
    protected $_sGroupName = null;
    protected $_sGroupDesc = null;
    protected $_sReadAccess = null;
    protected $_sWriteAccess = null;
    protected $_sIpRange = null;
    protected $_aRoles = array();
    protected $_aObjects = null;
    protected $_aSingleObjects = null;
    protected $_aAssignedObjects = null;
    protected $_aPlObjects = array();
    protected $_aSetRecursive = array();
    protected $_aValidObjectTypes = array();
    protected $_aObjectsInCategory = null;
    protected $_aObjectIsMember = array();

    /**
     * Constructor
     *
     * @param UamAccessHandler $oUamAccessHandler The access handler object.
     * @param integer          $iId               The id of the user group.
     */
    public function __construct(UamAccessHandler &$oUamAccessHandler, $iId = null)
    {
        $this->_oAccessHandler = $oUamAccessHandler;

        if ($iId !== null) {
            /**
             * @var wpdb $wpdb
             */
            global $wpdb;
            
            $this->_iId = $iId;
            
            $aDbUserGroup = $wpdb->get_row(
            	"SELECT *
    			FROM ".DB_ACCESSGROUP."
    			WHERE ID = ".$this->getId()."
    			LIMIT 1", 
                ARRAY_A
            );
            
            $this->_sGroupName = $aDbUserGroup['groupname'];
            $this->_sGroupDesc = $aDbUserGroup['groupdesc'];
            $this->_sReadAccess = $aDbUserGroup['read_access'];
            $this->_sWriteAccess = $aDbUserGroup['write_access'];
            $this->_sIpRange = $aDbUserGroup['ip_range'];
        }

        //Create default values for the objects.
        foreach ($this->getAllObjectTypes() as $sObjectType) {
            $this->_aAssignedObjects[$sObjectType] = null;
            $this->_aObjects[$sObjectType] = array(
                'real' => -1,
                'full' => -1
            );

            if ($iId !== null) {
                $this->getObjectsFromType($sObjectType);
            }
        }
    }
    
    /**
     * Returns the user access handler object.
     * 
     * @return UamAccessHandler
     */
    public function &getAccessHandler()
    {
        return $this->_oAccessHandler;
    }
    
    /**
     * Deletes the user group.
     * 
     * @return boolean
     */
    public function delete()
    {
        if ($this->_iId == null) {
            return false;
        }

        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        
        $wpdb->query(
        	"DELETE FROM " . DB_ACCESSGROUP . " 
        	WHERE ID = $this->_iId LIMIT 1"
        );
        
        foreach ($this->getAllObjectTypes() as $sObjectType) {
            $this->_deleteObjectsFromDb($sObjectType);
        }

        return true;
    }
    
    /**
     * Saves the user group.
     *
     * @param boolean $blRemoveOldAssignments If false we will not remove old entries.
     *
     * @return null
     */
    public function save($blRemoveOldAssignments = true)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        
        if ($this->_iId == null) {
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
            		'" . $this->_sGroupName . "',
            		'" . $this->_sGroupDesc . "',
            		'" . $this->_sReadAccess . "',
            		'" . $this->_sWriteAccess . "',
            		'" . $this->_sIpRange . "'
            	)"
            );
            
            $this->_iId = $wpdb->insert_id;
        } else {
            $wpdb->query(
            	"UPDATE " . DB_ACCESSGROUP . "
    			SET groupname = '" . $this->_sGroupName . "',
    				groupdesc = '" . $this->_sGroupDesc . "',
    				read_access = '" . $this->_sReadAccess . "',
    				write_access = '" . $this->_sWriteAccess . "',
    				ip_range = '" . $this->_sIpRange . "'
    			WHERE ID = " . $this->_iId
            );

            if ($blRemoveOldAssignments === true) {
                foreach ($this->getAllObjectTypes() as $sObjectType) {
                    //Delete form database
                    $this->_deleteObjectsFromDb($sObjectType);
                }
            }
        }
        
        foreach ($this->getAllObjectTypes() as $sObjectType) {
            $aKeys = $this->_getAssignedObjects($sObjectType);

            if (count($aKeys) > 0) {
                $sSql = $this->_getSqlQuery($sObjectType, 'insert', $aKeys);
                $wpdb->query($sSql);
            }
        }
    }
    
    
    /*
     * Primary values.
     */
    
    /**
     * Returns the group _iId.
     * 
     * @return integer
     */
    public function getId()
    {
        return $this->_iId;
    }
    
    /**
     * Returns the group name.
     * 
     * @return string
     */
    public function getGroupName()
    {
        return $this->_sGroupName;
    }
    
    /**
     * Sets the group name.
     * 
     * @param string $sGroupName The new group name.
     * 
     * @return null
     */
    public function setGroupName($sGroupName)
    {
        $this->_sGroupName = $sGroupName;
    }
    
    /**
     * Returns the group description.
     * 
     * @return string
     */
    public function getGroupDesc()
    {
        return $this->_sGroupDesc;
    }
    
    /**
     * Sets the group description.
     * 
     * @param string $sGroupDesc The new group description.
     * 
     * @return null
     */
    public function setGroupDesc($sGroupDesc)
    {
        $this->_sGroupDesc = $sGroupDesc;
    }
    
    /**
     * Returns the read access.
     * 
     * @return string
     */
    public function getReadAccess()
    {
        return $this->_sReadAccess;
    }
    
    /**
     * Sets the read access.
     * 
     * @param string $sReadAccess The read access.
     * 
     * @return null
     */
    public function setReadAccess($sReadAccess)
    {
        $this->_sReadAccess = $sReadAccess;
    }
    
    /**
     * Returns the write access.
     * 
     * @return string
     */
    public function getWriteAccess()
    {
        return $this->_sWriteAccess;
    }
    
    /**
     * Sets the write access.
     * 
     * @param string $sWriteAccess The write access.
     * 
     * @return null
     */
    public function setWriteAccess($sWriteAccess)
    {
        $this->_sWriteAccess = $sWriteAccess;
    }
    
    /**
     * Returns the ip range.
     * 
     * @param string $sType The return type.
     * 
     * @return array|string
     */
    public function getIpRange($sType = null)
    {
        if ($sType == 'string') {
            return $this->_sIpRange;
        }
        
        $aIpRange = explode(';', $this->_sIpRange);
        
        if ($aIpRange[0] == null) {
            return null;
        }
        
        return $aIpRange;
    }
    
    /**
     * Sets the ip range.
     * 
     * @param string|array $mIpRange The new ip range.
     * 
     * @return null
     */
    public function setIpRange($mIpRange)
    {
        if (is_array($mIpRange)) {
            $mIpRange = implode(';', $mIpRange);
        }
        
        $this->_sIpRange = $mIpRange;
    }
    
    /**
     * Returns all _aObjects types.
     * 
     * @return array
     */
    public function getAllObjectTypes()
    {
        return $this->getAccessHandler()->getAllObjectTypes();
    }

    /**
     * Checks if the object type is a valid one.
     *
     * @param string $sObjectType The object type to check.
     *
     * @return boolean
     */
    public function isValidObjectType($sObjectType)
    {
        return $this->getAccessHandler()->isValidObjectType($sObjectType);
    }

    /**
     * Sets the recursive object membership.
     *
     * @param string  $sObjectType       The object type.
     * @param integer $iObjectId         The object id.
     * @param array   $aObjectMembership The membership.
     *
     * @return null
     */
    public function setRecursiveMembership($sObjectType, $iObjectId, $aObjectMembership)
    {
        if (!isset($this->_aSetRecursive[$sObjectType])) {
            $this->_aSetRecursive[$sObjectType] = array();
        }

        $this->_aSetRecursive[$sObjectType][$iObjectId] = $aObjectMembership;
    }

    /**
     * Returns the recursive membership.
     *
     * @param string  $sObjectType    The object type.
     * @param integer $iObjectId      The object id.
     * @param string  $sCurObjectType The current object type.
     *
     * @return array
     */
    public function getRecursiveMembershipForObjectType($sObjectType, $iObjectId, $sCurObjectType)
    {
        if (isset($this->_aSetRecursive[$sObjectType])
            && isset($this->_aSetRecursive[$sObjectType][$iObjectId])
            && isset($this->_aSetRecursive[$sObjectType][$iObjectId][$sCurObjectType])
        ) {
            return $this->_aSetRecursive[$sObjectType][$iObjectId][$sCurObjectType];
        }

        return array();
    }

    /**
     * Returns true if the requested object is locked recursive.
     *
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object id.
     *
     * @return boolean
     */
    public function isLockedRecursive($sObjectType, $iObjectId)
    {
        if (isset($this->_aSetRecursive[$sObjectType]) && isset($this->_aSetRecursive[$sObjectType][$iObjectId])) {
            return true;
        }

        return false;
    }


    /*
     * Meta functions.
     */
    
    /**
     * Magic method getter.
     * 
     * @param string $sName      The name of the function
     * @param array  $aArguments The arguments for the function
     * 
     * @return mixed
     */
    public function __call($sName, $aArguments)
    {
        $oUam = $this->getAccessHandler()->getUserAccessManager();

        $sPrefix = '';

        if ($oUam->startsWith($sName, 'add')) {
            $sPrefix = 'add';
        } elseif ($oUam->startsWith($sName, 'remove')) {
            $sPrefix = 'remove';
        } elseif ($oUam->startsWith($sName, 'isMember')) {
            $sPrefix = 'isMember';
        }
        
        $sObjectType = str_replace($sPrefix, '', $sName);
        $sObjectType = strtolower($sObjectType);
        
        $iObjectId = $aArguments[0];
        
        if ($sPrefix == 'add') {
            return $this->addObject(
                $sObjectType, 
                $iObjectId
            );
        } elseif ($sPrefix == 'remove') {
            return $this->removeObject(
                $sObjectType, 
                $iObjectId
            );
        } elseif ($sPrefix == 'ismember') {
            $blWithInfo = $aArguments[1];
            
            return $this->objectIsMember(
            	$sObjectType, 
                $iObjectId,
                $blWithInfo
            );
        }

        return null;
    }
    
    /**
     * Returns the sql query.
     * 
     * @param string $sObjectType The object type.
     * @param string $sAction     The sql action.
     * @param array  $aKeys       The keys for the insert query.
     *
     * @return string
     */
    protected function _getSqlQuery($sObjectType, $sAction, $aKeys = array())
    {
        $sSql = '';
        
        if ($sAction == 'select') {
            $sSql = "SELECT object_id as id
    			FROM ".DB_ACCESSGROUP_TO_OBJECT."
    			WHERE group_id = ".$this->getId()." 
    			AND object_type = '".$sObjectType ."'";
        } elseif ($sAction == 'delete') {
            $sSql = "DELETE FROM ".DB_ACCESSGROUP_TO_OBJECT." 
        		WHERE group_id = ".$this->getId()." 
                AND object_type = '".$sObjectType ."'";
        } elseif ($sAction == 'insert') {
            $sSql = "INSERT INTO ".DB_ACCESSGROUP_TO_OBJECT." (
            		group_id, 
            		object_id, 
            		object_type
            	) VALUES ";

            foreach ($aKeys as $sKey) {
                $sKey = trim($sKey);
                $sSql .= "('".$this->getId()."', '".$sKey."', '".$sObjectType."'), ";
            }

            $sSql = rtrim($sSql, ', ');
            $sSql .= " ON DUPLICATE KEY UPDATE group_id = group_id ";
        }
        
        return $sSql;
    }
    
    /**
     * Adds a object of the given type.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object _iId.
     * 
     * @return null
     */
    public function addObject($sObjectType, $iObjectId)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return;
        }
        
        $this->getAccessHandler()->unsetUserGroupsForObject();
        $this->getObjectsFromType($sObjectType);
        
        $oObject = new stdClass;
        $oObject->iId = $iObjectId;
        
        $this->_aObjects[$sObjectType]['real'][$iObjectId] = $oObject;
        $this->_aObjects[$sObjectType]['full'] = -1;
        
        $this->_aAssignedObjects[$sObjectType][$iObjectId] = $iObjectId;
    }
    
    /**
     * Removes a object of the given type.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $sObjectId   The object _iId.
     * 
     * @return null
     */
    public function removeObject($sObjectType, $sObjectId)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return;
        }
        
        $this->getAccessHandler()->unsetUserGroupsForObject();
        $this->getObjectsFromType($sObjectType);
        
        unset($this->_aObjects[$sObjectType]['real'][$sObjectId]);
        $this->_aObjects[$sObjectType]['full'] = -1;
        
        unset($this->_aSingleObjects[$sObjectType][$sObjectId]);
        unset($this->_aAssignedObjects[$sObjectType][$sObjectId]);
    }
    
    /**
     * Returns the assigned _aObjects.
     * 
     * @param string $sObjectType The object type.
     * 
     * @return array
     */
    protected function _getAssignedObjects($sObjectType)
    {
        if ($this->_aAssignedObjects[$sObjectType] !== null) {
            return $this->_aAssignedObjects[$sObjectType];
        }

        $sCacheKey = '_getAssignedObjects|'.$sObjectType.'|'.$this->getId();
        $oUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $aAssignedObjects = $oUserAccessManager->getFromCache($sCacheKey);

        if ($aAssignedObjects !== null) {
            $this->_aAssignedObjects[$sObjectType] = $aAssignedObjects;
        } else {
            /**
             * @var wpdb $wpdb
             */
            global $wpdb;

            $aDbObjects = $wpdb->get_results(
                $this->_getSqlQuery($sObjectType, 'select')
            );

            $this->_aAssignedObjects[$sObjectType] = array();

            foreach ($aDbObjects as $oDbObject) {
                $this->_aAssignedObjects[$sObjectType][$oDbObject->id]
                    = $oDbObject->id;
            }

            $oUserAccessManager->addToCache($sCacheKey, $this->_aAssignedObjects[$sObjectType]);
        }
        
        return $this->_aAssignedObjects[$sObjectType];
    }
    
    /**
     * Checks if the object is assigned to the group.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object _iId.
     * 
     * @return boolean
     */
    protected function _isObjectAssignedToGroup($sObjectType, $iObjectId)
    {
        $aAssignedObjects = $this->_getAssignedObjects($sObjectType);
        return isset($aAssignedObjects[$iObjectId]);
    }
    
    /**
     * Unset the objects.
     * 
     * @param string  $sObjectType The object type.
     * @param boolean $blPlusRemove If true also database entries will remove.
     * 
     * @return null;
     */
    public function unsetObjects($sObjectType, $blPlusRemove = false)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return;
        }
        
        if ($blPlusRemove) {
            $this->_deleteObjectsFromDb($sObjectType);
        }
        
        $this->_aObjects[$sObjectType] = array(
    		'real' => array(),
        	'full' => array(),
        );
    }
    
    /**
     * Removes all _aObjects from the user group.
     * 
     * @param string $sObjectType The object type.
     * 
     * @return null
     */
    protected function _deleteObjectsFromDb($sObjectType)
    {
        if (isset($this->_iId)) {
            /**
             * @var wpdb $wpdb
             */
            global $wpdb;
        
            $wpdb->query(
            	$this->_getSqlQuery($sObjectType, 'delete')
            );
        }
    }
    
	/**
     * Checks if the given object is a member of the group.
     * 
     * @param string   $sObjectType The object type.
     * @param integer  $iObjectId   The _iId of the object which should be checked.
     * @param boolean  $blWithInfo   If true then we return additional info.
     * 
     * @return boolean|array
     */
    public function objectIsMember($sObjectType, $iObjectId, $blWithInfo = false)
    {
        $sCacheKey = $sObjectType.'|'.$iObjectId;
        $sCacheKey .= ($blWithInfo) ? '|wi' : '|ni';

        if (!isset($this->_aObjectIsMember[$sCacheKey])) {
            $this->_aObjectIsMember[$sCacheKey] = false;

            if ($this->isValidObjectType($sObjectType)) {
                $oObject = $this->_getSingleObject($sObjectType, $iObjectId, 'full');

                if ($oObject !== null) {
                    if ($blWithInfo && isset($oObject->recursiveMember)) {
                        $this->_aObjectIsMember[$sCacheKey] = $oObject->recursiveMember;
                    } else {
                        $this->_aObjectIsMember[$sCacheKey] = true;
                    }
                }
            }
        }

        return $this->_aObjectIsMember[$sCacheKey];
    }

    /**
     * Returns all objects of the given type.
     * 
     * @param string $sObjectType The object type.
     * @param string $sType       The return type, could be real or full.
     * 
     * @return array
     */
    function getObjectsFromType($sObjectType, $sType = 'real')
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return null;
        }
        
        if ($this->_iId == null || $sType != 'real' && $sType != 'full') {
            return array();
        }
        
        if (isset($this->_aObjects[$sObjectType])
            && isset($this->_aObjects[$sObjectType][$sType])
            && $this->_aObjects[$sObjectType][$sType] != -1
        ) {
            return $this->_aObjects[$sObjectType][$sType];
        } else {
            $this->_aObjects[$sObjectType][$sType] = array();
        }

        $aObjectIds = $this->_getAssignedObjects($sObjectType);

        foreach ($aObjectIds as $sObjectId) {
            $oObject = $this->_getSingleObject($sObjectType, $sObjectId, $sType);
            
            if ($oObject !== null) {
                $this->_aObjects[$sObjectType][$sType][$oObject->id] = $oObject;
            }
        }
        
        if ($sType == 'full' && $this->getAccessHandler()->isPostableType($sObjectType) && $sObjectType != 'role') {
            if ($sObjectType == 'category') {
                $this->_aObjects[$sObjectType][$sType] = $this->getFullCategories($this->_aObjects[$sObjectType][$sType]);
            } elseif ($sObjectType == 'user') {
                 $this->_aObjects[$sObjectType][$sType] = $this->getFullUsers();
            } else {
                $oPlObject = $this->getAccessHandler()->getPlObject($sObjectType);
                $this->_aObjects[$sObjectType][$sType] = $oPlObject['reference']->{$oPlObject['getFullObjects']}(
                    $this->_aObjects[$sObjectType][$sType],
                    $this
                );
            }
        }
        
        return $this->_aObjects[$sObjectType][$sType];
    }
    
    /**
     * Returns a single object.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The _iId of the object which should be checked.
     * @param string  $sType       The return type. Can be real or full.
     * 
     * @return object
     */
    protected function _getSingleObject($sObjectType, $iObjectId, $sType)
    {
        if (!isset($this->_aSingleObjects[$sObjectType])
            || !isset($this->_aSingleObjects[$sObjectType][$iObjectId])
        ) {
            $this->_aSingleObjects[$sObjectType][$iObjectId] = null;
            $aIsRecursiveMember = array();

            if ($sType == 'full' && $sObjectType != 'role') {
                if ($sObjectType == 'category') {
                    $aIsRecursiveMember = $this->_getFullCategory($iObjectId);
                } elseif ($sObjectType == 'user') {
                    $aIsRecursiveMember = $this->_getFullUser($iObjectId);
                } elseif ($this->getAccessHandler()->isPostableType($sObjectType)) {
                    $aIsRecursiveMember = $this->_getFullPost($sObjectType, $iObjectId);
                } else {
                    $aIsRecursiveMember = $this->_getFullPlObject($sObjectType, $iObjectId);
                }
            }

            if ($aIsRecursiveMember != array()
                || $this->_isObjectAssignedToGroup($sObjectType, $iObjectId)
            ) {
                $oObject = new stdClass();
                $oObject->id = $iObjectId;

                if ($aIsRecursiveMember != array()) {
                    $oObject->recursiveMember = $aIsRecursiveMember;
                }

                $this->_aSingleObjects[$sObjectType][$iObjectId] = $oObject;
            }
        }

        return $this->_aSingleObjects[$sObjectType][$iObjectId];
    }

    
    /*
     * Group users functions.
     */
    
    /**
     * Returns a single user.
     * 
     * @param integer $iObjectId The object _iId.
     * 
     * @return object
     */
    protected function _getFullUser($iObjectId)
    {
        $aIsRecursiveMember = array();
        
        global $wpdb;
        
        $oCurUserData = get_userdata($iObjectId);
        
        if (isset($oCurUserData->{$wpdb->prefix . "capabilities"})) {
            $aCapabilities = $oCurUserData->{$wpdb->prefix . "capabilities"};
        } else {
            $aCapabilities = array();
        }
        
        $aRole = (count($aCapabilities) > 0) ? array_keys($aCapabilities) : array('norole');
        $sRole = $aRole[0];
        $aObjects = $this->getObjectsFromType('role');

        if (isset($aObjects[$sRole])) {
            $oRoleObject = new stdClass();
            $oRoleObject->name = $sRole;
            
            $aIsRecursiveMember = array('role' => array());
            $aIsRecursiveMember['role'][] = $oRoleObject;
        }

        return $aIsRecursiveMember;
    }
    
    /**
     * Returns the users in the group
     * 
     * @return array
     */
    public function getFullUsers()
    {
        $sCacheKey = 'getFullUsers';
        $oUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $aFullUsers = $oUserAccessManager->getFromCache($sCacheKey);

        if ($aFullUsers === null) {
            /**
             * @var wpdb $wpdb
             */
            global $wpdb;

            $aDbUsers = $wpdb->get_results(
                "SELECT ID, user_nicename
                FROM ".$wpdb->users
            );

            $aFullUsers = array();

            if (isset($aDbUsers)) {
                foreach ($aDbUsers as $oDbUser) {
                    $oUser = $this->_getSingleObject('user', $oDbUser->ID, 'full');

                    if ($oUser !== null) {
                        $aFullUsers[$oUser->id] = $oUser;
                    }
                }
            }

            $oUserAccessManager->addToCache($sCacheKey, $aFullUsers);
        }

        return $aFullUsers;
    }
    

    /*
     * Group categories functions.
     */
    
    /**
     * Returns a single category.
     * 
     * @param integer $iObjectId The object _iId.
     * 
     * @return object
     */
    protected function _getFullCategory($iObjectId)
    {
        $oCategory = $this->getAccessHandler()->getUserAccessManager()->getCategory($iObjectId);

        $aIsRecursiveMember = array();
        
        $oUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $aUamOptions = $oUserAccessManager->getAdminOptions();

        if ($aUamOptions['lock_recursive'] == 'true') {
            if (isset($oCategory->parent)
                && !is_null($oCategory->parent)
            ) {
                $oParentCategory = $this->_getSingleObject(
                    'category',
                    $oCategory->parent,
                    'full'
                );

                if ($oParentCategory !== null) {
                    $oCurCategory = $this->getAccessHandler()->getUserAccessManager()->getCategory($iObjectId);
                    $oParentCategory->name = $oCurCategory->name;
                    $aIsRecursiveMember['category'][] = $oParentCategory;
                }
            }
        }

        return $aIsRecursiveMember;
    }
    
    /**
     * Returns the categories in the group
     * 
     * @param array $aCategories The real categories.
     * 
     * @return array
     */
    public function getFullCategories($aCategories)
    {
        $aUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $aUamOptions = $aUserAccessManager->getAdminOptions();

        foreach ($aCategories as $oCategory) {
            if ($oCategory != null) {
                if ($aUamOptions['lock_recursive'] == 'true') {
                    //We have to remove the filter to get all categories
                    $blRemoveSuccess = remove_filter(
                    	'get_terms', 
                        array(
                            $this->getAccessHandler()->getUserAccessManager(), 
                            'showCategory'
                        )
                    );
                    
                    if ($blRemoveSuccess) {
                        $aArgs = array(
                            'child_of' => $oCategory->id,
                            'hide_empty' => false
                        );
                        
                        $aCategoryChildren = get_categories($aArgs);
                        
                        add_filter(
                        	'get_terms', 
                            array($aUserAccessManager, 'showCategory')
                        );
                        
                        
                        foreach ($aCategoryChildren as $oCategoryChild) {
                            $oCurCategoryChild = new stdClass();
                            $oCurCategoryChild->id = $oCategoryChild->term_id;
                            $oCurCategoryChild->name = $oCategoryChild->name;
                            
                            $oCurCategoryChild->recursiveMember = array('category' => array());
                            $oCurCategoryChild->recursiveMember['category'][] = $oCurCategoryChild;
                            $aCategories[$oCurCategoryChild->id] = $oCurCategoryChild;
                        }
                    }
                }
            
                $aCategories[$oCategory->id] = $oCategory;
            }
        }
        
        return $aCategories;
    }
    
    
    /*
     * Group posts functions.
     */

    /**
     * Checks if the give post in the give category. 
     * 
     * @param integer $iPostId     The post _iId.
     * @param integer $iCategoryId The category _iId.
     * 
     * @return boolean
     */
    protected function _isPostInCategory($iPostId, $iCategoryId)
    {
        if ($this->_aObjectsInCategory === null) {
            $this->_aObjectsInCategory = array();
            $sCacheKey = '_isPostInCategory';
            $oUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
            $aObjectsInCategory = $oUserAccessManager->getFromCache($sCacheKey);

            if ($aObjectsInCategory === null) {
                /**
                 * @var wpdb $wpdb
                 */
                global $wpdb;

                $aDbObjects = $wpdb->get_results(
                    "SELECT tr.object_id AS objectId, tt.term_id AS categoryId
                    FROM ".$wpdb->term_relationships." AS tr,
                    ".$wpdb->term_taxonomy." AS tt
                WHERE tr.term_taxonomy_id = tt.term_taxonomy_id
                    AND tt.taxonomy = 'category'"
                );

                foreach ($aDbObjects as $oDbObject) {
                    if (!isset($this->_aObjectsInCategory[$oDbObject->objectId])) {
                        $this->_aObjectsInCategory[$oDbObject->objectId] = array();
                    }

                    $this->_aObjectsInCategory[$oDbObject->objectId][$oDbObject->categoryId] = $oDbObject->categoryId;
                }

                $oUserAccessManager->addToCache($sCacheKey, $this->_aObjectsInCategory);
            } else {
                $this->_aObjectsInCategory = $aObjectsInCategory;
            }
        }

        return (isset($this->_aObjectsInCategory[$iPostId])
            &&  isset($this->_aObjectsInCategory[$iPostId][$iCategoryId]));
    }
    
    /**
     * Returns the membership of a single post.
     * 
     * @param string  $sPostType The post type needed for the intern representation.
     * @param integer $iObjectId The object _iId.
     * 
     * @return object
     */
    protected function _getFullPost($sPostType, $iObjectId)
    {
        $aIsRecursiveMember = array();
        $oPost = $this->getAccessHandler()->getUserAccessManager()->getPost($iObjectId);
        $aUamOptions = $this->getAccessHandler()->getUserAccessManager()->getAdminOptions();

        foreach ($this->getObjectsFromType('category', 'full') as $oCategory) {
            if ($this->_isPostInCategory($oPost->ID, $oCategory->id)) {
                $oCategoryObject = $this->getAccessHandler()->getUserAccessManager()->getCategory($oCategory->id);
                $oCategory->name = $oCategoryObject->name;
                
                $aIsRecursiveMember['category'][] = $oCategory;
            }
        }
        
        if ($oPost->post_parent == 0
            && $oPost->post_type == 'post'
            && $this->getAccessHandler()->getUserAccessManager()->getWpOption('show_on_front') == 'page'
            && $this->getAccessHandler()->getUserAccessManager()->getWpOption('page_for_posts') != $iObjectId
        ) {
            $iParentId = $this->getAccessHandler()->getUserAccessManager()->getWpOption('page_for_posts');
        } else {
            $iParentId = $oPost->post_parent;
        }

        if ($iParentId != 0
        	&& $aUamOptions['lock_recursive'] == 'true'
        ) {
            $oParent = $this->getAccessHandler()->getUserAccessManager()->getPost($iParentId);
            
            $oParentPost = $this->_getSingleObject(
                $oParent->post_type,
                $iParentId,
                'full'
            );
    
            if ($oParentPost !== null) {
                $oPostObject = $this->getAccessHandler()->getUserAccessManager()->getPost($oParentPost->id);
                $oParentPost->name = $oPostObject->post_title;

                $aIsRecursiveMember[$oParent->post_type][] = $oParentPost;
            }
        }

        return $aIsRecursiveMember;
    }

    
    /*
     * Group pluggable _aObjects functions.
     */
    
    /**
     * Returns a the recursive membership for a pluggable object.
     * 
     * @param string  $sObjectType The pluggable object type.
     * @param integer $iObjectId   The object _iId.
     * 
     * @return array
     */
    protected function _getFullPlObject($sObjectType, $iObjectId)
    {
        $blIsRecursiveMember = array();
        
        $oPlObject = $this->getAccessHandler()->getPlObject($sObjectType);
        
        if (isset($oPlObject['reference'])
            && isset($oPlObject['getFull'])
        ) {
            $aPlRecMember = $oPlObject['reference']->{$oPlObject['getFull']}(
                $iObjectId,
                $this
            );
            
            if (is_array($aPlRecMember)) {
                $blIsRecursiveMember = $aPlRecMember;
            }
        }

        return $blIsRecursiveMember;
    }
}