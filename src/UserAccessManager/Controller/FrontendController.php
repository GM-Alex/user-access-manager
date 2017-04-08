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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
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
    protected $Database;

    /**
     * @var Cache
     */
    protected $Cache;

    /**
     * @var Util
     */
    protected $Util;

    /**
     * @var ObjectHandler
     */
    protected $ObjectHandler;

    /**
     * @var FileHandler
     */
    protected $FileHandler;

    /**
     * @var AccessHandler
     */
    protected $AccessHandler;

    /**
     * FrontendController constructor.
     *
     * @param Php           $Php
     * @param Wordpress     $Wordpress
     * @param Config        $Config
     * @param Database      $Database
     * @param Util          $Util
     * @param Cache         $Cache
     * @param ObjectHandler $ObjectHandler
     * @param AccessHandler $AccessHandler
     * @param FileHandler   $FileHandler
     */
    public function __construct(
        Php $Php,
        Wordpress $Wordpress,
        Config $Config,
        Database $Database,
        Util $Util,
        Cache $Cache,
        ObjectHandler $ObjectHandler,
        AccessHandler $AccessHandler,
        FileHandler $FileHandler
    ) {
        parent::__construct($Php, $Wordpress, $Config);
        $this->Database = $Database;
        $this->Util = $Util;
        $this->Cache = $Cache;
        $this->ObjectHandler = $ObjectHandler;
        $this->AccessHandler = $AccessHandler;
        $this->FileHandler = $FileHandler;
    }

    /**
     * Functions for other content.
     */

    /**
     * Register all other styles.
     */
    protected function registerStylesAndScripts()
    {
        $sUrlPath = $this->Config->getUrlPath();

        $this->Wordpress->registerStyle(
            self::HANDLE_STYLE_LOGIN_FORM,
            $sUrlPath.'assets/css/uamLoginForm.css',
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
        $this->Wordpress->enqueueStyle(self::HANDLE_STYLE_LOGIN_FORM);
    }

    /*
     * Functions for the blog content.
     */

    /**
     * Manipulates the wordpress query object to filter content.
     *
     * @param \WP_Query $WpQuery The wordpress query object.
     */
    public function parseQuery($WpQuery)
    {
        $aExcludedPosts = $this->AccessHandler->getExcludedPosts();

        if (count($aExcludedPosts) > 0) {
            $WpQuery->query_vars['post__not_in'] = array_unique(
                array_merge($WpQuery->query_vars['post__not_in'], $aExcludedPosts)
            );
        }
    }

    /**
     * Returns the admin hint.
     *
     * @param string  $sObjectType The object type.
     * @param integer $iObjectId   The object id we want to check.
     * @param string  $sText       The text on which we want to append the hint.
     *
     * @return string
     */
    public function adminOutput($sObjectType, $iObjectId, $sText = null)
    {
        $sOutput = '';

        if ($this->Config->atAdminPanel() === false
            && $this->Config->blogAdminHint() === true
        ) {
            $sHintText = $this->Config->getBlogAdminHintText();

            if ($sText !== null && $this->Util->endsWith($sText, $sHintText) === true) {
                return $sOutput;
            }

            if ($this->AccessHandler->userIsAdmin($this->Wordpress->getCurrentUser()->ID) === true
                && count($this->AccessHandler->getUserGroupsForObject($sObjectType, $iObjectId)) > 0
            ) {
                $sOutput .= $sHintText;
            }
        }

        return $sOutput;
    }

    /**
     * Returns the login bar.
     *
     * @return string
     */
    public function getLoginFormHtml()
    {
        $sLoginForm = '';

        if ($this->Wordpress->isUserLoggedIn() === false) {
            $sLoginForm = $this->getIncludeContents('LoginForm.php');
        }

        return $this->Wordpress->applyFilters('uam_login_form', $sLoginForm);
    }

    /**
     * Modifies the content of the post by the given settings.
     *
     * @param \WP_Post $Post    The current post.
     * @param bool     $blLocked
     *
     * @return object|null
     */
    protected function processPost(\WP_Post $Post, &$blLocked = null)
    {
        $Post->post_title .= $this->adminOutput($Post->post_type, $Post->ID);
        $blLocked = ($this->AccessHandler->checkObjectAccess($Post->post_type, $Post->ID) === false);

        if ($blLocked === true) {
            if ($this->Config->hidePostType($Post->post_type) === true
                || $this->Config->atAdminPanel() === true
            ) {
                return null;
            }

            $sUamPostContent = $this->Config->getPostTypeContent($Post->post_type);
            $sUamPostContent = str_replace('[LOGIN_FORM]', $this->getLoginFormHtml(), $sUamPostContent);

            if ($Post->post_type === 'post'
                && $this->Config->showPostContentBeforeMore() === true
                && preg_match('/<!--more(.*?)?-->/', $Post->post_content, $aMatches)
            ) {
                $sUamPostContent = explode($aMatches[0], $Post->post_content)[0]." ".$sUamPostContent;
            }

            $Post->post_content = stripslashes($sUamPostContent);

            if ($this->Config->hidePostTypeTitle($Post->post_type) === true) {
                $Post->post_title = $this->Config->getPostTypeTitle($Post->post_type);
            }

            if ($this->Config->hidePostTypeComments($Post->post_type) === true) {
                $Post->comment_status = 'close';
            }
        }

        return $Post;
    }

    /**
     * The function for the the_posts filter.
     *
     * @param array $aPosts The posts.
     *
     * @return array
     */
    public function showPosts($aPosts = [])
    {
        $aShowPosts = [];

        if ($this->Wordpress->isFeed() === false || $this->Config->protectFeed() === true) {
            foreach ($aPosts as $Post) {
                if ($Post !== null) {
                    $Post = $this->processPost($Post);

                    if ($Post !== null) {
                        $aShowPosts[] = $Post;
                    }
                }
            }
        }

        return $aShowPosts;
    }

    /**
     * The function for the get_pages filter.
     *
     * @param \WP_Post[] $aPages The pages.
     *
     * @return array
     */
    public function showPages($aPages = [])
    {
        $aShowPages = [];

        foreach ($aPages as $Page) {
            $Page = $this->processPost($Page);

            if ($Page !== null) {
                $aShowPages[] = $Page;
            }
        }

        $aPages = $aShowPages;

        return $aPages;
    }

    /**
     * The function for the posts_where_paged filter.
     *
     * @param string $sQuery The where sql statement.
     *
     * @return string
     */
    public function showPostSql($sQuery)
    {
        $aExcludedPosts = $this->AccessHandler->getExcludedPosts();

        if (count($aExcludedPosts) > 0) {
            $sExcludedPostsStr = implode(',', $aExcludedPosts);
            $sQuery .= " AND {$this->Database->getPostsTable()}.ID NOT IN($sExcludedPostsStr) ";
        }

        return $sQuery;
    }

    /**
     * Function for the wp_count_posts filter.
     *
     * @param \stdClass $Counts
     * @param string    $sType
     * @param string    $sPerm
     *
     * @return \stdClass
     */
    public function showPostCount($Counts, $sType, $sPerm)
    {
        $CachedCounts = $this->Cache->getFromCache(self::POST_COUNTS_CACHE_KEY);

        if ($CachedCounts === null) {
            $aExcludedPosts = $this->AccessHandler->getExcludedPosts();

            if (count($aExcludedPosts) > 0) {
                $sExcludedPosts = implode('\', \'', $aExcludedPosts);

                $sQuery = "SELECT post_status, COUNT(*) AS num_posts 
                    FROM {$this->Database->getPostsTable()} 
                    WHERE post_type = %s
                      AND ID NOT IN ('{$sExcludedPosts}')";

                if ('readable' === $sPerm && $this->Wordpress->isUserLoggedIn() === true) {
                    $PostTypeObject = $this->Wordpress->getPostTypeObject($sType);

                    if ($this->Wordpress->currentUserCan($PostTypeObject->cap->read_private_posts) === false) {
                        $sQuery .= $this->Database->prepare(
                            ' AND (post_status != \'private\' OR (post_author = %d AND post_status = \'private\'))',
                            $this->Wordpress->getCurrentUser()->ID
                        );
                    }
                }

                $sQuery .= ' GROUP BY post_status';

                $aResults = (array)$this->Database->getResults(
                    $this->Database->prepare($sQuery, $sType),
                    ARRAY_A
                );

                foreach ($aResults as $aResult) {
                    if (isset($Counts->{$aResult['post_status']})) {
                        $Counts->{$aResult['post_status']} = $aResult['num_posts'];
                    }
                }
            }

            $CachedCounts = $Counts;
            $this->Cache->addToCache(self::POST_COUNTS_CACHE_KEY, $CachedCounts);
        }

        return $CachedCounts;
    }

    /**
     * Sets the excluded terms as argument.
     *
     * @param array $aArguments
     *
     * @return array
     */
    public function getTermArguments(array $aArguments)
    {
        $aExclude = (isset($aArguments['exclude']) === true) ?
            $this->Wordpress->parseIdList($aArguments['exclude']) : [];
        $aArguments['exclude'] = array_merge($aExclude, $this->AccessHandler->getExcludedTerms());
        $aArguments['exclude'] = array_unique($aArguments['exclude']);

        return $aArguments;
    }

    /**
     * The function for the comments_array filter.
     *
     * @param \WP_Comment[] $aComments The comments.
     *
     * @return array
     */
    public function showComment($aComments = [])
    {
        $aShowComments = [];

        foreach ($aComments as $Comment) {
            $Post = $this->ObjectHandler->getPost($Comment->comment_post_ID);

            if ($Post !== false
                && $this->AccessHandler->checkObjectAccess($Post->post_type, $Post->ID) === false
            ) {
                if ($this->Config->hidePostTypeComments($Post->post_type) === true
                    || $this->Config->hidePostType($Post->post_type) === true
                    || $this->Config->atAdminPanel() === true
                ) {
                    continue;
                }

                $Comment->comment_content = $this->Config->getPostTypeCommentContent($Post->post_type);
            }

            $aShowComments[] = $Comment;
        }

        return $aShowComments;
    }

    /**
     * The function for the get_ancestors filter.
     *
     * @param array  $aAncestors
     * @param int    $sObjectId
     * @param string $sObjectType
     *
     * @return array
     */
    public function showAncestors($aAncestors, $sObjectId, $sObjectType)
    {
        if ($this->Config->lockRecursive() === true
            && $this->AccessHandler->checkObjectAccess($sObjectType, $sObjectId) === false
        ) {
            return [];
        }

        foreach ($aAncestors as $sKey => $aAncestorId) {
            if ($this->AccessHandler->checkObjectAccess($sObjectType, $aAncestorId) === false) {
                unset($aAncestors[$sKey]);
            }
        }

        return $aAncestors;
    }

    /**
     * The function for the get_previous_post_where and
     * the get_next_post_where filter.
     *
     * @param string $sQuery The current sql string.
     *
     * @return string
     */
    public function showNextPreviousPost($sQuery)
    {
        $aExcludedPosts = $this->AccessHandler->getExcludedPosts();

        if (count($aExcludedPosts) > 0) {
            $sExcludedPosts = implode(', ', $aExcludedPosts);
            $sQuery .= " AND p.ID NOT IN ({$sExcludedPosts}) ";
        }

        return $sQuery;
    }

    /**
     * Returns the post count for the term.
     *
     * @param string $sTermType
     * @param int    $iTermId
     *
     * @return int
     */
    protected function getVisibleElementsCount($sTermType, $iTermId)
    {
        $iCount = 0;

        $aTerms = [$iTermId => $iTermId];
        $aTermTreeMap = $this->ObjectHandler->getTermTreeMap();

        if (isset($aTermTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$sTermType]) === true
            && isset($aTermTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$sTermType][$iTermId]) === true
        ) {
            $aTerms += $aTermTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$sTermType][$iTermId];
        }

        $aPosts = [];
        $aTermPostMap = $this->ObjectHandler->getTermPostMap();

        foreach ($aTerms as $iTermId) {
            if (isset($aTermPostMap[$iTermId]) === true) {
                $aPosts += $aTermPostMap[$iTermId];
            }
        }

        foreach ($aPosts as $iPostId => $sPostType) {
            if ($this->Config->hidePostType($sPostType) === false
                || $this->AccessHandler->checkObjectAccess(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $iPostId) === true
            ) {
                $iCount++;
            }
        }

        return $iCount;
    }

    /**
     * Modifies the content of the term by the given settings.
     *
     * @param \WP_Term $Term     The current term.
     * @param bool     $blIsEmpty
     *
     * @return object|null
     */
    protected function processTerm($Term, &$blIsEmpty = null)
    {
        $blIsEmpty = false;

        if (($Term instanceof \WP_Term) === false) {
            return $Term;
        }

        if ($this->AccessHandler->checkObjectAccess($Term->taxonomy, $Term->term_id) === false) {
            return null;
        }

        $Term->name .= $this->adminOutput($Term->taxonomy, $Term->term_id, $Term->name);
        $Term->count = $this->getVisibleElementsCount($Term->taxonomy, $Term->term_id);

        //For categories
        if ($Term->count <= 0
            && $this->Config->atAdminPanel() === false
            && $this->Config->hideEmptyTaxonomy($Term->taxonomy) === true
        ) {
            $blIsEmpty = true;
        }

        if ($this->Config->lockRecursive() === false) {
            $CurrentTerm = $Term;

            while ($CurrentTerm->parent != 0) {
                $CurrentTerm = $this->ObjectHandler->getTerm($CurrentTerm->parent);

                if ($CurrentTerm === false) {
                    break;
                }

                $blAccess = $this->AccessHandler->checkObjectAccess(
                    $CurrentTerm->taxonomy,
                    $CurrentTerm->term_id
                );

                if ($blAccess === true) {
                    $Term->parent = $CurrentTerm->term_id;
                    break;
                }
            }
        }

        return $Term;
    }

    /**
     * The function for the get_term filter.
     *
     * @param \WP_Term $Term
     *
     * @return null|object
     */
    public function showTerm($Term)
    {
        return $this->processTerm($Term);
    }

    /**
     * The function for the get_terms filter.
     *
     * @param array $aTerms The terms.
     *
     * @return array
     */
    public function showTerms($aTerms = [])
    {
        foreach ($aTerms as $sKey => $mTerm) {
            if (is_numeric($mTerm) === true) {
                if ((int)$mTerm === 0) {
                    unset($aTerms[$sKey]);
                    continue;
                }

                $mTerm = $this->ObjectHandler->getTerm($mTerm);
            } elseif (($mTerm instanceof \WP_Term) === false) {
                continue;
            }

            $mTerm = $this->processTerm($mTerm, $blIsEmpty);

            if ($mTerm !== null && $blIsEmpty === false) {
                $aTerms[$sKey] = $mTerm;
            } else {
                unset($aTerms[$sKey]);
            }
        }

        return $aTerms;
    }

    /**
     * The function for the wp_get_nav_menu_items filter.
     *
     * @param array $aItems The menu item.
     *
     * @return array
     */
    public function showCustomMenu($aItems)
    {
        $aShowItems = [];

        foreach ($aItems as $sKey => $Item) {
            $Item->title .= $this->adminOutput($Item->object, $Item->object_id, $Item->title);

            if ($this->ObjectHandler->isPostType($Item->object) === true) {
                if ($this->AccessHandler->checkObjectAccess($Item->object, $Item->object_id) === false) {
                    if ($this->Config->hidePostType($Item->object) === true
                        || $this->Config->atAdminPanel() === true
                    ) {
                        continue;
                    }

                    if ($this->Config->hidePostTypeTitle($Item->object) === true) {
                        $Item->title = $this->Config->getPostTypeTitle($Item->object);
                    }
                }

                $aShowItems[$sKey] = $Item;
            } elseif ($this->ObjectHandler->isTaxonomy($Item->object) === true) {
                $Object = $this->ObjectHandler->getTerm($Item->object_id);
                $Category = $this->processTerm($Object, $blIsEmpty);

                if ($Category !== null && $blIsEmpty === false) {
                    $aShowItems[$sKey] = $Item;
                }
            } else {
                $aShowItems[$sKey] = $Item;
            }
        }

        return $aShowItems;
    }

    /**
     * The function for the edit_post_link filter.
     *
     * @param string  $sLink   The edit link.
     * @param integer $iPostId The _iId of the post.
     *
     * @return string
     */
    public function showGroupMembership($sLink, $iPostId)
    {
        $aUserGroups = $this->AccessHandler->getFilteredUserGroupsForObject(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $iPostId
        );

        if (count($aUserGroups) > 0) {
            $aEscapedGroups = array_map(
                function (UserGroup $Group) {
                    return htmlentities($Group->getName());
                },
                $aUserGroups
            );

            $sLink .= ' | '.TXT_UAM_ASSIGNED_GROUPS.': ';
            $sLink .= implode(', ', $aEscapedGroups);
        }

        return $sLink;
    }

    /**
     * Checks if we allowed show the login form.
     *
     * @return bool
     */
    public function showLoginForm()
    {
        return $this->Wordpress->isSingle() === true || $this->Wordpress->isPage() === true;
    }

    /**
     * Returns the login url.
     *
     * @return mixed
     */
    public function getLoginUrl()
    {
        $sLoginUrl = $this->Wordpress->getBlogInfo('wpurl').'/wp-login.php';
        return $this->Wordpress->applyFilters('uam_login_form_url', $sLoginUrl);
    }

    /**
     * Returns the login redirect url.
     *
     * @return mixed
     */
    public function getRedirectLoginUrl()
    {
        $sLoginUrl = $this->Wordpress->getBlogInfo('wpurl')
            .'/wp-login.php?redirect_to='.urlencode($_SERVER['REQUEST_URI']);
        return $this->Wordpress->applyFilters('uam_login_url', $sLoginUrl);
    }

    /**
     * Returns the user login name.
     *
     * @return string
     */
    public function getUserLogin()
    {
        $sUserLogin = $this->getRequestParameter('log');
        return $this->Wordpress->escHtml(stripslashes($sUserLogin));
    }


    /*
     * Functions for the redirection and files.
     */

    /**
     * Returns the post by the given url.
     *
     * @param string $sUrl The url of the post(attachment).
     *
     * @return object The post.
     */
    public function getPostIdByUrl($sUrl)
    {
        $aPostUrls = (array)$this->Cache->getFromCache(self::POST_URL_CACHE_KEY);

        if (isset($aPostUrls[$sUrl]) === true) {
            return $aPostUrls[$sUrl];
        }

        $aPostUrls[$sUrl] = null;

        //Filter edit string
        $aNewUrlPieces = preg_split('/-e[0-9]{1,}/', $sUrl);
        $sNewUrl = (count($aNewUrlPieces) === 2) ? $aNewUrlPieces[0].$aNewUrlPieces[1] : $aNewUrlPieces[0];

        //Filter size
        $aNewUrlPieces = preg_split('/-[0-9]{1,}x[0-9]{1,}/', $sNewUrl);
        $sNewUrl = (count($aNewUrlPieces) === 2) ? $aNewUrlPieces[0].$aNewUrlPieces[1] : $aNewUrlPieces[0];

        $sQuery = $this->Database->prepare(
            "SELECT ID
            FROM {$this->Database->getPostsTable()}
            WHERE guid = '%s'
            LIMIT 1",
            $sNewUrl
        );

        $DbPost = $this->Database->getRow($sQuery);

        if ($DbPost !== null) {
            $aPostUrls[$sUrl] = $DbPost->ID;
            $this->Cache->addToCache(self::POST_URL_CACHE_KEY, $aPostUrls);
        }

        return $aPostUrls[$sUrl];
    }

    /**
     * Returns the file object by the given type and url.
     *
     * @param string $sObjectType The type of the requested file.
     * @param string $sObjectUrl  The file url.
     *
     * @return object|null
     */
    protected function getFileSettingsByType($sObjectType, $sObjectUrl)
    {
        $Object = null;

        if ($sObjectType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            $aUploadDir = $this->Wordpress->getUploadDir();
            $sUploadDir = str_replace(ABSPATH, '/', $aUploadDir['basedir']);
            $sRegex = '/.*'.str_replace('/', '\/', $sUploadDir).'\//i';
            $sCleanObjectUrl = preg_replace($sRegex, '', $sObjectUrl);
            $sUploadUrl = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
            $sObjectUrl = rtrim($sUploadUrl, '/').'/'.ltrim($sCleanObjectUrl, '/');

            $Post = $this->ObjectHandler->getPost($this->getPostIdByUrl($sObjectUrl));

            if ($Post !== null
                && $Post->post_type === ObjectHandler::ATTACHMENT_OBJECT_TYPE
            ) {
                $Object = new \stdClass();
                $Object->id = $Post->ID;
                $Object->isImage = $this->Wordpress->attachmentIsImage($Post->ID);
                $Object->type = $sObjectType;
                $sMultiPath = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
                $Object->file = $aUploadDir['basedir'].str_replace($sMultiPath, '', $sObjectUrl);
            }
        }

        return $Object;
    }

    /**
     * Delivers the content of the requested file.
     *
     * @param string $sObjectType The type of the requested file.
     * @param string $sObjectUrl  The file url.
     *
     * @return null
     */
    public function getFile($sObjectType, $sObjectUrl)
    {
        $Object = $this->getFileSettingsByType($sObjectType, $sObjectUrl);

        if ($Object === null) {
            return null;
        }

        $sFile = null;

        if ($this->AccessHandler->checkObjectAccess($Object->type, $Object->id) === true) {
            $sFile = $Object->file;
        } elseif ($Object->isImage === true) {
            $sRealPath = $this->Config->getRealPath();
            $sFile = $sRealPath.'gfx/noAccessPic.png';
        } else {
            $this->Wordpress->wpDie(TXT_UAM_NO_RIGHTS);
            return null;
        }

        return $this->FileHandler->getFile($sFile, $Object->isImage);
    }

    /**
     * Redirects the user to his destination.
     *
     * @param bool $blCheckPosts
     */
    public function redirectUser($blCheckPosts = true)
    {
        if ($blCheckPosts === true) {
            $aPosts = (array)$this->Wordpress->getWpQuery()->get_posts();

            foreach ($aPosts as $Post) {
                if ($this->AccessHandler->checkObjectAccess($Post->post_type, $Post->ID)) {
                    return;
                }
            }
        }

        $sPermalink = null;
        $sRedirect = $this->Config->getRedirect();

        if ($sRedirect === 'custom_page') {
            $sRedirectCustomPage = $this->Config->getRedirectCustomPage();
            $Post = $this->ObjectHandler->getPost($sRedirectCustomPage);
            $sUrl = null;

            if ($Post !== false) {
                $sUrl = $Post->guid;
                $sPermalink = $this->Wordpress->getPageLink($Post);
            }
        } elseif ($sRedirect === 'custom_url') {
            $sUrl = $this->Config->getRedirectCustomUrl();
        } else {
            $sUrl = $this->Wordpress->getHomeUrl('/');
        }

        $sCurrentUrl = $this->Util->getCurrentUrl();

        if ($sUrl !== null && $sUrl !== $sCurrentUrl && $sPermalink !== $sCurrentUrl) {
            $this->Wordpress->wpRedirect($sUrl);
            return;
        }
    }

    /**
     * Redirects to a page or to content.
     *
     * @param string $sHeaders    The headers which are given from wordpress.
     * @param object $PageParams The params of the current page.
     *
     * @return string
     */
    public function redirect($sHeaders, $PageParams)
    {
        $sFileUrl = $this->getRequestParameter('uamgetfile');
        $sFileType = $this->getRequestParameter('uamfiletype');

        if ($sFileUrl !== null && $sFileType !== null) {
            $this->getFile($sFileType, $sFileUrl);
        } elseif ($this->Config->atAdminPanel() === false
            && $this->Config->getRedirect() !== 'false'
        ) {
            $ObjectType = null;
            $iObjectId = null;

            if (isset($PageParams->query_vars['p']) === true) {
                $ObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $iObjectId = $PageParams->query_vars['p'];
            } elseif (isset($PageParams->query_vars['page_id']) === true) {
                $ObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $iObjectId = $PageParams->query_vars['page_id'];
            } elseif (isset($PageParams->query_vars['cat_id']) === true) {
                $ObjectType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
                $iObjectId = $PageParams->query_vars['cat_id'];
            } elseif (isset($PageParams->query_vars['name']) === true) {
                $sPostableTypes = implode('\',\'', $this->ObjectHandler->getPostTypes());

                $sQuery = $this->Database->prepare(
                    "SELECT ID
                    FROM {$this->Database->getPostsTable()}
                    WHERE post_name = %s
                      AND post_type IN ('{$sPostableTypes}')",
                    $PageParams->query_vars['name']
                );

                $ObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $iObjectId = (int)$this->Database->getVariable($sQuery);
            } elseif (isset($PageParams->query_vars['pagename']) === true) {
                $Object = $this->Wordpress->getPageByPath($PageParams->query_vars['pagename']);

                if ($Object !== null) {
                    $ObjectType = $Object->post_type;
                    $iObjectId = $Object->ID;
                }
            }

            if ($this->AccessHandler->checkObjectAccess($ObjectType, $iObjectId) === false) {
                $this->redirectUser(false);
            }
        }

        return $sHeaders;
    }

    /**
     * Returns the url for a locked file.
     *
     * @param string  $sUrl The base url.
     * @param integer $iId  The _iId of the file.
     *
     * @return string
     */
    public function getFileUrl($sUrl, $iId)
    {
        if ($this->Config->isPermalinksActive() === false && $this->Config->lockFile() === true) {
            $Post = $this->ObjectHandler->getPost($iId);

            if ($Post !== null) {
                $aType = explode('/', $Post->post_mime_type);
                $sType = (isset($aType[1]) === true) ? $aType[1] : $aType[0];

                $sLockedFileTypes = $this->Config->getLockedFileTypes();
                $aFileTypes = explode(',', $sLockedFileTypes);

                if ($sLockedFileTypes === 'all' || in_array($sType, $aFileTypes) === true) {
                    $sUrl = $this->Wordpress->getHomeUrl('/').'?uamfiletype=attachment&uamgetfile='.$sUrl;
                }
            }
        }

        return $sUrl;
    }

    /**
     * Caches the urls for the post for a later lookup.
     *
     * @param string $sUrl  The url of the post.
     * @param object $Post The post object.
     *
     * @return string
     */
    public function cachePostLinks($sUrl, $Post)
    {
        $aPostUrls = (array)$this->Cache->getFromCache(self::POST_URL_CACHE_KEY);
        $aPostUrls[$sUrl] = $Post->ID;
        $this->Cache->addToCache(self::POST_URL_CACHE_KEY, $aPostUrls);
        return $sUrl;
    }

    /**
     * Filter for Yoast SEO Plugin
     *
     * Hides the url from the site map if the user has no access
     *
     * @param string $sUrl    The url to check
     * @param string $sType   The object type
     * @param object $Object The object
     *
     * @return false|string
     */
    public function getWpSeoUrl($sUrl, $sType, $Object)
    {
        return ($this->AccessHandler->checkObjectAccess($sType, $Object->ID) === true) ? $sUrl : false;
    }
}
