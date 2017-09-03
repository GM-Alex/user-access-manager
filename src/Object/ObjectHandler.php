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
namespace UserAccessManager\Object;

use UserAccessManager\Cache\Cache;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ObjectHandler
 *
 * @package UserAccessManager\ObjectHandler
 */
class ObjectHandler
{
    const TREE_MAP_PARENTS = 'PARENT';
    const TREE_MAP_CHILDREN = 'CHILDREN';
    const GENERAL_ROLE_OBJECT_TYPE = '_role_';
    const GENERAL_USER_OBJECT_TYPE = '_user_';
    const GENERAL_POST_OBJECT_TYPE = '_post_';
    const GENERAL_TERM_OBJECT_TYPE = '_term_';
    const ATTACHMENT_OBJECT_TYPE = 'attachment';
    const POST_OBJECT_TYPE = 'post';
    const PAGE_OBJECT_TYPE = 'page';
    const POST_FORMAT_TYPE = 'post_format';
    const POST_TREE_MAP_CACHE_KEY = 'uamPostTreeMap';
    const TERM_TREE_MAP_CACHE_KEY = 'uamTermTreeMap';
    const TERM_POST_MAP_CACHE_KEY = 'uamTermPostMap';
    const POST_TERM_MAP_CACHE_KEY = 'uamPostTermMap';

    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Cache
     */
    private $cache;

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
     * @var \WP_User
     */
    private $users = null;

    /**
     * @var \WP_Post[]
     */
    private $posts = null;

    /**
     * @var \WP_Term[]
     */
    private $terms = null;

    /**
     * @var null|array
     */
    private $termPostMap = null;

    /**
     * @var null|array
     */
    private $postTermMap = null;

    /**
     * @var null|array
     */
    private $termTreeMap = null;

