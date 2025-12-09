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
        if (count($this->columns) <= 0) {
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
        $primaryKeys = [];

        foreach ($this->columns as $column) {
            if ($column->isKey() === true) {
                $primaryKeys[] = "`{$column->getName()}`";
            }
        }

        $primaryKeysQuery = '';

        if ($primaryKeys !== []) {
            $primaryKeysQuery = implode(', ', $primaryKeys);
            $primaryKeysQuery = ", PRIMARY KEY ($primaryKeysQuery)";
        }

        return "CREATE TABLE `$this->name` (
                $columns{$primaryKeysQuery}
            ) $this->charsetCollate;";
    }
}
