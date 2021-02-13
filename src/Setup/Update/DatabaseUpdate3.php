<?php
/**
 * DatabaseUpdate3.php
 *
 * The DatabaseUpdate3 class file.
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
 * Class DatabaseUpdate3
 *
 * @package UserAccessManager\Setup\Update
 */
class DatabaseUpdate3 extends DatabaseUpdate
{
    /**
     * Returns the version.
     * @return string
     */
    public function getVersion(): string
    {
        return '1.3';
    }

    /**
     * Executes the update.
     * @return bool
     */
    public function update(): bool
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $generalTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $update = $this->database->update(
            $dbAccessGroupToObject,
            ['object_type' => $generalTermType],
            ['object_type' => 'category']
        );

        return $update !== false;
    }
}
