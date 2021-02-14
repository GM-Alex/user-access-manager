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

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
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
     * @var ObjectMapHandler
     */
    private $objectMapHandler;

    /**
     * TermMembershipHandler constructor.
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param Wordpress                    $wordpress
     * @param ObjectHandler                $objectHandler
     * @param ObjectMapHandler             $objectMapHandler
     * @throws Exception
     */
    public function __construct(
        AssignmentInformationFactory $assignmentInformationFactory,
        Wordpress $wordpress,
        ObjectHandler $objectHandler,
        ObjectMapHandler $objectMapHandler
    ) {
        parent::__construct($assignmentInformationFactory);

        $this->wordpress = $wordpress;
        $this->objectHandler = $objectHandler;
        $this->objectMapHandler = $objectMapHandler;
    }

    /**
     * Returns the object and type name.
     * @param int|string $objectId
     * @param string $typeName
     * @return int|string
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
     * @return array
     */
    public function getHandledObjects(): array
    {
        $keys = array_keys($this->objectHandler->getTaxonomies());
        return array_merge(parent::getHandledObjects(), array_combine($keys, $keys));
    }

    /**
     * Returns the map.
     * @return array
     */
    protected function getMap(): array
    {
        return $this->objectMapHandler->getTermTreeMap();
    }

    /**
     * Checks if the term is a member of the user group.
     * @param AbstractUserGroup $userGroup
     * @param bool $lockRecursive
     * @param int|string $objectId
     * @param null|AssignmentInformation $assignmentInformation
     * @return bool
     * @throws Exception
     */
    public function isMember(
        AbstractUserGroup $userGroup,
        bool $lockRecursive,
        $objectId,
        ?AssignmentInformation &$assignmentInformation = null
    ): bool {
        return $this->getMembershipByMap($userGroup, $lockRecursive, $objectId, $assignmentInformation);
    }

    /**
     * Returns the term role objects.
     * @param AbstractUserGroup $userGroup
     * @param bool $lockRecursive
     * @param null $objectType
     * @return array
     * @throws Exception
     */
    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, $objectType = null): array
    {
        $objectType = ($objectType === null) ? $this->generalObjectType : $objectType;
        return $this->getFullObjectsByMap($userGroup, $lockRecursive, $objectType);
    }
}
