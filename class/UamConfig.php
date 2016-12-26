<?php
/**
 * UamAccessHandler.php
 *
 * The UamConfig class file.
 *
 * PHP versions 5
 *
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * The config class.
 *
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */
class UamConfig
{
    const ADMIN_OPTIONS_NAME = 'uamAdminOptions';
    protected $_aAdminOptions = null;
    protected $_aWpOptions = array();

    /**
     * Returns the WordPress options.
     *
     * @param string $sOption
     *
     * @return mixed
     */
    public function getWpOption($sOption)
    {
        if (!isset($this->_aWpOptions[$sOption])) {
            $this->_aWpOptions[$sOption] = get_option($sOption);
        }

        return $this->_aWpOptions[$sOption];
    }

    /**
     * Returns the string value of a string option.
     *
     * @param string $sOptionName
     *
     * @return string
     */
    protected function _getStringOption($sOptionName)
    {
        $aOptions = $this->getAdminOptions();
        return isset($aOptions[$sOptionName]) ? (string)$aOptions[$sOptionName] : '';
    }

    /**
     * Returns the boolean value for an boolean option.
     *
     * @param string $sOptionName
     *
     * @return bool
     */
    protected function _getBooleanOption($sOptionName)
    {
        $aOptions = $this->getAdminOptions();
        return (isset($aOptions[$sOptionName]) && $aOptions[$sOptionName] === 'true');
    }

    /**
     * Returns the current settings
     *
     * @return array
     */
    public function getAdminOptions()
    {
        if ($this->_aAdminOptions === null) {
            $aUamAdminOptions = array(
                'hide_post_title' => 'false',
                'post_title' => __('No rights!', 'user-access-manager'),
                'post_content' => __(
                    'Sorry you have no rights to view this post!',
                    'user-access-manager'
                ),
                'hide_post' => 'false',
                'hide_post_comment' => 'false',
                'post_comment_content' => __(
                    'Sorry no rights to view comments!',
                    'user-access-manager'
                ),
                'post_comments_locked' => 'false',
                'hide_page_title' => 'false',
                'page_title' => __('No rights!', 'user-access-manager'),
                'page_content' => __(
                    'Sorry you have no rights to view this page!',
                    'user-access-manager'
                ),
                'hide_page' => 'false',
                'hide_page_comment' => 'false',
                'page_comment_content' => __(
                    'Sorry no rights to view comments!',
                    'user-access-manager'
                ),
                'page_comments_locked' => 'false',
                'redirect' => 'false',
                'redirect_custom_page' => '',
                'redirect_custom_url' => '',
                'lock_recursive' => 'true',
                'authors_has_access_to_own' => 'true',
                'authors_can_add_posts_to_groups' => 'false',
                'lock_file' => 'false',
                'file_pass_type' => 'random',
                'lock_file_types' => 'all',
                'download_type' => 'fopen',
                'locked_file_types' => 'zip,rar,tar,gz',
                'not_locked_file_types' => 'gif,jpg,jpeg,png',
                'blog_admin_hint' => 'true',
                'blog_admin_hint_text' => '[L]',
                'hide_empty_categories' => 'true',
                'protect_feed' => 'true',
                'show_post_content_before_more' => 'false',
                'full_access_role' => 'administrator'
            );

            $aUamOptions = $this->getWpOption(self::ADMIN_OPTIONS_NAME);

            if (!empty($aUamOptions)) {
                foreach ($aUamOptions as $sKey => $mOption) {
                    $aUamAdminOptions[$sKey] = $mOption;
                }
            }

            update_option(self::ADMIN_OPTIONS_NAME, $aUamAdminOptions);
            $this->_aAdminOptions = $aUamAdminOptions;
        }

        return $this->_aAdminOptions;
    }

    /**
     * Returns the option value if the option exists otherwise true.
     *
     * @param string $sOptionName
     *
     * @return bool
     */
    protected function _hideObject($sOptionName)
    {
        $aOptions = $this->getAdminOptions();

        if (isset($aOptions[$sOptionName])) {
            return $this->_getBooleanOption($sOptionName);
        }

        return true;
    }

    /**
     * @param string $sObjectType
     *
     * @return bool
     */
    public function hideObjectType($sObjectType)
    {
        return $this->_hideObject('hide_'.$sObjectType);
    }

    /**
     * @param string $sObjectType
     *
     * @return bool
     */
    public function hideObjectTypeTitle($sObjectType)
    {
        return $this->_hideObject('hide_'.$sObjectType.'_title');
    }

    /**
     * @param string $sObjectType
     *
     * @return bool
     */
    public function hideObjectTypeComments($sObjectType)
    {
        return $this->_hideObject($sObjectType.'_comments_locked');
    }

