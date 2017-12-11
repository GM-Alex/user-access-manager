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

use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Wrapper\WordpressCli;
use WP_CLI\ExitException;

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
     * @var UserGroupHandler
     */
    private $userGroupHandler;

    /**
     * ObjectCommand constructor.
     *
     * @param WordpressCli     $wordpressCli
     * @param UserGroupHandler $userGroupHandler
     */
    public function __construct(WordpressCli $wordpressCli, UserGroupHandler $userGroupHandler)
    {
        $this->wordpressCli = $wordpressCli;
        $this->userGroupHandler = $userGroupHandler;
    }

    /**
     * Converts the string to and associative array of index and group
     *
     * @param AbstractUserGroup[] $userGroups
     *
     * @return array
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

        $userGroupNames = array_flip($userGroupNames);
        return (is_array($userGroupNames) === true) ? $userGroupNames : [];
    }

    /**
     * Returns the user group id and the type of the id.
     *
     * @param array  $namesMap
     * @param string $identifier
     * @param string $type
     *
     * @return mixed
     */
    private function getUserGroupIdAndType(array $namesMap, $identifier, &$type)
    {
        $type = (is_numeric($identifier) === true) ? 'id' : 'name';
        return isset($namesMap[$identifier]) ? $namesMap[$identifier] : $identifier;
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
     *
     * @throws ExitException
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
            $userGroupId = $this->getUserGroupIdAndType($namesMap, $identifier, $type);

            if (isset($userGroups[$userGroupId]) !== true) {
                $this->wordpressCli->error("There is no group with the {$type}: {$identifier}");
                return false;
            }

            $addUserGroups[$userGroupId] = $userGroups[$userGroupId];
        }

        $removeUserGroups = ($operation === self::ACTION_UPDATE) ?
            $this->userGroupHandler->getUserGroupsForObject($objectType, $objectId) : [];

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
     *
     * @throws ExitException
     */
    public function __invoke(array $arguments, array $assocArguments)
    {
        if (count($arguments) < 4) {
            $this->wordpressCli->error('<operation>, <object_type>, <object_id> and <user_groups> are required');
            return;
        }

        $operation = $arguments[0];
        $messages = [
            self::ACTION_ADD => 'Groups %1$s successfully added to %2$s %3$s',
            self::ACTION_UPDATE => 'Successfully updated %2$s %3$s with groups %1$s',
            self::ACTION_REMOVE => 'Successfully removed groups: %1$s from %2$s %3$s'
        ];

        // check that operation is valid
        if (isset($messages[$operation]) === false) {
            $this->wordpressCli->error("Operation is not valid: {$operation}");
            return;
        }

        $objectType = $arguments[1];
        $objectId = $arguments[2];
        $userGroupsArgument = $arguments[3];
        $userGroups = $this->userGroupHandler->getUserGroups();

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

        foreach ($userGroups as $groupId => $userGroup) {
            if (isset($removeUserGroups[$groupId]) === true) {
                $userGroup->removeObject($objectType, $objectId);
            }

            if (isset($addUserGroups[$groupId]) === true) {
                $userGroup->addObject($objectType, $objectId);
            }

            $userGroup->save();
        }

        $this->wordpressCli->success(sprintf($messages[$operation], $userGroupsArgument, $objectType, $objectId));
    }
}
