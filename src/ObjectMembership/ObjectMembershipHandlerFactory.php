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

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

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
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectMapHandler
     */
    private $objectMapHandler;

    /**
     * @var AssignmentInformationFactory
     */
    private $assignmentInformationFactory;

    /**
     * MembershipHandlerFactory constructor.
     * @param Php                          $php
     * @param Wordpress                    $wordpress
     * @param Database                     $database
     * @param ObjectMapHandler             $objectMapHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        ObjectMapHandler $objectMapHandler,
        AssignmentInformationFactory $assignmentInformationFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->objectMapHandler = $objectMapHandler;
        $this->assignmentInformationFactory = $assignmentInformationFactory;
    }

    /**
     * Creates a PostMembershipHandler object.
     * @param ObjectHandler $objectHandler
     * @return PostMembershipHandler
     * @throws Exception
     */
    public function createPostMembershipHandler(ObjectHandler $objectHandler): PostMembershipHandler
    {
        return new PostMembershipHandler(
            $this->assignmentInformationFactory,
            $this->wordpress,
            $objectHandler,
            $this->objectMapHandler
        );
    }

    /**
     * Creates a RoleMembershipHandler object.
     * @return RoleMembershipHandler
     * @throws Exception
     */
    public function createRoleMembershipHandler(): RoleMembershipHandler
    {
        return new RoleMembershipHandler($this->assignmentInformationFactory, $this->wordpress);
    }

    /**
     * Creates a TermMembershipHandler object.
     * @param ObjectHandler $objectHandler
     * @return TermMembershipHandler
     * @throws Exception
     */
    public function createTermMembershipHandler(ObjectHandler $objectHandler): TermMembershipHandler
    {
        return new TermMembershipHandler(
            $this->assignmentInformationFactory,
            $this->wordpress,
            $objectHandler,
            $this->objectMapHandler
        );
    }

    /**
     * Creates an UserMembershipHandler object.
     * @param ObjectHandler $objectHandler
     * @return UserMembershipHandler
     * @throws Exception
     */
    public function createUserMembershipHandler(ObjectHandler $objectHandler): UserMembershipHandler
    {
        return new UserMembershipHandler(
            $this->assignmentInformationFactory,
            $this->php,
            $this->database,
            $objectHandler
        );
    }
}
