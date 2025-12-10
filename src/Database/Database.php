<?php

declare(strict_types=1);

namespace UserAccessManager\Database;

use UserAccessManager\Wrapper\Wordpress;
use wpdb;

class Database
{
    public const USER_GROUP_TABLE_NAME = 'uam_accessgroups';
    public const USER_GROUP_TO_OBJECT_TABLE_NAME = 'uam_accessgroup_to_object';

    private wpdb $wpDatabase;

    public function __construct(
        private Wordpress $wordpress
    ) {
        $this->wpDatabase = $wordpress->getDatabase();
    }

    public function getWordpressDatabase(): wpdb
    {
        return $this->wpDatabase;
    }

    public function getUserGroupTable(): string
    {
        return $this->wpDatabase->prefix . self::USER_GROUP_TABLE_NAME;
    }

    public function getUserGroupToObjectTable(): string
    {
        return $this->wpDatabase->prefix . self::USER_GROUP_TO_OBJECT_TABLE_NAME;
    }

    /**
     * @see dbDelta()
     */
    public function dbDelta(string $queries = '', bool $execute = true): array
    {
        return $this->wordpress->dbDelta($queries, $execute);
    }

    /**
     * @see wpdb
     */
    public function getPrefix(): string
    {
        return $this->wpDatabase->prefix;
    }

    public function getLastInsertId(): int|string
    {
        return $this->wpDatabase->insert_id;
    }

    public function getCurrentBlogId(): int|string
    {
        return $this->wpDatabase->blogid;
    }

    public function getBlogsTable(): string
    {
        return $this->wpDatabase->blogs;
    }

    public function getPostsTable(): string
    {
        return $this->wpDatabase->posts;
    }

    public function getTermRelationshipsTable(): string
    {
        return $this->wpDatabase->term_relationships;
    }

    public function getTermTaxonomyTable(): string
    {
        return $this->wpDatabase->term_taxonomy;
    }

    public function getUsersTable(): string
    {
        return $this->wpDatabase->users;
    }

    public function getCapabilitiesTable(): string
    {
        return $this->wpDatabase->prefix . 'capabilities';
    }

    /**
     * @see wpdb::get_col
     */
    public function getColumn(string $query = null, int $column = 0): array
    {
        return $this->wpDatabase->get_col($query, $column);
    }

    /**
     * @see wpdb::get_row
     */
    public function getRow(string $query = null, string $output = OBJECT, int $row = 0): object|array|null
    {
        return $this->wpDatabase->get_row($query, $output, $row);
    }

    /**
     * @see wpdb::get_var
     */
    public function getVariable(string $query = null, int $column = 0, int $row = 0): int|string|null
    {
        return $this->wpDatabase->get_var($query, $column, $row);
    }

    /**
     * @see wpdb::get_blog_prefix
     */
    public function getBlogPrefix(int|string|null $blogId = null): string
    {
        return $this->wpDatabase->get_blog_prefix($blogId);
    }

    /**
     * @see wpdb::prepare
     */
    public function prepare(string $query, mixed $arguments): string
    {
        return $this->wpDatabase->prepare($query, $arguments);
    }

    /**
     * @see wpdb::query
     */
    public function query(string $query): bool|int
    {
        return $this->wpDatabase->query($query);
    }

    /**
     * @see wpdb::get_results
     */
    public function getResults(string $query = null, string $output = OBJECT): object|array|null
    {
        return $this->wpDatabase->get_results($query, $output);
    }

    /**
     * @see wpdb::insert
     */
    public function insert(string $table, array $data, $format = null): bool|int
    {
        return $this->wpDatabase->insert($table, $data, $format);
    }

    /**
     * @see wpdb::update
     */
    public function update(string $table, array $data, array $where, $format = null, $whereFormat = null): bool|int
    {
        return $this->wpDatabase->update($table, $data, $where, $format, $whereFormat);
    }

    /**
     * @see wpdb::insert
     */
    public function replace(string $table, array $data, $format = null): bool|int
    {
        return $this->wpDatabase->replace($table, $data, $format);
    }

    /**
     * @see wpdb::delete
     */
    public function delete(string $table, array $where, $whereFormat = null): bool|int
    {
        return $this->wpDatabase->delete($table, $where, $whereFormat);
    }

    public function getCharset(): string
    {
        $charsetCollate = '';

        $mySlqVersion = (string) $this->getVariable('SELECT VERSION() as mysql_version');

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
