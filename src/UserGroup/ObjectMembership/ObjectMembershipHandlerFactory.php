<?php
/**
 * MembershipHandlerFactory.php
 *
 * The MembershipHandlerFactory class file.
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
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Php;

/**
 * Class MembershipHandlerFactory
 *
 * @package UserAccessManager\UserGroup
 */
class ObjectMembershipHandlerFactory
{
    /**
     * @var Php
     */
    private $php;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AssignmentInformationFactory
     */
    private $assignmentInformationFactory;

    /**
     * MembershipHandlerFactory constructor.
     *
     * @param Php                          $php
     * @param Database                     $database
     * @param ObjectHandler                $objectHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     */
    public function __construct(
        Php $php,
        Database $database,
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory
    ) {
        $this->php = $php;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->assignmentInformationFactory = $assignmentInformationFactory;
    }

    /**
     * Creates a PostMembershipHandler object.
     *
     * @param AbstractUserGroup $userGroup
     *
     * @return PostMembershipHandler
     */
    public function createPostMembershipHandler(AbstractUserGroup $userGroup)
    {
        return new PostMembershipHandler($this->objectHandler, $this->assignmentInformationFactory, $userGroup);
    }

    /**
     * Creates a RoleMembershipHandler object.
     *
     * @param AbstractUserGroup $userGroup
     *
     * @return RoleMembershipHandler
     */
    public function createRoleMembershipHandler(AbstractUserGroup $userGroup)
    {
        return new RoleMembershipHandler($this->assignmentInformationFactory, $userGroup);
    }

    /**
     * Creates a TermMembershipHandler object.
     *
     * @param AbstractUserGroup $userGroup
     *
     * @return TermMembershipHandler
     */
    public function createTermMembershipHandler(AbstractUserGroup $userGroup)
    {
        return new TermMembershipHandler($this->objectHandler, $this->assignmentInformationFactory, $userGroup);
    }

    /**
     * Creates an UserMembershipHandler object.
     *
     * @param AbstractUserGroup $userGroup
     *
     * @return UserMembershipHandler
     */
    public function createUserMembershipHandler(AbstractUserGroup $userGroup)
    {
        return new UserMembershipHandler(
            $this->php,
            $this->database,
            $this->objectHandler,
            $this->assignmentInformationFactory,
            $userGroup
        );
    }
}
