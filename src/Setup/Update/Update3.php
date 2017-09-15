<?php
/**
 * Update3.php
 *
 * The Update3 class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Object\ObjectHandler;

/**
 * Class Update3
 *
 * @package UserAccessManager\Setup\Update
 */
class Update3 extends Update implements UpdateInterface
{
    /**
     * Returns the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.3';
    }

    /**
     * Executes the update.
     *
     * @return bool
     */
    public function update()
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
