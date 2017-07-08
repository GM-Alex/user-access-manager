<?php
/**
 * AbstractUserGroup.php
 *
 * The AbstractUserGroup class file.
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

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AbstractUserGroup
 *
 * @package UserAccessManager\UserGroup
 */
abstract class AbstractUserGroup
{
    const NONE_ROLE = '_none-role_';

    /**
     * @var Php
     */
    protected $php;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var MainConfig
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
     * @var AssignmentInformationFactory
     */
    protected $assignmentInformationFactory;

    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var string
     */
    protected $type = null;

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
    protected $readAccess = 'group';

    /**
     * @var string
     */
    protected $writeAccess = 'group';

    /**
     * @var string
     */
    protected $ipRange = null;

    /**
     * @var bool
     */
    protected $ignoreDates = false;

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
     * AbstractUserGroup constructor.
     *
     * @param Php                          $php
     * @param Wordpress                    $wordpress
     * @param Database                     $database
     * @param MainConfig                   $config
     * @param Util                         $util
     * @param ObjectHandler                $objectHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param string                       $id
     *
     * @throws UserGroupTypeException
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory,
        $id = null
    ) {
        if ($this->type === null) {
            throw new UserGroupTypeException('User group type must not null.');
        }

        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->config = $config;
        $this->util = $util;
        $this->objectHandler = $objectHandler;
        $this->assignmentInformationFactory = $assignmentInformationFactory;
        $this->id = $id;
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
     * Returns the user group type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
     * @return array|string
     */
    public function getIpRange()
    {
        return $this->ipRange;
    }

    /**
     * Returns the ip range as array
     *
     * @return array
     */
    public function getIpRangeArray()
    {
        return explode(';', $this->ipRange);
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
     * Sets the ignore dates flag.
     *
     * @param bool $ignoreDates
     */
    public function setIgnoreDates($ignoreDates)
    {
        if ($this->ignoreDates !== $ignoreDates) {
            $this->resetObjects();
        }

        $this->ignoreDates = $ignoreDates;
    }

    /**
     * Resets the objects
     */
    protected function resetObjects()
    {
        $this->assignedObjects = [];
        $this->roleMembership = [];
        $this->userMembership = [];
        $this->termMembership = [];
        $this->postMembership = [];
        $this->pluggableObjectMembership = [];
        $this->fullObjectMembership = [];
    }

    /**
     * Deletes the user group.
     *
     * @return bool
     */
    public function delete()
    {
        $allObjectTypes = $this->objectHandler->getAllObjectTypes();

        foreach ($allObjectTypes as $objectType) {
            $this->removeObject($objectType);
        }

        return true;
    }

    /**
     * Adds a object of the given type.
     *
     * @param string $objectType The object type.
     * @param string $objectId   The object id.
     * @param string $fromDate   From date.
     * @param string $toDate     To date.
     *
     * @return bool
     */
    public function addObject($objectType, $objectId, $fromDate = null, $toDate = null)
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
                'group_type' => $this->type,
                'object_id' => $objectId,
                'general_object_type' => $generalObjectType,
                'object_type' => $objectType,
                'from_date' => $fromDate,
                'to_date' => $toDate
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s'
            ]
        );

        if ($return !== false) {
            $this->resetObjects();
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
        $generalObjectType = $this->objectHandler->getGeneralObjectType($objectType);

        if ($generalObjectType === null
            || $this->objectHandler->isValidObjectType($objectType) === false
        ) {
            return false;
        }

        $query = "DELETE FROM {$this->database->getUserGroupToObjectTable()}
            WHERE group_id = %d
              AND group_type = '%s'
              AND (general_object_type = '%s' OR object_type = '%s')";

        $values = [
            $this->id,
            $this->type,
            $generalObjectType,
            $objectType
        ];

        if ($objectId !== null) {
            $query .= ' AND object_id = %d';
            $values[] = $objectId;
        }

        $query = $this->database->prepare($query, $values);
        $success = ($this->database->query($query) !== false);

        if ($success === true) {
            $this->resetObjects();
        }

        return $success;
    }

    /**
     * Returns the assigned objects.
     *
     * @param string $objectType The object type.
     *
     * @return AssignmentInformation[]
     */
    public function getAssignedObjects($objectType)
    {
        if (isset($this->assignedObjects[$objectType]) === false) {
            $query = "SELECT object_id AS id, object_type AS objectType, from_date AS fromDate, to_date AS toDate
                FROM {$this->database->getUserGroupToObjectTable()}
                WHERE group_id = '%s'
                  AND group_type = '%s'
                  AND (general_object_type = '%s' OR object_type = '%s')";

            $parameters = [
                $this->id,
                $this->type,
                $objectType,
                $objectType
            ];

            if ($this->ignoreDates === false) {
                $query .= " AND (from_date IS NULL OR from_date <= '%s') AND (to_date IS NULL OR to_date >= '%s')";
                $time = $this->wordpress->currentTime('mysql');
                $parameters = array_merge($parameters, [$time, $time]);
            }

            $query = $this->database->prepare($query, $parameters);
            $results = (array)$this->database->getResults($query);
            $this->assignedObjects[$objectType] = [];

            foreach ($results as $result) {
                $this->assignedObjects[$objectType][$result->id] = $this->assignmentInformationFactory
                    ->createAssignmentInformation($result->objectType, $result->fromDate, $result->toDate);
            }
        }

        return $this->assignedObjects[$objectType];
    }

    /**
     * Returns the assigned objects as array map.
     *
     * @param string $objectType The object type.
     *
     * @return array
     */
    private function getSimpleAssignedObjects($objectType)
    {
        $objects = $this->getAssignedObjects($objectType);
        return array_map(
            function (AssignmentInformation $element) {
                return $element->getType();
            },
            $objects
        );
    }

    /**
     * Checks if the object is assigned to the group.
     *
     * @param string                     $objectType            The object type.
     * @param string                     $objectId              The object id.
     * @param AssignmentInformation|null $assignmentInformation The assignment information object.
     *
     * @return bool
     */
    protected function isObjectAssignedToGroup(
        $objectType,
        $objectId,
        &$assignmentInformation = null
    ) {
        $assignmentInformation = null;
        $assignedObjects = $this->getAssignedObjects($objectType);

        if (isset($assignedObjects[$objectId]) === true) {
            $assignmentInformation = $assignedObjects[$objectId];
            return true;
        }

        return false;
    }

    /**
     * Assigns recursive membership to the assignment information object.
     *
     * @param AssignmentInformation $assignmentInformation
     * @param array                 $recursiveMembership
     */
    protected function assignRecursiveMembership(
        &$assignmentInformation,
        array $recursiveMembership
    ) {
        if ($assignmentInformation === null) {
            $assignmentInformation = $this->assignmentInformationFactory->createAssignmentInformation();
        }

        $assignmentInformation->setRecursiveMembership($recursiveMembership);
    }

    /**
     * Returns the recursive membership.
     *
     * @param \Closure              $mapFunction
     * @param string                $objectType
     * @param string                $objectId
     * @param AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    protected function isObjectRecursiveMember(
        $mapFunction,
        $objectType,
        $objectId,
        &$assignmentInformation = null
    ) {
        // Reset value to prevent errors
        $recursiveMembership = [];

        if ($this->config->lockRecursive() === true) {
            $map = $mapFunction();
            $generalMap = isset($map[ObjectHandler::TREE_MAP_PARENTS][$objectType]) ?
                $map[ObjectHandler::TREE_MAP_PARENTS][$objectType] : [];

            if (isset($generalMap[$objectId]) === true) {
                foreach ($generalMap[$objectId] as $parentId => $type) {
                    if ($this->isObjectAssignedToGroup($objectType, $parentId, $rmAssignmentInformation) === true) {
                        $recursiveMembership[$objectType][$parentId] = $rmAssignmentInformation;
                    }
                }
            }
        }

        $isMember = $this->isObjectAssignedToGroup($objectType, $objectId, $assignmentInformation);
        $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);

        return $isMember === true || count($recursiveMembership) > 0;
    }

    /**
     * Checks if the role is a group member.
     *
     * @param string                $roleId
     * @param AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isRoleMember(
        $roleId,
        &$assignmentInformation = null
    ) {
        if (isset($this->roleMembership[$roleId]) === false) {
            $isMember = $this->isObjectAssignedToGroup(
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                $roleId,
                $assignmentInformation
            );
            $this->roleMembership[$roleId] = ($isMember === true) ? $assignmentInformation : false;
        }

        $assignmentInformation = ($this->roleMembership[$roleId] !== false) ? $this->roleMembership[$roleId] : null;

        return ($this->roleMembership[$roleId] !== false);
    }

    /**
     * Checks if the user is a group member.
     *
     * @param int                   $userId                The user id.
     * @param AssignmentInformation $assignmentInformation The assignment information.
     *
     * @return bool
     */
    public function isUserMember(
        $userId,
        &$assignmentInformation = null
    ) {
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
                            $this->php->arrayFill(
                                0,
                                count($recursiveRoles),
                                $this->assignmentInformationFactory->createAssignmentInformation(
                                    ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                                )
                            )
                        );
                    }
                }
            }

            $isMember = $this->isObjectAssignedToGroup(
                ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                $userId,
                $assignmentInformation
            );
            $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);

            $this->userMembership[$userId] = ($isMember === true || count($recursiveMembership) > 0) ?
                $assignmentInformation : false;
        }

        $assignmentInformation = ($this->userMembership[$userId] !== false) ? $this->userMembership[$userId] : null;

        return ($this->userMembership[$userId] !== false);
    }

    /**
     * Checks if the term is a group member.
     *
     * @param int                   $termId
     * @param AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isTermMember(
        $termId,
        &$assignmentInformation = null
    ) {
        if (isset($this->termMembership[$termId]) === false) {
            $isMember = $this->isObjectRecursiveMember(
                function () {
                    return $this->objectHandler->getTermTreeMap();
                },
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                $termId,
                $assignmentInformation
            );

            $this->termMembership[$termId] = ($isMember === true) ? $assignmentInformation : false;
        }

        $assignmentInformation = ($this->termMembership[$termId] !== false) ? $this->termMembership[$termId] : null;

        return ($this->termMembership[$termId] !== false);
    }

    /**
     * Checks if the post is a group member
     *
     * @param int                   $postId
     * @param AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isPostMember(
        $postId,
        &$assignmentInformation = null
    ) {
        if (isset($this->postMembership[$postId]) === false) {
            $isMember = $this->isObjectRecursiveMember(
                function () {
                    return $this->objectHandler->getPostTreeMap();
                },
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                $postId,
                $assignmentInformation
            );

            if ($this->config->lockRecursive() === true) {
                $recursiveMembership = ($assignmentInformation !== null) ?
                    $assignmentInformation->getRecursiveMembership() : [];

                $postTermMap = $this->objectHandler->getPostTermMap();

                if (isset($postTermMap[$postId]) === true) {
                    foreach ($postTermMap[$postId] as $termId => $type) {
                        if ($this->isTermMember($termId, $rmAssignmentInformation) === true) {
                            $recursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$termId] =
                                $rmAssignmentInformation;
                        }
                    }
                }

                $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
                $isMember = $isMember || count($recursiveMembership) > 0;
            }

            $this->postMembership[$postId] = ($isMember === true) ? $assignmentInformation : false;
        }

        $assignmentInformation = ($this->postMembership[$postId] !== false) ? $this->postMembership[$postId] : null;

        return ($this->postMembership[$postId] !== false);
    }

    /**
     * Returns a the recursive membership for a pluggable object.
     *
     * @param string                $objectType            The pluggable object type.
     * @param string                $objectId              The object id.
     * @param AssignmentInformation $assignmentInformation The assignment information
     *
     * @return bool
     */
    public function isPluggableObjectMember(
        $objectType,
        $objectId,
        &$assignmentInformation = null
    ) {
        if (isset($this->pluggableObjectMembership[$objectType]) === false) {
            $this->pluggableObjectMembership[$objectType] = [];
        }

        if (isset($this->pluggableObjectMembership[$objectType][$objectId]) === false) {
            $isMember = false;
            $pluggableObject = $this->objectHandler->getPluggableObject($objectType);

            if ($pluggableObject !== null) {
                $recursiveMembership = $pluggableObject->getRecursiveMembership($this, $objectId);
                $isMember = $this->isObjectAssignedToGroup($objectType, $objectId, $assignmentInformation)
                    || count($recursiveMembership) > 0;

                $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
            }

            $this->pluggableObjectMembership[$objectType][$objectId] = ($isMember === true) ?
                $assignmentInformation : false;
        }

        $assignmentInformation = ($this->pluggableObjectMembership[$objectType][$objectId] !== false) ?
            $this->pluggableObjectMembership[$objectType][$objectId] : null;

        return ($this->pluggableObjectMembership[$objectType][$objectId] !== false);
    }

    /**
     * Returns a single object.
     *
     * @param string                $objectType            The object type.
     * @param string                $objectId              The id of the object which should be checked.
     * @param AssignmentInformation $assignmentInformation The assignment information
     *
     * @return bool
     */
    public function isObjectMember(
        $objectType,
        $objectId,
        &$assignmentInformation = null
    ) {
        $isMember = false;

        if ($objectType === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
            $isMember = $this->isRoleMember($objectId, $assignmentInformation);
        } elseif ($objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            $isMember = $this->isUserMember($objectId, $assignmentInformation);
        } elseif ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
            || $this->objectHandler->isTaxonomy($objectType) === true
        ) {
            $isMember = $this->isTermMember($objectId, $assignmentInformation);
        } elseif ($objectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
            || $this->objectHandler->isPostType($objectType) === true
        ) {
            $isMember = $this->isPostMember($objectId, $assignmentInformation);
        } elseif ($this->objectHandler->isPluggableObject($objectType) === true) {
            $isMember = $this->isPluggableObjectMember(
                $objectType,
                $objectId,
                $assignmentInformation
            );
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
        /**
         * @var AssignmentInformation $assignmentInformation
         */
        if ($this->isObjectMember($objectType, $objectId, $assignmentInformation) === true) {
            return $assignmentInformation->getRecursiveMembership();
        }

        return [];
    }

    /**
     * Returns true if the requested object is locked recursive.
     *
     * @param string $objectType The object type.
     * @param string $objectId   The object id.
     *
     * @return bool
     */
    public function isLockedRecursive($objectType, $objectId)
    {
        /**
         * @var AssignmentInformation $assignmentInformation
         */
        if ($this->isObjectMember($objectType, $objectId, $assignmentInformation) === true) {
            return (count($assignmentInformation->getRecursiveMembership()) > 0);
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
        $objects = $this->getSimpleAssignedObjects($objectType);

        if ($this->config->lockRecursive() === true) {
            $map = $mapFunction();
            $map = isset($map[ObjectHandler::TREE_MAP_CHILDREN][$objectType]) ?
                $map[ObjectHandler::TREE_MAP_CHILDREN][$objectType] : [];
            $map = array_intersect_key($map, $objects);

            foreach ($map as $childrenIds) {
                foreach ($childrenIds as $parentId => $type) {
                    if ($this->isObjectMember($objectType, $parentId) === true) {
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
                    if (isset($termsPostMap[$termId]) === true) {
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
            return $this->getSimpleAssignedObjects($objectType);
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
