<?php
/**
 * FrontendPostController.php
 *
 * The FrontendPostController class file.
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
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FrontendPostController
 *
 * @package UserAccessManager\Controller
 */
class PostController extends Controller
{
    use LoginControllerTrait;
    use AdminOutputControllerTrait;

    const POST_COUNTS_CACHE_KEY = 'WpPostCounts';

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AccessHandler
     */
    protected $accessHandler;

    /**
     * @var array
     */
    private $wordpressFilters = [];

    /**
     * FrontendPostController constructor.
     *
     * @param Php           $php
     * @param Wordpress     $wordpress
     * @param MainConfig    $config
     * @param Database      $database
     * @param Util          $util
     * @param Cache         $cache
     * @param ObjectHandler $objectHandler
     * @param AccessHandler $accessHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        MainConfig $config,
        Database $database,
        Util $util,
        Cache $cache,
        ObjectHandler $objectHandler,
        AccessHandler $accessHandler
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->database = $database;
        $this->util = $util;
        $this->cache = $cache;
        $this->objectHandler = $objectHandler;
        $this->accessHandler = $accessHandler;
    }

    /**
     * Manipulates the wordpress query object to filter content.
     *
     * @param \WP_Query $wpQuery The wordpress query object.
     */
    public function parseQuery($wpQuery)
    {
        if (isset($wpQuery->query_vars['suppress_filters']) === true
            && $wpQuery->query_vars['suppress_filters'] === true
        ) {
            $excludedPosts = $this->accessHandler->getExcludedPosts();

            if (count($excludedPosts) > 0) {
                $postsNotIn = (isset($wpQuery->query_vars['post__not_in']) === true) ?
                    $wpQuery->query_vars['post__not_in'] : [];

                $wpQuery->query_vars['post__not_in'] = array_unique(
                    array_merge($postsNotIn, $excludedPosts)
                );
            }
        }
    }

    /**
     * Returns the login bar.
     *
     * @return string
     */
    public function getLoginFormHtml()
    {
        $loginForm = '';

        if ($this->wordpress->isUserLoggedIn() === false) {
            $loginForm = $this->getIncludeContents('LoginForm.php');
        }

        return $this->wordpress->applyFilters('uam_login_form', $loginForm);
    }

    /**
     * If filters are suppressed we still want to filter posts, so we have to turn the suppression off,
     * remove all other filters than the ones from the user access manager and store them to restore
     * them later.
     *
     * @param array     $posts
     * @param \WP_Query $query
     *
     * @return mixed
     */
    public function postsPreQuery($posts, \WP_Query $query)
    {
        if (isset($query->query_vars['suppress_filters']) === true
            && $query->query_vars['suppress_filters'] === true
        ) {
            $filters = $this->wordpress->getFilters();

            if (isset($filters['the_posts']) === true && isset($filters['the_posts']->callbacks[10]) === true) {
                foreach ($filters['the_posts']->callbacks[10] as $postFilter) {
                    if (is_array($postFilter['function']) === true
                        && $postFilter['function'][0] instanceof PostController
                        && $postFilter['function'][1] === 'showPosts'
                    ) {
                        $this->wordpressFilters['the_posts'] = $filters['the_posts'];
                        $query->query_vars['suppress_filters'] = false;
                        $filters['the_posts']->callbacks = [10 => [$postFilter]];
                        break;
                    }
                }
            }

            // Only unset filter if the user access filter is active
            if ($query->query_vars['suppress_filters'] === false) {
                $filtersToProcess = ['posts_results'];

                foreach ($filtersToProcess as $filterToProcess) {
                    if (isset($filters[$filterToProcess]) === true) {
                        $this->wordpressFilters[$filterToProcess] = $filters[$filterToProcess];
                        unset($filters[$filterToProcess]);
                    }
                }

                $this->wordpress->setFilters($filters);
            }
        }

        return $posts;
    }

    /**
     * Restores the filters to normal.
     */
    private function restoreFilters()
    {
        if (count($this->wordpressFilters) > 0) {
            $filters = $this->wordpress->getFilters();

            foreach ($this->wordpressFilters as $filterKey => $filter) {
                $filters[$filterKey] = $filter;
            }

            $this->wordpress->setFilters($filters);
            $this->wordpressFilters = [];
        }
    }

    /**
     * Tries to get the post from the given mixed data.
     *
     * @param mixed $post
     *
     * @return false|\WP_Post
     */
    private function getPost($post)
    {
        if ($post instanceof \WP_post) {
            return $post;
        } elseif (is_int($post) === true) {
            return $this->objectHandler->getPost($post);
        } elseif (($post instanceof \stdClass) === true && isset($post->ID) === true) {
            return $this->objectHandler->getPost($post->ID);
        }

        return false;
    }

