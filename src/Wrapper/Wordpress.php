<?php
/**
 * Wordpress.php
 *
 * The Wordpress class file.
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

namespace UserAccessManager\Wrapper;

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

/**
 * Class Wordpress
 *
 * @package UserAccessManager\Wrapper
 */
class Wordpress
{
    /**
     * Returns the database.
     * @return wpdb
     */
    public function getDatabase(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Returns true if web server is nginx.
     * @return bool
     */
    public function isNginx(): bool
    {
        global $is_nginx;
        return $is_nginx;
    }

    /**
     * @param string $postType
     * @return bool
     * @see \is_post_type_hierarchical()
     */
    public function isPostTypeHierarchical(string $postType): bool
    {
        return is_post_type_hierarchical($postType);
    }

    /**
     * @param string $taxonomy
     * @return bool
     * @see \is_taxonomy_hierarchical()
     */
    public function isTaxonomyHierarchical(string $taxonomy): bool
    {
        return is_taxonomy_hierarchical($taxonomy);
    }

    /**
     * @param int|string $id
     * @return false|WP_User
     * @see \get_userdata()
     */
    public function getUserData($id)
    {
        return get_userdata($id);
    }

    /**
     * @param string|array $arguments
     * @param string $output
     * @param string $operator
     * @return array
     * @see \get_post_types()
     */
    public function getPostTypes($arguments = [], $output = 'names', $operator = 'and'): array
    {
        return get_post_types($arguments, $output, $operator);
    }

    /**
     * @param array $arguments
     * @param string $output
     * @param string $operator
     * @return array
     * @see \get_taxonomies()
     */
    public function getTaxonomies(array $arguments = [], $output = 'names', $operator = 'and'): array
    {
        return get_taxonomies($arguments, $output, $operator);
    }

    /**
     * @param string $taxonomy
     * @return false|WP_Taxonomy
     * @see \get_taxonomy()
     */
    public function getTaxonomy(string $taxonomy)
    {
        return get_taxonomy($taxonomy);
    }

    /**
     * @param int|string $id The post id.
     * @param string $output
     * @param string $filter
     * @return WP_Post|array|null
     * @see \get_post()
     */
    public function getPost($id, $output = OBJECT, $filter = 'raw')
    {
        return get_post($id, $output, $filter);
    }

    /**
     * @param int|string $postType
     * @return null|WP_Post_Type
     * @see \get_post_type_object()
     */
    public function getPostTypeObject($postType): ?WP_Post_Type
    {
        return get_post_type_object($postType);
    }

    /**
     * @param int|string $id
     * @param string $taxonomy
     * @param string $output
     * @param string $filter
     * @return array|null|WP_Error|WP_Term
     * @see \get_term()
     */
    public function getTerm($id, string $taxonomy = '', string $output = OBJECT, string $filter = 'raw')
    {
        return get_term($id, $taxonomy, $output, $filter);
    }


    /**
     * @param array|string $queries
     * @param bool $execute
     * @return array
     * @see \dbDelta()
     */
    public function dbDelta($queries = '', $execute = true): array
    {
        if (function_exists('\dbDelta') === false) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        return dbDelta($queries, $execute);
    }

    /**
     * @param integer $blogId
     * @return int|string
     * @see \switch_to_blog()
     */
    public function switchToBlog($blogId)
    {
        if (function_exists('\switch_to_blog') === true) {
            return switch_to_blog($blogId);
        }

        return true;
    }

    /**
     * @return bool
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
     * @return bool
     * @see \is_multisite()
     */
    public function isMultiSite(): bool
    {
        return is_multisite();
    }

    /**
     * @param string $tag
     * @param mixed $arguments
     * @see \do_action()
     */
    public function doAction(string $tag, $arguments = '')
    {
        do_action($tag, $arguments);
    }

    /**
     * @param string $tag
     * @param callable $functionToAdd
     * @param int $priority
     * @param int $acceptedArguments
     * @return true
     * @see \add_action()
     */
    public function addAction(string $tag, callable $functionToAdd, $priority = 10, $acceptedArguments = 1)
    {
        return add_action($tag, $functionToAdd, $priority, $acceptedArguments);
    }

    /**
     * @param string $tag
     * @param bool|callable $functionToCheck
     * @return bool|false|int
     * @see \has_filter()
     */
    public function hasFilter(string $tag, $functionToCheck = false)
    {
        return has_filter($tag, $functionToCheck);
    }

    /**
     * @param string $tag
     * @param callable $functionToAdd
     * @param int $priority
     * @param int $acceptedArguments
     * @return true
     * @see \add_filter()
     */
    public function addFilter(string $tag, callable $functionToAdd, $priority = 10, $acceptedArguments = 1)
    {
        return add_filter($tag, $functionToAdd, $priority, $acceptedArguments);
    }

    /**
     * @param string $tag
     * @param callable $functionToRemove
     * @param int $priority
     * @return bool
     * @see \remove_filter()
     */
    public function removeFilter(string $tag, callable $functionToRemove, $priority = 10): bool
    {
        return remove_filter($tag, $functionToRemove, $priority);
    }

    /**
     * @param string $option
     * @param mixed $value
     * @param string $deprecated
     * @param string|bool $autoload
     * @return bool
     * @see \add_option()
     */
    public function addOption(string $option, $value = '', $deprecated = '', $autoload = 'yes'): bool
    {
        return add_option($option, $value, $deprecated, $autoload);
    }

    /**
     * @param string $option
     * @return bool
     * @see \delete_option()
     */
    public function deleteOption(string $option): bool
    {
        return delete_option($option);
    }

    /**
     * @param string $option
     * @param mixed $value
     * @param string|bool $autoload
     * @return bool
     * @see \update_option()
     */
    public function updateOption(string $option, $value = '', $autoload = 'yes'): bool
    {
        return update_option($option, $value, $autoload);
    }

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     * @see \get_option()
     */
    public function getOption(string $option, $default = false)
    {
        return get_option($option, $default);
    }

    /**
     * @param int|bool $userId
     * @return bool
     * @see \is_super_admin()
     */
    public function isSuperAdmin($userId = false): bool
    {
        return is_super_admin($userId);
    }

    /**
     * @return WP_User
     * @see \wp_get_current_user()
     */
    public function getCurrentUser(): WP_User
    {
        if (function_exists('\wp_get_current_user') === false) {
            require_once(ABSPATH . 'wp-includes/pluggable.php');
        }

        return wp_get_current_user();
    }

    /**
     * @param int|WP_User $user
     * @return array
     * @see \get_allowed_mime_types()
     */
    public function getAllowedMimeTypes($user = null): array
    {
        return get_allowed_mime_types($user);
    }

    /**
     * @param string $time
     * @param bool $createDir
     * @param bool $refreshCache
     * @return array
     * @see \wp_upload_dir()
     */
    public function getUploadDir($time = null, $createDir = true, $refreshCache = false): array
    {
        return wp_upload_dir($time, $createDir, $refreshCache);
    }

    /**
     * @param string $path
     * @param null|string $scheme
     * @return string
     * @see \home_url()
     */
    public function getHomeUrl($path = '', $scheme = null): string
    {
        return home_url($path, $scheme);
    }

    /**
     * @param string $path
     * @param null|string $scheme
     * @return string
     * @see \site_url();
     */
    public function getSiteUrl($path = '', $scheme = null): string
    {
        return site_url($path, $scheme);
    }

    /**
     * @return string
     * @see \get_home_path()
     */
    public function getHomePath(): string
    {
        if (function_exists('\get_home_path') === false) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        return get_home_path();
    }

    /**
     * @param array|string $list
     * @return array
     * @see \wp_parse_id_list()
     */
    public function parseIdList($list): array
    {
        return wp_parse_id_list($list);
    }

    /**
     * @param string $message
     * @param string $title
     * @param array $arguments
     * @see \wp_die()
     */
    public function wpDie($message = '', $title = '', array $arguments = [])
    {
        wp_die($message, $title, $arguments);
    }

    /**
     * @param string|array $feeds
     * @return bool
     * @see \is_feed()
     */
    public function isFeed($feeds = ''): bool
    {
        return is_feed($feeds);
    }

    /**
     * @return bool
     * @see \is_user_logged_in()
     */
    public function isUserLoggedIn(): bool
    {
        return is_user_logged_in();
    }

    /**
     * @param string $pagePath
     * @param string $output
     * @param string $postType
     * @return array|null|WP_Post
     * @see \get_page_by_path()
     */
    public function getPageByPath(string $pagePath, $output = OBJECT, $postType = 'page')
    {
        return get_page_by_path($pagePath, $output, $postType);
    }

    /**
     * @param string $location
     * @param int $status
     * @return bool
     * @see \wp_redirect()
     */
    public function wpRedirect(string $location, $status = 302): bool
    {
        return wp_redirect($location, $status);
    }

    /**
     * @param bool|int|WP_Post $post
     * @param bool $leaveName
     * @param bool $sample
     * @return string The page permalink.
     */
    public function getPageLink($post = false, $leaveName = false, $sample = false): string
    {
        return get_page_link($post, $leaveName, $sample);
    }

    /**
     * Returns the wp_query object.
     * @return WP_Query
     */
    public function getWpQuery(): WP_Query
    {
        global $wp_query;
        return $wp_query;
    }

    /**
     * @return bool
     * @see \is_admin()
     */
    public function isAdmin(): bool
    {
        //Ajax request are always identified as administrative interface page
        if (wp_doing_ajax() === true) {
            //So let's check if we are calling the ajax data for the frontend or backend
            //If the referer is an admin url we are requesting the data for the backend
            $adminUrl = get_admin_url();
            return (substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, strlen((string) $adminUrl)) === $adminUrl);
        }

        //No ajax request just use the normal function
        return is_admin();
    }

