<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Database;

class Column
{
    public function __construct(
        private string $name,
        private string $type,
        private bool $isNull = false,
        private mixed $default = null,
        private bool $isKey = false,
        private bool $isAutoIncrement = false
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefault(): int|string|null
    {
        return $this->default;
    }

    public function isNull(): bool
    {
        return $this->isNull;
    }

    public function isKey(): bool
    {
        return $this->isKey;
    }

    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    public function __toString(): string
    {
        $nullConstraint = ($this->isNull) ? 'NULL' : 'NOT NULL';
        // MySQL reports a declared INT as INT(11); normalise so schema comparison sees no difference.
        $type = $this->type === 'INT(11)' ? 'INT' : $this->type;
        $column = "`$this->name` $type $nullConstraint";

        if ($this->default === null && $this->isNull) {
            $column .= ' DEFAULT NULL';
        } elseif ($this->default !== null) {
            $defaultValue = is_numeric($this->default) === false ? "'$this->default'" : $this->default;
            $column .= " DEFAULT $defaultValue";
        }

        if ($this->isAutoIncrement) {
            $column .= ' AUTO_INCREMENT';
        }

        return $column;
    }
}
