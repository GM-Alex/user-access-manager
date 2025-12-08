<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;

abstract class ObjectMembershipHandler
{
    protected ?string $generalObjectType = null;

    /**
     * @throws Exception
     */
    public function __construct(
        protected AssignmentInformationFactory $assignmentInformationFactory
    ) {
        if ($this->generalObjectType === null) {
            throw new MissingObjectTypeException('Missing general object type, Object type must be set.');
        }
    }

    abstract public function getObjectName(int|string $objectId, string &$typeName = ''): int|string;

    public function getGeneralObjectType(): string
    {
        return $this->generalObjectType;
    }

    public function getHandledObjects(): array
    {
        return [$this->generalObjectType => $this->generalObjectType];
    }

    public function handlesObject(mixed $objectType): bool
    {
        $objectTypes = $this->getHandledObjects();
        return isset($objectTypes[$objectType]);
    }

    protected function assignRecursiveMembership(
        ?AssignmentInformation &$assignmentInformation,
        array $recursiveMembership
    ): void {
        if ($assignmentInformation === null) {
            $assignmentInformation = $this->assignmentInformationFactory->createAssignmentInformation();
        }

        $assignmentInformation->setRecursiveMembership($recursiveMembership);
    }

    protected function checkAccessWithRecursiveMembership(
        bool $isMember,
        array $recursiveMembership,
        ?AssignmentInformation &$assignmentInformation
    ): bool {
        if ($isMember === true || count($recursiveMembership) > 0) {
            $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
            return true;
        }

        return false;
    }

    protected function getSimpleAssignedObjects(AbstractUserGroup $userGroup, string $objectType): array
    {
        $objects = $userGroup->getAssignedObjects($objectType);

        return array_map(
            function (AssignmentInformation $element) {
                return $element->getType();
            },
            $objects
        );
    }

    abstract public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool;

    abstract public function getFullObjects(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        ?string $objectType = null
    ): array;
}
