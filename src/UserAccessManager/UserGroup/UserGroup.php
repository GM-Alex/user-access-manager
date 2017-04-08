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
    protected $oWordpress;

    /**
     * @var Database
     */
    protected $oDatabase;

    /**
     * @var Config
     */
    protected $oConfig;

    /**
     * @var Util
     */
    protected $oUtil;

    /**
     * @var ObjectHandler
     */
    protected $oObjectHandler;

    /**
     * @var int
     */
    protected $iId = null;

    /**
     * @var string
     */
    protected $sName = null;

    /**
     * @var string
     */
    protected $sDescription = null;

    /**
     * @var string
     */
    protected $sReadAccess = null;

    /**
     * @var string
     */
    protected $sWriteAccess = null;

    /**
     * @var string
     */
    protected $sIpRange = null;

    /**
     * @var array
     */
    protected $aAssignedObjects = [];

    /**
     * @var array
     */
    protected $aRoleMembership = [];

    /**
     * @var array
     */
    protected $aUserMembership = [];

    /**
     * @var array
     */
    protected $aTermMembership = [];

    /**
     * @var array
     */
    protected $aPostMembership = [];

    /**
     * @var array
     */
    protected $aPluggableObjectMembership = [];

    /**
     * @var array
     */
    protected $aFullObjectMembership = [];

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
    ) {
        $this->oWordpress = $oWordpress;
        $this->oDatabase = $oDatabase;
        $this->oConfig = $oConfig;
        $this->oUtil = $oUtil;
        $this->oObjectHandler = $oObjectHandler;

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
        return $this->iId;
    }

    /**
     * Returns the group name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->sName;
    }

    /**
     * Sets the group name.
     *
     * @param string $sName The new group name.
     */
    public function setName($sName)
    {
        $this->sName = $sName;
    }

    /**
     * Returns the group description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->sDescription;
    }

    /**
     * Sets the group description.
     *
     * @param string $sDescription The new group description.
     */
    public function setDescription($sDescription)
    {
        $this->sDescription = $sDescription;
    }

    /**
     * Returns the read access.
     *
     * @return string
     */
    public function getReadAccess()
    {
        return $this->sReadAccess;
    }

    /**
     * Sets the read access.
     *
     * @param string $sReadAccess The read access.
     */
    public function setReadAccess($sReadAccess)
    {
        $this->sReadAccess = $sReadAccess;
    }

    /**
     * Returns the write access.
     *
     * @return string
     */
    public function getWriteAccess()
    {
        return $this->sWriteAccess;
    }

    /**
     * Sets the write access.
     *
     * @param string $sWriteAccess The write access.
     */
    public function setWriteAccess($sWriteAccess)
    {
        $this->sWriteAccess = $sWriteAccess;
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
        return ($blString === true) ? $this->sIpRange : explode(';', $this->sIpRange);
    }

    /**
     * Sets the ip range.
     *
     * @param string|array $mIpRange The new ip range.
     */
    public function setIpRange($mIpRange)
    {
        $this->sIpRange = (is_array($mIpRange) === true) ? implode(';', $mIpRange) : $mIpRange;
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
        $sQuery = $this->oDatabase->prepare(
            "SELECT *
            FROM {$this->oDatabase->getUserGroupTable()}
            WHERE ID = %s
            LIMIT 1",
            $iId
        );

        $oDbUserGroup = $this->oDatabase->getRow($sQuery);

        if ($oDbUserGroup !== null) {
            $this->iId = $iId;
            $this->sName = $oDbUserGroup->groupname;
            $this->sDescription = $oDbUserGroup->groupdesc;
            $this->sReadAccess = $oDbUserGroup->read_access;
            $this->sWriteAccess = $oDbUserGroup->write_access;
            $this->sIpRange = $oDbUserGroup->ip_range;

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
        if ($this->iId === null) {
            $mReturn = $this->oDatabase->insert(
                $this->oDatabase->getUserGroupTable(),
                [
                    'groupname' => $this->sName,
                    'groupdesc' => $this->sDescription,
                    'read_access' => $this->sReadAccess,
                    'write_access' => $this->sWriteAccess,
                    'ip_range' => $this->sIpRange
                ]
            );

            if ($mReturn !== false) {
                $this->iId = $this->oDatabase->getLastInsertId();
            }
        } else {
            $mReturn = $this->oDatabase->update(
                $this->oDatabase->getUserGroupTable(),
                [
                    'groupname' => $this->sName,
                    'groupdesc' => $this->sDescription,
                    'read_access' => $this->sReadAccess,
                    'write_access' => $this->sWriteAccess,
                    'ip_range' => $this->sIpRange
                ],
                ['ID' => $this->iId]
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
        if ($this->iId === null) {
            return false;
        }

        $blSuccess = $this->oDatabase->delete(
            $this->oDatabase->getUserGroupTable(),
            ['ID' => $this->iId]
        );

        if ($blSuccess !== false) {
            $aAllObjectTypes = $this->oObjectHandler->getAllObjectTypes();

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
        $sGeneralObjectType = $this->oObjectHandler->getGeneralObjectType($sObjectType);

        if ($sGeneralObjectType === null
            || $this->oObjectHandler->isValidObjectType($sObjectType) === false
        ) {
            return false;
        }

        $mReturn = $this->oDatabase->insert(
            $this->oDatabase->getUserGroupToObjectTable(),
            [
                'group_id' => $this->iId,
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
            $this->aAssignedObjects = [];
            $this->aRoleMembership = [];
            $this->aUserMembership = [];
            $this->aTermMembership = [];
            $this->aPostMembership = [];
            $this->aPluggableObjectMembership = [];
            $this->aFullObjectMembership = [];

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
        if ($this->oObjectHandler->isValidObjectType($sObjectType) === false) {
            return false;
        }

        $sQuery = "DELETE FROM {$this->oDatabase->getUserGroupToObjectTable()}
            WHERE group_id = %d
              AND (general_object_type = '%s' OR object_type = '%s')";

        $aValues = [
            $this->iId,
            $sObjectType,
            $sObjectType
        ];

        if ($sObjectId !== null) {
            $sQuery .= ' AND object_id = %d';
            $aValues[] = $sObjectId;
        }

        $sQuery = $this->oDatabase->prepare($sQuery, $aValues);
        $blSuccess = ($this->oDatabase->query($sQuery) !== false);

        if ($blSuccess === true) {
            $this->aAssignedObjects = [];
            $this->aRoleMembership = [];
            $this->aUserMembership = [];
            $this->aTermMembership = [];
            $this->aPostMembership = [];
            $this->aPluggableObjectMembership = [];
            $this->aFullObjectMembership = [];
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
    protected function getAssignedObjects($sObjectType)
    {
        if (isset($this->aAssignedObjects[$sObjectType]) === false) {
            $sQuery = $this->oDatabase->prepare(
                "SELECT object_id AS id, object_type AS objectType
                FROM {$this->oDatabase->getUserGroupToObjectTable()}
                WHERE group_id = %d
                  AND (general_object_type = '%s' OR object_type = '%s')",
                [
                    $this->getId(),
                    $sObjectType,
                    $sObjectType
                ]
            );

            $aResults = (array)$this->oDatabase->getResults($sQuery);
            $this->aAssignedObjects[$sObjectType] = [];

            foreach ($aResults as $oResult) {
                $this->aAssignedObjects[$sObjectType][$oResult->id] = $oResult->objectType;
            }
        }

        return $this->aAssignedObjects[$sObjectType];
    }

    /**
     * Checks if the object is assigned to the group.
     *
     * @param string $sObjectType The object type.
     * @param string $sObjectId   The object id.
     *
     * @return boolean
     */
    protected function isObjectAssignedToGroup($sObjectType, $sObjectId)
    {
        $aAssignedObjects = $this->getAssignedObjects($sObjectType);
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
    protected function isObjectRecursiveMember(
        $cMapFunction,
        $sObjectType,
        $sObjectId,
        array &$aRecursiveMembership = []
    ) {
        // Reset value to prevent errors
        $aRecursiveMembership = [];

        if ($this->oConfig->lockRecursive() === true) {
            $aMap = $cMapFunction();
            $aGeneralMap = isset($aMap[ObjectHandler::TREE_MAP_PARENTS][$sObjectType]) ?
                $aMap[ObjectHandler::TREE_MAP_PARENTS][$sObjectType] : [];

            if (isset($aGeneralMap[$sObjectId])) {
                foreach ($aGeneralMap[$sObjectId] as $iParentId => $sType) {
                    if ($this->isObjectAssignedToGroup($sObjectType, $iParentId)) {
                        $aRecursiveMembership[$sObjectType][$iParentId] = $sType;
                    }
                }
            }
        }

        return $this->isObjectAssignedToGroup($sObjectType, $sObjectId)
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
        if (isset($this->aRoleMembership[$sRoleId]) === false) {
            $aRecursiveMembership = [];
            $blIsMember = $this->isObjectAssignedToGroup(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, $sRoleId);
            $this->aRoleMembership[$sRoleId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->aRoleMembership[$sRoleId] !== false) ? $this->aRoleMembership[$sRoleId] : [];

        return ($this->aRoleMembership[$sRoleId] !== false);
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
        if (isset($this->aUserMembership[$iUserId]) === false) {
            $aRecursiveMembership = [];
            $oUser = $this->oObjectHandler->getUser($iUserId);

            if ($oUser !== false) {
                $sCapabilitiesTable = $this->oDatabase->getCapabilitiesTable();

                $aCapabilities = (isset($oUser->{$sCapabilitiesTable}) === true) ? $oUser->{$sCapabilitiesTable} : [];

                if (is_array($aCapabilities) === true && count($aCapabilities) > 0) {
                    $aAssignedRoles = $this->getAssignedObjects(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);
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

            $blIsMember = $this->isObjectAssignedToGroup(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iUserId)
                || count($aRecursiveMembership) > 0;

            $this->aUserMembership[$iUserId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->aUserMembership[$iUserId] !== false) ? $this->aUserMembership[$iUserId] : [];

        return ($this->aUserMembership[$iUserId] !== false);
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
        if (isset($this->aTermMembership[$iTermId]) === false) {
            $blIsMember = $this->isObjectRecursiveMember(
                function () {
                    return $this->oObjectHandler->getTermTreeMap();
                },
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                $iTermId,
                $aRecursiveMembership
            );

            $this->aTermMembership[$iTermId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->aTermMembership[$iTermId] !== false) ? $this->aTermMembership[$iTermId] : [];

        return ($this->aTermMembership[$iTermId] !== false);
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
        if (isset($this->aPostMembership[$iPostId]) === false) {
            $blIsMember = $this->isObjectRecursiveMember(
                function () {
                    return $this->oObjectHandler->getPostTreeMap();
                },
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                $iPostId,
                $aRecursiveMembership
            );

            if ($this->oConfig->lockRecursive() === true) {
                $aPostTermMap = $this->oObjectHandler->getPostTermMap();

                if (isset($aPostTermMap[$iPostId])) {
                    foreach ($aPostTermMap[$iPostId] as $iTermId => $sType) {
                        if ($this->isTermMember($iTermId) === true) {
                            $aRecursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$iTermId] = $sType;
                        }
                    }
                }

                $blIsMember = $blIsMember || count($aRecursiveMembership) > 0;
            }

            $this->aPostMembership[$iPostId] = ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->aPostMembership[$iPostId] !== false) ? $this->aPostMembership[$iPostId] : [];

        return ($this->aPostMembership[$iPostId] !== false);
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
        if (isset($this->aPluggableObjectMembership[$sObjectType]) === false) {
            $this->aPluggableObjectMembership[$sObjectType] = [];
        }

        if (isset($this->aPluggableObjectMembership[$sObjectType][$sObjectId]) === false) {
            $blIsMember = false;
            $oPluggableObject = $this->oObjectHandler->getPluggableObject($sObjectType);

            if ($oPluggableObject !== null) {
                $aRecursiveMembership = $oPluggableObject->getRecursiveMembership($this, $sObjectId);
                $blIsMember = $this->isObjectAssignedToGroup($sObjectType, $sObjectId)
                    || count($aRecursiveMembership) > 0;
            }

            $this->aPluggableObjectMembership[$sObjectType][$sObjectId] =
                ($blIsMember === true) ? $aRecursiveMembership : false;
        }

        $aRecursiveMembership = ($this->aPluggableObjectMembership[$sObjectType][$sObjectId] !== false) ?
            $this->aPluggableObjectMembership[$sObjectType][$sObjectId] : [];

        return ($this->aPluggableObjectMembership[$sObjectType][$sObjectId] !== false);
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
            || $this->oObjectHandler->isTaxonomy($sObjectType) === true
        ) {
            $blIsMember = $this->isTermMember($sObjectId, $aRecursiveMembership);
        } elseif ($sObjectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
            || $this->oObjectHandler->isPostType($sObjectType) === true
        ) {
            $blIsMember = $this->isPostMember($sObjectId, $aRecursiveMembership);
        } elseif ($this->oObjectHandler->isPluggableObject($sObjectType) === true) {
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
    protected function getFullObjects($cMapFunction, $sObjectType)
    {
        $aObjects = $this->getAssignedObjects($sObjectType);

        if ($this->oConfig->lockRecursive() === true) {
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
        if (isset($this->aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE]) === false) {
            $this->aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE] = [];

            $aDatabaseUsers = (array)$this->oDatabase->getResults(
                "SELECT ID, user_nicename
                FROM {$this->oDatabase->getUsersTable()}"
            );

            foreach ($aDatabaseUsers as $oUser) {
                if ($this->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $oUser->ID) === true) {
                    $this->aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE][$oUser->ID] =
                        ObjectHandler::GENERAL_USER_OBJECT_TYPE;
                }
            }
        }

        return $this->aFullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE];
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
        if (isset($this->aFullObjectMembership[$sTermType]) === false) {
            $sTermType = ($sTermType === null) ? ObjectHandler::GENERAL_TERM_OBJECT_TYPE : $sTermType;

            $this->aFullObjectMembership[$sTermType] = $this->getFullObjects(
                function () {
                    return $this->oObjectHandler->getTermTreeMap();
                },
                $sTermType
            );
        }

        return $this->aFullObjectMembership[$sTermType];
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
        if (isset($this->aFullObjectMembership[$sPostType]) === false) {
            $sPostType = ($sPostType === null) ? ObjectHandler::GENERAL_POST_OBJECT_TYPE : $sPostType;
            $aPosts = $this->getFullObjects(
                function () {
                    return $this->oObjectHandler->getPostTreeMap();
                },
                $sPostType
            );

            if ($this->oConfig->lockRecursive() === true) {
                $aTermsPostMap = $this->oObjectHandler->getTermPostMap();
                $aTerms = $this->getFullTerms();

                foreach ($aTerms as $iTermId => $sTerm) {
                    if (isset($aTermsPostMap[$iTermId])) {
                        $aPosts += $aTermsPostMap[$iTermId];
                    }
                }
            }

            $this->aFullObjectMembership[$sPostType] = $aPosts;
        }

        return $this->aFullObjectMembership[$sPostType];
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
            return $this->getAssignedObjects($sObjectType);
        } elseif ($sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            return $this->getFullUsers();
        } elseif ($sObjectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
            || $this->oObjectHandler->isTaxonomy($sObjectType) === true
        ) {
            return $this->getFullTerms($sObjectType);
        } elseif ($sObjectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
            || $this->oObjectHandler->isPostType($sObjectType) === true
        ) {
            return $this->getFullPosts($sObjectType);
        } elseif ($this->oObjectHandler->isPluggableObject($sObjectType)) {
            $oPluggableObject = $this->oObjectHandler->getPluggableObject($sObjectType);
            return ($oPluggableObject !== null) ? $oPluggableObject->getFullObjects($this) : [];
        }

        return [];
    }
}
