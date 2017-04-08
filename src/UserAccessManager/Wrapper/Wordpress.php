<?php
/**
 * Wordpress.php
 *
 * The Wordpress class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Wrapper;

/**
 * Class Wordpress
 *
 * @package UserAccessManager\Wrapper
 */
class Wordpress
{
    /**
     * Returns the database.
     *
     * @return \wpdb
     */
    public function getDatabase()
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Returns true if web server is nginx.
     *
     * @return bool
     */
    public function isNginx()
    {
        global $is_nginx;
        return $is_nginx;
    }

    /**
     * @see \is_post_type_hierarchical()
     *
     * @param string $sPostType
     *
     * @return bool
     */
    public function isPostTypeHierarchical($sPostType)
    {
        return \is_post_type_hierarchical($sPostType);
    }

    /**
     * @see \is_taxonomy_hierarchical()
     *
     * @param string $sTaxonomy
     *
     * @return bool
     */
    public function isTaxonomyHierarchical($sTaxonomy)
    {
        return \is_taxonomy_hierarchical($sTaxonomy);
    }

    /**
     * @see \get_userdata()
     *
     * @param string $sId
     *
     * @return false|\WP_User
     */
    public function getUserData($sId)
    {
        return \get_userdata($sId);
    }

    /**
     * @see \get_post_types()
     *
     * @param string|array $mArguments
     * @param string       $sOutput
     * @param string       $sOperator
     *
     * @return array
     */
    public function getPostTypes($mArguments = [], $sOutput = 'names', $sOperator = 'and')
    {
        return \get_post_types($mArguments, $sOutput, $sOperator);
    }

    /**
     * @see \get_taxonomies()
     *
     * @param array  $aArguments
     * @param string $sOutput
     * @param string $sOperator
     *
     * @return array
     */
    public function getTaxonomies(array $aArguments = [], $sOutput = 'names', $sOperator = 'and')
    {
        return \get_taxonomies($aArguments, $sOutput, $sOperator);
    }

    /**
     * @see \get_taxonomy()
     *
     * @param string $sTaxonomy
     *
     * @return false|\WP_Taxonomy
     */
    public function getTaxonomy($sTaxonomy)
    {
        return \get_taxonomy($sTaxonomy);
    }

    /**
     * @see \get_post()
     *
     * @param string $sId The post id.
     * @param string $sOutput
     * @param string $sFilter
     *
     * @return \WP_Post|array|null
     */
    public function getPost($sId, $sOutput = OBJECT, $sFilter = 'raw')
    {
        return \get_post($sId, $sOutput, $sFilter);
    }

    /**
     * @see \get_post_type_object()
     *
     * @param string $sPostType
     *
     * @return null|\WP_Post_Type
     */
    public function getPostTypeObject($sPostType)
    {
        return \get_post_type_object($sPostType);
    }

    /**
     * @see \get_term()
     *
     * @param string $sId
     * @param string $sTaxonomy
     * @param string $sOutput
     * @param string $sFilter
     *
     * @return array|null|\WP_Error|\WP_Term
     */
    public function getTerm($sId, $sTaxonomy = '', $sOutput = OBJECT, $sFilter = 'raw')
    {
        return \get_term($sId, $sTaxonomy, $sOutput, $sFilter);
    }


    /**
     * @see \dbDelta()
     *
     * @param array|string $mQueries
     * @param bool         $blExecute
     *
     * @return array
     */
    public function dbDelta($mQueries = '', $blExecute = true)
    {
        return \dbDelta($mQueries, $blExecute);
    }

    /**
     * @see \switch_to_blog()
     *
     * @param integer $iBlogId
     *
     * @return int|true
     */
    public function switchToBlog($iBlogId)
    {
        return \switch_to_blog($iBlogId);
    }

    /**
     * @see \is_multisite()
     *
     * @return bool
     */
    public function isMultiSite()
    {
        return \is_multisite();
    }

    /**
     * @see \do_action()
     *
     * @param string $sTag
     * @param mixed  $mArguments
     */
    public function doAction($sTag, $mArguments = '')
    {
        \do_action($sTag, $mArguments);
    }

    /**
     * @see \add_action()
     *
     * @param string   $sTag
     * @param callable $mFunctionToAdd
     * @param int      $iPriority
     * @param int      $iAcceptedArguments
     *
     * @return true
     */
    public function addAction($sTag, $mFunctionToAdd, $iPriority = 10, $iAcceptedArguments = 1)
    {
        return \add_action($sTag, $mFunctionToAdd, $iPriority, $iAcceptedArguments);
    }

