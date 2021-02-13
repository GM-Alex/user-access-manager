<?php
/**
 * UserGroup.php
 *
 * The UserGroup class file.
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

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class UserGroup
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroup extends AbstractUserGroup
{
    const USER_GROUP_TYPE = 'UserGroup';

    /**
     * @var string
     */
    protected $type = self::USER_GROUP_TYPE;

    /**
     * @var string
     */
    protected $ipRange = null;

    /**
     * UserGroup constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param Database $database
     * @param MainConfig $config
     * @param Util $util
     * @param ObjectHandler $objectHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param null|string $id
     * @throws UserGroupTypeException
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory,
        $id = null
    ) {
        parent::__construct(
            $php,
            $wordpress,
            $database,
            $config,
            $util,
            $objectHandler,
            $assignmentInformationFactory
        );

        if ($id !== null) {
            $this->load($id);
        }
    }

    /**
     * Returns the ip range.
     * @return array|string
     */
    public function getIpRange()
    {
        return $this->ipRange;
    }

    /**
     * Returns the ip range as array
     * @return array
     */
    public function getIpRangeArray(): array
    {
        return explode(';', $this->ipRange);
    }

    /**
     * Sets the ip range.
     * @param string|array $ipRange The new ip range.
     */
    public function setIpRange($ipRange)
    {
        $this->ipRange = (is_array($ipRange) === true) ? implode(';', $ipRange) : $ipRange;
    }

    /**
     * Loads the user group.
     * @param int|string $id
     * @return bool
     */
    public function load($id): bool
    {
        $query = $this->database->prepare(
            "SELECT *
            FROM {$this->database->getUserGroupTable()}
            WHERE ID = %d
            LIMIT 1",
            $id
        );

        $dbUserGroup = $this->database->getRow($query);

        if ($dbUserGroup !== null) {
            $this->id = $id;
            $this->name = $dbUserGroup->groupname;
            $this->description = $dbUserGroup->groupdesc;
            $this->readAccess = $dbUserGroup->read_access;
            $this->writeAccess = $dbUserGroup->write_access;
            $this->ipRange = $dbUserGroup->ip_range;

            return true;
        }

        return false;
    }

    /**
     * Saves the user group.
     * @return bool
     */
    public function save(): bool
    {
        if ($this->id === null) {
            $return = $this->database->insert(
                $this->database->getUserGroupTable(),
                [
                    'groupname' => $this->name,
                    'groupdesc' => $this->description,
                    'read_access' => $this->readAccess,
                    'write_access' => $this->writeAccess,
                    'ip_range' => $this->ipRange
                ]
            );

            if ($return !== false) {
                $this->id = (string) $this->database->getLastInsertId();
            }
        } else {
            $return = $this->database->update(
                $this->database->getUserGroupTable(),
                [
                    'groupname' => $this->name,
                    'groupdesc' => $this->description,
                    'read_access' => $this->readAccess,
                    'write_access' => $this->writeAccess,
                    'ip_range' => $this->ipRange
                ],
                ['ID' => $this->id]
            );
        }

        return ($return !== false);
    }

    /**
     * Deletes the user group.
     * @return bool
     * @throws Exception
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $success = $this->database->delete(
            $this->database->getUserGroupTable(),
            ['ID' => $this->id]
        );

        if ($success !== false) {
            $success = parent::delete();
        }

        return $success;
    }
}