    /**
     * @param int|string $action
     * @return string
     * @see \wp_create_nonce()
     */
    public function createNonce($action): string
    {
        return wp_create_nonce($action);
    }

    /**
     * @param int|string $action
     * @param string $name
     * @param bool $referrer
     * @param bool $echo
     * @return string
     * @see \wp_nonce_field()
     */
    public function getNonceField($action = -1, $name = '_wpnonce', $referrer = true, $echo = true): string
    {
        return wp_nonce_field($action, $name, $referrer, $echo);
    }

    /**
     * @param string|null $nonce
     * @param string|int $action
     * @return false|int
     * @see \wp_verify_nonce()
     */
    public function verifyNonce(?string $nonce, $action = -1)
    {
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Returns the wordpress roles.
     * @return WP_Roles
     */
    public function getRoles(): WP_Roles
    {
        global $wp_roles;
        return $wp_roles;
    }

    /**
     * @param string $pageTitle
     * @param string $menuTitle
     * @param string $capability
     * @param string $menuSlug
     * @param mixed $function
     * @param string $iconUrl
     * @param null $position
     * @return string
     * @see \add_menu_page()
     */
    public function addMenuPage(
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        $function = '',
        $iconUrl = '',
        $position = null
    ): string {
        return add_menu_page($pageTitle, $menuTitle, $capability, $menuSlug, $function, $iconUrl, $position);
    }

    /**
     * @param string $parentSlug
     * @param string $pageTitle
     * @param string $menuTitle
     * @param string $capability
     * @param string $menuSlug
     * @param string|callable $function
     * @return false|string
     * @see \add_submenu_page()
     */
    public function addSubMenuPage(
        string $parentSlug,
        string $pageTitle,
        string $menuTitle,
        string $capability,
        string $menuSlug,
        $function = ''
    ) {
        return add_submenu_page($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $function);
    }

    /**
     * @param string $id
     * @param string $title
     * @param callable $callback
     * @param null $screen
     * @param string $context
     * @param string $priority
     * @param null $callbackArguments
     * @see \add_meta_box()
     */
    public function addMetaBox(
        string $id,
        string $title,
        callable $callback,
        $screen = null,
        $context = 'advanced',
        $priority = 'default',
        $callbackArguments = null
    ) {
        add_meta_box($id, $title, $callback, $screen, $context, $priority, $callbackArguments);
    }

    /**
     * @param array|string $arguments
     * @return array|false
     * @see \get_pages()
     */
    public function getPages($arguments)
    {
        return get_pages($arguments);
    }

    /**
     * @param string $handle
     * @param string $source
     * @param array $depends
     * @param string|bool|null $version
     * @param string $media
     * @return bool
     * @see \wp_register_style()
     */
    public function registerStyle(string $handle, string $source, $depends = [], $version = false, $media = 'all'): bool
    {
        return wp_register_style($handle, $source, $depends, $version, $media);
    }

    /**
     * @param string $handle
     * @param string $source
     * @param array $depends
     * @param string|bool|null $version
     * @param bool $inFooter
     * @return bool
     * @see \wp_register_script()
     */
    public function registerScript(
        string $handle,
        string $source,
        $depends = [],
        $version = false,
        $inFooter = false
    ): bool {
        return wp_register_script($handle, $source, $depends, $version, $inFooter);
    }

    /**
     * @param string $handle
     * @param string $source
     * @param array $depends
     * @param string|bool|null $version
     * @param string $media
     * @see \wp_enqueue_style()
     */
    public function enqueueStyle(string $handle, $source = '', $depends = [], $version = false, $media = 'all')
    {
        wp_enqueue_style($handle, $source, $depends, $version, $media);
    }

    /**
     * @param string $handle
     * @param string $source
     * @param array $depends
     * @param string|bool|null $version
     * @param bool $inFooter
     * @see \wp_enqueue_script()
     */
    public function enqueueScript(string $handle, $source = '', $depends = [], $version = false, $inFooter = false)
    {
        wp_enqueue_script($handle, $source, $depends, $version, $inFooter);
    }

    /**
     * Returns the wordpress meta boxes.
     * @return array
     */
    public function getMetaBoxes(): array
    {
        global $wp_meta_boxes;
        return $wp_meta_boxes;
    }

    /**
     * Sets the wordpress meta boxes.
     * @param array $wpMetaBoxes
     */
    public function setMetaBoxes(array $wpMetaBoxes)
    {
        global $wp_meta_boxes;
        $wp_meta_boxes = $wpMetaBoxes;
    }

    /**
     * @param array $arguments
     * @return array
     * @see \get_sites()
     */
    public function getSites(array $arguments = []): array
    {
        if (function_exists('\get_sites') === true && class_exists('\WP_Site_Query') === true) {
            return get_sites($arguments);
        }

        return [];
    }

    /**
     * @param string $tag
     * @param mixed $value
     * @return mixed
     * @see \apply_filters()
     */
    public function applyFilters(string $tag, $value)
    {
        return call_user_func_array('\apply_filters', func_get_args());
    }

    /**
     * @param string $show
     * @param string $filter
     * @return string
     * @see \get_bloginfo()
     */
    public function getBlogInfo($show = '', $filter = 'raw'): string
    {
        return get_bloginfo($show, $filter);
    }

    /**
     * @param string $text
     * @return string
     * @see \esc_html()
     */
    public function escHtml(string $text): string
    {
        return esc_html($text);
    }

    /**
     * @param int|string|array $post
     * @return bool
     * @see \is_single()
     */
    public function isSingle($post = ''): bool
    {
        return is_single($post);
    }

    /**
     * @param int|string|array $page
     * @return bool
     * @see \is_page()
     */
    public function isPage($page = ''): bool
    {
        return is_page($page);
    }

    /**
     * @return string
     * @see \WP_PLUGIN_DIR
     */
    public function getPluginDir(): string
    {
        return WP_PLUGIN_DIR;
    }

    /**
     * @param string $path
     * @param string $plugin
     * @return string
     * @see \plugins_url()
     */
    public function pluginsUrl($path = '', $plugin = ''): string
    {
        return plugins_url($path, $plugin);
    }

    /**
     * @param $file
     * @return string
     * @see \plugin_basename()
     */
    public function pluginBasename($file): string
    {
        return plugin_basename($file);
    }

    /**
     * @param int|WP_Post $post
     * @return bool
     * @see \wp_attachment_is_image()
     */
    public function attachmentIsImage($post): bool
    {
        return wp_attachment_is_image($post);
    }

    /**
     * @param string $capability
     * @return bool
     * @see \current_user_can()
     */
    public function currentUserCan(string $capability): bool
    {
        return current_user_can($capability);
    }

    /**
     * @param array $arguments
     * @return array
     * @see \get_users()
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

    /**
     * @param array $filters
     */
    public function setFilters(array $filters)
    {
        global $wp_filter;
        $wp_filter = $filters;
    }

    /**
     * @param string $type
     * @param int $gmt
     * @return int|string
     * @see \current_time()
     */
    public function currentTime(string $type, $gmt = 0)
    {
        return current_time($type, $gmt);
    }

    /**
     * Formats the date like wordpress does it.
     * @param string $date
     * @return string
     */
    public function formatDate(string $date): string
    {
        $dateFormat = __('M j, Y @ H:i');
        return date_i18n($dateFormat, strtotime($date));
    }

    /**
     * @param mixed $widgetClass
     * @see \register_widget()
     */
    public function registerWidget($widgetClass)
    {
        register_widget($widgetClass);
    }

    /**
     * @param string $redirect
     * @param bool $forceReauth
     * @return string
     * @see \wp_login_url()
     */
    public function wpLoginUrl($redirect = '', $forceReauth = false): string
    {
        return wp_login_url($redirect, $forceReauth);
    }

    /**
     * @param string $redirect
     * @return string
     * @see \wp_logout_url()
     */
    public function wpLogoutUrl($redirect = ''): string
    {
        return wp_logout_url($redirect);
    }

    /**
     * @return string
     * @see \wp_registration_url()
     */
    public function wpRegistrationUrl(): string
    {
        return wp_registration_url();
    }

    /**
     * @param string $redirect
     * @return string
     * @see \wp_lostpassword_url()
     */
    public function wpLostPasswordUrl($redirect = ''): string
    {
        return wp_lostpassword_url($redirect);
    }

    /**
     * @param string $tag
     * @param callable $function
     * @see \add_shortcode()
     */
    public function addShortCode(string $tag, callable $function)
    {
        add_shortcode($tag, $function);
    }

    /**
     * @param string $content
     * @param bool $ignoreHtml
     * @return string
     * @see \do_shortcode()
     */
    public function doShortCode(string $content, $ignoreHtml = false): string
    {
        return do_shortcode($content, $ignoreHtml);
    }

    /**
     * @param string $url
     * @return int
     * @see \attachment_url_to_postid()
     */
    public function attachmentUrlToPostId(string $url): int
    {
        return attachment_url_to_postid($url);
    }

    /**
     * @return bool
     * @see \got_mod_rewrite()
     */
    public function gotModRewrite(): bool
    {
        if (function_exists('\got_mod_rewirte') === false) {
            require_once(ABSPATH . 'wp-admin/includes/misc.php');
        }

        return got_mod_rewrite();
    }

    /**
     * @return bool
     * @see \is_user_member_of_blog()
     */
    public function isUserMemberOfBlog(): bool
    {
        return (bool) is_user_member_of_blog();
    }
}
