<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\Exception\MissingObjectMembershipHandlerException;
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
        protected Wordpress $wordpress,
        protected Database $database,
        protected MainConfig $config,
        protected ObjectHandler $objectHandler,
        protected AssignedObjectsLoader $assignedObjectsLoader,
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

    private function resetObjectsAfterAssignmentChange(): void
    {
        $this->assignedObjectsLoader->flush();
        $this->resetObjects();
    }

    /**
     * Returns the general object type of an assignable object type, null if the object type can't be assigned.
     */
    private function getAssignableGeneralObjectType(string $objectType): ?string
    {
        $generalObjectType = $this->objectHandler->getGeneralObjectType($objectType);

        return ($generalObjectType !== null && $this->objectHandler->isValidObjectType($objectType) === true) ?
            $generalObjectType : null;
    }

    /**
     * @throws Exception
     */
    public function delete(): bool
    {
        foreach ($this->objectHandler->getAllObjectTypes() as $objectType) {
            $this->removeObject($objectType);
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function addObject(string $objectType, int|string|null $objectId, $fromDate = null, $toDate = null): bool
    {
        $generalObjectType = $this->getAssignableGeneralObjectType($objectType);

        if ($generalObjectType === null) {
            return false;
        }

        $success = $this->database->replace(
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
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        ) !== false;

        if ($success === true) {
            $this->resetObjectsAfterAssignmentChange();
        }

        return $success;
    }

    /**
     * @throws Exception
     */
    public function removeObject(string $objectType, $objectId = null, bool $ignoreGeneralType = false): bool
    {
        $generalObjectType = $this->getAssignableGeneralObjectType($objectType);

        if ($generalObjectType === null) {
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

        $success = $this->database->query($this->database->prepare($query, $values)) !== false;

        if ($success === true) {
            $this->resetObjectsAfterAssignmentChange();
        }

        return $success;
    }

    /**
     * @return AssignmentInformation[]
     */
    public function getAssignedObjects(string $objectType): array
    {
        return $this->assignedObjects[$objectType] ??= $this->assignedObjectsLoader->getAssignedObjects(
            $this->type,
            $this->id,
            $objectType,
            $this->ignoreDates
        );
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

    /**
     * @return array<string, array{0: int|null, 1: int|null}> Time ranges keyed by the object type.
     */
    public function getDefaultGroupForObjectTypes(): array
    {
        return $this->defaultTypes ??= $this->loadDefaultGroupForObjectTypes();
    }

    /**
     * @return array<string, array{0: int|null, 1: int|null}>
     */
    private function loadDefaultGroupForObjectTypes(): array
    {
        $query = $this->database->prepare(
            "SELECT object_type AS objectType, from_date AS fromDate, to_date AS toDate
                FROM {$this->database->getUserGroupToObjectTable()}
                WHERE group_id = '%s'
                  AND group_type = '%s'
                  AND object_id = ''",
            [
                $this->id,
                $this->type
            ]
        );

        $defaultTypes = [];

        foreach ((array) $this->database->getResults($query) as $result) {
            $defaultTypes[$result->objectType] = [
                ($result->fromDate !== null) ? strtotime($result->fromDate) : null,
                ($result->toDate !== null) ? strtotime($result->toDate) : null
            ];
        }

        return $defaultTypes;
    }

    public function isDefaultGroupForObjectType(string $objectType, ?int &$fromTime = null, ?int &$toTime = null): bool
    {
        $defaultGroupForObjectTypes = $this->getDefaultGroupForObjectTypes();

        // The reference values have to be reset even when the group is no default group for the object type
        $fromTime = null;
        $toTime = null;

        if (isset($defaultGroupForObjectTypes[$objectType]) === false) {
            return false;
        }

        [$fromTimestamp, $toTimestamp] = $defaultGroupForObjectTypes[$objectType];
        $fromTime = ($fromTimestamp !== null) ? (int) $fromTimestamp : null;
        $toTime = ($toTimestamp !== null) ? (int) $toTimestamp : null;

        return true;
    }

    public function isObjectAssignedToGroup(
        string $objectType,
        int|string|null $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        $assignmentInformation = $this->getAssignedObjects($objectType)[$objectId] ?? null;

        return $assignmentInformation !== null;
    }

    /**
     * @throws Exception
     */
    public function isObjectMember(
        string $objectType,
        int|string|null $objectId,
        ?AssignmentInformation &$assignmentInformation = null
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

            $this->objectMembership[$objectType][$objectId] = ($isMember === true) ? $assignmentInformation : false;
        }

        $membership = $this->objectMembership[$objectType][$objectId];
        $assignmentInformation = ($membership instanceof AssignmentInformation) ? $membership : null;

        return $membership !== false;
    }

    /**
     * @throws Exception
     */
    public function isRoleMember(int|string|null $roleId, ?AssignmentInformation &$assignmentInformation = null): bool
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, $roleId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function isUserMember(int|string|null $userId, ?AssignmentInformation &$assignmentInformation = null): bool
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function isTermMember(int|string|null $termId, ?AssignmentInformation &$assignmentInformation = null): bool
    {
        return $this->isObjectMember(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function isPostMember(int|string|null $postId, ?AssignmentInformation &$assignmentInformation = null): bool
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
        return count($this->getRecursiveMembershipForObject($objectType, $objectId)) > 0;
    }

    /**
     * @throws Exception
     */
    public function getAssignedObjectsByType(string $objectType): array
    {
        if (isset($this->fullObjectMembership[$objectType]) === false) {
            try {
                $membershipHandler = $this->objectHandler->getObjectMembershipHandler($objectType);
                $this->fullObjectMembership[$objectType] = $membershipHandler->getFullObjects(
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
