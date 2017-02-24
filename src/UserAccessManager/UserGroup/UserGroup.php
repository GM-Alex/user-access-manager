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

    /**
     * @var array
     */
    protected $_aAssignedObjects = array();

    /**
     * @var array
     */
    protected $_aObjectMembership = array();

    /**
     * @var array
     */
    protected $_aFullObjects = array();

    /**
     * UserGroup constructor.
     *
     * @param Wordpress     $oWrapper
     * @param Database      $oDatabase
     * @param Config        $oConfig
     * @param Util          $oUtil
     * @param ObjectHandler $oObjectHandler
     * @param null          $iId
     */
    public function __construct(
        Wordpress $oWrapper,
        Database $oDatabase,
        Config $oConfig,
        Util $oUtil,
        ObjectHandler $oObjectHandler,
        $iId = null
    )
    {
        $this->_oWrapper = $oWrapper;
        $this->_oDatabase = $oDatabase;
        $this->_oConfig = $oConfig;
        $this->_oUtil = $oUtil;
        $this->_oObjectHandler = $oObjectHandler;

        if ($iId !== null) {
            $this->load($iId);
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
        if ($sType === 'string') {
            return $this->_sIpRange;
        }

        return explode(';', $this->_sIpRange);
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
     * Loads the user group.
     *
     * @param $iId
     *
     * @return bool
     */
    public function load($iId)
    {
        $sQuery = $this->_oDatabase->prepare(
            "SELECT *
            FROM {$this->_oDatabase->getUserGroupTable()}
            WHERE ID = %s
            LIMIT 1",
            $iId
        );

        $oDbUserGroup = $this->_oDatabase->getRow($sQuery);

        if ($oDbUserGroup !== null) {
            $this->_iId = $iId;
            $this->_sGroupName = $oDbUserGroup->groupname;
            $this->_sGroupDesc = $oDbUserGroup->groupdesc;
            $this->_sReadAccess = $oDbUserGroup->read_access;
            $this->_sWriteAccess = $oDbUserGroup->write_access;
            $this->_sIpRange = $oDbUserGroup->ip_range;

            return true;
        }

        return false;
    }

    /**
     * Saves the user group.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->_iId === null) {
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
        }

        return ($mReturn !== false);
    }

    /**
     * Removes all objects from the user group.
     *
     * @param string $sObjectType The object type.
     *
     * @return bool
     */
    protected function _deleteObjectsFromDb($sObjectType)
    {
        if ($this->_iId !== null) {
            $sQuery = $this->_oDatabase->prepare(
                "DELETE FROM {$this->_oDatabase->getUserGroupToObjectTable()}
                WHERE group_id = %d
                  AND (general_object_type = '%s' OR object_type = '%s')",
                array(
                    $this->_iId,
                    $sObjectType,
                    $sObjectType
                )
            );

            return ($this->_oDatabase->query($sQuery) !== false);
        }

        return false;
    }

    /**
     * Deletes the user group.
     *
     * @return boolean
     */
    public function delete()
    {
        if ($this->_iId === null) {
            return false;
        }

        $blSuccess = $this->_oDatabase->delete(
            $this->_oDatabase->getUserGroupTable(),
            array('ID' => $this->_iId)
        );

        if ($blSuccess !== false) {
            $aAllObjectTypes = $this->_oObjectHandler->getAllObjectTypes();

            foreach ($aAllObjectTypes as $sObjectType) {
                $this->_deleteObjectsFromDb($sObjectType);
            }
        }

        return true;
    }

    /**
     * Returns the assigned objects.
     *
     * @param string $sObjectType The object type.
     *
     * @return array
     */
    protected function _getAssignedObjects($sObjectType)
    {
        if (isset($this->_aAssignedObjects[$sObjectType]) === false) {
            $sQuery = $this->_oDatabase->prepare(
                "SELECT object_id as id
                FROM {$this->_oDatabase->getUserGroupToObjectTable()}
                WHERE group_id = %d
                  AND (general_object_type = '%s' OR object_type = '%s')",
                array(
                    $this->getId(),
                    $sObjectType,
                    $sObjectType
                )
            );

            $aDbObjects = $this->_oDatabase->getResults($sQuery);
            $this->_aAssignedObjects[$sObjectType] = array();

            foreach ($aDbObjects as $oDbObject) {
                $this->_aAssignedObjects[$sObjectType][$oDbObject->id] = $oDbObject->id;
            }
        }

        return $this->_aAssignedObjects[$sObjectType];
    }

    /**
     * Adds a object of the given type.
     *
     * @param string $sObjectType The object type.
     * @param string $iObjectId   The object id.
     *                            
     * @return bool
     */
    public function addObject($sObjectType, $iObjectId)
    {
        $sGeneralObjectType = $this->_oObjectHandler->getGeneralObjectType($sObjectType);

        if ($sGeneralObjectType === null
            || $this->_oObjectHandler->isValidObjectType($sObjectType) === false
        ) {
            return false;
        }



        $mReturn = $this->_oDatabase->insert(
            $this->_oDatabase->getUserGroupToObjectTable(),
            array(
                'group_id' => $this->_iId,
                'object_id' => $iObjectId,
                'general_object_type' => $sGeneralObjectType,
                'object_type' => $sObjectType
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%s'
            )
        );

        if ($mReturn !== false) {
            $this->_aAssignedObjects = array();
            $this->_aObjectMembership = array();
            return true;
        }
        
        return false;
    }

    /**
     * Removes a object of the given type.
     *
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object id.
     *
     * @return bool
     */
    public function removeObject($sObjectType, $iObjectId)
    {
        if ($this->_oObjectHandler->isValidObjectType($sObjectType) === false) {
            return false;
        }

        $sQuery = $this->_oDatabase->prepare(
            "DELETE FROM {$this->_oDatabase->getUserGroupToObjectTable()}
                WHERE group_id = %d
                  AND object_id = %d
                  AND (general_object_type = '%s' OR object_type = '%s')",
            array(
                $this->_iId,
                $iObjectId,
                $sObjectType,
                $sObjectType
            )
        );

        $blSuccess = ($this->_oDatabase->query($sQuery) !== false);

        if ($blSuccess === true) {
            $this->_aAssignedObjects = array();
            $this->_aObjectMembership = array();
        }

        return $blSuccess;
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
    public function getRecursiveMembershipForObject($sObjectType, $iObjectId, $sCurObjectType)
    {
        $aRecursiveMembership = array();

        if ($this->isObjectMember($sObjectType, $iObjectId, $aRecursiveMembership) === true
            && isset($aRecursiveMembership[$sCurObjectType])
        ) {
            return $aRecursiveMembership[$sCurObjectType];
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
        $aRecursiveMembership = array();

        if ($this->isObjectMember($sObjectType, $iObjectId, $aRecursiveMembership) === true) {
            return (count($aRecursiveMembership) > 0);
        }

        return false;
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

        unset($this->_aAssignedObjects[$sObjectType]);
        unset($this->_aObjectMembership);

        if ($blPlusRemove === true) {
            $this->_deleteObjectsFromDb($sObjectType);
        }
    }

    /**
     * Returns all objects of the given type.
     *
     * @param string $sObjectType The object type.
     *
     * @return array
     */
    public function getObjectsByType($sObjectType)
    {
        if ($sObjectType === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
            return $this->_getAssignedObjects($sObjectType);
        } elseif ($sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            return $this->getFullUsers();
        } elseif ($sObjectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
            || $this->_oObjectHandler->isTaxonomy($sObjectType)
        ) {
            return $this->getFullTerms($sObjectType);
        } elseif ($sObjectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
            || $this->_oObjectHandler->isPostableType($sObjectType) === true
        ) {
            return $this->getFullPosts($sObjectType);
        } elseif ($this->_oObjectHandler->isPluggableObject($sObjectType)) {
            $oPluggableObject = $this->_oObjectHandler->getPluggableObject($sObjectType);
            return $oPluggableObject->getFullObjects($this);
        }

        return array();
    }

    /**
     * Returns a single object.
     *
     * @param string  $sObjectType          The object type.
     * @param int     $iObjectId            The id of the object which should be checked.
     * @param array   $aRecursiveMembership The recursive membership.
     *
     * @return bool
     */
    public function isObjectMember($sObjectType, $iObjectId, array &$aRecursiveMembership = array())
    {
        if (isset($this->_aObjectMembership[$sObjectType]) === false) {
            $this->_aObjectMembership[$sObjectType] = array();
        }

        if (isset($this->_aObjectMembership[$sObjectType][$iObjectId]) === false) {
            $blIsMember = false;
            $aRecursiveMembership = array();

            if ($sObjectType === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
                $blIsMember = $this->_isObjectAssignedToGroup($sObjectType, $iObjectId);
            } elseif ($sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
                $blIsMember = $this->_isUserMember($iObjectId, $aRecursiveMembership);
            } elseif ($sObjectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
                || $this->_oObjectHandler->isTaxonomy($sObjectType) === true
            ) {
                $blIsMember = $this->_isTermMember($iObjectId, $aRecursiveMembership);
            } elseif ($sObjectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
                || $this->_oObjectHandler->isPostableType($sObjectType) === true
            ) {
                $blIsMember = $this->_isPostMember($iObjectId, $aRecursiveMembership);
            } elseif ($this->_oObjectHandler->isPluggableObject($sObjectType)) {
                $blIsMember = $this->_isPluggableObjectMember($sObjectType, $iObjectId, $aRecursiveMembership);
            }

            $this->_aObjectMembership[$sObjectType][$iObjectId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->_aObjectMembership[$sObjectType][$iObjectId] !== false) ?
            $this->_aObjectMembership[$sObjectType][$iObjectId] : array();

        return ($this->_aObjectMembership[$sObjectType][$iObjectId] !== false);
    }


    /*
     * Group users functions.
     */

    /**
     * Returns a single user.
     *
     * @param integer $iUserId              The user id.
     * @param array   $aRecursiveMembership The recursive membership array.
     *
     * @return bool
     */
    private function _isUserMember($iUserId, array &$aRecursiveMembership = array())
    {
        $aRecursiveMembership = array();
        $oUser = $this->_oObjectHandler->getUser($iUserId);
        $sCapabilitiesTable = $this->_oDatabase->getCapabilitiesTable();

        $aCapabilities = (isset($oUser->{$sCapabilitiesTable})) ? $oUser->{$sCapabilitiesTable}: array();
        $aRoles = (is_array($aCapabilities) && count($aCapabilities) > 0) ? array_keys($aCapabilities) : array('norole');
        $aRoleObjects = $this->getObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        foreach ($aRoles as $sRole) {
            if (isset($aRoleObjects[$sRole])) {
                $oRoleObject = new \stdClass();
                $oRoleObject->name = $sRole;

                $aRecursiveMembership = array(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => array());
                $aRecursiveMembership[ObjectHandler::GENERAL_ROLE_OBJECT_TYPE][] = $oRoleObject;
            }
        }

        return $this->_isObjectAssignedToGroup(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iUserId)
            || count($aRecursiveMembership) > 0;
    }

    /**
     * Returns the users assigned to the group.
     *
     * @return array
     */
    public function getFullUsers()
    {
        if (isset($this->_aFullObjects[ObjectHandler::GENERAL_USER_OBJECT_TYPE]) === false) {
            $this->_aFullObjects[ObjectHandler::GENERAL_USER_OBJECT_TYPE] = array();

            $aDatabaseUsers = (array)$this->_oDatabase->getResults(
                "SELECT ID, user_nicename
                FROM {$this->_oDatabase->getUsersTable()}"
            );

            foreach ($aDatabaseUsers as $oUser) {
                if ($this->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $oUser->ID) === true) {
                    $this->_aFullObjects[ObjectHandler::GENERAL_USER_OBJECT_TYPE][$oUser->ID] = $oUser->ID;
                }
            }
        }

        return $this->_aFullObjects[ObjectHandler::GENERAL_USER_OBJECT_TYPE];
    }


    /*
     * Group categories functions.
     */

    /**
     * Checks if the term is a group member.
     *
     * @param int   $iTermId
     * @param array $aRecursiveMembership
     *
     * @return bool
     */
    private function _isTermMember($iTermId, array &$aRecursiveMembership = array())
    {
        // Reset value to prevent errors
        $aRecursiveMembership = array();

        if ($this->_oConfig->lockRecursive() === true) {
            $aTermMap = $this->_oObjectHandler->getTermTreeMap();
            $aGeneralMap = isset($aTermMap[ObjectHandler::TREE_MAP_PARENTS][ObjectHandler::GENERAL_TERM_OBJECT_TYPE]) ?
                $aTermMap[ObjectHandler::TREE_MAP_PARENTS][ObjectHandler::GENERAL_TERM_OBJECT_TYPE] : array();

            if (isset($aGeneralMap[$iTermId])) {
                foreach ($aGeneralMap[$iTermId] as $iParentId) {
                    if ($this->_isObjectAssignedToGroup(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iParentId)) {
                        $aRecursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$iParentId] = $iParentId;
                        return true;
                    }
                }
            }
        }

        return $this->_isObjectAssignedToGroup(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iTermId)
            || count($aRecursiveMembership) > 0;
    }

    /**
     * Returns the terms assigned to the group.
     *
     * @param string $sTermType The term type.
     *
     * @return array
     */
    public function getFullTerms($sTermType = null)
    {
        if (isset($this->_aFullObjects[ObjectHandler::GENERAL_TERM_OBJECT_TYPE]) === false) {
            $this->_aFullObjects[ObjectHandler::GENERAL_TERM_OBJECT_TYPE] = array();
        }

        if (isset($this->_aFullObjects[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$sTermType]) === false) {
            $sTermType = ($sTermType === null) ? ObjectHandler::GENERAL_TERM_OBJECT_TYPE : $sTermType;
            $aTerms = $this->_getAssignedObjects($sTermType);

            if ($this->_oConfig->lockRecursive() === true) {
                $aTermTreeMap = $this->_oObjectHandler->getTermTreeMap();
                $aTermTreeMap = isset($aTermTreeMap[ObjectHandler::TREE_MAP_PARENTS][$sTermType]) ?
                    $aTermTreeMap[ObjectHandler::TREE_MAP_PARENTS][$sTermType] : array();
                $aTermTreeMap = array_intersect_key($aTermTreeMap, $aTerms);

                foreach ($aTermTreeMap as $aParentIds) {
                    foreach ($aParentIds as $iParentId) {
                        if ($this->isObjectMember($sTermType, $iParentId)) {
                            $aTerms[$iParentId] = $iParentId;
                        }
                    }
                }
            }

            $this->_aFullObjects[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$sTermType] = $aTerms;
        }

        return $this->_aFullObjects[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$sTermType];
    }


    /*
     * Group posts functions.
     */

    /**
     * Checks if the post is a group member
     *
     * @param int   $iPostId
     * @param array $aRecursiveMembership
     *
     * @return bool
     */
    protected function _isPostMember($iPostId, array &$aRecursiveMembership = array())
    {
        // Reset value to prevent errors
        $aRecursiveMembership = array();

        if ($this->_oConfig->lockRecursive() === true) {
            $aPostMap = $this->_oObjectHandler->getPostTreeMap();
            $aGeneralMap = isset($aPostMap[ObjectHandler::TREE_MAP_PARENTS][ObjectHandler::GENERAL_POST_OBJECT_TYPE]) ?
                $aPostMap[ObjectHandler::TREE_MAP_PARENTS][ObjectHandler::GENERAL_POST_OBJECT_TYPE] : array();

            if (isset($aGeneralMap[$iPostId])) {
                foreach ($aGeneralMap[$iPostId] as $iParentId) {
                    if ($this->_isObjectAssignedToGroup(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $iParentId)) {
                        $aRecursiveMembership[ObjectHandler::GENERAL_POST_OBJECT_TYPE][$iParentId] = $iParentId;
                        break;
                    }
                }
            }

            $aPostTermMap = $this->_oObjectHandler->getPostTermMap();

            if (isset($aPostTermMap[$iPostId])) {
                foreach ($aPostTermMap[$iPostId] as $iTermId) {
                    if ($this->_isTermMember($iTermId)) {
                        $aRecursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$iTermId] = $iTermId;
                    }
                }
            }
        }

        /** TODO
        $oPost->post_parent === 0
        && $oPost->post_type === ObjectHandler::POST_OBJECT_TYPE
        && $this->_oConfig->getWpOption('show_on_front') === ObjectHandler::PAGE_OBJECT_TYPE
        && $this->_oConfig->getWpOption('page_for_posts') != $iObjectId
         */

        return $this->_isObjectAssignedToGroup(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $iPostId)
            || count($aRecursiveMembership) > 0;
    }

    /**
     * Returns the posts assigned to the group.
     *
     * @param string $sPostType The post type.
     *
     * @return array
     */
    public function getFullPosts($sPostType = null)
    {
        if (isset($this->_aFullObjects[ObjectHandler::GENERAL_POST_OBJECT_TYPE]) === false) {
            $this->_aFullObjects[ObjectHandler::GENERAL_POST_OBJECT_TYPE] = array();
        }

        if (isset($this->_aFullObjects[ObjectHandler::GENERAL_POST_OBJECT_TYPE][$sPostType]) === false) {
            $sPostType = ($sPostType === null) ? ObjectHandler::GENERAL_POST_OBJECT_TYPE : $sPostType;
            $aPosts = $this->_getAssignedObjects($sPostType);

            if ($this->_oConfig->lockRecursive() === true) {
                $aPostTreeMap = $this->_oObjectHandler->getPostTreeMap();
                $aPostTreeMap = isset($aPostTreeMap[ObjectHandler::TREE_MAP_PARENTS][$sPostType]) ?
                    $aPostTreeMap[ObjectHandler::TREE_MAP_PARENTS][$sPostType] : array();
                $aPostTreeMap = array_intersect_key($aPostTreeMap, $aPosts);

                foreach ($aPostTreeMap as $aParentIds) {
                    foreach ($aParentIds as $iParentId) {
                        if ($this->isObjectMember($sPostType, $iParentId)) {
                            $aTerms[$iParentId] = $iParentId;
                        }
                    }
                }

                //TODO add terms
            }

            $this->_aFullObjects[ObjectHandler::GENERAL_POST_OBJECT_TYPE][$sPostType] = $aPosts;
        }

        return $this->_aFullObjects[ObjectHandler::GENERAL_POST_OBJECT_TYPE][$sPostType];
    }


    /*
     * Group pluggable objects functions.
     */

    /**
     * Returns a the recursive membership for a pluggable object.
     *
     * @param string $sObjectType           The pluggable object type.
     * @param int    $iObjectId             The object id.
     * @param array  $aRecursiveMembership  The object id.
     *
     * @return bool
     */
    protected function _isPluggableObjectMember($sObjectType, $iObjectId, array &$aRecursiveMembership = array())
    {
        $aRecursiveMembership = array();
        $oPluggableObject = $this->_oObjectHandler->getPluggableObject($sObjectType);
        $aRecursiveMembership = $oPluggableObject->getRecursiveMemberShip($this);

        return $this->_isObjectAssignedToGroup($sObjectType, $iObjectId)
            || count($aRecursiveMembership) > 0;
    }
}
