<?php
/**
 * UserGroupAssignmentHandlerTest.php
 *
 * The UserGroupAssignmentHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\UserGroup;

use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroupAssignmentException;
use UserAccessManager\UserGroup\UserGroupAssignmentHandler;

/**
 * Class UserGroupAssignmentHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\UserGroupAssignmentHandler
 */
class UserGroupAssignmentHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectController = new UserGroupAssignmentHandler(
            $this->getDateUtil(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf(UserGroupAssignmentHandler::class, $objectController);
    }

    /**
     * @param string $type
     * @param string $id
     * @param array  $with
     * @param bool   $throwException
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroup
     */
    private function getDynamicUserGroupWithAdd(
        $type,
        $id,
        array $with,
        $throwException = false
    ) {
        $dynamicUserGroup = parent::getDynamicUserGroup(
            $type,
            $id
        );

        $dynamicUserGroup->expects($this->once())
            ->method('addObject')
            ->with(...$with)
            ->will($this->returnCallback(function () use ($throwException) {
                if ($throwException === true) {
                    throw new UserGroupAssignmentException('User group assignment exception');
                }

                return null;
            }));

        return $dynamicUserGroup;
    }

    /**
     * @group  unit
     * @covers ::getDateParameter()
     */
    public function testGetDataParameter()
    {
        $objectController = new UserGroupAssignmentHandler(
            $this->getDateUtil(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory()
        );

        self::assertNull(self::callMethod($objectController, 'getDateParameter', [['name'], 'someName']));
        self::assertNull(self::callMethod($objectController, 'getDateParameter', [['name'], 'name']));
        self::assertNull(self::callMethod($objectController, 'getDateParameter', [['name'], 'name']));
        self::assertNull(
            self::callMethod(
                $objectController,
                'getDateParameter',
                [['name' => ['date' => 'dateValue']], 'name']
            )
        );
        self::assertNull(
            self::callMethod(
                $objectController,
                'getDateParameter',
                [['name' => ['time' => 'timeValue']], 'name']
            )
        );
        self::assertNull(
            self::callMethod(
                $objectController,
                'getDateParameter',
                [['name' => ['date' => '', 'time' => '']], 'name']
            )
        );
        self::assertNull(
            self::callMethod(
                $objectController,
                'getDateParameter',
                [['name' => ['date' => 'dateValue', 'time' => '']], 'name']
            )
        );
        self::assertNull(
            self::callMethod(
                $objectController,
                'getDateParameter',
                [['name' => ['date' => '', 'time' => 'timeValue']], 'name']
            )
        );
        self::assertEquals(
            'dateValueTtimeValue',
            self::callMethod(
                $objectController,
                'getDateParameter',
                [['name' => ['date' => 'dateValue', 'time' => 'timeValue']], 'name']
            )
        );
    }

    /**
     * @group  unit
     * @covers ::assignObjectToUserGroups()
     * @covers ::setUserGroups()
     * @covers ::setDynamicGroups()
     * @covers ::setDefaultGroups()
     *
     * @throws UserGroupAssignmentException
     */
    public function testSaveObjectData()
    {
        $dateUtil = $this->getDateUtil();
        $dateUtil->expects($this->exactly(2))
            ->method('getDateFromTime')
            ->withConsecutive(
                [1],
                [2]
            )
            ->will($this->onConsecutiveCalls(
                '1970-01-01 00:01:41',
                '1970-01-01 00:01:42'
            ));

        $userHandler = $this->getUserHandler();
        $userHandler->expects($this->exactly(5))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(
                true,
                true,
                true,
                false,
                true
            ));

        $userGroupHandler = $this->getUserGroupHandler();

        $fullGroupOne = $this->getUserGroupWithAddDelete(
            1000,
            [['objectType', 'objectId', '1970-01-01 00:01:41', '1970-01-01 00:01:42']]
        );

        $fullGroupOne->expects($this->once())
            ->method('isDefaultGroupForObjectType')
            ->with('objectType', null, null)
            ->will($this->returnCallback(function ($objectType, &$fromTime, &$toTime) {
                $fromTime = 1;
                $toTime = 2;
                return true;
            }));

        $fullGroupTwo = $this->getUserGroupWithAddDelete(1001);

        $fullGroupTwo->expects($this->once())
            ->method('isDefaultGroupForObjectType')
            ->with('objectType', 1, 2)
            ->will($this->returnValue(false));

        $userGroupHandler->expects($this->once())
            ->method('getFullUserGroups')
            ->will($this->returnValue([$fullGroupOne, $fullGroupTwo]));

        $userGroupHandler->expects($this->exactly(5))
            ->method('getFilteredUserGroups')
            ->will($this->onConsecutiveCalls(
                $this->getUserGroupArray([1, 3], [1, 2, 3], [['objectType', 1, '1T2', 'toTDate']], [100, 101]),
                $this->getUserGroupArray([2, 4], [1, 2, 4], [['objectType', 1, null, null]]),
                $this->getUserGroupArray([1, 2], [2, 3, 4], [['objectType', 1, null, '23T4']]),
                $this->getUserGroupArray([4], [2, 3], [['objectType', 'objectId', null, null]]),
                $this->getUserGroupArray([], [1, 2], [['objectType', 1, null, null]])
            ));

        $userGroupHandler->expects($this->exactly(4))
            ->method('unsetUserGroupsForObject');

        $userGroupFactory = $this->getUserGroupFactory();

        $userGroupFactory->expects($this->exactly(3))
            ->method('createDynamicUserGroup')
            ->withConsecutive(
                [DynamicUserGroup::USER_TYPE, '1'],
                [DynamicUserGroup::ROLE_TYPE, 'admin'],
                [DynamicUserGroup::ROLE_TYPE, 'some']
            )->will($this->onConsecutiveCalls(
                $this->getDynamicUserGroupWithAdd(
                    DynamicUserGroup::USER_TYPE,
                    '1',
                    ['objectType', 1, 'fromTDate', 'toTDate']
                ),
                $this->getDynamicUserGroupWithAdd(
                    DynamicUserGroup::ROLE_TYPE,
                    'admin',
                    ['objectType', 1, null, null]
                ),
                $this->getDynamicUserGroupWithAdd(
                    DynamicUserGroup::ROLE_TYPE,
                    'some',
                    ['objectType', 1, null, null],
                    true
                )
            ));

        $objectController = new UserGroupAssignmentHandler(
            $dateUtil,
            $userHandler,
            $userGroupHandler,
            $userGroupFactory
        );

        $addUserGroups = [
            1 => ['id' => 1, 'fromDate' => ['date' => 1, 'time' => 2], 'toDate' => ['date' => 'to', 'time' => 'Date']],
            3 => ['id' => 3, 'fromDate' => ['date' => 1, 'time' => 2], 'toDate' => ['date' => 'to', 'time' => 'Date']],
            100 => [],
            101 => ['id' => 100]
        ];
        $removeUserGroups = [1 => 1, 2 => 2, 3 => 3];
        $dynamicUserGroups = [
            DynamicUserGroup::USER_TYPE.'|1' => [
                'id' => DynamicUserGroup::USER_TYPE.'|1',
                'fromDate' => ['date' => 'from', 'time' => 'Date'],
                'toDate' => ['date' => 'to', 'time' => 'Date']
            ],
            DynamicUserGroup::ROLE_TYPE.'|admin' => ['id' => DynamicUserGroup::ROLE_TYPE.'|admin'],
            DynamicUserGroup::ROLE_TYPE.'|some' => ['id' => DynamicUserGroup::ROLE_TYPE.'|some'],
            'A|B' => ['id' => 'B|A'],
        ];

        $exception = null;

        try {
            $objectController->assignObjectToUserGroups(
                'objectType',
                1,
                $addUserGroups,
                $removeUserGroups,
                $dynamicUserGroups
            );
        } catch (\Throwable $throwable) {
            $exception = $throwable;
        }

        self::assertInstanceOf(UserGroupAssignmentException::class, $exception);
        self::assertEquals('User group assignment exception', $exception->getMessage());

        $addUserGroups = [
            2 => ['id' => 2],
            4 => ['id' => 4]
        ];
        $removeUserGroups = [1 => 1, 2 => 2, 4 => 4];
        $dynamicUserGroups = [];

        $objectController->assignObjectToUserGroups(
            'objectType',
            1,
            $addUserGroups,
            $removeUserGroups,
            $dynamicUserGroups
        );

        $addUserGroups = [
            1 => ['id' => 1, 'formDate' => ['date' => '', 'time' => ''], 'toDate' => ['date' => 23, 'time' => 4]],
            2 => ['id' => 2, 'formDate' => ['date' => '', 'time' => ''], 'toDate' => ['date' => 23, 'time' => 4]]
        ];
        $removeUserGroups = [2 => 2, 3 => 3,4 => 4];
        $objectController->assignObjectToUserGroups(
            'objectType',
            1,
            $addUserGroups,
            $removeUserGroups,
            $dynamicUserGroups
        );

        $addUserGroups = [
            4 => ['id' => 4]
        ];
        $removeUserGroups = [2 => 2, 3 => 3];
        $objectController->assignObjectToUserGroups(
            'objectType',
            'objectId',
            $addUserGroups,
            $removeUserGroups,
            $dynamicUserGroups
        );

        $addUserGroups = [
            1 => ['id' => 1],
            2 => ['id' => 2]
        ];
        $removeUserGroups = [1 => 1, 2 => 2, 3 => 3];
        $dynamicUserGroups = [];
        $objectController->assignObjectToUserGroups(
            'objectType',
            1,
            $addUserGroups,
            $removeUserGroups,
            $dynamicUserGroups
        );
    }
}
