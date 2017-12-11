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
namespace UserAccessManager\ObjectMembership;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class RoleMembershipHandler
 *
 * @package UserAccessManager\UserGroup
 */
class RoleMembershipHandler extends ObjectMembershipHandler
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var string
     */
    protected $generalObjectType = ObjectHandler::GENERAL_ROLE_OBJECT_TYPE;

    /**
     * RoleMembershipHandler constructor.
     *
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param Wordpress                    $wordpress
     *
     * @throws \Exception
     */
    public function __construct(AssignmentInformationFactory $assignmentInformationFactory, Wordpress $wordpress)
    {
        parent::__construct($assignmentInformationFactory);
        $this->wordpress = $wordpress;
    }

    /**
     * Returns the object and type name.
     *
     * @param string $objectId
     * @param string $typeName
     *
     * @return string
     */
    public function getObjectName($objectId, &$typeName = '')
    {
        $typeName = $this->generalObjectType;
        $roles = $this->wordpress->getRoles()->role_names;
        return (isset($roles[$objectId]) === true) ? $roles[$objectId] : $objectId;
    }

    /**
     * Checks if the role is a member of the user group.
     *
     * @param AbstractUserGroup          $userGroup
     * @param bool                       $lockRecursive
     * @param string                     $objectId
     * @param null|AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isMember(AbstractUserGroup $userGroup, $lockRecursive, $objectId, &$assignmentInformation = null)
    {
        $isMember = $userGroup->isObjectAssignedToGroup(
            $this->generalObjectType,
            $objectId,
            $assignmentInformation
        );
        $assignmentInformation = ($isMember === true) ? $assignmentInformation : null;

        return $isMember;
    }

    /**
     * Returns the full role objects.
     *
     * @param AbstractUserGroup $userGroup
     * @param bool              $lockRecursive
     * @param null              $objectType
     *
     * @return array
     */
    public function getFullObjects(AbstractUserGroup $userGroup, $lockRecursive, $objectType = null)
    {
        $objectType = ($objectType === null) ? $this->generalObjectType : $objectType;

        return $this->getSimpleAssignedObjects($userGroup, $objectType);
    }
}
