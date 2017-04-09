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
namespace UserAccessManager\AccessHandler;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroup;

class AccessHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\AccessHandler\AccessHandler', $accessHandler);
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
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroups()
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
            $this->getConfig(),
            $this->getCache(),
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
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
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

        $userUserGroups = $userGroups;
        unset($userUserGroups[4]);
        unset($userUserGroups[6]);

        self::setValue($accessHandler, 'userGroupsForUser', $userUserGroups);
        self::assertEquals($userUserGroups, $accessHandler->getFilteredUserGroups());
    }

    /**
     * @group   unit
     * @depends testGetUserGroups
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::addUserGroup()
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
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::deleteUserGroup()
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
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroupsForObject()
     *
     * @return AccessHandler
     */
    public function testGetUserGroupsForObject()
    {
        $cache = $this->getCache();

        $cache->expects($this->exactly(3))
            ->method('generateCacheKey')
            ->withConsecutive(
                ['getUserGroupsForObject', 'objectType', 0],
                ['getUserGroupsForObject', 'objectType', 1],
                ['getUserGroupsForObject', 'objectType', 2]
            )
            ->will($this->returnValue('cacheKey'));

        $cache->expects($this->exactly(3))
            ->method('getFromCache')
            ->with('cacheKey')
            ->will($this->onConsecutiveCalls(
                [4 => $this->getUserGroup(4)],
                null,
                null
            ));

        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $cache,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $userGroups = [
            1 => $this->getUserGroup(1, true, true),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($accessHandler, 'userGroups', $userGroups);

        self::assertEquals([], $accessHandler->getUserGroupsForObject('invalid', 1));

        self::assertEquals(
            [4 => $this->getUserGroup(4)],
            $accessHandler->getUserGroupsForObject('objectType', 0)
        );

        self::assertEquals(
            [1 => $this->getUserGroup(1, true, true)],
            $accessHandler->getUserGroupsForObject('objectType', 1)
        );

        $userGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2, true, true),
            3 => $this->getUserGroup(3, true, true),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($accessHandler, 'userGroups', $userGroups);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $accessHandler->getUserGroupsForObject('objectType', 2)
        );

        return $accessHandler;
    }

    /**
     * @group   unit
     * @depends testGetUserGroupsForObject
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::unsetUserGroupsForObject()
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
     * @covers \UserAccessManager\AccessHandler\AccessHandler::isIpInRange()
     * @covers \UserAccessManager\AccessHandler\AccessHandler::calculateIp()
     */
    public function testIsIpInRange()
    {
        $ranges = [
            '1.1.1.1-1.1.2.1',
            '2.2.2.2',
            '5.5.5.5-6.6.6',
            '7.7.7-8.8.8.8'
        ];

        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals(1, self::callMethod($accessHandler, 'calculateIp', [[0, 0, 0, 1]]));
        self::assertEquals(256, self::callMethod($accessHandler, 'calculateIp', [[0, 0, 1, 0]]));
        self::assertEquals(65536, self::callMethod($accessHandler, 'calculateIp', [[0, 1, 0, 0]]));
        self::assertEquals(16777216, self::callMethod($accessHandler, 'calculateIp', [[1, 0, 0, 0]]));

        self::assertTrue($accessHandler->isIpInRange('1.1.1.1', $ranges));
        self::assertTrue($accessHandler->isIpInRange('1.1.1.100', $ranges));
        self::assertTrue($accessHandler->isIpInRange('1.1.2.1', $ranges));
        self::assertFalse($accessHandler->isIpInRange('1.1.2.2', $ranges));
        self::assertTrue($accessHandler->isIpInRange('2.2.2.2', $ranges));
        self::assertFalse($accessHandler->isIpInRange('3.2.2.2', $ranges));
        self::assertFalse($accessHandler->isIpInRange('5.5.5.5', $ranges));
        self::assertFalse($accessHandler->isIpInRange('8.8.8.8', $ranges));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroupsForUser()
     */
    public function testGetUserGroupsForUser()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $config = $this->getConfig();
        $config->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(false, true, true, false, false, false, false, true, false));

        $accessHandler = new AccessHandler(
            $wordpress,
            $config,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
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
        unset($expected[4]);
        unset($expected[6]);
        unset($expected[7]);
        self::assertEquals($expected, $accessHandler->getUserGroupsForUser());
        self::assertEquals($userGroups, $accessHandler->getUserGroupsForUser());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getFilteredUserGroupsForObject()
     */
    public function testGetFilteredUserGroupsForObject()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
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
     * @covers \UserAccessManager\AccessHandler\AccessHandler::checkUserAccess()
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserRole()
     */
    public function testCheckUserAccess()
    {
        $wordpress = $this->getWordpress(null, null);
        $wordpress->expects($this->exactly(3))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(true, false, false));

        $config = $this->getConfig();
        $config->expects($this->any())
            ->method('getFullAccessRole')
            ->will($this->returnValue('administrator'));

        $accessHandler = new AccessHandler(
            $wordpress,
            $config,
            $this->getCache(),
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

        $config = $this->getConfig();
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
            $this->getCache(),
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
     * @covers \UserAccessManager\AccessHandler\AccessHandler::checkObjectAccess()
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
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(0),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::setValue($accessHandler, 'objectUserGroups', $objectUserGroups);
        self::setValue($accessHandler, 'userGroupsForUser', []);

        self::assertTrue($accessHandler->checkObjectAccess('invalid', 1));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 2));

        $config = $this->getConfig();

        $config->expects($this->exactly(5))
            ->method('authorsHasAccessToOwn')
            ->will($this->onConsecutiveCalls(true, true, false, false, true));

        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $config,
            $this->getCache(),
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
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getExcludedTerms()
     */
    public function testGetExcludedTerms()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $accessHandler->getExcludedTerms());

        $accessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
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
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getExcludedPosts()
     */
    public function testGetExcludedPosts()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $accessHandler->getExcludedPosts());

        $objectHandler = $this->getObjectHandler();

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $config = $this->getConfig();
        $config->expects($this->exactly(2))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['page'])
            ->will($this->onConsecutiveCalls(true, false));

        $objectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post', 'page']));

        $accessHandler = new AccessHandler(
            $wordpress,
            $config,
            $this->getCache(),
            $this->getDatabase(),
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
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::userIsAdmin()
     */
    public function testUserIsAdmin()
    {
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
            $this->getConfig(),
            $this->getCache(),
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
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $objectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($accessHandler->userIsAdmin(1));
    }
}
