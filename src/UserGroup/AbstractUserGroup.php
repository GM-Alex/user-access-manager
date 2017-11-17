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
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
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
     * @var ObjectMembershipHandlerFactory
     */
    protected $membershipHandlerFactory;

    /**
     * @var AssignmentInformationFactory
     */
    protected $assignmentInformationFactory;

    /**
     * @var string
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
    protected $objectMembership = [];

    /**
     * @var array
     */
    protected $fullObjectMembership = [];

    /**
     * @var array|null
     */
    protected $defaultTypes = null;

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
     * @param null|string                  $id
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
     * @return string
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
        $this->objectMembership = [];
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

        $return = $this->database->replace(
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
     * @param string $objectType        The object type.
     * @param string $objectId          The object id.
     * @param bool   $ignoreGeneralType
     *
     * @return bool
     */
    public function removeObject($objectType, $objectId = null, $ignoreGeneralType = false)
    {
        $generalObjectType = $this->objectHandler->getGeneralObjectType($objectType);

        if ($generalObjectType === null
            || $this->objectHandler->isValidObjectType($objectType) === false
        ) {
            return false;
        }

        $objectTypeQuery = " AND object_type = '%s' ";
        $values = [
            $this->id,
            $this->type,
            $objectType
        ];

        if ($ignoreGeneralType === false) {
            $objectTypeQuery = " AND (object_type = '%s' OR general_object_type = '%s') ";
            $values[] = $generalObjectType;
        }

        $query = "DELETE FROM {$this->database->getUserGroupToObjectTable()}
            WHERE group_id = %d
              AND group_type = '%s'
              {$objectTypeQuery}";

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
                  AND object_id != ''
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
     * Marks the group as default for the object type.
     *
     * @param string   $objectType
     * @param null|int $fromTime
     * @param null|int $toTime
     *
     * @return bool
     */
    public function addDefaultType($objectType, $fromTime = null, $toTime = null)
    {
        $fromDate = ($fromTime !== null) ? gmdate('Y-m-d H:i:s', $fromTime) : null;
        $toTime = ($toTime !== null && $toTime <= $fromTime) ? $fromTime + 1 : $toTime;
        $toDate = ($toTime !== null) ? gmdate('Y-m-d H:i:s', $toTime) : null;

        return $this->addObject($objectType, '', $fromDate, $toDate);
    }

    /**
     * Removes the group as default for object type.
     *
     * @param string $objectType
     *
     * @return bool
     */
    public function removeDefaultType($objectType)
    {
        return $this->removeObject($objectType, '', true);
    }

    /**
     * Returns the object type for which the user group is the default group.
     *
     * @return array
     */
    public function getDefaultGroupForObjectTypes()
    {
        if ($this->defaultTypes === null) {
            $this->defaultTypes = [];

            $query = "SELECT object_type AS objectType, from_date AS fromDate, to_date AS toDate
                FROM {$this->database->getUserGroupToObjectTable()}
                WHERE group_id = '%s'
                  AND group_type = '%s'
                  AND object_id = ''";

            $parameters = [
                $this->id,
                $this->type
            ];

            $query = $this->database->prepare($query, $parameters);
            $results = (array)$this->database->getResults($query);

            foreach ($results as $result) {
                $this->defaultTypes[$result->objectType] = [
                    ($result->fromDate !== null) ? strtotime($result->fromDate) : null,
                    ($result->toDate !== null) ? strtotime($result->toDate) : null
                ];
            }
        }

        return $this->defaultTypes;
    }

    /**
     * Checks if the group is the default one for the given object type.
     *
     * @param string   $objectType
     * @param null|int $fromTime
     * @param null|int $toTime
     *
     * @return bool
     */
    public function isDefaultGroupForObjectType($objectType, &$fromTime = null, &$toTime = null)
    {
        $defaultGroupForObjectTypes = $this->getDefaultGroupForObjectTypes();

        // Reset reference values anyway
        $fromTime = null;
        $toTime = null;

        if (isset($defaultGroupForObjectTypes[$objectType])) {
            $fromTime = $defaultGroupForObjectTypes[$objectType][0] !== null ?
                (int)$defaultGroupForObjectTypes[$objectType][0] : null;
            $toTime = $defaultGroupForObjectTypes[$objectType][1] !== null ?
                (int)$defaultGroupForObjectTypes[$objectType][1] : null;

            return true;
        }

        return false;
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
    public function isObjectAssignedToGroup(
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
        if (isset($this->objectMembership[$objectType][$objectId]) === false) {
            try {
                $isMember = $this->objectHandler->getObjectMembershipHandler($objectType)->isMember(
                    $this,
                    $this->config->lockRecursive(),
                    $objectId,
                    $assignmentInformation
                );
            } catch (MissingObjectMembershipHandlerException $exception) {
                $isMember = false;
            }

            $this->objectMembership[$objectType][$objectId] = ($isMember === true) ?
                $assignmentInformation : false;
        }

        $assignmentInformation = ($this->objectMembership[$objectType][$objectId] instanceof AssignmentInformation) ?
            $this->objectMembership[$objectType][$objectId] : null;

        return ($this->objectMembership[$objectType][$objectId] !== false);
    }

    /**
     * Checks if the role is a group member.
     *
     * @param string                $roleId
     * @param AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isRoleMember($roleId, &$assignmentInformation = null)
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, $roleId, $assignmentInformation);
    }

    /**
     * Checks if the user is a group member.
     *
     * @param int                   $userId                The user id.
     * @param AssignmentInformation $assignmentInformation The assignment information.
     *
     * @return bool
     */
    public function isUserMember($userId, &$assignmentInformation = null)
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId, $assignmentInformation);
    }

    /**
     * Checks if the term is a group member.
     *
     * @param int                   $termId
     * @param AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isTermMember($termId, &$assignmentInformation = null)
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId, $assignmentInformation);
    }

    /**
     * Checks if the post is a group member
     *
     * @param int                   $postId
     * @param AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isPostMember($postId, &$assignmentInformation = null)
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $postId, $assignmentInformation);
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
     * Returns all objects of the given type.
     *
     * @param string $objectType The object type.
     *
     * @return array
     */
    public function getAssignedObjectsByType($objectType)
    {
        if (isset($this->fullObjectMembership[$objectType]) === false) {
            try {
                $handler = $this->objectHandler->getObjectMembershipHandler($objectType);
                $this->fullObjectMembership[$objectType] = $handler->getFullObjects(
                    $this,
                    $this->config->lockRecursive(),
                    ($objectType === $this->objectHandler->getGeneralObjectType($objectType)) ? null : $objectType
                );
            } catch (MissingObjectMembershipHandlerException $exception) {
                $this->fullObjectMembership[$objectType] = [];
            }
        }

        return $this->fullObjectMembership[$objectType];
    }

    /**
     * Returns the roles assigned to the group.
     *
     * @return array
     */
    public function getFullRoles()
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);
    }


    /**
     * Returns the users assigned to the group.
     *
     * @return array
     */
    public function getFullUsers()
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_USER_OBJECT_TYPE);
    }

    /**
     * Returns the terms assigned to the group.
     *
     * @return array
     */
    public function getFullTerms()
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE);
    }

    /**
     * Returns the posts assigned to the group.
     *
     * @return array
     */
    public function getFullPosts()
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_POST_OBJECT_TYPE);
    }
}
