<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
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
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        ObjectHandler $objectHandler,
        AssignedObjectsLoader $assignedObjectsLoader,
        int|string|null $id = null
    ) {
        parent::__construct($wordpress, $database, $config, $objectHandler, $assignedObjectsLoader);

        if ($id !== null) {
            $this->load($id);
        }
    }

    public function getIpRange(): ?string
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

        $databaseUserGroup = $this->database->getRow($query);

        if ($databaseUserGroup === null) {
            return false;
        }

        $this->assignDatabaseValues($databaseUserGroup);

        return true;
    }

    public function assignDatabaseValues(object $databaseUserGroup): void
    {
        $this->id = $databaseUserGroup->ID;
        $this->name = $databaseUserGroup->groupname;
        $this->description = $databaseUserGroup->groupdesc;
        $this->readAccess = $databaseUserGroup->read_access;
        $this->writeAccess = $databaseUserGroup->write_access;
        $this->ipRange = $databaseUserGroup->ip_range;
    }

    public function save(): bool
    {
        $columns = [
            'groupname' => $this->name,
            'groupdesc' => $this->description,
            'read_access' => $this->readAccess,
            'write_access' => $this->writeAccess,
            'ip_range' => $this->ipRange
        ];

        if ($this->id !== null) {
            return $this->database->update(
                $this->database->getUserGroupTable(),
                $columns,
                ['ID' => $this->id]
            ) !== false;
        }

        $return = $this->database->insert($this->database->getUserGroupTable(), $columns);

        if ($return === false) {
            return false;
        }

        $this->id = (string) $this->database->getLastInsertId();

        return true;
    }

    /**
     * @throws Exception
     */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $deleted = $this->database->delete(
            $this->database->getUserGroupTable(),
            ['ID' => $this->id]
        );

        return ($deleted !== false) && parent::delete();
    }
}
