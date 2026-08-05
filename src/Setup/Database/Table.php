<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Database;

class Table
{
    /**
     * @throws MissingColumnsException
     */
    public function __construct(
        private string $name,
        private string $charsetCollate,
        private array $columns
    ) {
        if ($this->columns === []) {
            throw new MissingColumnsException('The table needs at least one column.');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCharsetCollate(): string
    {
        return $this->charsetCollate;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function __toString(): string
    {
        $columns = implode(', ', $this->columns);
        $primaryKeys = array_map(
            static fn(Column $column) => "`{$column->getName()}`",
            array_filter($this->columns, static fn(Column $column) => $column->isKey() === true)
        );

        $primaryKeysQuery = $primaryKeys === []
            ? ''
            : ', PRIMARY KEY (' . implode(', ', $primaryKeys) . ')';

        return "CREATE TABLE `$this->name` (
                $columns{$primaryKeysQuery}
            ) $this->charsetCollate;";
    }
}
