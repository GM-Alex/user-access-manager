<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Wrapper\Wordpress;

class MainConfig extends Config
{
    public const MAIN_CONFIG_KEY = 'uamAdminOptions';
    public const DEFAULT_TYPE = 'default';
    public const CACHE_PROVIDER_NONE = 'none';

    /**
     * MainConfig constructor.
     * @param Wordpress $wordpress
     * @param ObjectHandler $objectHandler
     * @param Cache $cache
     * @param ConfigParameterFactory $configParameterFactory
     */
    public function __construct(
        Wordpress $wordpress,
        private ObjectHandler $objectHandler,
        private Cache $cache,
        private ConfigParameterFactory $configParameterFactory
    ) {
        parent::__construct($wordpress, self::MAIN_CONFIG_KEY);
    }

    /**
     * @throws Exception
     */
    private function addDefaultGeneralConfigParameters(array &$configParameters): void
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

        $id = 'extra_ip_header';
        $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id, 'HTTP_X_REAL_IP');

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
     * @throws Exception
     */
    private function addDefaultPostConfigParameters(array &$configParameters): void
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

            $id = "hide_$postType";
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
     * @throws Exception
     */
    private function addDefaultTaxonomyConfigParameters(array &$configParameters): void
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
     * @throws Exception
     */
    private function addDefaultFileConfigParameters(array &$configParameters): void
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

    private function hideObject(string $objectType, string $parameterName): bool
    {
        $parameter = $this->getObjectParameter($objectType, $parameterName);
        return ($parameter !== null) ? $parameter->getValue() : true;
    }

    private function getObjectContent(string $objectType, string $parameterName): bool|string
    {
        $parameter = $this->getObjectParameter($objectType, $parameterName);
        return ($parameter !== null) ? $parameter->getValue() : '';
    }

    public function hidePostType(string $postType): bool
    {
        return $this->hideObject($postType, 'hide_%s');
    }

    public function hidePostTypeTitle(string $postType): bool
    {
        return $this->hideObject($postType, 'hide_%s_title');
    }

    public function hidePostTypeComments(string $postType): bool
    {
        return $this->hideObject($postType, 'hide_%s_comment');
    }

    public function lockPostTypeComments(string $postType): bool
    {
        return $this->hideObject($postType, '%s_comments_locked');
    }

    public function getPostTypeTitle(string $postType): string
    {
        return $this->getObjectContent($postType, '%s_title');
    }

    public function getPostTypeContent(string $postType): string
    {
        return $this->getObjectContent($postType, '%s_content');
    }

    public function getPostTypeCommentContent(string $postType): string
    {
        return $this->getObjectContent($postType, '%s_comment_content');
    }

    public function showPostTypeContentBeforeMore(string $postType): bool
    {
        return (bool) $this->getObjectContent($postType, 'show_%s_content_before_more');
    }

    public function getRedirect(): ?string
    {
        return $this->getParameterValue('redirect');
    }

    public function getRedirectCustomPage(): ?string
    {
        return $this->getParameterValue('redirect_custom_page');
    }

    public function getRedirectCustomUrl(): ?string
    {
        return $this->getParameterValue('redirect_custom_url');
    }

    public function lockRecursive(): bool
    {
        return (bool) $this->getParameterValue('lock_recursive');
    }

    public function authorsHasAccessToOwn(): bool
    {
        return (bool) $this->getParameterValue('authors_has_access_to_own');
    }

    public function authorsCanAddPostsToGroups(): bool
    {
        return (bool) $this->getParameterValue('authors_can_add_posts_to_groups');
    }

    public function lockFile(): bool
    {
        return (bool) $this->getParameterValue('lock_file');
    }

    public function getInlineFiles(): ?string
    {
        return $this->getParameterValue('inline_files');
    }

    public function getNoAccessImageType(): ?string
    {
        return $this->getParameterValue('no_access_image_type');
    }

    public function getCustomNoAccessImage(): ?string
    {
        return $this->getParameterValue('custom_no_access_image');
    }

    public function useCustomFileHandlingFile(): bool
    {
        return (bool) $this->getParameterValue('use_custom_file_handling_file');
    }

    public function getLockedDirectoryType(): ?string
    {
        return $this->getParameterValue('locked_directory_type');
    }

    public function getCustomLockedDirectories(): ?string
    {
        return $this->getParameterValue('custom_locked_directories');
    }

    public function getFilePassType(): ?string
    {
        return $this->getParameterValue('file_pass_type');
    }

    public function getDownloadType(): ?string
    {
        return $this->getParameterValue('download_type');
    }

    public function getLockedFileType(): ?string
    {
        return $this->getParameterValue('lock_file_types');
    }

    public function getLockedFiles(): ?string
    {
        return $this->getParameterValue('locked_file_types');
    }

    public function getNotLockedFiles(): ?string
    {
        return $this->getParameterValue('not_locked_file_types');
    }

    public function blogAdminHint(): bool
    {
        return (bool) $this->getParameterValue('blog_admin_hint');
    }

    public function getBlogAdminHintText(): ?string
    {
        return $this->getParameterValue('blog_admin_hint_text');
    }

    public function showAssignedGroups(): bool
    {
        return (bool) $this->getParameterValue('show_assigned_groups');
    }

    public function hideEditLinkOnNoAccess(): bool
    {
        return (bool) $this->getParameterValue('hide_edit_link_on_no_access');
    }

    public function hideEmptyTaxonomy(string $taxonomy): bool
    {
        $parameter = $this->getObjectParameter($taxonomy, 'hide_empty_%s');
        return ($parameter !== null) ? $parameter->getValue() : false;
    }

    public function protectFeed(): bool
    {
        return (bool) $this->getParameterValue('protect_feed');
    }

    public function getFullAccessRole(): ?string
    {
        return $this->getParameterValue('full_access_role');
    }

    public function getActiveCacheProvider(): ?string
    {
        return $this->getParameterValue('active_cache_provider');
    }

    public function getExtraIpHeader(): ?string
    {
        return $this->getParameterValue('extra_ip_header');
    }
}
