<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Wordpress;

class TermMembershipHandler extends ObjectMembershipWithMapHandler
{
    protected ?string $generalObjectType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;

    public function __construct(
        AssignmentInformationFactory $assignmentInformationFactory,
        private Wordpress $wordpress,
        private ObjectHandler $objectHandler,
        private ObjectMapHandler $objectMapHandler
    ) {
        parent::__construct($assignmentInformationFactory);
    }

    public function getObjectName(int|string $objectId, string &$typeName = ''): int|string
    {
        $term = $this->objectHandler->getTerm($objectId);

        if ($term !== false) {
            $taxonomy = $this->wordpress->getTaxonomy($term->taxonomy);
            $typeName = ($taxonomy !== false) ? $taxonomy->labels->name : $typeName;
            return $term->name;
        }

        return $objectId;
    }

    public function getHandledObjects(): array
    {
        $keys = array_keys($this->objectHandler->getTaxonomies());
        return array_merge(parent::getHandledObjects(), array_combine($keys, $keys));
    }

    protected function getMap(): array
    {
        return $this->objectMapHandler->getTermTreeMap();
    }

    /**
     * @throws Exception
     */
    public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        return $this->getMembershipByMap($userGroup, $lockRecursive, $objectId, $assignmentInformation);
    }

    /**
     * @throws Exception
     */
    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, $objectType = null): array
    {
        $objectType = ($objectType === null) ? $this->generalObjectType : $objectType;
        return $this->getFullObjectsByMap($userGroup, $lockRecursive, $objectType);
    }
}
