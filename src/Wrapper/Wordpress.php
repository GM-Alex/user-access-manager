<?php

declare(strict_types=1);

namespace UserAccessManager\Wrapper;

use JetBrains\PhpStorm\NoReturn;
use WP_Error;
use WP_Hook;
use WP_Post;
use WP_Post_Type;
use WP_Query;
use WP_Roles;
use WP_Taxonomy;
use WP_Term;
use WP_User;
use wpdb;
use function add_action;
use function add_filter;
use function add_menu_page;
use function add_meta_box;
use function add_option;
use function add_shortcode;
use function add_submenu_page;
use function attachment_url_to_postid;
use function current_time;
use function current_user_can;
use function date_i18n;
use function dbDelta;
use function delete_option;
use function do_action;
use function do_shortcode;
use function esc_html;
use function function_exists;
use function get_allowed_mime_types;
use function get_bloginfo;
use function get_home_path;
use function get_option;
use function get_page_by_path;
use function get_page_link;
use function get_pages;
use function get_post;
use function get_post_type_object;
use function get_post_types;
use function get_queried_object_id;
use function get_sites;
use function get_taxonomies;
use function get_taxonomy;
use function get_term;
use function get_userdata;
use function get_users;
use function got_mod_rewrite;
use function has_filter;
use function home_url;
use function is_admin;
use function is_feed;
use function is_multisite;
use function is_page;
use function is_post_type_hierarchical;
use function is_single;
use function is_super_admin;
use function is_taxonomy_hierarchical;
use function is_user_logged_in;
use function plugin_basename;
use function plugins_url;
use function register_widget;
use function remove_action;
use function remove_filter;
use function site_url;
use function switch_to_blog;
use function update_option;
use function wp_attachment_is_image;
use function wp_create_nonce;
use function wp_die;
use function wp_doing_ajax;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_get_current_user;
use function wp_login_url;
use function wp_logout_url;
use function wp_lostpassword_url;
use function wp_nonce_field;
use function wp_parse_id_list;
use function wp_redirect;
use function wp_register_script;
use function wp_register_style;
use function wp_registration_url;
use function wp_upload_dir;
use function wp_verify_nonce;

