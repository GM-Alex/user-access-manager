<?php
/**
 * Update6.php
 *
 * The Update6 class file.
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

use UserAccessManager\UserGroup\UserGroup;

/**
 * Class Update6
 *
 * @package UserAccessManager\Setup\Update
 */
class Update6 extends Update implements UpdateInterface
{
    /**
     * Returns the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.6';
    }

    /**
     * Executes the update.
     *
     * @return bool
     */
    public function update()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $alterQuery = "ALTER TABLE {$dbAccessGroupToObject}
            ADD group_type VARCHAR(64) NOT NULL AFTER group_id,
            ADD from_date DATETIME NULL DEFAULT NULL,
            ADD to_date DATETIME NULL DEFAULT NULL,
            MODIFY group_id VARCHAR(64) NOT NULL,
            MODIFY object_id VARCHAR(64) NOT NULL,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (object_id, object_type, group_id, group_type)";

        $success = $this->database->query($alterQuery) !== false;

        $update = $this->database->update(
            $dbAccessGroupToObject,
            ['group_type' => UserGroup::USER_GROUP_TYPE],
            ['group_type' => '']
        );

        return $success && $update !== false;
    }
}
