<?php
/**
 * UamUserGroup.php
 * 
 * The UamUserGroup class file.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
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
    const OBJECTS_FULL = 'full';
    const OBJECTS_REAL = 'real';

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
    protected $_aObjectsInTerm = null;
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
            $oDatabase = $this->_oAccessHandler->getUserAccessManager()->getDatabase();
            $this->_iId = $iId;
            
            $aDbUserGroup = $oDatabase->get_row(
                "SELECT *
                FROM ".DB_ACCESSGROUP."
                WHERE ID = {$this->getId()}
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
                self::OBJECTS_REAL => -1,
                self::OBJECTS_FULL => -1
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

        $oDatabase = $this->getAccessHandler()->getUserAccessManager()->getDatabase();
        
        $oDatabase->query(
            "DELETE FROM " . DB_ACCESSGROUP . "
            WHERE ID = {$this->_iId} LIMIT 1"
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
     */
    public function save($blRemoveOldAssignments = true)
    {
        $oDatabase = $this->getAccessHandler()->getUserAccessManager()->getDatabase();
        
        if ($this->_iId == null) {
            $sInsertQuery = "
                INSERT INTO " . DB_ACCESSGROUP . " (
                    ID,
                    groupname,
                    groupdesc,
                    read_access,
                    write_access,
                    ip_range
                )
                VALUES (
                    NULL,
                    '{$this->_sGroupName}',
                    '{$this->_sGroupDesc}',
                    '{$this->_sReadAccess}',
                    '{$this->_sWriteAccess}',
                    '{$this->_sIpRange}'
                )";

            $oDatabase->query($sInsertQuery);
            
            $this->_iId = $oDatabase->insert_id;
        } else {
            $sAccessGroupTable = DB_ACCESSGROUP;
            $sUpdateQuery = "
                UPDATE {$sAccessGroupTable} 
                SET groupname = '{$this->_sGroupName}',
                    groupdesc = '{$this->_sGroupDesc}',
                    read_access = '{$this->_sReadAccess}',
                    write_access = '{$this->_sWriteAccess}',
                    ip_range = '{$this->_sIpRange}'
                WHERE ID = {$this->_iId}";

            $oDatabase->query($sUpdateQuery);

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
                $oDatabase->query($sSql);
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
            $this->addObject(
                $sObjectType, 
                $iObjectId
            );
        } elseif ($sPrefix == 'remove') {
            $this->removeObject(
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
        $sAccessGroupToObjectTable = DB_ACCESSGROUP_TO_OBJECT;
        
        if ($sAction == 'select') {
            $sSql = "
                SELECT object_id as id
                FROM {$sAccessGroupToObjectTable}
                WHERE group_id = {$this->getId()}
                  AND object_type = '{$sObjectType}'";
        } elseif ($sAction == 'delete') {
            $sSql = "
                DELETE FROM {$sAccessGroupToObjectTable}
                WHERE group_id = {$this->getId()}
                  AND object_type = '{$sObjectType}'";
        } elseif ($sAction == 'insert') {
            $sSql = "
                INSERT INTO {$sAccessGroupToObjectTable} (
                    group_id,
                    object_id,
                    object_type
                ) VALUES ";

            foreach ($aKeys as $sKey) {
                $sKey = trim($sKey);
                $sSql .= "('{$this->getId()}', '{$sKey}', '{$sObjectType}'), ";
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
     * @param integer $iObjectId   The object id.
     */
    public function addObject($sObjectType, $iObjectId)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return;
        }
        
        $this->getAccessHandler()->unsetUserGroupsForObject();
        $this->getObjectsFromType($sObjectType);
        
        $oObject = new stdClass();
        $oObject->iId = $iObjectId;
        
        $this->_aObjects[$sObjectType][self::OBJECTS_REAL][$iObjectId] = $oObject;
        $this->_aObjects[$sObjectType][self::OBJECTS_FULL] = -1;
        
        $this->_aAssignedObjects[$sObjectType][$iObjectId] = $iObjectId;
    }
    
    /**
     * Removes a object of the given type.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $sObjectId   The object id.
     */
    public function removeObject($sObjectType, $sObjectId)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return;
        }
        
        $this->getAccessHandler()->unsetUserGroupsForObject();
        $this->getObjectsFromType($sObjectType);
        
        unset($this->_aObjects[$sObjectType][self::OBJECTS_REAL][$sObjectId]);
        $this->_aObjects[$sObjectType][self::OBJECTS_FULL] = -1;
        
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
            $oDatabase = $this->getAccessHandler()->getUserAccessManager()->getDatabase();

            $aDbObjects = $oDatabase->get_results(
                $this->_getSqlQuery($sObjectType, 'select')
            );

            $this->_aAssignedObjects[$sObjectType] = array();

            foreach ($aDbObjects as $oDbObject) {
                $this->_aAssignedObjects[$sObjectType][$oDbObject->id] = $oDbObject->id;
            }

            $oUserAccessManager->addToCache($sCacheKey, $this->_aAssignedObjects[$sObjectType]);
        }
        
        return $this->_aAssignedObjects[$sObjectType];
    }
    
    /**
     * Checks if the object is assigned to the group.
     * 
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object id.
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
     */
    public function unsetObjects($sObjectType, $blPlusRemove = false)
    {
        if (!$this->isValidObjectType($sObjectType)) {
            return;
        }

        if ($blPlusRemove) {
            $this->_deleteObjectsFromDb($sObjectType);
        }

        $this->_aAssignedObjects[$sObjectType] = array();
        $this->getAccessHandler()->getUserAccessManager()->flushCache();

        $this->_aObjects[$sObjectType] = array(
            self::OBJECTS_REAL => array(),
            self::OBJECTS_FULL => array(),
        );
    }
    
    /**
     * Removes all _aObjects from the user group.
     * 
     * @param string $sObjectType The object type.
     */
    protected function _deleteObjectsFromDb($sObjectType)
    {
        if (isset($this->_iId)) {
            $oDatabase = $this->getAccessHandler()->getUserAccessManager()->getDatabase();

            $sQuery = $this->_getSqlQuery($sObjectType, 'delete');
            $oDatabase->query($sQuery);
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
                $oObject = $this->_getSingleObject($sObjectType, $iObjectId, self::OBJECTS_FULL);

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
    public function getObjectsFromType($sObjectType, $sType = self::OBJECTS_REAL)
    {
        if (!$this->isValidObjectType($sObjectType)
            || $this->_iId == null
            || $sType != self::OBJECTS_REAL && $sType != self::OBJECTS_FULL
        ) {
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
        
        if ($sType == self::OBJECTS_FULL
            && $this->getAccessHandler()->isPostableType($sObjectType)
            && $sObjectType != UserAccessManager::ROLE_OBJECT_TYPE
        ) {
            if ($sObjectType === UserAccessManager::TERM_OBJECT_TYPE) {
                $this->_aObjects[$sObjectType][$sType] = $this->getFullTerms($this->_aObjects[$sObjectType][$sType]);
            } elseif ($sObjectType === UserAccessManager::USER_OBJECT_TYPE) {
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

            if ($sType == self::OBJECTS_FULL && $sObjectType != UserAccessManager::ROLE_OBJECT_TYPE) {
                if ($sObjectType === UserAccessManager::TERM_OBJECT_TYPE) {
                    $aIsRecursiveMember = $this->_getFullTerm($iObjectId);
                } elseif ($sObjectType == UserAccessManager::USER_OBJECT_TYPE) {
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
     * @param integer $iObjectId The object id.
     * 
     * @return array
     */
    protected function _getFullUser($iObjectId)
    {
        $aIsRecursiveMember = array();
        
        $oDatabase = $this->getAccessHandler()->getUserAccessManager()->getDatabase();
        $oCurUserData = $this->getAccessHandler()->getUserAccessManager()->getUser($iObjectId);
        
        if (isset($oCurUserData->{$oDatabase->prefix . "capabilities"})) {
            $aCapabilities = $oCurUserData->{$oDatabase->prefix . "capabilities"};
        } else {
            $aCapabilities = array();
        }
        
        $aRoles = (is_array($aCapabilities) && count($aCapabilities) > 0) ? array_keys($aCapabilities) : array('norole');
        $aObjects = $this->getObjectsFromType('role');

        foreach ($aRoles as $sRole) {
            if (isset($aObjects[$sRole])) {
                $oRoleObject = new stdClass();
                $oRoleObject->name = $sRole;

                $aIsRecursiveMember = array(UserAccessManager::ROLE_OBJECT_TYPE => array());
                $aIsRecursiveMember[UserAccessManager::ROLE_OBJECT_TYPE][] = $oRoleObject;
            }
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
            $oDatabase = $this->getAccessHandler()->getUserAccessManager()->getDatabase();

            $aDbUsers = $oDatabase->get_results(
                "SELECT ID, user_nicename
                FROM ".$oDatabase->users
            );

            $aFullUsers = array();

            if (isset($aDbUsers)) {
                foreach ($aDbUsers as $oDbUser) {
                    $oUser = $this->_getSingleObject(UserAccessManager::USER_OBJECT_TYPE, $oDbUser->ID, self::OBJECTS_FULL);

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
     * @param integer $iObjectId The object id.
     * 
     * @return object[]
     */
    protected function _getFullTerm($iObjectId)
    {
        $oTerm = $this->getAccessHandler()->getUserAccessManager()->getTerm($iObjectId);

        $aIsRecursiveMember = array();
        
        $oUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $aUamOptions = $oUserAccessManager->getAdminOptions();

        if ($aUamOptions['lock_recursive'] == 'true'
            && isset($oTerm->parent)
            && !is_null($oTerm->parent)
        ) {
            $oParentTerm = $this->_getSingleObject(
                UserAccessManager::TERM_OBJECT_TYPE,
                $oTerm->parent,
                self::OBJECTS_FULL
            );

            if ($oParentTerm !== null) {
                $oCurrentTerm = $this->getAccessHandler()->getUserAccessManager()->getTerm($iObjectId);
                $oParentTerm->name = $oCurrentTerm->name;
                $aIsRecursiveMember[UserAccessManager::TERM_OBJECT_TYPE][] = $oParentTerm;
            }
        }

        return $aIsRecursiveMember;
    }
    
    /**
     * Returns the terms in the group
     * 
     * @param array $aTerms The real terms.
     * 
     * @return array
     */
    public function getFullTerms($aTerms)
    {
        $oUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
        $aUamOptions = $oUserAccessManager->getAdminOptions();

        foreach ($aTerms as $oTerm) {
            if ($oTerm !== null) {
                if ($aUamOptions['lock_recursive'] == 'true') {
                    $iPriority = has_filter('get_terms', array($this, 'showTerms'));

                    //We have to remove the filter to get all categories
                    $blRemoveSuccess = remove_filter('get_terms', array($oUserAccessManager, 'showTerms'), $iPriority);
                    
                    if ($blRemoveSuccess) {
                        $aArgs = array(
                            'child_of' => $oTerm->id,
                            'hide_empty' => false
                        );
                        
                        $aTermChildren = get_terms($aArgs);
                        add_filter('get_terms', array($oUserAccessManager, 'showTerms'), $iPriority, 2);

                        foreach ($aTermChildren as $oTermChild) {
                            $oCurrentTermChild = new stdClass();
                            $oCurrentTermChild->id = $oTermChild->term_id;
                            $oCurrentTermChild->name = $oTermChild->name;
                            
                            $oCurrentTermChild->recursiveMember = array(UserAccessManager::TERM_OBJECT_TYPE => array());
                            $oCurrentTermChild->recursiveMember[UserAccessManager::TERM_OBJECT_TYPE][] = $oTermChild;
                            $aTerms[$oCurrentTermChild->id] = $oCurrentTermChild;
                        }
                    }
                }
            
                $aTerms[$oTerm->id] = $oTerm;
            }
        }
        
        return $aTerms;
    }
    
    
    /*
     * Group posts functions.
     */

    /**
     * Checks if the give post in the give term.
     * 
     * @param integer $iPostId The post id.
     * @param integer $iTermId The term id.
     * 
     * @return boolean
     */
    protected function _isPostInTerm($iPostId, $iTermId)
    {
        if ($this->_aObjectsInTerm === null) {
            $this->_aObjectsInTerm = array();
            $sCacheKey = '_isPostInTerm';
            $oUserAccessManager = $this->getAccessHandler()->getUserAccessManager();
            $aObjectsInCategory = $oUserAccessManager->getFromCache($sCacheKey);

            if ($aObjectsInCategory === null) {
                $oDatabase = $this->getAccessHandler()->getUserAccessManager()->getDatabase();

                $aDbObjects = $oDatabase->get_results(
                    "SELECT tr.object_id AS objectId, tr.term_taxonomy_id AS termId
                    FROM ".$oDatabase->term_relationships." AS tr"
                );

                foreach ($aDbObjects as $oDbObject) {
                    if (!isset($this->_aObjectsInTerm[$oDbObject->objectId])) {
                        $this->_aObjectsInTerm[$oDbObject->objectId] = array();
                    }

                    $this->_aObjectsInTerm[$oDbObject->objectId][$oDbObject->termId] = $oDbObject->termId;
                }

                $oUserAccessManager->addToCache($sCacheKey, $this->_aObjectsInTerm);
            } else {
                $this->_aObjectsInTerm = $aObjectsInCategory;
            }
        }

        return (isset($this->_aObjectsInTerm[$iPostId])
            &&  isset($this->_aObjectsInTerm[$iPostId][$iTermId]));
    }
    
    /**
     * Returns the membership of a single post.
     *
     * @param string  $sPostType The post type needed for the intern representation.
     * @param integer $iObjectId The object id.
     * 
     * @return array
     */
    protected function _getFullPost($sPostType, $iObjectId)
    {
        $aIsRecursiveMember = array();
        $oPost = $this->getAccessHandler()->getUserAccessManager()->getPost($iObjectId);
        $aUamOptions = $this->getAccessHandler()->getUserAccessManager()->getAdminOptions();
        $aTerms = $this->getObjectsFromType(UserAccessManager::TERM_OBJECT_TYPE, self::OBJECTS_FULL);

        foreach ($aTerms as $oTerm) {
            if ($this->_isPostInTerm($oPost->ID, $oTerm->id)) {
                $oTermObject = $this->getAccessHandler()->getUserAccessManager()->getTerm($oTerm->id);
                $oTerm->name = $oTermObject->name;
                
                $aIsRecursiveMember[UserAccessManager::TERM_OBJECT_TYPE][] = $oTermObject;
            }
        }
        
        if ($oPost->post_parent == 0
            && $oPost->post_type === UserAccessManager::POST_OBJECT_TYPE
            && $this->getAccessHandler()->getUserAccessManager()->getWpOption('show_on_front') == UserAccessManager::PAGE_OBJECT_TYPE
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
                self::OBJECTS_FULL
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
     * @param integer $iObjectId   The object id.
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