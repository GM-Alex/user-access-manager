<?php
/**
 * UserGroupAssignmentHandler.php
 *
 * The UserGroupAssignmentHandler class file.
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

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\User\UserHandler;
use UserAccessManager\Util\DateUtil;

/**
 * Class UserGroupAssignmentHandler
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupAssignmentHandler
{
    /**
     * @var DateUtil
     */
    protected $dateUtil;

    /**
     * @var UserHandler
     */
    protected $userHandler;

    /**
     * @var UserGroupHandler
     */
    protected $userGroupHandler;

    /**
     * @var UserGroupFactory
     */
    protected $userGroupFactory;

    /**
     * UserGroupAssignmentHandler constructor.
     * @param DateUtil $dateUtil
     * @param UserHandler $userHandler
     * @param UserGroupHandler $userGroupHandler
     * @param UserGroupFactory $userGroupFactory
     */
    public function __construct(
        DateUtil $dateUtil,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        UserGroupFactory $userGroupFactory
    ) {
        $this->dateUtil = $dateUtil;
        $this->userHandler = $userHandler;
        $this->userGroupHandler = $userGroupHandler;
        $this->userGroupFactory = $userGroupFactory;
    }

    /**
     * Processes the date parameter.
     * @param array $data
     * @param string $name
     * @return null|string
     */
    private function getDateParameter(array $data, string $name): ?string
    {
        $isValid = isset($data[$name]['date']) === true && isset($data[$name]['time']) === true
            && (string) $data[$name]['date'] !== '' && (string) $data[$name]['time'] !== '';

        return ($isValid === true) ? (string) $data[$name]['date'] . 'T' . $data[$name]['time'] : null;
    }

    /**
     * Updates the user groups for the given object.
     * @param AbstractUserGroup[] $filteredUserGroups
     * @param string $objectType
     * @param int|string $objectId
     * @param array $addUserGroups
     * @param array $removeUserGroups
     * @throws Exception
     */
    private function setUserGroups(
        array $filteredUserGroups,
        string $objectType,
        $objectId,
        array $addUserGroups,
        array $removeUserGroups
    ) {
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
     * Sets the dynamic user groups for the given object.
     * @param string $objectType
     * @param int|string $objectId
     * @param array $addDynamicUserGroups
     * @throws UserGroupAssignmentException
     * @throws UserGroupTypeException
     */
    private function setDynamicGroups(string $objectType, $objectId, array $addDynamicUserGroups)
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
     * Sets the default user groups for the given object.
     * @param AbstractUserGroup[] $filteredUserGroups
     * @param string $objectType
     * @param int|string $objectId
     * @throws UserGroupTypeException
     * @throws Exception
     */
    private function setDefaultGroups(array $filteredUserGroups, string $objectType, $objectId)
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
     * Saves the object data to the database.
     * @param string $objectType
     * @param int|string $objectId
     * @param array $addUserGroups
     * @param array $removeUserGroups
     * @param array $addDynamicUserGroups
     * @throws UserGroupAssignmentException
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function assignObjectToUserGroups(
        string $objectType,
        $objectId,
        array $addUserGroups,
        array $removeUserGroups,
        array $addDynamicUserGroups
    ) {
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
