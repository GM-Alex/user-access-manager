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
namespace UserAccessManager\Wrapper;

/**
 * Class Wordpress
 *
 * @package UserAccessManager\Wrapper
 */
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
     * Returns true if web server is nginx.
     *
     * @return bool
     */
    public function isNginx()
    {
        global $is_nginx;
        return $is_nginx;
    }

    /**
     * @see \is_post_type_hierarchical()
     *
     * @param string $postType
     *
     * @return bool
     */
    public function isPostTypeHierarchical($postType)
    {
        return \is_post_type_hierarchical($postType);
    }

    /**
     * @see \is_taxonomy_hierarchical()
     *
     * @param string $taxonomy
     *
     * @return bool
     */
    public function isTaxonomyHierarchical($taxonomy)
    {
        return \is_taxonomy_hierarchical($taxonomy);
    }

    /**
     * @see \get_userdata()
     *
     * @param string $id
     *
     * @return false|\WP_User
     */
    public function getUserData($id)
    {
        return \get_userdata($id);
    }

    /**
     * @see \get_post_types()
     *
     * @param string|array $arguments
     * @param string       $output
     * @param string       $operator
     *
     * @return array
     */
    public function getPostTypes($arguments = [], $output = 'names', $operator = 'and')
    {
        return \get_post_types($arguments, $output, $operator);
    }

    /**
     * @see \get_taxonomies()
     *
     * @param array  $arguments
     * @param string $output
     * @param string $operator
     *
     * @return array
     */
    public function getTaxonomies(array $arguments = [], $output = 'names', $operator = 'and')
    {
        return \get_taxonomies($arguments, $output, $operator);
    }

    /**
     * @see \get_taxonomy()
     *
     * @param string $taxonomy
     *
     * @return false|\WP_Taxonomy
     */
    public function getTaxonomy($taxonomy)
    {
        return \get_taxonomy($taxonomy);
    }

    /**
     * @see \get_post()
     *
     * @param string $id The post id.
     * @param string $output
     * @param string $filter
     *
     * @return \WP_Post|array|null
     */
    public function getPost($id, $output = OBJECT, $filter = 'raw')
    {
        return \get_post($id, $output, $filter);
    }

    /**
     * @see \get_post_type_object()
     *
     * @param string $postType
     *
     * @return null|\WP_Post_Type
     */
    public function getPostTypeObject($postType)
    {
        return \get_post_type_object($postType);
    }

    /**
     * @see \get_term()
     *
     * @param string $id
     * @param string $taxonomy
     * @param string $output
     * @param string $filter
     *
     * @return array|null|\WP_Error|\WP_Term
     */
    public function getTerm($id, $taxonomy = '', $output = OBJECT, $filter = 'raw')
    {
        return \get_term($id, $taxonomy, $output, $filter);
    }


    /**
     * @see \dbDelta()
     *
     * @param array|string $queries
     * @param bool         $execute
     *
     * @return array
     */
    public function dbDelta($queries = '', $execute = true)
    {
        if (\function_exists('\dbDelta') === false) {
            /**
             * @noinspection PhpIncludeInspection
             */
            require_once(ABSPATH.'wp-admin/includes/upgrade.php');
        }

        return \dbDelta($queries, $execute);
    }

    /**
     * @see \switch_to_blog()
     *
     * @param integer $blogId
     *
     * @return int|true
     */
    public function switchToBlog($blogId)
    {
        if (function_exists('\switch_to_blog') === true) {
            return \switch_to_blog($blogId);
        }

        return true;
    }

    /**
     * @see \is_multisite()
     *
     * @return bool
     */
    public function isMultiSite()
    {
        return \is_multisite();
    }

    /**
     * @see \do_action()
     *
     * @param string $tag
     * @param mixed  $arguments
     */
    public function doAction($tag, $arguments = '')
    {
        \do_action($tag, $arguments);
    }

    /**
     * @see \add_action()
     *
     * @param string   $tag
     * @param callable $functionToAdd
     * @param int      $priority
     * @param int      $acceptedArguments
     *
     * @return true
     */
    public function addAction($tag, $functionToAdd, $priority = 10, $acceptedArguments = 1)
    {
        return \add_action($tag, $functionToAdd, $priority, $acceptedArguments);
    }

    /**
     * @see \has_filter()
     *
     * @param string        $tag
     * @param bool|callable $functionToCheck
     *
     * @return bool|false|int
     */
    public function hasFilter($tag, $functionToCheck = false)
    {
        return \has_filter($tag, $functionToCheck);
    }

    /**
     * @see \add_filter()
     *
     * @param string   $tag
     * @param callable $functionToAdd
     * @param int      $priority
     * @param int      $acceptedArguments
     *
     * @return true
     */
    public function addFilter($tag, $functionToAdd, $priority = 10, $acceptedArguments = 1)
    {
        return \add_filter($tag, $functionToAdd, $priority, $acceptedArguments);
    }

    /**
     * @see \remove_filter()
     *
     * @param string   $tag
     * @param callable $functionToRemove
     * @param int      $priority
     *
     * @return bool
     */
    public function removeFilter($tag, $functionToRemove, $priority = 10)
    {
        return \remove_filter($tag, $functionToRemove, $priority);
    }

    /**
     * @see \add_option()
     *
     * @param string      $option
     * @param mixed       $value
     * @param string      $deprecated
     * @param string|bool $autoload
     *
     * @return bool
     */
    public function addOption($option, $value = '', $deprecated = '', $autoload = 'yes')
    {
        return \add_option($option, $value, $deprecated, $autoload);
    }

    /**
     * @see \delete_option()
     *
     * @param string $option
     *
     * @return bool
     */
    public function deleteOption($option)
    {
        return \delete_option($option);
    }

    /**
     * @see \update_option()
     *
     * @param string      $option
     * @param mixed       $value
     * @param string|bool $autoload
     *
     * @return bool
     */
    public function updateOption($option, $value = '', $autoload = 'yes')
    {
        return \update_option($option, $value, $autoload);
    }

    /**
     * @see \get_option()
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($option, $default = false)
    {
        return \get_option($option, $default);
    }

    /**
     * @see \is_super_admin()
     *
     * @param int|bool $userId
     *
     * @return bool
     */
    public function isSuperAdmin($userId = false)
    {
        return \is_super_admin($userId);
    }

    /**
     * @see \wp_get_current_user()
     *
     * @return \WP_User
     */
    public function getCurrentUser()
    {
        if (function_exists('\wp_get_current_user') === false) {
            require_once(ABSPATH.'wp-includes/pluggable.php');
        }

        return \wp_get_current_user();
    }

    /**
     * @see \get_allowed_mime_types()
     *
     * @param int|\WP_User $user
     *
     * @return array
     */
    public function getAllowedMimeTypes($user = null)
    {
        return \get_allowed_mime_types($user);
    }

    /**
     * @see \wp_upload_dir()
     *
     * @param string $time
     * @param bool   $createDir
     * @param bool   $refreshCache
     *
     * @return array
     */
    public function getUploadDir($time = null, $createDir = true, $refreshCache = false)
    {
        return \wp_upload_dir($time, $createDir, $refreshCache);
    }

    /**
     * @see \home_url()
     *
     * @param string $path
     * @param string $scheme
     *
     * @return string
     */
    public function getHomeUrl($path = '', $scheme = null)
    {
        return \home_url($path, $scheme);
    }

    /**
     * @see \wp_parse_id_list()
     *
     * @param array|string $list
     *
     * @return array
     */
    public function parseIdList($list)
    {
        return \wp_parse_id_list($list);
    }

    /**
     * @see \wp_die()
     *
     * @param string $message
     * @param string $title
     * @param array  $arguments
     */
    public function wpDie($message = '', $title = '', array $arguments = [])
    {
        \wp_die($message, $title, $arguments);
    }

    /**
     * @see \is_feed()
     *
     * @param string|array $feeds
     *
     * @return bool
     */
    public function isFeed($feeds = '')
    {
        return \is_feed($feeds);
    }

    /**
     * @see \is_user_logged_in()
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return \is_user_logged_in();
    }

    /**
     * @see \get_page_by_path()
     *
     * @param string $pagePath
     * @param string $output
     * @param string $postType
     *
     * @return array|null|\WP_Post
     */
    public function getPageByPath($pagePath, $output = OBJECT, $postType = 'page')
    {
        return \get_page_by_path($pagePath, $output, $postType);
    }

    /**
     * @see \wp_redirect()
     *
     * @param string $location
     * @param int    $status
     *
     * @return bool
     */
    public function wpRedirect($location, $status = 302)
    {
        return \wp_redirect($location, $status);
    }

    /**
     * @param bool|int|\WP_Post $post
     * @param bool              $leaveName
     * @param bool              $sample
     *
     * @return string The page permalink.
     */
    public function getPageLink($post = false, $leaveName = false, $sample = false)
    {
        return \get_page_link($post, $leaveName, $sample);
    }

    /**
     * Returns the wp_query object.
     *
     * @return \WP_Query
     */
    public function getWpQuery()
    {
        global $wp_query;
        return $wp_query;
    }

    /**
     * @see \is_admin()
     *
     * @return bool
     */
    public function isAdmin()
    {
        return \is_admin();
    }

    /**
     * @see \wp_create_nonce()
     *
     * @param int|string $action
     *
     * @return string
     */
    public function createNonce($action)
    {
        return \wp_create_nonce($action);
    }

    /**
     * @see \wp_nonce_field()
     *
     * @param int|string $action
     * @param string     $name
     * @param bool       $referrer
     * @param bool       $echo
     *
     * @return string
     */
    public function getNonceField($action = -1, $name = '_wpnonce', $referrer = true, $echo = true)
    {
        return \wp_nonce_field($action, $name, $referrer, $echo);
    }

    /**
     * @see \wp_verify_nonce()
     *
     * @param string     $nonce
     * @param string|int $action
     *
     * @return false|int
     */
    public function verifyNonce($nonce, $action = -1)
    {
        return \wp_verify_nonce($nonce, $action);
    }

    /**
     * Returns the wordpress roles.
     *
     * @return \WP_Roles
     */
    public function getRoles()
    {
        global $wp_roles;
        return $wp_roles;
    }

    /**
     * @see \add_menu_page()
     *
     * @param string $pageTitle
     * @param string $menuTitle
     * @param string $capability
     * @param string $menuSlug
     * @param mixed  $function
     * @param string $iconUrl
     * @param null   $position
     *
     * @return string
     */
    public function addMenuPage(
        $pageTitle,
        $menuTitle,
        $capability,
        $menuSlug,
        $function = '',
        $iconUrl = '',
        $position = null
    ) {
        return \add_menu_page($pageTitle, $menuTitle, $capability, $menuSlug, $function, $iconUrl, $position);
    }

    /**
     * @see \add_submenu_page()
     *
     * @param string          $parentSlug
     * @param string          $pageTitle
     * @param string          $menuTitle
     * @param string          $capability
     * @param string          $menuSlug
     * @param string|callable $function
     *
     * @return false|string
     */
    public function addSubMenuPage($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $function = '')
    {
        return \add_submenu_page($parentSlug, $pageTitle, $menuTitle, $capability, $menuSlug, $function);
    }

    /**
     * @see \add_meta_box()
     *
     * @param string                  $id
     * @param string                  $title
     * @param callable                $callback
     * @param string|array|\WP_Screen $screen
     * @param string                  $context
     * @param string                  $priority
     * @param array                   $callbackArguments
     */
    public function addMetaBox(
        $id,
        $title,
        $callback,
        $screen = null,
        $context = 'advanced',
        $priority = 'default',
        $callbackArguments = null
    ) {
        \add_meta_box($id, $title, $callback, $screen, $context, $priority, $callbackArguments);
    }

    /**
     * @see \get_pages()
     *
     * @param array|string $arguments
     *
     * @return array|false
     */
    public function getPages($arguments)
    {
        return \get_pages($arguments);
    }

    /**
     * @see \wp_register_style()
     *
     * @param string           $handle
     * @param string           $source
     * @param array            $depends
     * @param string|bool|null $version
     * @param string           $media
     *
     * @return bool
     */
    public function registerStyle($handle, $source, $depends = [], $version = false, $media = 'all')
    {
        return \wp_register_style($handle, $source, $depends, $version, $media);
    }

    /**
     * @see \wp_register_script()
     *
     * @param string           $handle
     * @param string           $source
     * @param array            $depends
     * @param string|bool|null $version
     * @param bool             $inFooter
     *
     * @return bool
     */
    public function registerScript($handle, $source, $depends = [], $version = false, $inFooter = false)
    {
        return \wp_register_script($handle, $source, $depends, $version, $inFooter);
    }

    /**
     * @see \wp_enqueue_style()
     *
     * @param string           $handle
     * @param string           $source
     * @param array            $depends
     * @param string|bool|null $version
     * @param string           $media
     */
    public function enqueueStyle($handle, $source = '', $depends = [], $version = false, $media = 'all')
    {
        \wp_enqueue_style($handle, $source, $depends, $version, $media);
    }

    /**
     * @see \wp_enqueue_script()
     *
     * @param string           $handle
     * @param string           $source
     * @param array            $depends
     * @param string|bool|null $version
     * @param bool             $inFooter
     */
    public function enqueueScript($handle, $source = '', $depends = [], $version = false, $inFooter = false)
    {
        \wp_enqueue_script($handle, $source, $depends, $version, $inFooter);
    }

    /**
     * Returns the wordpress meta boxes.
     *
     * @return array
     */
    public function getMetaBoxes()
    {
        global $wp_meta_boxes;
        return $wp_meta_boxes;
    }

    /**
     * Sets the wordpress meta boxes.
     *
     * @param array $wpMetaBoxes
     */
    public function setMetaBoxes(array $wpMetaBoxes)
    {
        global $wp_meta_boxes;
        $wp_meta_boxes= $wpMetaBoxes;
    }

    /**
     * @see \get_sites()
     *
     * @param array $arguments
     *
     * @return array
     */
    public function getSites(array $arguments = [])
    {
        if (function_exists('\get_sites') === true && class_exists('\WP_Site_Query') === true) {
            return \get_sites($arguments);
        }

        return [];
    }

    /**
     * @see \apply_filters()
     *
     * @param string $tag
     * @param mixed  $value
     *
     * @return mixed
     */
    public function applyFilters($tag, $value)
    {
        return call_user_func_array('\apply_filters', func_get_args());
    }

    /**
     * @see \get_bloginfo()
     *
     * @param string $show
     * @param string $filter
     *
     * @return string
     */
    public function getBlogInfo($show = '', $filter = 'raw')
    {
        return \get_bloginfo($show, $filter);
    }

    /**
     * @see \esc_html()
     *
     * @param string $text
     *
     * @return string
     */
    public function escHtml($text)
    {
        return \esc_html($text);
    }

    /**
     * @see \is_single()
     *
     * @param int|string|array $post
     *
     * @return bool
     */
    public function isSingle($post = '')
    {
        return \is_single($post);
    }

    /**
     * @see \is_page()
     *
     * @param int|string|array $page
     *
     * @return bool
     */
    public function isPage($page = '')
    {
        return \is_page($page);
    }

    /**
     * @see \WP_PLUGIN_DIR
     *
     * @return string
     */
    public function getPluginDir()
    {
        return WP_PLUGIN_DIR;
    }

    /**
     * @see \plugins_url()
     *
     * @param string $path
     * @param string $plugin
     *
     * @return string
     */
    public function pluginsUrl($path = '', $plugin = '')
    {
        return \plugins_url($path, $plugin);
    }

    /**
     * @see \plugin_basename()
     *
     * @param $file
     *
     * @return string
     */
    public function pluginBasename($file)
    {
        return \plugin_basename($file);
    }

    /**
     * @see wp_attachment_is_image()
     *
     * @param int|\WP_Post $post
     *
     * @return bool
     */
    public function attachmentIsImage($post)
    {
        return wp_attachment_is_image($post);
    }

    /**
     * @see current_user_can()
     *
     * @param string $capability
     *
     * @return bool
     */
    public function currentUserCan($capability)
    {
        return current_user_can($capability);
    }

    /**
     * @return \WP_Hook[]
     */
    public function getFilters()
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
}
