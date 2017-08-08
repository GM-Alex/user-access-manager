<?php
/**
 * RoleMembershipHandler.php
 *
 * The RoleMembershipHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup\ObjectMembership;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\AssignmentInformation;

/**
 * Class RoleMembershipHandler
 *
 * @package UserAccessManager\UserGroup
 */
class RoleMembershipHandler extends ObjectMembershipHandler
{
    /**
     * @var string
     */
    protected $objectType = ObjectHandler::GENERAL_ROLE_OBJECT_TYPE;

    /**
     * Checks if the role is a member of the user group.
     *
     * @param bool                       $lockRecursive
     * @param string                     $objectId
     * @param null|AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isMember($lockRecursive, $objectId, &$assignmentInformation = null)
    {
        $isMember = $this->userGroup->isObjectAssignedToGroup(
            $this->objectType,
            $objectId,
            $assignmentInformation
        );
        $assignmentInformation = ($isMember === true) ? $assignmentInformation : null;

        return $isMember;
    }

    /**
     * Returns the full role objects.
     *
     * @param bool $lockRecursive
     * @param null $objectType
     *
     * @return array
     */
    public function getFullObjects($lockRecursive, $objectType = null)
    {
        $objectType = ($objectType === null) ? $this->objectType : $objectType;

        return $this->getSimpleAssignedObjects($objectType);
    }
}
