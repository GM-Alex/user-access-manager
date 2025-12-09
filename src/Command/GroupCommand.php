<?php

declare(strict_types=1);

namespace UserAccessManager\Command;

use Exception;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Wrapper\WordpressCli;
use WP_CLI\ExitException;
use WP_CLI\Formatter;
use WP_CLI_Command;

class GroupCommand extends WP_CLI_Command
{
    const FORMATTER_PREFIX = 'uam_user_groups';

    private static array $allowedAccessValues = ['group', 'all'];

    public function __construct(
        private WordpressCli $wordpressCli,
        private UserGroupHandler $userGroupHandler,
        private UserGroupFactory $userGroupFactory
    ) {
        parent::__construct();
    }

    /**
     * Returns the formatter
     * @param $assocArguments
     * @return Formatter
     */
    private function getFormatter(&$assocArguments): Formatter
    {
        return $this->wordpressCli->createFormatter(
            $assocArguments,
            [
                'ID',
                'group_name',
                'group_desc',
                'read_access',
                'write_access',
                'roles',
                'ip_range',
            ],
            self::FORMATTER_PREFIX
        );
    }

    /**
     * List groups
     * ## OPTIONS
     * [--format=<format>]: Accepted values: table, csv, JSON, count, ids. Default: table
     * ## EXAMPLES
     * wp uam groups list
     * @subcommand ls
     * @throws ExitException
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function ls(array $arguments, array $assocArguments): void
    {
        if (count($arguments) > 0) {
            $this->wordpressCli->error('No arguments excepted. Please use the format option.');
            return;
        }

        $userGroups = $this->userGroupHandler->getUserGroups();

        if (count($userGroups) <= 0) {
            $this->wordpressCli->error('No groups defined yet!');
            return;
        }

        $groups = [];

        foreach ($userGroups as $userGroup) {
            $groups[$userGroup->getId()] = [
                'ID' => $userGroup->getId(),
                'group_name' => $userGroup->getName(),
                'group_desc' => $userGroup->getDescription(),
                'read_access' => $userGroup->getReadAccess(),
                'write_access' => $userGroup->getWriteAccess(),
                'roles' => implode(
                    ',',
                    array_keys($userGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE))
                ),
                'ip_range' => $userGroup->getIpRange() !== null ? $userGroup->getIpRange() : ''
            ];
        }

        $formatter = $this->getFormatter($assocArguments);
        $formatter->display_items($groups);
    }

    /**
     * delete groups
     * ## OPTIONS
     * <group_id>: id of the group(s) to delete; accepts unlimited ids
     * ## EXAMPLES
     * wp uam groups del 3 5
     * @subcommand del
     * @throws ExitException
     * @throws UserGroupTypeException
     */
    public function del(array $arguments): void
    {
        if (count($arguments) < 1) {
            $this->wordpressCli->error('Expected: wp uam groups del \<id\> ...');
            return;
        }

        foreach ($arguments as $userGroupId) {
            if ($this->userGroupHandler->deleteUserGroup($userGroupId) === true) {
                $this->wordpressCli->success("Successfully deleted group with id '$userGroupId'.");
            } else {
                $this->wordpressCli->error("Group id '$userGroupId' doesn't exists.");
            }
        }
    }

    /**
     * Checks if the user group already exists.
     * @param mixed $userGroupName
     * @return bool
     * @throws ExitException
     * @throws UserGroupTypeException
     */
    private function doesUserGroupExists(mixed $userGroupName): bool
    {
        $userGroups = $this->userGroupHandler->getUserGroups();

        foreach ($userGroups as $userGroup) {
            if ($userGroup->getName() === $userGroupName) {
                $this->wordpressCli->error(
                    "Group with the same name '$userGroupName' already exists: {$userGroup->getId()}"
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Returns the argument value.
     */
    private function getArgumentValue(array $arguments, string $value): string
    {
        return (isset($arguments[$value]) === true) ? (string) $arguments[$value] : '';
    }

    /**
     * Processes the access value.
     */
    private function getAccessValue(array $arguments, string $value, bool $porcelain): string
    {
        $accessValue = $this->getArgumentValue($arguments, $value);

        if (in_array($accessValue, self::$allowedAccessValues) === false) {
            if ($porcelain === true) {
                $this->wordpressCli->line("setting $value to " . self::$allowedAccessValues[0]);
            }

            $accessValue = self::$allowedAccessValues[0];
        }

        return $accessValue;
    }

    /**
     * Creates the user group.
     * @throws UserGroupTypeException
     * @throws Exception
     */
    private function createUserGroup(string $userGroupName, array $assocArguments): UserGroup
    {
        $groupDescription = $this->getArgumentValue($assocArguments, 'desc');
        $ipRange = $this->getArgumentValue($assocArguments, 'ip_range');
        $porcelain = isset($assocArguments['porcelain']);
        $readAccess = $this->getAccessValue($assocArguments, 'read_access', $porcelain);
        $writeAccess = $this->getAccessValue($assocArguments, 'write_access', $porcelain);

        $userGroup = $this->userGroupFactory->createUserGroup();
        $userGroup->setName($userGroupName);
        $userGroup->setDescription($groupDescription);
        $userGroup->setIpRange($ipRange);
        $userGroup->setReadAccess($readAccess);
        $userGroup->setWriteAccess($writeAccess);

        // add roles
        if (isset($assocArguments['roles']) === true) {
            $roles = explode(',', $assocArguments['roles']);

            $userGroup->removeObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

            foreach ($roles as $role) {
                $userGroup->addObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, trim($role));
            }
        }

        $userGroup->save();

        return $userGroup;
    }

    /**
     * add group
     * ## OPTIONS
     * <group_name>: the name of the new group
     * [--porcelain]: Output just the new post id.
     * [--roles=<list>]: comma separated list of group associated roles
     * [--<field>=<value>]: Associative args for a new UamUserGroup object
     * allowed fields and values are:
     * desc="",
     * read_access={group,all*},
     * write_access={group,all*},
     * ip_range="192.168.0.1-192.168.0.10;192.168.0.20-192.168.0.30"
     * *=default
     * ## EXAMPLES
     * wp uam groups add fighters --read_access=all
     * @throws ExitException
     * @throws UserGroupTypeException
     */
    public function add(array $arguments, array $assocArguments): void
    {
        if (isset($arguments[0]) === false) {
            $this->wordpressCli->error("Please provide a group name.");
            return;
        }

        $userGroupName = $arguments[0];

        if ($this->doesUserGroupExists($userGroupName) === false) {
            return;
        }

        $userGroup = $this->createUserGroup($userGroupName, $assocArguments);
        $this->userGroupHandler->addUserGroup($userGroup);

        if (isset($assocArguments['porcelain']) === true) {
            $this->wordpressCli->line($userGroup->getId());
        } else {
            $this->wordpressCli->success("Added new group '$userGroupName' with id {$userGroup->getId()}.");
        }
    }
}
