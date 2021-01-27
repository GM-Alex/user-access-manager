<?php
/**
 * Database.php
 *
 * The Database class file.
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

namespace UserAccessManager\Database;

use UserAccessManager\Wrapper\Wordpress;
use wpdb;

/**
 * Class Database
 *
 * @package UserAccessManager\Database
 */
class Database
{
    const USER_GROUP_TABLE_NAME = 'uam_accessgroups';
    const USER_GROUP_TO_OBJECT_TABLE_NAME = 'uam_accessgroup_to_object';

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var wpdb
     */
    private $wpDatabase;

    /**
     * Database constructor.
     * @param Wordpress $wordpress
     */
    public function __construct(Wordpress $wordpress)
    {
        $this->wordpress = $wordpress;
        $this->wpDatabase = $wordpress->getDatabase();
    }

    /**
     * Returns the wordpress database.
     * @return wpdb
     */
    public function getWordpressDatabase(): wpdb
    {
        return $this->wpDatabase;
    }

    /**
     * Returns the user group table name.
     * @return string
     */
    public function getUserGroupTable(): string
    {
        return $this->wpDatabase->prefix . self::USER_GROUP_TABLE_NAME;
    }

    /**
     * Returns the user group table name.
     * @return string
     */
    public function getUserGroupToObjectTable(): string
    {
        return $this->wpDatabase->prefix . self::USER_GROUP_TO_OBJECT_TABLE_NAME;
    }

    /**
     * @param string $queries
     * @param bool $execute
     * @return array
     * @see dbDelta()
     */
    public function dbDelta($queries = '', $execute = true): array
    {
        return $this->wordpress->dbDelta($queries, $execute);
    }

    /**
     * @return string
     * @see \wpdb::$prefix
     */
    public function getPrefix(): string
    {
        return $this->wpDatabase->prefix;
    }

    /**
     * Returns the last insert id.
     * @return mixed
     */
    public function getLastInsertId()
    {
        return $this->wpDatabase->insert_id;
    }

    /**
     * Returns the current blog id.
     * @return int|string
     */
    public function getCurrentBlogId()
    {
        return $this->wpDatabase->blogid;
    }

    /**
     * Returns the blogs table name.
     * @return string
     */
    public function getBlogsTable(): string
    {
        return $this->wpDatabase->blogs;
    }

    /**
     * Returns the posts table name.
     * @return string
     */
    public function getPostsTable(): string
    {
        return $this->wpDatabase->posts;
    }

    /**
     * Returns the term_relationships table name.
     * @return string
     */
    public function getTermRelationshipsTable(): string
    {
        return $this->wpDatabase->term_relationships;
    }

    /**
     * Returns the term_taxonomy table name.
     * @return string
     */
    public function getTermTaxonomyTable(): string
    {
        return $this->wpDatabase->term_taxonomy;
    }

    /**
     * Returns the users table name.
     * @return string
     */
    public function getUsersTable(): string
    {
        return $this->wpDatabase->users;
    }

    /**
     * Returns the capabilities table name.
     * @return string
     */
    public function getCapabilitiesTable(): string
    {
        return $this->wpDatabase->prefix . 'capabilities';
    }

    /**
     * @param string $query
     * @param int $column
     * @return array
     * @see \wpdb::get_col()
     */
    public function getColumn($query = null, $column = 0): array
    {
        return $this->wpDatabase->get_col($query, $column);
    }

    /**
     * @param string $query
     * @param string $output
     * @param int $row
     * @return array|null|object
     * @see \wpdb::get_row()
     */
    public function getRow($query = null, $output = OBJECT, $row = 0)
    {
        return $this->wpDatabase->get_row($query, $output, $row);
    }

    /**
     * @param null|string $query
     * @param int $column
     * @param int $row
     * @return null|int|string
     * @see \wpdb::get_var()
     */
    public function getVariable($query = null, $column = 0, $row = 0)
    {
        return $this->wpDatabase->get_var($query, $column, $row);
    }

    /**
     * @param int $blogId
     * @return string
     * @see \wpdb::get_blog_prefix()
     */
    public function getBlogPrefix($blogId = null): string
    {
        return $this->wpDatabase->get_blog_prefix($blogId);
    }

    /**
     * @param string $query
     * @param mixed $arguments
     * @return string
     * @see \wpdb::prepare()
     */
    public function prepare(string $query, $arguments): string
    {
        return $this->wpDatabase->prepare($query, $arguments);
    }

    /**
     * @param string $query
     * @return false|int
     * @see \wpdb::query()
     */
    public function query(string $query)
    {
        return $this->wpDatabase->query($query);
    }

    /**
     * @param string $query
     * @param string $output
     * @return array|null|object
     * @see \wpdb::get_results()
     */
    public function getResults($query = null, $output = OBJECT)
    {
        return $this->wpDatabase->get_results($query, $output);
    }

    /**
     * @param string $table
     * @param array $data
     * @param null $format
     * @return false|int
     * @see \wpdb::insert()
     */
    public function insert(string $table, array $data, $format = null)
    {
        return $this->wpDatabase->insert($table, $data, $format);
    }

    /**
     * @param string $table
     * @param array $data
     * @param array $where
     * @param null $format
     * @param null $whereFormat
     * @return false|int
     * @see \wpdb::update()
     */
    public function update(string $table, array $data, array $where, $format = null, $whereFormat = null)
    {
        return $this->wpDatabase->update($table, $data, $where, $format, $whereFormat);
    }

    /**
     * @param string $table
     * @param array $data
     * @param null $format
     * @return false|int
     * @see \wpdb::insert()
     */
    public function replace(string $table, array $data, $format = null)
    {
        return $this->wpDatabase->replace($table, $data, $format);
    }

    /**
     * @param string $table
     * @param array $where
     * @param null $whereFormat
     * @return false|int
     * @see \wpdb::delete()
     */
    public function delete(string $table, array $where, $whereFormat = null)
    {
        return $this->wpDatabase->delete($table, $where, $whereFormat);
    }

    /**
     * Returns the database charset.
     * @return string
     */
    public function getCharset(): string
    {
        $charsetCollate = '';

        $mySlqVersion = $this->getVariable('SELECT VERSION() as mysql_version');

        if (version_compare($mySlqVersion, '4.1.0', '>=')) {
            if (!empty($this->wpDatabase->charset)) {
                $charsetCollate = "DEFAULT CHARACTER SET {$this->wpDatabase->charset}";
            }

            if (!empty($this->wpDatabase->collate)) {
                $charsetCollate .= " COLLATE {$this->wpDatabase->collate}";
            }
        }

        return $charsetCollate;
    }
}
