<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

class AssignmentInformationFactory
{
    public function createAssignmentInformation(
        string $type = null,
        string $fromDate = null,
        string $toDate = null,
        array $recursiveMembership = []
    ): AssignmentInformation {
        return new AssignmentInformation($type, $fromDate, $toDate, $recursiveMembership);
    }
}