class Wordpress
{
    public function getDatabase(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    public function isNginx(): bool
    {
        global $is_nginx;
        return $is_nginx;
    }

    /**
     * @see is_post_type_hierarchical
     */
    public function isPostTypeHierarchical(string $postType): bool
    {
        return is_post_type_hierarchical($postType);
    }

    /**
     * @see is_taxonomy_hierarchical
     */
    public function isTaxonomyHierarchical(string $taxonomy): bool
    {
        return is_taxonomy_hierarchical($taxonomy);
    }

    /**
     * @see get_userdata
     */
    public function getUserData(int|string $id): WP_User|bool
    {
        return get_userdata($id);
    }

    /**
     * @see get_post_types
     */
    public function getPostTypes(
        array|string $arguments = [],
        string $output = 'names',
        string $operator = 'and'
    ): array {
        return get_post_types($arguments, $output, $operator);
    }

    /**
     * @return string[]|WP_Taxonomy[]
     * @see get_taxonomies
     */
    public function getTaxonomies(array $arguments = [], string $output = 'names', string $operator = 'and'): array
    {
        return get_taxonomies($arguments, $output, $operator);
    }

    /**
     * @see get_taxonomy
     */
    public function getTaxonomy(string $taxonomy): WP_Taxonomy|bool
    {
        return get_taxonomy($taxonomy);
    }

    /**
     * @see get_post
     */
    public function getPost(int|string|null $id, string $output = OBJECT, string $filter = 'raw'): array|WP_Post|null
    {
        return get_post($id, $output, $filter);
    }

    /**
     * @see get_post_type_object
     */
    public function getPostTypeObject(int|string $postType): ?WP_Post_Type
    {
        return get_post_type_object($postType);
    }

    /**
     * @see get_term
     */
    public function getTerm(
        int|string $id,
        string $taxonomy = '',
        string $output = OBJECT,
        string $filter = 'raw'
    ): WP_Term|WP_Error|array|null {
        return get_term($id, $taxonomy, $output, $filter);
    }


    /**
     * @see dbDelta
     */
    public function dbDelta(array|string $queries = '', bool $execute = true): array
    {
        if (function_exists('\dbDelta') === false) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        return dbDelta($queries, $execute);
    }

    /**
     * @see switch_to_blog
     */
    public function switchToBlog(int|string $blogId): bool
    {
        if (function_exists('\switch_to_blog') === true) {
            return switch_to_blog($blogId);
        }

        return true;
    }

    /**
     * @see \restore_current_blog()
     */
    public function restoreCurrentBlog(): bool
    {
        if (function_exists('\restore_current_blog') === true) {
            return restore_current_blog();
        }

        return true;
    }

    /**
     * @see is_multisite
     */
    public function isMultiSite(): bool
    {
        return is_multisite();
    }

    /**
     * @see do_action
     */
    public function doAction(string $tag, mixed $arguments = ''): void
    {
        do_action($tag, $arguments);
    }

    /**
     * @see add_action
     */
    public function addAction(
        string $tag,
        callable $functionToAdd,
        int $priority = 10,
        int $acceptedArguments = 1
    ): bool {
        return add_action($tag, $functionToAdd, $priority, $acceptedArguments);
    }

    /**
     * @see remove_action
     */
    public function removeAction(string $hookName, callable $callback, int $priority = 10): bool
    {
        return remove_action($hookName, $callback, $priority);
    }

    /**
     * @see has_filter
     */
    public function hasFilter(string $tag, callable|bool $functionToCheck = false): bool|int
    {
        return has_filter($tag, $functionToCheck);
    }

    /**
     * @see add_filter
     */
    public function addFilter(
        string $tag,
        callable $functionToAdd,
        int $priority = 10,
        int $acceptedArguments = 1
    ): bool {
        return add_filter($tag, $functionToAdd, $priority, $acceptedArguments);
    }

    /**
     * @see remove_filter
     */
    public function removeFilter(string $tag, callable $functionToRemove, int $priority = 10): bool
    {
        return remove_filter($tag, $functionToRemove, $priority);
    }

    /**
     * @see add_option
     */
    public function addOption(
        string $option,
        mixed $value = '',
        string $deprecated = '',
        bool|string $autoload = 'yes'
    ): bool {
        return add_option($option, $value, $deprecated, $autoload);
    }

    /**
     * @see delete_option
     */
    public function deleteOption(string $option): bool
    {
        return delete_option($option);
    }

    /**
     * @see update_option
     */
    public function updateOption(string $option, mixed $value = '', bool|string $autoload = 'yes'): bool
    {
        return update_option($option, $value, $autoload);
    }

    /**
     * @see get_option
     */
    public function getOption(string $option, mixed $default = false)
    {
        return get_option($option, $default);
    }

    /**
     * @see is_super_admin
     */
    public function isSuperAdmin(bool|int|string $userId = false): bool
    {
        return is_super_admin($userId);
    }

    /**
     * @see wp_get_current_user
     */
    public function getCurrentUser(): WP_User
    {
        if (function_exists('\wp_get_current_user') === false) {
            require_once(ABSPATH . 'wp-includes/pluggable.php');
        }

        return wp_get_current_user();
    }

    /**
     * @see get_allowed_mime_types
     */
    public function getAllowedMimeTypes(WP_User|int|string $user = null): array
    {
        return get_allowed_mime_types($user);
    }

    /**
     * @see wp_upload_dir
     */
    public function getUploadDir(string $time = null, bool $createDir = true, bool $refreshCache = false): array
    {
        return wp_upload_dir($time, $createDir, $refreshCache);
    }

    /**
     * @see home_url
     */
    public function getHomeUrl(string $path = '', string $scheme = null): string
    {
        return home_url($path, $scheme);
    }

    /**
     * @see site_url
     */
    public function getSiteUrl(string $path = '', string $scheme = null): string
    {
        return site_url($path, $scheme);
    }

    /**
     * @see get_home_path
     */
    public function getHomePath(): string
    {
        if (function_exists('\get_home_path') === false) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        return get_home_path();
    }

    /**
     * @see wp_parse_id_list
     */
    public function parseIdList(array|string $list): array
    {
        return wp_parse_id_list($list);
    }

    /**
     * @see wp_die
     */
    #[NoReturn]
    public function wpDie(string $message = '', string $title = '', array $arguments = []): void
    {
        wp_die($message, $title, $arguments);
    }

    /**
     * @see is_feed
     */
    public function isFeed(array|string $feeds = ''): bool
    {
        return is_feed($feeds);
    }

    /**
     * @see is_user_logged_in
     */
    public function isUserLoggedIn(): bool
    {
        return is_user_logged_in();
    }

    /**
     * @see get_page_by_path
     */
    public function getPageByPath(
        string $pagePath,
        string $output = OBJECT,
        string $postType = 'page'
    ): array|WP_Post|null {
        return get_page_by_path($pagePath, $output, $postType);
    }

    /**
     * @see wp_redirect
     */
    public function wpRedirect(string $location, int $status = 302): bool
    {
        return wp_redirect($location, $status);
    }

    public function getPageLink(
        bool|int|string|WP_Post $post = false,
        bool $leaveName = false,
        bool $sample = false
    ): string {
        return get_page_link($post, $leaveName, $sample);
    }

    public function getWpQuery(): WP_Query
    {
        global $wp_query;
        return $wp_query;
    }

    /**
     * @see is_admin
     */
    public function isAdmin(): bool
    {
        //Ajax request are always identified as an administrative interface page
        if (wp_doing_ajax() === true || defined('REST_REQUEST') && REST_REQUEST) {
            //So let's check if we are calling the ajax data for the frontend or backend
            //If the referer is an admin url we are requesting the data for the backend
            $adminUrl = get_admin_url();
            return str_starts_with((string)($_SERVER['HTTP_REFERER'] ?? ''), $adminUrl);
        }

        //No ajax request just uses the normal function
        return is_admin();
    }

    /**
     * @see wp_create_nonce
     */
    public function createNonce(int|string $action): string
    {
        return wp_create_nonce($action);
    }

    /**
     * @see wp_nonce_field
     */
    public function getNonceField(
        int|string $action = -1,
        string $name = '_wpnonce',
        bool $referrer = true,
        bool $echo = true
    ): string {
        return wp_nonce_field($action, $name, $referrer, $echo);
    }

    /**
     * @see wp_verify_nonce
     */
    public function verifyNonce(?string $nonce, int|string $action = -1): bool|int
    {
        return wp_verify_nonce($nonce, $action);
    }

    public function getRoles(): WP_Roles
    {
        global $wp_roles;
        return $wp_roles;
    }

    /**
     * @see add_menu_page
     */
    public function addMenuPage(
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        mixed $function = '',
        string $iconUrl = '',
        int|float|null $position = null
    ): string {
        return add_menu_page($pageTitle, $menuTitle, $capability, $menuSlug, $function, $iconUrl, $position);
    }

    /**
     * @see add_submenu_page
     */
    public function addSubMenuPage(
        string $parentSlug,
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        callable|string $function = ''
    ): bool|string {
        return add_submenu_page($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $function);
    }

    /**
     * @see add_meta_box
     */
    public function addMetaBox(
        string $id,
        string $title,
        callable $callback,
        $screen = null,
        string $context = 'advanced',
        string $priority = 'default',
        $callbackArguments = null
    ): void {
        add_meta_box($id, $title, $callback, $screen, $context, $priority, $callbackArguments);
    }

    /**
     * @see get_pages
     */
    public function getPages(array|string $arguments): bool|array
    {
        return get_pages($arguments);
    }

    /**
     * @see wp_register_style
     */
    public function registerStyle(
        string $handle,
        string $source,
        array $depends = [],
        bool|string|null $version = false,
        string $media = 'all'
    ): bool {
        return wp_register_style($handle, $source, $depends, $version, $media);
    }

    /**
     * @see wp_register_script
     */
    public function registerScript(
        string           $handle,
        string           $source,
        array            $depends = [],
        bool|string|null $version = false,
        bool             $inFooter = false
    ): bool {
        return wp_register_script($handle, $source, $depends, $version, $inFooter);
    }

    /**
     * @see wp_enqueue_style
     */
    public function enqueueStyle(
        string $handle,
        string $source = '',
        array $depends = [],
        bool|string|null $version = false,
        string $media = 'all'
    ): void {
        wp_enqueue_style($handle, $source, $depends, $version, $media);
    }

    /**
     * @see wp_enqueue_script
     */
    public function enqueueScript(
        string $handle,
        string $source = '',
        array $depends = [],
        bool|string|null $version = false,
        bool $inFooter = false
    ): void {
        wp_enqueue_script($handle, $source, $depends, $version, $inFooter);
    }

    public function getMetaBoxes(): array
    {
        global $wp_meta_boxes;
        return $wp_meta_boxes;
    }

    public function setMetaBoxes(array $wpMetaBoxes): void
    {
        global $wp_meta_boxes;
        $wp_meta_boxes = $wpMetaBoxes;
    }

    /**
     * @see get_sites
     */
    public function getSites(array $arguments = []): array
    {
        if (function_exists('\get_sites') === true && class_exists('\WP_Site_Query') === true) {
            return get_sites($arguments);
        }

        return [];
    }

    /**
     * @see apply_filters()
     */
    public function applyFilters(string $tag, mixed $value): mixed
    {
        return call_user_func_array('\apply_filters', func_get_args());
    }

    /**
     * @see get_bloginfo
     */
    public function getBlogInfo(string $show = '', string $filter = 'raw'): string
    {
        return get_bloginfo($show, $filter);
    }

    /**
     * @see esc_html
     */
    public function escHtml(string $text): string
    {
        return esc_html($text);
    }

    /**
     * @see is_single
     */
    public function isSingle(int|array|string $post = ''): bool
    {
        return is_single($post);
    }

    /**
     * @see is_page
     */
    public function isPage(int|array|string $page = ''): bool
    {
        return is_page($page);
    }

    /**
     * @see \WP_PLUGIN_DIR
     */
    public function getPluginDir(): string
    {
        return WP_PLUGIN_DIR;
    }

    /**
     * @see plugins_url
     */
    public function pluginsUrl(string $path = '', string $plugin = ''): string
    {
        return plugins_url($path, $plugin);
    }

    /**
     * @see plugin_basename
     */
    public function pluginBasename(string $file): string
    {
        return plugin_basename($file);
    }

    /**
     * @see wp_attachment_is_image
     */
    public function attachmentIsImage(int|WP_Post $post): bool
    {
        return wp_attachment_is_image($post);
    }

    /**
     * @see current_user_can
     */
    public function currentUserCan(string $capability): bool
    {
        return current_user_can($capability);
    }

    /**
     * @see get_users
     */
    public function getUsers(array $arguments = []): array
    {
        return get_users($arguments);
    }

    /**
     * @return WP_Hook[]
     */
    public function getFilters(): array
    {
        global $wp_filter;
        return $wp_filter;
    }

    public function setFilters(array $filters): void
    {
        global $wp_filter;
        $wp_filter = $filters;
    }

    /**
     * @see current_time
     */
    public function currentTime(string $type, int $gmt = 0): int|string
    {
        return current_time($type, $gmt);
    }

    public function formatDate(string $date): string
    {
        $dateFormat = __('M j, Y @ H:i');
        return date_i18n($dateFormat, strtotime($date));
    }

    /**
     * @see register_widget
     */
    public function registerWidget(mixed $widgetClass): void
    {
        register_widget($widgetClass);
    }

    /**
     * @see wp_login_url
     */
    public function wpLoginUrl(string $redirect = '', bool $forceReauth = false): string
    {
        return wp_login_url($redirect, $forceReauth);
    }

    /**
     * @see wp_logout_url
     */
    public function wpLogoutUrl(string $redirect = ''): string
    {
        return wp_logout_url($redirect);
    }

    /**
     * @see wp_registration_url
     */
    public function wpRegistrationUrl(): string
    {
        return wp_registration_url();
    }

    /**
     * @see wp_lostpassword_url
     */
    public function wpLostPasswordUrl(string $redirect = ''): string
    {
        return wp_lostpassword_url($redirect);
    }

    /**
     * @see add_shortcode
     */
    public function addShortCode(string $tag, callable $function): void
    {
        add_shortcode($tag, $function);
    }

    /**
     * @see do_shortcode
     */
    public function doShortCode(string $content, bool $ignoreHtml = false): string
    {
        return do_shortcode($content, $ignoreHtml);
    }

    /**
     * @see attachment_url_to_postid
     */
    public function attachmentUrlToPostId(string $url): int
    {
        return attachment_url_to_postid($url);
    }

    /**
     * @see got_mod_rewrite
     */
    public function gotModRewrite(): bool
    {
        if (function_exists('\got_mod_rewirte') === false) {
            require_once(ABSPATH . 'wp-admin/includes/misc.php');
        }

        return got_mod_rewrite();
    }

    /**
     * @see is_user_member_of_blog()
     */
    public function isUserMemberOfBlog(): bool
    {
        return is_user_member_of_blog();
    }

    /**
     * @see get_queried_object_id
     */
    public function getQueriedObjectId(): int
    {
        return get_queried_object_id();
    }

    public function getCurrentPost(): array|WP_Post|null
    {
        global $post;
        return $post;
    }
}
