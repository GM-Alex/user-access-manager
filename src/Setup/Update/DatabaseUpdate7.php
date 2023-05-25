<?php
/**
 * DatabaseUpdate6.php
 *
 * The DatabaseUpdate6 class file.
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
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class DatabaseUpdate6
 *
 * @package UserAccessManager\Setup\Update
 */
class DatabaseUpdate7 extends DatabaseUpdate
{
    /**
     * Returns the version.
     * @return string
     */
    public function getVersion(): string
    {
        return '1.6.2';
    }

    /**
     * Executes the update.
     * @return bool
     */
    public function update(): bool
    {
        $userGroupTable = $this->database->getUserGroupTable();
        $alterQuery = "ALTER TABLE {$userGroupTable}
            MODIFY ID INT NOT NULL";

        return $this->database->query($alterQuery) !== false;
    }
}
