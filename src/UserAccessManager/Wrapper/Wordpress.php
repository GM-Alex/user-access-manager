<?php
namespace UserAccessManager\Wrapper;

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
     * @see is_post_type_hierarchical()
     *
     * @param string $sType
     *
     * @return bool
     */
    public function isPostTypeHierarchical($sType)
    {
        return is_post_type_hierarchical($sType);
    }

    /**
     * @see get_userdata()
     *
     * @param string $sId
     *
     * @return false|\WP_User
     */
    public function getUserData($sId)
    {
        return get_userdata($sId);
    }

    /**
     * @see get_post_types()
     *
     * @param array  $aArguments
     * @param string $sOutput
     * @param string $sOperator
     *
     * @return array
     */
    public function getPostTypes(array $aArguments = array(), $sOutput = 'names', $sOperator = 'and')
    {
        return get_post_types($aArguments, $sOutput, $sOperator);
    }

    /**
     * @see get_taxonomies()
     *
     * @param array  $aArguments
     * @param string $sOutput
     * @param string $sOperator
     *
     * @return array
     */
    public function getTaxonomies(array $aArguments = array(), $sOutput = 'names', $sOperator = 'and')
    {
        return get_taxonomies($aArguments, $sOutput, $sOperator);
    }

    /**
     * @see get_post()
     *
     * @param string $sId The post id.
     * @param string $sOutput
     * @param string $sFilter
     *
     * @return \WP_Post|array|null
     */
    public function getPost($sId, $sOutput = OBJECT, $sFilter = 'raw')
    {
        return get_post($sId, $sOutput, $sFilter);
    }

    /**
     * @see get_term()
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
        return get_term($sId, $sTaxonomy, $sOutput, $sFilter);
    }


    /**
     * @see dbDelta()
     *
     * @param array|string $mQueries
     * @param bool         $blExecute
     *
     * @return array
     */
    public function dbDelta($mQueries = '', $blExecute = true)
    {
        return dbDelta($mQueries, $blExecute);
    }

    /**
     * @see switch_to_blog()
     *
     * @param integer $iBlogId
     *
     * @return int|true
     */
    public function switchToBlog($iBlogId)
    {
        return switch_to_blog($iBlogId);
    }

    /**
     * @see is_multisite()
     *
     * @return bool
     */
    public function isMultiSite()
    {
        return is_multisite();
    }

    /**
     * @see do_action()
     *
     * @param string $sTag
     * @param mixed  $mArguments
     */
    public function doAction($sTag, $mArguments = '')
    {
        do_action($sTag, $mArguments);
    }

    /**
     * @see add_action()
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
        return add_action($sTag, $mFunctionToAdd, $iPriority, $iAcceptedArguments);
    }

    /**
     * @see has_filter()
     *
     * @param string        $sTag
     * @param bool|callable $mFunctionToCheck
     *
     * @return bool|false|int
     */
    public function hasFilter($sTag, $mFunctionToCheck = false)
    {
        return has_filter($sTag, $mFunctionToCheck);
    }

    /**
     * @see add_filter()
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
        return add_filter($sTag, $mFunctionToAdd, $iPriority, $iAcceptedArguments);
    }

    /**
     * @see remove_filter()
     *
     * @param string   $sTag
     * @param callable $mFunctionToRemove
     * @param int      $iPriority
     *
     * @return bool
     */
    public function removeFilter($sTag, $mFunctionToRemove, $iPriority = 10)
    {
        return remove_filter($sTag, $mFunctionToRemove, $iPriority);
    }

    /**
     * @see add_option()
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
        return add_option($sOption, $mValue, $sDeprecated, $mAutoload);
    }

    /**
     * @see delete_option()
     *
     * @param string $sOption
     *
     * @return bool
     */
    public function deleteOption($sOption)
    {
        return delete_option($sOption);
    }

    /**
     * @param string      $sOption
     * @param mixed       $mValue
     * @param string|bool $mAutoload
     *
     * @return bool
     */
    public function updateOption($sOption, $mValue = '', $mAutoload = 'yes')
    {
        return update_option($sOption, $mValue, $mAutoload);
    }

    /**
     * @see get_option()
     *
     * @param string $sOption
     * @param bool   $blDefault
     *
     * @return mixed
     */
    public function getOption($sOption, $blDefault = false)
    {
        return get_option($sOption, $blDefault);
    }

    /**
     * @see is_super_admin()
     *
     * @param bool $sUserId
     *
     * @return bool
     */
    public function isSuperAdmin($sUserId = false)
    {
        return is_super_admin($sUserId);
    }

    /**
     * @see wp_get_current_user()
     *
     * @return \WP_User
     */
    public function getCurrentUser()
    {
        return wp_get_current_user();
    }

    /**
     * @see get_allowed_mime_types()
     *
     * @param int|\WP_User $mUser
     *
     * @return array
     */
    public function getAllowedMimeTypes($mUser = null)
    {
        return get_allowed_mime_types($mUser);
    }

    /**
     * @see wp_upload_dir()
     *
     * @param string $sTime
     * @param bool   $blCreateDir
     * @param bool   $blRefreshCache
     *
     * @return array
     */
    public function getUploadDir($sTime = null, $blCreateDir = true, $blRefreshCache = false)
    {
        return wp_upload_dir($sTime, $blCreateDir, $blRefreshCache);
    }

    /**
     * @see home_url()
     *
     * @param string $sPath
     * @param string   $sScheme
     *
     * @return string
     */
    public function getHomeUrl($sPath = '', $sScheme = null)
    {
        return home_url($sPath, $sScheme);
    }

    /**
     * @see wp_parse_id_list()
     *
     * @param array|string $mList
     *
     * @return array
     */
    public function parseIdList($mList)
    {
        return wp_parse_id_list($mList);
    }

    /**
     * @see wp_die()
     *
     * @param string $sMessage
     * @param string $sTitle
     * @param array  $aArguments
     */
    public function wpDie($sMessage = '', $sTitle = '', array $aArguments = array())
    {
        wp_die($sMessage, $sTitle, $aArguments);
    }

    /**
     * @see is_feed()
     *
     * @param string|array $mFeeds
     *
     * @return bool
     */
    public function isFeed($mFeeds = '')
    {
        return is_feed($mFeeds);
    }

    /**
     * @see is_user_logged_in()
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return is_user_logged_in();
    }

    /**
     * @see get_page_by_path()
     *
     * @param string $sPagePath
     * @param string $sOutput
     * @param string $sPostType
     *
     * @return array|null|\WP_Post
     */
    public function getPageByPath($sPagePath, $sOutput = OBJECT, $sPostType = 'page')
    {
        return get_page_by_path($sPagePath, $sOutput, $sPostType);
    }

    /**
     * @see wp_redirect()
     *
     * @param string $sLocation
     * @param int    $iStatus
     *
     * @return bool
     */
    public function wpRedirect($sLocation, $iStatus = 302)
    {
        return wp_redirect($sLocation, $iStatus);
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
        return get_page_link($mPost, $blLeaveName, $blSample);
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
}