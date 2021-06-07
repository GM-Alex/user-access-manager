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

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use stdClass;
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use WP_Comment;
use WP_Hook;
use WP_Post;
use WP_Query;

/**
 * Class FrontendPostController
 *
 * @package UserAccessManager\Controller
 */
class PostController extends ContentController
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var array
     */
    private $wordpressFilters = [];

    /**
     * @var null|stdClass
     */
    private $cachedCounts = [];

    /**
     * PostController constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig $mainConfig
     * @param Database $database
     * @param Util $util
     * @param ObjectHandler $objectHandler
     * @param UserHandler $userHandler
     * @param UserGroupHandler $userGroupHandler
     * @param AccessHandler $accessHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Database $database,
        Util $util,
        ObjectHandler $objectHandler,
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
        $this->database = $database;
    }

    /**
     * Return the wordpress filters.
     * @return array
     */
    public function getWordpressFilters(): array
    {
        return $this->wordpressFilters;
    }

    /**
     * Returns true if the filters are suppressed.
     * @param WP_Query $wpQuery
     * @return bool
     */
    private function filtersSuppressed(WP_Query $wpQuery): bool
    {
        return isset($wpQuery->query_vars['suppress_filters']) === true
            && $wpQuery->query_vars['suppress_filters'] === true;
    }

    /**
     * Manipulates the wordpress query object to filter content.
     * @param WP_Query $wpQuery The wordpress query object.
     * @throws UserGroupTypeException
     */
    public function parseQuery(WP_Query $wpQuery)
    {
        if ($this->filtersSuppressed($wpQuery) === true) {
            $excludedPosts = $this->accessHandler->getExcludedPosts();

            if ($excludedPosts !== []) {
                $postsNotIn = (isset($wpQuery->query_vars['post__not_in']) === true) ?
                    $wpQuery->query_vars['post__not_in'] : [];

                $wpQuery->query_vars['post__not_in'] = array_unique(
                    array_merge($postsNotIn, $excludedPosts)
                );
            }
        }
    }

    /**
     * Extracts the user access manager filters and returns true if it was successful.
     * @param WP_Hook[] $filters
     * @return bool
     */
    private function extractOwnFilters(array $filters): bool
    {
        if (isset($filters['the_posts']->callbacks[10]) === true) {
            foreach ($filters['the_posts']->callbacks[10] as $postFilter) {
                if (is_array($postFilter['function']) === true
                    && $postFilter['function'][0] instanceof PostController
                    && $postFilter['function'][1] === 'showPosts'
                ) {
                    $this->wordpressFilters['the_posts'] = $filters['the_posts'];
                    $filters['the_posts']->callbacks = [10 => [$postFilter]];
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * If filters are suppressed we still want to filter posts, so we have to turn the suppression off,
     * remove all other filters than the ones from the user access manager and store them to restore
     * them later.
     * @param array|null $posts
     * @param WP_Query $query
     * @return null|array
     */
    public function postsPreQuery(?array $posts, WP_Query $query): ?array
    {
        if ($this->filtersSuppressed($query) === true) {
            $filters = $this->wordpress->getFilters();

            // Only unset filter if the user access filter is active
            if ($this->extractOwnFilters($filters) === true) {
                $query->query_vars['suppress_filters'] = false;

                if (isset($filters['posts_results']) === true) {
                    $this->wordpressFilters['posts_results'] = $filters['posts_results'];
                    unset($filters['posts_results']);
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
     * @param mixed $post
     * @return false|WP_Post
     */
    private function getPost($post)
    {
        if ($post instanceof WP_post) {
            return $post;
        } elseif (is_int($post) === true) {
            return $this->objectHandler->getPost($post);
        } elseif (isset($post->ID) === true) {
            return $this->objectHandler->getPost($post->ID);
        }

        return false;
    }

    /**
     * Processes the post content and searches for the more tag.
     * @param WP_Post $post
     * @return string
     */
    private function processPostContent(WP_Post $post): string
    {
        $uamPostContent = htmlspecialchars_decode($this->mainConfig->getPostTypeContent($post->post_type));

        if ($this->mainConfig->showPostTypeContentBeforeMore($post->post_type) === true
            && preg_match('/<!--more(.*?)?-->/', $post->post_content, $matches)
        ) {
            $uamPostContent = explode($matches[0], $post->post_content)[0] . ' ' . $uamPostContent;
        }

        return stripslashes($uamPostContent);
    }

    /**
     * Modifies the content of the post by the given settings.
     * @param WP_Post $post The current post.
     * @return null|WP_Post
     * @throws UserGroupTypeException
     */
    private function processPost(WP_Post $post): ?WP_Post
    {
        $post->post_title .= $this->adminOutput((string) $post->post_type, $post->ID);

        if ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === false) {
            if ($this->removePostFromList($post->post_type) === true) {
                return null;
            }

            $post->post_content = $this->processPostContent($post);

            if ($this->mainConfig->hidePostTypeTitle($post->post_type) === true) {
                $post->post_title = $this->mainConfig->getPostTypeTitle($post->post_type);
            }

            if ($this->mainConfig->lockPostTypeComments($post->post_type) === true) {
                $post->comment_status = 'close';
            }
        }

        return $post;
    }

    /**
     * Filters the raw posts.
     * @param array $rawPosts
     * @return array
     * @throws UserGroupTypeException
     */
    private function filterRawPosts(array $rawPosts): array
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
     * @param null|array $showPosts The posts.
     * @return array
     * @throws UserGroupTypeException
     */
    public function showPosts(?array $showPosts = []): ?array
    {
        if ($this->wordpress->isFeed() === false || $this->mainConfig->protectFeed() === true) {
            $showPosts = $this->filterRawPosts((array) $showPosts);
        }

        $this->restoreFilters();

        return $showPosts;
    }

    /**
     * The function for the get_pages filter.
     * @param WP_Post[] $rawPages The pages.
     * @return array
     * @throws UserGroupTypeException
     */
    public function showPages($rawPages = []): array
    {
        return $this->filterRawPosts((array) $rawPages);
    }

    /**
     * Checks the access of the attached file.
     * @param string $file
     * @param int|string $attachmentId
     * @return string|false
     * @throws UserGroupTypeException
     */
    public function getAttachedFile(string $file, $attachmentId)
    {
        $isImage = (bool) preg_match('/(?i)\.(jpg|jpeg|jpe|png|gif)$/', $file);

        if ($isImage === false && $this->mainConfig->lockFile() === true) {
            $hasAccess = $this->accessHandler->checkObjectAccess(ObjectHandler::ATTACHMENT_OBJECT_TYPE, $attachmentId);
            return ($hasAccess === true) ? $file : false;
        }

        return $file;
    }

    /**
     * Adds the excluded posts filter to the given query.
     * @param string $query
     * @param string $table
     * @return string
     * @throws UserGroupTypeException
     */
    private function addQueryExcludedPostFilter(string $query, string $table): string
    {
        $excludedPosts = $this->accessHandler->getExcludedPosts();

        if ($excludedPosts !== []) {
            $excludedPostsStr = implode(', ', $excludedPosts);
            $query .= " AND {$table}.ID NOT IN ($excludedPostsStr) ";
        }

        return $query;
    }

    /**
     * The function for the posts_where_paged filter.
     * @param string $query The where sql statement.
     * @return string
     * @throws UserGroupTypeException
     */
    public function showPostSql(string $query): string
    {
        return $this->addQueryExcludedPostFilter($query, $this->database->getPostsTable());
    }

    /**
     * The function for the get_previous_post_where and
     * the get_next_post_where filter.
     * @param string $query The current sql string.
     * @return string
     * @throws UserGroupTypeException
     */
    public function showNextPreviousPost(string $query): string
    {
        return $this->addQueryExcludedPostFilter($query, 'p');
    }

    /**
     * Returns the post count query.
     * @param array $excludedPosts
     * @param string $type
     * @param string $perm
     * @return string
     */
    private function getPostCountQuery(array $excludedPosts, string $type, string $perm): string
    {
        $excludedPosts = implode('\', \'', $excludedPosts);
        $query = "SELECT post_status, COUNT(*) AS num_posts 
            FROM {$this->database->getPostsTable()} 
            WHERE post_type = %s
              AND ID NOT IN ('{$excludedPosts}')";

        if ('readable' === $perm
            && $this->wordpress->isUserLoggedIn() === true
            && $this->wordpress->currentUserCan(
                $this->wordpress->getPostTypeObject($type)->cap->read_private_posts
            ) === false
        ) {
            $query .= $this->database->prepare(
                ' AND (post_status != \'private\' OR (post_author = %d AND post_status = \'private\'))',
                $this->wordpress->getCurrentUser()->ID
            );
        }

        $query .= ' GROUP BY post_status';
        return $query;
    }

    /**
     * Function for the wp_count_posts filter.
     * @param stdClass $counts
     * @param string $type
     * @param string $perm
     * @return stdClass
     * @throws UserGroupTypeException
     */
    public function showPostCount(stdClass $counts, string $type, string $perm): stdClass
    {
        if (isset($this->cachedCounts[$type]) === false) {
            $excludedPosts = $this->accessHandler->getExcludedPosts();

            if ($excludedPosts !== []) {
                $query = $this->getPostCountQuery($excludedPosts, $type, $perm);
                $results = (array) $this->database->getResults(
                    $this->database->prepare($query, $type),
                    ARRAY_A
                );

                foreach ($results as $result) {
                    if (isset($counts->{$result['post_status']})) {
                        $counts->{$result['post_status']} = $result['num_posts'];
                    }
                }
            }

            $this->cachedCounts[$type] = $counts;
        }

        return $this->cachedCounts[$type];
    }

    /**
     * Checks if the post comment should be completely hidden.
     * @param string $postType
     * @return bool
     */
    private function hidePostComment(string $postType): bool
    {
        return $this->mainConfig->lockPostTypeComments($postType) === true
            || $this->mainConfig->hidePostType($postType) === true
            || $this->wordpressConfig->atAdminPanel() === true;
    }

    /**
     * The function for the comments_array filter.
     * @param WP_Comment[] $comments The comments.
     * @return array
     * @throws UserGroupTypeException
     */
    public function showComment($comments = []): array
    {
        $showComments = [];

        foreach ($comments as $comment) {
            $post = $this->objectHandler->getPost($comment->comment_post_ID);

            if ($post !== false
                && $this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === false
            ) {
                if ($this->hidePostComment($post->post_type)) {
                    continue;
                }

                if ($this->mainConfig->hidePostTypeComments($post->post_type) === true) {
                    $comment->comment_content = $this->mainConfig->getPostTypeCommentContent($post->post_type);
                }
            }

            $showComments[] = $comment;
        }

        return $showComments;
    }

    /**
     * The function for the edit_post_link filter.
     * @param null|string $link The edit link.
     * @param int|string $postId The _iId of the post.
     * @return string
     * @throws UserGroupTypeException
     */
    public function showEditLink(?string $link, $postId): string
    {
        if ($this->mainConfig->hideEditLinkOnNoAccess() === true
            && $this->accessHandler->checkObjectAccess(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $postId, true) === false
        ) {
            $link = '';
        }

        if ($this->mainConfig->showAssignedGroups() === true) {
            $userGroups = $this->userGroupHandler->getFilteredUserGroupsForObject(
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

                $link .= $link !== '' ? ' | ' : ' ';
                $link .= TXT_UAM_ASSIGNED_GROUPS . ': ' . implode(', ', $escapedGroups);
            }
        }

        return (string) $link;
    }
}
