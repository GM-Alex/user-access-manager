<?php
/**
 * DatabaseObjectFactory.php
 *
 * The DatabaseObjectFactory class file.
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
 * Class DatabaseObjectFactory
 *
 * @package UserAccessManager\Setup\Database
 */
class DatabaseObjectFactory
{
    /**
     * Creates a table object.
     * @param string $name
     * @param string $charsetCollate
     * @param array $columns
     * @return Table
     * @throws MissingColumnsException
     */
    public function createTable(string $name, string $charsetCollate, array $columns): Table
    {
        return new Table($name, $charsetCollate, $columns);
    }

    /**
     * Creates a column object.
     * @param string $name
     * @param string $type
     * @param bool $isNull
     * @param mixed $default
     * @param bool $isKey
     * @param bool $isAutoIncrement
     * @return Column
     */
    public function createColumn(
        string $name,
        string $type,
        $isNull = false,
        $default = null,
        $isKey = false,
        $isAutoIncrement = false
    ): Column {
        return new Column($name, $type, $isNull, $default, $isKey, $isAutoIncrement);
    }
}
