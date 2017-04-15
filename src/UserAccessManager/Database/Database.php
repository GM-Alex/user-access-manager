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

namespace UserAccessManager\Database;

use UserAccessManager\Wrapper\Wordpress;

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
     * @var \wpdb
     */
    private $wpDatabase;

    /**
     * Database constructor.
     *
     * @param Wordpress $wordpress
     */
    public function __construct(Wordpress $wordpress)
    {
        $this->wordpress = $wordpress;
        $this->wpDatabase = $wordpress->getDatabase();
    }

    /**
     * Returns the wordpress database.
     *
     * @return \wpdb
     */
    public function getWordpressDatabase()
    {
        return $this->wpDatabase;
    }

    /**
     * Returns the user group table name.
     *
     * @return string
     */
    public function getUserGroupTable()
    {
        return $this->wpDatabase->prefix.self::USER_GROUP_TABLE_NAME;
    }

    /**
     * Returns the user group table name.
     *
     * @return string
     */
    public function getUserGroupToObjectTable()
    {
        return $this->wpDatabase->prefix.self::USER_GROUP_TO_OBJECT_TABLE_NAME;
    }

    /**
     * @see dbDelta()
     *
     * @param string $queries
     * @param bool   $execute
     *
     * @return array
     */
    public function dbDelta($queries = '', $execute = true)
    {
        return $this->wordpress->dbDelta($queries, $execute);
    }

    /**
     * @see \wpdb::$prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->wpDatabase->prefix;
    }

    /**
     * Returns the last insert id.
     *
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->wpDatabase->insert_id;
    }

    /**
     * Returns the current blog id.
     *
     * @return int
     */
    public function getCurrentBlogId()
    {
        return $this->wpDatabase->blogid;
    }

    /**
     * Returns the blogs table name.
     *
     * @return string
     */
    public function getBlogsTable()
    {
        return $this->wpDatabase->blogs;
    }

    /**
     * Returns the posts table name.
     *
     * @return string
     */
    public function getPostsTable()
    {
        return $this->wpDatabase->posts;
    }

    /**
     * Returns the term_relationships table name.
     *
     * @return string
     */
    public function getTermRelationshipsTable()
    {
        return $this->wpDatabase->term_relationships;
    }

    /**
     * Returns the term_taxonomy table name.
     *
     * @return string
     */
    public function getTermTaxonomyTable()
    {
        return $this->wpDatabase->term_taxonomy;
    }

    /**
     * Returns the users table name.
     *
     * @return string
     */
    public function getUsersTable()
    {
        return $this->wpDatabase->users;
    }

    /**
     * Returns the capabilities table name.
     *
     * @return string
     */
    public function getCapabilitiesTable()
    {
        return $this->wpDatabase->prefix.'capabilities';
    }

    /**
     * @see \wpdb::get_col()
     *
     * @param string $query
     * @param int    $column
     *
     * @return array
     */
    public function getColumn($query = null, $column = 0)
    {
        return $this->wpDatabase->get_col($query, $column);
    }

    /**
     * @see \wpdb::get_row()
     *
     * @param string $query
     * @param string $output
     * @param int    $row
     *
     * @return array|null|object
     */
    public function getRow($query = null, $output = OBJECT, $row = 0)
    {
        return $this->wpDatabase->get_row($query, $output, $row);
    }

    /**
     * @see \wpdb::get_var()
     *
     * @param null $query
     * @param int  $column
     * @param int  $row
     *
     * @return null|string
     */
    public function getVariable($query = null, $column = 0, $row = 0)
    {
        return $this->wpDatabase->get_var($query, $column, $row);
    }

    /**
     * @see \wpdb::get_blog_prefix()
     *
     * @param int $blogId
     *
     * @return string
     */
    public function getBlogPrefix($blogId = null)
    {
        return $this->wpDatabase->get_blog_prefix($blogId);
    }

    /**
     * @see \wpdb::prepare()
     *
     * @param string $query
     * @param mixed  $arguments
     *
     * @return string
     */
    public function prepare($query, $arguments)
    {
        return $this->wpDatabase->prepare($query, $arguments);
    }

    /**
     * @see \wpdb::query()
     *
     * @param string $query
     *
     * @return false|int
     */
    public function query($query)
    {
        return $this->wpDatabase->query($query);
    }

    /**
     * @see \wpdb::get_results()
     *
     * @param null   $query
     * @param string $output
     *
     * @return array|null|object
     */
    public function getResults($query = null, $output = OBJECT)
    {
        return $this->wpDatabase->get_results($query, $output);
    }

    /**
     * @see \wpdb::insert()
     *
     * @param string       $table
     * @param array        $data
     * @param array|string $format
     *
     * @return false|int
     */
    public function insert($table, array $data, $format = null)
    {
        return $this->wpDatabase->insert($table, $data, $format);
    }

    /**
     * @see \wpdb::update()
     *
     * @param string       $table
     * @param array        $data
     * @param array        $where
     * @param array|string $format
     * @param array|string $whereFormat
     *
     * @return false|int
     */
    public function update($table, array $data, array $where, $format = null, $whereFormat = null)
    {
        return $this->wpDatabase->update($table, $data, $where, $format, $whereFormat);
    }

    /**
     * @see \wpdb::delete()
     *
     * @param string       $table
     * @param array        $where
     * @param array|string $whereFormat
     *
     * @return false|int
     */
    public function delete($table, array $where, $whereFormat = null)
    {
        return $this->wpDatabase->delete($table, $where, $whereFormat);
    }

    /**
     * Returns the database charset.
     *
     * @return string
     */
    public function getCharset()
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
