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

    public function dbDelta(string $queries = '', bool $execute = true): array
    {
        return $this->wordpress->dbDelta($queries, $execute);
    }

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

    public function getColumn(?string $query = null, int $column = 0): array
    {
        return $this->wpDatabase->get_col($query, $column);
    }

    public function getRow(?string $query = null, string $output = OBJECT, int $row = 0): object|array|null
    {
        return $this->wpDatabase->get_row($query, $output, $row);
    }

    public function getVariable(?string $query = null, int $column = 0, int $row = 0): int|string|null
    {
        return $this->wpDatabase->get_var($query, $column, $row);
    }

    public function getBlogPrefix(int|string|null $blogId = null): string
    {
        return $this->wpDatabase->get_blog_prefix($blogId);
    }

    public function prepare(string $query, mixed $arguments): string
    {
        return $this->wpDatabase->prepare($query, $arguments);
    }

    public function query(string $query): bool|int
    {
        return $this->wpDatabase->query($query);
    }

    public function getResults(?string $query = null, string $output = OBJECT): object|array|null
    {
        return $this->wpDatabase->get_results($query, $output);
    }

    public function insert(string $table, array $data, array|string|null $format = null): bool|int
    {
        return $this->wpDatabase->insert($table, $data, $format);
    }

    public function update(
        string $table,
        array $data,
        array $where,
        array|string|null $format = null,
        array|string|null $whereFormat = null
    ): bool|int {
        return $this->wpDatabase->update($table, $data, $where, $format, $whereFormat);
    }

    public function replace(string $table, array $data, array|string|null $format = null): bool|int
    {
        return $this->wpDatabase->replace($table, $data, $format);
    }

    public function delete(string $table, array $where, array|string|null $whereFormat = null): bool|int
    {
        return $this->wpDatabase->delete($table, $where, $whereFormat);
    }

    public function getCharset(): string
    {
        $mySqlVersion = (string) $this->getVariable('SELECT VERSION() as mysql_version');

        if (version_compare($mySqlVersion, '4.1.0', '<')) {
            return '';
        }

        $charsetCollate = '';

        if (!empty($this->wpDatabase->charset)) {
            $charsetCollate = "DEFAULT CHARACTER SET {$this->wpDatabase->charset}";
        }

        if (!empty($this->wpDatabase->collate)) {
            $charsetCollate .= " COLLATE {$this->wpDatabase->collate}";
        }

        return $charsetCollate;
    }
}
