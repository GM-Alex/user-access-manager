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
    protected function _registerStylesAndScripts()
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
        $this->_registerStylesAndScripts();
        $this->_oWordpress->enqueueStyle(self::HANDLE_STYLE_LOGIN_FORM);
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
            $oWpQuery->query_vars['post__not_in'] = array_unique(
                array_merge($oWpQuery->query_vars['post__not_in'], $aExcludedPosts)
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

        if ($this->_oConfig->atAdminPanel() === false
            && $this->_oConfig->blogAdminHint() === true
        ) {
            $sHintText = $this->_oConfig->getBlogAdminHintText();

            if ($sText !== null && $this->_oUtil->endsWith($sText, $sHintText) === true) {
                return $sOutput;
            }

            if ($this->_oAccessHandler->userIsAdmin($this->_oWordpress->getCurrentUser()->ID) === true
                && count($this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId)) > 0
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

        if ($this->_oWordpress->isUserLoggedIn() === false) {
            $sLoginForm = $this->_getIncludeContents('LoginForm.php');
        }

        return $this->_oWordpress->applyFilters('uam_login_form', $sLoginForm);
    }

    /**
     * Modifies the content of the post by the given settings.
     *
     * @param \WP_Post $oPost    The current post.
     * @param bool     $blLocked
     *
     * @return object|null
     */
    protected function _processPost(\WP_Post $oPost, &$blLocked = null)
    {
        $oPost->post_title .= $this->adminOutput($oPost->post_type, $oPost->ID);
        $blLocked = ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID) === false);

        if ($blLocked === true) {
            if ($this->_oConfig->hidePostType($oPost->post_type) === true
                || $this->_oConfig->atAdminPanel() === true
            ) {
                return null;
            }

            $sUamPostContent = $this->_oConfig->getPostTypeContent($oPost->post_type);
            $sUamPostContent = str_replace('[LOGIN_FORM]', $this->getLoginFormHtml(), $sUamPostContent);

            if ($oPost->post_type === 'post'
                && $this->_oConfig->showPostContentBeforeMore() === true
                && preg_match('/<!--more(.*?)?-->/', $oPost->post_content, $aMatches)
            ) {
                $sUamPostContent = explode($aMatches[0], $oPost->post_content, 2)[0]." ".$sUamPostContent;
            }

            $oPost->post_content = stripslashes($sUamPostContent);

            if ($this->_oConfig->hidePostTypeTitle($oPost->post_type) === true) {
                $oPost->post_title = $this->_oConfig->getPostTypeTitle($oPost->post_type);
            }

            if ($this->_oConfig->hidePostTypeComments($oPost->post_type) === true) {
                $oPost->comment_status = 'close';
            }
        }

        return $oPost;
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

        if ($this->_oWordpress->isFeed() === false || $this->_oConfig->protectFeed() === true) {
            foreach ($aPosts as $oPost) {
                if ($oPost !== null) {
                    $oPost = $this->_processPost($oPost);

                    if ($oPost !== null) {
                        $aShowPosts[] = $oPost;
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

        foreach ($aPages as $oPage) {
            $oPage = $this->_processPost($oPage);

            if ($oPage !== null) {
                $aShowPages[] = $oPage;
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
     * @param string    $sPerm
     *
     * @return \stdClass
     */
    public function showPostCount($oCounts, $sType, $sPerm)
    {
        $oCachedCounts = $this->_oCache->getFromCache(self::POST_COUNTS_CACHE_KEY);

        if ($oCachedCounts === null) {
            $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();

            if (count($aExcludedPosts) > 0) {
                $sExcludedPosts = implode('\', \'', $aExcludedPosts);

                $sQuery = "SELECT post_status, COUNT(*) AS num_posts 
                    FROM {$this->_oDatabase->getPostsTable()} 
                    WHERE post_type = %s
                      AND ID NOT IN ('{$sExcludedPosts}')";

                if ('readable' === $sPerm && $this->_oWordpress->isUserLoggedIn() === true) {
                    $oPostTypeObject = $this->_oWordpress->getPostTypeObject($sType);

                    if ($this->_oWordpress->currentUserCan($oPostTypeObject->cap->read_private_posts) === false) {
                        $sQuery .= $this->_oDatabase->prepare(
                            ' AND (post_status != \'private\' OR (post_author = %d AND post_status = \'private\'))',
                            $this->_oWordpress->getCurrentUser()->ID
                        );
                    }
                }

                $sQuery .= ' GROUP BY post_status';

                $aResults = (array)$this->_oDatabase->getResults(
                    $this->_oDatabase->prepare($sQuery, $sType),
                    ARRAY_A
                );

                foreach ($aResults as $aResult) {
                    if (isset($oCounts->{$aResult['post_status']})) {
                        $oCounts->{$aResult['post_status']} = $aResult['num_posts'];
                    }
                }
            }

            $oCachedCounts = $oCounts;
            $this->_oCache->addToCache(self::POST_COUNTS_CACHE_KEY, $oCachedCounts);
        }

        return $oCachedCounts;
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
            $this->_oWordpress->parseIdList($aArguments['exclude']) : [];
        $aArguments['exclude'] = array_merge($aExclude, $this->_oAccessHandler->getExcludedTerms());
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

        foreach ($aComments as $oComment) {
            $oPost = $this->_oObjectHandler->getPost($oComment->comment_post_ID);

            if ($oPost !== false
                && $this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID) === false
            ) {
                if ($this->_oConfig->hidePostTypeComments($oPost->post_type) === true
                    || $this->_oConfig->hidePostType($oPost->post_type) === true
                    || $this->_oConfig->atAdminPanel() === true
                ) {
                    continue;
                }

                $oComment->comment_content = $this->_oConfig->getPostTypeCommentContent($oPost->post_type);
            }

            $aShowComments[] = $oComment;
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
        if ($this->_oConfig->lockRecursive() === true
            && $this->_oAccessHandler->checkObjectAccess($sObjectType, $sObjectId) === false
        ) {
            return [];
        }

        foreach ($aAncestors as $sKey => $aAncestorId) {
            if ($this->_oAccessHandler->checkObjectAccess($sObjectType, $aAncestorId) === false) {
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
        $aExcludedPosts = $this->_oAccessHandler->getExcludedPosts();

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
    protected function _getVisibleElementsCount($sTermType, $iTermId)
    {
        $iCount = 0;

        $aTerms = [$iTermId => $iTermId];
        $aTermTreeMap = $this->_oObjectHandler->getTermTreeMap();

        if (isset($aTermTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$sTermType]) === true
            && isset($aTermTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$sTermType][$iTermId]) === true
        ) {
            $aTerms += $aTermTreeMap[ObjectHandler::TREE_MAP_CHILDREN][$sTermType][$iTermId];
        }

        $aPosts = [];
        $aTermPostMap = $this->_oObjectHandler->getTermPostMap();

        foreach ($aTerms as $iTermId) {
            if (isset($aTermPostMap[$iTermId]) === true) {
                $aPosts += $aTermPostMap[$iTermId];
            }
        }

        foreach ($aPosts as $iPostId => $sPostType) {
            if ($this->_oConfig->hidePostType($sPostType) === false
                || $this->_oAccessHandler->checkObjectAccess(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $iPostId) === true
            ) {
                $iCount++;
            }
        }

        return $iCount;
    }

    /**
     * Modifies the content of the term by the given settings.
     *
     * @param \WP_Term $oTerm     The current term.
     * @param bool     $blIsEmpty
     *
     * @return object|null
     */
    protected function _processTerm($oTerm, &$blIsEmpty = null)
    {
        $blIsEmpty = false;

        if (($oTerm instanceof \WP_Term) === false) {
            return $oTerm;
        }

        if ($this->_oAccessHandler->checkObjectAccess($oTerm->taxonomy, $oTerm->term_id) === false) {
            return null;
        }

        $oTerm->name .= $this->adminOutput($oTerm->taxonomy, $oTerm->term_id, $oTerm->name);
        $oTerm->count = $this->_getVisibleElementsCount($oTerm->taxonomy, $oTerm->term_id);

        //For categories
        if ($oTerm->count <= 0
            && $this->_oConfig->atAdminPanel() === false
            && $this->_oConfig->hideEmptyTaxonomy($oTerm->taxonomy) === true
        ) {
            $blIsEmpty = true;
        }

        if ($this->_oConfig->lockRecursive() === false) {
            $oCurrentTerm = $oTerm;

            while ($oCurrentTerm->parent != 0) {
                $oCurrentTerm = $this->_oObjectHandler->getTerm($oCurrentTerm->parent);

                if ($oCurrentTerm !== false
                    && $this->_oAccessHandler->checkObjectAccess($oCurrentTerm->taxonomy, $oCurrentTerm->term_id) === true
                ) {
                    $oTerm->parent = $oCurrentTerm->term_id;
                    break;
                }
            }
        }

        return $oTerm;
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
        foreach ($aTerms as $sKey => $mTerm) {
            if (is_numeric($mTerm) === true) {
                if ((int)$mTerm === 0) {
                    unset($aTerms[$sKey]);
                    continue;
                }

                $mTerm = $this->_oObjectHandler->getTerm($mTerm);
            } elseif (($mTerm instanceof \WP_Term) === false) {
                continue;
            }

            $mTerm = $this->_processTerm($mTerm, $blIsEmpty);

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

        foreach ($aItems as $sKey => $oItem) {
            $oItem->title .= $this->adminOutput($oItem->object, $oItem->object_id, $oItem->title);

            if ($this->_oObjectHandler->isPostType($oItem->object) === true) {
                if ($this->_oAccessHandler->checkObjectAccess($oItem->object, $oItem->object_id) === false) {
                    if ($this->_oConfig->hidePostType($oItem->object) === true
                        || $this->_oConfig->atAdminPanel() === true
                    ) {
                        continue;
                    }

                    if ($this->_oConfig->hidePostTypeTitle($oItem->object) === true) {
                        $oItem->title = $this->_oConfig->getPostTypeTitle($oItem->object);
                    }
                }

                $aShowItems[$sKey] = $oItem;
            } elseif ($this->_oObjectHandler->isTaxonomy($oItem->object) === true) {
                $oObject = $this->_oObjectHandler->getTerm($oItem->object_id);
                $oCategory = $this->_processTerm($oObject, $blIsEmpty);

                if ($oCategory !== null && $blIsEmpty === false) {
                    $aShowItems[$sKey] = $oItem;
                }
            } else {
                $aShowItems[$sKey] = $oItem;
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
        $aUserGroups = $this->_oAccessHandler->getFilteredUserGroupsForObject(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $iPostId
        );

        if (count($aUserGroups) > 0) {
            $aEscapedGroups = array_map(
                function (UserGroup $oGroup) {
                    return htmlentities($oGroup->getName());
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
        return $this->_oWordpress->isSingle() === true || $this->_oWordpress->isPage() === true;
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
        $sLoginUrl = $this->_oWordpress->getBlogInfo('wpurl').'/wp-login.php?redirect_to='.urlencode($_SERVER['REQUEST_URI']);
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
        $aPostUrls = (array)$this->_oCache->getFromCache(self::POST_URL_CACHE_KEY);

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
            $aUploadDir = $this->_oWordpress->getUploadDir();
            $sUploadDir = str_replace(ABSPATH, '/', $aUploadDir['basedir']);
            $sRegex = '/.*'.str_replace('/', '\/', $sUploadDir).'\//i';
            $sCleanObjectUrl = preg_replace($sRegex, '', $sObjectUrl);
            $sUploadUrl = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
            $sObjectUrl = rtrim($sUploadUrl, '/').'/'.ltrim($sCleanObjectUrl, '/');

            $oPost = $this->_oObjectHandler->getPost($this->getPostIdByUrl($sObjectUrl));

            if ($oPost !== null
                && $oPost->post_type === ObjectHandler::ATTACHMENT_OBJECT_TYPE
            ) {
                $oObject = new \stdClass();
                $oObject->id = $oPost->ID;
                $oObject->isImage = $this->_oWordpress->attachmentIsImage($oPost->ID);
                $oObject->type = $sObjectType;
                $sMultiPath = str_replace('/files', $sUploadDir, $aUploadDir['baseurl']);
                $oObject->file = $aUploadDir['basedir'].str_replace($sMultiPath, '', $sObjectUrl);
            }
        }

        return $oObject;
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

        if ($this->_oAccessHandler->checkObjectAccess($oObject->type, $oObject->id) === true) {
            $sFile = $oObject->file;
        } elseif ($oObject->isImage === true) {
            $sRealPath = $this->_oConfig->getRealPath();
            $sFile = $sRealPath.'gfx/noAccessPic.png';
        } else {
            $this->_oWordpress->wpDie(TXT_UAM_NO_RIGHTS);
            return null;
        }

        return $this->_oFileHandler->getFile($sFile, $oObject->isImage);
    }

    /**
     * Redirects the user to his destination.
     *
     * @param bool $blCheckPosts
     */
    public function redirectUser($blCheckPosts = true)
    {
        if ($blCheckPosts === true) {
            $aPosts = (array)$this->_oWordpress->getWpQuery()->get_posts();

            foreach ($aPosts as $oPost) {
                if ($this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID)) {
                    return;
                }
            }
        }

        $sPermalink = null;
        $sRedirect = $this->_oConfig->getRedirect();

        if ($sRedirect === 'custom_page') {
            $sRedirectCustomPage = $this->_oConfig->getRedirectCustomPage();
            $oPost = $this->_oObjectHandler->getPost($sRedirectCustomPage);
            $sUrl = null;

            if ($oPost !== false) {
                $sUrl = $oPost->guid;
                $sPermalink = $this->_oWordpress->getPageLink($oPost);
            }
        } elseif ($sRedirect === 'custom_url') {
            $sUrl = $this->_oConfig->getRedirectCustomUrl();
        } else {
            $sUrl = $this->_oWordpress->getHomeUrl('/');
        }

        $sCurrentUrl = $this->_oUtil->getCurrentUrl();

        if ($sUrl !== null && $sUrl !== $sCurrentUrl && $sPermalink !== $sCurrentUrl) {
            $this->_oWordpress->wpRedirect($sUrl);
            return;
        }
    }

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
        $sFileUrl = $this->getRequestParameter('uamgetfile');
        $sFileType = $this->getRequestParameter('uamfiletype');

        if ($sFileUrl !== null && $sFileType !== null) {
            $this->getFile($sFileType, $sFileUrl);
        } elseif ($this->_oConfig->atAdminPanel() === false
            && $this->_oConfig->getRedirect() !== 'false'
        ) {
            $oObjectType = null;
            $iObjectId = null;

            if (isset($oPageParams->query_vars['p']) === true) {
                $oObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $iObjectId = $oPageParams->query_vars['p'];
            } elseif (isset($oPageParams->query_vars['page_id']) === true) {
                $oObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $iObjectId = $oPageParams->query_vars['page_id'];
            } elseif (isset($oPageParams->query_vars['cat_id']) === true) {
                $oObjectType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
                $iObjectId = $oPageParams->query_vars['cat_id'];
            } elseif (isset($oPageParams->query_vars['name']) === true) {
                $sPostableTypes = implode('\',\'', $this->_oObjectHandler->getPostTypes());

                $sQuery = $this->_oDatabase->prepare(
                    "SELECT ID
                    FROM {$this->_oDatabase->getPostsTable()}
                    WHERE post_name = %s
                      AND post_type IN ('{$sPostableTypes}')",
                    $oPageParams->query_vars['name']
                );

                $oObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                $iObjectId = (int)$this->_oDatabase->getVariable($sQuery);
            } elseif (isset($oPageParams->query_vars['pagename']) === true) {
                $oObject = $this->_oWordpress->getPageByPath($oPageParams->query_vars['pagename']);

                if ($oObject !== null) {
                    $oObjectType = $oObject->post_type;
                    $iObjectId = $oObject->ID;
                }
            }

            if ($this->_oAccessHandler->checkObjectAccess($oObjectType, $iObjectId) === false) {
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
        if ($this->_oConfig->isPermalinksActive() === false && $this->_oConfig->lockFile() === true) {
            $oPost = $this->_oObjectHandler->getPost($iId);

            if ($oPost !== null) {
                $aType = explode('/', $oPost->post_mime_type);
                $sType = (isset($aType[1]) === true) ? $aType[1] : $aType[0];

                $sLockedFileTypes = $this->_oConfig->getLockedFileTypes();
                $aFileTypes = explode(',', $sLockedFileTypes);

                if ($sLockedFileTypes === 'all' || in_array($sType, $aFileTypes) === true) {
                    $sUrl = $this->_oWordpress->getHomeUrl('/').'?uamfiletype=attachment&uamgetfile='.$sUrl;
                }
            }
        }

        return $sUrl;
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
    public function getWpSeoUrl($sUrl, $sType, $oObject)
    {
        return ($this->_oAccessHandler->checkObjectAccess($sType, $oObject->ID) === true) ? $sUrl : false;
    }
}