    /**
     * Processes the post content and searches for the more tag.
     *
     * @param \WP_Post $post
     *
     * @return string
     */
    private function processPostContent(\WP_Post $post)
    {
        $uamPostContent = htmlspecialchars_decode($this->config->getPostTypeContent($post->post_type));

        if ($post->post_type === 'post'
            && $this->config->showPostContentBeforeMore() === true
            && preg_match('/<!--more(.*?)?-->/', $post->post_content, $matches)
        ) {
            $uamPostContent = explode($matches[0], $post->post_content)[0]." ".$uamPostContent;
        }

        return stripslashes($uamPostContent);
    }

    /**
     * Modifies the content of the post by the given settings.
     *
     * @param \WP_Post $post    The current post.
     * @param bool     $locked
     *
     * @return null|\WP_Post
     */
    private function processPost(\WP_Post $post, &$locked = null)
    {
        $post->post_title .= $this->adminOutput($post->post_type, $post->ID);
        $locked = ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === false);

        if ($locked === true) {
            if ($this->config->hidePostType($post->post_type) === true
                || $this->config->atAdminPanel() === true
            ) {
                return null;
            }

            $post->post_content = $this->processPostContent($post);

            if ($this->config->hidePostTypeTitle($post->post_type) === true) {
                $post->post_title = $this->config->getPostTypeTitle($post->post_type);
            }

            if ($this->config->lockPostTypeComments($post->post_type) === true) {
                $post->comment_status = 'close';
            }
        }

