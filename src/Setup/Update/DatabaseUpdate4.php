<?php
/**
 * DatabaseUpdate4.php
 *
 * The DatabaseUpdate4 class file.
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

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Setup\Database\DatabaseUpdate;

/**
 * Class DatabaseUpdate4
 *
 * @package UserAccessManager\Setup\Update
 */
class DatabaseUpdate4 extends DatabaseUpdate
{
    /**
     * Returns the version.
     * @return string
     */
    public function getVersion(): string
    {
        return '1.4.1';
    }

    /**
     * Executes the update.
     * @return bool
     */
    public function update(): bool
    {
        $success = true;

        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $alterQuery = "ALTER TABLE {$dbAccessGroupToObject}
            ADD general_object_type VARCHAR(64) NOT NULL AFTER object_id";

        $success = $success && $this->database->query($alterQuery) !== false;

        // Update post entries
        $generalPostType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalPostType}'
            WHERE object_type IN ('post', 'page', 'attachment')";

        $success = $success && $this->database->query($query) !== false;

        // Update role entries
        $generalRoleType = ObjectHandler::GENERAL_ROLE_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalRoleType}'
            WHERE object_type = 'role'";

        $success = $success && $this->database->query($query) !== false;

        // Update user entries
        $generalUserType = ObjectHandler::GENERAL_USER_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalUserType}'
            WHERE object_type = 'user'";

        $success = $success && $this->database->query($query) !== false;

        // Update term entries
        $generalTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;

        $query = "UPDATE {$dbAccessGroupToObject}
            SET general_object_type = '{$generalTermType}'
            WHERE object_type = 'term'";

        $success = $success && $this->database->query($query) !== false;

        $query = "UPDATE {$dbAccessGroupToObject} AS gto
            LEFT JOIN {$this->database->getTermTaxonomyTable()} AS tt 
              ON gto.object_id = tt.term_id
            SET gto.object_type = tt.taxonomy
            WHERE gto.general_object_type = '{$generalTermType}'";

        return $success && $this->database->query($query) !== false;
    }
}
