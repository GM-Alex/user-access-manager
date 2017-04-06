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
    const NONE_ROLE = '_none-role_';

    /**
     * @var Wordpress
     */
    protected $_oWordpress;

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
    protected $_sName = null;

    /**
     * @var string
     */
    protected $_sDescription = null;

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
    protected $_aAssignedObjects = [];

    /**
     * @var array
     */
    protected $_aRoleMembership = [];

    /**
     * @var array
     */
    protected $_aUserMembership = [];

    /**
     * @var array
     */
    protected $_aTermMembership = [];

    /**
     * @var array
     */
    protected $_aPostMembership = [];

    /**
     * @var array
     */
    protected $_aPluggableObjectMembership = [];

    /**
     * @var array
     */
    protected $_aFullObjectMembership = [];

    /**
     * UserGroup constructor.
     *
     * @param Wordpress     $oWordpress
     * @param Database      $oDatabase
     * @param Config        $oConfig
     * @param Util          $oUtil
     * @param ObjectHandler $oObjectHandler
     * @param null          $iId
     */
    public function __construct(
        Wordpress $oWordpress,
        Database $oDatabase,
        Config $oConfig,
        Util $oUtil,
        ObjectHandler $oObjectHandler,
        $iId = null
    )
    {
        $this->_oWordpress = $oWordpress;
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
    public function getName()
    {
        return $this->_sName;
    }

    /**
     * Sets the group name.
     *
     * @param string $sName The new group name.
     */
    public function setName($sName)
    {
        $this->_sName = $sName;
    }

    /**
     * Returns the group description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->_sDescription;
    }

    /**
     * Sets the group description.
     *
     * @param string $sDescription The new group description.
     */
    public function setDescription($sDescription)
    {
        $this->_sDescription = $sDescription;
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
     * @param bool $blString If true return it as string.
     *
     * @return array|string
     */
    public function getIpRange($blString = false)
    {
        return ($blString === true) ? $this->_sIpRange : explode(';', $this->_sIpRange);
    }

    /**
     * Sets the ip range.
     *
     * @param string|array $mIpRange The new ip range.
     */
    public function setIpRange($mIpRange)
    {
        $this->_sIpRange = (is_array($mIpRange) === true) ? implode(';', $mIpRange) : $mIpRange;
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
            $this->_sName = $oDbUserGroup->groupname;
            $this->_sDescription = $oDbUserGroup->groupdesc;
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
                [
                    'groupname' => $this->_sName,
                    'groupdesc' => $this->_sDescription,
                    'read_access' => $this->_sReadAccess,
                    'write_access' => $this->_sWriteAccess,
                    'ip_range' => $this->_sIpRange
                ]
            );

            if ($mReturn !== false) {
                $this->_iId = $this->_oDatabase->getLastInsertId();
            }
        } else {
            $mReturn = $this->_oDatabase->update(
                $this->_oDatabase->getUserGroupTable(),
                [
                    'groupname' => $this->_sName,
                    'groupdesc' => $this->_sDescription,
                    'read_access' => $this->_sReadAccess,
                    'write_access' => $this->_sWriteAccess,
                    'ip_range' => $this->_sIpRange
                ],
                ['ID' => $this->_iId]
            );
        }

        return ($mReturn !== false);
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
            ['ID' => $this->_iId]
        );

        if ($blSuccess !== false) {
            $aAllObjectTypes = $this->_oObjectHandler->getAllObjectTypes();

            foreach ($aAllObjectTypes as $sObjectType) {
                $this->removeObject($sObjectType);
            }
        }

        return $blSuccess;
    }

    /**
     * Adds a object of the given type.
     *
     * @param string $sObjectType The object type.
     * @param string $sObjectId   The object id.
     *
     * @return bool
     */
    public function addObject($sObjectType, $sObjectId)
    {
        $sGeneralObjectType = $this->_oObjectHandler->getGeneralObjectType($sObjectType);

        if ($sGeneralObjectType === null
            || $this->_oObjectHandler->isValidObjectType($sObjectType) === false
        ) {
            return false;
        }

        $mReturn = $this->_oDatabase->insert(
            $this->_oDatabase->getUserGroupToObjectTable(),
            [
                'group_id' => $this->_iId,
                'object_id' => $sObjectId,
                'general_object_type' => $sGeneralObjectType,
                'object_type' => $sObjectType
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($mReturn !== false) {
            $this->_aAssignedObjects = [];
            $this->_aRoleMembership = [];
            $this->_aUserMembership = [];
            $this->_aTermMembership = [];
            $this->_aPostMembership = [];
            $this->_aPluggableObjectMembership = [];
            $this->_aFullObjectMembership = [];

            return true;
        }

        return false;
    }

    /**
     * Removes a object of the given type.
     *
     * @param string $sObjectType The object type.
     * @param string $sObjectId   The object id.
     *
     * @return bool
     */
    public function removeObject($sObjectType, $sObjectId = null)
    {
        if ($this->_oObjectHandler->isValidObjectType($sObjectType) === false) {
            return false;
        }

        $sQuery = "DELETE FROM {$this->_oDatabase->getUserGroupToObjectTable()}
            WHERE group_id = %d
              AND (general_object_type = '%s' OR object_type = '%s')";

        $aValues = [
            $this->_iId,
            $sObjectType,
            $sObjectType
        ];

        if ($sObjectId !== null) {
            $sQuery .= ' AND object_id = %d';
            $aValues[] = $sObjectId;
        }

        $sQuery = $this->_oDatabase->prepare($sQuery, $aValues);
        $blSuccess = ($this->_oDatabase->query($sQuery) !== false);

        if ($blSuccess === true) {
            $this->_aAssignedObjects = [];
            $this->_aRoleMembership = [];
            $this->_aUserMembership = [];
            $this->_aTermMembership = [];
            $this->_aPostMembership = [];
            $this->_aPluggableObjectMembership = [];
            $this->_aFullObjectMembership = [];
        }

        return $blSuccess;
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
                "SELECT object_id AS id, object_type AS objectType
                FROM {$this->_oDatabase->getUserGroupToObjectTable()}
                WHERE group_id = %d
                  AND (general_object_type = '%s' OR object_type = '%s')",
                [
                    $this->getId(),
                    $sObjectType,
                    $sObjectType
                ]
            );

            $aResults = (array)$this->_oDatabase->getResults($sQuery);
            $this->_aAssignedObjects[$sObjectType] = [];

            foreach ($aResults as $oResult) {
                $this->_aAssignedObjects[$sObjectType][$oResult->id] = $oResult->objectType;
            }
        }

        return $this->_aAssignedObjects[$sObjectType];
    }

    /**
     * Checks if the object is assigned to the group.
     *
     * @param string $sObjectType The object type.
     * @param string $sObjectId   The object id.
     *
     * @return boolean
     */
    protected function _isObjectAssignedToGroup($sObjectType, $sObjectId)
    {
        $aAssignedObjects = $this->_getAssignedObjects($sObjectType);
        return isset($aAssignedObjects[$sObjectId]);
    }

    /**
     * Returns the recursive membership.
     *
     * @param \Closure $cMapFunction
     * @param string   $sObjectType
     * @param string   $sObjectId
     * @param array    $aRecursiveMembership
     *
     * @return bool
     */
    protected function _isObjectRecursiveMember(
        $cMapFunction,
        $sObjectType,
        $sObjectId,
        array &$aRecursiveMembership = []
    )
    {
        // Reset value to prevent errors
        $aRecursiveMembership = [];

        if ($this->_oConfig->lockRecursive() === true) {
            $aMap = $cMapFunction();
            $aGeneralMap = isset($aMap[ObjectHandler::TREE_MAP_PARENTS][$sObjectType]) ?
                $aMap[ObjectHandler::TREE_MAP_PARENTS][$sObjectType] : [];

            if (isset($aGeneralMap[$sObjectId])) {
                foreach ($aGeneralMap[$sObjectId] as $iParentId => $sType) {
                    if ($this->_isObjectAssignedToGroup($sObjectType, $iParentId)) {
                        $aRecursiveMembership[$sObjectType][$iParentId] = $sType;
                    }
                }
            }
        }

        return $this->_isObjectAssignedToGroup($sObjectType, $sObjectId)
            || count($aRecursiveMembership) > 0;
    }

    /**
     * Checks if the role is a group member.
     *
     * @param string $sRoleId
     * @param array  $aRecursiveMembership
     *
     * @return bool
     */
    public function isRoleMember($sRoleId, array &$aRecursiveMembership = [])
    {
        if (isset($this->_aRoleMembership[$sRoleId]) === false) {
            $aRecursiveMembership = [];
            $blIsMember = $this->_isObjectAssignedToGroup(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, $sRoleId);
            $this->_aRoleMembership[$sRoleId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->_aRoleMembership[$sRoleId] !== false) ? $this->_aRoleMembership[$sRoleId] : [];

        return ($this->_aRoleMembership[$sRoleId] !== false);
    }

    /**
     * Checks if the user is a group member.
     *
     * @param integer $iUserId              The user id.
     * @param array   $aRecursiveMembership The recursive membership array.
     *
     * @return bool
     */
    public function isUserMember($iUserId, array &$aRecursiveMembership = [])
    {
        if (isset($this->_aUserMembership[$iUserId]) === false) {
            $aRecursiveMembership = [];
            $oUser = $this->_oObjectHandler->getUser($iUserId);

            if ($oUser !== false) {
                $sCapabilitiesTable = $this->_oDatabase->getCapabilitiesTable();

                $aCapabilities = (isset($oUser->{$sCapabilitiesTable})) ? $oUser->{$sCapabilitiesTable} : [];

                if (is_array($aCapabilities) && count($aCapabilities) > 0) {
                    $aAssignedRoles = $this->_getAssignedObjects(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);
                    $aRecursiveRoles = array_intersect(
                        array_keys($aCapabilities),
                        array_keys($aAssignedRoles)
                    );

                    if (count($aRecursiveRoles) > 0) {
                        $aRecursiveMembership[ObjectHandler::GENERAL_ROLE_OBJECT_TYPE] = array_combine(
                            $aRecursiveRoles,
                            array_fill(
                                0,
                                count($aRecursiveRoles),
                                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                            )
                        );
                    }
                }
            }

            $blIsMember = $this->_isObjectAssignedToGroup(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iUserId)
                || count($aRecursiveMembership) > 0;

            $this->_aUserMembership[$iUserId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->_aUserMembership[$iUserId] !== false) ? $this->_aUserMembership[$iUserId] : [];

        return ($this->_aUserMembership[$iUserId] !== false);
    }

    /**
     * Checks if the term is a group member.
     *
     * @param int   $iTermId
     * @param array $aRecursiveMembership
     *
     * @return bool
     */
    public function isTermMember($iTermId, array &$aRecursiveMembership = [])
    {
        if (isset($this->_aTermMembership[$iTermId]) === false) {
            $blIsMember = $this->_isObjectRecursiveMember(
                function () {
                    return $this->_oObjectHandler->getTermTreeMap();
                },
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                $iTermId,
                $aRecursiveMembership
            );

            $this->_aTermMembership[$iTermId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->_aTermMembership[$iTermId] !== false) ? $this->_aTermMembership[$iTermId] : [];

        return ($this->_aTermMembership[$iTermId] !== false);
    }

    /**
     * Checks if the post is a group member
     *
     * @param int   $iPostId
     * @param array $aRecursiveMembership
     *
     * @return bool
     */
    public function isPostMember($iPostId, array &$aRecursiveMembership = [])
    {
        if (isset($this->_aPostMembership[$iPostId]) === false) {
            $blIsMember = $this->_isObjectRecursiveMember(
                function () {
                    return $this->_oObjectHandler->getPostTreeMap();
                },
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                $iPostId,
                $aRecursiveMembership
            );

            if ($this->_oConfig->lockRecursive() === true) {
                $aPostTermMap = $this->_oObjectHandler->getPostTermMap();

                if (isset($aPostTermMap[$iPostId])) {
                    foreach ($aPostTermMap[$iPostId] as $iTermId => $sType) {
                        if ($this->isTermMember($iTermId) === true) {
                            $aRecursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$iTermId] = $sType;
                        }
                    }
                }

                $blIsMember = $blIsMember || count($aRecursiveMembership) > 0;
            }

            $this->_aPostMembership[$iPostId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->_aPostMembership[$iPostId] !== false) ? $this->_aPostMembership[$iPostId] : [];

        return ($this->_aPostMembership[$iPostId] !== false);
    }

    /**
     * Returns a the recursive membership for a pluggable object.
     *
     * @param string $sObjectType           The pluggable object type.
     * @param string $sObjectId             The object id.
     * @param array  $aRecursiveMembership  The object id.
     *
     * @return bool
     */
    public function isPluggableObjectMember($sObjectType, $sObjectId, array &$aRecursiveMembership = [])
    {
        if (isset($this->_aPluggableObjectMembership[$sObjectType]) === false) {
            $this->_aPluggableObjectMembership[$sObjectType] = [];
        }

        if (isset($this->_aPluggableObjectMembership[$sObjectType][$sObjectId]) === false) {
            $blIsMember = false;
            $oPluggableObject = $this->_oObjectHandler->getPluggableObject($sObjectType);

            if ($oPluggableObject !== null) {
                $aRecursiveMembership = $oPluggableObject->getRecursiveMembership($this, $sObjectId);
                $blIsMember = $this->_isObjectAssignedToGroup($sObjectType, $sObjectId)
                    || count($aRecursiveMembership) > 0;
            }

            $this->_aPluggableObjectMembership[$sObjectType][$sObjectId] =
                ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->_aPluggableObjectMembership[$sObjectType][$sObjectId] !== false) ?
            $this->_aPluggableObjectMembership[$sObjectType][$sObjectId] : [];

        return ($this->_aPluggableObjectMembership[$sObjectType][$sObjectId] !== false);
    }

    /**
     * Returns a single object.
     *
     * @param string  $sObjectType          The object type.
     * @param string  $sObjectId            The id of the object which should be checked.
     * @param array   $aRecursiveMembership The recursive membership.
     *
     * @return bool
     */
    public function isObjectMember($sObjectType, $sObjectId, array &$aRecursiveMembership = [])
    {
        $blIsMember = false;
        $aRecursiveMembership = [];

        if ($sObjectType === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
            $blIsMember = $this->isRoleMember($sObjectId, $aRecursiveMembership);
        } elseif ($sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            $blIsMember = $this->isUserMember($sObjectId, $aRecursiveMembership);
        } elseif ($sObjectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
            || $this->_oObjectHandler->isTaxonomy($sObjectType) === true
        ) {
            $blIsMember = $this->isTermMember($sObjectId, $aRecursiveMembership);
        } elseif ($sObjectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
            || $this->_oObjectHandler->isPostType($sObjectType) === true
        ) {
            $blIsMember = $this->isPostMember($sObjectId, $aRecursiveMembership);
        } elseif ($this->_oObjectHandler->isPluggableObject($sObjectType) === true) {
            $blIsMember = $this->isPluggableObjectMember($sObjectType, $sObjectId, $aRecursiveMembership);
        }

        return $blIsMember;
    }

    /**
     * Returns the recursive membership.
     *
     * @param string $sObjectType    The object type.
     * @param string $sObjectId      The object id.
     *
     * @return array
     */
    public function getRecursiveMembershipForObject($sObjectType, $sObjectId)
    {
        $aRecursiveMembership = [];

        if ($this->isObjectMember($sObjectType, $sObjectId, $aRecursiveMembership) === true) {
            return $aRecursiveMembership;
        }

        return [];
    }

    /**
     * Returns true if the requested object is locked recursive.
     *
     * @param string $sObjectType The object type.
     * @param string $sObjectId   The object id.
     *
     * @return boolean
     */
    public function isLockedRecursive($sObjectType, $sObjectId)
    {
        $aRecursiveMembership = [];

        if ($this->isObjectMember($sObjectType, $sObjectId, $aRecursiveMembership) === true) {
            return (count($aRecursiveMembership) > 0);
        }

        return false;
    }

    /**
     * Returns the objects by the given type including the children.
     *
     * @param \Closure $cMapFunction
     * @param string   $sObjectType
     *
     * @return array
     */
    protected function _getFullObjects($cMapFunction, $sObjectType)
    {
        $aObjects = $this->_getAssignedObjects($sObjectType);

        if ($this->_oConfig->lockRecursive() === true) {
            $aMap = $cMapFunction();
            $aMap = isset($aMap[ObjectHandler::TREE_MAP_CHILDREN][$sObjectType]) ?
                $aMap[ObjectHandler::TREE_MAP_CHILDREN][$sObjectType] : [];
            $aMap = array_intersect_key($aMap, $aObjects);

            foreach ($aMap as $aChildrenIds) {
                foreach ($aChildrenIds as $iParentId => $sType) {
                    if ($this->isObjectMember($sObjectType, $iParentId)) {
                        $aObjects[$iParentId] = $sType;
                    }
                }
            }
        }

        return $aObjects;
    }

    /**
     * Returns the users assigned to the group.
     *
     * @return array
     */
    public function getFullUsers()
    {
        if (isset($this->_aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE]) === false) {
            $this->_aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE] = [];

            $aDatabaseUsers = (array)$this->_oDatabase->getResults(
                "SELECT ID, user_nicename
                FROM {$this->_oDatabase->getUsersTable()}"
            );

            foreach ($aDatabaseUsers as $oUser) {
                if ($this->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $oUser->ID) === true) {
                    $this->_aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE][$oUser->ID] =
                        ObjectHandler::GENERAL_USER_OBJECT_TYPE;
                }
            }
        }

        return $this->_aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE];
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
        if (isset($this->_aFullObjectMembership[$sTermType]) === false) {
            $sTermType = ($sTermType === null) ? ObjectHandler::GENERAL_TERM_OBJECT_TYPE : $sTermType;

            $this->_aFullObjectMembership[$sTermType] = $this->_getFullObjects(
                function () {
                    return $this->_oObjectHandler->getTermTreeMap();
                },
                $sTermType
            );
        }

        return $this->_aFullObjectMembership[$sTermType];
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
        if (isset($this->_aFullObjectMembership[$sPostType]) === false) {
            $sPostType = ($sPostType === null) ? ObjectHandler::GENERAL_POST_OBJECT_TYPE : $sPostType;
            $aPosts = $this->_getFullObjects(
                function () {
                    return $this->_oObjectHandler->getPostTreeMap();
                },
                $sPostType
            );

            if ($this->_oConfig->lockRecursive() === true) {
                $aTermsPostMap = $this->_oObjectHandler->getTermPostMap();
                $aTerms = $this->getFullTerms();

                foreach ($aTerms as $iTermId => $sTerm) {
                    if (isset($aTermsPostMap[$iTermId])) {
                        $aPosts += $aTermsPostMap[$iTermId];
                    }
                }
            }

            $this->_aFullObjectMembership[$sPostType] = $aPosts;
        }

        return $this->_aFullObjectMembership[$sPostType];
    }

    /**
     * Returns all objects of the given type.
     *
     * @param string $sObjectType The object type.
     *
     * @return array
     */
    public function getAssignedObjectsByType($sObjectType)
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
            || $this->_oObjectHandler->isPostType($sObjectType) === true
        ) {
            return $this->getFullPosts($sObjectType);
        } elseif ($this->_oObjectHandler->isPluggableObject($sObjectType)) {
            $oPluggableObject = $this->_oObjectHandler->getPluggableObject($sObjectType);
            return ($oPluggableObject !== null) ? $oPluggableObject->getFullObjects($this) : [];
        }

        return [];
    }
}
