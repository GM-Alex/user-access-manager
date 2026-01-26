<?php

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

class PostController extends ContentController
{
    private array $wordpressFilters = [];
    private stdClass|array|null $cachedCounts = [];

    private array $posts = [];

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
        private Database $database
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

    public function getWordpressFilters(): array
    {
        return $this->wordpressFilters;
    }

    private function filtersSuppressed(WP_Query $wpQuery): bool
    {
        return isset($wpQuery->query_vars['suppress_filters']) === true
            && $wpQuery->query_vars['suppress_filters'] === true;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function parseQuery(WP_Query $wpQuery): void
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
     * @param WP_Hook[] $filters
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

    private function restoreFilters(): void
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

    private function getPost(mixed $post): bool|WP_Post
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
     * @throws UserGroupTypeException
     */
    private function processPost(WP_Post $post): WP_Post|bool
    {
        $post->post_title .= $this->adminOutput($post->post_type, $post->ID);

        if ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === false) {
            if ($this->removePostFromList($post->post_type) === true) {
                return false;
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
     * @throws UserGroupTypeException
     */
    private function getProcessedPost(WP_Post $post): ?WP_Post
    {
        $post = $this->posts[$post->post_type . '|' . $post->ID] ??= $this->processPost($post);
        return $post === false ? null : $post;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function filterRawPosts(array $rawPosts): array
    {
        $filteredPosts = [];

        foreach ($rawPosts as $rawPost) {
            $post = $this->getPost($rawPost);

            if ($post !== false) {
                $post = $this->getProcessedPost($post);

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
     * @param WP_Post[] $rawPages The pages.
     * @throws UserGroupTypeException
     */
    public function showPages(array $rawPages = []): array
    {
        return $this->filterRawPosts($rawPages);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getAttachedFile(string $file, int|string|null $attachmentId): bool|string
    {
        $isImage = (bool) preg_match('/(?i)\.(jpg|jpeg|jpe|png|gif)$/', $file);

        if ($isImage === false && $this->mainConfig->lockFile() === true) {
            $hasAccess = $this->accessHandler->checkObjectAccess(ObjectHandler::ATTACHMENT_OBJECT_TYPE, $attachmentId);
            return ($hasAccess === true) ? $file : false;
        }

        return $file;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function addQueryExcludedPostFilter(string $query, string $table): string
    {
        $excludedPosts = $this->accessHandler->getExcludedPosts();

        if ($excludedPosts !== []) {
            $excludedPostsStr = implode(', ', $excludedPosts);
            $query .= " AND $table.ID NOT IN ($excludedPostsStr) ";
        }

        return $query;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showPostSql(string $query): string
    {
        return $this->addQueryExcludedPostFilter($query, $this->database->getPostsTable());
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showNextPreviousPost(string $query): string
    {
        return $this->addQueryExcludedPostFilter($query, 'p');
    }

    private function getPostCountQuery(array $excludedPosts, string $type, string $perm): string
    {
        $excludedPosts = implode('\', \'', $excludedPosts);
        $query = "SELECT post_status, COUNT(*) AS num_posts 
            FROM {$this->database->getPostsTable()} 
            WHERE post_type = %s
              AND ID NOT IN ('$excludedPosts')";

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

    private function hidePostComment(string $postType): bool
    {
        return $this->mainConfig->lockPostTypeComments($postType) === true
            || $this->mainConfig->hidePostType($postType) === true
            || $this->wordpressConfig->atAdminPanel() === true;
    }

    /**
     * @param WP_Comment[] $comments The comments.
     * @throws UserGroupTypeException
     */
    public function showComment(array $comments = []): array
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
     * @throws UserGroupTypeException
     */
    public function showEditLink(?string $link, int|string|null $postId): string
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
