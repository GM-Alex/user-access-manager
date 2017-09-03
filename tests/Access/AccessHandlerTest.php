<?php
/**
 * AccessHandlerTest.php
 *
 * The AccessHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Access;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\HandlerTestCase;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class AccessHandlerTest
 *
 * @package UserAccessManager\AccessHandler
 * @coversDefaultClass \UserAccessManager\Access\AccessHandler
 */
class AccessHandlerTest extends HandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf(AccessHandler::class, $accessHandler);
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
        $readAccess = 'none',
        $writeAccess = 'none',
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

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers ::getUserGroups()
     *
     * @return AccessHandler
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

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
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

        self::assertEquals($expected, $accessHandler->getUserGroups());
        self::assertAttributeEquals($expected, 'userGroups', $accessHandler);
        self::assertEquals($expected, $accessHandler->getUserGroups());

        return $accessHandler;
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

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
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

        self::assertEquals($expect, $accessHandler->getDynamicUserGroups());
        self::assertEquals($expect, $accessHandler->getDynamicUserGroups());
    }

    /**
     * @group  unit
     * @covers ::getFullUserGroups()
     */
    public function testGetFullUserGroups()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );

        self::setValue($accessHandler, 'userGroups', [1 => $this->getUserGroup(1)]);
        self::setValue(
            $accessHandler,
            'dynamicUserGroups',
            [DynamicUserGroup::USER_TYPE.'|0' =>$this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 0)]
        );

        self::assertEquals(
            [
                1 => $this->getUserGroup(1),
                DynamicUserGroup::USER_TYPE.'|0' =>$this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 0)
            ],
            $accessHandler->getFullUserGroups()
        );
    }

    /**
     * @group  unit
     * @covers ::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
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

        self::setValue($accessHandler, 'userGroups', $userGroups);

        $dynamicUserGroups = [
            'users|0', $this->createMock(DynamicUserGroup::class),
        ];

        self::setValue($accessHandler, 'dynamicUserGroups', $dynamicUserGroups);

        $userUserGroups = $userGroups;
        unset($userUserGroups[4]);
        unset($userUserGroups[6]);

        self::setValue($accessHandler, 'userGroupsForUser', $userUserGroups);
        self::assertEquals($userUserGroups, $accessHandler->getFilteredUserGroups());
    }

    /**
     * @group   unit
     * @depends testGetUserGroups
     * @covers  ::addUserGroup()
     *
     * @param AccessHandler $accessHandler
     *
     * @return AccessHandler
     */
    public function testAddUserGroups(AccessHandler $accessHandler)
    {
        $expected = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($accessHandler, 'filteredUserGroups', []);
        $accessHandler->addUserGroup($this->getUserGroup(4));
        self::assertAttributeEquals($expected, 'userGroups', $accessHandler);
        self::assertAttributeEquals(null, 'filteredUserGroups', $accessHandler);

        return $accessHandler;
    }

    /**
     * @group   unit
     * @depends testAddUserGroups
     * @covers  ::deleteUserGroup()
     *
     * @param AccessHandler $accessHandler
     */
    public function testDeleteUserGroups(AccessHandler $accessHandler)
    {
        $expected = [
            1 => $this->getUserGroup(1),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($accessHandler, 'filteredUserGroups', []);
        self::assertFalse($accessHandler->deleteUserGroup(10));
        self::assertFalse($accessHandler->deleteUserGroup(3));
        self::assertAttributeEquals([], 'filteredUserGroups', $accessHandler);

        self::assertTrue($accessHandler->deleteUserGroup(2));
        self::assertAttributeEquals($expected, 'userGroups', $accessHandler);
        self::assertAttributeEquals(null, 'filteredUserGroups', $accessHandler);
    }

    /**
     * @group  unit
     * @covers ::getUserGroupsForObject()
     *
     * @return AccessHandler
     */
    public function testGetUserGroupsForObject()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
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

        self::setValue($accessHandler, 'userGroups', $userGroups);
        self::setValue(
            $accessHandler,
            'dynamicUserGroups',
            [DynamicUserGroup::USER_TYPE.'|0' =>$this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 0)]
        );

        self::assertEquals([], $accessHandler->getUserGroupsForObject('invalid', 1));

        self::assertEquals(
            [1 => $this->getUserGroup(1, true, true)],
            $accessHandler->getUserGroupsForObject('objectType', 1)
        );

        $userGroups = [
            1 => $this->getUserGroup(1, true, false, [''], 'none', 'none', [], [], null, [[false], [true]]),
            2 => $this->getUserGroup(2, true, true, [''], 'none', 'none', [], [], null, [[false], [true]]),
            3 => $this->getUserGroup(3, true, true, [''], 'none', 'none', [], [], null, [[false], [true]]),
            4 => $this->getUserGroup(4, true, false, [''], 'none', 'none', [], [], null, [[false], [true]])
        ];

        self::setValue($accessHandler, 'userGroups', $userGroups);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $accessHandler->getUserGroupsForObject('objectType', 2)
        );

        self::setValue($accessHandler, 'objectUserGroups', [0 => ['objectType' => []]]);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $accessHandler->getUserGroupsForObject('objectType', 2, true)
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
            $accessHandler
        );

        return $accessHandler;
    }

    /**
     * @group   unit
     * @depends testGetUserGroupsForObject
     * @covers  ::unsetUserGroupsForObject()
     *
     * @param AccessHandler $accessHandler
     */
    public function testUnsetUserGroupsForObject(AccessHandler $accessHandler)
    {
        self::assertAttributeNotEquals([], 'objectUserGroups', $accessHandler);
        $accessHandler->unsetUserGroupsForObject();
        self::assertAttributeEquals([], 'objectUserGroups', $accessHandler);
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
        $wordpressConfig->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(false, true, true, false, false, false, false, true, false));

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $userHandler->expects($this->once())
            ->method('getUserRole')
            ->will($this->returnValue([UserGroup::NONE_ROLE]));

        $userHandler->expects($this->exactly(6))
            ->method('isIpInRange')
            ->withConsecutive(
                ['1.1.1.1', ['1.1.1.1']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']],
                ['1.1.1.1', ['']]
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

        $userGroupFactory->expects($this->exactly(2))
            ->method('createDynamicUserGroup')
            ->withConsecutive(
                [DynamicUserGroup::USER_TYPE, 1],
                [DynamicUserGroup::ROLE_TYPE, '_none-role_']
            )
            ->will($this->returnCallback(function ($type, $id) {
                return $this->getDynamicUserGroup($type, $id);
            }));

        $accessHandler = new AccessHandler(
            $wordpress,
            $wordpressConfig,
            $this->getMainConfig(),
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

        self::setValue($accessHandler, 'userGroups', $userGroups);

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

        self::setValue($accessHandler, 'objectUserGroups', $objectUserGroups);

        $expected = $userGroups;
        $expected['user|1'] = $this->getDynamicUserGroup(DynamicUserGroup::USER_TYPE, 1);
        $expected['role|_none-role_'] = $this->getDynamicUserGroup(DynamicUserGroup::ROLE_TYPE, '_none-role_');
        unset($expected[4]);
        unset($expected[6]);
        unset($expected[7]);

        self::assertEquals($expected, $accessHandler->getUserGroupsForUser());
        self::assertEquals($userGroups, $accessHandler->getUserGroupsForUser());
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

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
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

        self::setValue($accessHandler, 'userGroups', $userGroups);

        $userUserGroups = $userGroups;
        unset($userUserGroups[4]);
        unset($userUserGroups[6]);

        self::setValue($accessHandler, 'userGroupsForUser', $userUserGroups);

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

        self::setValue($accessHandler, 'objectUserGroups', $objectUserGroups);

        $expected = [
            0 => $this->getUserGroup(0),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            5 => $this->getUserGroup(5)
        ];

        self::assertEquals($expected, $accessHandler->getFilteredUserGroupsForObject('objectType', 1));
    }

    /**
     * @group  unit
     * @covers ::checkObjectAccess()
     * @covers ::hasAuthorAccess()
     */
    public function testCheckObjectAccess()
    {
        $objectUserGroups = [
            0 => [
                'postType' => [
                    -1 => [3 => $this->getUserGroup(11)],
                    1 => [3 => $this->getUserGroup(3)],
                    2 => [0 => $this->getUserGroup(0)],
                    3 => [],
                    4 => [10 => $this->getUserGroup(10)]
                ]
            ]
        ];

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('checkUserAccess')
            ->will($this->returnValue(true));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(0),
            $userHandler,
            $this->getUserGroupFactory()
        );

        self::setValue($accessHandler, 'objectUserGroups', $objectUserGroups);
        self::setValue($accessHandler, 'userGroupsForUser', []);

        self::assertTrue($accessHandler->checkObjectAccess('invalid', 1));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 2));

        $config = $this->getMainConfig();

        $config->expects($this->exactly(5))
            ->method('authorsHasAccessToOwn')
            ->will($this->onConsecutiveCalls(true, true, false, false, true));

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(8))
            ->method('checkUserAccess')
            ->will($this->returnValue(false));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(3),
            $userHandler,
            $this->getUserGroupFactory()
        );

        self::setValue($accessHandler, 'objectUserGroups', $objectUserGroups);

        $userUserGroups = [0 => $this->getUserGroup(0)];
        self::setValue($accessHandler, 'userGroupsForUser', $userUserGroups);

        self::assertTrue($accessHandler->checkObjectAccess('postType', 1));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 2));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 3));
        self::assertFalse($accessHandler->checkObjectAccess('postType', 4));
        self::assertFalse($accessHandler->checkObjectAccess('postType', -1));

        self::assertAttributeEquals(
            [
                'postType' => [
                    1 => true,
                    2 => true,
                    3 => true,
                    4 => false,
                    -1 => false
                ]
            ],
            'objectAccess',
            $accessHandler
        );
    }

    /**
     * @group  unit
     * @covers ::getExcludedTerms()
     * @covers ::getExcludedObjects()
     */
    public function testGetExcludedTerms()
    {
        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('checkUserAccess')
            ->will($this->returnValue(true));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(['manage_user_groups']),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $userHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $accessHandler->getExcludedTerms());

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );

        $userGroups = [
            0 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [1 => 'term', 2 => 'term', 5 => 'term']),
            1 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [3 => 'term', 2 => 'term', 4 => 'term'])
        ];

        self::setValue($accessHandler, 'userGroups', $userGroups);

        $userGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [1 => 'term', 3 => 'term']),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [5 => 'term', 3 => 'term'])
        ];

        self::setValue($accessHandler, 'userGroupsForUser', $userGroupsForUser);

        self::assertEquals([2 => 2, 4 => 4], $accessHandler->getExcludedTerms());
        self::assertAttributeEquals([2 => 2, 4 => 4], 'excludedTerms', $accessHandler);
    }

    /**
     * @group  unit
     * @covers ::getExcludedPosts()
     * @covers ::getNoneHiddenPostTypes()
     * @covers ::getExcludedObjects()
     */
    public function testGetExcludedPosts()
    {
        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('checkUserAccess')
            ->will($this->returnValue(true));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(['manage_user_groups']),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $userHandler,
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $accessHandler->getExcludedPosts());

        $objectHandler = $this->getObjectHandler();

        $wordpress = $this->getWordpressWithUser();

        $wordpress->expects($this->exactly(3))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(false, true, false));

        $config = $this->getMainConfig();

        $config->expects($this->exactly(3))
            ->method('authorsHasAccessToOwn')
            ->will($this->onConsecutiveCalls(false, false, true));


        $config->expects($this->exactly(4))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['page'], ['post'], ['page'])
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->once())
            ->method('prepare')
            ->with(
                new MatchIgnoreWhitespace(
                    "SELECT ID
                    FROM postTable
                    WHERE post_author = %d"
                ),
                1
            )
            ->will($this->returnValue('ownPostQuery'));

        $database->expects($this->once())
            ->method('getResults')
            ->with('ownPostQuery')
            ->will($this->returnCallback(function () {
                $post = new \stdClass();
                $post->ID = 4;
                return [$post];
            }));

        $objectHandler->expects($this->exactly(2))
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'page' => 'page']));

        $accessHandler = new AccessHandler(
            $wordpress,
            $this->getWordpressConfig(),
            $config,
            $database,
            $objectHandler,
            $this->getUserHandler(),
            $this->getUserGroupFactory()
        );


        $userGroups = [
            0 => $this->getUserGroup(
                0,
                true,
                false,
                [''],
                'none',
                'none',
                [1 => 'post', 2 => 'page', 5 => 'post', 6 => 'post']
            ),
            1 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [3 => 'post', 2 => 'page', 4 => 'post'])
        ];

        self::setValue($accessHandler, 'userGroups', $userGroups);

        $userGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [1 => 'post', 3 => 'post']),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [5 => 'post', 3 => 'post'])
        ];

        self::setValue($accessHandler, 'userGroupsForUser', $userGroupsForUser);
        self::assertEquals([4 => 4, 6 => 6], $accessHandler->getExcludedPosts());
        self::assertAttributeEquals(['page' => 'page'], 'noneHiddenPostTypes', $accessHandler);
        self::assertAttributeEquals([4 => 4, 6 => 6], 'excludedPosts', $accessHandler);

        self::setValue($accessHandler, 'noneHiddenPostTypes', null);
        self::setValue($accessHandler, 'excludedPosts', null);
        self::assertEquals([2 => 2, 4 => 4, 6 => 6], $accessHandler->getExcludedPosts());
        self::assertAttributeEquals([], 'noneHiddenPostTypes', $accessHandler);
        self::assertAttributeEquals([2 => 2, 4 => 4, 6 => 6], 'excludedPosts', $accessHandler);

        self::setValue($accessHandler, 'noneHiddenPostTypes', null);
        self::setValue($accessHandler, 'excludedPosts', null);
        self::assertEquals([6 => 6], $accessHandler->getExcludedPosts());
        self::assertAttributeEquals(['page' => 'page'], 'noneHiddenPostTypes', $accessHandler);
        self::assertAttributeEquals([6 => 6], 'excludedPosts', $accessHandler);
    }
}
