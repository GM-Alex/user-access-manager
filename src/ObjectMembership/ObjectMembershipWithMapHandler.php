<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;

abstract class ObjectMembershipWithMapHandler extends ObjectMembershipHandler
{
    abstract protected function getMap(): array;

    protected function getHandledObjectsIncluding(array $objectTypes): array
    {
        $objectTypeNames = array_keys($objectTypes);

        return array_merge(
            parent::getHandledObjects(),
            array_combine($objectTypeNames, $objectTypeNames)
        );
    }

    /**
     * @throws Exception
     */
    private function getRecursiveMembershipByMap(AbstractUserGroup $userGroup, int|string|null $objectId): array
    {
        $parentMap = $this->getMap()[ObjectMapHandler::TREE_MAP_PARENTS][$this->generalObjectType] ?? [];
        $recursiveMembership = [];

        foreach (array_keys($parentMap[$objectId] ?? []) as $parentId) {
            $isObjectMember = $userGroup->isObjectMember(
                $this->generalObjectType,
                $parentId,
                $parentAssignmentInformation
            );

            if ($isObjectMember === true) {
                $recursiveMembership[$this->generalObjectType][$parentId] = $parentAssignmentInformation;
            }
        }

        return $recursiveMembership;
    }

    /**
     * @throws Exception
     */
    protected function getMembershipByMap(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string|null $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        $recursiveMembership = ($lockRecursive === true) ?
            $this->getRecursiveMembershipByMap($userGroup, $objectId) : [];

        $isMember = $userGroup->isObjectAssignedToGroup($this->generalObjectType, $objectId, $assignmentInformation);
        return $this->checkAccessWithRecursiveMembership($isMember, $recursiveMembership, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    protected function getFullObjectsByMap(AbstractUserGroup $userGroup, bool $lockRecursive, string $objectType): array
    {
        $objects = $this->getSimpleAssignedObjects($userGroup, $objectType);

        if ($lockRecursive === false) {
            return $objects;
        }

        $childrenMap = $this->getMap()[ObjectMapHandler::TREE_MAP_CHILDREN][$objectType] ?? [];

        foreach (array_intersect_key($childrenMap, $objects) as $childrenIds) {
            foreach ($childrenIds as $parentId => $type) {
                if ($userGroup->isObjectMember($objectType, $parentId) === true) {
                    $objects[$parentId] = $type;
                }
            }
        }

        return $objects;
    }
}
