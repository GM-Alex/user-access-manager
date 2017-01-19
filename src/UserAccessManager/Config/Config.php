<?php
/**
 * Config.php
 *
 * The Config class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Config
 *
 * @package UserAccessManager\Config
 */
class Config
{
    const ADMIN_OPTIONS_NAME = 'uamAdminOptions';

    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var ConfigParameterFactory
     */
    protected $_oConfigParameterFactory;

    /**
     * @var array
     */
    protected $_aConfigParameters = null;

    /**
     * @var array
     */
    protected $_aWpOptions = array();

    /**
     * @var bool
     */
    protected $_blAtAdminPanel = false;

    /**
     * Config constructor.
     *
     * @param Wordpress              $oWrapper
     * @param ConfigParameterFactory $oConfigParameterFactory
     */
    public function __construct(Wordpress $oWrapper, ConfigParameterFactory $oConfigParameterFactory)
    {
        $this->_oWrapper = $oWrapper;
        $this->_oConfigParameterFactory = $oConfigParameterFactory;
    }

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
            $this->_aWpOptions[$sOption] = $this->_oWrapper->getOption($sOption);
        }

        return $this->_aWpOptions[$sOption];
    }

    /**
     * Returns the current settings
     *
     * @return ConfigParameter[]
     */
    public function getConfigParameters()
    {
        if ($this->_aConfigParameters === null) {
            /**
             * @var ConfigParameter[] $aConfigParameters
             */
            $aConfigParameters = array();
            $aObjects = array(
                'post', 'page'
            );

            foreach ($aObjects as $sObject) {
                $sId = "hide_{$sObject}";
                $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId);

                $sId = "hide_{$sObject}_title";
                $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId);

                $sId = "{$sObject}_title";
                $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter(
                    $sId,
                    __('No rights!', 'user-access-manager')
                );

                $sId = "{$sObject}_content";
                $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter(
                    $sId,
                    sprintf(__('Sorry you have no rights to view this %s!', 'user-access-manager'), $sObject)
                );

                $sId = "hide_{$sObject}_comment";
                $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId);

                $sId = "{$sObject}_comment_content";
                $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter(
                    $sId,
                    __('Sorry no rights to view comments!', 'user-access-manager')
                );

                $sId = "{$sObject}_comments_locked";
                $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId);
            }

            $sId = 'redirect';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'false',
                array('false', 'custom_page', 'custom_url')
            );

            $sId = 'redirect_custom_page';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter($sId);

            $sId = 'redirect_custom_url';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter($sId);

            $sId = 'lock_recursive';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId, true);


            $sId = 'authors_has_access_to_own';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId, true);


            $sId = 'authors_can_add_posts_to_groups';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId);


            $sId = 'lock_file';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId);

            $sId = 'file_pass_type';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'random',
                array('random', 'user')
            );

            $sId = 'lock_file_types';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'all',
                array('all', 'selected', 'not_selected')
            );
            
            $sId = 'download_type';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'fopen',
                array('fopen', 'normal')
            );

            $sId = 'locked_file_types';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter(
                $sId,
                'zip,rar,tar,gz'
            );

            $sId = 'not_locked_file_types';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter(
                $sId,
                'gif,jpg,jpeg,png'
            );

            $sId = 'blog_admin_hint';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId, true);

            $sId = 'blog_admin_hint_text';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createStringConfigParameter($sId, '[L]');
            
            $sId = 'hide_empty_categories';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId, true);
            
            $sId = 'protect_feed';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId, true);

            $sId = 'show_post_content_before_more';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createBooleanConfigParameter($sId);

            $sId = 'full_access_role';
            $aConfigParameters[$sId] = $this->_oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'administrator',
                array('administrator', 'editor', 'author', 'contributor', 'subscriber')
            );

            $aCurrentOptions = (array)$this->getWpOption(self::ADMIN_OPTIONS_NAME);

            if (is_array($aCurrentOptions)) {
                foreach ($aCurrentOptions as $sKey => $mOption) {
                    $aConfigParameters[$sKey]->setValue($mOption);
                }
            }

            $this->_aConfigParameters = $aConfigParameters;
        }

        return $this->_aConfigParameters;
    }

    /**
     * Sets the new config parameters and saves them to the database.
     *
     * @param $aRawParameters
     */
    public function setConfigParameters(array $aRawParameters)
    {
        $aConfigParameters = $this->getConfigParameters();

        foreach ($aRawParameters as $sKey => $mValue) {
            if (isset($aConfigParameters[$sKey])) {
                $aConfigParameters[$sKey]->setValue($mValue);
            }
        }

        $this->_aConfigParameters = $aConfigParameters;

        $aSimpleConfigParameters = array();

        foreach ($aConfigParameters as $oParameter) {
            $aSimpleConfigParameters[$oParameter->getId()] = $oParameter->getValue();
        }

        $this->_oWrapper->updateOption(self::ADMIN_OPTIONS_NAME, $aSimpleConfigParameters);
    }

    /**
     * Returns the requested parameter value
     *
     * @param string $sParameterName
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function _getParameterValue($sParameterName)
    {
        $aOptions = $this->getConfigParameters();

        if (isset($aOptions[$sParameterName]) === false) {
            throw new \Exception("Unknown config parameter '{$sParameterName}'.");
        }

        return $aOptions[$sParameterName]->getValue();
    }

    /**
     * Returns the option value if the option exists otherwise true.
     *
     * @param string $sParameterName
     *
     * @return bool
     */
    protected function _hideObject($sParameterName)
    {
        $aOptions = $this->getConfigParameters();

        if (isset($aOptions[$sParameterName])) {
            return $aOptions[$sParameterName]->getValue();
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
        return $this->_getParameterValue($sObjectType.'_title');
    }

    /**
     * @param string $sObjectType
     *
     * @return string
     */
    public function getObjectTypeContent($sObjectType)
    {
        return $this->_getParameterValue($sObjectType.'_content');
    }

    /**
     * @param string $sObjectType
     *
     * @return string
     */
    public function getObjectTypeCommentContent($sObjectType)
    {
        return $this->_getParameterValue($sObjectType.'_comment_content');
    }

    /**
     * @return bool
     */
    public function hidePostTitle()
    {
        return $this->_getParameterValue('hide_post_title');
    }

    /**
     * @return string
     */
    public function getPostTitle()
    {
        return $this->_getParameterValue('post_title');
    }

    /**
     * @return string
     */
    public function getPostContent()
    {
        return $this->_getParameterValue('post_content');
    }

    /**
     * @return bool
     */
    public function hidePost()
    {
        return $this->_getParameterValue('hide_post');
    }

    /**
     * @return bool
     */
    public function hidePostComment()
    {
        return $this->_getParameterValue('hide_post_comment');
    }

    /**
     * @return string
     */
    public function getPostCommentContent()
    {
        return $this->_getParameterValue('post_comment_content');
    }

    /**
     * @return bool
     */
    public function isPostCommentsLocked()
    {
        return $this->_getParameterValue('post_comments_locked');
    }

    /**
     * @return bool
     */
    public function hidePageTitle()
    {
        return $this->_getParameterValue('hide_page_title');
    }

    /**
     * @return string
     */
    public function getPageTitle()
    {
        return $this->_getParameterValue('page_title');
    }

    /**
     * @return string
     */
    public function getPageContent()
    {
        return $this->_getParameterValue('page_content');
    }

    /**
     * @return bool
     */
    public function hidePage()
    {
        return $this->_getParameterValue('hide_page');
    }

    /**
     * @return bool
     */
    public function hidePageComment()
    {
        return $this->_getParameterValue('hide_page_comment');
    }

    /**
     * @return string
     */
    public function getPageCommentContent()
    {
        return $this->_getParameterValue('page_comment_content');
    }

    /**
     * @return bool
     */
    public function isPageCommentsLocked()
    {
        return $this->_getParameterValue('page_comments_locked');
    }

    /**
     * @return string
     */
    public function getRedirect()
    {
        return $this->_getParameterValue('redirect');
    }

    /**
     * @return string
     */
    public function getRedirectCustomPage()
    {
        return $this->_getParameterValue('redirect_custom_page');
    }

    /**
     * @return string
     */
    public function getRedirectCustomUrl()
    {
        return $this->_getParameterValue('redirect_custom_url');
    }

    /**
     * @return bool
     */
    public function lockRecursive()
    {
        return $this->_getParameterValue('lock_recursive');
    }

    /**
     * @return bool
     */
    public function authorsHasAccessToOwn()
    {
        return $this->_getParameterValue('authors_has_access_to_own');
    }

    /**
     * @return bool
     */
    public function authorsCanAddPostsToGroups()
    {
        return $this->_getParameterValue('authors_can_add_posts_to_groups');
    }

    /**
     * @return bool
     */
    public function lockFile()
    {
        return $this->_getParameterValue('lock_file');
    }

    /**
     * @return string
     */
    public function getFilePassType()
    {
        return $this->_getParameterValue('file_pass_type');
    }

    /**
     * @return string
     */
    public function getLockFileTypes()
    {
        return $this->_getParameterValue('lock_file_types');
    }

    /**
     * @return string
     */
    public function getDownloadType()
    {
        return $this->_getParameterValue('download_type');
    }

    /**
     * @return string
     */
    public function getLockedFileTypes()
    {
        return $this->_getParameterValue('locked_file_types');
    }

    /**
     * @return string
     */
    public function getNotLockedFileTypes()
    {
        return $this->_getParameterValue('not_locked_file_types');
    }

    /**
     * @return bool
     */
    public function blogAdminHint()
    {
        return $this->_getParameterValue('blog_admin_hint');
    }

    /**
     * @return string
     */
    public function getBlogAdminHintText()
    {
        return $this->_getParameterValue('blog_admin_hint_text');
    }

    /**
     * @return bool
     */
    public function hideEmptyCategories()
    {
        return $this->_getParameterValue('hide_empty_categories');
    }

    /**
     * @return bool
     */
    public function protectFeed()
    {
        return $this->_getParameterValue('protect_feed');
    }

    /**
     * @return bool
     */
    public function showPostContentBeforeMore()
    {
        return $this->_getParameterValue('show_post_content_before_more');
    }

    /**
     * @return string
     */
    public function getFullAccessRole()
    {
        return $this->_getParameterValue('full_access_role');
    }

    /**
     * Returns true if a user is at the admin panel.
     *
     * @return boolean
     */
    public function atAdminPanel()
    {
        return $this->_oWrapper->isAdmin();
    }

    /**
     * Returns true if permalinks are active otherwise false.
     *
     * @return boolean
     */
    public function isPermalinksActive()
    {
        $sPermalinkStructure = $this->getWpOption('permalink_structure');
        return !empty($sPermalinkStructure);
    }



    /**
     * Returns the upload directory.
     *
     * @return null|string
     */
    public function getUploadDirectory()
    {
        $aWordpressUploadDir = $this->_oWrapper->getUploadDir();

        if (empty($aWordpressUploadDir['error'])) {
            return $aWordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
        }

        return null;
    }
}