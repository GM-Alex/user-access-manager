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

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Config
 *
 * @package UserAccessManager\Config
 */
class MainConfig extends Config
{
    const MAIN_CONFIG_KEY = 'uamAdminOptions';
    const DEFAULT_TYPE = 'default';
    const CACHE_PROVIDER_NONE = 'none';

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ConfigParameterFactory
     */
    protected $configParameterFactory;

    /**
     * MainConfig constructor.
     * @param Wordpress $wordpress
     * @param ObjectHandler $objectHandler
     * @param Cache $cache
     * @param ConfigParameterFactory $configParameterFactory
     */
    public function __construct(
        Wordpress $wordpress,
        ObjectHandler $objectHandler,
        Cache $cache,
        ConfigParameterFactory $configParameterFactory
    ) {
        $this->objectHandler = $objectHandler;
        $this->cache = $cache;
        $this->configParameterFactory = $configParameterFactory;

        parent::__construct($wordpress, self::MAIN_CONFIG_KEY);
    }

    /**
     * Adds the default general config parameters to the config parameters.
     * @param array $configParameters
     * @throws Exception
     */
    private function addDefaultGeneralConfigParameters(array &$configParameters)
    {
        $id = 'redirect';
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            'false',
            ['false', 'blog', 'login', 'custom_page', 'custom_url']
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

        $id = 'blog_admin_hint';
        $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

        $id = 'blog_admin_hint_text';
        $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id, '[L]');

        $id = 'show_assigned_groups';
        $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

        $id = 'hide_edit_link_on_no_access';
        $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

