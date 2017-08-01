<?php
/**
 * GroupCommand.php
 *
 * The GroupCommand class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @author    Nils Woetzel nils.woetzel@h-its.org
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Command;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Wrapper\WordpressCli;
use WP_CLI\CommandWithDBObject;

/**
 * Class GroupCommand
 *
 * @package UserAccessManager\Command
 */
class GroupCommand extends CommandWithDBObject
{
    const FORMATTER_PREFIX = 'uam_user_groups';

    /**
     * @var array
     */
    private static $allowedAccessValues = ['group', 'all'];

    /**
     * @var WordpressCli
     */
    private $wordpressCli;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

    /**
     * ObjectCommand constructor.
     *
     * @param WordpressCli     $wordpressCli
     * @param AccessHandler    $accessHandler
     * @param UserGroupFactory $userGroupFactory
     */
    public function __construct(
        WordpressCli $wordpressCli,
        AccessHandler $accessHandler,
        UserGroupFactory $userGroupFactory
    ) {
        $this->wordpressCli = $wordpressCli;
        $this->accessHandler = $accessHandler;
        $this->userGroupFactory = $userGroupFactory;
    }

    /**
     * Returns the formatter
     *
     * @param $assocArguments
     *
     * @return \WP_CLI\Formatter
     */
    private function getFormatter(&$assocArguments)
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
     * list groups
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Accepted values: table, csv, json, count, ids. Default: table
     *
     * ## EXAMPLES
     *
     * wp uam groups list
     *
     * @subcommand ls
     *
     * @param array $arguments
     * @param array $assocArguments
     */
    public function ls(array $arguments, array $assocArguments)
    {
        if (count($arguments) > 0) {
            $this->wordpressCli->error('No arguments excepted. Please use the format option.');
            return;
        }

        $userGroups = $this->accessHandler->getUserGroups();

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
     *
     * ## OPTIONS
     * <group_id>
     * : id of the group(s) to delete; accepts unlimited ids
     *
     * ## EXAMPLES
     *
     * wp uam groups del 3 5
     *
     * @subcommand del
     *
     * @param array $arguments
     */
    public function del(array $arguments)
    {
        if (count($arguments) < 1) {
            $this->wordpressCli->error('Expected: wp uam groups del \<id\> ...');
            return;
        }

        foreach ($arguments as $userGroupId) {
            if ($this->accessHandler->deleteUserGroup($userGroupId) === true) {
                $this->wordpressCli->success("Successfully deleted group with id '{$userGroupId}'.");
            } else {
                $this->wordpressCli->error("Group id '{$userGroupId}' doesn't exists.");
            }
        }
    }

    /**
     * add group
     *
     * ## OPTIONS
     * <group_name>
     * : the name of the new group
     *
     * [--porcelain]
     * : Output just the new post id.
     *
     * [--roles=<list>]
     * : comma separated list of group associated roles
     *
     * [--<field>=<value>]
     * : Associative args for new UamUserGroup object
     * allowed fields and values are:
     * desc="",
     * read_access={group,all*},
     * write_access={group,all*},
     * ip_range="192.168.0.1-192.168.0.10;192.168.0.20-192.168.0.30"
     *
     * *=default
     *
     * ## EXAMPLES
     *
     * wp uam groups add fighters --read_access=all
     *
     * @param array $arguments
     * @param array $assocArguments
     */
    public function add(array $arguments, array $assocArguments)
    {
        if (isset($arguments[0]) === false) {
            $this->wordpressCli->error("Please provide a group name.");
            return;
        }

        $groupName = $arguments[0];
        $userGroups = $this->accessHandler->getUserGroups();

        foreach ($userGroups as $userGroup) {
            if ($userGroup->getName() === $groupName) {
                $this->wordpressCli->error(
                    "Group with the same name '{$groupName}' already exists: {$userGroup->getId()}"
                );
                return;
            }
        }

        $groupDescription = (isset($assocArguments['desc']) === true) ? $assocArguments['desc'] : '';
        $ipRange = (isset($assocArguments['ip_range']) === true) ? $assocArguments['ip_range'] : '';
        $readAccess = (isset($assocArguments['read_access']) === true) ? $assocArguments['read_access'] : '';
        $writeAccess = (isset($assocArguments['write_access']) === true) ? $assocArguments['write_access'] : '';
        $porcelain = isset($assocArguments['porcelain']);

        if (in_array($readAccess, self::$allowedAccessValues) === false) {
            if ($porcelain === true) {
                $this->wordpressCli->line('setting read_access to '.self::$allowedAccessValues[0]);
            }

            $readAccess = self::$allowedAccessValues[0];
        }

        if (in_array($writeAccess, self::$allowedAccessValues) === false) {
            if ($porcelain === true) {
                $this->wordpressCli->line('setting write_access to '.self::$allowedAccessValues[0]);
            }

            $writeAccess = self::$allowedAccessValues[0];
        }

        $userGroup = $this->userGroupFactory->createUserGroup();
        $userGroup->setName($groupName);
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

        $this->accessHandler->addUserGroup($userGroup);

        if ($porcelain === true) {
            $this->wordpressCli->line($userGroup->getId());
        } else {
            $this->wordpressCli->success("Added new group '{$groupName}' with id {$userGroup->getId()}.");
        }
    }
}