    /**
     * @see \has_filter()
     *
     * @param string        $sTag
     * @param bool|callable $mFunctionToCheck
     *
     * @return bool|false|int
     */
    public function hasFilter($sTag, $mFunctionToCheck = false)
    {
        return \has_filter($sTag, $mFunctionToCheck);
    }

    /**
     * @see \add_filter()
     *
     * @param string   $sTag
     * @param callable $mFunctionToAdd
     * @param int      $iPriority
     * @param int      $iAcceptedArguments
     *
     * @return true
     */
    public function addFilter($sTag, $mFunctionToAdd, $iPriority = 10, $iAcceptedArguments = 1)
    {
        return \add_filter($sTag, $mFunctionToAdd, $iPriority, $iAcceptedArguments);
    }

    /**
     * @see \remove_filter()
     *
     * @param string   $sTag
     * @param callable $mFunctionToRemove
     * @param int      $iPriority
     *
     * @return bool
     */
    public function removeFilter($sTag, $mFunctionToRemove, $iPriority = 10)
    {
        return \remove_filter($sTag, $mFunctionToRemove, $iPriority);
    }

    /**
     * @see \add_option()
     *
     * @param string      $sOption
     * @param mixed       $mValue
     * @param string      $sDeprecated
     * @param string|bool $mAutoload
     *
     * @return bool
     */
    public function addOption($sOption, $mValue = '', $sDeprecated = '', $mAutoload = 'yes')
    {
        return \add_option($sOption, $mValue, $sDeprecated, $mAutoload);
    }

    /**
     * @see \delete_option()
     *
     * @param string $sOption
     *
     * @return bool
     */
    public function deleteOption($sOption)
    {
        return \delete_option($sOption);
    }

    /**
     * @see \update_option()
     * 
     * @param string      $sOption
     * @param mixed       $mValue
     * @param string|bool $mAutoload
     *
     * @return bool
     */
    public function updateOption($sOption, $mValue = '', $mAutoload = 'yes')
    {
        return \update_option($sOption, $mValue, $mAutoload);
    }

    /**
     * @see \get_option()
     *
     * @param string $sOption
     * @param bool   $blDefault
     *
     * @return mixed
     */
    public function getOption($sOption, $blDefault = false)
    {
        return \get_option($sOption, $blDefault);
    }

    /**
     * @see \is_super_admin()
     *
     * @param bool $sUserId
     *
     * @return bool
     */
    public function isSuperAdmin($sUserId = false)
    {
        return \is_super_admin($sUserId);
    }

    /**
     * @see \wp_get_current_user()
     *
     * @return \WP_User
     */
    public function getCurrentUser()
    {
        return \wp_get_current_user();
    }

    /**
     * @see \get_allowed_mime_types()
     *
     * @param int|\WP_User $mUser
     *
     * @return array
     */
    public function getAllowedMimeTypes($mUser = null)
    {
        return \get_allowed_mime_types($mUser);
    }

    /**
     * @see \wp_upload_dir()
     *
     * @param string $sTime
     * @param bool   $blCreateDir
     * @param bool   $blRefreshCache
     *
     * @return array
     */
    public function getUploadDir($sTime = null, $blCreateDir = true, $blRefreshCache = false)
    {
        return \wp_upload_dir($sTime, $blCreateDir, $blRefreshCache);
    }

    /**
     * @see \home_url()
     *
     * @param string $sPath
     * @param string $sScheme
     *
     * @return string
     */
    public function getHomeUrl($sPath = '', $sScheme = null)
    {
        return \home_url($sPath, $sScheme);
    }

    /**
     * @see \wp_parse_id_list()
     *
     * @param array|string $mList
     *
     * @return array
     */
    public function parseIdList($mList)
    {
        return \wp_parse_id_list($mList);
    }

    /**
     * @see \wp_die()
     *
     * @param string $sMessage
     * @param string $sTitle
     * @param array  $aArguments
     */
    public function wpDie($sMessage = '', $sTitle = '', array $aArguments = [])
    {
        \wp_die($sMessage, $sTitle, $aArguments);
    }

    /**
     * @see \is_feed()
     *
     * @param string|array $mFeeds
     *
     * @return bool
     */
    public function isFeed($mFeeds = '')
    {
        return \is_feed($mFeeds);
    }

    /**
     * @see \is_user_logged_in()
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return \is_user_logged_in();
    }

    /**
     * @see \get_page_by_path()
     *
     * @param string $sPagePath
     * @param string $sOutput
     * @param string $sPostType
     *
     * @return array|null|\WP_Post
     */
    public function getPageByPath($sPagePath, $sOutput = OBJECT, $sPostType = 'page')
    {
        return \get_page_by_path($sPagePath, $sOutput, $sPostType);
    }