        return $post;
    }

    /**
     * Filters the raw posts.
     *
     * @param array $rawPosts
     *
     * @return array
     */
    private function filterRawPosts(array $rawPosts)
    {
        $filteredPosts = [];

        foreach ($rawPosts as $rawPost) {
            $post = $this->getPost($rawPost);

            if ($post !== false) {
                $post = $this->processPost($post);

                if ($post !== null) {
                    $filteredPosts[] = $post;
                }
            } else {
                $filteredPosts[] = $rawPost;
            }
        }

        return $filteredPosts;
    }

    /**
     * The function for the the_posts filter.
     *
     * @param array $rawPosts The posts.
     *
     * @return array
     */
    public function showPosts($rawPosts = [])
    {
        if ($this->wordpress->isFeed() === false || $this->config->protectFeed() === true) {
            $showPosts = $this->filterRawPosts($rawPosts);
        } else {
            $showPosts = $rawPosts;
        }

        $this->restoreFilters();

        return $showPosts;
    }

    /**
     * The function for the get_pages filter.
     *
     * @param \WP_Post[] $rawPages The pages.
     *
     * @return array
     */
    public function showPages($rawPages = [])
    {
        return $this->filterRawPosts($rawPages);
    }

    /**
     * Checks the access of the attached file.
     *
     * @param string $file
     * @param int    $attachmentId
     *
     * @return string|false
     */
    public function getAttachedFile($file, $attachmentId)
    {
        //TODO add check for images
        if ($this->config->lockFile() === true) {
            $hasAccess = $this->accessHandler->checkObjectAccess(ObjectHandler::ATTACHMENT_OBJECT_TYPE, $attachmentId);
            return ($hasAccess === true) ? $file : false;
        }

        return $file;
    }

    /**
     * Handles the login form short code.
     *
     * @return string
     */
    public function loginFormShortCode()
    {
        return $this->getLoginFormHtml();
    }

    /**
     * Handles the public short code.
     *
     * @param array  $attributes
     * @param string $content
     *
     * @return string
     */
    public function publicShortCode($attributes, $content = '')
    {
        return ($this->wordpress->isUserLoggedIn() === false) ? $this->wordpress->doShortCode($content) : '';
    }

    /**
     * Handles the private short code.
     *
     * @param array  $attributes
     * @param string $content
     *
     * @return string
     */
    public function privateShortCode($attributes, $content = '')
    {
        if ($this->wordpress->isUserLoggedIn() === true) {
            $userGroups = (isset($attributes['group']) === true) ? explode(',', $attributes['group']) : [];

            if ($userGroups === []) {
                return $this->wordpress->doShortCode($content);
            }

            $userGroupMap = array_flip(array_map('trim', $userGroups));
            $userUserGroups = $this->accessHandler->getUserGroupsForUser();

            foreach ($userUserGroups as $userGroup) {
                if (isset($userGroupMap[$userGroup->getId()])
                    || isset($userGroupMap[$userGroup->getName()])
                ) {
                    return $this->wordpress->doShortCode($content);
                }
            }
        }

        return '';
    }

    /**
     * Adds the excluded posts filter to the given query.
     *
     * @param string $query
     * @param string $table
     *
     * @return string
     */
    private function addQueryExcludedPostFilter($query, $table)
    {
        $excludedPosts = $this->accessHandler->getExcludedPosts();

        if (count($excludedPosts) > 0) {
            $excludedPostsStr = implode(', ', $excludedPosts);
            $query .= " AND {$table}.ID NOT IN ($excludedPostsStr) ";
        }

        return $query;
    }

    /**
     * The function for the posts_where_paged filter.
     *
     * @param string $query The where sql statement.
     *
     * @return string
     */
    public function showPostSql($query)
    {
        return $this->addQueryExcludedPostFilter($query, $this->database->getPostsTable());
    }

    /**
     * The function for the get_previous_post_where and
     * the get_next_post_where filter.
     *
     * @param string $query The current sql string.
     *
     * @return string
     */
    public function showNextPreviousPost($query)
    {
        return $this->addQueryExcludedPostFilter($query, 'p');
    }

    /**
     * Function for the wp_count_posts filter.
     *
     * @param \stdClass $counts
     * @param string    $type
     * @param string    $perm
     *
     * @return \stdClass
     */
    public function showPostCount($counts, $type, $perm)
    {
        $cachedCounts = $this->cache->getFromRuntimeCache(self::POST_COUNTS_CACHE_KEY);

        if ($cachedCounts === null) {
            $excludedPosts = $this->accessHandler->getExcludedPosts();

            if (count($excludedPosts) > 0) {
                $excludedPosts = implode('\', \'', $excludedPosts);

                $query = "SELECT post_status, COUNT(*) AS num_posts 
                    FROM {$this->database->getPostsTable()} 
                    WHERE post_type = %s
                      AND ID NOT IN ('{$excludedPosts}')";

                if ('readable' === $perm && $this->wordpress->isUserLoggedIn() === true) {
                    $postTypeObject = $this->wordpress->getPostTypeObject($type);

                    if ($this->wordpress->currentUserCan($postTypeObject->cap->read_private_posts) === false) {
                        $query .= $this->database->prepare(
                            ' AND (post_status != \'private\' OR (post_author = %d AND post_status = \'private\'))',
                            $this->wordpress->getCurrentUser()->ID
                        );
                    }
                }

                $query .= ' GROUP BY post_status';

                $results = (array)$this->database->getResults(
                    $this->database->prepare($query, $type),
                    ARRAY_A
                );

                foreach ($results as $result) {
                    if (isset($counts->{$result['post_status']})) {
                        $counts->{$result['post_status']} = $result['num_posts'];
                    }
                }
            }

            $cachedCounts = $counts;
            $this->cache->addToRuntimeCache(self::POST_COUNTS_CACHE_KEY, $cachedCounts);
        }

        return $cachedCounts;
    }

    /**
     * The function for the comments_array filter.
     *
     * @param \WP_Comment[] $comments The comments.
     *
     * @return array
     */
    public function showComment($comments = [])
    {
        $showComments = [];

        foreach ($comments as $comment) {
            $post = $this->objectHandler->getPost($comment->comment_post_ID);

            if ($post !== false
                && $this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === false
            ) {
                if ($this->config->lockPostTypeComments($post->post_type) === true
                    || $this->config->hidePostType($post->post_type) === true
                    || $this->config->atAdminPanel() === true
                ) {
                    continue;
                }

                if ($this->config->hidePostTypeComments($post->post_type) === true) {
                    $comment->comment_content = $this->config->getPostTypeCommentContent($post->post_type);
                }
            }

            $showComments[] = $comment;
        }

        return $showComments;
    }

    /**
     * The function for the edit_post_link filter.
     *
     * @param string  $link   The edit link.
     * @param integer $postId The _iId of the post.
     *
     * @return string
     */
    public function showGroupMembership($link, $postId)
    {
        $userGroups = $this->accessHandler->getFilteredUserGroupsForObject(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $postId
        );

        if (count($userGroups) > 0) {
            $escapedGroups = array_map(
                function (AbstractUserGroup $group) {
                    return htmlentities($group->getName());
                },
                $userGroups
            );

            $link .= ' | '.TXT_UAM_ASSIGNED_GROUPS.': ';
            $link .= implode(', ', $escapedGroups);
        }

        return $link;
    }
}
