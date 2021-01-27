<?php
/**
 * ObjectMapHandler.php
 *
 * The ObjectMapHandler class file.
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

use UserAccessManager\Cache\Cache;
use UserAccessManager\Database\Database;

/**
 * Class ObjectMapHandler
 *
 * @package UserAccessManager\Object
 */
class ObjectMapHandler
{
    const TREE_MAP_PARENTS = 'PARENT';
    const TREE_MAP_CHILDREN = 'CHILDREN';
    const POST_TREE_MAP_CACHE_KEY = 'uamPostTreeMap';
    const TERM_TREE_MAP_CACHE_KEY = 'uamTermTreeMap';
    const TERM_POST_MAP_CACHE_KEY = 'uamTermPostMap';
    const POST_TERM_MAP_CACHE_KEY = 'uamPostTermMap';

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Cache
     */
    private $cache;

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
     * ObjectMapHandler constructor.
     * @param Database $database
     * @param Cache $cache
     */
    public function __construct(
        Database $database,
        Cache $cache
    ) {
        $this->database = $database;
        $this->cache = $cache;
    }

    /**
     * Resolves all tree map elements
     * @param array $map
     * @param array|null $subMap
     * @param array $processed
     * @return array
     */
    private function processTreeMapElements(array &$map, array $subMap = null, array &$processed = []): array
    {
        $processMap = ($subMap === null) ? $map : $subMap;

        foreach ($processMap as $id => $subIds) {
            foreach ($subIds as $subId => $type) {
                if (isset($map[$subId]) === true && isset($processed[$id][$subId]) === false) {
                    $map[$id] += $this->processTreeMapElements($map, [$subId => $map[$subId]], $processed)[$subId];
                    $processed[$id][$subId] = $subId;
                }
            }
        }

        return $map;
    }

    /**
     * Returns the tree map for the query.
     * @param string $select
     * @param string $generalType
     * @return array
     */
    private function getTreeMap(string $select, string $generalType): array
    {
        $treeMap = [
            self::TREE_MAP_CHILDREN => [
                $generalType => []
            ],
            self::TREE_MAP_PARENTS => [
                $generalType => []
            ]
        ];
        $results = (array) $this->database->getResults($select);

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
     * @param string $cacheKey
     * @param string $generalType
     * @param string $query
     * @return array
     */
    private function getCachedTreeMap(string $cacheKey, string $generalType, string $query): array
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
     * @return array
     */
    public function getPostTreeMap(): ?array
    {
        if ($this->postTreeMap === null) {
            $query = "SELECT ID AS id, post_parent AS parentId, post_type AS type 
                FROM {$this->database->getPostsTable()}
                WHERE post_parent != 0 AND post_type != 'revision'";

            $this->postTreeMap = $this->getCachedTreeMap(
                self::POST_TREE_MAP_CACHE_KEY,
                ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                $query
            );
        }

        return $this->postTreeMap;
    }

    /**
     * Returns the term tree map.
     * @return array
     */
    public function getTermTreeMap(): ?array
    {
        if ($this->termTreeMap === null) {
            $query = "SELECT term_id AS id, parent AS parentId, taxonomy AS type
                FROM {$this->database->getTermTaxonomyTable()}
                WHERE parent != 0";

            $this->termTreeMap = $this->getCachedTreeMap(
                self::TERM_TREE_MAP_CACHE_KEY,
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
                $query
            );
        }

        return $this->termTreeMap;
    }

    /**
     * Returns the cached map.
     * @param string $cacheKey
     * @param string $query
     * @return array
     */
    private function getCachedMap(string $cacheKey, string $query): array
    {
        $map = $this->cache->get($cacheKey);

        if ($map === null) {
            $map = [];
            $results = (array) $this->database->getResults($query);

            foreach ($results as $result) {
                $map[$result->parentId][$result->objectId] = $result->type;
            }

            $this->cache->add($cacheKey, $map);
        }

        return $map;
    }

    /**
     * Returns the term post map.
     * @return array
     */
    public function getTermPostMap(): ?array
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
     * @return array
     */
    public function getPostTermMap(): ?array
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
}
