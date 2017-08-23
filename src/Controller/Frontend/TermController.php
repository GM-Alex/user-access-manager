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

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserHandler\UserHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FrontendTermController
 *
 * @package UserAccessManager\Controller
 */
class TermController extends Controller
{
    use AdminOutputControllerTrait;

    const POST_COUNTS_CACHE_KEY = 'WpPostCounts';

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var UserHandler
     */
    protected $userHandler;

    /**
     * @var AccessHandler
     */
    protected $accessHandler;

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
     * @param Php           $php
     * @param Wordpress     $wordpress
     * @param MainConfig    $config
     * @param Util          $util
     * @param ObjectHandler $objectHandler
     * @param UserHandler   $userHandler
     * @param AccessHandler $accessHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        AccessHandler $accessHandler
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->util = $util;
        $this->objectHandler = $objectHandler;
        $this->userHandler = $userHandler;
        $this->accessHandler = $accessHandler;
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
                $this->postObjectHideConfig[$postType] = $this->config->hidePostType($postType);
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
        $termTreeMap = $this->objectHandler->getTermTreeMap();

        if (isset($termTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$termType]) === true
            && isset($termTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$termType][$termId]) === true
        ) {
            $fullTerms += $termTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$termType][$termId];
        }

        $posts = [];
        $termPostMap = $this->objectHandler->getTermPostMap();

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

        if (($term instanceof \WP_Term) === false) {
            return $term;
        }

        if ($this->accessHandler->checkObjectAccess($term->taxonomy, $term->term_id) === false) {
            return null;
        }

        $term->name .= $this->adminOutput($term->taxonomy, $term->term_id, $term->name);
        $term->count = $this->getVisibleElementsCount($term->taxonomy, $term->term_id);

        //For categories
        if ($term->count <= 0
            && $this->config->atAdminPanel() === false
            && $this->config->hideEmptyTaxonomy($term->taxonomy) === true
        ) {
            $isEmpty = true;
        }

        if ($this->config->lockRecursive() === false) {
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

            if (($term instanceof \WP_Term) === false) {
                continue;
            }

            $term = $this->processTerm($term, $isEmpty);

            if ($term === null || $isEmpty === true) {
                unset($terms[$key]);
            }
        }

        return $terms;
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
                if ($this->accessHandler->checkObjectAccess($item->object, $item->object_id) === false) {
                    if ($this->config->hidePostType($item->object) === true
                        || $this->config->atAdminPanel() === true
                    ) {
                        continue;
                    }

                    if ($this->config->hidePostTypeTitle($item->object) === true) {
                        $item->title = $this->config->getPostTypeTitle($item->object);
                    }
                }
            } elseif ($this->objectHandler->isTaxonomy($item->object) === true) {
                $rawTerm = $this->objectHandler->getTerm($item->object_id);
                $term = $this->processTerm($rawTerm, $isEmpty);

                if ($term === false || $term === null || $isEmpty === true) {
                    continue;
                }
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
