<?php
/**
 * MembershipHandlerTestCase.php
 *
 * The MembershipHandlerTestCase unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\ObjectMembership;

use PHPUnit\Framework\MockObject\MockObject;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;

/**
 * Class MembershipHandlerTestCase
 *
 * @package UserAccessManager\Tests\Unit\ObjectMembership
 */
abstract class ObjectMembershipHandlerTestCase extends UserAccessManagerTestCase
{
    /**
     * @return MockObject|ObjectHandler
     */
    protected function getObjectHandler()
    {
        $objectHandler = parent::getObjectHandler();

        $objectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'postObjectType');
            }));

        return $objectHandler;
    }

    /**
     * @return MockObject|ObjectMapHandler
     */
    protected function getObjectMapHandler()
    {
        $objectMapHandler = parent::getObjectMapHandler();

        $objectMapHandler->expects($this->any())
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectMapHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 'term'],
                        2 => [3 => 'term'],
                        4 => [1 => 'term']
                    ]
                ],
                ObjectMapHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        3 => [1 => 'term', 2 => 'term'],
                        1 => [4 => 'term']
                    ]
                ]
            ]));

        $objectMapHandler->expects($this->any())
            ->method('getPostTreeMap')
            ->will($this->returnValue([
                ObjectMapHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        1 => [3 => 'post'],
                        2 => [3 => 'post'],
                        4 => [1 => 'post']
                    ]
                ],
                ObjectMapHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        3 => [1 => 'post', 2 => 'post'],
                        1 => [4 => 'post']
                    ]
                ]
            ]));

        $objectMapHandler->expects($this->any())
            ->method('getPostTermMap')
            ->will($this->returnValue([
                2 => [3 => 'term', 9 => 'term'],
                10 => [3 => 'term']
            ]));

        $objectMapHandler->expects($this->any())
            ->method('getTermPostMap')
            ->will($this->returnValue([
                2 => [9 => 'post', 10 => 'page']
            ]));

        return $objectMapHandler;
    }

    /**
     * @param array $withIsObjectMember
     * @param array $withIsObjectAssignedToGroup
     * @param array $falseIds
     * @param null|string $fromDate
     * @param null|string $toDate
     * @return MockObject|AbstractUserGroup
     */
    protected function getMembershipUserGroup(
        array $withIsObjectMember,
        array $withIsObjectAssignedToGroup,
        array $falseIds,
        $fromDate = null,
        $toDate = null
    )
    {
        /**
         * @var MockObject|AbstractUserGroup $userGroup
         */
        $userGroup = $this->createMock(AbstractUserGroup::class);

        $return = $this->returnCallback(
            function (
                $objectType,
                $objectId,
                &$assignmentInformation = null
            ) use (
                $falseIds,
                $fromDate,
                $toDate
            ) {
                if (in_array($objectId, $falseIds) === true) {
                    $assignmentInformation = null;
                    return false;
                }

                if ($objectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE) {
                    $objectType = 'post';
                } elseif ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE) {
                    $objectType = 'term';
                } elseif ($objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
                    $objectType = 'user';
                }

                $assignmentInformation = $this->getAssignmentInformation($objectType, $fromDate, $toDate);
                return true;
            }
        );

        $userGroup->expects($this->exactly(count($withIsObjectMember)))
            ->method('isObjectMember')
            ->withConsecutive(...$withIsObjectMember)
            ->will($return);

        $userGroup->expects($this->exactly(count($withIsObjectAssignedToGroup)))
            ->method('isObjectAssignedToGroup')
            ->withConsecutive(...$withIsObjectAssignedToGroup)
            ->will($return);

        return $userGroup;
    }
}
