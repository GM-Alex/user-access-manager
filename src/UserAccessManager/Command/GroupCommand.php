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
    protected $_oWordpressCli;

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $_oUserGroupFactory;

    /**
     * ObjectCommand constructor.
     *
     * @param WordpressCli     $oWordpressCli
     * @param AccessHandler    $oAccessHandler
     * @param UserGroupFactory $oUserGroupFactory
     */
    public function __construct(
        WordpressCli $oWordpressCli,
        AccessHandler $oAccessHandler,
        UserGroupFactory $oUserGroupFactory
    )
    {
        $this->_oWordpressCli = $oWordpressCli;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oUserGroupFactory = $oUserGroupFactory;
    }

    /**
     * Returns the formatter
     *
     * @param $aAssocArguments
     *
     * @return \WP_CLI\Formatter
     */
    protected function _getFormatter(&$aAssocArguments)
    {
        return $this->_oWordpressCli->createFormatter(
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
            $this->_oWordpressCli->error('No arguments excepted. Please use the format option.');
            return;
        }

        $aUserGroups = $this->_oAccessHandler->getUserGroups();
        echo count($aUserGroups);

        if (count($aUserGroups) <= 0) {
            $this->_oWordpressCli->error('No groups defined yet!');
            return;
        }

        $aGroups = [];

        foreach ($aUserGroups as $oUserGroup) {
            $aGroups[$oUserGroup->getId()] = [
                'ID' => $oUserGroup->getId(),
                'group_name' => $oUserGroup->getName(),
                'group_desc' => $oUserGroup->getDescription(),
                'read_access' => $oUserGroup->getReadAccess(),
                'write_access' => $oUserGroup->getWriteAccess(),
                'roles' => implode(',', array_keys($oUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE))),
                'ip_range' => $oUserGroup->getIpRange(true) === null ? '' : $oUserGroup->getIpRange(true)
            ];
        }

        $oFormatter = $this->_getFormatter($aAssocArguments);
        $oFormatter->display_items($aGroups);
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
            $this->_oWordpressCli->error('Expected: wp uam groups del \<id\> ...');
            return;
        }

        foreach ($aArguments as $sUserGroupId) {
            if ($this->_oAccessHandler->deleteUserGroup($sUserGroupId) === true) {
                $this->_oWordpressCli->success("Successfully deleted group with id '{$sUserGroupId}'.");
            } else {
                $this->_oWordpressCli->error("Group id '{$sUserGroupId}' doesn't exists.");
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
     * allowed fields and values are: desc="", read_access={group,all*}, write_access={group,all*}, ip_range="192.168.0.1-192.168.0.10;192.168.0.20-192.168.0.30"
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
            $this->_oWordpressCli->error("Please provide a group name.");
            return;
        }

        $sGroupName = $aArguments[0];
        $aUserGroups = $this->_oAccessHandler->getUserGroups();

        foreach ($aUserGroups as $oUserGroup) {
            if ($oUserGroup->getName() === $sGroupName) {
                $this->_oWordpressCli->error("Group with the same name '{$sGroupName}' already exists: {$oUserGroup->getId()}");
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
                $this->_oWordpressCli->line('setting read_access to '.self::$aAllowedAccessValues[0]);
            }

            $sReadAccess = self::$aAllowedAccessValues[0];
        }

        if (!in_array($sWriteAccess, self::$aAllowedAccessValues)) {
            if ($blPorcelain === true) {
                $this->_oWordpressCli->line('setting write_access to '.self::$aAllowedAccessValues[0]);
            }

            $sWriteAccess = self::$aAllowedAccessValues[0];
        }

        $oUserGroup = $this->_oUserGroupFactory->createUserGroup();
        $oUserGroup->setName($sGroupName);
        $oUserGroup->setDescription($sGroupDescription);
        $oUserGroup->setIpRange($sIpRange);
        $oUserGroup->setReadAccess($sReadAccess);
        $oUserGroup->setWriteAccess($sWriteAccess);

        // add roles
        if (isset($aAssocArguments['roles'])) {
            $aRoles = explode(',', $aAssocArguments['roles']);

            $oUserGroup->removeObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

            foreach ($aRoles as $sRole) {
                $oUserGroup->addObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, trim($sRole));
            }
        }

        $oUserGroup->save();

        $this->_oAccessHandler->addUserGroup($oUserGroup);

        if ($blPorcelain === true) {
            $this->_oWordpressCli->line($oUserGroup->getId());
        } else {
            $this->_oWordpressCli->success("Added new group '{$sGroupName}' with id {$oUserGroup->getId()}.");
        }
    }
}