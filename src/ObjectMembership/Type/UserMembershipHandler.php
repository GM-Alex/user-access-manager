<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership\Type;

use Exception;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Php;

class UserMembershipHandler extends ObjectMembershipHandler
{
    protected ?string $generalObjectType = ObjectHandler::GENERAL_USER_OBJECT_TYPE;

    public function __construct(
        AssignmentInformationFactory $assignmentInformationFactory,
        private Php $php,
        private Database $database,
        private ObjectHandler $objectHandler
    ) {
        parent::__construct($assignmentInformationFactory);
    }

    public function getObjectName(int|string|null $objectId, string &$typeName = ''): int|string
    {
        $typeName = $this->generalObjectType;
        $user = $this->objectHandler->getUser($objectId);
        return ($user !== false) ? $user->display_name : $objectId;
    }

    private function getUserCapabilities(int|string|null $objectId): array
    {
        $user = $this->objectHandler->getUser($objectId);

        if ($user === false) {
            return [];
        }

        $capabilitiesTable = $this->database->getCapabilitiesTable();
        $capabilities = $user->{$capabilitiesTable} ?? [];

        return is_array($capabilities) === true ? $capabilities : [];
    }

    private function getRecursiveRoleMembership(AbstractUserGroup $userGroup, int|string|null $objectId): array
    {
        $capabilities = $this->getUserCapabilities($objectId);

        if ($capabilities === []) {
            return [];
        }

        $recursiveRoles = array_intersect(
            array_keys($capabilities),
            array_keys($userGroup->getAssignedObjects(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE))
        );

        if ($recursiveRoles === []) {
            return [];
        }

        return [
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => array_combine(
                $recursiveRoles,
                $this->php->arrayFill(
                    0,
                    count($recursiveRoles),
                    $this->assignmentInformationFactory->createAssignmentInformation(
                        ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                    )
                )
            )
        ];
    }

    public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string|null $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        $assignmentInformation = null;
        $recursiveMembership = $this->getRecursiveRoleMembership($userGroup, $objectId);

        $isMember = $userGroup->isObjectAssignedToGroup(
            $this->generalObjectType,
            $objectId,
            $assignmentInformation
        );

        return $this->checkAccessWithRecursiveMembership($isMember, $recursiveMembership, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, ?string $objectType = null): array
    {
        $users = [];

        $databaseUsers = (array) $this->database->getResults(
            "SELECT ID, user_nicename
                FROM {$this->database->getUsersTable()}"
        );

        foreach ($databaseUsers as $user) {
            if ($userGroup->isObjectMember($this->generalObjectType, $user->ID) === true) {
                $users[$user->ID] = $this->generalObjectType;
            }
        }

        return $users;
    }
}
