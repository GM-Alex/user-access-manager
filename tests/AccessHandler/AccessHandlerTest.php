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
namespace UserAccessManager\Tests\AccessHandler;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class AccessHandlerTest
 *
 * @package UserAccessManager\AccessHandler
 * @coversDefaultClass \UserAccessManager\AccessHandler\AccessHandler
 */
class AccessHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
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
     * @param array $capabilities
     * @param int   $capExpects
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_User
     */
    private function getUser(array $capabilities = null, $capExpects = null)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')
            ->setMethods(['has_cap'])
            ->getMock();
        $user->ID = 1;

        $capExpects = ($capExpects !== null) ? $this->exactly($capExpects) : $this->any();

        $user->expects($capExpects)
            ->method('has_cap')
            ->will($this->returnCallback(function ($cap) use ($capabilities) {
                return ($cap === 'user_cap' || in_array($cap, (array)$capabilities));
            }));

        if ($capabilities !== null) {
            $user->prefix_capabilities = $capabilities;
        }

        return $user;
    }

    /**
     * @param array $capabilities
     * @param int   $capExpects
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    protected function getWordpress(array $capabilities = null, $capExpects = null)
    {
        $wordpress = parent::getWordpress();

        $user = $this->getUser($capabilities, $capExpects);
        $wordpress->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        return $wordpress;
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
     * @param int $getPostsExpect
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    protected function getObjectHandler($getPostsExpect = null)
    {
        $objectHandler = parent::getObjectHandler();

        $objectHandler->expects($this->any())
            ->method('isValidObjectType')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'objectType'
                    || $objectType === 'postType'
                    || $objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE);
            }));

        $objectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'postType');
            }));

        $postExpects = ($getPostsExpect === null) ? $this->any() : $this->exactly($getPostsExpect);
        $objectHandler->expects($postExpects)
            ->method('getPost')
            ->will($this->returnCallback(function ($id) {
                if ($id === -1) {
                    return false;
                }

                /**
                 * @var \stdClass $post
                 */
                $post = $this->getMockBuilder('\WP_Post')->getMock();
                $post->ID = $id;
                $post->post_author = $id;
                return $post;
            }));

        return $objectHandler;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    protected function getDatabase()
    {
        $database = parent::getDatabase();

        $database->expects($this->any())
            ->method('getUserGroupTable')
            ->will($this->returnValue('getUserGroupTable'));

        $database->expects($this->any())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        return $database;
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
            $this->getWordpress(),
            $this->getMainConfig(),
            $database,
            $this->getObjectHandler(),
            $this->getUtil(),
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
            $this->getWordpress(),
            $this->getMainConfig(),
            $database,
            $this->getObjectHandler(),
            $this->getUtil(),
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
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
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
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
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
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
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

        self::setValue($accessHandler, 'objectUserGroups', ['objectType' => []]);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $accessHandler->getUserGroupsForObject('objectType', 2, true)
        );

        self::assertAttributeEquals(['objectType' => []], 'objectUserGroups', $accessHandler);

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
     * @covers ::isIpInRange()
     * @covers ::calculateIp()
     */
    public function testIsIpInRange()
    {
        $ranges = [
            '0.0.0.0-1.1.1',
            '1.1.1.1-1.1.2.1',
            '2.2.2.2',
            '5.5.5.5-6.6.6',
            '7.7.7-8.8.8.8',
            '0:0:0:0:0:ffff:101:101-0:0:0:0:0:ffff:101:201',
            '0:0:0:0:0:ffff:202:202',
            '0:0:0:0:0:ffff:505:505-0:0:0:0:0:ffff:606',
            '0:0:0:0:0:ffff:707-0:0:0:0:0:ffff:808:808'
        ];

        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertFalse(self::callMethod($accessHandler, 'calculateIp', ['0.0.0']));
        self::assertFalse(self::callMethod($accessHandler, 'calculateIp', ['255.255.255255']));
        self::assertEquals('1', self::callMethod($accessHandler, 'calculateIp', ['0.0.0.1']));
        self::assertEquals('100000000', self::callMethod($accessHandler, 'calculateIp', ['0.0.1.0']));
        self::assertFalse(self::callMethod($accessHandler, 'calculateIp', ['0:0:0:0:0:FFFF:0000']));
        self::assertEquals(
            '0000000000000000000000000000000000000000000000000000000000000000'
            .'0000000000000000111111111111111100000000000000000000000100000000',
            self::callMethod($accessHandler, 'calculateIp', ['0:0:0:0:0:FFFF:0000:0100'])
        );

        self::assertFalse($accessHandler->isIpInRange('0.0.0', ['0.0.0.0']));
        self::assertFalse($accessHandler->isIpInRange('1.1.1', $ranges));
        self::assertFalse($accessHandler->isIpInRange('0.0.0.0', $ranges));
        self::assertTrue($accessHandler->isIpInRange('1.1.1.1', $ranges));
        self::assertTrue($accessHandler->isIpInRange('1.1.1.100', $ranges));
        self::assertTrue($accessHandler->isIpInRange('1.1.2.1', $ranges));
        self::assertFalse($accessHandler->isIpInRange('1.1.2.2', $ranges));
        self::assertTrue($accessHandler->isIpInRange('2.2.2.2', $ranges));
        self::assertFalse($accessHandler->isIpInRange('3.2.2.2', $ranges));
        self::assertFalse($accessHandler->isIpInRange('5.5.5.5', $ranges));
        self::assertFalse($accessHandler->isIpInRange('8.8.8.8', $ranges));

        self::assertTrue($accessHandler->isIpInRange('0:0:0:0:0:ffff:101:101', $ranges));
        self::assertTrue($accessHandler->isIpInRange('0:0:0:0:0:ffff:101:164', $ranges));
        self::assertTrue($accessHandler->isIpInRange('0:0:0:0:0:ffff:101:201', $ranges));
        self::assertFalse($accessHandler->isIpInRange('0:0:0:0:0:ffff:101:202', $ranges));
        self::assertTrue($accessHandler->isIpInRange('0:0:0:0:0:ffff:202:202', $ranges));
        self::assertFalse($accessHandler->isIpInRange('0:0:0:0:0:ffff:302:202', $ranges));
        self::assertFalse($accessHandler->isIpInRange('0:0:0:0:0:ffff:505:505', $ranges));
        self::assertFalse($accessHandler->isIpInRange('0:0:0:0:0:ffff:808:808', $ranges));
    }

    /**
     * @group  unit
     * @covers ::getUserGroupsForUser()
     */
    public function testGetUserGroupsForUser()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $config = $this->getMainConfig();
        $config->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(false, true, true, false, false, false, false, true, false));

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
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
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
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [
                1 => [
                    0 => $this->getUserGroup(0),
                    5 => $this->getUserGroup(5)
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
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
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
            'objectType' => [
                1 => [
                    0 => $this->getUserGroup(0),
                    2 => $this->getUserGroup(2, true, false, [''], 'all'),
                    4 => $this->getUserGroup(4),
                    5 => $this->getUserGroup(5)
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
     * @covers ::checkUserAccess()
     * @covers ::getUserRole()
     */
    public function testCheckUserAccess()
    {
        $wordpress = $this->getWordpress(null, null);
        $wordpress->expects($this->exactly(3))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(true, false, false));

        $config = $this->getMainConfig();
        $config->expects($this->any())
            ->method('getFullAccessRole')
            ->will($this->returnValue('administrator'));

        $accessHandler = new AccessHandler(
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess('user_cap'));
        self::assertFalse($accessHandler->checkUserAccess());

        $noneUser = $this->getUser(null, 0);
        $unkownRoleUser = $this->getUser(['unkown' => true], 0);
        $multiRoleUser = $this->getUser(['subscriber' => true, 'contributor' => true, 'administrator' => true], 0);
        $adminUser = $this->getUser(['administrator' => true], 0);
        $editorUser = $this->getUser(['editor' => true], 0);
        $authorUser = $this->getUser(['author' => true], 0);
        $contributorUser = $this->getUser(['contributor' => true], 0);
        $subscriberUser = $this->getUser(['subscriber' => true], 0);

        $userReturn = [$adminUser, $editorUser, $authorUser, $contributorUser, $subscriberUser, $noneUser];

        $wordpress = parent::getWordpress();
        $wordpress->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->onConsecutiveCalls(
                $unkownRoleUser,
                $multiRoleUser,
                ...$userReturn,
                ...$userReturn,
                ...$userReturn,
                ...$userReturn,
                ...$userReturn,
                ...$userReturn
            ));

        $wordpress->expects($this->any())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $config = $this->getMainConfig();
        $config->expects($this->any())
            ->method('getFullAccessRole')
            ->will($this->onConsecutiveCalls(
                UserGroup::NONE_ROLE,
                'administrator',
                ...array_fill(0, 6, 'administrator'),
                ...array_fill(0, 6, 'editor'),
                ...array_fill(0, 6, 'author'),
                ...array_fill(0, 6, 'contributor'),
                ...array_fill(0, 6, 'subscriber'),
                ...array_fill(0, 6, UserGroup::NONE_ROLE)
            ));

        $accessHandler = new AccessHandler(
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );


        self::assertFalse($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());

        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());

        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());

        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());

        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertFalse($accessHandler->checkUserAccess());

        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
        self::assertTrue($accessHandler->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers ::checkObjectAccess()
     */
    public function testCheckObjectAccess()
    {
        $objectUserGroups = [
            'postType' => [
                -1 => [3 => $this->getUserGroup(11)],
                1 => [3 => $this->getUserGroup(3)],
                2 => [0 => $this->getUserGroup(0)],
                3 => [],
                4 => [10 => $this->getUserGroup(10)]
            ]
        ];

        $accessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(0),
            $this->getUtil(),
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

        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(3),
            $this->getUtil(),
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
     */
    public function testGetExcludedTerms()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $accessHandler->getExcludedTerms());

        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
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
     */
    public function testGetExcludedPosts()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $accessHandler->getExcludedPosts());

        $objectHandler = $this->getObjectHandler();

        $wordpress = $this->getWordpress();

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
            ->will($this->returnValue(['post', 'page']));

        $accessHandler = new AccessHandler(
            $wordpress,
            $config,
            $database,
            $objectHandler,
            $this->getUtil(),
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
        self::assertAttributeEquals([4 => 4, 6 => 6], 'excludedPosts', $accessHandler);

        $this->setValue($accessHandler, 'excludedPosts', null);
        self::assertEquals([2 => 2, 4 => 4, 6 => 6], $accessHandler->getExcludedPosts());
        self::assertAttributeEquals([2 => 2, 4 => 4, 6 => 6], 'excludedPosts', $accessHandler);

        $this->setValue($accessHandler, 'excludedPosts', null);
        self::assertEquals([6 => 6], $accessHandler->getExcludedPosts());
        self::assertAttributeEquals([6 => 6], 'excludedPosts', $accessHandler);
    }

    /**
     * @group  unit
     * @covers ::userIsAdmin()
     * @covers ::getUserRole()
     */
    public function testUserIsAdmin()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(2))
            ->method('getUser')
            ->will($this->onConsecutiveCalls(false, $this->getUser(['administrator' => 1])));

        $accessHandler = new AccessHandler(
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $objectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertFalse($accessHandler->userIsAdmin(1));
        self::assertTrue($accessHandler->userIsAdmin(1));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser();
            }));

        $accessHandler = new AccessHandler(
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $objectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertFalse($accessHandler->userIsAdmin(1));
        self::assertTrue($accessHandler->userIsAdmin(1));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->never())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser(['administrator' => 1]);
            }));

        $accessHandler = new AccessHandler(
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $objectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($accessHandler->userIsAdmin(1));
    }
}
