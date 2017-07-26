<?php
/**
 * FrontendController.php
 *
 * The FrontendController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileObject;
use UserAccessManager\FileHandler\FileObjectFactory;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FrontendController
 *
 * @package UserAccessManager\Controller
 */
class FrontendController extends Controller
{
    const HANDLE_STYLE_LOGIN_FORM = 'UserAccessManagerLoginForm';
    const POST_URL_CACHE_KEY = 'PostUrls';
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
    private $util;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var FileObjectFactory
     */
    private $fileObjectFactory;

    /**
     * @var array
     */
    private $wordpressFilters = [];

    /**
     * FrontendController constructor.
     *
     * @param Php               $php
     * @param Wordpress         $wordpress
     * @param Config            $config
     * @param Database          $database
     * @param Util              $util
     * @param Cache             $cache
     * @param ObjectHandler     $objectHandler
     * @param AccessHandler     $accessHandler
     * @param FileHandler       $fileHandler
     * @param FileObjectFactory $fileObjectFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Config $config,
        Database $database,
        Util $util,
        Cache $cache,
        ObjectHandler $objectHandler,
        AccessHandler $accessHandler,
        FileHandler $fileHandler,
        FileObjectFactory $fileObjectFactory
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->database = $database;
        $this->util = $util;
        $this->cache = $cache;
        $this->objectHandler = $objectHandler;
        $this->accessHandler = $accessHandler;
        $this->fileHandler = $fileHandler;
        $this->fileObjectFactory = $fileObjectFactory;
    }

    /**
     * Functions for other content.
     */

    /**
     * Register all other styles.
     */
    private function registerStylesAndScripts()
    {
        $urlPath = $this->config->getUrlPath();

        $this->wordpress->registerStyle(
            self::HANDLE_STYLE_LOGIN_FORM,
            $urlPath.'assets/css/uamLoginForm.css',
            [],
            UserAccessManager::VERSION,
            'screen'
        );
    }

    /**
     * The function for the wp_enqueue_scripts action.
     */
    public function enqueueStylesAndScripts()
    {
        $this->registerStylesAndScripts();
        $this->wordpress->enqueueStyle(self::HANDLE_STYLE_LOGIN_FORM);
    }