        $id = 'protect_feed';
        $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);

        $id = 'full_access_role';
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            'administrator',
            ['administrator', 'editor', 'author', 'contributor', 'subscriber']
        );

        $id = 'active_cache_provider';
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            self::CACHE_PROVIDER_NONE,
            array_merge([self::CACHE_PROVIDER_NONE], array_keys($this->cache->getRegisteredCacheProviders()))
        );
    }

    /**
     * Adds the default post config parameters to the config parameters.
     * @param array $configParameters
     * @throws Exception
     */
    private function addDefaultPostConfigParameters(array &$configParameters)
    {
        $postTypes = $this->objectHandler->getPostTypes();
        array_unshift($postTypes, self::DEFAULT_TYPE);

        foreach ($postTypes as $postType) {
            if ($postType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
                continue;
            }

            if ($postType !== self::DEFAULT_TYPE) {
                $id = "{$postType}_use_default";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);
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

            $id = "show_{$postType}_content_before_more";
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);
        }
    }

    /**
     * Adds the default taxonomy config parameters to the config parameters.
     * @param array $configParameters
     * @throws Exception
     */
    private function addDefaultTaxonomyConfigParameters(array &$configParameters)
    {
        $taxonomies = $this->objectHandler->getTaxonomies();
        array_unshift($taxonomies, self::DEFAULT_TYPE);

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy !== self::DEFAULT_TYPE) {
                $id = "{$taxonomy}_use_default";
                $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);
            }

            $id = 'hide_empty_' . $taxonomy;
            $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, true);
        }
    }

    /**
     * Adds the default file config parameters to the config parameters.
     * @param array $configParameters
     * @throws Exception
     */
    private function addDefaultFileConfigParameters(array &$configParameters)
    {
        $id = 'lock_file';
        $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

        $id = 'download_type';
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            'fopen',
            ['xsendfile', 'fopen', 'normal']
        );

        $id = 'inline_files';
        $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id, 'pdf');

        $id = 'no_access_image_type';
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            'default',
            ['default', 'custom']
        );

        $id = 'custom_no_access_image';
        $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id);

        $id = 'use_custom_file_handling_file';
        $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id);

        $id = 'locked_directory_type';
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            'wordpress',
            ['wordpress', 'all', 'custom']
        );

        $id = 'custom_locked_directories';
        $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id);

        $id = 'file_pass_type';
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            'random',
            ['random', 'user']
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
    }

    /**
     * Returns the default config parameters settings
     * @return ConfigParameter[]
     * @throws Exception
     */
    protected function getDefaultConfigParameters(): array
    {
        if ($this->defaultConfigParameters === []) {
            /**
             * @var ConfigParameter[] $configParameters
             */
            $configParameters = [];

            $this->addDefaultGeneralConfigParameters($configParameters);
            $this->addDefaultPostConfigParameters($configParameters);
            $this->addDefaultTaxonomyConfigParameters($configParameters);
            $this->addDefaultFileConfigParameters($configParameters);

            $this->defaultConfigParameters = $configParameters;
        }

        return $this->defaultConfigParameters;
    }

    /**
     * Returns the object parameter name.
     * @param string $objectType
     * @param string $rawParameterName
     * @return ConfigParameter|null
     */
    private function getObjectParameter(string $objectType, string $rawParameterName): ?ConfigParameter
    {
        $options = $this->getConfigParameters();
        $parameterName = sprintf($rawParameterName, $objectType);

        if (isset($options[$parameterName]) === false
            || isset($options["{$objectType}_use_default"]) === true
            && $options["{$objectType}_use_default"]->getValue() === true
        ) {
            $parameterName = sprintf($rawParameterName, self::DEFAULT_TYPE);
        }

        return (isset($options[$parameterName]) === true) ? $options[$parameterName] : null;
    }

    /**
     * Returns the option value if the option exists otherwise true.
     * @param string $objectType
     * @param string $parameterName
     * @return bool
     */
    private function hideObject(string $objectType, string $parameterName): bool
    {
        $parameter = $this->getObjectParameter($objectType, $parameterName);
        return ($parameter !== null) ? $parameter->getValue() : true;
    }

    /**
     * Returns the option value if the option exists otherwise an empty string.
     * @param string $objectType
     * @param string $parameterName
     * @return bool|string
     */
    private function getObjectContent(string $objectType, string $parameterName)
    {
        $parameter = $this->getObjectParameter($objectType, $parameterName);
        return ($parameter !== null) ? $parameter->getValue() : '';
    }

    /**
     * @param string $postType
     * @return bool
     */
    public function hidePostType(string $postType): bool
    {
        return $this->hideObject($postType, 'hide_%s');
    }

    /**
     * @param string $postType
     * @return bool
     */
    public function hidePostTypeTitle(string $postType): bool
    {
        return $this->hideObject($postType, 'hide_%s_title');
    }

    /**
     * @param string $postType
     * @return bool
     */
    public function hidePostTypeComments(string $postType): bool
    {
        return $this->hideObject($postType, 'hide_%s_comment');
    }

    /**
     * @param string $postType
     * @return bool
     */
    public function lockPostTypeComments(string $postType): bool
    {
        return $this->hideObject($postType, '%s_comments_locked');
    }

    /**
     * @param string $postType
     * @return string
     */
    public function getPostTypeTitle(string $postType): string
    {
        return $this->getObjectContent($postType, '%s_title');
    }

    /**
     * @param string $postType
     * @return string
     */
    public function getPostTypeContent(string $postType): string
    {
        return $this->getObjectContent($postType, '%s_content');
    }

    /**
     * @param string $postType
     * @return string
     */
    public function getPostTypeCommentContent(string $postType): string
    {
        return $this->getObjectContent($postType, '%s_comment_content');
    }

    /**
     * @param string $postType
     * @return bool
     */
    public function showPostTypeContentBeforeMore(string $postType)
    {
        return (bool) $this->getObjectContent($postType, 'show_%s_content_before_more');
    }

    /**
     * @return string
     */
    public function getRedirect(): ?string
    {
        return $this->getParameterValue('redirect');
    }

    /**
     * @return string
     */
    public function getRedirectCustomPage(): ?string
    {
        return $this->getParameterValue('redirect_custom_page');
    }

    /**
     * @return string
     */
    public function getRedirectCustomUrl(): ?string
    {
        return $this->getParameterValue('redirect_custom_url');
    }

    /**
     * @return bool
     */
    public function lockRecursive(): bool
    {
        return (bool) $this->getParameterValue('lock_recursive');
    }

    /**
     * @return bool
     */
    public function authorsHasAccessToOwn(): bool
    {
        return (bool) $this->getParameterValue('authors_has_access_to_own');
    }

    /**
     * @return bool
     */
    public function authorsCanAddPostsToGroups(): bool
    {
        return (bool) $this->getParameterValue('authors_can_add_posts_to_groups');
    }

    /**
     * @return bool
     */
    public function lockFile(): bool
    {
        return (bool) $this->getParameterValue('lock_file');
    }

    /**
     * @return string
     */
    public function getInlineFiles(): ?string
    {
        return $this->getParameterValue('inline_files');
    }

    /**
     * @return string
     */
    public function getNoAccessImageType(): ?string
    {
        return $this->getParameterValue('no_access_image_type');
    }

    /**
     * @return string
     */
    public function getCustomNoAccessImage(): ?string
    {
        return $this->getParameterValue('custom_no_access_image');
    }

    /**
     * @return bool
     */
    public function useCustomFileHandlingFile(): bool
    {
        return (bool) $this->getParameterValue('use_custom_file_handling_file');
    }

    /**
     * @return string
     */
    public function getLockedDirectoryType(): ?string
    {
        return $this->getParameterValue('locked_directory_type');
    }

    /**
     * @return string
     */
    public function getCustomLockedDirectories(): ?string
    {
        return $this->getParameterValue('custom_locked_directories');
    }

    /**
     * @return string
     */
    public function getFilePassType(): ?string
    {
        return $this->getParameterValue('file_pass_type');
    }

    /**
     * @return string
     */
    public function getDownloadType(): ?string
    {
        return $this->getParameterValue('download_type');
    }

    /**
     * @return string
     */
    public function getLockedFileType(): ?string
    {
        return $this->getParameterValue('lock_file_types');
    }

    /**
     * @return string
     */
    public function getLockedFiles(): ?string
    {
        return $this->getParameterValue('locked_file_types');
    }

    /**
     * @return string
     */
    public function getNotLockedFiles(): ?string
    {
        return $this->getParameterValue('not_locked_file_types');
    }

    /**
     * @return bool
     */
    public function blogAdminHint(): bool
    {
        return (bool) $this->getParameterValue('blog_admin_hint');
    }

    /**
     * @return string
     */
    public function getBlogAdminHintText(): ?string
    {
        return $this->getParameterValue('blog_admin_hint_text');
    }

    /**
     * @return bool
     */
    public function showAssignedGroups(): bool
    {
        return (bool) $this->getParameterValue('show_assigned_groups');
    }

    /**
     * @return bool
     */
    public function hideEditLinkOnNoAccess(): bool
    {
        return (bool) $this->getParameterValue('hide_edit_link_on_no_access');
    }

    /**
     * @param string $taxonomy
     * @return bool
     */
    public function hideEmptyTaxonomy(string $taxonomy): bool
    {
        $parameter = $this->getObjectParameter($taxonomy, 'hide_empty_%s');
        return ($parameter !== null) ? $parameter->getValue() : false;
    }

    /**
     * @return bool
     */
    public function protectFeed(): bool
    {
        return (bool) $this->getParameterValue('protect_feed');
    }

    /**
     * @return string
     */
    public function getFullAccessRole(): ?string
    {
        return $this->getParameterValue('full_access_role');
    }

    /**
     * @return null|string
     */
    public function getActiveCacheProvider(): ?string
    {
        return $this->getParameterValue('active_cache_provider');
    }
}
