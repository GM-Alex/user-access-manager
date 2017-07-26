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
 * @version   SVN: $id$
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
    private $wordpress;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var ConfigParameterFactory
     */
    private $configParameterFactory;

    /**
     * @var string
     */
    private $baseFile;

    /**
     * @var null|array
     */
    private $configParameters = null;

    /**
     * @var array
     */
    private $wpOptions = [];

    /**
     * @var array
     */
    private $mimeTypes = null;

    /**
     * Config constructor.
     *
     * @param Wordpress              $wordpress
     * @param ObjectHandler          $objectHandler
     * @param ConfigParameterFactory $configParameterFactory
     * @param String                 $baseFile
     */
    public function __construct(
        Wordpress $wordpress,
        ObjectHandler $objectHandler,
        ConfigParameterFactory $configParameterFactory,
        $baseFile
    ) {
        $this->wordpress = $wordpress;
        $this->objectHandler = $objectHandler;
        $this->configParameterFactory = $configParameterFactory;
        $this->baseFile = $baseFile;
    }

    /**
     * Returns the WordPress options.
     *
     * @param string $option
     *
     * @return mixed
     */
    public function getWpOption($option)
    {
        if (!isset($this->wpOptions[$option]) === true) {
            $this->wpOptions[$option] = $this->wordpress->getOption($option);
        }

        return $this->wpOptions[$option];
    }

    /**
     * Flushes the config parameters.
     */
    public function flushConfigParameters()
    {
        $this->configParameters = null;
    }

    /**
     * Returns the current settings
     *
     * @return ConfigParameter[]
     */
    public function getConfigParameters()
    {
        if ($this->configParameters === null) {
            /**
             * @var ConfigParameter[] $configParameters
             */
            $configParameters = [];

            $postTypes = $this->objectHandler->getPostTypes();

            foreach ($postTypes as $postType) {
                if ($postType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
                    continue;
                }

                $id = "hide_{$postType}";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                $id = "hide_{$postType}_title";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                $id = "{$postType}_title";
                $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                    $id,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS
                );

                $id = "{$postType}_content";
                $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                    $id,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_ENTRY
                );

                $id = "hide_{$postType}_comment";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                $id = "{$postType}_comment_content";
                $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                    $id,
                    TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_COMMENTS
                );

                $id = "{$postType}_comments_locked";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

                if ($postType === 'post') {
                    $id = "show_{$postType}_content_before_more";
                    $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);
                }
            }

            $id = 'redirect';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'false',
                ['false', 'custom_page', 'custom_url']
            );

            $id = 'redirect_custom_page';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id);

            $id = 'redirect_custom_url';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id);

            $id = 'lock_recursive';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

            $id = 'authors_has_access_to_own';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);


            $id = 'authors_can_add_posts_to_groups';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

            $id = 'lock_file';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

            $id = 'file_pass_type';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'random',
                ['random', 'user']
            );

            $id = 'download_type';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'fopen',
                ['fopen', 'normal']
            );

            $id = 'lock_file_types';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'all',
                ['all', 'selected', 'not_selected']
            );

            $id = 'locked_file_types';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                $id,
                'zip,rar,tar,gz'
            );

            $id = 'not_locked_file_types';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter(
                $id,
                'gif,jpg,jpeg,png'
            );

            $id = 'blog_admin_hint';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

            $id = 'blog_admin_hint_text';
            $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id, '[L]');

            $taxonomies = $this->objectHandler->getTaxonomies();

            foreach ($taxonomies as $taxonomy) {
                $id = 'hide_empty_'.$taxonomy;
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);
            }

            $id = 'protect_feed';
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

            $id = 'full_access_role';
            $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
                $id,
                'administrator',
                ['administrator', 'editor', 'author', 'contributor', 'subscriber']
            );

            $currentOptions = (array)$this->getWpOption(self::ADMIN_OPTIONS_NAME);

            foreach ($currentOptions as $key => $option) {
                if (isset($configParameters[$key])) {
                    $configParameters[$key]->setValue($option);
                }
            }

            $this->configParameters = $configParameters;
        }

        return $this->configParameters;
    }

    /**
     * Sets the new config parameters and saves them to the database.
     *
     * @param $rawParameters
     */
    public function setConfigParameters(array $rawParameters)
    {
        $configParameters = $this->getConfigParameters();

        foreach ($rawParameters as $key => $value) {
            if (isset($configParameters[$key]) === true) {
                $configParameters[$key]->setValue($value);
            }
        }

        $this->configParameters = $configParameters;

        $simpleConfigParameters = [];

        foreach ($configParameters as $parameter) {
            $simpleConfigParameters[$parameter->getId()] = $parameter->getValue();
        }

        $this->wordpress->updateOption(self::ADMIN_OPTIONS_NAME, $simpleConfigParameters);
    }

    /**
     * Returns the requested parameter value
     *
     * @param string $parameterName
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function getParameterValue($parameterName)
    {
        $options = $this->getConfigParameters();

        if (isset($options[$parameterName]) === false) {
            throw new \Exception("Unknown config parameter '{$parameterName}'.");
        }

        return $options[$parameterName]->getValue();
    }

    /**
     * Returns true if a user is at the admin panel.
     *
     * @return bool
     */
    public function atAdminPanel()
    {
        return $this->wordpress->isAdmin();
    }

    /**
     * Returns true if permalinks are active otherwise false.
     *
     * @return bool
     */
    public function isPermalinksActive()
    {
        $permalinkStructure = $this->getWpOption('permalink_structure');
        return (empty($permalinkStructure) === false);
    }

    /**
     * Returns the upload directory.
     *
     * @return null|string
     */
    public function getUploadDirectory()
    {
        $wordpressUploadDir = $this->wordpress->getUploadDir();

        if (empty($wordpressUploadDir['error'])) {
            return $wordpressUploadDir['basedir'].DIRECTORY_SEPARATOR;
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
        if ($this->mimeTypes === null) {
            $mimeTypes = $this->wordpress->getAllowedMimeTypes();
            $fullMimeTypes = [];

            foreach ($mimeTypes as $extensions => $mineType) {
                $extensions = explode('|', $extensions);

                foreach ($extensions as $extension) {
                    $fullMimeTypes[$extension] = $mineType;
                }
            }

            $this->mimeTypes = $fullMimeTypes;
        }

        return $this->mimeTypes;
    }

    /**
     * Returns the module url path.
     *
     * @return string
     */
    public function getUrlPath()
    {
        return $this->wordpress->pluginsUrl('', $this->baseFile).'/';
    }

    /**
     * Returns the module real path.
     *
     * @return string
     */
    public function getRealPath()
    {
        $dirName = dirname($this->baseFile);

        return $this->wordpress->getPluginDir().DIRECTORY_SEPARATOR
            .$this->wordpress->pluginBasename($dirName).DIRECTORY_SEPARATOR;
    }

    /**
     * Returns the option value if the option exists otherwise true.
     *
     * @param string $parameterName
     *
     * @return bool
     */
    private function hideObject($parameterName)
    {
        $options = $this->getConfigParameters();

        if (isset($options[$parameterName]) === true) {
            return $options[$parameterName]->getValue();
        }

        return true;
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function hidePostType($postType)
    {
        return $this->hideObject('hide_'.$postType);
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function hidePostTypeTitle($postType)
    {
        return $this->hideObject('hide_'.$postType.'_title');
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function hidePostTypeComments($postType)
    {
        return $this->hideObject('hide_'.$postType.'_comment');
    }

    /**
     * @param string $postType
     *
     * @return bool
     */
    public function lockPostTypeComments($postType)
    {
        return $this->hideObject($postType.'_comments_locked');
    }

    /**
     * @param string $postType
     *
     * @return string
     */
    public function getPostTypeTitle($postType)
    {
        return $this->getParameterValue($postType.'_title');
    }

    /**
     * @param string $postType
     *
     * @return string
     */
    public function getPostTypeContent($postType)
    {
        return $this->getParameterValue($postType.'_content');
    }

    /**
     * @param string $postType
     *
     * @return string
     */
    public function getPostTypeCommentContent($postType)
    {
        return $this->getParameterValue($postType.'_comment_content');
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
     * @param string $taxonomy
     *
     * @return bool
     */
    public function hideEmptyTaxonomy($taxonomy)
    {
        $parameterName = 'hide_empty_'.$taxonomy;
        $options = $this->getConfigParameters();

        if (isset($options[$parameterName]) === true) {
            return $options[$parameterName]->getValue();
        }

        return false;
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
