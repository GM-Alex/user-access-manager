<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

abstract class AbstractUserGroup
{
    public const NONE_ROLE = '_none-role_';

    protected ?string $type = null;
    protected ?string $name = null;
    protected ?string $description = null;
    protected string $readAccess = 'group';
    protected string $writeAccess = 'group';
    protected bool $ignoreDates = false;
    protected array $assignedObjects = [];
    protected array $objectMembership = [];
    protected array $fullObjectMembership = [];
    protected ?array $defaultTypes = null;

    /**
     * @throws UserGroupTypeException
     */
    public function __construct(
        protected Php $php,
        protected Wordpress $wordpress,
        protected Database $database,
        protected MainConfig $config,
        protected Util $util,
        protected ObjectHandler $objectHandler,
        protected AssignmentInformationFactory $assignmentInformationFactory,
        protected int|string|null $id = null
    ) {
        if ($this->type === null) {
            throw new UserGroupTypeException('User group type must not null.');
        }
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getReadAccess(): string
    {
        return $this->readAccess;
    }

    public function setReadAccess(string $readAccess): void
    {
        $this->readAccess = $readAccess;
    }

    public function getWriteAccess(): string
    {
        return $this->writeAccess;
    }

    public function setWriteAccess(string $writeAccess): void
    {
        $this->writeAccess = $writeAccess;
    }

    public function setIgnoreDates(bool $ignoreDates): void
    {
        if ($this->ignoreDates !== $ignoreDates) {
            $this->resetObjects();
        }

        $this->ignoreDates = $ignoreDates;
    }

    public function getIgnoreDates(): bool
    {
        return $this->ignoreDates;
    }

    protected function resetObjects(): void
    {
        $this->assignedObjects = [];
        $this->objectMembership = [];
        $this->fullObjectMembership = [];
    }

    /**
     * @throws Exception
     */
    public function delete(): bool
    {
        $allObjectTypes = $this->objectHandler->getAllObjectTypes();

        foreach ($allObjectTypes as $objectType) {
            $this->removeObject($objectType);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function addObject(string $objectType, int|string|null $objectId, $fromDate = null, $toDate = null): bool
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
     * @throws Exception
     */
    public function removeObject(string $objectType, $objectId = null, bool $ignoreGeneralType = false): bool
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
              $objectTypeQuery";

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
     * @return AssignmentInformation[]
     */
    public function getAssignedObjects(string $objectType): array
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
            $results = (array) $this->database->getResults($query);
            $this->assignedObjects[$objectType] = [];

            foreach ($results as $result) {
                $this->assignedObjects[$objectType][$result->id] = $this->assignmentInformationFactory
                    ->createAssignmentInformation($result->objectType, $result->fromDate, $result->toDate);
            }
        }

        return $this->assignedObjects[$objectType];
    }

    /**
     * @throws Exception
     */
    public function addDefaultType(
        string $objectType,
        int|string|null $fromTime = null,
        int|string|null $toTime = null
    ): bool {
        $fromDate = ($fromTime !== null) ? gmdate('Y-m-d H:i:s', $fromTime) : null;
        $toTime = ($toTime !== null && $toTime <= $fromTime) ? $fromTime + 1 : $toTime;
        $toDate = ($toTime !== null) ? gmdate('Y-m-d H:i:s', $toTime) : null;

        return $this->addObject($objectType, '', $fromDate, $toDate);
    }

    /**
     * @throws Exception
     */
    public function removeDefaultType(string $objectType): bool
    {
        return $this->removeObject($objectType, '', true);
    }

    public function getDefaultGroupForObjectTypes(): ?array
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
            $results = (array) $this->database->getResults($query);

            foreach ($results as $result) {
                $this->defaultTypes[$result->objectType] = [
                    ($result->fromDate !== null) ? strtotime($result->fromDate) : null,
                    ($result->toDate !== null) ? strtotime($result->toDate) : null
                ];
            }
        }

        return $this->defaultTypes;
    }

    public function isDefaultGroupForObjectType(string $objectType, int &$fromTime = null, int &$toTime = null): bool
    {
        $defaultGroupForObjectTypes = $this->getDefaultGroupForObjectTypes();

        // Reset reference values anyway
        $fromTime = null;
        $toTime = null;

        if (isset($defaultGroupForObjectTypes[$objectType])) {
            $fromTime = $defaultGroupForObjectTypes[$objectType][0] !== null ?
                (int) $defaultGroupForObjectTypes[$objectType][0] : null;
            $toTime = $defaultGroupForObjectTypes[$objectType][1] !== null ?
                (int) $defaultGroupForObjectTypes[$objectType][1] : null;

            return true;
        }

        return false;
    }

    public function isObjectAssignedToGroup(
        string $objectType,
        int|string|null $objectId,
        AssignmentInformation &$assignmentInformation = null
    ): bool {
        $assignmentInformation = null;
        $assignedObjects = $this->getAssignedObjects($objectType);

        if (isset($assignedObjects[$objectId]) === true) {
            $assignmentInformation = $assignedObjects[$objectId];
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function isObjectMember(
        string $objectType,
        int|string|null $objectId,
        AssignmentInformation &$assignmentInformation = null
    ): bool {
        if (isset($this->objectMembership[$objectType][$objectId]) === false) {
            try {
                $isMember = $this->objectHandler->getObjectMembershipHandler($objectType)->isMember(
                    $this,
                    $this->config->lockRecursive(),
                    $objectId,
                    $assignmentInformation
                );
            } catch (MissingObjectMembershipHandlerException) {
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
     * @throws Exception
     */
    public function isRoleMember(int|string $roleId, ?AssignmentInformation &$assignmentInformation = null): bool
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, $roleId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function isUserMember(int|string $userId, AssignmentInformation &$assignmentInformation = null): bool
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function isTermMember(int|string $termId, AssignmentInformation &$assignmentInformation = null): bool
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function isPostMember(int|string $postId, AssignmentInformation &$assignmentInformation = null): bool
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $postId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function getRecursiveMembershipForObject(string $objectType, int|string|null $objectId): array
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
     * @throws Exception
     */
    public function isLockedRecursive(string $objectType, int|string|null $objectId): bool
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
     * @throws Exception
     */
    public function getAssignedObjectsByType(string $objectType): array
    {
        if (isset($this->fullObjectMembership[$objectType]) === false) {
            try {
                $handler = $this->objectHandler->getObjectMembershipHandler($objectType);
                $this->fullObjectMembership[$objectType] = $handler->getFullObjects(
                    $this,
                    $this->config->lockRecursive(),
                    ($objectType === $this->objectHandler->getGeneralObjectType($objectType)) ? null : $objectType
                );
            } catch (MissingObjectMembershipHandlerException) {
                $this->fullObjectMembership[$objectType] = [];
            }
        }

        return $this->fullObjectMembership[$objectType];
    }

    /**
     * @throws Exception
     */
    public function getFullRoles(): array
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);
    }


    /**
     * @throws Exception
     */
    public function getFullUsers(): array
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_USER_OBJECT_TYPE);
    }

    /**
     * @throws Exception
     */
    public function getFullTerms(): array
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE);
    }

    /**
     * @throws Exception
     */
    public function getFullPosts(): array
    {
        return $this->getAssignedObjectsByType(ObjectHandler::GENERAL_POST_OBJECT_TYPE);
    }
}
