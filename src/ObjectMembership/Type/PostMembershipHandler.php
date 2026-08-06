<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership\Type;

use Exception;
use UserAccessManager\ObjectMembership\ObjectMembershipWithMapHandler;
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
        return $this->getHandledObjectsIncluding($this->objectHandler->getPostTypes());
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
        $termsOfPost = $this->objectMapHandler->getPostTermMap()[$objectId] ?? [];

        foreach (array_keys($termsOfPost) as $termId) {
            if ($userGroup->isTermMember($termId, $termAssignmentInformation) === true) {
                $recursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$termId] = $termAssignmentInformation;
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
        $objectType ??= $this->generalObjectType;
        $posts = $this->getFullObjectsByMap($userGroup, $lockRecursive, $objectType);

        if ($lockRecursive === false) {
            return $posts;
        }

        $termsPostMap = $this->objectMapHandler->getTermPostMap();

        foreach (array_keys($userGroup->getFullTerms()) as $termId) {
            $postsOfTerm = $termsPostMap[$termId] ?? null;

            if ($postsOfTerm === null) {
                continue;
            }

            if ($objectType !== $this->generalObjectType) {
                $postsOfTerm = array_filter($postsOfTerm, fn($type) => $type === $objectType);
            }

            $posts += $postsOfTerm;
        }

        return $posts;
    }
}
