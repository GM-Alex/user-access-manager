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
use WP_Term;
use WP_User;

/**
 * Class ObjectHandler
 *
 * @package UserAccessManager\ObjectHandler
 */
class ObjectHandler
{
    const GENERAL_ROLE_OBJECT_TYPE = '_role_';
    const GENERAL_USER_OBJECT_TYPE = '_user_';
    const GENERAL_POST_OBJECT_TYPE = '_post_';
    const GENERAL_TERM_OBJECT_TYPE = '_term_';
    const ATTACHMENT_OBJECT_TYPE = 'attachment';
    const POST_OBJECT_TYPE = 'post';
    const PAGE_OBJECT_TYPE = 'page';
    const POST_FORMAT_TYPE = 'post_format';

    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var ObjectMembershipHandlerFactory
     */
    private $membershipHandlerFactory;

    /**
     * @var null|array
     */
    private $postTypes = null;

    /**
     * @var null|array
     */
    private $taxonomies = null;

    /**
     * @var WP_User
     */
    private $users = null;

    /**
     * @var WP_Post[]
     */
    private $posts = null;

    /**
     * @var WP_Term[]
     */
    private $terms = null;

    /**
     * @var null|array
     */
    private $objectMembershipHandlers = null;

    /**
     * @var null|array
     */
    private $objectTypes = null;

    /**
     * @var null|array
     */
    private $allObjectTypesMap = null;

    /**
     * @var null|array
     */
    private $allObjectTypes = null;

    /**
     * @var array
     */
    private $validObjectTypes = [];

    /**
     * ObjectHandler constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param ObjectMembershipHandlerFactory $membershipHandlerFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        ObjectMembershipHandlerFactory $membershipHandlerFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->membershipHandlerFactory = $membershipHandlerFactory;
    }

    /**
     * Returns all post types.
     * @return array
     */
    public function getPostTypes(): ?array
    {
        if ($this->postTypes === null) {
            $this->postTypes = $this->wordpress->getPostTypes(['public' => true]);
        }

        return $this->postTypes;
    }

    /**
     * Returns the taxonomies.
     * @return array
     */
    public function getTaxonomies(): ?array
    {
        if ($this->taxonomies === null) {
            $this->taxonomies = $this->wordpress->getTaxonomies(['public' => true]);
        }

        return $this->taxonomies;
    }

    /**
     * Returns a user.
     * @param int|string $id The user id.
     * @return WP_User|false
     */
    public function getUser($id)
    {
        if (isset($this->users[$id]) === false) {
            $this->users[$id] = $this->wordpress->getUserData($id);
        }

        return $this->users[$id];
    }

    /**
     * Returns a post.
     * @param int|string $id The post id.
     * @return WP_Post|false
     */
    public function getPost($id)
    {
        if (isset($this->posts[$id]) === false) {
            $post = $this->wordpress->getPost($id);
            $this->posts[$id] = ($post instanceof WP_Post) ? $post : false;
        }

        return $this->posts[$id];
    }

    /**
     * Returns a term.
     * @param int|string $id The term id.
     * @param string $taxonomy The taxonomy.
     * @return false|WP_Term
     */
    public function getTerm($id, $taxonomy = '')
    {
        $fullId = $id . '|' . $taxonomy;

        if (isset($this->terms[$fullId]) === false) {
            $term = $this->wordpress->getTerm($id, $taxonomy);
            $this->terms[$fullId] = ($term instanceof WP_Term) ? $term : false;
        }

        return $this->terms[$fullId];
    }

    /**
     * Used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     * @param string $postType The string for the new post_type
     * @param WP_Post_Type $arguments The array of arguments used to create the post_type
     */
    public function registeredPostType(string $postType, WP_Post_Type $arguments)
    {
        if ((bool) $arguments->public === true) {
            $this->postTypes = $this->getPostTypes();
            $this->postTypes[$postType] = $postType;
            $this->objectTypes = null;
            $this->allObjectTypes = null;
            $this->allObjectTypesMap = null;
            $this->validObjectTypes = [];
        }
    }

    /**
     * Adds an custom taxonomy.
     * @param string $taxonomy
     * @param array|string $objectType
     * @param array $arguments
     */
    public function registeredTaxonomy(string $taxonomy, $objectType, array $arguments)
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

    /**
     * Checks if type is postable.
     * @param string $type
     * @return bool
     */
    public function isPostType(string $type): bool
    {
        $postableTypes = $this->getPostTypes();
        return isset($postableTypes[$type]);
    }

    /**
     * Checks if the taxonomy is a valid one.
     * @param string $taxonomy
     * @return bool
     */
    public function isTaxonomy(string $taxonomy): bool
    {
        $taxonomies = $this->getTaxonomies();
        return in_array($taxonomy, $taxonomies);
    }

    /**
     * Returns the predefined object types.
     * @return array
     */
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
     * Returns the object types map.
     * @return array
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
     * Returns all objects types.
     * @return array
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
     * Returns the general object type.
     * @param string|null $objectType
     * @return string
     * @throws Exception
     */
    public function getGeneralObjectType(?string $objectType): ?string
    {
        $objectsTypeMap = $this->getAllObjectsTypesMap();
        return (isset($objectsTypeMap[$objectType]) === true) ? $objectsTypeMap[$objectType] : null;
    }

    /**
     * Checks if the object type is a valid one.
     * @param string|null $objectType The object type to check.
     * @return bool
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
     * Returns the object membership handlers.
     * @return ObjectMembershipHandler[]
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
     * Returns the membership handler for the given object type.
     * @param null|string $objectType
     * @return ObjectMembershipHandler
     * @throws MissingObjectMembershipHandlerException
     * @throws Exception
     */
    public function getObjectMembershipHandler(?string $objectType): ObjectMembershipHandler
    {
        $objectMembershipHandlers = $this->getObjectMembershipHandlers();
        $generalObjectType = $this->getGeneralObjectType($objectType);

        if (isset($objectMembershipHandlers[$generalObjectType]) === false) {
            throw new MissingObjectMembershipHandlerException("Missing membership handler for '{$objectType}'.");
        }

        return $objectMembershipHandlers[$generalObjectType];
    }
}
