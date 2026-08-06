<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership\Type;

use Exception;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
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

    public function getObjectName(int|string|null $objectId, string &$typeName = ''): int|string
    {
        $typeName = $this->generalObjectType;
        return $this->wordpress->getRoles()->role_names[$objectId] ?? $objectId;
    }

    public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string|null $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        $isMember = $userGroup->isObjectAssignedToGroup(
            $this->generalObjectType,
            $objectId,
            $assignmentInformation
        );

        if ($isMember === false) {
            $assignmentInformation = null;
        }

        return $isMember;
    }

    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, ?string $objectType = null): array
    {
        return $this->getSimpleAssignedObjects($userGroup, $objectType ?? $this->generalObjectType);
    }
}
