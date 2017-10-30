<?php
/**
 * FrontendTermController.php
 *
 * The FrontendTermController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FrontendTermController
 *
 * @package UserAccessManager\Controller
 */
class TermController extends ContentController
{
    /**
     * @var array
     */
    private $visibleElementsCount = [];

    /**
     * @var null|array
     */
    private $postObjectHideConfig = null;

    /**
     * TermController constructor.
     *
     * @param Php              $php
     * @param Wordpress        $wordpress
     * @param WordpressConfig  $wordpressConfig
     * @param MainConfig       $mainConfig
     * @param Util             $util
     * @param ObjectHandler    $objectHandler
     * @param ObjectMapHandler $objectMapHandler
     * @param UserHandler      $userHandler
     * @param UserGroupHandler $userGroupHandler
     * @param AccessHandler    $accessHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util,
        ObjectHandler $objectHandler,
        ObjectMapHandler $objectMapHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        AccessHandler $accessHandler
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
        $this->objectMapHandler = $objectMapHandler;
    }

    /**
     * Returns the post object hide config.
     *
     * @return array
     */
    private function getPostObjectHideConfig()
    {
        if ($this->postObjectHideConfig === null) {
            $this->postObjectHideConfig = [];

            foreach ($this->objectHandler->getPostTypes() as $postType) {
                $this->postObjectHideConfig[$postType] = $this->mainConfig->hidePostType($postType);
            }
        }

        return $this->postObjectHideConfig;
    }

    /**
     * Returns all posts for the given term.
     *
     * @param string $termType
     * @param int    $termId
     *
     * @return array
     */
    private function getAllPostForTerm($termType, $termId)
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
     * Returns the post count for the term.
     *
     * @param string $termType
     * @param int    $termId
     *
     * @return int
     */
    private function getVisibleElementsCount($termType, $termId)
    {
        $key = $termType.'|'.$termId;

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

    /**
     * Checks if the category is empty.
     *
     * @param $term
     *
     * @return bool
     */
    private function isCategoryEmpty($term)
    {
        return $term->count <= 0
            && $this->wordpressConfig->atAdminPanel() === false
            && $this->mainConfig->hideEmptyTaxonomy($term->taxonomy) === true;
    }

    /**
     * Updates the term parent.
     *
     * @param \WP_Term $term
     *
     * @return \WP_Term
     */
    private function updateTermParent(\WP_Term $term)
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
     * Modifies the content of the term by the given settings.
     *
     * @param mixed $term
     * @param bool  $isEmpty
     *
     * @return null|\WP_Term
     */
    private function processTerm($term, &$isEmpty = null)
    {
        $isEmpty = false;

        if (($term instanceof \WP_Term) === false
            || $this->objectHandler->isValidObjectType($term->taxonomy) === false
        ) {
            return $term;
        }

        if ($this->accessHandler->checkObjectAccess($term->taxonomy, $term->term_id) === false) {
            return null;
        }

        $term->name .= $this->adminOutput($term->taxonomy, $term->term_id, $term->name);
        $term->count = $this->getVisibleElementsCount($term->taxonomy, $term->term_id);
        $isEmpty = $this->isCategoryEmpty($term);

        if ($this->mainConfig->lockRecursive() === false) {
            $term = $this->updateTermParent($term);
        }

        return $term;
    }

    /**
     * The function for the get_term filter.
     *
     * @param \WP_Term $term
     *
     * @return null|object
     */
    public function showTerm($term)
    {
        return $this->processTerm($term);
    }

    /**
     * The function for the get_terms filter.
     *
     * @param array $terms The terms.
     *
     * @return array
     */
    public function showTerms($terms = [])
    {
        foreach ($terms as $key => $term) {
            if (is_numeric($term) === true) {
                if ((int)$term === 0) {
                    unset($terms[$key]);
                    continue;
                }

                $term = $this->objectHandler->getTerm($term);
            }

            $term = $this->processTerm($term, $isEmpty);

            if ($term === null || $isEmpty === true) {
                unset($terms[$key]);
            }
        }

        return $terms;
    }

    /**
     * Processes a post menu item.
     *
     * @param \stdClass $item
     *
     * @return bool
     */
    private function processPostMenuItem(&$item)
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
     * Processes a term menu item.
     *
     * @param \stdClass $item
     *
     * @return bool
     */
    private function processTermMenuItem(&$item)
    {
        $rawTerm = $this->objectHandler->getTerm($item->object_id);
        $term = $this->processTerm($rawTerm, $isEmpty);

        return !($term === false || $term === null || $isEmpty === true);
    }

    /**
     * The function for the wp_get_nav_menu_items filter.
     *
     * @param array $items The menu item.
     *
     * @return array
     */
    public function showCustomMenu($items)
    {
        $showItems = [];

        foreach ($items as $key => $item) {
            $item->title .= $this->adminOutput($item->object, $item->object_id, $item->title);

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
     * Sets the excluded terms as argument.
     *
     * @param array $arguments
     *
     * @return array
     */
    public function getTermArguments(array $arguments)
    {
        $exclude = (isset($arguments['exclude']) === true) ?
            $this->wordpress->parseIdList($arguments['exclude']) : [];
        $arguments['exclude'] = array_merge($exclude, $this->accessHandler->getExcludedTerms());
        $arguments['exclude'] = array_unique($arguments['exclude']);

        return $arguments;
    }
}
