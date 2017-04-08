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
    const ACTION_ADD = 'add';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';

    /**
     * @var WordpressCli
     */
    protected $oWordpressCli;

    /**
     * @var AccessHandler
     */
    protected $oAccessHandler;

    /**
     * ObjectCommand constructor.
     *
     * @param WordpressCli  $oWordpressCli
     * @param AccessHandler $oAccessHandler
     */
    public function __construct(WordpressCli $oWordpressCli, AccessHandler $oAccessHandler)
    {
        $this->oWordpressCli = $oWordpressCli;
        $this->oAccessHandler = $oAccessHandler;
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
     * <user_groups>
     * : comma separated list of group names or ids to add, remove of update to for the object
     *
     * ## EXAMPLES
     *
     * wp uam object add    user     1      fighters,losers
     * wp uam object remove role     author fighters
     * wp uam object update category 5      controller
     *
     * @param array $aArguments
     * @param array $aAssocArguments
     */
    public function __invoke(array $aArguments, array $aAssocArguments)
    {
        if (count($aArguments) < 4) {
            $this->oWordpressCli->error('<operation>, <object_type>, <object_id> and <user_groups> are required');
            return;
        }

        $sOperation = $aArguments[0];

        // check that operation is valid
        if ($sOperation !== self::ACTION_ADD
            && $sOperation !== self::ACTION_UPDATE
            && $sOperation !== self::ACTION_REMOVE
        ) {
            $this->oWordpressCli->error("Operation is not valid: {$sOperation}");
            return;
        }

        $sObjectType = $aArguments[1];
        $sObjectId = $aArguments[2];

        // convert the string to and associative array of index and group
        $aAddUserGroups = [];
        $aUserGroups = $this->oAccessHandler->getUserGroups();

        $aUserGroupNames = array_map(
            function (UserGroup $oUserGroup) {
                return $oUserGroup->getName();
            },
            $aUserGroups
        );

        foreach ($aUserGroups as $oUserGroup) {
            $aUserGroupNames[$oUserGroup->getId()] = $oUserGroup->getName();
        }

        $aNamesMap = array_flip($aUserGroupNames);

        // groups passes
        $sUserGroups = $aArguments[3];
        $aUserGroupIds = array_unique(explode(',', $sUserGroups));

        // find the UserGroup object for the ids or strings given on the commandline
        foreach ($aUserGroupIds as $sIdentifier) {
            $sUserGroupId = isset($aNamesMap[$sIdentifier]) ? $aNamesMap[$sIdentifier] : $sIdentifier;

            if (isset($aUserGroups[$sUserGroupId])) {
                $aAddUserGroups[$sUserGroupId] = $aUserGroups[$sUserGroupId];
            } else {
                $sType = (is_numeric($sIdentifier) === true) ? 'id' : 'name';
                $this->oWordpressCli->error("There is no group with the {$sType}: {$sIdentifier}");
                return;
            }
        }

        $aRemoveUserGroups = ($sOperation === self::ACTION_UPDATE) ?
            $this->oAccessHandler->getUserGroupsForObject($sObjectType, $sObjectId) : [];

        if ($sOperation === self::ACTION_REMOVE) {
            $aRemoveUserGroups = $aAddUserGroups;
            $aAddUserGroups = [];
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

        if ($sOperation === self::ACTION_ADD) {
            $this->oWordpressCli->success(
                "Groups {$sUserGroups} successfully added to {$sObjectType} {$sObjectId}"
            );
        } elseif ($sOperation === self::ACTION_UPDATE) {
            $this->oWordpressCli->success(
                "Successfully updated {$sObjectType} {$sObjectId} with groups {$sUserGroups}"
            );
        } elseif ($sOperation === self::ACTION_REMOVE) {
            $this->oWordpressCli->success(
                "Successfully removed groups: {$sUserGroups} from {$sObjectType} {$sObjectId}"
            );
        }
    }
}
