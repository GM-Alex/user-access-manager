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
use WeakMap;
use WP_Comment;
use WP_Error;
use WP_Hook;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

class PostController extends ContentController
{
    private const REST_OBJECT_ROUTE_PATTERN = '#^/[^/]+/v\d+/([^/]+)/(\d+)(?:/(\w+))?#';

    private array $wordpressFilters = [];
    private stdClass|array|null $cachedCounts = [];
    private ?array $restBaseToPostTypeMap = null;

    private WeakMap $posts;

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

        $this->posts = new WeakMap();
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

    private function addExcludedPosts(mixed $postsNotIn, array $excludedPosts): array
    {
        return array_unique(array_merge((array) $postsNotIn, $excludedPosts));
    }

    /**
     * @throws UserGroupTypeException
     */
    public function parseQuery(WP_Query $wpQuery): void
    {
        if ($this->filtersSuppressed($wpQuery) === true) {
            $excludedPosts = $this->accessHandler->getExcludedPosts();

            if ($excludedPosts !== []) {
                $wpQuery->query_vars['post__not_in'] = $this->addExcludedPosts(
                    $wpQuery->query_vars['post__not_in'] ?? [],
                    $excludedPosts
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
        $post = $this->posts[$post] ??= $this->processPost($post);
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

    private function getRestAccessDeniedError(): WP_Error
    {
        return $this->wordpress->getWpError(
            'uam_rest_access_denied',
            TXT_UAM_REST_ACCESS_DENIED,
            ['status' => $this->wordpress->isUserLoggedIn() === true ? 403 : 401]
        );
    }

    private function setRestField(array &$data, string $field, string $value): void
    {
        if (array_key_exists($field, $data) === false) {
            return;
        }

        if (is_array($data[$field]) === false) {
            $data[$field] = $value;

            return;
        }

        $restrictedValues = ['rendered' => $value, 'raw' => $value, 'protected' => false];

        foreach ($restrictedValues as $key => $restrictedValue) {
            if (array_key_exists($key, $data[$field]) === true) {
                $data[$field][$key] = $restrictedValue;
            }
        }
    }

    /**
     * @throws UserGroupTypeException
     */
    public function restrictRestResponse(mixed $response, mixed $post = null, mixed $request = null): mixed
    {
        if (($response instanceof WP_REST_Response) === false
            || ($post instanceof WP_Post) === false
            || $this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === true
        ) {
            return $response;
        }

        $restrictedContent = $this->processPostContent($post);
        $data = (array) $response->get_data();

        $this->setRestField($data, 'content', $restrictedContent);
        $this->setRestField($data, 'excerpt', $restrictedContent);

        if ($this->mainConfig->hidePostTypeTitle($post->post_type) === true) {
            $this->setRestField($data, 'title', $this->mainConfig->getPostTypeTitle($post->post_type));
        }

        $response->set_data($data);

        return $response;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function excludeRestrictedPostsFromRestQuery(array $queryArgs): array
    {
        $excludedPosts = $this->accessHandler->getExcludedPosts();

        if ($excludedPosts !== []) {
            $queryArgs['post__not_in'] = $this->addExcludedPosts($queryArgs['post__not_in'] ?? [], $excludedPosts);
        }

        return $queryArgs;
    }

    private function getRestBaseToPostTypeMap(): array
    {
        if ($this->restBaseToPostTypeMap !== null) {
            return $this->restBaseToPostTypeMap;
        }

        $this->restBaseToPostTypeMap = [];

        foreach ((array) $this->objectHandler->getPostTypes() as $postType) {
            $restBase = $this->wordpress->getPostTypeObject($postType)?->rest_base;
            $this->restBaseToPostTypeMap[empty($restBase) === true ? $postType : $restBase] = $postType;
        }

        return $this->restBaseToPostTypeMap;
    }

    /**
     * @return null|array{type: string, id: int, addressesSubResource: bool}
     */
    private function getRestRouteTarget(WP_REST_Request $request): ?array
    {
        if (preg_match(self::REST_OBJECT_ROUTE_PATTERN, (string) $request->get_route(), $matches) !== 1) {
            return null;
        }

        $postType = $this->getRestBaseToPostTypeMap()[$matches[1]] ?? null;

        return $postType === null ? null : [
            'type' => $postType,
            'id' => (int) $matches[2],
            'addressesSubResource' => ($matches[3] ?? '') !== ''
        ];
    }

    private function isReadingRestRequest(WP_REST_Request $request): bool
    {
        return in_array(strtoupper((string) $request->get_method()), Wordpress::REST_READING_METHODS, true);
    }

    private function isEditingRestRoute(bool $addressesSubResource, WP_REST_Request $request): bool
    {
        return $this->isReadingRestRequest($request) === false || $addressesSubResource === true;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function hasRestRouteAccess(string $objectType, int $objectId, bool $isEditingRoute): bool
    {
        if ($isEditingRoute === true) {
            return $this->accessHandler->checkObjectAccess($objectType, $objectId, true);
        }

        return $this->removePostFromList($objectType) === false
            || $this->accessHandler->checkObjectAccess($objectType, $objectId);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function restrictRestRequest(mixed $result, mixed $server = null, mixed $request = null): mixed
    {
        if (($request instanceof WP_REST_Request) === false) {
            return $result;
        }

        $routeTarget = $this->getRestRouteTarget($request);
        $isEditingRoute = $routeTarget !== null
            && $this->isEditingRestRoute($routeTarget['addressesSubResource'], $request);

        $this->wordpress->setRestRequestContext(
            $isEditingRoute === true || $request->get_param('context') === 'edit'
        );

        if ($result !== null || $routeTarget === null) {
            return $result;
        }

        ['type' => $type, 'id' => $id] = $routeTarget;

        return $this->hasRestRouteAccess($type, $id, $isEditingRoute) === true ?
            $result : $this->getRestAccessDeniedError();
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
            $excludedPostsStr = implode(', ', array_map('intval', $excludedPosts));
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
        $excludedPosts = implode(', ', array_map('intval', $excludedPosts));
        $query = "SELECT post_status, COUNT(*) AS num_posts
            FROM {$this->database->getPostsTable()}
            WHERE post_type = %s
              AND ID NOT IN ($excludedPosts)";

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
