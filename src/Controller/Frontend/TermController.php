<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use Exception;
use stdClass;
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use WP_Term;

class TermController extends ContentController
{
    private array $visibleElementsCount = [];
    private ?array $postObjectHideConfig = null;

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        AccessHandler $accessHandler,
        private ObjectMapHandler $objectMapHandler
    ) {
        parent::__construct(
            $php,
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $util,
            $objectHandler,
            $userHandler,
            $userGroupHandler,
            $accessHandler
        );
    }

    private function getPostObjectHideConfig(): ?array
    {
        if ($this->postObjectHideConfig === null) {
            $this->postObjectHideConfig = [];

            foreach ($this->objectHandler->getPostTypes() as $postType) {
                $this->postObjectHideConfig[$postType] = $this->mainConfig->hidePostType($postType);
            }
        }

        return $this->postObjectHideConfig;
    }

    private function getAllPostForTerm(string $termType, int|string|null $termId): array
    {
        $fullTerms = [$termId => $termType];
        $termTreeMap = $this->objectMapHandler->getTermTreeMap();

        if (isset($termTreeMap[ObjectMapHandler::TREE_MAP_CHILDREN][$termType]) === true
            && isset($termTreeMap[ObjectMapHandler::TREE_MAP_CHILDREN][$termType][$termId]) === true
        ) {
            $fullTerms += $termTreeMap[ObjectMapHandler::TREE_MAP_CHILDREN][$termType][$termId];
        }

        $posts = [];
        $termPostMap = $this->objectMapHandler->getTermPostMap();

        foreach ($fullTerms as $fullTermId => $fullTermType) {
            if (isset($termPostMap[$fullTermId]) === true) {
                $posts += $termPostMap[$fullTermId];
            }
        }

        return $posts;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function getVisibleElementsCount(string $termType, int|string|null $termId): int
    {
        $key = $termType . '|' . $termId;

        if (isset($this->visibleElementsCount[$key]) === false) {
            $count = 0;
            $posts = $this->getAllPostForTerm($termType, $termId);
            $postTypeHiddenMap = $this->getPostObjectHideConfig();

            foreach ($posts as $postId => $postType) {
                if (isset($postTypeHiddenMap[$postType]) && $postTypeHiddenMap[$postType] === false
                    || $this->accessHandler->checkObjectAccess(
                        ObjectHandler::GENERAL_POST_OBJECT_TYPE,
                        $postId
                    ) === true
                ) {
                    $count++;
                }
            }

            $this->visibleElementsCount[$key] = $count;
        }

        return $this->visibleElementsCount[$key];
    }

    private function isCategoryEmpty(WP_Term $term): bool
    {
        return $term->count <= 0
            && $this->wordpressConfig->atAdminPanel() === false
            && $this->mainConfig->hideEmptyTaxonomy($term->taxonomy) === true;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function updateTermParent(WP_Term|stdClass $term): WP_Term|stdClass
    {
        $currentTerm = $term;

        while ($currentTerm->parent != 0) {
            $currentTerm = $this->objectHandler->getTerm($currentTerm->parent);

            if ($currentTerm === false) {
                break;
            }

            $access = $this->accessHandler->checkObjectAccess(
                $currentTerm->taxonomy,
                $currentTerm->term_id
            );

            if ($access === true) {
                $term->parent = $currentTerm->term_id;
                break;
            }
        }

        return $term;
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    private function processTerm(mixed $term, bool &$isEmpty = null): mixed
    {
        $isEmpty = false;

        if (($term instanceof WP_Term) === false
            || $this->objectHandler->isValidObjectType($term->taxonomy) === false
        ) {
            return $term;
        }

        if ($this->accessHandler->checkObjectAccess($term->taxonomy, $term->term_id) === false) {
            return false;
        }

        $term->name .= $this->adminOutput((string) $term->taxonomy, $term->term_id, $term->name);
        $term->count = $this->getVisibleElementsCount($term->taxonomy, $term->term_id);
        $isEmpty = $this->isCategoryEmpty($term);

        if ($this->mainConfig->lockRecursive() === false) {
            $term = $this->updateTermParent($term);
        }

        return $term;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showTerm(WP_Term|stdClass $term): WP_Term|stdClass|bool
    {
        return $this->processTerm($term);
    }

    /**
     * @param WP_Term[] $terms
     * @throws UserGroupTypeException
     */
    public function showTerms(array $terms = []): array
    {
        foreach ($terms as $key => $term) {
            if (is_numeric($term) === true) {
                if ((int) $term === 0) {
                    unset($terms[$key]);
                    continue;
                }

                $term = $this->objectHandler->getTerm($term);
            }

            $term = $this->processTerm($term, $isEmpty);

            if ($term === false || $isEmpty === true) {
                unset($terms[$key]);
            }
        }

        return $terms;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function processPostMenuItem(object $item): bool
    {
        if ($this->accessHandler->checkObjectAccess($item->object, $item->object_id) === false) {
            if ($this->removePostFromList($item->object) === true) {
                return false;
            }

            if ($this->mainConfig->hidePostTypeTitle($item->object) === true) {
                $item->title = $this->mainConfig->getPostTypeTitle($item->object);
            }
        }

        return true;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function processTermMenuItem(mixed $item): bool
    {
        $rawTerm = $this->objectHandler->getTerm($item->object_id);
        $term = $this->processTerm($rawTerm, $isEmpty);

        return !($term === false || $isEmpty === true);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showCustomMenu(array $items): array
    {
        $showItems = [];

        foreach ($items as $key => $item) {
            $item->title .= $this->adminOutput((string) $item->object, $item->object_id, $item->title);

            if ($this->objectHandler->isPostType($item->object) === true) {
                if ($this->processPostMenuItem($item) === false) {
                    continue;
                }
            } elseif ($this->objectHandler->isTaxonomy($item->object) === true
                && $this->processTermMenuItem($item) === false
            ) {
                continue;
            }

            $showItems[$key] = $item;
        }

        return $showItems;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getTermArguments(array $arguments): array
    {
        $exclude = (isset($arguments['exclude']) === true) ?
            $this->wordpress->parseIdList($arguments['exclude']) : [];
        $arguments['exclude'] = array_merge($exclude, $this->accessHandler->getExcludedTerms());
        $arguments['exclude'] = array_unique($arguments['exclude']);

        return $arguments;
    }
}
