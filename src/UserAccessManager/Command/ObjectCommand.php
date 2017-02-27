<?php
/**
 * ObjectsCommand.php
 *
 * The ObjectCommand class file.
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
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\Wrapper\WordpressCli;

/**
 * Class ObjectCommand
 *
 * @package UserAccessManager\Command
 */
class ObjectCommand extends \WP_CLI_Command
{
    /**
     * @var WordpressCli
     */
    protected $_oWrapper;

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * ObjectCommand constructor.
     *
     * @param WordpressCli  $oWrapper
     * @param AccessHandler $oAccessHandler
     */
    public function __construct(WordpressCli $oWrapper, AccessHandler $oAccessHandler)
    {
        $this->_oWrapper = $oWrapper;
        $this->_oAccessHandler = $oAccessHandler;
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
     * : comma separated list of group names or ids to add,remove of update to for the object
     *
     * ## EXAMPLES
     *
     * wp uam object add    user     1      --groups=fighters,loosers
     * wp uam object remove role     author --groups=figthers
     * wp uam object update category 5      --groups=controller
     *
     * @param array $aArguments
     * @param array $aAssocArguments
     */
    public function __invoke(array $aArguments, array $aAssocArguments)
    {
        $sOperation = $aArguments[0];
        $sObjectType = $aArguments[1];
        $sObjectId = $aArguments[2];

        // check that operation is valid
        switch ($sOperation) {
            case "add":
                break;
            case "update":
                break;
            case "remove":
                break;
            default:
                $this->_oWrapper->error("operation is not valid: {$sOperation}");
        }

        // groups passes
        $aUserGroupIds = array_unique(explode(',', $aAssocArguments['groups']));

        // convert the string to and associative array of index and group
        $aAddUserGroups = array();
        $aUserGroups = $this->_oAccessHandler->getUserGroups();

        $aUserGroupNames = array_map(
            function ($oUserGroup) {
                /**
                 * @var UserGroup $oUserGroup
                 */
                return $oUserGroup->getGroupName();
            },
            $aUserGroups
        );
        $aNamesMap = array_flip($aUserGroupNames);

        // find the UserGroup object for the ids or strings given on the commandline
        foreach ($aUserGroupIds as $sIdentifier) {
            $sUserGroupId = isset($aNamesMap[$sIdentifier]) ? $aNamesMap[$sIdentifier] : $sIdentifier;

            if (isset($aUamUserGroups[$sUserGroupId])) {
                $aAddUserGroups[$sUserGroupId] = $aUamUserGroups[$sUserGroupId];
            } else {
                $sType = is_numeric($sIdentifier) ? 'id' : 'name';
                $this->_oWrapper->error("there is no group with the {$sType}: {$sIdentifier}");
            }
        }

        $aRemoveUserGroups = $this->_oAccessHandler->getUserGroupsForObject($sObjectType, $sObjectId);

        if ($sOperation === 'remove') {
            $aRemoveUserGroups = $aAddUserGroups;
            $aAddUserGroups = array();
        }

        foreach ($aUserGroups as $sGroupId => $oUamUserGroup) {
            if (isset($aRemoveUserGroups[$sGroupId])) {
                $oUamUserGroup->removeObject($sObjectType, $sObjectId);
            }

            if (isset($aAddUserGroups[$sGroupId])) {
                $oUamUserGroup->addObject($sObjectType, $sObjectId);
            }

            $oUamUserGroup->save();
        }

        switch ($sOperation) {
            case "add":
                $this->_oWrapper->error(
                    "Groups {$aAssocArguments['groups']} successfully added to {$sObjectType} {$sObjectId}"
                );
                break;
            case "update":
                $this->_oWrapper->error(
                    "Successfully updated {$sObjectType} {$sObjectId} with groups {$aAssocArguments['groups']}"
                );
                break;
            case "remove":
                $this->_oWrapper->error(
                    "Successfully removed groups: {$aAssocArguments['groups']} from {$sObjectType} {$sObjectId}"
                );
                break;
            default:
                break;
        }
    }
}