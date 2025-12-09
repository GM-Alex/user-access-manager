<?php
/**
 * Column.php
 *
 * The Column class file.
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

namespace UserAccessManager\Setup\Database;

/**
 * Class Column
 *
 * @package UserAccessManager\Setup\Database
 */
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
