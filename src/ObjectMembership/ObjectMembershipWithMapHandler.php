<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;

abstract class ObjectMembershipWithMapHandler extends ObjectMembershipHandler
{
    /**
     * Returns the map.
     * @return array
     */
    abstract protected function getMap(): array;

    /**
     * @throws Exception
     */
    protected function getMembershipByMap(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string $objectId,
        AssignmentInformation &$assignmentInformation = null
    ): bool {
        // Reset value to prevent errors
        $recursiveMembership = [];

        if ($lockRecursive === true) {
            $map = $this->getMap();
            $generalMap = $map[ObjectMapHandler::TREE_MAP_PARENTS][$this->generalObjectType] ?? [];

            if (isset($generalMap[$objectId]) === true) {
                foreach ($generalMap[$objectId] as $parentId => $type) {
                    $isObjectMember = $userGroup->isObjectMember(
                        $this->generalObjectType,
                        $parentId,
                        $rmAssignmentInformation
                    );

                    if ($isObjectMember === true) {
                        $recursiveMembership[$this->generalObjectType][$parentId] = $rmAssignmentInformation;
                    }
                }
            }
        }

        $isMember = $userGroup->isObjectAssignedToGroup($this->generalObjectType, $objectId, $assignmentInformation);
        return $this->checkAccessWithRecursiveMembership($isMember, $recursiveMembership, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    protected function getFullObjectsByMap(AbstractUserGroup $userGroup, bool $lockRecursive, string $objectType): array
    {
        $objects = $this->getSimpleAssignedObjects($userGroup, $objectType);

        if ($lockRecursive === true) {
            $map = $this->getMap();
            $map = $map[ObjectMapHandler::TREE_MAP_CHILDREN][$objectType] ?? [];
            $map = array_intersect_key($map, $objects);

            foreach ($map as $childrenIds) {
                foreach ($childrenIds as $parentId => $type) {
                    if ($userGroup->isObjectMember($objectType, $parentId) === true) {
                        $objects[$parentId] = $type;
                    }
                }
            }
        }

        return $objects;
    }
}
