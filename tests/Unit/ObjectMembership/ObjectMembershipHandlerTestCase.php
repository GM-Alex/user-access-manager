<?php

namespace UserAccessManager\Tests\Unit\ObjectMembership;

use PHPUnit\Framework\MockObject\MockObject;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;

abstract class ObjectMembershipHandlerTestCase extends UserAccessManagerTestCase
{
    private const ASSIGNMENT_TYPE_BY_GENERAL_OBJECT_TYPE = [
        ObjectHandler::GENERAL_POST_OBJECT_TYPE => 'post',
        ObjectHandler::GENERAL_TERM_OBJECT_TYPE => 'term',
        ObjectHandler::GENERAL_USER_OBJECT_TYPE => 'user'
    ];

    protected function getObjectHandler(): ObjectHandler|MockObject
    {
        $objectHandler = parent::getObjectHandler();

        $objectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(fn($objectType) => $objectType === 'postObjectType'));

        return $objectHandler;
    }

    protected function getObjectMapHandler(): MockObject|ObjectMapHandler
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

    protected function getMembershipUserGroup(
        array $withIsObjectMember,
        array $withIsObjectAssignedToGroup,
        array $falseIds,
        ?string $fromDate = null,
        ?string $toDate = null
    ): MockObject|AbstractUserGroup {
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

                $assignmentInformation = $this->getAssignmentInformation(
                    self::ASSIGNMENT_TYPE_BY_GENERAL_OBJECT_TYPE[$objectType] ?? $objectType,
                    $fromDate,
                    $toDate
                );

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