    /**
     * @see \wp_redirect()
     *
     * @param string $sLocation
     * @param int    $iStatus
     *
     * @return bool
     */
    public function wpRedirect($sLocation, $iStatus = 302)
    {
        return \wp_redirect($sLocation, $iStatus);
    }

    /**
     * @param bool|int|\WP_Post $mPost
     * @param bool              $blLeaveName
     * @param bool              $blSample
     *
     * @return string The page permalink.
     */
    public function getPageLink($mPost = false, $blLeaveName = false, $blSample = false)
    {
        return \get_page_link($mPost, $blLeaveName, $blSample);
    }

    /**
     * Returns the wp_query object.
     *
     * @return \WP_Query
     */
    public function getWpQuery()
    {
        global $wp_query;
        return $wp_query;
    }

    /**
     * @see \is_admin()
     *
     * @return bool
     */
    public function isAdmin()
    {
        return \is_admin();
    }

    /**
     * @see \wp_create_nonce()
     *
     * @param int|string $mAction
     *
     * @return string
     */
    public function createNonce($mAction)
    {
        return \wp_create_nonce($mAction);
    }

    /**
     * @see \wp_nonce_field()
     *
     * @param int|string $mAction
     * @param string     $sName
     * @param bool       $blReferrer
     * @param bool       $blEcho
     *
     * @return string
     */
    public function getNonceField($mAction = -1, $sName = '_wpnonce', $blReferrer = true, $blEcho = true)
    {
        return \wp_nonce_field($mAction, $sName, $blReferrer, $blEcho);
    }

    /**
     * @see \wp_verify_nonce()
     *
     * @param string     $sNonce
     * @param string|int $mAction
     *
     * @return false|int
     */
    public function verifyNonce($sNonce, $mAction = -1)
    {
        return \wp_verify_nonce($sNonce, $mAction);
    }

    /**
     * Returns the wordpress roles.
     *
     * @return \WP_Roles
     */
    public function getRoles()
    {
        global $wp_roles;
        return $wp_roles;
    }

    /**
     * @see \add_menu_page()
     *
     * @param string $sPageTitle
     * @param string $sMenuTitle
     * @param string $sCapability
     * @param string $sMenuSlug
     * @param mixed  $cFunction
     * @param string $sIconUrl
     * @param null   $iPosition
     *
     * @return string
     */
    public function addMenuPage($sPageTitle, $sMenuTitle, $sCapability, $sMenuSlug, $cFunction = '', $sIconUrl = '', $iPosition = null)
    {
        return \add_menu_page($sPageTitle, $sMenuTitle, $sCapability, $sMenuSlug, $cFunction, $sIconUrl, $iPosition);
    }

    /**
     * @see \add_submenu_page()
     *
     * @param string $sParentSlug
     * @param string $sPageTitle
     * @param string $sMenuTitle
     * @param string $sCapability
     * @param string $sMenuSlug
     * @param string $cFunction
     *
     * @return false|string
     */
    public function addSubMenuPage($sParentSlug, $sPageTitle, $sMenuTitle, $sCapability, $sMenuSlug, $cFunction = '')
    {
        return \add_submenu_page($sParentSlug, $sPageTitle, $sMenuTitle, $sCapability, $sMenuSlug, $cFunction);
    }

    /**
     * @see \add_meta_box()
     *
     * @param string                  $sId
     * @param string                  $sTitle
     * @param callable                $cCallback
     * @param string|array|\WP_Screen $mScreen
     * @param string                  $sContext
     * @param string                  $sPriority
     * @param array                   $aCallbackArguments
     */
    public function addMetaBox(
        $sId,
        $sTitle,
        $cCallback,
        $mScreen = null,
        $sContext = 'advanced',
        $sPriority = 'default',
        $aCallbackArguments = null
    )
    {
        \add_meta_box($sId, $sTitle, $cCallback, $mScreen, $sContext, $sPriority, $aCallbackArguments);
    }

    /**
     * @see \get_pages()
     *
     * @param array|string $mArguments
     *
     * @return array|false
     */
    public function getPages($mArguments)
    {
        return \get_pages($mArguments);
    }