    /*
     * Functions for the blog content.
     */

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
     * Returns the admin hint.
     *
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id we want to check.
     * @param string  $text       The text on which we want to append the hint.
     *
     * @return string
     */
    public function adminOutput($objectType, $objectId, $text = null)
    {
        $output = '';

        if ($this->config->atAdminPanel() === false
            && $this->config->blogAdminHint() === true
        ) {
            $hintText = $this->config->getBlogAdminHintText();

            if ($text !== null && $this->util->endsWith($text, $hintText) === true) {
                return $output;
            }

            if ($this->accessHandler->userIsAdmin($this->wordpress->getCurrentUser()->ID) === true
                && count($this->accessHandler->getUserGroupsForObject($objectType, $objectId)) > 0
            ) {
                $output .= $hintText;
            }
        }

        return $output;
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
                        && $postFilter['function'][0] instanceof FrontendController
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

            $uamPostContent = $this->config->getPostTypeContent($post->post_type);

            if ($post->post_type === 'post'
                && $this->config->showPostContentBeforeMore() === true
                && preg_match('/<!--more(.*?)?-->/', $post->post_content, $matches)
            ) {
                $uamPostContent = explode($matches[0], $post->post_content)[0]." ".$uamPostContent;
            }

            $post->post_content = stripslashes($uamPostContent);

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
     * The function for the the_posts filter.
     *
     * @param array $rawPosts The posts.
     *
     * @return array
     */
    public function showPosts($rawPosts = [])
    {
        $showPosts = [];

        if ($this->wordpress->isFeed() === false || $this->config->protectFeed() === true) {
            foreach ($rawPosts as $rawPost) {
                $post = $this->getPost($rawPost);

                if ($post !== false) {
                    $post = $this->processPost($post);

                    if ($post !== null) {
                        $showPosts[] = $post;
                    }
                } else {
                    $showPosts[] = $rawPost;
                }
            }
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
        $showPages = [];

        foreach ($rawPages as $rawPage) {
            $page = $this->getPost($rawPage);

            if ($page !== false) {
                $page = $this->processPost($page);

                if ($page !== null) {
                    $showPages[] = $page;
                }
            } else {
                $showPages[] = $rawPage;
            }
        }

        $rawPages = $showPages;

        return $rawPages;
    }

    /**
     * Checks the access of the attached file.
     *
     * @param string $file
     * @param int    $attachmentId
     *
     * @return string|bool
     */
    public function getAttachedFile($file, $attachmentId)
    {
        if ($this->config->lockFile() === true) {
            $hasAccess = $this->accessHandler->checkObjectAccess(ObjectHandler::ATTACHMENT_OBJECT_TYPE, $attachmentId);
            return ($hasAccess === true) ? $file : false;
        }

        return $file;
    }

    /**
     * Needed to prevent the form against the auto <br>s of wordpress
     *
     * @param string $content
     *
     * @return string
     */
    public function showContent($content)
    {
        return (string)str_replace('[LOGIN_FORM]', $this->getLoginFormHtml(), $content);
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
        $excludedPosts = $this->accessHandler->getExcludedPosts();

        if (count($excludedPosts) > 0) {
            $excludedPostsStr = implode(', ', $excludedPosts);
            $query .= " AND {$this->database->getPostsTable()}.ID NOT IN ($excludedPostsStr) ";
        }

        return $query;
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
        $cachedCounts = $this->cache->getFromCache(self::POST_COUNTS_CACHE_KEY);

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
            $this->cache->addToCache(self::POST_COUNTS_CACHE_KEY, $cachedCounts);
        }

        return $cachedCounts;
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
     * The function for the get_ancestors filter.
     *
     * @param array  $ancestors
     * @param int    $objectId
     * @param string $objectType
     *
     * @return array
     */
    public function showAncestors($ancestors, $objectId, $objectType)
    {
        if ($this->config->lockRecursive() === true
            && $this->accessHandler->checkObjectAccess($objectType, $objectId) === false
        ) {
            return [];
        }

        foreach ($ancestors as $key => $ancestorId) {
            if ($this->accessHandler->checkObjectAccess($objectType, $ancestorId) === false) {
                unset($ancestors[$key]);
            }
        }

        return $ancestors;
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
        $excludedPosts = $this->accessHandler->getExcludedPosts();

        if (count($excludedPosts) > 0) {
            $excludedPosts = implode(', ', $excludedPosts);
            $query .= " AND p.ID NOT IN ({$excludedPosts}) ";
        }

        return $query;
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
        $count = 0;

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

        foreach ($posts as $postId => $postType) {
            if ($this->config->hidePostType($postType) === false
                || $this->accessHandler->checkObjectAccess(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $postId) === true
            ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Modifies the content of the term by the given settings.
     *
     * @param \WP_Term $term     The current term.
     * @param bool     $isEmpty
     *
     * @return mixed
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
            $isNumeric = (is_numeric($term) === true);

            if ($isNumeric === true) {
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

            if ($term !== null && $isEmpty === false) {
                $terms[$key] = ($isNumeric === true) ? $term->term_id : $term;
            } else {
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

                $showItems[$key] = $item;
            } elseif ($this->objectHandler->isTaxonomy($item->object) === true) {
                $term = $this->objectHandler->getTerm($item->object_id);

                if ($term !== false) {
                    $term = $this->processTerm($term, $isEmpty);

                    if ($term !== null && $isEmpty === false) {
                        $showItems[$key] = $item;
                    }
                }
            } else {
                $showItems[$key] = $item;
            }
        }

        return $showItems;
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
                function (UserGroup $group) {
                    return htmlentities($group->getName());
                },
                $userGroups
            );

            $link .= ' | '.TXT_UAM_ASSIGNED_GROUPS.': ';
            $link .= implode(', ', $escapedGroups);
        }

        return $link;
    }

    /**
     * Checks if we allowed show the login form.
     *
     * @return bool
     */
    public function showLoginForm()
    {
        return $this->wordpress->isSingle() === true || $this->wordpress->isPage() === true;
    }

    /**
     * Returns the login url.
     *
     * @var array $parameters
     *
     * @return mixed
     */
    public function getLoginUrl(array $parameters = [])
    {
        $loginUrl = $this->wordpress->getBlogInfo('wpurl').'/wp-login.php';
        $loginUrl .= (count($parameters) > 0) ? '?'.http_build_query($parameters) : '';
        return $this->wordpress->applyFilters('uam_login_form_url', $loginUrl, $parameters);
    }

    /**
     * Returns the login redirect url.
     *
     * @return mixed
     */
    public function getRedirectLoginUrl()
    {
        $loginUrl = $this->wordpress->getBlogInfo('wpurl')
            .'/wp-login.php?redirect_to='.urlencode($_SERVER['REQUEST_URI']);
        return $this->wordpress->applyFilters('uam_login_url', $loginUrl);
    }

    /**
     * Returns the user login name.
     *
     * @return string
     */
    public function getUserLogin()
    {
        $userLogin = $this->getRequestParameter('log');
        return $this->wordpress->escHtml(stripslashes($userLogin));
    }


    /*
     * Functions for the redirection and files.
     */

    /**
     * Returns the post by the given url.
     *
     * @param string $url The url of the post(attachment).
     *
     * @return int
     */
    public function getPostIdByUrl($url)
    {
        $postUrls = (array)$this->cache->getFromCache(self::POST_URL_CACHE_KEY);

        if (isset($postUrls[$url]) === true) {
            return $postUrls[$url];
        }

        $postUrls[$url] = null;

        //Filter edit string
        $newUrlPieces = preg_split('/-e[0-9]{1,}/', $url);
        $newUrl = (count($newUrlPieces) === 2) ? $newUrlPieces[0].$newUrlPieces[1] : $newUrlPieces[0];

        //Filter size
        $newUrlPieces = preg_split('/-[0-9]{1,}x[0-9]{1,}(_[a-z])?/', $newUrl);
        $newUrl = (count($newUrlPieces) === 2) ? $newUrlPieces[0].$newUrlPieces[1] : $newUrlPieces[0];
        $newUrl = preg_replace('/\-pdf\.jpg$/', '.pdf', $newUrl);

        $query = $this->database->prepare(
            "SELECT ID
            FROM {$this->database->getPostsTable()}
            WHERE guid = '%s'
            LIMIT 1",
            $newUrl
        );

        $dbPost = $this->database->getRow($query);

        if ($dbPost !== null) {
            $postUrls[$url] = $dbPost->ID;
            $this->cache->addToCache(self::POST_URL_CACHE_KEY, $postUrls);
        }

        return $postUrls[$url];
    }

    /**
     * Returns the file object by the given type and url.
     *
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl  The file url.
     *
     * @return null|FileObject
     */
    private function getFileSettingsByType($objectType, $objectUrl)
    {
        $fileObject = null;

        if ($objectType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            $uploadDirs = $this->wordpress->getUploadDir();
            $uploadDir = str_replace(ABSPATH, '/', $uploadDirs['basedir']);
            $regex = '/.*'.str_replace('/', '\/', $uploadDir).'\//i';
            $cleanObjectUrl = preg_replace($regex, '', $objectUrl);
            $uploadUrl = str_replace('/files', $uploadDir, $uploadDirs['baseurl']);
            $objectUrl = rtrim($uploadUrl, '/').'/'.ltrim($cleanObjectUrl, '/');

            $post = $this->objectHandler->getPost($this->getPostIdByUrl($objectUrl));

            if ($post !== false
                && $post->post_type === ObjectHandler::ATTACHMENT_OBJECT_TYPE
            ) {
                $multiPath = str_replace('/files', $uploadDir, $uploadDirs['baseurl']);

                $fileObject = $this->fileObjectFactory->createFileObject(
                    $post->ID,
                    $objectType,
                    $uploadDirs['basedir'].str_replace($multiPath, '', $objectUrl),
                    $this->wordpress->attachmentIsImage($post->ID)
                );
            }
        } else {
            $extraParameter = $this->getRequestParameter('uamextra');

            $fileObject = $this->wordpress->applyFilters(
                'uam_get_file_settings_by_type',
                $fileObject,
                $objectType,
                $objectUrl,
                $extraParameter
            );
        }

        return $fileObject;
    }

    /**
     * Delivers the content of the requested file.
     *
     * @param string $objectType The type of the requested file.
     * @param string $objectUrl  The file url.
     */
    public function getFile($objectType, $objectUrl)
    {
        $fileObject = $this->getFileSettingsByType($objectType, $objectUrl);

        if ($fileObject === null) {
            return;
        }

        if ($this->accessHandler->checkObjectAccess($fileObject->getType(), $fileObject->getId()) === true) {
            $file = $fileObject->getFile();
        } elseif ($fileObject->isImage() === true) {
            $realPath = $this->config->getRealPath();
            $file = $realPath.'assets/gfx/noAccessPic.png';
        } else {
            $this->wordpress->wpDie(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);
            return;
        }

        $this->fileHandler->getFile($file, $fileObject->isImage());
    }

    /**
     * Redirects the user to his destination.
     *
     * @param bool $checkPosts
     */
    public function redirectUser($checkPosts = true)
    {
        if ($checkPosts === true) {
            $posts = (array)$this->wordpress->getWpQuery()->get_posts();

            foreach ($posts as $post) {
                if ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID)) {
                    return;
                }
            }
        }

        $permalink = null;
        $redirect = $this->config->getRedirect();

        if ($redirect === 'custom_page') {
            $redirectCustomPage = $this->config->getRedirectCustomPage();
            $post = $this->objectHandler->getPost($redirectCustomPage);
            $url = null;

            if ($post !== false) {
                $url = $post->guid;
                $permalink = $this->wordpress->getPageLink($post);
            }
        } elseif ($redirect === 'custom_url') {
            $url = $this->config->getRedirectCustomUrl();
        } else {
            $url = $this->wordpress->getHomeUrl('/');
        }

        $currentUrl = $this->util->getCurrentUrl();

        if ($url !== null && $url !== $currentUrl && $permalink !== $currentUrl) {
            $this->wordpress->wpRedirect($url);
            $this->php->callExit();
        }
    }

