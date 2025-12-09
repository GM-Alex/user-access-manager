<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\User\UserHandler;
use UserAccessManager\Util\DateUtil;

class UserGroupAssignmentHandler
{
    public function __construct(
        protected DateUtil $dateUtil,
        protected UserHandler $userHandler,
        protected UserGroupHandler $userGroupHandler,
        protected UserGroupFactory $userGroupFactory
    ) {
    }

    private function getDateParameter(array $data, string $name): ?string
    {
        $isValid = isset($data[$name]['date']) === true && isset($data[$name]['time']) === true
            && (string) $data[$name]['date'] !== '' && (string) $data[$name]['time'] !== '';

        return ($isValid === true) ? ((string) $data[$name]['date']) . 'T' . $data[$name]['time'] : null;
    }

    /**
     * @throws Exception
     */
    private function setUserGroups(
        array $filteredUserGroups,
        string $objectType,
        int|string $objectId,
        array $addUserGroups,
        array $removeUserGroups
    ): void {
        /** @var UserGroup $userGroup */
        foreach ($filteredUserGroups as $groupId => $userGroup) {
            if (isset($removeUserGroups[$groupId]) === true) {
                $userGroup->removeObject($objectType, $objectId);
            }

            if (isset($addUserGroups[$groupId]['id']) === true
                && (int) $addUserGroups[$groupId]['id'] === (int) $groupId
            ) {
                $userGroup->addObject(
                    $objectType,
                    $objectId,
                    $this->getDateParameter($addUserGroups[$groupId], 'fromDate'),
                    $this->getDateParameter($addUserGroups[$groupId], 'toDate')
                );
            }
        }
    }

    /**
     * @throws UserGroupAssignmentException
     * @throws UserGroupTypeException
     */
    private function setDynamicGroups(string $objectType, int|string $objectId, array $addDynamicUserGroups): void
    {
        foreach ($addDynamicUserGroups as $dynamicUserGroupKey => $addDynamicUserGroup) {
            $dynamicUserGroupData = explode('|', $dynamicUserGroupKey);

            if (count($dynamicUserGroupData) === 2
                && $addDynamicUserGroup['id'] === $dynamicUserGroupKey
            ) {
                $dynamicUserGroup = $this->userGroupFactory->createDynamicUserGroup(
                    $dynamicUserGroupData[0],
                    $dynamicUserGroupData[1]
                );

                $dynamicUserGroup->addObject(
                    $objectType,
                    $objectId,
                    $this->getDateParameter($addDynamicUserGroup, 'fromDate'),
                    $this->getDateParameter($addDynamicUserGroup, 'toDate')
                );
            }
        }
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    private function setDefaultGroups(array $filteredUserGroups, string $objectType, int|string $objectId): void
    {
        /**
         * @var UserGroup[] $userGroupsToCheck
         */
        $userGroupsToCheck = array_diff_key($this->userGroupHandler->getFullUserGroups(), $filteredUserGroups);

        foreach ($userGroupsToCheck as $userGroupToCheck) {
            if ($userGroupToCheck->isDefaultGroupForObjectType($objectType, $fromTime, $toTime) === true) {
                $userGroupToCheck->addObject(
                    $objectType,
                    $objectId,
                    $this->dateUtil->getDateFromTime($fromTime),
                    $this->dateUtil->getDateFromTime($toTime)
                );
            }
        }
    }

    /**
     * @throws UserGroupAssignmentException
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function assignObjectToUserGroups(
        string $objectType,
        int|string $objectId,
        array $addUserGroups,
        array $removeUserGroups,
        array $addDynamicUserGroups
    ): void {
        $filteredUserGroups = $this->userGroupHandler->getFilteredUserGroups();
        $this->setUserGroups($filteredUserGroups, $objectType, $objectId, $addUserGroups, $removeUserGroups);

        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === true) {
            $this->setDynamicGroups($objectType, $objectId, $addDynamicUserGroups);
        } else {
            $this->setDefaultGroups($filteredUserGroups, $objectType, $objectId);
        }

        $this->userGroupHandler->unsetUserGroupsForObject();
    }
}