    /**
     * @see \wp_register_style()
     *
     * @param string           $sHandle
     * @param string           $sSource
     * @param array            $aDepends
     * @param string|bool|null $mVersion
     * @param string           $sMedia
     *
     * @return bool
     */
    public function registerStyle($sHandle, $sSource, $aDepends = [], $mVersion = false, $sMedia = 'all')
    {
        return \wp_register_style($sHandle, $sSource, $aDepends, $mVersion, $sMedia);
    }

    /**
     * @see \wp_register_script()
     *
     * @param string           $sHandle
     * @param string           $sSource
     * @param array            $aDepends
     * @param string|bool|null $mVersion
     * @param bool             $blInFooter
     *
     * @return bool
     */
    public function registerScript($sHandle, $sSource, $aDepends = [], $mVersion = false, $blInFooter = false)
    {
        return \wp_register_script($sHandle, $sSource, $aDepends, $mVersion, $blInFooter);
    }

    /**
     * @see \wp_enqueue_style()
     *
     * @param string           $sHandle
     * @param string           $sSource
     * @param array            $aDepends
     * @param string|bool|null $mVersion
     * @param string           $sMedia
     */
    public function enqueueStyle($sHandle, $sSource = '', $aDepends = [], $mVersion = false, $sMedia = 'all')
    {
        \wp_enqueue_style($sHandle, $sSource, $aDepends, $mVersion, $sMedia);
    }

    /**
     * @see \wp_enqueue_script()
     *
     * @param string           $sHandle
     * @param string           $sSource
     * @param array            $aDepends
     * @param string|bool|null $mVersion
     * @param bool             $blInFooter
     */
    public function enqueueScript($sHandle, $sSource = '', $aDepends = [], $mVersion = false, $blInFooter = false)
    {
        \wp_enqueue_script($sHandle, $sSource, $aDepends, $mVersion, $blInFooter);
    }

    /**
     * Returns the wordpress meta boxes.
     *
     * @return array
     */
    public function getMetaBoxes()
    {
        global $wp_meta_boxes;
        return $wp_meta_boxes;
    }

    /**
     * Sets the wordpress meta boxes.
     *
     * @param array $aWpMetaBoxes
     */
    public function setMetaBoxes(array $aWpMetaBoxes)
    {
        global $wp_meta_boxes;
        $wp_meta_boxes= $aWpMetaBoxes;
    }

    /**
     * @see \get_sites()
     *
     * @param array $aArguments
     *
     * @return array
     */
    public function getSites(array $aArguments = [])
    {
        if (function_exists('get_sites')) {
            return \get_sites($aArguments);
        }

        return [];
    }

    /**
     * @see \apply_filters()
     *
     * @param string $sTag
     * @param mixed  $mValue
     *
     * @return mixed
     */
    public function applyFilters($sTag, $mValue)
    {
        return \apply_filters($sTag, $mValue);
    }

    /**
     * @see \get_bloginfo()
     *
     * @param string $sShow
     * @param string $sFilter
     *
     * @return string
     */
    public function getBlogInfo($sShow = '', $sFilter = 'raw')
    {
        return \get_bloginfo($sShow, $sFilter);
    }

    /**
     * @see \esc_html()
     *
     * @param string $sText
     *
     * @return string
     */
    public function escHtml($sText)
    {
        return \esc_html($sText);
    }

    /**
     * @see \is_single()
     *
     * @param int|string|array $mPost
     *
     * @return bool
     */
    public function isSingle($mPost = '')
    {
        return \is_single($mPost);
    }

    /**
     * @see \is_page()
     *
     * @param int|string|array $mPage
     *
     * @return bool
     */
    public function isPage($mPage = '')
    {
        return \is_page($mPage);
    }

    /**
     * @see \WP_PLUGIN_DIR
     *
     * @return string
     */
    public function getPluginDir()
    {
        return WP_PLUGIN_DIR;
    }

    /**
     * @see \plugins_url()
     *
     * @param string $sPath
     * @param string $sPlugin
     *
     * @return string
     */
    public function pluginsUrl($sPath = '', $sPlugin = '')
    {
        return \plugins_url($sPath, $sPlugin);
    }

    /**
     * @see \plugin_basename()
     *
     * @param $sFile
     *
     * @return string
     */
    public function pluginBasename($sFile)
    {
        return \plugin_basename($sFile);
    }

    /**
     * @see wp_attachment_is_image()
     *
     * @param int|\WP_Post $mPost
     *
     * @return bool
     */
    public function attachmentIsImage($mPost)
    {
        return wp_attachment_is_image($mPost);
    }

    /**
     * @see current_user_can()
     *
     * @param string $sCapability
     *
     * @return bool
     */
    public function currentUserCan($sCapability)
    {
        return current_user_can($sCapability);
    }

}