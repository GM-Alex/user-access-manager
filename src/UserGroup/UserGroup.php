<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class UserGroup extends AbstractUserGroup
{
    const USER_GROUP_TYPE = 'UserGroup';

    protected ?string $type = self::USER_GROUP_TYPE;
    protected ?string $ipRange = null;

    /**
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
        int|string|null $id = null
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

    public function getIpRange(): array|string|null
    {
        return $this->ipRange;
    }

    public function getIpRangeArray(): array
    {
        return explode(';', (string) $this->ipRange);
    }

    public function setIpRange(array|string $ipRange): void
    {
        $this->ipRange = (is_array($ipRange) === true) ? implode(';', $ipRange) : $ipRange;
    }

    public function load(int|string $id): bool
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
