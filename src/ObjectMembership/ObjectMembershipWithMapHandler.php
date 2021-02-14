<?php
/**
 * MembershipWithMapHandler.php
 *
 * The MembershipWithMapHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;

/**
 * Class MembershipWithMapHandler
 *
 * @package UserAccessManager\UserGroup
 */
abstract class ObjectMembershipWithMapHandler extends ObjectMembershipHandler
{
    /**
     * Returns the map.
     * @return array
     */
    abstract protected function getMap(): array;

    /**
     * Uses a map function to resolve the recursive membership.
     * @param AbstractUserGroup $userGroup
     * @param bool $lockRecursive
     * @param int|string $objectId
     * @param null|AssignmentInformation $assignmentInformation
     * @return bool
     * @throws Exception
     */
    protected function getMembershipByMap(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        $objectId,
        &$assignmentInformation = null
    ): bool {
        // Reset value to prevent errors
        $recursiveMembership = [];

        if ($lockRecursive === true) {
            $map = $this->getMap();
            $generalMap = isset($map[ObjectMapHandler::TREE_MAP_PARENTS][$this->generalObjectType]) ?
                $map[ObjectMapHandler::TREE_MAP_PARENTS][$this->generalObjectType] : [];

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
     * Returns the objects by the given type including the children.
     * @param AbstractUserGroup $userGroup
     * @param bool $lockRecursive
     * @param string $objectType
     * @return array
     * @throws Exception
     */
    protected function getFullObjectsByMap(AbstractUserGroup $userGroup, bool $lockRecursive, string $objectType): array
    {
        $objects = $this->getSimpleAssignedObjects($userGroup, $objectType);

        if ($lockRecursive === true) {
            $map = $this->getMap();
            $map = isset($map[ObjectMapHandler::TREE_MAP_CHILDREN][$objectType]) ?
                $map[ObjectMapHandler::TREE_MAP_CHILDREN][$objectType] : [];
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
