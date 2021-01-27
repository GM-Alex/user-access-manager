<?php
/**
 * Table.php
 *
 * The Table class file.
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
 * Class Table
 *
 * @package UserAccessManager\Setup\Database
 */
class Table
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $charsetCollate;

    /**
     * @var Column[]
     */
    private $columns;

    /**
     * Table constructor.
     * @param string $name
     * @param string $charsetCollate
     * @param array $columns
     * @throws MissingColumnsException
     */
    public function __construct(string $name, string $charsetCollate, array $columns)
    {
        $this->name = $name;
        $this->charsetCollate = $charsetCollate;

        if ($columns === []) {
            throw new MissingColumnsException('The table needs at least one column.');
        }

        $this->columns = $columns;
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

    /**
     * Returns the table in sql format.
     * @return string
     */
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
            $primaryKeysQuery = ", PRIMARY KEY ({$primaryKeysQuery})";
        }

        return "CREATE TABLE `{$this->name}` (
                {$columns}{$primaryKeysQuery}
            ) {$this->charsetCollate};";
    }
}
