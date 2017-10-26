<?php
/**
 * UserGroupHandlerTest.php
 *
 * The UserGroupHandlerTest unit test class file.
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

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\HandlerTestCase;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupHandler;

/**
 * Class UserGroupHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\UserGroupHandler
 */
class UserGroupHandlerTest extends HandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $userGroupHandler = new UserGroupHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf(UserGroupHandler::class, $userGroupHandler);
    }

    /**
     * Generates return values.
     *
     * @param int $number
     *
     * @return array
     */
    private function generateReturn($number)
    {
        $returns = [];

        for ($counter = 1; $counter <= $number; $counter++) {
            $return = new \stdClass();
            $return->ID = $counter;
            $returns[] = $return;
        }

        return $returns;
    }

    /**
     * @param string $id
     * @param bool   $deletable
     * @param bool   $objectIsMember
     * @param array  $ipRange
     * @param string $readAccess
     * @param string $writeAccess
     * @param array  $posts
     * @param array  $terms
     * @param null   $name
     * @param array  $setIgnoreDates
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|UserGroup
     */
    protected function getUserGroup(
        $id,
        $deletable = true,
        $objectIsMember = false,
        array $ipRange = [''],
        $readAccess = 'unset',
        $writeAccess = 'unset',
        array $posts = [],
        array $terms = [],
        $name = null,
        array $setIgnoreDates = []
    ) {
        $userGroup = parent::getUserGroup(
            $id,
            $deletable,
            $objectIsMember,
            $ipRange,
            $readAccess,
            $writeAccess,
            $posts,
            $terms,
            $name
        );

        if (count($setIgnoreDates) > 0) {
            $userGroup->expects($this->exactly(count($setIgnoreDates)))
                ->method('setIgnoreDates')
                ->withConsecutive(...$setIgnoreDates);
        } else {
            $userGroup->expects($this->never())
                ->method('setIgnoreDates');
        }

        if ($writeAccess !== null) {
            $userGroup->expects($this->any())
                ->method('getWriteAccess')
                ->will($this->returnValue($writeAccess));
        }

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers ::getUserGroups()
     *
     * @return UserGroupHandler
     */
    public function testGetUserGroups()
    {
        $database = $this->getDatabase();

        $query = 'SELECT ID FROM getUserGroupTable';

        $database->expects($this->once())
            ->method('getResults')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($query)]
            )
            ->will($this->returnValue($this->generateReturn(3)));

        $userGroupFactory = $this->getUserGroupFactory();

        $userGroupFactory->expects($this->exactly(3))
            ->method('createUserGroup')
            ->withConsecutive([1], [2], [3])
            ->will($this->returnCallback(function ($id) {
                return $this->getUserGroup($id, !($id === 3));
            }));

        $userGroupHandler = new UserGroupHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $database,
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $userGroupFactory
        );

        $expected = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3, false)
        ];

        self::assertEquals($expected, $userGroupHandler->getUserGroups());
        self::assertAttributeEquals($expected, 'userGroups', $userGroupHandler);
        self::assertEquals($expected, $userGroupHandler->getUserGroups());

        return $userGroupHandler;
    }

    /**
     * @param array $results
     *
     * @return array
     */
    private function getQueryResult(array $results)
    {
        $queryResults = [];

        foreach ($results as $result) {
            $queryResult = new \stdClass();
            $queryResult->id = $result[1];
            $queryResult->type = $result[0];
            $queryResults[] = $queryResult;
        }

        return $queryResults;
    }

    /**
     * @group  unit
     * @covers ::getDynamicUserGroups()
     */
    public function testGetDynamicUserGroups()
    {
        $userGroupFactory = $this->getUserGroupFactory();

        $userGroupFactory->expects($this->exactly(4))
            ->method('createDynamicUserGroup')
            ->withConsecutive(
                [DynamicUserGroup::USER_TYPE, 0],
                [DynamicUserGroup::USER_TYPE, 0],
                [DynamicUserGroup::USER_TYPE, 10],
                [DynamicUserGroup::ROLE_TYPE, 'administrator']
            )
            ->will($this->returnCallback(function ($type, $id) {
                return $this->getDynamicUserGroup($type, $id);
            }));

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('getResults')
            ->with(new MatchIgnoreWhitespace(
                'SELECT group_id AS id, group_type AS type
                FROM userGroupToObjectTable
                WHERE group_type IN (\'role\', \'user\')
                GROUP BY group_type, group_id'
            ))
            ->will($this->returnValue($this->getQueryResult([
                [DynamicUserGroup::USER_TYPE, 0],
                [DynamicUserGroup::USER_TYPE, 10],
                [DynamicUserGroup::ROLE_TYPE, 'administrator']
            ])));

        $userGroupHandler = new UserGroupHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $database,
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $userGroupFactory
        );

        $expect = [
            DynamicUserGroup::USER_TYPE.'|0' => $this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 0),
            DynamicUserGroup::USER_TYPE.'|10' => $this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 10),
            DynamicUserGroup::ROLE_TYPE.'|administrator' => $this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 1)
        ];

        self::assertEquals($expect, $userGroupHandler->getDynamicUserGroups());
        self::assertEquals($expect, $userGroupHandler->getDynamicUserGroups());
    }

    /**
     * @group  unit
     * @covers ::getFullUserGroups()
     */
    public function testGetFullUserGroups()
    {
        $userGroupHandler = new UserGroupHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );

        self::setValue($userGroupHandler, 'userGroups', [1 => $this->getUserGroup(1)]);
        self::setValue(
            $userGroupHandler,
            'dynamicUserGroups',
            [DynamicUserGroup::USER_TYPE.'|0' =>$this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 0)]
        );

        self::assertEquals(
            [
                1 => $this->getUserGroup(1),
                DynamicUserGroup::USER_TYPE.'|0' =>$this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 0)
            ],
            $userGroupHandler->getFullUserGroups()
        );
    }

    /**
     * @group  unit
     * @covers ::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $userGroupHandler = new UserGroupHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );

        $userGroups = [
            0 => $this->getUserGroup(0),
            1 => $this->getUserGroup(1, true, false, ['1.1.1.1']),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'all'),
            4 => $this->getUserGroup(4),
            5 => $this->getUserGroup(5),
            6 => $this->getUserGroup(6)
        ];

        self::setValue($userGroupHandler, 'userGroups', $userGroups);

        $dynamicUserGroups = [
            'users|0', $this->createMock(DynamicUserGroup::class),
        ];

        self::setValue($userGroupHandler, 'dynamicUserGroups', $dynamicUserGroups);

        $userUserGroups = $userGroups;
        unset($userUserGroups[4]);
        unset($userUserGroups[6]);

        self::setValue($userGroupHandler, 'userGroupsForUser', $userUserGroups);
        self::assertEquals($userUserGroups, $userGroupHandler->getFilteredUserGroups());
    }

    /**
     * @group   unit
     * @depends testGetUserGroups
     * @covers  ::addUserGroup()
     *
     * @param UserGroupHandler $userGroupHandler
     *
     * @return UserGroupHandler
     */
    public function testAddUserGroups(UserGroupHandler $userGroupHandler)
    {
        $expected = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($userGroupHandler, 'filteredUserGroups', []);
        $userGroupHandler->addUserGroup($this->getUserGroup(4));
        self::assertAttributeEquals($expected, 'userGroups', $userGroupHandler);
        self::assertAttributeEquals(null, 'filteredUserGroups', $userGroupHandler);

        return $userGroupHandler;
    }

    /**
     * @group   unit
     * @depends testAddUserGroups
     * @covers  ::deleteUserGroup()
     *
     * @param UserGroupHandler $userGroupHandler
     */
    public function testDeleteUserGroups(UserGroupHandler $userGroupHandler)
    {
        $expected = [
            1 => $this->getUserGroup(1),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($userGroupHandler, 'filteredUserGroups', []);
        self::assertFalse($userGroupHandler->deleteUserGroup(10));
        self::assertFalse($userGroupHandler->deleteUserGroup(3));
        self::assertAttributeEquals([], 'filteredUserGroups', $userGroupHandler);

        self::assertTrue($userGroupHandler->deleteUserGroup(2));
        self::assertAttributeEquals($expected, 'userGroups', $userGroupHandler);
        self::assertAttributeEquals(null, 'filteredUserGroups', $userGroupHandler);
    }

    /**
     * @group  unit
     * @covers ::getUserGroupsForObject()
     *
     * @return UserGroupHandler
     */
    public function testGetUserGroupsForObject()
    {
        $userGroupHandler = new UserGroupHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );

        $userGroups = [
            1 => $this->getUserGroup(1, true, true, [''], 'none', 'none', [], [], null, [[false]]),
            2 => $this->getUserGroup(2, true, false, [''], 'none', 'none', [], [], null, [[false]]),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'none', [], [], null, [[false]]),
            4 => $this->getUserGroup(4, true, false, [''], 'none', 'none', [], [], null, [[false]])
        ];

        self::setValue($userGroupHandler, 'userGroups', $userGroups);
        self::setValue(
            $userGroupHandler,
            'dynamicUserGroups',
            [DynamicUserGroup::USER_TYPE.'|0' =>$this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 0)]
        );

        self::assertEquals([], $userGroupHandler->getUserGroupsForObject('invalid', 1));

        self::assertEquals(
            [1 => $this->getUserGroup(1, true, true)],
            $userGroupHandler->getUserGroupsForObject('objectType', 1)
        );

        $userGroups = [
            1 => $this->getUserGroup(1, true, false, [''], 'none', 'none', [], [], null, [[false], [true]]),
            2 => $this->getUserGroup(2, true, true, [''], 'none', 'none', [], [], null, [[false], [true]]),
            3 => $this->getUserGroup(3, true, true, [''], 'none', 'none', [], [], null, [[false], [true]]),
            4 => $this->getUserGroup(4, true, false, [''], 'none', 'none', [], [], null, [[false], [true]])
        ];

        self::setValue($userGroupHandler, 'userGroups', $userGroups);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $userGroupHandler->getUserGroupsForObject('objectType', 2)
        );

        self::setValue($userGroupHandler, 'objectUserGroups', [0 => ['objectType' => []]]);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $userGroupHandler->getUserGroupsForObject('objectType', 2, true)
        );

        self::assertAttributeEquals(
            [
                0 => ['objectType' => []],
                1 => [
                    'objectType' => [
                        2 => [
                            2 => $this->getUserGroup(2, true, true),
                            3 => $this->getUserGroup(3, true, true)
                        ]
                    ]
                ]
            ],
            'objectUserGroups',
            $userGroupHandler
        );

        return $userGroupHandler;
    }

    /**
     * @group   unit
     * @depends testGetUserGroupsForObject
     * @covers  ::unsetUserGroupsForObject()
     *
     * @param UserGroupHandler $userGroupHandler
     */
    public function testUnsetUserGroupsForObject(UserGroupHandler $userGroupHandler)
    {
        self::assertAttributeNotEquals([], 'objectUserGroups', $userGroupHandler);
        $userGroupHandler->unsetUserGroupsForObject();
        self::assertAttributeEquals([], 'objectUserGroups', $userGroupHandler);
    }

    /**
     * @group  unit
     * @covers ::getUserGroupsForUser()
     * @covers ::assignDynamicUserGroupsForUser()
     * @covers ::checkUserGroupAccess()
     */
    public function testGetUserGroupsForUser()
    {
        $wordpress = $this->getWordpressWithUser();

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(11))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(false, true, true, false, false, false, false, true, false, false, false));

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(3))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true, false));

        $userHandler->expects($this->exactly(2))
            ->method('getUserRole')
            ->will($this->returnValue([UserGroup::NONE_ROLE]));

        $userHandler->expects($this->exactly(7))
            ->method('isIpInRange')
            ->withConsecutive(
                ['1.1.1.1', ['1.1.1.1']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']],
                ['1.1.1.2', ['1.1.1.2']]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false,
                false,
                false,
                false,
                false
            ));

        $userGroupFactory = $this->getUserGroupFactory();

        $userGroupFactory->expects($this->exactly(4))
            ->method('createDynamicUserGroup')
            ->withConsecutive(
                [DynamicUserGroup::USER_TYPE, 1],
                [DynamicUserGroup::ROLE_TYPE, '_none-role_'],
                [DynamicUserGroup::USER_TYPE, 1],
                [DynamicUserGroup::ROLE_TYPE, '_none-role_']
            )
            ->will($this->returnCallback(function ($type, $id) {
                return $this->getDynamicUserGroup($type, $id);
            }));

        $userGroupHandler = new UserGroupHandler(
            $wordpress,
            $wordpressConfig,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $userHandler,
            $userGroupFactory
        );

        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';

        $userGroups = [
            0 => $this->getUserGroup(0),
            1 => $this->getUserGroup(1, true, false, ['1.1.1.1']),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'all'),
            4 => $this->getUserGroup(4),
            5 => $this->getUserGroup(5),
            6 => $this->getUserGroup(6),
            7 => $this->getUserGroup(7, true, false, [''], 'all', 'all')
        ];

        self::setValue($userGroupHandler, 'userGroups', $userGroups);

        $objectUserGroups = [
            0 => [
                ObjectHandler::GENERAL_USER_OBJECT_TYPE => [
                    1 => [
                        0 => $this->getUserGroup(0),
                        5 => $this->getUserGroup(5)
                    ]
                ]
            ]
        ];

        self::setValue($userGroupHandler, 'objectUserGroups', $objectUserGroups);

        $expected = $userGroups;
        $expected['user|1'] = $this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 1);
        $expected['role|_none-role_'] = $this->getDynamicUserGroup(DynamicUserGroup::ROLE_TYPE, '_none-role_');
        unset($expected[4]);
        unset($expected[6]);
        unset($expected[7]);

        self::assertEquals($expected, $userGroupHandler->getUserGroupsForUser());
        self::assertEquals($userGroups, $userGroupHandler->getUserGroupsForUser());


        $_SERVER['HTTP_X_REAL_IP'] = '1.1.1.2';
        $userGroups = [1 => $this->getUserGroup(1, true, false, ['1.1.1.2'])];
        self::setValue($userGroupHandler, 'userGroups', $userGroups);
        self::setValue($userGroupHandler, 'userGroupsForUser', null);
        $userGroupHandler->getUserGroupsForUser();
    }
    
    /**
     * @group  unit
     * @covers ::getFilteredUserGroupsForObject()
     */
    public function testGetFilteredUserGroupsForObject()
    {
        $userGroupFactory = $this->getUserGroupFactory();
        $userGroupFactory->expects($this->once())
            ->method('createDynamicUserGroup')
            ->withConsecutive(
                [DynamicUserGroup::USER_TYPE, DynamicUserGroup::NOT_LOGGED_IN_USER_ID]
            )
            ->will($this->returnCallback(function ($type, $id) {
                return $this->getDynamicUserGroup($type, $id);
            }));

        $userGroupHandler = new UserGroupHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $userGroupFactory
        );

        $userGroups = [
            0 => $this->getUserGroup(0),
            1 => $this->getUserGroup(1, true, false, ['1.1.1.1']),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'all'),
            4 => $this->getUserGroup(4),
            5 => $this->getUserGroup(5),
            6 => $this->getUserGroup(6)
        ];

        self::setValue($userGroupHandler, 'userGroups', $userGroups);

        $userUserGroups = $userGroups;
        unset($userUserGroups[4]);
        unset($userUserGroups[6]);

        self::setValue($userGroupHandler, 'userGroupsForUser', $userUserGroups);

        $objectUserGroups = [
            0 => [
                'objectType' => [
                    1 => [
                        0 => $this->getUserGroup(0),
                        2 => $this->getUserGroup(2, true, false, [''], 'all'),
                        4 => $this->getUserGroup(4),
                        5 => $this->getUserGroup(5)
                    ]
                ]
            ]
        ];

        self::setValue($userGroupHandler, 'objectUserGroups', $objectUserGroups);

        $expected = [
            0 => $this->getUserGroup(0),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            5 => $this->getUserGroup(5)
        ];

        self::assertEquals($expected, $userGroupHandler->getFilteredUserGroupsForObject('objectType', 1));
    }
}
