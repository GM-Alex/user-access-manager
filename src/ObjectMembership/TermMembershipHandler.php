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
namespace UserAccessManager\ObjectMembership;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Wordpress;

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
    protected $generalObjectType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * TermMembershipHandler constructor.
     *
     * @param ObjectHandler                $objectHandler
     * @param Wordpress                    $wordpress
     * @param AssignmentInformationFactory $assignmentInformationFactory
     */
    public function __construct(
        AssignmentInformationFactory $assignmentInformationFactory,
        Wordpress $wordpress,
        ObjectHandler $objectHandler
    ) {
        parent::__construct($assignmentInformationFactory);

        $this->wordpress = $wordpress;
        $this->objectHandler = $objectHandler;
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
        $term = $this->objectHandler->getTerm($objectId);

        if ($term !== false) {
            $taxonomy = $this->wordpress->getTaxonomy($term->taxonomy);
            $typeName = ($taxonomy !== false) ? $taxonomy->labels->name : $typeName;
            return $term->name;
        }

        return $objectId;
    }

    /**
     * Returns the handled objects.
     *
     * @return array
     */
    public function getHandledObjects()
    {
        $keys = array_keys($this->objectHandler->getTaxonomies());
        return array_merge(parent::getHandledObjects(), array_combine($keys, $keys));
    }

    /**
     * Returns the map.
     *
     * @return array
     */
    protected function getMap()
    {
        return $this->objectHandler->getTermTreeMap();
    }

    /**
     * Checks if the term is a member of the user group.
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
        return $this->getMembershipByMap($userGroup, $lockRecursive, $objectId, $assignmentInformation);
    }

    /**
     * Returns the term role objects.
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
        return $this->getFullObjectsByMap($userGroup, $lockRecursive, $objectType);
    }
}
