<?php
/**
 * MembershipHandler.php
 *
 * The MembershipHandler class file.
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

use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;

/**
 * Class MembershipHandler
 *
 * @package UserAccessManager\UserGroup
 */
abstract class ObjectMembershipHandler
{
    /**
     * @var AssignmentInformationFactory
     */
    protected $assignmentInformationFactory;

    /**
     * @var AbstractUserGroup
     */
    protected $userGroup;

    /**
     * @var string
     */
    protected $objectType;

    /**
     * @var array
     */
    protected $assignedObjects;

    /**
     * MembershipHandler constructor.
     *
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param AbstractUserGroup            $userGroup
     *
     * @throws \Exception
     */
    public function __construct(
        AssignmentInformationFactory $assignmentInformationFactory,
        AbstractUserGroup $userGroup
    ) {
        if ($this->objectType === null) {
            throw new MissingObjectTypeException('Missing object type, Object type must be set.');
        }

        $this->assignmentInformationFactory = $assignmentInformationFactory;
        $this->userGroup = $userGroup;
    }

    /**
     * Assigns recursive membership to the assignment information object.
     *
     * @param null|AssignmentInformation $assignmentInformation
     * @param array                      $recursiveMembership
     */
    protected function assignRecursiveMembership(
        &$assignmentInformation,
        array $recursiveMembership
    ) {
        if ($assignmentInformation === null) {
            $assignmentInformation = $this->assignmentInformationFactory->createAssignmentInformation();
        }

        $assignmentInformation->setRecursiveMembership($recursiveMembership);
    }

    /**
     * Returns the assigned objects as array map.
     *
     * @param string $objectType The object type.
     *
     * @return array
     */
    protected function getSimpleAssignedObjects($objectType)
    {
        $objects = $this->userGroup->getAssignedObjects($objectType);

        return array_map(
            function (AssignmentInformation $element) {
                return $element->getType();
            },
            $objects
        );
    }

    /**
     * Checks if the object is a member of the user group.
     *
     * @param bool                       $lockRecursive
     * @param string                     $objectId
     * @param null|AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    abstract public function isMember($lockRecursive, $objectId, &$assignmentInformation = null);

    /**
     * Returns the full objects.
     *
     * @param bool   $lockRecursive
     * @param string $objectType
     *
     * @return array
     */
    abstract public function getFullObjects($lockRecursive, $objectType = null);
}