    /**
     * Redirects to a page or to content.
     *
     * @param string $headers    The headers which are given from wordpress.
     * @param object $pageParams The params of the current page.
     *
     * @return string
     */
    public function redirect($headers, $pageParams)
    {
        $fileUrl = $this->getRequestParameter('uamgetfile');
        $fileType = $this->getRequestParameter('uamfiletype');

        if ($fileUrl !== null && $fileType !== null) {
            $this->getFile($fileType, $fileUrl);
        } elseif ($this->config->atAdminPanel() === false
            && $this->config->getRedirect() !== 'false'
        ) {
            $objectType = null;
            $objectId = null;

            if (isset($pageParams->query_vars['p']) === true) {
                $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $objectId = $pageParams->query_vars['p'];
            } elseif (isset($pageParams->query_vars['page_id']) === true) {
                $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $objectId = $pageParams->query_vars['page_id'];
            } elseif (isset($pageParams->query_vars['cat_id']) === true) {
                $objectType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
                $objectId = $pageParams->query_vars['cat_id'];
            } elseif (isset($pageParams->query_vars['name']) === true) {
                $postableTypes = implode('\',\'', $this->objectHandler->getPostTypes());

                $query = $this->database->prepare(
                    "SELECT ID
                    FROM {$this->database->getPostsTable()}
                    WHERE post_name = %s
                      AND post_type IN ('{$postableTypes}')",
                    $pageParams->query_vars['name']
                );

                $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $objectId = (int)$this->database->getVariable($query);
            } elseif (isset($pageParams->query_vars['pagename']) === true) {
                $object = $this->wordpress->getPageByPath($pageParams->query_vars['pagename']);

                if ($object !== null) {
                    $objectType = $object->post_type;
                    $objectId = $object->ID;
                }
            }

            if ($this->accessHandler->checkObjectAccess($objectType, $objectId) === false) {
                $this->redirectUser(false);
            }
        }

        return $headers;
    }

