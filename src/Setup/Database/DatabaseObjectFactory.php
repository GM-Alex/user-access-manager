<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Database;

class DatabaseObjectFactory
{
    /**
     * @throws MissingColumnsException
     */
    public function createTable(string $name, string $charsetCollate, array $columns): Table
    {
        return new Table($name, $charsetCollate, $columns);
    }

    public function createColumn(
        string $name,
        string $type,
        bool $isNull = false,
        mixed $default = null,
        bool $isKey = false,
        bool $isAutoIncrement = false
    ): Column {
        return new Column($name, $type, $isNull, $default, $isKey, $isAutoIncrement);
    }
}
