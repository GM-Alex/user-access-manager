<?php
/**
 * DatabaseUpdate5.php
 *
 * The DatabaseUpdate5 class file.
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

use Exception;
use UserAccessManager\Setup\Database\DatabaseUpdate;

/**
 * Class DatabaseUpdate5
 *
 * @package UserAccessManager\Setup\Update
 */
class DatabaseUpdate5 extends DatabaseUpdate
{
    /**
     * Returns the version.
     * @return string
     */
    public function getVersion(): string
    {
        return '1.5.1';
    }

    /**
     * Executes the update.
     * @return bool
     * @throws Exception
     */
    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "SELECT object_id AS objectId, object_type AS objectType, group_id AS groupId
            FROM {$dbAccessGroupToObject}
            WHERE general_object_type = ''";

        $dbObjects = (array) $this->database->getResults($query);
        $success = true;

        foreach ($dbObjects as $dbObject) {
            $update = $this->database->update(
                $dbAccessGroupToObject,
                ['general_object_type' => $this->objectHandler->getGeneralObjectType($dbObject->objectType)],
                [
                    'object_id' => $dbObject->objectId,
                    'group_id' => $dbObject->groupId,
                    'object_type' => $dbObject->objectType
                ]
            );
            $success = $success && ($update !== false);
        }

        return $success;
    }
}
