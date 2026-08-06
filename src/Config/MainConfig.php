<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Parameter\ConfigParameter;
use UserAccessManager\Config\Parameter\ConfigParameterFactory;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Wrapper\Wordpress;

class MainConfig extends Config
{
    public const MAIN_CONFIG_KEY = 'uamAdminOptions';
    public const DEFAULT_TYPE = 'default';
    public const CACHE_PROVIDER_NONE = 'none';

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
    private function addBoolean(array &$configParameters, string $id, bool $defaultValue = false): void
    {
        $configParameters[$id] = $this->configParameterFactory->createBooleanConfigParameter($id, $defaultValue);
    }

    /**
     * @throws Exception
     */
    private function addString(array &$configParameters, string $id, string $defaultValue = ''): void
    {
        $configParameters[$id] = $this->configParameterFactory->createStringConfigParameter($id, $defaultValue);
    }

    /**
     * @throws Exception
     */
    private function addSelection(
        array &$configParameters,
        string $id,
        string $defaultValue,
        array $selections
    ): void {
        $configParameters[$id] = $this->configParameterFactory->createSelectionConfigParameter(
            $id,
            $defaultValue,
            $selections
        );
    }

    /**
     * @throws Exception
     */
    private function addDefaultGeneralConfigParameters(array &$configParameters): void
    {
        $this->addSelection(
            $configParameters,
            'redirect',
            'false',
            ['false', 'blog', 'login', 'custom_page', 'custom_url', 'origin']
        );
        $this->addString($configParameters, 'redirect_custom_page');
        $this->addString($configParameters, 'redirect_custom_url');
        $this->addBoolean($configParameters, 'append_redirect_to_parameter');
        $this->addBoolean($configParameters, 'lock_recursive', true);
        $this->addBoolean($configParameters, 'authors_has_access_to_own', true);
        $this->addBoolean($configParameters, 'authors_can_add_posts_to_groups');
        $this->addBoolean($configParameters, 'blog_admin_hint', true);
        $this->addString($configParameters, 'blog_admin_hint_text', '[L]');
        $this->addBoolean($configParameters, 'show_assigned_groups', true);
        $this->addBoolean($configParameters, 'hide_edit_link_on_no_access', true);
        $this->addString($configParameters, 'extra_ip_header', 'HTTP_X_REAL_IP');
        $this->addBoolean($configParameters, 'protect_feed', true);
        $this->addSelection(
            $configParameters,
            'full_access_role',
            'administrator',
            ['administrator', 'editor', 'author', 'contributor', 'subscriber']
        );
        $this->addSelection(
            $configParameters,
            'active_cache_provider',
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
                $this->addBoolean($configParameters, "{$postType}_use_default");
            }

            $this->addBoolean($configParameters, "hide_$postType");
            $this->addBoolean($configParameters, "hide_{$postType}_title");
            $this->addString($configParameters, "{$postType}_title", TXT_UAM_SETTING_DEFAULT_NO_RIGHTS);
            $this->addString($configParameters, "{$postType}_content", TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_ENTRY);
            $this->addBoolean($configParameters, "hide_{$postType}_comment");
            $this->addString(
                $configParameters,
                "{$postType}_comment_content",
                TXT_UAM_SETTING_DEFAULT_NO_RIGHTS_FOR_COMMENTS
            );
            $this->addBoolean($configParameters, "{$postType}_comments_locked");
            $this->addBoolean($configParameters, "show_{$postType}_content_before_more");
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
                $this->addBoolean($configParameters, "{$taxonomy}_use_default");
            }

            $this->addBoolean($configParameters, "hide_empty_$taxonomy", true);
        }
    }

    /**
     * @throws Exception
     */
    private function addDefaultFileConfigParameters(array &$configParameters): void
    {
        $this->addBoolean($configParameters, 'lock_file');
        $this->addSelection($configParameters, 'download_type', 'fopen', ['xsendfile', 'fopen', 'normal']);
        $this->addString($configParameters, 'inline_files', 'pdf');
        $this->addSelection($configParameters, 'no_access_image_type', 'default', ['default', 'custom']);
        $this->addString($configParameters, 'custom_no_access_image');
        $this->addBoolean($configParameters, 'use_custom_file_handling_file');
        $this->addSelection($configParameters, 'locked_directory_type', 'wordpress', ['wordpress', 'all', 'custom']);
        $this->addString($configParameters, 'custom_locked_directories');
        $this->addSelection($configParameters, 'file_pass_type', 'random', ['random', 'user']);
        $this->addSelection($configParameters, 'lock_file_types', 'all', ['all', 'selected', 'not_selected']);
        $this->addString($configParameters, 'locked_file_types', 'zip,rar,tar,gz');
        $this->addString($configParameters, 'not_locked_file_types', 'gif,jpg,jpeg,png');
    }

    /**
     * @return ConfigParameter[]
     * @throws Exception
     */
    protected function getDefaultConfigParameters(): array
    {
        if ($this->defaultConfigParameters !== []) {
            return $this->defaultConfigParameters;
        }

        /**
         * @var ConfigParameter[] $configParameters
         */
        $configParameters = [];

        $this->addDefaultGeneralConfigParameters($configParameters);
        $this->addDefaultPostConfigParameters($configParameters);
        $this->addDefaultTaxonomyConfigParameters($configParameters);
        $this->addDefaultFileConfigParameters($configParameters);

        return $this->defaultConfigParameters = $configParameters;
    }

    private function getObjectParameter(string $objectType, string $rawParameterName): ?ConfigParameter
    {
        $options = $this->getConfigParameters();
        $parameterName = sprintf($rawParameterName, $objectType);
        $useDefaultParameter = $options["{$objectType}_use_default"] ?? null;

        if (isset($options[$parameterName]) === false || $useDefaultParameter?->getValue() === true) {
            $parameterName = sprintf($rawParameterName, self::DEFAULT_TYPE);
        }

        return $options[$parameterName] ?? null;
    }

    private function hideObject(string $objectType, string $parameterName): bool
    {
        return $this->getObjectParameter($objectType, $parameterName)?->getValue() ?? true;
    }

    private function getObjectContent(string $objectType, string $parameterName): bool|string
    {
        return $this->getObjectParameter($objectType, $parameterName)?->getValue() ?? '';
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

    public function appendRedirectToParameter(): bool
    {
        return (bool) $this->getParameterValue('append_redirect_to_parameter');
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
        return $this->getObjectParameter($taxonomy, 'hide_empty_%s')?->getValue() ?? false;
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
