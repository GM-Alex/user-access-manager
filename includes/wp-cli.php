<?php
/**
 * wp-cli.php
 *
 * The wp cli file.
 *
 * PHP versions 5
 *
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Nils Woetzel nils.woetzel@h-its.org
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

if (!defined('ABSPATH')) {
    die();
}

if (!defined('WP_CLI') || !WP_CLI) {
    die();
}

/**
 * The group command class.
 *
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @author   Nils Woetzel nils.woetzel@h-its.org
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */
class Groups_Command extends \WP_CLI\CommandWithDBObject
{
    private static $aAllowedAccessValues = array('group', 'all');

    protected $sObjectType = 'uam_accessgroup';
    protected $aObjectFields = array(
        'ID',
        'groupname',
        'groupdesc',
        'read_access',
        'write_access',
        'roles',
        'ip_range',
    );

    private $oUserAccessManager;

    /**
     * Groups_Command constructor
     */
    public function __construct()
    {
        $this->oUserAccessManager = new UserAccessManager();
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
     * @subcommand list
     */
    public function list_($_, $assoc_args)
    {
        $aUamUserGroups = $this->oUserAccessManager->getAccessHandler()->getUserGroups();

        if (!isset($aUamUserGroups)) {
            WP_CLI:
            error("no groups defined yet!");
        }

        $aGroups = array();

        foreach ($aUamUserGroups as $oUamUserGroup) {
            $aGroup = array(
                'ID' => $oUamUserGroup->getId(),
                'groupname' => $oUamUserGroup->getGroupName(),
                'groupdesc' => $oUamUserGroup->getGroupDesc(),
                'read_access' => $oUamUserGroup->getReadAccess(),
                'write_access' => $oUamUserGroup->getWriteAccess(),
                'roles' => implode(',', array_keys($oUamUserGroup->getObjectsFromType('role'))),
                'ip_range' => $oUamUserGroup->getIpRange() === null ? '' : $oUamUserGroup->getIpRange()
            );

            // add current group
            $aGroups[] = $aGroup;
        }

        $oFormatter = $this->getFormatter($assoc_args);
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
     */
    public function del($_, $assoc_args)
    {
        if (count($_) < 1) {
            WP_CLI::error('Expected: wp uam groups del <id> ..');
        }

        foreach ($_ as $delId) {
            if ($this->oUserAccessManager->getAccessHandler()->getUserGroups($delId) == null) {
                WP_CLI::error('no group with this id: ' . $delId);
            }
            $this->oUserAccessManager->getAccessHandler()->deleteUserGroup($delId);
        }
        WP_CLI::success('successfully deleted groups: ' . implode(' ', $_));
    }

    /**
     * Returns the formatter
     *
     * @param $assoc_args
     * 
     * @return \WP_CLI\Formatter
     */
    protected function getFormatter(&$assoc_args)
    {
        return new \WP_CLI\Formatter($assoc_args, $this->aObjectFields, $this->sObjectType);
    }

    /**
     * add group
     *
     * ## OPTIONS
     * <groupname>
     * : the name of the new group
     *
     * [--porcelain]
     * : Output just the new post id.
     *
     * [--roles=<list>]
     * : comma seperated list of group associated roles
     *
     * [--<field>=<value>]
     * : Associative args for new UamUserGroup object
     * allowed fields and values are: groupdesc="", read_access={group,all*}, write_access={group,all*}, ip_range="192.168.0.1-192.168.0.10;192.168.0.20-192.168.0.30"
     * *=default
     *
     * ## EXAMPLES
     *
     * wp uam groups add fighters --read_access=all
     *
     */
    public function add($_, $assoc_args)
    {
        $blPorcelain = isset($assoc_args['porcelain']);
        $sGroupName = $_[0];
        $aUamUserGroups = $this->oUserAccessManager->getAccessHandler()->getUserGroups();

        foreach ($aUamUserGroups as $oUamUserGroup) {
            if ($oUamUserGroup->getGroupName() == $sGroupName) {
                WP_CLI::error('group with the same name already exists: ' . $oUamUserGroup->getId());
            }
        }

        $sGroupDescription = $assoc_args['groupdesc'];
        $read_access = $assoc_args['read_access'];
        $write_access = $assoc_args['write_access'];
        $ip_range = $assoc_args['ip_range'];

        $oUamUserGroup = new UamUserGroup($this->oUserAccessManager->getAccessHandler(), null);

        if (!in_array($read_access, self::$aAllowedAccessValues)) {
            if (!$blPorcelain) {
                WP_CLI::line('setting read_access to ' . self::$aAllowedAccessValues[0]);
            }
            $read_access = self::$aAllowedAccessValues[0];
        }

        if (!in_array($write_access, self::$aAllowedAccessValues)) {
            if (!$blPorcelain) {
                WP_CLI::line('setting write_access to ' . self::$aAllowedAccessValues[0]);
            }
            $write_access = self::$aAllowedAccessValues[0];
        }

        $oUamUserGroup->setGroupName($sGroupName);
        $oUamUserGroup->setGroupDesc($sGroupDescription);
        $oUamUserGroup->setReadAccess($read_access);
        $oUamUserGroup->setWriteAccess($write_access);
        $oUamUserGroup->setIpRange($ip_range);

        // add roles
        if (isset($assoc_args['roles'])) {
            $roles = explode(',', $assoc_args['roles']);

            $oUamUserGroup->unsetObjects('role', true);

            foreach ($roles as $role) {
                $oUamUserGroup->addObject('role', $role);
            }
        }

        $oUamUserGroup->save();

        $this->oUserAccessManager->getAccessHandler()->addUserGroup($oUamUserGroup);

        if ($blPorcelain) {
            WP_CLI::line($oUamUserGroup->getId());
        } else {
            WP_CLI::success('added new group ' . $sGroupName . ' with id ' . $oUamUserGroup->getId());
        }
    }
}

/**
 * The object command class.
 *
 * @category UserAccessManager
 * @package  UserAccessManager
 * @author   Alexander Schneider <alexanderschneider85@gmail.com>
 * @author   Nils Woetzel nils.woetzel@h-its.org
 * @license  http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @link     http://wordpress.org/extend/plugins/user-access-manager/
 */
class Objects_Command extends WP_CLI_Command
{

    private $oUserAccessManager;

    /**
     * Objects_Command constructor.
     */
    public function __construct()
    {
        $this->oUserAccessManager = new UserAccessManager();
    }

    /**
     * update groups for an object
     *
     * ## OPTIONS
     *
     * <operation>
     * : 'add', 'remove' or 'update'
     *
     * <object_type>
     * : 'page', 'post', 'user', 'role', 'category' or any other term type
     *
     * <object_id>
     * : the id of the object (string for role)
     *
     * [--groups=<list>]
     * : comma seperated list of group names or ids to add,remove of upate to for the object
     *
     * ## EXAMPLES
     *
     * wp uam object add    user     1      --groups=fighters,loosers
     * wp uam object remove role     author --groups=figthers
     * wp uam object update category 5      --groups=controller
     *
     */
    public function __invoke($_, $assoc_args)
    {
        $sOperation = $_[0];
        $sObjectType = $_[1];
        $sObjectId = $_[2];

        // check that operation is valid
        switch ($sOperation) {
            case "add":
                break;
            case "update":
                break;
            case "remove":
                break;
            default:
                WP_CLI::error("operation is not valid: " . $sOperation);
        }

        $oUamAccessHandler = $this->oUserAccessManager->getAccessHandler();

        // groups passes
        $aGroups = array_unique(explode(',', $assoc_args['groups']));

        // convert the string to and associative array of index and group
        $aAddUserGroups = array();
        $aUamUserGroups = $oUamAccessHandler->getUserGroups();

        // find the UserGroup object for the ids or strings given on the commandline
        foreach ($aGroups as $sGroup) {
            if (is_numeric($sGroup)) {
                $oUamUserGroup = $oUamAccessHandler->getUserGroups($sGroup);

                if (!$oUamUserGroup) {
                    WP_CLI::error("there is no group with the id: " . $sGroup);
                }

                $aAddUserGroups[$sGroup] = $oUamUserGroup;
            } else {
                $blFound = false;

                foreach ($aUamUserGroups as $oUamUserGroup) {
                    if ($oUamUserGroup->getGroupName() == $sGroup) {
                        $aAddUserGroups[$oUamUserGroup->getId()] = $oUamUserGroup;
                        $blFound = true;
                        break;
                    }
                }

                if (!$blFound) {
                    WP_CLI::error("there is no group with the name: " . $sGroup);
                }
            }
        }

        $aRemoveUserGroups = $oUamAccessHandler->getUserGroupsForObject($sObjectType, $sObjectId);
        $blRemoveOldAssignments = true;

        switch ($sOperation) {
            case "add":
                $blRemoveOldAssignments = false;
                break;
            case "update":
                break;
            case "remove":
                $aRemoveUserGroups = $aAddUserGroups;
                $aAddUserGroups = array();
                break;
            default:
                WP_CLI::error("operation is not valid: " . $sOperation);
        }

        foreach ($aUamUserGroups as $sGroupId => $oUamUserGroup) {
            if (isset($aRemoveUserGroups[$sGroupId])) {
                $oUamUserGroup->removeObject($sObjectType, $sObjectId);
            }

            if (isset($aAddUserGroups[$sGroupId])) {
                $oUamUserGroup->addObject($sObjectType, $sObjectId);
            }

            $oUamUserGroup->save($blRemoveOldAssignments);
        }

        switch ($sOperation) {
            case "add":
                WP_CLI::success(implode(" ", array("groups:", $assoc_args['groups'], "sucessfully added to", $sObjectType, $sObjectId)));
                break;
            case "update":
                WP_CLI::success(implode(" ", array("sucessfully updated", $sObjectType, $sObjectId, "with groups:", $assoc_args['groups'])));
                break;
            case "remove":
                WP_CLI::success(implode(" ", array("sucessfully removed groups:", $assoc_args['groups'], "from", $sObjectType, $sObjectId)));
                break;
            default:
        }
    }
}

// add the command
WP_CLI::add_command('uam groups', 'Groups_Command');
WP_CLI::add_command('uam objects', 'Objects_Command');