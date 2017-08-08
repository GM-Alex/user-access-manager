<?php
/**
 * PostMembershipHandler.php
 *
 * The PostMembershipHandler class file.
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
 * Class PostMembershipHandler
 *
 * @package UserAccessManager\UserGroup
 */
class PostMembershipHandler extends ObjectMembershipWithMapHandler
{
    /**
     * @var string
     */
    protected $objectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * PostMembershipHandler constructor.
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
        return $this->objectHandler->getPostTreeMap();
    }

    /**
     * Checks if the post is a member of the user group.
     *
     * @param bool                       $lockRecursive
     * @param string                     $objectId
     * @param null|AssignmentInformation $assignmentInformation
     *
     * @return bool
     */
    public function isMember($lockRecursive, $objectId, &$assignmentInformation = null)
    {
        $isMember = $this->getMembershipByMap($lockRecursive, $objectId, $assignmentInformation);

        if ($lockRecursive === true) {
            $recursiveMembership = ($assignmentInformation !== null) ?
                $assignmentInformation->getRecursiveMembership() : [];

            $postTermMap = $this->objectHandler->getPostTermMap();

            if (isset($postTermMap[$objectId]) === true) {
                foreach ($postTermMap[$objectId] as $termId => $type) {
                    if ($this->userGroup->isTermMember($termId, $rmAssignmentInformation) === true) {
                        $recursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$termId] =
                            $rmAssignmentInformation;
                    }
                }
            }

            $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
            $isMember = $isMember || count($recursiveMembership) > 0;
        }

        return $isMember;
    }

    /**
     * Returns the full post objects.
     *
     * @param bool $lockRecursive
     * @param null $objectType
     *
     * @return array
     */
    public function getFullObjects($lockRecursive, $objectType = null)
    {
        $objectType = ($objectType === null) ? $this->objectType : $objectType;
        $posts = $this->getFullObjectsByMap($lockRecursive, $objectType);

        if ($lockRecursive === true) {
            $termsPostMap = $this->objectHandler->getTermPostMap();
            $terms = $this->userGroup->getFullTerms();

            foreach ($terms as $termId => $term) {
                if (isset($termsPostMap[$termId]) === true) {
                    $map = $termsPostMap[$termId];

                    if ($objectType !== $this->objectType) {
                        $map = array_filter(
                            $map,
                            function ($element) use ($objectType) {
                                return ($element === $objectType);
                            }
                        );
                    }

                    $posts += $map;
                }
            }
        }

        return $posts;
    }
}