    /**
     * @var null|array
     */
    private $postTreeMap = null;

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
     *
     * @param Php                            $php
     * @param Wordpress                      $wordpress
     * @param Database                       $database
     * @param Cache                          $cache
     * @param ObjectMembershipHandlerFactory $membershipHandlerFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        Cache $cache,
        ObjectMembershipHandlerFactory $membershipHandlerFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->cache = $cache;
        $this->membershipHandlerFactory = $membershipHandlerFactory;
    }

    /**
     * Returns all post types.
     *
     * @return array
     */
    public function getPostTypes()
    {
        if ($this->postTypes === null) {
            $this->postTypes = $this->wordpress->getPostTypes(['public' => true]);
        }

        return $this->postTypes;
    }

    /**
     * Returns the taxonomies.
     *
     * @return array
     */
    public function getTaxonomies()
    {
        if ($this->taxonomies === null) {
            $this->taxonomies = $this->wordpress->getTaxonomies(['public' => true]);
        }

        return $this->taxonomies;
    }

    /**
     * Returns a user.
     *
     * @param int|string $id The user id.
     *
     * @return \WP_User|false
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
     *
     * @param int $id The post id.
     *
     * @return \WP_Post|false
     */
    public function getPost($id)
    {
        if (isset($this->posts[$id]) === false) {
            $post = $this->wordpress->getPost($id);
            $this->posts[$id] = ($post instanceof \WP_Post) ? $post : false;
        }

        return $this->posts[$id];
    }

    /**
     * Returns a term.
     *
     * @param int    $id       The term id.
     * @param string $taxonomy The taxonomy.
     *
     * @return false|\WP_Term
     */
    public function getTerm($id, $taxonomy = '')
    {
        $fullId = $id.'|'.$taxonomy;

        if (isset($this->terms[$fullId]) === false) {
            $term = $this->wordpress->getTerm($id, $taxonomy);
            $this->terms[$fullId] = ($term instanceof \WP_Term) ? $term : false;
        }

        return $this->terms[$fullId];
    }

    /**
     * Resolves all tree map elements
     *
     * @param array $map
     * @param array $subMap
     * @param array $processed
     *
     * @return array
     */
    private function processTreeMapElements(array &$map, array $subMap = null, array &$processed = [])
    {
        $processMap = ($subMap === null) ? $map : $subMap;

        foreach ($processMap as $id => $subIds) {
            foreach ($subIds as $subId => $type) {
                if (isset($map[$subId]) === true
                    && (isset($processed[$id]) === false || $processed[$id] !== $subId)
                ) {
                    $map[$id] += $this->processTreeMapElements($map, [$subId => $map[$subId]], $processed)[$subId];
                    $processed[$id] = $subId;
                }
            }
        }

        return $map;
    }

    /**
     * Returns the tree map for the query.
     *
     * @param string $select
     * @param string $generalType
     *
     * @return array
     */
    private function getTreeMap($select, $generalType)
    {
        $treeMap = [
            self::TREE_MAP_CHILDREN => [
                $generalType => []
            ],
            self::TREE_MAP_PARENTS => [
                $generalType => []
            ]
        ];
        $results = (array)$this->database->getResults($select);

        foreach ($results as $result) {
            $treeMap[self::TREE_MAP_CHILDREN][$generalType][$result->parentId][$result->id] = $result->type;
            $treeMap[self::TREE_MAP_CHILDREN][$result->type][$result->parentId][$result->id] = $result->type;
            $treeMap[self::TREE_MAP_PARENTS][$generalType][$result->id][$result->parentId] = $result->type;
            $treeMap[self::TREE_MAP_PARENTS][$result->type][$result->id][$result->parentId] = $result->type;
        }

        //Process elements
        foreach ($treeMap as $mapType => $mayTypeMap) {
            foreach ($mayTypeMap as $objectType => $map) {
                $treeMap[$mapType][$objectType] = $this->processTreeMapElements($map);
            }
        }

        return $treeMap;
    }

    /**
     * Checks if a cache key exists and returns the map.
     *
     * @param string $cacheKey
     * @param string $generalType
     * @param string $query
     *
     * @return array
     */
    private function getCachedTreeMap($cacheKey, $generalType, $query)
    {
        $map = $this->cache->get($cacheKey);

        if ($map === null) {
            $map = $this->getTreeMap($query, $generalType);
            $this->cache->add($cacheKey, $map);
        }


        return $map;
    }

    /**
     * Returns the post tree map.
     *
     * @return array
     */
    public function getPostTreeMap()
    {
        if ($this->postTreeMap === null) {
            $this->postTreeMap = $this->getCachedTreeMap(
                self::POST_TREE_MAP_CACHE_KEY,
                self::GENERAL_POST_OBJECT_TYPE,
                "SELECT ID AS id, post_parent AS parentId, post_type AS type 
                FROM {$this->database->getPostsTable()}
                  WHERE post_parent != 0 AND post_type != 'revision'"
            );
        }

        return $this->postTreeMap;
    }

    /**
     * Returns the term tree map.
     *
     * @return array
     */
    public function getTermTreeMap()
    {
        if ($this->termTreeMap === null) {
            $this->termTreeMap = $this->getCachedTreeMap(
                self::TERM_TREE_MAP_CACHE_KEY,
                self::GENERAL_TERM_OBJECT_TYPE,
                "SELECT term_id AS id, parent AS parentId, taxonomy AS type
                FROM {$this->database->getTermTaxonomyTable()}
                  WHERE parent != 0"
            );
        }

        return $this->termTreeMap;
    }

    /**
     * Returns the cached map.
     *
     * @param string $cacheKey
     * @param string $query
     *
     * @return array
     */
    private function getCachedMap($cacheKey, $query)
    {
        $map = $this->cache->get($cacheKey);

        if ($map === null) {
            $map = [];
            $results = (array)$this->database->getResults($query);

            foreach ($results as $result) {
                $map[$result->parentId][$result->objectId] = $result->type;
            }

            $this->cache->add($cacheKey, $map);
        }

        return $map;
    }

    /**
     * Returns the term post map.
     *
     * @return array
     */
    public function getTermPostMap()
    {
        if ($this->termPostMap === null) {
            $select = "
                SELECT tr.object_id AS objectId, tt.term_id AS parentId, p.post_type AS type
                FROM {$this->database->getTermRelationshipsTable()} AS tr
                  LEFT JOIN {$this->database->getPostsTable()} AS p
                   ON (tr.object_id = p.ID)
                  LEFT JOIN {$this->database->getTermTaxonomyTable()} AS tt
                    ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

            $this->termPostMap = $this->getCachedMap(self::TERM_POST_MAP_CACHE_KEY, $select);
        }

        return $this->termPostMap;
    }

    /**
     * Returns the post term map.
     *
     * @return array
     */
    public function getPostTermMap()
    {
        if ($this->postTermMap === null) {
            $select = "
                SELECT tr.object_id AS parentId, tt.term_id AS objectId, tt.taxonomy AS type
                FROM {$this->database->getTermRelationshipsTable()} AS tr 
                  LEFT JOIN {$this->database->getTermTaxonomyTable()} AS tt
                    ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

            $this->postTermMap = $this->getCachedMap(self::POST_TERM_MAP_CACHE_KEY, $select);
        }

        return $this->postTermMap;
    }

    /**
     * Used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     *
     * @param string        $postType  The string for the new post_type
     * @param \WP_Post_Type $arguments The array of arguments used to create the post_type
     */
    public function registeredPostType($postType, \WP_Post_Type $arguments)
    {
        if ((bool)$arguments->public === true) {
            $this->postTypes = $this->getPostTypes();
            $this->postTypes[$postType] = $postType;
            $this->objectTypes = null;
            $this->allObjectTypes = null;
            $this->validObjectTypes = [];
        }
    }

    /**
     * Adds an custom taxonomy.
     *
     * @param string $taxonomy
     * @param string $objectType
     * @param array  $arguments
     */
    public function registeredTaxonomy($taxonomy, $objectType, array $arguments)
    {
        if ((bool)$arguments['public'] === true) {
            $this->taxonomies = $this->getTaxonomies();
            $this->taxonomies[$taxonomy] = $taxonomy;
            $this->objectTypes = null;
            $this->allObjectTypes = null;
            $this->validObjectTypes = [];
        }
    }

    /**
     * Checks if type is postable.
     *
     * @param string $type
     *
     * @return bool
     */
    public function isPostType($type)
    {
        $postableTypes = $this->getPostTypes();
        return isset($postableTypes[$type]);
    }

    /**
     * Checks if the taxonomy is a valid one.
     *
     * @param string $taxonomy
     *
     * @return bool
     */
    public function isTaxonomy($taxonomy)
    {
        $taxonomies = $this->getTaxonomies();
        return in_array($taxonomy, $taxonomies);
    }

    /**
     * Returns the predefined object types.
     *
     * @return array
     */
    public function getObjectTypes()
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
     *
     * @return array
     */
    private function getAllObjectsTypesMap()
    {
        if ($this->allObjectTypesMap === null) {
            $this->allObjectTypesMap = [];
            $objectHandlers = $this->getObjectMembershipHandlers();

            foreach ($objectHandlers as $objectHandler) {
                $handledObjects = $objectHandler->getHandledObjects();
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
     *
     * @return array
     */
    public function getAllObjectTypes()
    {
        if ($this->allObjectTypes === null) {
            $objectTypes = array_keys($this->getAllObjectsTypesMap());
            $this->allObjectTypes = array_combine($objectTypes, $objectTypes);
        }

        return $this->allObjectTypes;
    }

    /**
     * Returns the general object type.
     *
     * @param string $objectType
     *
     * @return string
     */
    public function getGeneralObjectType($objectType)
    {
        $objectsTypeMap = $this->getAllObjectsTypesMap();
        return (isset($objectsTypeMap[$objectType]) === true) ? $objectsTypeMap[$objectType] : null;
    }

    /**
     * Checks if the object type is a valid one.
     *
     * @param string $objectType The object type to check.
     *
     * @return bool
     */
    public function isValidObjectType($objectType)
    {
        if (isset($this->validObjectTypes[$objectType]) === false) {
            $objectTypesMap = $this->getAllObjectTypes();
            $this->validObjectTypes[$objectType] = isset($objectTypesMap[$objectType]);
        }

        return $this->validObjectTypes[$objectType];
    }

    /**
     * Returns the object membership handlers.
     *
     * @return ObjectMembershipHandler[]
     */
    private function getObjectMembershipHandlers()
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
     *
     * @param string $objectType
     *
     * @return ObjectMembershipHandler
     *
     * @throws MissingObjectMembershipHandlerException
     */
    public function getObjectMembershipHandler($objectType)
    {
        $objectMembershipHandlers = $this->getObjectMembershipHandlers();
        $generalObjectType = $this->getGeneralObjectType($objectType);

        if (isset($objectMembershipHandlers[$generalObjectType]) === false) {
            throw new MissingObjectMembershipHandlerException("Missing membership handler for '{$objectType}'.");
        }

        return $objectMembershipHandlers[$generalObjectType];
    }
}
