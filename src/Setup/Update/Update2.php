<?php
/**
 * Update2.php
 *
 * The Update2 class file.
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

/**
 * Class Update2
 *
 * @package UserAccessManager\Setup\Update
 */
class Update2 extends Update implements UpdateInterface
{
    /**
     * Returns the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.2';
    }

    /**
     * Executes the update.
     *
     * @return bool
     */
    public function update()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "ALTER TABLE `{$dbAccessGroupToObject}`
            CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
            CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

        return $this->database->query($query) !== false;
    }
}
