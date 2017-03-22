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

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var Cache
     */
    protected $_oCache;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var FileHandler
     */
    protected $_oFileHandler;

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * FrontendController constructor.
     *
     * @param Php           $oPhp
     * @param Wordpress     $oWordpress
     * @param Config        $oConfig
     * @param Database      $oDatabase
     * @param Util          $oUtil
     * @param Cache         $oCache
     * @param ObjectHandler $oObjectHandler
     * @param AccessHandler $oAccessHandler
     * @param FileHandler   $oFileHandler
     */
    public function __construct(
        Php $oPhp,
        Wordpress $oWordpress,
        Config $oConfig,
        Database $oDatabase,
        Util $oUtil,
        Cache $oCache,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler,
        FileHandler $oFileHandler
    )
    {
        parent::__construct($oPhp, $oWordpress, $oConfig);
        $this->_oDatabase = $oDatabase;
        $this->_oUtil = $oUtil;
        $this->_oCache = $oCache;
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oFileHandler = $oFileHandler;
    }

    /**
     * Functions for other content.
     */

    /**
     * Register all other styles.
     */
    protected function registerStylesAndScripts()
    {
        $sUrlPath = $this->_oConfig->getUrlPath();

        $this->_oWordpress->registerStyle(
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
        wp_enqueue_style(self::HANDLE_STYLE_LOGIN_FORM);
    }

    /*
     * Functions for the blog content.
     */

    /**
     * Manipulates the wordpress query object to filter content.
     *
     * @param \WP_Query $oWpQuery The wordpress query object.
     */
    public function parseQuery($oWpQuery)
    {
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();

        if (count($aExcludedPosts) > 0) {
            $oWpQuery->query_vars['post__not_in'] = array_merge(
                $oWpQuery->query_vars['post__not_in'],
                $aExcludedPosts
            );
        }
    }

    /**
     * Modifies the content of the post by the given settings.
     *
     * @param object $oPost The current post.
     *
     * @return object|null
     */
    protected function _processPost($oPost)
    {
        $sPostType = $oPost->post_type;

        if ($this->_oConfig->hideObjectType($sPostType) === true || $this->_oConfig->atAdminPanel()) {
            if ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->post_title .= $this->adminOutput($oPost->post_type, $oPost->ID);
                return $oPost;
            }
        } else {
            if (!$this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                $oPost->isLocked = true;

                $sUamPostContent = $this->_oConfig->getObjectTypeContent($sPostType);
                $sUamPostContent = str_replace('[LOGIN_FORM]', $this->getLoginFormHtml(), $sUamPostContent);

                if ($this->_oConfig->hideObjectTypeTitle($sPostType) === true) {
                    $oPost->post_title = $this->_oConfig->getObjectTypeTitle($sPostType);
                }

                if ($this->_oConfig->hideObjectTypeComments($sPostType) === false) {
                    $oPost->comment_status = 'close';
                }

                if ($sPostType === 'post'
                    && $this->_oConfig->showPostContentBeforeMore() === true
                    && preg_match('/<!--more(.*?)?-->/', $oPost->post_content, $aMatches)
                ) {
                    $oPost->post_content = explode($aMatches[0], $oPost->post_content, 2);
                    $sUamPostContent = $oPost->post_content[0]." ".$sUamPostContent;
                }

                $oPost->post_content = stripslashes($sUamPostContent);
            }

            $oPost->post_title .= $this->adminOutput($oPost->post_type, $oPost->ID);

            return $oPost;
        }

        return null;
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

        if ($this->_oWordpress->isFeed() === false
            || ($this->_oConfig->protectFeed() === true && $this->_oWordpress->isFeed()) === true
        ) {
            foreach ($aPosts as $iPostId) {
                if ($iPostId !== null) {
                    $oPost = $this->_processPost($iPostId);

                    if ($oPost !== null) {
                        $aShowPosts[] = $oPost;
                    }
                }
            }

            $aPosts = $aShowPosts;
        }

        return $aPosts;
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
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();

        if (count($aExcludedPosts) > 0) {
            $sExcludedPostsStr = implode(',', $aExcludedPosts);
            $sQuery .= " AND {$this->_oDatabase->getPostsTable()}.ID NOT IN($sExcludedPostsStr) ";
        }

        return $sQuery;
    }

    /**
     * Function for the wp_count_posts filter.
     *
     * @param \stdClass $oCounts
     * @param string    $sType
     *
     * @return \stdClass
     */
    public function showPostCount($oCounts, $sType)
    {
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();

        if (isset($aExcludedPosts[$sType])) {
            $oCounts->publish -= count($aExcludedPosts[$sType]);
        }

        return $oCounts;
    }

    /**
     * Sets the excluded terms as argument.
     *
     * @param array $aArguments
     *
     * @return array
     */
    public function getTermArguments($aArguments)
    {
        $aExclude = (isset($aArguments['exclude'])) ? $this->_oWordpress->parseIdList($aArguments['exclude']) : [];
        $aExcludedTerms = $this->_oAccessHandler->getExcludedTerms();

        if ($this->_oConfig->lockRecursive() === true) {
            $aTermTreeMap = $this->_oObjectHandler->getTermTreeMap();

            foreach ($aExcludedTerms as $sTermId) {
                if (isset($aTermTreeMap[$sTermId])) {
                    $aExcludedTerms = array_merge($aExcludedTerms, array_keys($aTermTreeMap[$sTermId]));
                }
            }
        }

        $aArguments['exclude'] = array_merge($aExclude, $aExcludedTerms);

        return $aArguments;
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

        foreach ($aItems as $oItem) {
            if ($this->_oObjectHandler->isPostType($oItem->object)) {
                $oObject = $this->_oObjectHandler->getPost($oItem->object_id);

                if ($oObject !== null) {
                    $oPost = $this->_processPost($oObject);

                    if ($oPost !== null) {
                        if (isset($oPost->isLocked)) {
                            $oItem->title = $oPost->post_title;
                        }

                        $oItem->title .= $this->adminOutput($oItem->object, $oItem->object_id);
                        $aShowItems[] = $oItem;
                    }
                }
            } elseif ($this->_oObjectHandler->isTaxonomy($oItem->object)) {
                $oObject = $this->_oObjectHandler->getTerm($oItem->object_id);
                $oCategory = $this->_processTerm($oObject);

                if ($oCategory !== null && !$oCategory->isEmpty) {
                    $oItem->title .= $this->adminOutput($oItem->object, $oItem->object_id);
                    $aShowItems[] = $oItem;
                }
            } else {
                $aShowItems[] = $oItem;
            }
        }

        return $aShowItems;
    }

    /**
     * The function for the comments_array filter.
     *
     * @param array $aComments The comments.
     *
     * @return array
     */
    public function showComment($aComments = [])
    {
        $aShowComments = [];

        foreach ($aComments as $oComment) {
            $oPost = $this->_oObjectHandler->getPost($oComment->comment_post_ID);
            $sPostType = $oPost->post_type;

            if ($this->_oConfig->hideObjectTypeComments($sPostType) === true
                || $this->_oConfig->hideObjectType($sPostType) === true
                || $this->_oConfig->atAdminPanel()
            ) {
                if ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $aShowComments[] = $oComment;
                }
            } else {
                if (!$this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $oComment->comment_content = $this->_oConfig->getObjectTypeCommentContent($sPostType);
                }

                $aShowComments[] = $oComment;
            }
        }

        $aComments = $aShowComments;

        return $aComments;
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

        foreach ($aPages as $oPage) {
            if ($this->_oConfig->hideObjectType($oPage->post_type) === true
                || $this->_oConfig->atAdminPanel()
            ) {
                if ($this->_oAccessHandler->checkObjectAccess($oPage->post_type, $oPage->ID)) {
                    $oPage->post_title .= $this->adminOutput(
                        $oPage->post_type,
                        $oPage->ID
                    );
                    $aShowPages[] = $oPage;
                }
            } else {
                if (!$this->_oAccessHandler->checkObjectAccess($oPage->post_type, $oPage->ID)) {
                    if ($this->_oConfig->hideObjectTypeTitle($oPage->post_type) === true) {
                        $oPage->post_title = $this->_oConfig->getObjectTypeTitle($oPage->post_type);
                    }

                    $oPage->post_content = $this->_oConfig->getObjectTypeContent($oPage->post_type);
                }

                $oPage->post_title .= $this->adminOutput($oPage->post_type, $oPage->ID);
                $aShowPages[] = $oPage;
            }
        }

        $aPages = $aShowPages;

        return $aPages;
    }

    /**
     * Returns the post count for the term.
     *
     * @param int $iTermId
     *
     * @return int
     */
    protected function _getVisibleElementsCount($iTermId)
    {
        $iCount = 0;
        $aTermPostMap = $this->_oObjectHandler->getTermPostMap();

        if (isset($aTermPostMap[$iTermId])) {
            foreach ($aTermPostMap[$iTermId] as $iPostId => $sPostType) {
                if ($this->_oConfig->hideObjectType($sPostType) === false
                    || $this->_oAccessHandler->checkObjectAccess($sPostType, $iPostId)
                ) {
                    $iCount++;
                }
            }
        }

        return $iCount;
    }

    /**
     * Modifies the content of the term by the given settings.
     *
     * @param \WP_Term $oTerm The current term.
     *
     * @return object|null
     */
    protected function _processTerm($oTerm)
    {
        if (is_object($oTerm) === false) {
            return $oTerm;
        }

        $oTerm->name .= $this->adminOutput($oTerm->taxonomy, $oTerm->term_id, $oTerm->name);

        $oTerm->isEmpty = false;

        if ($this->_oAccessHandler->checkObjectAccess($oTerm->taxonomy, $oTerm->term_id)) {
            if ($this->_oConfig->hideObjectType(ObjectHandler::POST_OBJECT_TYPE) === true
                || $this->_oConfig->hideObjectType(ObjectHandler::PAGE_OBJECT_TYPE) === true
            ) {
                $iTermRequest = $oTerm->term_id;
                $oTerm->count = $this->_getVisibleElementsCount($iTermRequest);
                $iFullCount = $oTerm->count;

                if ($iFullCount <= 0) {
                    $aTermTreeMap = $this->_oObjectHandler->getTermTreeMap();

                    if (isset($aTermTreeMap[$iTermRequest])) {
                        foreach ($aTermTreeMap[$iTermRequest] as $iTermId => $sType) {
                            if ($oTerm->taxonomy === $sType) {
                                $iFullCount += $this->_getVisibleElementsCount($iTermId);

                                if ($iFullCount > 0) {
                                    break;
                                }
                            }
                        }
                    }
                }

                //For categories
                if ($iFullCount <= 0
                    && $this->_oConfig->atAdminPanel() === false
                    && $this->_oConfig->hideEmptyCategories() === true
                    && ($oTerm->taxonomy === 'term' || $oTerm->taxonomy === 'category')
                ) {
                    $oTerm->isEmpty = true;
                }

                if ($this->_oConfig->lockRecursive() === false) {
                    $oCurrentTerm = $oTerm;

                    while ($oCurrentTerm->parent != 0) {
                        $oCurrentTerm = $this->_oObjectHandler->getTerm($oCurrentTerm->parent);

                        if ($this->_oAccessHandler->checkObjectAccess($oCurrentTerm->taxonomy, $oCurrentTerm->term_id)) {
                            $oTerm->parent = $oCurrentTerm->term_id;
                            break;
                        }
                    }
                }
            }

            return $oTerm;
        }

        return null;
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
        if ($this->_oConfig->lockRecursive() === true
            && $this->_oAccessHandler->checkObjectAccess($sObjectType, $sObjectId) === false
        ) {
            return [];
        }

        foreach ($aAncestors as $sKey => $aAncestorId) {
            if (!$this->_oAccessHandler->checkObjectAccess($sObjectType, $aAncestorId) === false) {
                unset($aAncestors[$sKey]);
            }
        }

        return $aAncestors;
    }

    /**
     * The function for the get_term filter.
     *
     * @param \WP_Term $oTerm
     *
     * @return null|object
     */
    public function showTerm($oTerm)
    {
        return $this->_processTerm($oTerm);
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
        $aShowTerms = [];

        foreach ($aTerms as $mTerm) {
            if (!is_object($mTerm) && is_numeric($mTerm)) {
                if ((int)$mTerm === 0) {
                    continue;
                }

                $mTerm = $this->_oObjectHandler->getTerm($mTerm);
            }

            $mTerm = $this->_processTerm($mTerm);

            if ($mTerm !== null && is_object($mTerm) && (!isset($mTerm->isEmpty) || !$mTerm->isEmpty)) {
                $aShowTerms[$mTerm->term_id] = $mTerm;
            }
        }

        foreach ($aTerms as $sKey => $mTerm) {
            if ($mTerm === null || is_object($mTerm) && !isset($aShowTerms[$mTerm->term_id])) {
                unset($aTerms[$sKey]);
            }
        }

        return $aTerms;
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
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();

        if (count($aExcludedPosts) > 0) {
            $sExcludedPosts = implode(',', $aExcludedPosts);
            $sQuery .= " AND p.ID NOT IN({$sExcludedPosts}) ";
        }

        return $sQuery;
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

        if ($this->_oConfig->atAdminPanel() === false
            && $this->_oConfig->blogAdminHint() === true
        ) {
            $sHintText = $this->_oConfig->getBlogAdminHintText();

            if ($sText !== null && $this->_oUtil->endsWith($sText, $sHintText)) {
                return $sOutput;
            }

            $oCurrentUser = $this->_oWordpress->getCurrentUser();

            if (!isset($oCurrentUser->user_level)) {
                return $sOutput;
            }

            if ($this->_oAccessHandler->userIsAdmin($oCurrentUser->ID)
                && count($this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId)) > 0
            ) {
                $sOutput .= $sHintText;
            }
        }


        return $sOutput;
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
        $aUserGroups = $this->_oAccessHandler->getFilteredUserGroupsForObject(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $iPostId
        );

        if (count($aUserGroups) > 0) {
            $sLink .= ' | '.TXT_UAM_ASSIGNED_GROUPS.': ';

            foreach ($aUserGroups as $oGroup) {
                $sLink .= htmlentities($oGroup->getName()).', ';
            }

            $sLink = rtrim($sLink, ', ');
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
        return $this->_oWordpress->isSingle() || $this->_oWordpress->isPage();
    }

    /**
     * Returns the login url.
     *
     * @return mixed
     */
    public function getLoginUrl()
    {
        $sLoginUrl = $this->_oWordpress->getBlogInfo('wpurl').'/wp-login.php';
        return $this->_oWordpress->applyFilters('uam_login_form_url', $sLoginUrl);
    }

    /**
     * Returns the login redirect url.
     *
     * @return mixed
     */
    public function getRedirectLoginUrl()
    {
        $sLoginUrl = $this->getLoginUrl().'/wp-login.php?redirect_to='.urlencode($_SERVER['REQUEST_URI']);
        return $this->_oWordpress->applyFilters('uam_login_url', $sLoginUrl);
    }

    /**
     * Returns the user login name.
     *
     * @return string
     */
    public function getUserLogin()
    {
        $sUserLogin = $this->getRequestParameter('log');
        return $this->_oWordpress->escHtml(stripslashes($sUserLogin));
    }

    /**
     * Returns the login bar.
     *
     * @return string
     */
    public function getLoginFormHtml()
    {
        $sLoginForm = '';

        if ($this->_oWordpress->isUserLoggedIn() === false) {
            $sLoginForm = $this->_getIncludeContents('LoginForm.php');
        }

        return $this->_oWordpress->applyFilters('uam_login_form', $sLoginForm);
    }


    /*
     * Functions for the redirection and files.
     */

    /**
     * Redirects to a page or to content.
     *
     * @param string $sHeaders    The headers which are given from wordpress.
     * @param object $oPageParams The params of the current page.
     *
     * @return string
     */
    public function redirect($sHeaders, $oPageParams)
    {
        if (isset($_GET['uamgetfile']) && isset($_GET['uamfiletype'])) {
            $sFileUrl = $_GET['uamgetfile'];
            $sFileType = $_GET['uamfiletype'];
            $this->getFile($sFileType, $sFileUrl);
        } elseif (!$this->_oConfig->atAdminPanel() && $this->_oConfig->getRedirect() !== 'false') {
            $oObject = null;

            if (isset($oPageParams->query_vars['p'])) {
                $oObject = $this->_oObjectHandler->getPost($oPageParams->query_vars['p']);
                $oObjectType = $oObject->post_type;
                $iObjectId = $oObject->ID;
            } elseif (isset($oPageParams->query_vars['page_id'])) {
                $oObject = $this->_oObjectHandler->getPost($oPageParams->query_vars['page_id']);
                $oObjectType = $oObject->post_type;
                $iObjectId = $oObject->ID;
            } elseif (isset($oPageParams->query_vars['cat_id'])) {
                $oObject = $this->_oObjectHandler->getTerm($oPageParams->query_vars['cat_id']);
                $oObjectType = $oObject->taxonomy;
                $iObjectId = $oObject->term_id;
            } elseif (isset($oPageParams->query_vars['name'])) {
                $sPostableTypes = "'".implode("','", $this->_oObjectHandler->getPostTypes())."'";

                $sQuery = $this->_oDatabase->prepare(
                    "SELECT ID
                    FROM {$this->_oDatabase->getPostsTable()}
                    WHERE post_name = %s
                      AND post_type IN ({$sPostableTypes})",
                    $oPageParams->query_vars['name']
                );

                $sObjectId = $this->_oDatabase->getVariable($sQuery);

                if ($sObjectId) {
                    $oObject = $this->_oObjectHandler->getPost($sObjectId);
                }

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            } elseif (isset($oPageParams->query_vars['pagename'])) {
                $oObject = $this->_oWordpress->getPageByPath($oPageParams->query_vars['pagename']);

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            }

            if ($oObject !== null
                && isset($oObjectType)
                && isset($iObjectId)
                && !$this->_oAccessHandler->checkObjectAccess($oObjectType, $iObjectId)
            ) {
                $this->redirectUser($oObject);
            }
        }

        return $sHeaders;
    }

    /**
     * Redirects the user to his destination.
     *
     * @param object $oObject The current object we want to access.
     */
    public function redirectUser($oObject = null)
    {
        $blPostToShow = false;
        $aPosts = $this->_oWordpress->getWpQuery()->get_posts();

        if ($oObject === null && isset($aPosts)) {
            foreach ($aPosts as $oPost) {
                if ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    $blPostToShow = true;
                    break;
                }
            }
        }

        if ($blPostToShow === false) {
            $sPermalink = null;

            if ($this->_oConfig->getRedirect() === 'custom_page') {
                $sRedirectCustomPage = $this->_oConfig->getRedirectCustomPage();
                $oPost = $this->_oObjectHandler->getPost($sRedirectCustomPage);
                $sUrl = $oPost->guid;
                $sPermalink = $this->_oWordpress->getPageLink($oPost);
            } elseif ($this->_oConfig->getRedirect() === 'custom_url') {
                $sUrl = $this->_oConfig->getRedirectCustomUrl();
            } else {
                $sUrl = $this->_oWordpress->getHomeUrl('/');
            }

            $sCurrentUrl = $this->_oUtil->getCurrentUrl();

            if ($sUrl != $sCurrentUrl && $sPermalink != $sCurrentUrl) {
                $this->_oWordpress->wpRedirect($sUrl);
                exit;
            }
        }
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
        $oObject = $this->_getFileSettingsByType($sObjectType, $sObjectUrl);

        if ($oObject === null) {
            return null;
        }

        $sFile = null;

        if ($this->_oAccessHandler->checkObjectAccess($oObject->type, $oObject->id)) {
            $sFile = $oObject->file;
        } elseif ($oObject->isImage) {
            $sRealPath = $this->_oConfig->getRealPath();
            $sFile = $sRealPath.'gfx/noAccessPic.png';
        } else {
            $this->_oWordpress->wpDie(TXT_UAM_NO_RIGHTS);
        }

        $blIsImage = $oObject->isFile;

        $this->_oFileHandler->getFile($sFile, $blIsImage);
        return null;
    }

    /**
     * Returns the file object by the given type and url.
     *
     * @param string $sObjectType The type of the requested file.
     * @param string $sObjectUrl  The file url.
     *
     * @return object|null
     */
    protected function _getFileSettingsByType($sObjectType, $sObjectUrl)
    {
        $oObject = null;

        if ($sObjectType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
            $aUploadDir = wp_upload_dir();
            $sUploadDir = str_replace(ABSPATH, '/', $aUploadDir['basedir']);
            $sRegex = '/.*'.str_replace('/', '\/', $sUploadDir).'\//i';
            $sCleanObjectUrl = preg_replace($sRegex, '', $sObjectUrl);
            $sUploadUrl = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
            $sObjectUrl = $sUploadUrl.'/'.ltrim($sCleanObjectUrl, '/');
            $oPost = $this->_oObjectHandler->getPost($this->getPostIdByUrl($sObjectUrl));

            if ($oPost !== null
                && $oPost->post_type === ObjectHandler::ATTACHMENT_OBJECT_TYPE
            ) {
                $oObject = new \stdClass();
                $oObject->id = $oPost->ID;
                $oObject->isImage = wp_attachment_is_image($oPost->ID);
                $oObject->type = $sObjectType;
                $sMultiPath = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
                $oObject->file = $aUploadDir['basedir'].str_replace($sMultiPath, '', $sObjectUrl);
            }
        } else {
            $aPlObject = $this->_oObjectHandler->getPluggableObject($sObjectType);

            if (isset($aPlObject) && isset($aPlObject['getFileObject'])) {
                $oObject = $aPlObject['reference']->{$aPlObject['getFileObject']}($sObjectUrl);
            }
        }

        return $oObject;
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
        if ($this->_oConfig->isPermalinksActive() === false && $this->_oConfig->lockFile() === true) {
            $oPost = $this->_oObjectHandler->getPost($iId);
            $aType = explode('/', $oPost->post_mime_type);
            $sType = $aType[1];
            $aFileTypes = explode(',', $this->_oConfig->getLockedFileTypes());

            if ($this->_oConfig->getLockedFileTypes() === 'all' || in_array($sType, $aFileTypes)) {
                $sUrl = $this->_oWordpress->getHomeUrl('/').'?uamfiletype=attachment&uamgetfile='.$sUrl;
            }
        }

        return $sUrl;
    }

    /**
     * Returns the post by the given url.
     *
     * @param string $sUrl The url of the post(attachment).
     *
     * @return object The post.
     */
    public function getPostIdByUrl($sUrl)
    {
        $aPostUrls = (array)$this->_oCache->getFromCache(self::POST_URL_CACHE_KEY);

        if (isset($aPostUrls[$sUrl])) {
            return $aPostUrls[$sUrl];
        }

        $aPostUrls[$sUrl] = null;

        //Filter edit string
        $sNewUrl = preg_split("/-e[0-9]{1,}/", $sUrl);

        if (count($sNewUrl) === 2) {
            $sNewUrl = $sNewUrl[0].$sNewUrl[1];
        } else {
            $sNewUrl = $sNewUrl[0];
        }

        //Filter size
        $sNewUrl = preg_split("/-[0-9]{1,}x[0-9]{1,}/", $sNewUrl);

        if (count($sNewUrl) === 2) {
            $sNewUrl = $sNewUrl[0].$sNewUrl[1];
        } else {
            $sNewUrl = $sNewUrl[0];
        }

        $sQuery = $this->_oDatabase->prepare(
            "SELECT ID
            FROM {$this->_oDatabase->getPostsTable()}
            WHERE guid = '%s'
            LIMIT 1",
            $sNewUrl
        );

        $oDbPost = $this->_oDatabase->getRow($sQuery);

        if ($oDbPost !== null) {
            $aPostUrls[$sUrl] = $oDbPost->ID;
            $this->_oCache->addToCache(self::POST_URL_CACHE_KEY, $aPostUrls);
        }

        return $aPostUrls[$sUrl];
    }

    /**
     * Caches the urls for the post for a later lookup.
     *
     * @param string $sUrl  The url of the post.
     * @param object $oPost The post object.
     *
     * @return string
     */
    public function cachePostLinks($sUrl, $oPost)
    {
        $aPostUrls = (array)$this->_oCache->getFromCache(self::POST_URL_CACHE_KEY);
        $aPostUrls[$sUrl] = $oPost->ID;
        $this->_oCache->addToCache(self::POST_URL_CACHE_KEY, $aPostUrls);
        return $sUrl;
    }

    /**
     * Filter for Yoast SEO Plugin
     *
     * Hides the url from the site map if the user has no access
     *
     * @param string $sUrl    The url to check
     * @param string $sType   The object type
     * @param object $oObject The object
     *
     * @return false|string
     */
    function wpSeoUrl($sUrl, $sType, $oObject)
    {
        return ($this->_oAccessHandler->checkObjectAccess($sType, $oObject->ID) === true) ? $sUrl : false;
    }
}