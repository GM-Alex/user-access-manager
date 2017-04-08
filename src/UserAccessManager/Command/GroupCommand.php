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
 * @version   SVN: $Id$
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
    private static $aAllowedAccessValues = ['group', 'all'];

    /**
     * @var WordpressCli
     */
    protected $WordpressCli;

    /**
     * @var AccessHandler
     */
    protected $AccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $UserGroupFactory;

    /**
     * ObjectCommand constructor.
     *
     * @param WordpressCli     $WordpressCli
     * @param AccessHandler    $AccessHandler
     * @param UserGroupFactory $UserGroupFactory
     */
    public function __construct(
        WordpressCli $WordpressCli,
        AccessHandler $AccessHandler,
        UserGroupFactory $UserGroupFactory
    ) {
        $this->WordpressCli = $WordpressCli;
        $this->AccessHandler = $AccessHandler;
        $this->UserGroupFactory = $UserGroupFactory;
    }

    /**
     * Returns the formatter
     *
     * @param $aAssocArguments
     *
     * @return \WP_CLI\Formatter
     */
    protected function getFormatter(&$aAssocArguments)
    {
        return $this->WordpressCli->createFormatter(
            $aAssocArguments,
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
     * @param array $aArguments
     * @param array $aAssocArguments
     */
    public function ls(array $aArguments, array $aAssocArguments)
    {
        if (count($aArguments) > 0) {
            $this->WordpressCli->error('No arguments excepted. Please use the format option.');
            return;
        }

        $aUserGroups = $this->AccessHandler->getUserGroups();

        if (count($aUserGroups) <= 0) {
            $this->WordpressCli->error('No groups defined yet!');
            return;
        }

        $aGroups = [];

        foreach ($aUserGroups as $UserGroup) {
            $aGroups[$UserGroup->getId()] = [
                'ID' => $UserGroup->getId(),
                'group_name' => $UserGroup->getName(),
                'group_desc' => $UserGroup->getDescription(),
                'read_access' => $UserGroup->getReadAccess(),
                'write_access' => $UserGroup->getWriteAccess(),
                'roles' => implode(
                    ',',
                    array_keys($UserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE))
                ),
                'ip_range' => $UserGroup->getIpRange() !== null ? $UserGroup->getIpRange(true) : ''
            ];
        }

        $Formatter = $this->getFormatter($aAssocArguments);
        $Formatter->display_items($aGroups);
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
     * @param array $aArguments
     */
    public function del(array $aArguments)
    {
        if (count($aArguments) < 1) {
            $this->WordpressCli->error('Expected: wp uam groups del \<id\> ...');
            return;
        }

        foreach ($aArguments as $sUserGroupId) {
            if ($this->AccessHandler->deleteUserGroup($sUserGroupId) === true) {
                $this->WordpressCli->success("Successfully deleted group with id '{$sUserGroupId}'.");
            } else {
                $this->WordpressCli->error("Group id '{$sUserGroupId}' doesn't exists.");
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
     * @param array $aArguments
     * @param array $aAssocArguments
     */
    public function add(array $aArguments, array $aAssocArguments)
    {
        if (isset($aArguments[0]) === false) {
            $this->WordpressCli->error("Please provide a group name.");
            return;
        }

        $sGroupName = $aArguments[0];
        $aUserGroups = $this->AccessHandler->getUserGroups();

        foreach ($aUserGroups as $UserGroup) {
            if ($UserGroup->getName() === $sGroupName) {
                $this->WordpressCli->error(
                    "Group with the same name '{$sGroupName}' already exists: {$UserGroup->getId()}"
                );
                return;
            }
        }

        $sGroupDescription = (isset($aAssocArguments['desc']) === true) ? $aAssocArguments['desc'] : '';
        $sIpRange = (isset($aAssocArguments['ip_range']) === true) ? $aAssocArguments['ip_range'] : '';
        $sReadAccess = (isset($aAssocArguments['read_access']) === true) ? $aAssocArguments['read_access'] : '';
        $sWriteAccess = (isset($aAssocArguments['write_access']) === true) ? $aAssocArguments['write_access'] : '';
        $blPorcelain = isset($aAssocArguments['porcelain']);

        if (!in_array($sReadAccess, self::$aAllowedAccessValues)) {
            if ($blPorcelain === true) {
                $this->WordpressCli->line('setting read_access to '.self::$aAllowedAccessValues[0]);
            }

            $sReadAccess = self::$aAllowedAccessValues[0];
        }

        if (!in_array($sWriteAccess, self::$aAllowedAccessValues)) {
            if ($blPorcelain === true) {
                $this->WordpressCli->line('setting write_access to '.self::$aAllowedAccessValues[0]);
            }

            $sWriteAccess = self::$aAllowedAccessValues[0];
        }

        $UserGroup = $this->UserGroupFactory->createUserGroup();
        $UserGroup->setName($sGroupName);
        $UserGroup->setDescription($sGroupDescription);
        $UserGroup->setIpRange($sIpRange);
        $UserGroup->setReadAccess($sReadAccess);
        $UserGroup->setWriteAccess($sWriteAccess);

        // add roles
        if (isset($aAssocArguments['roles'])) {
            $aRoles = explode(',', $aAssocArguments['roles']);

            $UserGroup->removeObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

            foreach ($aRoles as $sRole) {
                $UserGroup->addObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, trim($sRole));
            }
        }

        $UserGroup->save();

        $this->AccessHandler->addUserGroup($UserGroup);

        if ($blPorcelain === true) {
            $this->WordpressCli->line($UserGroup->getId());
        } else {
            $this->WordpressCli->success("Added new group '{$sGroupName}' with id {$UserGroup->getId()}.");
        }
    }
}
