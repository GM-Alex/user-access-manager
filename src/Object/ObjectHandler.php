<?php
/**
 * ObjectHandler.php
 *
 * The ObjectHandler class file.
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

namespace UserAccessManager\Object;

use Exception;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use WP_Post;
use WP_Post_Type;
use WP_Taxonomy;
use WP_Term;
use WP_User;

/**
 * Class ObjectHandler
 *
 * @package UserAccessManager\ObjectHandler
 */
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
    private ?array $users = null;
    /** @var WP_Post[] */
    private ?array $posts = null;
    /** @var WP_Term[] */
    private ?array $terms = null;
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

    public function getPostTypes(): ?array
    {
        if ($this->postTypes === null) {
            $this->postTypes = $this->wordpress->getPostTypes(['public' => true]);
        }

        return $this->postTypes;
    }

    public function getTaxonomies(): ?array
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

    public function getTerm(int|string $id, string $taxonomy = ''): WP_Term|bool
    {
        $fullId = $id . '|' . $taxonomy;

        if (isset($this->terms[$fullId]) === false) {
            $term = $this->wordpress->getTerm($id, $taxonomy);
            $this->terms[$fullId] = ($term instanceof WP_Term) ? $term : false;
        }

        return $this->terms[$fullId];
    }

    /**
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     */
    public function registeredPostType(string $postType, WP_Post_Type $arguments): void
    {
        if ($arguments->public === true) {
            $this->postTypes = $this->getPostTypes();
            $this->postTypes[$postType] = $postType;
            $this->objectTypes = null;
            $this->allObjectTypes = null;
            $this->allObjectTypesMap = null;
            $this->validObjectTypes = [];
        }
    }

    public function registeredTaxonomy(string $taxonomy, array|string|null $objectType, array $arguments): void
    {
        if ((bool) $arguments['public'] === true) {
            $this->taxonomies = $this->getTaxonomies();
            $this->taxonomies[$taxonomy] = $taxonomy;
            $this->objectTypes = null;
            $this->allObjectTypes = null;
            $this->allObjectTypesMap = null;
            $this->validObjectTypes = [];
        }
    }

    public function isPostType(string $type): bool
    {
        $postableTypes = $this->getPostTypes();
        return isset($postableTypes[$type]);
    }

    public function isTaxonomy(string $taxonomy): bool
    {
        $taxonomies = $this->getTaxonomies();
        return in_array($taxonomy, $taxonomies);
    }

    public function getObjectTypes(): ?array
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
    private function getAllObjectsTypesMap(): ?array
    {
        if ($this->allObjectTypesMap === null) {
            $this->allObjectTypesMap = [];
            $objectHandlers = $this->getObjectMembershipHandlers();

            foreach ($objectHandlers as $objectHandler) {
                $handledObjects = $objectHandler->getHandledObjects();

                if ($handledObjects === []) {
                    continue;
                }

                $handledObjectsMap = array_combine(
                    $handledObjects,
                    $this->php->arrayFill(0, count($handledObjects), $objectHandler->getGeneralObjectType())
                );

                $this->allObjectTypesMap = array_merge($this->allObjectTypesMap, $handledObjectsMap);
            }
        }

        return $this->allObjectTypesMap;
    }

    /**
     * @throws Exception
     */
    public function getAllObjectTypes(): ?array
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
        $objectsTypeMap = $this->getAllObjectsTypesMap();
        return (isset($objectsTypeMap[$objectType]) === true) ? $objectsTypeMap[$objectType] : null;
    }

    /**
     * @throws Exception
     */
    public function isValidObjectType(?string $objectType): bool
    {
        if (isset($this->validObjectTypes[$objectType]) === false) {
            $objectTypesMap = $this->getAllObjectTypes();
            $this->validObjectTypes[$objectType] = isset($objectTypesMap[$objectType]);
        }

        return $this->validObjectTypes[$objectType];
    }

    /**
     * @throws Exception
     */
    private function getObjectMembershipHandlers(): ?array
    {
        if ($this->objectMembershipHandlers === null) {
            $factory = $this->membershipHandlerFactory;

            $roleMembershipHandler = $factory->createRoleMembershipHandler();
            $userMembershipHandler = $factory->createUserMembershipHandler($this);
            $termMembershipHandler = $factory->createTermMembershipHandler($this);
            $postMembershipHandler = $factory->createPostMembershipHandler($this);

            $this->objectMembershipHandlers = [
                $roleMembershipHandler->getGeneralObjectType() => $roleMembershipHandler,
                $userMembershipHandler->getGeneralObjectType() => $userMembershipHandler,
                $termMembershipHandler->getGeneralObjectType() => $termMembershipHandler,
                $postMembershipHandler->getGeneralObjectType() => $postMembershipHandler
            ];

            $this->objectMembershipHandlers = $this->wordpress->applyFilters(
                'uam_register_object_membership_handler',
                $this->objectMembershipHandlers
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