    /**
     * @param string $sObjectType
     *
     * @return string
     */
    public function getObjectTypeTitle($sObjectType)
    {
        return $this->_getStringOption($sObjectType.'_title');
    }

    /**
     * @param string $sObjectType
     *
     * @return string
     */
    public function getObjectTypeContent($sObjectType)
    {
        return $this->_getStringOption($sObjectType.'_content');
    }

    /**
     * @param string $sObjectType
     *
     * @return string
     */
    public function getObjectTypeCommentContent($sObjectType)
    {
        return $this->_getStringOption($sObjectType . '_comment_content');
    }

    /**
     * @return bool
     */
    public function hidePostTitle()
    {
        return $this->_getBooleanOption('hide_post_title');
    }

    /**
     * @return string
     */
    public function getPostTitle()
    {
        return $this->_getStringOption('post_title');
    }

    /**
     * @return string
     */
    public function getPostContent()
    {
        return $this->_getStringOption('post_content');
    }

    /**
     * @return bool
     */
    public function hidePost()
    {
        return $this->_getBooleanOption('hide_post');
    }

    /**
     * @return bool
     */
    public function hidePostComment()
    {
        return $this->_getBooleanOption('hide_post_comment');
    }

    /**
     * @return string
     */
    public function getPostCommentContent()
    {
        return $this->_getStringOption('post_comment_content');
    }

    /**
     * @return bool
     */
    public function isPostCommentsLocked()
    {
        return $this->_getBooleanOption('post_comments_locked');
    }

    /**
     * @return bool
     */
    public function hidePageTitle()
    {
        return $this->_getBooleanOption('hide_page_title');
    }

    /**
     * @return string
     */
    public function getPageTitle()
    {
        return $this->_getStringOption('page_title');
    }

    /**
     * @return string
     */
    public function getPageContent()
    {
        return $this->_getStringOption('page_content');
    }

    /**
     * @return bool
     */
    public function hidePage()
    {
        return $this->_getBooleanOption('hide_page');
    }

    /**
     * @return bool
     */
    public function hidePageComment()
    {
        return $this->_getBooleanOption('hide_page_comment');
    }

    /**
     * @return string
     */
    public function getPageCommentContent()
    {
        return $this->_getStringOption('page_comment_content');
    }

    /**
     * @return bool
     */
    public function isPageCommentsLocked()
    {
        return $this->_getBooleanOption('page_comments_locked');
    }

    /**
     * @return string
     */
    public function getRedirect()
    {
        return $this->_getStringOption('redirect');
    }

    /**
     * @return string
     */
    public function getRedirectCustomPage()
    {
        return $this->_getStringOption('redirect_custom_page');
    }

    /**
     * @return string
     */
    public function getRedirectCustomUrl()
    {
        return $this->_getStringOption('redirect_custom_url');
    }

    /**
     * @return bool
     */
    public function lockRecursive()
    {
        return $this->_getBooleanOption('lock_recursive');
    }

    /**
     * @return bool
     */
    public function authorsHasAccessToOwn()
    {
        return $this->_getBooleanOption('authors_has_access_to_own');
    }

    /**
     * @return bool
     */
    public function authorsCanAddPostsToGroups()
    {
        return $this->_getBooleanOption('authors_can_add_posts_to_groups');
    }

    /**
     * @return bool
     */
    public function lockFile()
    {
        return $this->_getBooleanOption('lock_file');
    }

    /**
     * @return string
     */
    public function getFilePassType()
    {
        return $this->_getStringOption('file_pass_type');
    }

    /**
     * @return string
     */
    public function getLockFileTypes()
    {
        return $this->_getStringOption('lock_file_types');
    }

    /**
     * @return string
     */
    public function getDownloadType()
    {
        return $this->_getStringOption('download_type');
    }

    /**
     * @return string
     */
    public function getLockedFileTypes()
    {
        return $this->_getStringOption('locked_file_types');
    }

    /**
     * @return string
     */
    public function getNotLockedFileTypes()
    {
        return $this->_getStringOption('not_locked_file_types');
    }

    /**
     * @return bool
     */
    public function blogAdminHint()
    {
        return $this->_getBooleanOption('blog_admin_hint');
    }

    /**
     * @return string
     */
    public function getBlogAdminHintText()
    {
        return $this->_getStringOption('blog_admin_hint_text');
    }

    /**
     * @return bool
     */
    public function hideEmptyCategories()
    {
        return $this->_getBooleanOption('hide_empty_categories');
    }

    /**
     * @return bool
     */
    public function protectFeed()
    {
        return $this->_getBooleanOption('protect_feed');
    }

    /**
     * @return bool
     */
    public function showPostContentBeforeMore()
    {
        return $this->_getBooleanOption('show_post_content_before_more');
    }

    /**
     * @return string
     */
    public function getFullAccessRole()
    {
        return $this->_getStringOption('full_access_role');
    }
}