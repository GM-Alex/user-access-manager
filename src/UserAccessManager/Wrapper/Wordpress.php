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
     * @param string $sId     The post id.
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
     * Returns the current user.
     *
     * @return \WP_User
     */
    public function getCurrentUser()
    {
        return wp_get_current_user();
    }
}