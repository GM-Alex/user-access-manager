<?php
/**
 * TermMembershipHandler.php
 *
 * The TermMembershipHandler class file.
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
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;

/**
 * Class TermMembershipHandler
 *
 * @package UserAccessManager\UserGroup
 */
class TermMembershipHandler extends ObjectMembershipWithMapHandler
{
    /**
     * @var string
     */
    protected $objectType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * TermMembershipHandler constructor.
     *
     * @param ObjectHandler                $objectHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param AbstractUserGroup            $userGroup
     */
    public function __construct(
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory,
        AbstractUserGroup $userGroup
    ) {
        parent::__construct($assignmentInformationFactory, $userGroup);

        $this->objectHandler = $objectHandler;
    }

    /**
     * @inheritdoc
     */
    protected function getMap()
    {
        return $this->objectHandler->getTermTreeMap();
    }

    /**
     * Checks if the term is a member of the user group.
     *
     * @param bool                       $lockRecursive
     * @param string                     $objectId
     * @param null|AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isMember($lockRecursive, $objectId, &$assignmentInformation = null)
    {
        return $this->getMembershipByMap($lockRecursive, $objectId, $assignmentInformation);
    }

    /**
     * Returns the term role objects.
     *
     * @param bool $lockRecursive
     * @param null $objectType
     *
     * @return array
     */
    public function getFullObjects($lockRecursive, $objectType = null)
    {
        $objectType = ($objectType === null) ? $this->objectType : $objectType;
        return $this->getFullObjectsByMap($lockRecursive, $objectType);
    }
}
