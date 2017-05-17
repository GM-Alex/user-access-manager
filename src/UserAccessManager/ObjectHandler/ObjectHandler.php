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
namespace UserAccessManager\ObjectHandler;

use UserAccessManager\Database\Database;
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

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

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
     * @var array
     */
    private $pluggableObjects = [];

    /**
     * @var null|array
     */
    private $objectTypes = null;

    /**
     * @var null|array
     */
    private $allObjectTypes = null;

    /**
     * @var array
     */
    private $validObjectTypes = [];

    /**
     * Cache constructor.
     *
     * @param Wordpress $wordpress
     * @param Database  $database
     */
    public function __construct(Wordpress $wordpress, Database $database)
    {
        $this->wordpress = $wordpress;
        $this->database = $database;
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
    private function processTreeMapElements(array &$map, array $subMap = null, array $processed = [])
    {
        $processMap = ($subMap === null) ? $map : $subMap;

        foreach ($processMap as $id => $subIds) {
            foreach ($subIds as $subId => $type) {
                if (isset($map[$subId]) === true && isset($processed[$subId]) !== $id) {
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
            if (isset($treeMap[self::TREE_MAP_CHILDREN][$result->type]) === false) {
                $treeMap[self::TREE_MAP_CHILDREN][$result->type] = [];
            }

            if (isset($treeMap[self::TREE_MAP_PARENTS][$result->type]) === false) {
                $treeMap[self::TREE_MAP_PARENTS][$result->type] = [];
            }

            if (isset($treeMap[self::TREE_MAP_CHILDREN][$result->type][$result->parentId]) === false) {
                $treeMap[self::TREE_MAP_CHILDREN][$result->type][$result->parentId] = [];
            }

            if (isset($treeMap[self::TREE_MAP_PARENTS][$result->type][$result->id]) === false) {
                $treeMap[self::TREE_MAP_PARENTS][$result->type][$result->id] = [];
            }

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
     * Returns the post tree map.
     *
     * @return array
     */
    public function getPostTreeMap()
    {
        if ($this->postTreeMap === null) {
            $select = "
                SELECT ID AS id, post_parent AS parentId, post_type AS type 
                FROM {$this->database->getPostsTable()}
                  WHERE post_parent != 0 AND post_type != 'revision'";

            $this->postTreeMap = $this->getTreeMap($select, self::GENERAL_POST_OBJECT_TYPE);
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
            $select = "
                SELECT term_id AS id, parent AS parentId, taxonomy AS type
                FROM {$this->database->getTermTaxonomyTable()}
                  WHERE parent != 0";

            $this->termTreeMap = $this->getTreeMap($select, self::GENERAL_TERM_OBJECT_TYPE);
        }

        return $this->termTreeMap;
    }

    /**
     * Returns the term post map.
     *
     * @return array
     */
    public function getTermPostMap()
    {
        if ($this->termPostMap === null) {
            $this->termPostMap = [];

            $select = "
                SELECT tr.object_id AS objectId, tt.term_id AS termId, p.post_type AS postType
                FROM {$this->database->getTermRelationshipsTable()} AS tr
                  LEFT JOIN {$this->database->getPostsTable()} AS p
                   ON (tr.object_id = p.ID)
                  LEFT JOIN {$this->database->getTermTaxonomyTable()} AS tt
                    ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

            $results = (array)$this->database->getResults($select);

            foreach ($results as $result) {
                if (isset($this->termPostMap[$result->termId]) === false) {
                    $this->termPostMap[$result->termId] = [];
                }

                $this->termPostMap[$result->termId][$result->objectId] = $result->postType;
            }
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
            $this->postTermMap = [];

            $select = "
                SELECT tr.object_id AS objectId, tt.term_id AS termId, tt.taxonomy AS termType
                FROM {$this->database->getTermRelationshipsTable()} AS tr 
                  LEFT JOIN {$this->database->getTermTaxonomyTable()} AS tt
                    ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

            $results = (array)$this->database->getResults($select);

            foreach ($results as $result) {
                if (isset($this->postTermMap[$result->objectId]) === false) {
                    $this->postTermMap[$result->objectId] = [];
                }

                $this->postTermMap[$result->objectId][$result->termId] = $result->termType;
            }
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
     * Registers object that should be handel by the user access manager.
     *
     * @param PluggableObject $object The object which you want to register.
     */
    public function registerPluggableObject(PluggableObject $object)
    {
        $this->pluggableObjects[$object->getObjectType()] = $object;
    }

    /**
     * Returns a registered pluggable object.
     *
     * @param string $objectName The name of the object which should be returned.
     *
     * @return PluggableObject
     */
    public function getPluggableObject($objectName)
    {
        if (isset($this->pluggableObjects[$objectName]) === true) {
            return $this->pluggableObjects[$objectName];
        }

        return null;
    }

    /**
     * Returns true if the object is a pluggable object.
     *
     * @param string $objectName
     *
     * @return bool
     */
    public function isPluggableObject($objectName)
    {
        return isset($this->pluggableObjects[$objectName]);
    }

    /**
     * Returns all registered pluggable objects.
     *
     * @return PluggableObject[]
     */
    public function getPluggableObjects()
    {
        return $this->pluggableObjects;
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
     * Returns all objects types.
     *
     * @return array
     */
    public function getAllObjectTypes()
    {
        if ($this->allObjectTypes === null) {
            $objectTypes = $this->getObjectTypes();
            $pluggableObjects = $this->getPluggableObjects();
            $pluggableObjectKeys = array_keys($pluggableObjects);
            $pluggableObjectKeys = array_combine($pluggableObjectKeys, $pluggableObjectKeys);

            $this->allObjectTypes = array_merge(
                [
                    self::GENERAL_ROLE_OBJECT_TYPE => self::GENERAL_ROLE_OBJECT_TYPE,
                    self::GENERAL_USER_OBJECT_TYPE => self::GENERAL_USER_OBJECT_TYPE,
                    self::GENERAL_POST_OBJECT_TYPE => self::GENERAL_POST_OBJECT_TYPE,
                    self::GENERAL_TERM_OBJECT_TYPE => self::GENERAL_TERM_OBJECT_TYPE
                ],
                $objectTypes,
                $pluggableObjectKeys
            );
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
        if ($objectType === self::GENERAL_USER_OBJECT_TYPE
            || $objectType === self::GENERAL_ROLE_OBJECT_TYPE
            || $objectType === self::GENERAL_TERM_OBJECT_TYPE
            || $objectType === self::GENERAL_POST_OBJECT_TYPE
            || $this->isPluggableObject($objectType) === true
        ) {
            return $objectType;
        } elseif ($this->isPostType($objectType) === true) {
            return self::GENERAL_POST_OBJECT_TYPE;
        } elseif ($this->isTaxonomy($objectType) === true) {
            return self::GENERAL_TERM_OBJECT_TYPE;
        }

        return null;
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
}
