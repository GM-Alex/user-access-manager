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
 * Class PostMembershipHandler
 *
 * @package UserAccessManager\UserGroup
 */
class PostMembershipHandler extends ObjectMembershipWithMapHandler
{
    /**
     * @var string
     */
    protected $generalObjectType = ObjectHandler::GENERAL_POST_OBJECT_TYPE;

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
     * PostMembershipHandler constructor.
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
        $post = $this->objectHandler->getPost($objectId);

        if ($post !== false) {
            $postTypeObject = $this->wordpress->getPostTypeObject($post->post_type);
            $typeName = ($postTypeObject !== null) ? $postTypeObject->labels->name : $typeName;
            return $post->post_title;
        }

        return $objectId;
    }

    /**
     * Returns the handled objects.
     * @return array
     */
    public function getHandledObjects(): array
    {
        $keys = array_keys($this->objectHandler->getPostTypes());
        return array_merge(parent::getHandledObjects(), array_combine($keys, $keys));
    }

    /**
     * Returns the map.
     * @return array
     */
    protected function getMap(): array
    {
        return $this->objectMapHandler->getPostTreeMap();
    }

    /**
     * Assigns the recursive member ship by the group terms.
     * @param AbstractUserGroup $userGroup
     * @param int|string $objectId
     * @param array $recursiveMembership
     * @throws Exception
     */
    private function assignRecursiveMembershipByTerm(
        AbstractUserGroup $userGroup,
        $objectId,
        array &$recursiveMembership
    ) {
        $postTermMap = $this->objectMapHandler->getPostTermMap();

        if (isset($postTermMap[$objectId]) === true) {
            foreach ($postTermMap[$objectId] as $termId => $type) {
                if ($userGroup->isTermMember($termId, $rmAssignmentInformation) === true) {
                    $recursiveMembership[ObjectHandler::GENERAL_TERM_OBJECT_TYPE][$termId] = $rmAssignmentInformation;
                }
            }
        }
    }

    /**
     * Checks if the post is a member of the user group.
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
        $isMember = $this->getMembershipByMap($userGroup, $lockRecursive, $objectId, $assignmentInformation);

        if ($lockRecursive === true) {
            $recursiveMembership = ($assignmentInformation !== null) ?
                $assignmentInformation->getRecursiveMembership() : [];

            $this->assignRecursiveMembershipByTerm($userGroup, $objectId, $recursiveMembership);
            $this->assignRecursiveMembership($assignmentInformation, $recursiveMembership);
            $isMember = $isMember || count($recursiveMembership) > 0;
        }

        return $isMember;
    }

    /**
     * Returns the full post objects.
     * @param AbstractUserGroup $userGroup
     * @param bool $lockRecursive
     * @param null|string $objectType
     * @return array
     * @throws Exception
     */
    public function getFullObjects(AbstractUserGroup $userGroup, bool $lockRecursive, $objectType = null): array
    {
        $objectType = ($objectType === null) ? $this->generalObjectType : $objectType;
        $posts = $this->getFullObjectsByMap($userGroup, $lockRecursive, $objectType);

        if ($lockRecursive === true) {
            $termsPostMap = $this->objectMapHandler->getTermPostMap();
            $terms = $userGroup->getFullTerms();

            foreach ($terms as $termId => $term) {
                if (isset($termsPostMap[$termId]) === true) {
                    $map = $termsPostMap[$termId];

                    if ($objectType !== $this->generalObjectType) {
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
