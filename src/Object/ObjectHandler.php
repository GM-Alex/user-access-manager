<?php

declare(strict_types=1);

namespace UserAccessManager\Object;

use Exception;
use UserAccessManager\ObjectMembership\Exception\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use WP_Post;
use WP_Post_Type;
use WP_Taxonomy;
use WP_Term;
use WP_User;

class ObjectHandler
{
    public const GENERAL_ROLE_OBJECT_TYPE = '_role_';
    public const GENERAL_USER_OBJECT_TYPE = '_user_';
    public const GENERAL_POST_OBJECT_TYPE = '_post_';
    public const GENERAL_TERM_OBJECT_TYPE = '_term_';
    public const ATTACHMENT_OBJECT_TYPE = 'attachment';
    public const POST_OBJECT_TYPE = 'post';
    public const PAGE_OBJECT_TYPE = 'page';
    public const POST_FORMAT_TYPE = 'post_format';

    private ?array $postTypes = null;
    /** @var WP_Taxonomy[] */
    private ?array $taxonomies = null;
    /** @var WP_User[] */
    private array $users = [];
    /** @var WP_Post[] */
    private array $posts = [];
    /** @var WP_Term[] */
    private array $terms = [];
    private ?array $objectMembershipHandlers = null;
    private ?array $objectTypes = null;
    private ?array $allObjectTypesMap = null;
    private ?array $allObjectTypes = null;
    private array $validObjectTypes = [];

    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private ObjectMembershipHandlerFactory $membershipHandlerFactory
    ) {
    }

    public function getPostTypes(): array
    {
        if ($this->postTypes === null) {
            $this->postTypes = $this->wordpress->getPostTypes(['public' => true]);
        }

        return $this->postTypes;
    }

    public function getTaxonomies(): array
    {
        if ($this->taxonomies === null) {
            $this->taxonomies = $this->wordpress->getTaxonomies(['public' => true]);
        }

        return $this->taxonomies;
    }

    public function getUser(int|string|null $id): WP_User|bool
    {
        if (isset($this->users[$id]) === false) {
            $this->users[$id] = $this->wordpress->getUserData($id);
        }

        return $this->users[$id];
    }

    public function getPost(int|string|null $id): bool|WP_Post
    {
        if (isset($this->posts[$id]) === false) {
            $post = $this->wordpress->getPost($id);
            $this->posts[$id] = ($post instanceof WP_Post) ? $post : false;
        }

        return $this->posts[$id];
    }

    public function getTerm(int|string|null $id, string $taxonomy = ''): WP_Term|bool
    {
        $fullId = $id . '|' . $taxonomy;

        if (isset($this->terms[$fullId]) === false) {
            $term = $this->wordpress->getTerm($id, $taxonomy);
            $this->terms[$fullId] = ($term instanceof WP_Term) ? $term : false;
        }

        return $this->terms[$fullId];
    }

    private function resetDerivedObjectTypeCaches(): void
    {
        $this->objectTypes = null;
        $this->allObjectTypes = null;
        $this->allObjectTypesMap = null;
        $this->validObjectTypes = [];
    }

    /**
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     */
    public function registeredPostType(string $postType, WP_Post_Type $arguments): void
    {
        if ($arguments->public === true) {
            $this->postTypes = $this->getPostTypes();
            $this->postTypes[$postType] = $postType;
            $this->resetDerivedObjectTypeCaches();
        }
    }

    public function registeredTaxonomy(string $taxonomy, array|string|null $objectType, array $arguments): void
    {
        if ((bool) $arguments['public'] === true) {
            $this->taxonomies = $this->getTaxonomies();
            $this->taxonomies[$taxonomy] = $taxonomy;
            $this->resetDerivedObjectTypeCaches();
        }
    }

    public function isPostType(string $type): bool
    {
        return isset($this->getPostTypes()[$type]);
    }

    public function isTaxonomy(string $taxonomy): bool
    {
        return in_array($taxonomy, $this->getTaxonomies());
    }

    public function getObjectTypes(): array
    {
        if ($this->objectTypes === null) {
            $this->objectTypes = array_merge(
                $this->getPostTypes(),
                $this->getTaxonomies()
            );
        }

        return $this->objectTypes;
    }

    /**
     * @throws Exception
     */
    private function getAllObjectsTypesMap(): array
    {
        if ($this->allObjectTypesMap === null) {
            $this->allObjectTypesMap = [];

            foreach ($this->getObjectMembershipHandlers() as $membershipHandler) {
                $handledObjects = $membershipHandler->getHandledObjects();

                if ($handledObjects === []) {
                    continue;
                }

                $this->allObjectTypesMap = array_merge($this->allObjectTypesMap, array_combine(
                    $handledObjects,
                    $this->php->arrayFill(0, count($handledObjects), $membershipHandler->getGeneralObjectType())
                ));
            }
        }

        return $this->allObjectTypesMap;
    }

    /**
     * @throws Exception
     */
    public function getAllObjectTypes(): array
    {
        if ($this->allObjectTypes === null) {
            $objectTypes = array_keys($this->getAllObjectsTypesMap());
            $this->allObjectTypes = array_combine($objectTypes, $objectTypes);
        }

        return $this->allObjectTypes;
    }

    /**
     * @throws Exception
     */
    public function getGeneralObjectType(?string $objectType): ?string
    {
        return $this->getAllObjectsTypesMap()[$objectType] ?? null;
    }

    /**
     * @throws Exception
     */
    public function isValidObjectType(?string $objectType): bool
    {
        if (isset($this->validObjectTypes[$objectType]) === false) {
            $this->validObjectTypes[$objectType] = isset($this->getAllObjectTypes()[$objectType]);
        }

        return $this->validObjectTypes[$objectType];
    }

    /**
     * @return ObjectMembershipHandler[]
     * @throws Exception
     */
    private function getObjectMembershipHandlers(): array
    {
        if ($this->objectMembershipHandlers === null) {
            $handlersByGeneralObjectType = [];

            foreach ([
                $this->membershipHandlerFactory->createRoleMembershipHandler(),
                $this->membershipHandlerFactory->createUserMembershipHandler($this),
                $this->membershipHandlerFactory->createTermMembershipHandler($this),
                $this->membershipHandlerFactory->createPostMembershipHandler($this)
            ] as $membershipHandler) {
                $handlersByGeneralObjectType[$membershipHandler->getGeneralObjectType()] = $membershipHandler;
            }

            // Published before the filter runs, so a callback that re-enters does not rebuild the handlers.
            $this->objectMembershipHandlers = $handlersByGeneralObjectType;
            $this->objectMembershipHandlers = $this->wordpress->applyFilters(
                'uam_register_object_membership_handler',
                $handlersByGeneralObjectType
            );
        }

        return $this->objectMembershipHandlers;
    }

    /**
     * @throws MissingObjectMembershipHandlerException
     * @throws Exception
     */
    public function getObjectMembershipHandler(?string $objectType): ObjectMembershipHandler
    {
        $objectMembershipHandlers = $this->getObjectMembershipHandlers();
        $generalObjectType = $this->getGeneralObjectType($objectType);

        if (isset($objectMembershipHandlers[$generalObjectType]) === false) {
            throw new MissingObjectMembershipHandlerException("Missing membership handler for '$objectType'.");
        }

        return $objectMembershipHandlers[$generalObjectType];
    }
}