    /**
     * Returns the url for a locked file.
     *
     * @param string  $url The base url.
     * @param integer $id  The _iId of the file.
     *
     * @return string
     */
    public function getFileUrl($url, $id)
    {
        if ($this->config->isPermalinksActive() === false && $this->config->lockFile() === true) {
            $post = $this->objectHandler->getPost($id);

            if ($post !== null) {
                $type = explode('/', $post->post_mime_type);
                $type = (isset($type[1]) === true) ? $type[1] : $type[0];

                $lockedFileTypes = $this->config->getLockedFileTypes();
                $fileTypes = explode(',', $lockedFileTypes);

                if ($lockedFileTypes === 'all' || in_array($type, $fileTypes) === true) {
                    $url = $this->wordpress->getHomeUrl('/').'?uamfiletype=attachment&uamgetfile='.$url;
                }
            }
        }

        return $url;
    }

    /**
     * Caches the urls for the post for a later lookup.
     *
     * @param string $url  The url of the post.
     * @param object $post The post object.
     *
     * @return string
     */
    public function cachePostLinks($url, $post)
    {
        $postUrls = (array)$this->cache->getFromCache(self::POST_URL_CACHE_KEY);
        $postUrls[$url] = $post->ID;
        $this->cache->addToCache(self::POST_URL_CACHE_KEY, $postUrls);
        return $url;
    }

    /**
     * Filter for Yoast SEO Plugin
     *
     * Hides the url from the site map if the user has no access
     *
     * @param string $url    The url to check
     * @param string $type   The object type
     * @param object $object The object
     *
     * @return false|string
     */
    public function getWpSeoUrl($url, $type, $object)
    {
        return ($this->accessHandler->checkObjectAccess($type, $object->ID) === true) ? $url : false;
    }
}
