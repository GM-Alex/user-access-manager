<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Database\Database;
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

    public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string|null $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        $assignmentInformation = null;
        $recursiveMembership = [];
        $user = $this->objectHandler->getUser($objectId);

        if ($user !== false) {
            $capabilitiesTable = $this->database->getCapabilitiesTable();
            $capabilities = (isset($user->{$capabilitiesTable}) === true) ? $user->{$capabilitiesTable} : [];

            if (is_array($capabilities) === true && count($capabilities) > 0) {
                $assignedRoles = $userGroup->getAssignedObjects(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

                $recursiveRoles = array_intersect(
                    array_keys($capabilities),
                    array_keys($assignedRoles)
                );

                if (count($recursiveRoles) > 0) {
                    $recursiveMembership[ObjectHandler::GENERAL_ROLE_OBJECT_TYPE] = array_combine(
                        $recursiveRoles,
                        $this->php->arrayFill(
                            0,
                            count($recursiveRoles),
                            $this->assignmentInformationFactory->createAssignmentInformation(
                                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                            )
                        )
                    );
                }
            }
        }

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
    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, $objectType = null): array
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
