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
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int|string
     */
    private $default;

    /**
     * @var bool
     */
    private $isNull;

    /**
     * @var bool
     */
    private $isKey;

    /**
     * @var bool
     */
    private $isAutoIncrement;

    /**
     * Column constructor.
     * @param string $name
     * @param string $type
     * @param bool $isNull
     * @param mixed $default
     * @param bool $isKey
     * @param bool $isAutoIncrement
     */
    public function __construct(
        string $name,
        string $type,
        $isNull = false,
        $default = null,
        $isKey = false,
        $isAutoIncrement = false
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->isNull = $isNull;
        $this->default = $default;
        $this->isKey = $isKey;
        $this->isAutoIncrement = $isAutoIncrement;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int|string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->isNull;
    }

    /**
     * @return bool
     */
    public function isKey(): bool
    {
        return $this->isKey;
    }

    /**
     * @return bool
     */
    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    /**
     * Returns a mysql column string.
     * @return string
     */
    public function __toString(): string
    {
        $nullConstraint = ($this->isNull) ? 'NULL' : 'NOT NULL';
        $column = "`{$this->name}` {$this->type} {$nullConstraint}";

        if ($this->default === null && $this->isNull) {
            $column .= ' DEFAULT NULL';
        } elseif ($this->default !== null) {
            $defaultValue = is_numeric($this->default) === false ? "'{$this->default}'" : $this->default;
            $column .= " DEFAULT {$defaultValue}";
        }

        if ($this->isAutoIncrement) {
            $column .= ' AUTO_INCREMENT';
        }

        return $column;
    }
}
