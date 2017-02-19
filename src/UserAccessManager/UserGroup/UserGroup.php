<?php
/**
 * UserGroup.php
 *
 * The UserGroup class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class UserGroup
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroup
{
    const OBJECTS_FULL = 'full';
    const OBJECTS_REAL = 'real';

    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var Cache
     */
    protected $_oCache;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var int
     */
    protected $_iId = null;

    /**
     * @var string
     */
    protected $_sGroupName = null;

    /**
     * @var string
     */
    protected $_sGroupDesc = null;

    /**
     * @var string
     */
    protected $_sReadAccess = null;

    /**
     * @var string
     */
    protected $_sWriteAccess = null;

    /**
     * @var string
     */
    protected $_sIpRange = null;

    /**
     * @var array
     */
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
     * UserGroup constructor.
     *
     * @param Wordpress     $oWrapper
     * @param Database      $oDatabase
     * @param Config        $oConfig
     * @param Cache         $oCache
     * @param Util          $oUtil
     * @param ObjectHandler $oObjectHandler
     * @param null          $iId
     */
    public function __construct(
        Wordpress $oWrapper,
        Database $oDatabase,
        Config $oConfig,
        Cache $oCache,
        Util $oUtil,
        ObjectHandler $oObjectHandler,
        $iId = null
    )
    {
        $this->_oWrapper = $oWrapper;
        $this->_oDatabase = $oDatabase;
        $this->_oConfig = $oConfig;
        $this->_oCache = $oCache;
        $this->_oUtil = $oUtil;
        $this->_oObjectHandler = $oObjectHandler;

        if ($iId !== null) {
            $this->_iId = $iId;

            $aDbUserGroup = $this->_oDatabase->getRow(
                "SELECT *
                FROM {$this->_oDatabase->getUserGroupTable()}
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
        $aAllObjectTypes = $this->_oObjectHandler->getAllObjectTypes();

        foreach ($aAllObjectTypes as $sObjectType) {
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
     * Deletes the user group.
     *
     * @return boolean
     */
    public function delete()
    {
        if ($this->_iId == null) {
            return false;
        }

        $this->_oDatabase->delete(
            $this->_oDatabase->getUserGroupTable(),
            array('ID' => $this->_iId)
        );

        $aAllObjectTypes = $this->_oObjectHandler->getAllObjectTypes();

        foreach ($aAllObjectTypes as $sObjectType) {
            $this->_deleteObjectsFromDb($sObjectType);
        }

        return true;
    }

    /**
     * Saves the user group.
     *
     * @param boolean $blRemoveOldAssignments If false we will not remove old entries.
     *
     * @return bool
     */
    public function save($blRemoveOldAssignments = true)
    {
        $aAllObjectTypes = $this->_oObjectHandler->getAllObjectTypes();

        if ($this->_iId == null) {
            $mReturn = $this->_oDatabase->insert(
                $this->_oDatabase->getUserGroupTable(),
                array(
                    'groupname' => $this->_sGroupName,
                    'groupdesc' => $this->_sGroupDesc,
                    'read_access' => $this->_sReadAccess,
                    'write_access' => $this->_sWriteAccess,
                    'ip_range' => $this->_sIpRange
                )
            );

            $this->_iId = $this->_oDatabase->getLastInsertId();
        } else {
            $mReturn = $this->_oDatabase->update(
                $this->_oDatabase->getUserGroupTable(),
                array(
                    'groupname' => $this->_sGroupName,
                    'groupdesc' => $this->_sGroupDesc,
                    'read_access' => $this->_sReadAccess,
                    'write_access' => $this->_sWriteAccess,
                    'ip_range' => $this->_sIpRange
                ),
                array('ID' => $this->_iId)
            );

            if ($blRemoveOldAssignments === true) {
                foreach ($aAllObjectTypes as $sObjectType) {
                    //Delete form database
                    $this->_deleteObjectsFromDb($sObjectType);
                }
            }
        }

        foreach ($aAllObjectTypes as $sObjectType) {
            $aKeys = $this->_getAssignedObjects($sObjectType);

            if (count($aKeys) > 0) {
                $aQueryValues = array();

                foreach ($aKeys as $sKey) {
                    $sKey = trim($sKey);
                    $aQueryValues[] = " ('{$this->getId()}', '{$sKey}', '{$sObjectType}') ";
                }

                $sValues = implode(', ', $aQueryValues);
                $sQuery = "
                    INSERT INTO {$this->_oDatabase->getUserGroupToObjectTable()} (
                        group_id,
                        object_id,
                        object_type
                    ) VALUES {$sValues} ON DUPLICATE KEY UPDATE group_id = group_id ";

                $this->_oDatabase->query($sQuery);
            }
        }

        return ($mReturn !== false);
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

        if (!isset($aIpRange[0])) {
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
     * Adds a object of the given type.
     *
     * @param string $sObjectType The object type.
     * @param string $sObjectId   The object id.
     */
    public function addObject($sObjectType, $sObjectId)
    {
        if (!$this->_oObjectHandler->isValidObjectType($sObjectType)) {
            return;
        }

        $this->getObjectsFromType($sObjectType);

        $oObject = new \stdClass();
        $oObject->sId = $sObjectId;

        $this->_aObjects[$sObjectType][self::OBJECTS_REAL][$sObjectId] = $oObject;
        $this->_aObjects[$sObjectType][self::OBJECTS_FULL] = -1;

        $this->_aAssignedObjects[$sObjectType][$sObjectId] = $sObjectId;
    }

    /**
     * Removes a object of the given type.
     *
     * @param string  $sObjectType The object type.
     * @param integer $sObjectId   The object id.
     */
    public function removeObject($sObjectType, $sObjectId)
    {
        if (!$this->_oObjectHandler->isValidObjectType($sObjectType)) {
            return;
        }

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

        $sCacheKey = $this->_oCache->generateCacheKey('_getAssignedObjects', $sObjectType, $this->getId());
        $aAssignedObjects = $this->_oCache->getFromCache($sCacheKey);

        if ($aAssignedObjects !== null) {
            $this->_aAssignedObjects[$sObjectType] = $aAssignedObjects;
        } else {
            $sQuery = "
                SELECT object_id as id
                FROM {$this->_oDatabase->getUserGroupToObjectTable()}
                WHERE group_id = {$this->getId()}
                  AND object_type = '{$sObjectType}'";

            $aDbObjects = $this->_oDatabase->getResults($sQuery);
            $this->_aAssignedObjects[$sObjectType] = array();

            foreach ($aDbObjects as $oDbObject) {
                $this->_aAssignedObjects[$sObjectType][$oDbObject->id] = $oDbObject->id;
            }

            $this->_oCache->addToCache($sCacheKey, $this->_aAssignedObjects[$sObjectType]);
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
     * @param string  $sObjectType  The object type.
     * @param boolean $blPlusRemove If true also database entries will remove.
     */
    public function unsetObjects($sObjectType, $blPlusRemove = false)
    {
        if (!$this->_oObjectHandler->isValidObjectType($sObjectType)) {
            return;
        }

        if ($blPlusRemove) {
            $this->_deleteObjectsFromDb($sObjectType);
        }

        $this->_aAssignedObjects[$sObjectType] = array();
        $this->_oCache->flushCache();

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
            $sQuery = "
                DELETE FROM {$this->_oDatabase->getUserGroupToObjectTable()}
                WHERE group_id = {$this->getId()}
                  AND object_type = '{$sObjectType}'";

            $this->_oDatabase->query($sQuery);
        }
    }

    /**
     * Checks if the given object is a member of the group.
     *
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The _iId of the object which should be checked.
     * @param boolean $blWithInfo  If true then we return additional info.
     *
     * @return boolean|array
     */
    public function objectIsMember($sObjectType, $iObjectId, $blWithInfo = false)
    {
        $sCacheKey = $sObjectType.'|'.$iObjectId;
        $sCacheKey .= ($blWithInfo) ? '|wi' : '|ni';

        if (!isset($this->_aObjectIsMember[$sCacheKey])) {
            $this->_aObjectIsMember[$sCacheKey] = false;

            if ($this->_oObjectHandler->isValidObjectType($sObjectType)) {
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
        if (!$this->_oObjectHandler->isValidObjectType($sObjectType)
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
            && $this->_oObjectHandler->isPostableType($sObjectType)
            && $sObjectType != ObjectHandler::ROLE_OBJECT_TYPE
        ) {
            if ($sObjectType === ObjectHandler::TERM_OBJECT_TYPE) {
                $this->_aObjects[$sObjectType][$sType] = $this->getFullTerms($this->_aObjects[$sObjectType][$sType]);
            } elseif ($sObjectType === ObjectHandler::USER_OBJECT_TYPE) {
                $this->_aObjects[$sObjectType][$sType] = $this->getFullUsers();
            } else {
                $oPlObject = $this->_oObjectHandler->getPluggableObject($sObjectType);
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

            if ($sType == self::OBJECTS_FULL && $sObjectType != ObjectHandler::ROLE_OBJECT_TYPE) {
                if ($sObjectType === ObjectHandler::TERM_OBJECT_TYPE) {
                    $aIsRecursiveMember = $this->_getFullTerm($iObjectId);
                } elseif ($sObjectType == ObjectHandler::USER_OBJECT_TYPE) {
                    $aIsRecursiveMember = $this->_getFullUser($iObjectId);
                } elseif ($this->_oObjectHandler->isPostableType($sObjectType)) {
                    $aIsRecursiveMember = $this->_getFullPost($iObjectId);
                } elseif ($this->_oObjectHandler->isValidObjectType($sObjectType)) {
                    $aIsRecursiveMember = $this->_getFullPluggableObject($sObjectType, $iObjectId);
                }
            }

            if ($aIsRecursiveMember != array()
                || $this->_isObjectAssignedToGroup($sObjectType, $iObjectId)
            ) {
                $oObject = new \stdClass();
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
        $oCurUserData = $this->_oObjectHandler->getUser($iObjectId);
        $sCapabilitiesTable = $this->_oDatabase->getCapabilitiesTable();

        if (isset($oCurUserData->{$sCapabilitiesTable})) {
            $aCapabilities = $oCurUserData->{$sCapabilitiesTable};
        } else {
            $aCapabilities = array();
        }

        $aRoles = (is_array($aCapabilities) && count($aCapabilities) > 0) ? array_keys($aCapabilities) : array('norole');
        $aObjects = $this->getObjectsFromType('role');

        foreach ($aRoles as $sRole) {
            if (isset($aObjects[$sRole])) {
                $oRoleObject = new \stdClass();
                $oRoleObject->name = $sRole;

                $aIsRecursiveMember = array(ObjectHandler::ROLE_OBJECT_TYPE => array());
                $aIsRecursiveMember[ObjectHandler::ROLE_OBJECT_TYPE][] = $oRoleObject;
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
        $aFullUsers = $this->_oCache->getFromCache($sCacheKey);

        if ($aFullUsers === null) {
            $aDbUsers = $this->_oDatabase->getResults(
                "SELECT ID, user_nicename
                FROM {$this->_oDatabase->getUsersTable()}"
            );

            $aFullUsers = array();

            if (isset($aDbUsers)) {
                foreach ($aDbUsers as $oDbUser) {
                    $oUser = $this->_getSingleObject(ObjectHandler::USER_OBJECT_TYPE, $oDbUser->ID, self::OBJECTS_FULL);

                    if ($oUser !== null) {
                        $aFullUsers[$oUser->id] = $oUser;
                    }
                }
            }

            $this->_oCache->addToCache($sCacheKey, $aFullUsers);
        }

        return $aFullUsers;
    }


    /*
     * Group categories functions.
     */

    /**
     * Returns a single category.
     *
     * @param integer $iTermId The object id.
     *
     * @return object[]
     */
    protected function _getTermParents($iTermId)
    {
        $oTerm = $this->_oObjectHandler->getTerm($iObjectId);

        $aIsRecursiveMember = array();

        if ($this->_oConfig->lockRecursive() === true
            && isset($oTerm->parent)
            && !is_null($oTerm->parent)
        ) {
            $oParentTerm = $this->_getSingleObject(
                ObjectHandler::TERM_OBJECT_TYPE,
                $oTerm->parent,
                self::OBJECTS_FULL
            );

            if ($oParentTerm !== null) {
                $oCurrentTerm = $this->_oObjectHandler->getTerm($iObjectId);
                $oParentTerm->name = $oCurrentTerm->name;
                $aIsRecursiveMember[ObjectHandler::TERM_OBJECT_TYPE][] = $oParentTerm;
            }
        }

        /*
        $aIsRecursiveMember = array();

        if ($this->_oConfig->lockRecursive() === true) {
            $aTermMap = $this->_oObjectHandler->getTermTreeMap();

            if (isset($aTermMap[ObjectHandler::TREE_MAP_PARENTS][$iTermId])) {
                $aIsRecursiveMember[ObjectHandler::TERM_OBJECT_TYPE]
                    = $aTermMap[ObjectHandler::TREE_MAP_PARENTS][$iTermId];
            }
        }
        */

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
        foreach ($aTerms as $oTerm) {
            if ($oTerm !== null) {
                //TODO refactor
                /*if ($this->_oConfig->lockRecursive() === true) {
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
                            $oCurrentTermChild = new \stdClass();
                            $oCurrentTermChild->id = $oTermChild->term_id;
                            $oCurrentTermChild->name = $oTermChild->name;

                            $oCurrentTermChild->recursiveMember = array(ObjectHandler::TERM_OBJECT_TYPE => array());
                            $oCurrentTermChild->recursiveMember[ObjectHandler::TERM_OBJECT_TYPE][] = $oTermChild;
                            $aTerms[$oCurrentTermChild->id] = $oCurrentTermChild;
                        }
                    }
                }*/

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
            $aObjectsInCategory = $this->_oCache->getFromCache($sCacheKey);

            if ($aObjectsInCategory === null) {
                //TODO Use term_id instead of term_taxonomy_id use join with wp_term_taxonomy
                $aDbObjects = $this->_oDatabase->getResults(
                    "SELECT tr.object_id AS objectId, tr.term_taxonomy_id AS termId
                    FROM {$this->_oDatabase->getTermRelationshipsTable()} AS tr"
                );

                foreach ($aDbObjects as $oDbObject) {
                    if (!isset($this->_aObjectsInTerm[$oDbObject->objectId])) {
                        $this->_aObjectsInTerm[$oDbObject->objectId] = array();
                    }

                    $this->_aObjectsInTerm[$oDbObject->objectId][$oDbObject->termId] = $oDbObject->termId;
                }

                $this->_oCache->addToCache($sCacheKey, $this->_aObjectsInTerm);
            } else {
                $this->_aObjectsInTerm = $aObjectsInCategory;
            }
        }

        return (isset($this->_aObjectsInTerm[$iPostId])
            && isset($this->_aObjectsInTerm[$iPostId][$iTermId]));
    }

    /**
     * Returns the membership of a single post.
     *
     * @param integer $iObjectId The object id.
     *
     * @return array
     */
    protected function _getFullPost($iObjectId)
    {
        $aIsRecursiveMember = array();
        $oPost = $this->_oObjectHandler->getPost($iObjectId);
        $aTerms = $this->getObjectsFromType(ObjectHandler::TERM_OBJECT_TYPE, self::OBJECTS_FULL);

        foreach ($aTerms as $oTerm) {
            if ($this->_isPostInTerm($oPost->ID, $oTerm->id)) {
                $oTermObject = $this->_oObjectHandler->getTerm($oTerm->id);

                if ($oTermObject !== null) {
                    $oTerm->name = $oTermObject->name;
                }

                $aIsRecursiveMember[ObjectHandler::TERM_OBJECT_TYPE][] = $oTermObject;
            }
        }

        if ($oPost->post_parent == 0
            && $oPost->post_type === ObjectHandler::POST_OBJECT_TYPE
            && $this->_oConfig->getWpOption('show_on_front') == ObjectHandler::PAGE_OBJECT_TYPE
            && $this->_oConfig->getWpOption('page_for_posts') != $iObjectId
        ) {
            $iParentId = $this->_oConfig->getWpOption('page_for_posts');
        } else {
            $iParentId = $oPost->post_parent;
        }

        if ($iParentId != 0
            && $this->_oConfig->lockRecursive() === true
        ) {
            $oParent = $this->_oObjectHandler->getPost($iParentId);

            $oParentPost = $this->_getSingleObject(
                $oParent->post_type,
                $iParentId,
                self::OBJECTS_FULL
            );

            if ($oParentPost !== null) {
                $oPostObject = $this->_oObjectHandler->getPost($oParentPost->id);
                $oParentPost->name = $oPostObject->post_title;

                $aIsRecursiveMember[$oParent->post_type][] = $oParentPost;
            }
        }

        return $aIsRecursiveMember;
    }


    /*
     * Group pluggable objects functions.
     */

    /**
     * Returns a the recursive membership for a pluggable object.
     *
     * @param string  $sObjectType The pluggable object type.
     * @param integer $iObjectId   The object id.
     *
     * @return array
     */
    protected function _getFullPluggableObject($sObjectType, $iObjectId)
    {
        $blIsRecursiveMember = array();

        $oPlObject = $this->_oObjectHandler->getPluggableObject($sObjectType);

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
