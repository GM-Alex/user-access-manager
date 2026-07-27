<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use UserAccessManager\Database\Database;
use UserAccessManager\Wrapper\Wordpress;

class AssignedObjectsLoader
{
    /** @var array<string, array<int, array<string, array<string, AssignmentInformation[]>>>> */
    private array $assignedObjects = [];

    public function __construct(
        private Wordpress $wordpress,
        private Database $database,
        private AssignmentInformationFactory $assignmentInformationFactory
    ) {
    }

    /**
     * @return AssignmentInformation[] The assigned objects of the user group, keyed by the object id.
     */
    public function getAssignedObjects(
        ?string $userGroupType,
        int|string|null $userGroupId,
        string $objectType,
        bool $ignoreDates
    ): array {
        if (isset($this->assignedObjects[$objectType][$ignoreDates]) === false) {
            $this->assignedObjects[$objectType][$ignoreDates] = $this->loadAssignedObjects($objectType, $ignoreDates);
        }

        return $this->assignedObjects[$objectType][$ignoreDates][$userGroupType][$userGroupId] ?? [];
    }

    public function flush(): void
    {
        $this->assignedObjects = [];
    }

    /**
     * @return array<string, array<string, AssignmentInformation[]>>
     */
    private function loadAssignedObjects(string $objectType, bool $ignoreDates): array
    {
        $query = "SELECT group_id AS groupId, group_type AS groupType, object_id AS id,
                object_type AS objectType, from_date AS fromDate, to_date AS toDate
            FROM {$this->database->getUserGroupToObjectTable()}
            WHERE object_id != ''
              AND (general_object_type = '%s' OR object_type = '%s')";

        $parameters = [
            $objectType,
            $objectType
        ];

        if ($ignoreDates === false) {
            $query .= " AND (from_date IS NULL OR from_date <= '%s') AND (to_date IS NULL OR to_date >= '%s')";
            $time = $this->wordpress->currentTime('mysql');
            $parameters = array_merge($parameters, [$time, $time]);
        }

        $query = $this->database->prepare($query, $parameters);
        $results = (array) $this->database->getResults($query);
        $assignedObjects = [];

        foreach ($results as $result) {
            $assignedObjects[$result->groupType][$result->groupId][$result->id] = $this->assignmentInformationFactory
                ->createAssignmentInformation($result->objectType, $result->fromDate, $result->toDate);
        }

        return $assignedObjects;
    }
}
