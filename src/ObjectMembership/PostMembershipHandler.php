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

class PostMembershipHandler extends ObjectMembershipWithMapHandler
{
    protected ?string $generalObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;

    public function __construct(
        AssignmentInformationFactory $assignmentInformationFactory,
        private Wordpress $wordpress,
        private ObjectHandler $objectHandler,
        private ObjectMapHandler $objectMapHandler
    ) {
        parent::__construct($assignmentInformationFactory);
    }

    public function getObjectName(int|string|null $objectId, string &$typeName = ''): int|string
    {
        $post = $this->objectHandler->getPost($objectId);

        if ($post !== false) {
            $postTypeObject = $this->wordpress->getPostTypeObject($post->post_type);
            $typeName = ($postTypeObject !== null) ? $postTypeObject->labels->name : $typeName;
            return $post->post_title;
        }

        return $objectId;
    }

    public function getHandledObjects(): array
    {
        $keys = array_keys($this->objectHandler->getPostTypes());
        return array_merge(parent::getHandledObjects(), array_combine($keys, $keys));
    }

    protected function getMap(): array
    {
        return $this->objectMapHandler->getPostTreeMap();
    }

    /**
     * @throws Exception
     */
    private function assignRecursiveMembershipByTerm(
        AbstractUserGroup $userGroup,
        int|string|null $objectId,
        array &$recursiveMembership
    ): void {
        $postTermMap = $this->objectMapHandler->getPostTermMap();

        if (isset($postTermMap[$objectId]) === true) {
            foreach ($postTermMap[$objectId] as $termId => $type) {
                if ($userGroup->isTermMember($termId, $rmAssignmentInformation) === true) {
                    $recursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$termId] = $rmAssignmentInformation;
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        int|string|null $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        $isMember = $this->getMembershipByMap($userGroup, $lockRecursive, $objectId, $assignmentInformation);

        if ($lockRecursive === true) {
            $recursiveMembership = ($assignmentInformation !== null) ?
                $assignmentInformation->getRecursiveMembership() : [];

            $this->assignRecursiveMembershipByTerm($userGroup, $objectId, $recursiveMembership);
            $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
            $isMember = $isMember || count($recursiveMembership) > 0;
        }

        return $isMember;
    }

    /**
     * @throws Exception
     */
    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, ?string $objectType = null): array
    {
        $objectType = ($objectType === null) ? $this->generalObjectType : $objectType;
        $posts = $this->getFullObjectsByMap($userGroup, $lockRecursive, $objectType);

        if ($lockRecursive === true) {
            $termsPostMap = $this->objectMapHandler->getTermPostMap();
            $terms = $userGroup->getFullTerms();

            foreach ($terms as $termId => $term) {
                if (isset($termsPostMap[$termId]) === true) {
                    $map = $termsPostMap[$termId];

                    if ($objectType !== $this->generalObjectType) {
                        $map = array_filter(
                            $map,
                            function ($element) use ($objectType) {
                                return ($element === $objectType);
                            }
                        );
                    }

                    $posts += $map;
                }
            }
        }

        return $posts;
    }
}
