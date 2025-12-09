<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Wordpress;

class RoleMembershipHandler extends ObjectMembershipHandler
{
    protected ?string $generalObjectType = ObjectHandler::GENERAL_ROLE_OBJECT_TYPE;

    /**
     * @throws Exception
     */
    public function __construct(
        AssignmentInformationFactory $assignmentInformationFactory,
        private Wordpress $wordpress
    ) {
        parent::__construct($assignmentInformationFactory);
    }

    public function getObjectName(int|string $objectId, string &$typeName = ''): int|string
    {
        $typeName = $this->generalObjectType;
        $roles = $this->wordpress->getRoles()->role_names;
        return (isset($roles[$objectId]) === true) ? $roles[$objectId] : $objectId;
    }

    public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        $isMember = $userGroup->isObjectAssignedToGroup(
            $this->generalObjectType,
            $objectId,
            $assignmentInformation
        );
        $assignmentInformation = ($isMember === true) ? $assignmentInformation : null;

        return $isMember;
    }

    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, $objectType = null): array
    {
        $objectType = ($objectType === null) ? $this->generalObjectType : $objectType;

        return $this->getSimpleAssignedObjects($userGroup, $objectType);
    }
}
