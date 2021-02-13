<?php
/**
 * DatabaseUpdate2.php
 *
 * The DatabaseUpdate2 class file.
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

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Setup\Database\DatabaseUpdate;

/**
 * Class DatabaseUpdate2
 *
 * @package UserAccessManager\Setup\Update
 */
class DatabaseUpdate2 extends DatabaseUpdate
{
    /**
     * Returns the version.
     * @return string
     */
    public function getVersion(): string
    {
        return '1.2';
    }

    /**
     * Executes the update.
     * @return bool
     */
    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "ALTER TABLE `{$dbAccessGroupToObject}`
            CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
            CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

        return $this->database->query($query) !== false;
    }
}
