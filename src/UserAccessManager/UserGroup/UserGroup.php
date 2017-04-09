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
 * @version   SVN: $id$
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
    protected $wordpress;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var ObjectHandler
     */
    protected $objectHandler;

    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $name = null;

    /**
     * @var string
     */
    protected $description = null;

    /**
     * @var string
     */
    protected $readAccess = null;

    /**
     * @var string
     */
    protected $writeAccess = null;

    /**
     * @var string
     */
    protected $ipRange = null;

    /**
     * @var array
     */
    protected $assignedObjects = [];

    /**
     * @var array
     */
    protected $roleMembership = [];

    /**
     * @var array
     */
    protected $userMembership = [];

    /**
     * @var array
     */
    protected $termMembership = [];

    /**
     * @var array
     */
    protected $postMembership = [];

    /**
     * @var array
     */
    protected $pluggableObjectMembership = [];

    /**
     * @var array
     */
    protected $fullObjectMembership = [];

    /**
     * UserGroup constructor.
     *
     * @param Wordpress     $wordpress
     * @param Database      $database
     * @param Config        $config
     * @param Util          $util
     * @param ObjectHandler $objectHandler
     * @param null          $id
     */
    public function __construct(
        Wordpress $wordpress,
        Database $database,
        Config $config,
        Util $util,
        ObjectHandler $objectHandler,
        $id = null
    ) {
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->config = $config;
        $this->util = $util;
        $this->objectHandler = $objectHandler;

        if ($id !== null) {
            $this->load($id);
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
        return $this->id;
    }

    /**
     * Returns the group name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the group name.
     *
     * @param string $name The new group name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the group description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the group description.
     *
     * @param string $description The new group description.
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * @param string $writeAccess The write access.
     */
    public function setWriteAccess($writeAccess)
    {
        $this->writeAccess = $writeAccess;
    }

    /**
     * Returns the ip range.
     *
     * @param bool $string If true return it as string.
     *
     * @return array|string
     */
    public function getIpRange($string = false)
    {
        return ($string === true) ? $this->ipRange : explode(';', $this->ipRange);
    }

    /**
     * Sets the ip range.
     *
     * @param string|array $ipRange The new ip range.
     */
    public function setIpRange($ipRange)
    {
        $this->ipRange = (is_array($ipRange) === true) ? implode(';', $ipRange) : $ipRange;
    }

    /**
     * Loads the user group.
     *
     * @param $id
     *
     * @return bool
     */
    public function load($id)
    {
        $query = $this->database->prepare(
            "SELECT *
            FROM {$this->database->getUserGroupTable()}
            WHERE ID = %s
            LIMIT 1",
            $id
        );

        $dbUserGroup = $this->database->getRow($query);

        if ($dbUserGroup !== null) {
            $this->id = $id;
            $this->name = $dbUserGroup->groupname;
            $this->description = $dbUserGroup->groupdesc;
            $this->readAccess = $dbUserGroup->read_access;
            $this->writeAccess = $dbUserGroup->write_access;
            $this->ipRange = $dbUserGroup->ip_range;

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
        if ($this->id === null) {
            $return = $this->database->insert(
                $this->database->getUserGroupTable(),
                [
                    'groupname' => $this->name,
                    'groupdesc' => $this->description,
                    'read_access' => $this->readAccess,
                    'write_access' => $this->writeAccess,
                    'ip_range' => $this->ipRange
                ]
            );

            if ($return !== false) {
                $this->id = $this->database->getLastInsertId();
            }
        } else {
            $return = $this->database->update(
                $this->database->getUserGroupTable(),
                [
                    'groupname' => $this->name,
                    'groupdesc' => $this->description,
                    'read_access' => $this->readAccess,
                    'write_access' => $this->writeAccess,
                    'ip_range' => $this->ipRange
                ],
                ['ID' => $this->id]
            );
        }

        return ($return !== false);
    }

    /**
     * Deletes the user group.
     *
     * @return boolean
     */
    public function delete()
    {
        if ($this->id === null) {
            return false;
        }

        $success = $this->database->delete(
            $this->database->getUserGroupTable(),
            ['ID' => $this->id]
        );

        if ($success !== false) {
            $allObjectTypes = $this->objectHandler->getAllObjectTypes();

            foreach ($allObjectTypes as $objectType) {
                $this->removeObject($objectType);
            }
        }

        return $success;
    }

    /**
     * Adds a object of the given type.
     *
     * @param string $objectType The object type.
     * @param string $objectId   The object id.
     *
     * @return bool
     */
    public function addObject($objectType, $objectId)
    {
        $generalObjectType = $this->objectHandler->getGeneralObjectType($objectType);

        if ($generalObjectType === null
            || $this->objectHandler->isValidObjectType($objectType) === false
        ) {
            return false;
        }

        $return = $this->database->insert(
            $this->database->getUserGroupToObjectTable(),
            [
                'group_id' => $this->id,
                'object_id' => $objectId,
                'general_object_type' => $generalObjectType,
                'object_type' => $objectType
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($return !== false) {
            $this->assignedObjects = [];
            $this->roleMembership = [];
            $this->userMembership = [];
            $this->termMembership = [];
            $this->postMembership = [];
            $this->pluggableObjectMembership = [];
            $this->fullObjectMembership = [];

            return true;
        }

        return false;
    }

    /**
     * Removes a object of the given type.
     *
     * @param string $objectType The object type.
     * @param string $objectId   The object id.
     *
     * @return bool
     */
    public function removeObject($objectType, $objectId = null)
    {
        if ($this->objectHandler->isValidObjectType($objectType) === false) {
            return false;
        }

        $query = "DELETE FROM {$this->database->getUserGroupToObjectTable()}
            WHERE group_id = %d
              AND (general_object_type = '%s' OR object_type = '%s')";

        $values = [
            $this->id,
            $objectType,
            $objectType
        ];

        if ($objectId !== null) {
            $query .= ' AND object_id = %d';
            $values[] = $objectId;
        }

        $query = $this->database->prepare($query, $values);
        $success = ($this->database->query($query) !== false);

        if ($success === true) {
            $this->assignedObjects = [];
            $this->roleMembership = [];
            $this->userMembership = [];
            $this->termMembership = [];
            $this->postMembership = [];
            $this->pluggableObjectMembership = [];
            $this->fullObjectMembership = [];
        }

        return $success;
    }

    /**
     * Returns the assigned objects.
     *
     * @param string $objectType The object type.
     *
     * @return array
     */
    protected function getAssignedObjects($objectType)
    {
        if (isset($this->assignedObjects[$objectType]) === false) {
            $query = $this->database->prepare(
                "SELECT object_id AS id, object_type AS objectType
                FROM {$this->database->getUserGroupToObjectTable()}
                WHERE group_id = %d
                  AND (general_object_type = '%s' OR object_type = '%s')",
                [
                    $this->getId(),
                    $objectType,
                    $objectType
                ]
            );

            $results = (array)$this->database->getResults($query);
            $this->assignedObjects[$objectType] = [];

            foreach ($results as $result) {
                $this->assignedObjects[$objectType][$result->id] = $result->objectType;
            }
        }

        return $this->assignedObjects[$objectType];
    }

    /**
     * Checks if the object is assigned to the group.
     *
     * @param string $objectType The object type.
     * @param string $objectId   The object id.
     *
     * @return boolean
     */
    protected function isObjectAssignedToGroup($objectType, $objectId)
    {
        $assignedObjects = $this->getAssignedObjects($objectType);
        return isset($assignedObjects[$objectId]);
    }

    /**
     * Returns the recursive membership.
     *
     * @param \Closure $mapFunction
     * @param string   $objectType
     * @param string   $objectId
     * @param array    $recursiveMembership
     *
     * @return bool
     */
    protected function isObjectRecursiveMember(
        $mapFunction,
        $objectType,
        $objectId,
        array &$recursiveMembership = []
    ) {
        // Reset value to prevent errors
        $recursiveMembership = [];

        if ($this->config->lockRecursive() === true) {
            $map = $mapFunction();
            $generalMap = isset($map[ObjectHandler::TREE_MAP_PARENTS][$objectType]) ?
                $map[ObjectHandler::TREE_MAP_PARENTS][$objectType] : [];

            if (isset($generalMap[$objectId])) {
                foreach ($generalMap[$objectId] as $parentId => $type) {
                    if ($this->isObjectAssignedToGroup($objectType, $parentId)) {
                        $recursiveMembership[$objectType][$parentId] = $type;
                    }
                }
            }
        }

        return $this->isObjectAssignedToGroup($objectType, $objectId)
            || count($recursiveMembership) > 0;
    }

    /**
     * Checks if the role is a group member.
     *
     * @param string $roleId
     * @param array  $recursiveMembership
     *
     * @return bool
     */
    public function isRoleMember($roleId, array &$recursiveMembership = [])
    {
        if (isset($this->roleMembership[$roleId]) === false) {
            $recursiveMembership = [];
            $isMember = $this->isObjectAssignedToGroup(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, $roleId);
            $this->roleMembership[$roleId] = ($isMember === true) ? $recursiveMembership : false;
        }

        $recursiveMembership = ($this->roleMembership[$roleId] !== false) ? $this->roleMembership[$roleId] : [];

        return ($this->roleMembership[$roleId] !== false);
    }

    /**
     * Checks if the user is a group member.
     *
     * @param integer $userId              The user id.
     * @param array   $recursiveMembership The recursive membership array.
     *
     * @return bool
     */
    public function isUserMember($userId, array &$recursiveMembership = [])
    {
        if (isset($this->userMembership[$userId]) === false) {
            $recursiveMembership = [];
            $user = $this->objectHandler->getUser($userId);

            if ($user !== false) {
                $capabilitiesTable = $this->database->getCapabilitiesTable();

                $capabilities = (isset($user->{$capabilitiesTable}) === true) ? $user->{$capabilitiesTable} : [];

                if (is_array($capabilities) === true && count($capabilities) > 0) {
                    $assignedRoles = $this->getAssignedObjects(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);
                    $recursiveRoles = array_intersect(
                        array_keys($capabilities),
                        array_keys($assignedRoles)
                    );

                    if (count($recursiveRoles) > 0) {
                        $recursiveMembership[ObjectHandler::GENERAL_ROLE_OBJECT_TYPE] = array_combine(
                            $recursiveRoles,
                            array_fill(
                                0,
                                count($recursiveRoles),
                                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                            )
                        );
                    }
                }
            }

            $isMember = $this->isObjectAssignedToGroup(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId)
                || count($recursiveMembership) > 0;

            $this->userMembership[$userId] = ($isMember === true) ? $recursiveMembership : false;
        }

        $recursiveMembership = ($this->userMembership[$userId] !== false) ? $this->userMembership[$userId] : [];

        return ($this->userMembership[$userId] !== false);
    }

    /**
     * Checks if the term is a group member.
     *
     * @param int   $termId
     * @param array $recursiveMembership
     *
     * @return bool
     */
    public function isTermMember($termId, array &$recursiveMembership = [])
    {
        if (isset($this->termMembership[$termId]) === false) {
            $isMember = $this->isObjectRecursiveMember(
                function () {
                    return $this->objectHandler->getTermTreeMap();
                },
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                $termId,
                $recursiveMembership
            );

            $this->termMembership[$termId] = ($isMember === true) ? $recursiveMembership : false;
        }

        $recursiveMembership = ($this->termMembership[$termId] !== false) ? $this->termMembership[$termId] : [];

        return ($this->termMembership[$termId] !== false);
    }

    /**
     * Checks if the post is a group member
     *
     * @param int   $postId
     * @param array $recursiveMembership
     *
     * @return bool
     */
    public function isPostMember($postId, array &$recursiveMembership = [])
    {
        if (isset($this->postMembership[$postId]) === false) {
            $isMember = $this->isObjectRecursiveMember(
                function () {
                    return $this->objectHandler->getPostTreeMap();
                },
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                $postId,
                $recursiveMembership
            );

            if ($this->config->lockRecursive() === true) {
                $postTermMap = $this->objectHandler->getPostTermMap();

                if (isset($postTermMap[$postId])) {
                    foreach ($postTermMap[$postId] as $termId => $type) {
                        if ($this->isTermMember($termId) === true) {
                            $recursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$termId] = $type;
                        }
                    }
                }

                $isMember = $isMember || count($recursiveMembership) > 0;
            }

            $this->postMembership[$postId] = ($isMember === true) ? $recursiveMembership : false;
        }

        $recursiveMembership = ($this->postMembership[$postId] !== false) ? $this->postMembership[$postId] : [];

        return ($this->postMembership[$postId] !== false);
    }

    /**
     * Returns a the recursive membership for a pluggable object.
     *
     * @param string $objectType           The pluggable object type.
     * @param string $objectId             The object id.
     * @param array  $recursiveMembership  The object id.
     *
     * @return bool
     */
    public function isPluggableObjectMember($objectType, $objectId, array &$recursiveMembership = [])
    {
        if (isset($this->pluggableObjectMembership[$objectType]) === false) {
            $this->pluggableObjectMembership[$objectType] = [];
        }

        if (isset($this->pluggableObjectMembership[$objectType][$objectId]) === false) {
            $isMember = false;
            $pluggableObject = $this->objectHandler->getPluggableObject($objectType);

            if ($pluggableObject !== null) {
                $recursiveMembership = $pluggableObject->getRecursiveMembership($this, $objectId);
                $isMember = $this->isObjectAssignedToGroup($objectType, $objectId)
                    || count($recursiveMembership) > 0;
            }

            $this->pluggableObjectMembership[$objectType][$objectId] =
                ($isMember === true) ? $recursiveMembership : false;
        }

        $recursiveMembership = ($this->pluggableObjectMembership[$objectType][$objectId] !== false) ?
            $this->pluggableObjectMembership[$objectType][$objectId] : [];

        return ($this->pluggableObjectMembership[$objectType][$objectId] !== false);
    }

    /**
     * Returns a single object.
     *
     * @param string  $objectType          The object type.
     * @param string  $objectId            The id of the object which should be checked.
     * @param array   $recursiveMembership The recursive membership.
     *
     * @return bool
     */
    public function isObjectMember($objectType, $objectId, array &$recursiveMembership = [])
    {
        $isMember = false;
        $recursiveMembership = [];

        if ($objectType === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
            $isMember = $this->isRoleMember($objectId, $recursiveMembership);
        } elseif ($objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            $isMember = $this->isUserMember($objectId, $recursiveMembership);
        } elseif ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
            || $this->objectHandler->isTaxonomy($objectType) === true
        ) {
            $isMember = $this->isTermMember($objectId, $recursiveMembership);
        } elseif ($objectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
            || $this->objectHandler->isPostType($objectType) === true
        ) {
            $isMember = $this->isPostMember($objectId, $recursiveMembership);
        } elseif ($this->objectHandler->isPluggableObject($objectType) === true) {
            $isMember = $this->isPluggableObjectMember($objectType, $objectId, $recursiveMembership);
        }

        return $isMember;
    }

    /**
     * Returns the recursive membership.
     *
     * @param string $objectType    The object type.
     * @param string $objectId      The object id.
     *
     * @return array
     */
    public function getRecursiveMembershipForObject($objectType, $objectId)
    {
        $recursiveMembership = [];

        if ($this->isObjectMember($objectType, $objectId, $recursiveMembership) === true) {
            return $recursiveMembership;
        }

        return [];
    }

    /**
     * Returns true if the requested object is locked recursive.
     *
     * @param string $objectType The object type.
     * @param string $objectId   The object id.
     *
     * @return boolean
     */
    public function isLockedRecursive($objectType, $objectId)
    {
        $recursiveMembership = [];

        if ($this->isObjectMember($objectType, $objectId, $recursiveMembership) === true) {
            return (count($recursiveMembership) > 0);
        }

        return false;
    }

    /**
     * Returns the objects by the given type including the children.
     *
     * @param \Closure $mapFunction
     * @param string   $objectType
     *
     * @return array
     */
    protected function getFullObjects($mapFunction, $objectType)
    {
        $objects = $this->getAssignedObjects($objectType);

        if ($this->config->lockRecursive() === true) {
            $map = $mapFunction();
            $map = isset($map[ObjectHandler::TREE_MAP_CHILDREN][$objectType]) ?
                $map[ObjectHandler::TREE_MAP_CHILDREN][$objectType] : [];
            $map = array_intersect_key($map, $objects);

            foreach ($map as $childrenIds) {
                foreach ($childrenIds as $parentId => $type) {
                    if ($this->isObjectMember($objectType, $parentId)) {
                        $objects[$parentId] = $type;
                    }
                }
            }
        }

        return $objects;
    }

    /**
     * Returns the users assigned to the group.
     *
     * @return array
     */
    public function getFullUsers()
    {
        if (isset($this->fullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE]) === false) {
            $this->fullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE] = [];

            $databaseUsers = (array)$this->database->getResults(
                "SELECT ID, user_nicename
                FROM {$this->database->getUsersTable()}"
            );

            foreach ($databaseUsers as $user) {
                if ($this->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $user->ID) === true) {
                    $this->fullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE][$user->ID] =
                        ObjectHandler::GENERAL_USER_OBJECT_TYPE;
                }
            }
        }

        return $this->fullObjectMembership[ObjectHandler::GENERAL_USER_OBJECT_TYPE];
    }

    /**
     * Returns the terms assigned to the group.
     *
     * @param string $termType The term type.
     *
     * @return array
     */
    public function getFullTerms($termType = null)
    {
        if (isset($this->fullObjectMembership[$termType]) === false) {
            $termType = ($termType === null) ? ObjectHandler::GENERAL_TERM_OBJECT_TYPE : $termType;

            $this->fullObjectMembership[$termType] = $this->getFullObjects(
                function () {
                    return $this->objectHandler->getTermTreeMap();
                },
                $termType
            );
        }

        return $this->fullObjectMembership[$termType];
    }

    /**
     * Returns the posts assigned to the group.
     *
     * @param string $postType The post type.
     *
     * @return array
     */
    public function getFullPosts($postType = null)
    {
        if (isset($this->fullObjectMembership[$postType]) === false) {
            $postType = ($postType === null) ? ObjectHandler::GENERAL_POST_OBJECT_TYPE : $postType;
            $posts = $this->getFullObjects(
                function () {
                    return $this->objectHandler->getPostTreeMap();
                },
                $postType
            );

            if ($this->config->lockRecursive() === true) {
                $termsPostMap = $this->objectHandler->getTermPostMap();
                $terms = $this->getFullTerms();

                foreach ($terms as $termId => $term) {
                    if (isset($termsPostMap[$termId])) {
                        $posts += $termsPostMap[$termId];
                    }
                }
            }

            $this->fullObjectMembership[$postType] = $posts;
        }

        return $this->fullObjectMembership[$postType];
    }

    /**
     * Returns all objects of the given type.
     *
     * @param string $objectType The object type.
     *
     * @return array
     */
    public function getAssignedObjectsByType($objectType)
    {
        if ($objectType === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
            return $this->getAssignedObjects($objectType);
        } elseif ($objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            return $this->getFullUsers();
        } elseif ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
            || $this->objectHandler->isTaxonomy($objectType) === true
        ) {
            return $this->getFullTerms($objectType);
        } elseif ($objectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
            || $this->objectHandler->isPostType($objectType) === true
        ) {
            return $this->getFullPosts($objectType);
        } elseif ($this->objectHandler->isPluggableObject($objectType)) {
            $pluggableObject = $this->objectHandler->getPluggableObject($objectType);
            return ($pluggableObject !== null) ? $pluggableObject->getFullObjects($this) : [];
        }

        return [];
    }
}
