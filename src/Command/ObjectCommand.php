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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Command;

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
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
    private $wordpressCli;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * ObjectCommand constructor.
     *
     * @param WordpressCli  $wordpressCli
     * @param AccessHandler $accessHandler
     */
    public function __construct(WordpressCli $wordpressCli, AccessHandler $accessHandler)
    {
        $this->wordpressCli = $wordpressCli;
        $this->accessHandler = $accessHandler;
    }

    /**
     * Converts the string to and associative array of index and group
     *
     * @param AbstractUserGroup[] $userGroups
     *
     * @return array|bool
     */
    private function getUserGroupNameMap(array $userGroups)
    {
        $userGroupNames = array_map(
            function (UserGroup $userGroup) {
                return $userGroup->getName();
            },
            $userGroups
        );

        foreach ($userGroups as $userGroup) {
            $userGroupNames[$userGroup->getId()] = $userGroup->getName();
        }

        return array_flip($userGroupNames);
    }

    /**
     * Returns the add and remove user groups by reference.
     *
     * @param string              $operation
     * @param string              $objectType
     * @param string              $objectId
     * @param string              $userGroupsArgument
     * @param AbstractUserGroup[] $userGroups
     * @param array               $addUserGroups
     * @param array               $removeUserGroups
     *
     * @return bool
     */
    private function getAddRemoveUserGroups(
        $operation,
        $objectType,
        $objectId,
        $userGroupsArgument,
        array $userGroups,
        &$addUserGroups,
        &$removeUserGroups
    ) {
        $addUserGroups = [];
        $userGroupIds = array_unique(explode(',', $userGroupsArgument));
        $namesMap = $this->getUserGroupNameMap($userGroups);

        // find the UserGroup object for the ids or strings given on the commandline
        foreach ($userGroupIds as $identifier) {
            $userGroupId = isset($namesMap[$identifier]) ? $namesMap[$identifier] : $identifier;

            if (isset($userGroups[$userGroupId]) === true) {
                $addUserGroups[$userGroupId] = $userGroups[$userGroupId];
            } else {
                $type = (is_numeric($identifier) === true) ? 'id' : 'name';
                $this->wordpressCli->error("There is no group with the {$type}: {$identifier}");
                return false;
            }
        }

        $removeUserGroups = ($operation === self::ACTION_UPDATE) ?
            $this->accessHandler->getUserGroupsForObject($objectType, $objectId) : [];

        if ($operation === self::ACTION_REMOVE) {
            $removeUserGroups = $addUserGroups;
            $addUserGroups = [];
        }

        return true;
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
     * @param array $arguments
     * @param array $assocArguments
     */
    public function __invoke(array $arguments, array $assocArguments)
    {
        if (count($arguments) < 4) {
            $this->wordpressCli->error('<operation>, <object_type>, <object_id> and <user_groups> are required');
            return;
        }

        $operation = $arguments[0];

        // check that operation is valid
        if ($operation !== self::ACTION_ADD
            && $operation !== self::ACTION_UPDATE
            && $operation !== self::ACTION_REMOVE
        ) {
            $this->wordpressCli->error("Operation is not valid: {$operation}");
            return;
        }

        $objectType = $arguments[1];
        $objectId = $arguments[2];
        $userGroupsArgument = $arguments[3];
        $userGroups = $this->accessHandler->getUserGroups();

        $success = $this->getAddRemoveUserGroups(
            $operation,
            $objectType,
            $objectId,
            $userGroupsArgument,
            $userGroups,
            $addUserGroups,
            $removeUserGroups
        );

        if ($success === false) {
            return;
        }

        foreach ($userGroups as $groupId => $uamUserGroup) {
            if (isset($removeUserGroups[$groupId]) === true) {
                $uamUserGroup->removeObject($objectType, $objectId);
            }

            if (isset($addUserGroups[$groupId]) === true) {
                $uamUserGroup->addObject($objectType, $objectId);
            }

            $uamUserGroup->save();
        }

        if ($operation === self::ACTION_ADD) {
            $this->wordpressCli->success(
                "Groups {$userGroupsArgument} successfully added to {$objectType} {$objectId}"
            );
        } elseif ($operation === self::ACTION_UPDATE) {
            $this->wordpressCli->success(
                "Successfully updated {$objectType} {$objectId} with groups {$userGroupsArgument}"
            );
        } elseif ($operation === self::ACTION_REMOVE) {
            $this->wordpressCli->success(
                "Successfully removed groups: {$userGroupsArgument} from {$objectType} {$objectId}"
            );
        }
    }
}
