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

use UserAccessManager\ObjectHandler\ObjectHandler;
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
    protected $oWordpress;

    /**
     * @var ObjectHandler
     */
    protected $oObjectHandler;

    /**
     * @var ConfigParameterFactory
     */
    protected $oConfigParameterFactory;

    /**
     * @var string
     */
    protected $sBaseFile;

    /**
     * @var array
     */
    protected $aConfigParameters = null;

    /**
     * @var array
     */
    protected $aWpOptions = [];

    /**
     * @var bool
     */
    protected $blAtAdminPanel = false;

    /**
     * @var array
     */
    protected $aMimeTypes = null;

    /**
     * Config constructor.
     *
     * @param Wordpress              $oWordpress
     * @param ObjectHandler          $oObjectHandler
     * @param ConfigParameterFactory $oConfigParameterFactory
     * @param String                 $sBaseFile
     */
    public function __construct(
        Wordpress $oWordpress,
        ObjectHandler $oObjectHandler,
        ConfigParameterFactory $oConfigParameterFactory,
        $sBaseFile
    ) {
        $this->oWordpress = $oWordpress;
        $this->oObjectHandler = $oObjectHandler;
        $this->oConfigParameterFactory = $oConfigParameterFactory;
        $this->sBaseFile = $sBaseFile;
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
        if (!isset($this->aWpOptions[$sOption])) {
            $this->aWpOptions[$sOption] = $this->oWordpress->getOption($sOption);
        }

        return $this->aWpOptions[$sOption];
    }

    /**
     * Flushes the config parameters.
     */
    public function flushConfigParameters()
    {
        $this->aConfigParameters = null;
    }

    /**
     * Returns the current settings
     *
     * @return ConfigParameter[]
     */
    public function getConfigParameters()
    {
        if ($this->aConfigParameters === null) {
            /**
             * @var ConfigParameter[] $aConfigParameters
             */
            $aConfigParameters = [];

            $aPostTypes = $this->oObjectHandler->getPostTypes();

            foreach ($aPostTypes as $sPostType) {
                if ($sPostType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
                    continue;
                }

                $sId = "hide_{$sPostType}";
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);

                $sId = "hide_{$sPostType}_title";
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);

                $sId = "{$sPostType}_title";
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter(
                    $sId,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS
                );

                if ($sPostType === 'post') {
                    $sId = "show_{$sPostType}_content_before_more";
                    $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);
                }

                $sId = "{$sPostType}_content";
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter(
                    $sId,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_ENTRY
                );

                $sId = "hide_{$sPostType}_comment";
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);

                $sId = "{$sPostType}_comment_content";
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter(
                    $sId,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_COMMENTS
                );

                $sId = "{$sPostType}_comments_locked";
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);

                if ($sPostType === 'post') {
                    $sId = "show_{$sPostType}_content_before_more";
                    $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);
                }
            }

            $sId = 'redirect';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'false',
                ['false', 'custom_page', 'custom_url']
            );

            $sId = 'redirect_custom_page';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter($sId);

            $sId = 'redirect_custom_url';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter($sId);

            $sId = 'lock_recursive';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId, true);

            $sId = 'authors_has_access_to_own';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId, true);


            $sId = 'authors_can_add_posts_to_groups';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);

            $sId = 'lock_file';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId);

            $sId = 'file_pass_type';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'random',
                ['random', 'user']
            );

            $sId = 'download_type';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'fopen',
                ['fopen', 'normal']
            );

            $sId = 'lock_file_types';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'all',
                ['all', 'selected', 'not_selected']
            );

            $sId = 'locked_file_types';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter(
                $sId,
                'zip,rar,tar,gz'
            );

            $sId = 'not_locked_file_types';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter(
                $sId,
                'gif,jpg,jpeg,png'
            );

            $sId = 'blog_admin_hint';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId, true);

            $sId = 'blog_admin_hint_text';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createStringConfigParameter($sId, '[L]');

            $aTaxonomies = $this->oObjectHandler->getTaxonomies();

            foreach ($aTaxonomies as $sTaxonomy) {
                $sId = 'hide_empty_'.$sTaxonomy;
                $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId, true);
            }

            $sId = 'protect_feed';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createBooleanConfigParameter($sId, true);

            $sId = 'full_access_role';
            $aConfigParameters[$sId] = $this->oConfigParameterFactory->createSelectionConfigParameter(
                $sId,
                'administrator',
                ['administrator', 'editor', 'author', 'contributor', 'subscriber']
            );

            $aCurrentOptions = (array)$this->getWpOption(self::ADMIN_OPTIONS_NAME);

            foreach ($aCurrentOptions as $sKey => $mOption) {
                if (isset($aConfigParameters[$sKey])) {
                    $aConfigParameters[$sKey]->setValue($mOption);
                }
            }

            $this->aConfigParameters = $aConfigParameters;
        }

        return $this->aConfigParameters;
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

        $this->aConfigParameters = $aConfigParameters;

        $aSimpleConfigParameters = [];

        foreach ($aConfigParameters as $oParameter) {
            $aSimpleConfigParameters[$oParameter->getId()] = $oParameter->getValue();
        }

        $this->oWordpress->updateOption(self::ADMIN_OPTIONS_NAME, $aSimpleConfigParameters);
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
    protected function getParameterValue($sParameterName)
    {
        $aOptions = $this->getConfigParameters();

        if (isset($aOptions[$sParameterName]) === false) {
            throw new \Exception("Unknown config parameter '{$sParameterName}'.");
        }

        return $aOptions[$sParameterName]->getValue();
    }

    /**
     * Returns true if a user is at the admin panel.
     *
     * @return boolean
     */
    public function atAdminPanel()
    {
        return $this->oWordpress->isAdmin();
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
        $aWordpressUploadDir = $this->oWordpress->getUploadDir();

        if (empty($aWordpressUploadDir['error'])) {
            return $aWordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
        }

        return null;
    }

    /**
     * Returns the full supported mine types.
     *
     * @return array
     */
    public function getMimeTypes()
    {
        if ($this->aMimeTypes === null) {
            $aMimeTypes = $this->oWordpress->getAllowedMimeTypes();
            $aFullMimeTypes = [];

            foreach ($aMimeTypes as $sExtensions => $sMineType) {
                $aExtension = explode('|', $sExtensions);

                foreach ($aExtension as $sExtension) {
                    $aFullMimeTypes[$sExtension] = $sMineType;
                }
            }

            $this->aMimeTypes = $aFullMimeTypes;
        }

        return $this->aMimeTypes;
    }

    /**
     * Returns the module url path.
     *
     * @return string
     */
    public function getUrlPath()
    {
        return $this->oWordpress->pluginsUrl('', $this->sBaseFile).DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the module real path.
     *
     * @return string
     */
    public function getRealPath()
    {
        $sDirName = dirname($this->sBaseFile);

        return $this->oWordpress->getPluginDir().DIRECTORY_SEPARATOR
            .$this->oWordpress->pluginBasename($sDirName).DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the option value if the option exists otherwise true.
     *
     * @param string $sParameterName
     *
     * @return bool
     */
    protected function hideObject($sParameterName)
    {
        $aOptions = $this->getConfigParameters();

        if (isset($aOptions[$sParameterName])) {
            return $aOptions[$sParameterName]->getValue();
        }

        return true;
    }

    /**
     * @param string $sPostType
     *
     * @return bool
     */
    public function hidePostType($sPostType)
    {
        return $this->hideObject('hide_'.$sPostType);
    }

    /**
     * @param string $sPostType
     *
     * @return bool
     */
    public function hidePostTypeTitle($sPostType)
    {
        return $this->hideObject('hide_'.$sPostType.'_title');
    }

    /**
     * @param string $sPostType
     *
     * @return bool
     */
    public function hidePostTypeComments($sPostType)
    {
        return $this->hideObject($sPostType.'_comments_locked');
    }

    /**
     * @param string $sPostType
     *
     * @return string
     */
    public function getPostTypeTitle($sPostType)
    {
        return $this->getParameterValue($sPostType.'_title');
    }

    /**
     * @param string $sPostType
     *
     * @return string
     */
    public function getPostTypeContent($sPostType)
    {
        return $this->getParameterValue($sPostType.'_content');
    }

    /**
     * @param string $sPostType
     *
     * @return string
     */
    public function getPostTypeCommentContent($sPostType)
    {
        return $this->getParameterValue($sPostType.'_comment_content');
    }

    /**
     * @return string
     */
    public function getRedirect()
    {
        return $this->getParameterValue('redirect');
    }

    /**
     * @return string
     */
    public function getRedirectCustomPage()
    {
        return $this->getParameterValue('redirect_custom_page');
    }

    /**
     * @return string
     */
    public function getRedirectCustomUrl()
    {
        return $this->getParameterValue('redirect_custom_url');
    }

    /**
     * @return bool
     */
    public function lockRecursive()
    {
        return $this->getParameterValue('lock_recursive');
    }

    /**
     * @return bool
     */
    public function authorsHasAccessToOwn()
    {
        return $this->getParameterValue('authors_has_access_to_own');
    }

    /**
     * @return bool
     */
    public function authorsCanAddPostsToGroups()
    {
        return $this->getParameterValue('authors_can_add_posts_to_groups');
    }

    /**
     * @return bool
     */
    public function lockFile()
    {
        return $this->getParameterValue('lock_file');
    }

    /**
     * @return string
     */
    public function getFilePassType()
    {
        return $this->getParameterValue('file_pass_type');
    }

    /**
     * @return string
     */
    public function getLockFileTypes()
    {
        return $this->getParameterValue('lock_file_types');
    }

    /**
     * @return string
     */
    public function getDownloadType()
    {
        return $this->getParameterValue('download_type');
    }

    /**
     * @return string
     */
    public function getLockedFileTypes()
    {
        return $this->getParameterValue('locked_file_types');
    }

    /**
     * @return string
     */
    public function getNotLockedFileTypes()
    {
        return $this->getParameterValue('not_locked_file_types');
    }

    /**
     * @return bool
     */
    public function blogAdminHint()
    {
        return $this->getParameterValue('blog_admin_hint');
    }

    /**
     * @return string
     */
    public function getBlogAdminHintText()
    {
        return $this->getParameterValue('blog_admin_hint_text');
    }

    /**
     * @param string $sTaxonomy
     *
     * @return bool
     */
    public function hideEmptyTaxonomy($sTaxonomy)
    {
        return $this->hideObject('hide_empty_'.$sTaxonomy);
    }

    /**
     * @return bool
     */
    public function protectFeed()
    {
        return $this->getParameterValue('protect_feed');
    }

    /**
     * @return bool
     */
    public function showPostContentBeforeMore()
    {
        return $this->getParameterValue('show_post_content_before_more');
    }

    /**
     * @return string
     */
    public function getFullAccessRole()
    {
        return $this->getParameterValue('full_access_role');
    }
}
