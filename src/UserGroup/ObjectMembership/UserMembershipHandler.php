<?php
/**
 * UserMembershipHandler.php
 *
 * The UserMembershipHandler class file.
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

use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Php;

/**
 * Class UserMembershipHandler
 *
 * @package UserAccessManager\UserGroup
 */
class UserMembershipHandler extends ObjectMembershipHandler
{
    /**
     * @var string
     */
    protected $objectType = ObjectHandler::GENERAL_USER_OBJECT_TYPE;

    /**
     * @var Php
     */
    private $php;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var Database
     */
    private $database;

    /**
     * UserMembershipHandler constructor.
     *
     * @param Php                          $php
     * @param Database                     $database
     * @param ObjectHandler                $objectHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param AbstractUserGroup            $userGroup
     */
    public function __construct(
        Php $php,
        Database $database,
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory,
        AbstractUserGroup $userGroup
    ) {
        parent::__construct($assignmentInformationFactory, $userGroup);

        $this->php = $php;
        $this->objectHandler = $objectHandler;
        $this->database = $database;
    }

    /**
     * Checks if the user is a member of the user group.
     *
     * @param bool                       $lockRecursive
     * @param string                     $objectId
     * @param null|AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isMember($lockRecursive, $objectId, &$assignmentInformation = null)
    {
        $assignmentInformation = null;
        $recursiveMembership = [];
        $user = $this->objectHandler->getUser($objectId);

        if ($user !== false) {
            $capabilitiesTable = $this->database->getCapabilitiesTable();
            $capabilities = (isset($user->{$capabilitiesTable}) === true) ? $user->{$capabilitiesTable} : [];

            if (is_array($capabilities) === true && count($capabilities) > 0) {
                $assignedRoles = $this->userGroup->getAssignedObjects(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

                $recursiveRoles = array_intersect(
                    array_keys($capabilities),
                    array_keys($assignedRoles)
                );

                if (count($recursiveRoles) > 0) {
                    $recursiveMembership[ObjectHandler::GENERAL_ROLE_OBJECT_TYPE] = array_combine(
                        $recursiveRoles,
                        $this->php->arrayFill(
                            0,
                            count($recursiveRoles),
                            $this->assignmentInformationFactory->createAssignmentInformation(
                                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                            )
                        )
                    );
                }
            }
        }

        $isMember = $this->userGroup->isObjectAssignedToGroup(
            $this->objectType,
            $objectId,
            $assignmentInformation
        );

        if ($isMember === true || count($recursiveMembership) > 0) {
            $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
            return true;
        }

        return false;
    }

    /**
     * Returns the user role objects.
     *
     * @param bool $lockRecursive
     * @param null $objectType
     *
     * @return array
     */
    public function getFullObjects($lockRecursive, $objectType = null)
    {
        $users = [];

        $databaseUsers = (array)$this->database->getResults(
            "SELECT ID, user_nicename
                FROM {$this->database->getUsersTable()}"
        );

        foreach ($databaseUsers as $user) {
            if ($this->userGroup->isObjectMember($this->objectType, $user->ID) === true) {
                $users[$user->ID] = $this->objectType;
            }
        }

        return $users;
    }
}
