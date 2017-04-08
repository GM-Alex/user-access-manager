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
 * @version   SVN: $Id$
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
        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\AccessHandler\AccessHandler', $AccessHandler);
    }

    /**
     * Generates return values.
     *
     * @param int $iNumber
     *
     * @return array
     */
    private function generateReturn($iNumber)
    {
        $aReturn = [];

        for ($iCounter = 1; $iCounter <= $iNumber; $iCounter++) {
            $Return = new \stdClass();
            $Return->ID = $iCounter;
            $aReturn[] = $Return;
        }

        return $aReturn;
    }

    /**
     * @param array $aCapabilities
     * @param int   $iCapExpects
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_User
     */
    private function getUser(array $aCapabilities = null, $iCapExpects = null)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')
            ->setMethods(['has_cap'])
            ->getMock();
        $User->ID = 1;

        $CapExpects = ($iCapExpects !== null) ? $this->exactly($iCapExpects) : $this->any();

        $User->expects($CapExpects)
            ->method('has_cap')
            ->will($this->returnCallback(function ($sCap) use ($aCapabilities) {
                return ($sCap === 'user_cap' || in_array($sCap, (array)$aCapabilities));
            }));

        if ($aCapabilities !== null) {
            $User->prefix_capabilities = $aCapabilities;
        }

        return $User;
    }

    /**
     * @param array $aCapabilities
     * @param int   $iCapExpects
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    protected function getWordpress(array $aCapabilities = null, $iCapExpects = null)
    {
        $Wordpress = parent::getWordpress();

        $User = $this->getUser($aCapabilities, $iCapExpects);
        $Wordpress->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->returnValue($User));

        return $Wordpress;
    }

    /**
     * @param int $iGetPostsExpect
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    protected function getObjectHandler($iGetPostsExpect = null)
    {
        $ObjectHandler = parent::getObjectHandler();

        $ObjectHandler->expects($this->any())
            ->method('isValidObjectType')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'objectType'
                    || $sObjectType === 'postType'
                    || $sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE);
            }));

        $ObjectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'postType');
            }));

        $PostExpects = ($iGetPostsExpect === null) ? $this->any() : $this->exactly($iGetPostsExpect);
        $ObjectHandler->expects($PostExpects)
            ->method('getPost')
            ->will($this->returnCallback(function ($iId) {
                if ($iId === -1) {
                    return false;
                }

                /**
                 * @var \stdClass $Post
                 */
                $Post = $this->getMockBuilder('\WP_Post')->getMock();
                $Post->ID = $iId;
                $Post->post_author = $iId;
                return $Post;
            }));

        return $ObjectHandler;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    protected function getDatabase()
    {
        $Database = parent::getDatabase();

        $Database->expects($this->any())
            ->method('getUserGroupTable')
            ->will($this->returnValue('getUserGroupTable'));

        $Database->expects($this->any())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        return $Database;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroups()
     *
     * @return AccessHandler
     */
    public function testGetUserGroups()
    {
        $Database = $this->getDatabase();

        $sQuery = 'SELECT ID FROM getUserGroupTable';

        $Database->expects($this->once())
            ->method('getResults')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($sQuery)]
            )
            ->will($this->returnValue($this->generateReturn(3)));

        $UserGroupFactory = $this->getUserGroupFactory();

        $UserGroupFactory->expects($this->exactly(3))
            ->method('createUserGroup')
            ->withConsecutive([1], [2], [3])
            ->will($this->returnCallback(function ($iId) {
                return $this->getUserGroup($iId, !($iId === 3));
            }));

        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $Database,
            $this->getObjectHandler(),
            $this->getUtil(),
            $UserGroupFactory
        );

        $aExpected = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3, false)
        ];

        self::assertEquals($aExpected, $AccessHandler->getUserGroups());
        self::assertAttributeEquals($aExpected, 'aUserGroups', $AccessHandler);
        self::assertEquals($aExpected, $AccessHandler->getUserGroups());

        return $AccessHandler;
    }


    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $aUserGroups = [
            0 => $this->getUserGroup(0),
            1 => $this->getUserGroup(1, true, false, ['1.1.1.1']),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'all'),
            4 => $this->getUserGroup(4),
            5 => $this->getUserGroup(5),
            6 => $this->getUserGroup(6)
        ];

        self::setValue($AccessHandler, 'aUserGroups', $aUserGroups);

        $aUserUserGroups = $aUserGroups;
        unset($aUserUserGroups[4]);
        unset($aUserUserGroups[6]);

        self::setValue($AccessHandler, 'aUserGroupsForUser', $aUserUserGroups);
        self::assertEquals($aUserUserGroups, $AccessHandler->getFilteredUserGroups());
    }

    /**
     * @group   unit
     * @depends testGetUserGroups
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::addUserGroup()
     *
     * @param AccessHandler $AccessHandler
     *
     * @return AccessHandler
     */
    public function testAddUserGroups(AccessHandler $AccessHandler)
    {
        $aExpected = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($AccessHandler, 'aFilteredUserGroups', []);
        $AccessHandler->addUserGroup($this->getUserGroup(4));
        self::assertAttributeEquals($aExpected, 'aUserGroups', $AccessHandler);
        self::assertAttributeEquals(null, 'aFilteredUserGroups', $AccessHandler);

        return $AccessHandler;
    }

    /**
     * @group   unit
     * @depends testAddUserGroups
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::deleteUserGroup()
     *
     * @param AccessHandler $AccessHandler
     */
    public function testDeleteUserGroups(AccessHandler $AccessHandler)
    {
        $aExpected = [
            1 => $this->getUserGroup(1),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($AccessHandler, 'aFilteredUserGroups', []);
        self::assertFalse($AccessHandler->deleteUserGroup(10));
        self::assertFalse($AccessHandler->deleteUserGroup(3));
        self::assertAttributeEquals([], 'aFilteredUserGroups', $AccessHandler);

        self::assertTrue($AccessHandler->deleteUserGroup(2));
        self::assertAttributeEquals($aExpected, 'aUserGroups', $AccessHandler);
        self::assertAttributeEquals(null, 'aFilteredUserGroups', $AccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroupsForObject()
     *
     * @return AccessHandler
     */
    public function testGetUserGroupsForObject()
    {
        $Cache = $this->getCache();

        $Cache->expects($this->exactly(3))
            ->method('generateCacheKey')
            ->withConsecutive(
                ['getUserGroupsForObject', 'objectType', 0],
                ['getUserGroupsForObject', 'objectType', 1],
                ['getUserGroupsForObject', 'objectType', 2]
            )
            ->will($this->returnValue('cacheKey'));

        $Cache->expects($this->exactly(3))
            ->method('getFromCache')
            ->with('cacheKey')
            ->will($this->onConsecutiveCalls(
                [4 => $this->getUserGroup(4)],
                null,
                null
            ));

        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $Cache,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $aUserGroups = [
            1 => $this->getUserGroup(1, true, true),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($AccessHandler, 'aUserGroups', $aUserGroups);

        self::assertEquals([], $AccessHandler->getUserGroupsForObject('invalid', 1));

        self::assertEquals(
            [4 => $this->getUserGroup(4)],
            $AccessHandler->getUserGroupsForObject('objectType', 0)
        );

        self::assertEquals(
            [1 => $this->getUserGroup(1, true, true)],
            $AccessHandler->getUserGroupsForObject('objectType', 1)
        );

        $aUserGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2, true, true),
            3 => $this->getUserGroup(3, true, true),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($AccessHandler, 'aUserGroups', $aUserGroups);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $AccessHandler->getUserGroupsForObject('objectType', 2)
        );

        return $AccessHandler;
    }

    /**
     * @group   unit
     * @depends testGetUserGroupsForObject
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::unsetUserGroupsForObject()
     *
     * @param AccessHandler $AccessHandler
     */
    public function testUnsetUserGroupsForObject(AccessHandler $AccessHandler)
    {
        self::assertAttributeNotEquals([], 'aObjectUserGroups', $AccessHandler);
        $AccessHandler->unsetUserGroupsForObject();
        self::assertAttributeEquals([], 'aObjectUserGroups', $AccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::isIpInRange()
     * @covers \UserAccessManager\AccessHandler\AccessHandler::calculateIp()
     */
    public function testIsIpInRange()
    {
        $aRanges = [
            '1.1.1.1-1.1.2.1',
            '2.2.2.2',
            '5.5.5.5-6.6.6',
            '7.7.7-8.8.8.8'
        ];

        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals(1, self::callMethod($AccessHandler, 'calculateIp', [[0, 0, 0, 1]]));
        self::assertEquals(256, self::callMethod($AccessHandler, 'calculateIp', [[0, 0, 1, 0]]));
        self::assertEquals(65536, self::callMethod($AccessHandler, 'calculateIp', [[0, 1, 0, 0]]));
        self::assertEquals(16777216, self::callMethod($AccessHandler, 'calculateIp', [[1, 0, 0, 0]]));

        self::assertTrue($AccessHandler->isIpInRange('1.1.1.1', $aRanges));
        self::assertTrue($AccessHandler->isIpInRange('1.1.1.100', $aRanges));
        self::assertTrue($AccessHandler->isIpInRange('1.1.2.1', $aRanges));
        self::assertFalse($AccessHandler->isIpInRange('1.1.2.2', $aRanges));
        self::assertTrue($AccessHandler->isIpInRange('2.2.2.2', $aRanges));
        self::assertFalse($AccessHandler->isIpInRange('3.2.2.2', $aRanges));
        self::assertFalse($AccessHandler->isIpInRange('5.5.5.5', $aRanges));
        self::assertFalse($AccessHandler->isIpInRange('8.8.8.8', $aRanges));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroupsForUser()
     */
    public function testGetUserGroupsForUser()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(9))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(false, true, true, false, false, false, false, true, false));

        $AccessHandler = new AccessHandler(
            $Wordpress,
            $Config,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';

        $aUserGroups = [
            0 => $this->getUserGroup(0),
            1 => $this->getUserGroup(1, true, false, ['1.1.1.1']),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'all'),
            4 => $this->getUserGroup(4),
            5 => $this->getUserGroup(5),
            6 => $this->getUserGroup(6),
            7 => $this->getUserGroup(7, true, false, [''], 'all', 'all')
        ];

        self::setValue($AccessHandler, 'aUserGroups', $aUserGroups);

        $aObjectUserGroups = [
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [
                1 => [
                    0 => $this->getUserGroup(0),
                    5 => $this->getUserGroup(5)
                ]
            ]
        ];

        self::setValue($AccessHandler, 'aObjectUserGroups', $aObjectUserGroups);

        $aExpected = $aUserGroups;
        unset($aExpected[4]);
        unset($aExpected[6]);
        unset($aExpected[7]);
        self::assertEquals($aExpected, $AccessHandler->getUserGroupsForUser());
        self::assertEquals($aUserGroups, $AccessHandler->getUserGroupsForUser());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getFilteredUserGroupsForObject()
     */
    public function testGetFilteredUserGroupsForObject()
    {
        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $aUserGroups = [
            0 => $this->getUserGroup(0),
            1 => $this->getUserGroup(1, true, false, ['1.1.1.1']),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            3 => $this->getUserGroup(3, true, false, [''], 'none', 'all'),
            4 => $this->getUserGroup(4),
            5 => $this->getUserGroup(5),
            6 => $this->getUserGroup(6)
        ];

        self::setValue($AccessHandler, 'aUserGroups', $aUserGroups);

        $aUserUserGroups = $aUserGroups;
        unset($aUserUserGroups[4]);
        unset($aUserUserGroups[6]);

        self::setValue($AccessHandler, 'aUserGroupsForUser', $aUserUserGroups);

        $aObjectUserGroups = [
            'objectType' => [
                1 => [
                    0 => $this->getUserGroup(0),
                    2 => $this->getUserGroup(2, true, false, [''], 'all'),
                    4 => $this->getUserGroup(4),
                    5 => $this->getUserGroup(5)
                ]
            ]
        ];

        self::setValue($AccessHandler, 'aObjectUserGroups', $aObjectUserGroups);

        $aExpected = [
            0 => $this->getUserGroup(0),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            5 => $this->getUserGroup(5)
        ];

        self::assertEquals($aExpected, $AccessHandler->getFilteredUserGroupsForObject('objectType', 1));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::checkUserAccess()
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserRole()
     */
    public function testCheckUserAccess()
    {
        $Wordpress = $this->getWordpress(null, null);
        $Wordpress->expects($this->exactly(3))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(true, false, false));

        $Config = $this->getConfig();
        $Config->expects($this->any())
            ->method('getFullAccessRole')
            ->will($this->returnValue('administrator'));

        $AccessHandler = new AccessHandler(
            $Wordpress,
            $Config,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess('user_cap'));
        self::assertFalse($AccessHandler->checkUserAccess());

        $NoneUser = $this->getUser(null, 0);
        $UnkownRoleUser = $this->getUser(['unkown' => true], 0);
        $MultiRoleUser = $this->getUser(['subscriber' => true, 'contributor' => true, 'administrator' => true], 0);
        $AdminUser = $this->getUser(['administrator' => true], 0);
        $EditorUser = $this->getUser(['editor' => true], 0);
        $AuthorUser = $this->getUser(['author' => true], 0);
        $ContributorUser = $this->getUser(['contributor' => true], 0);
        $SubscriberUser = $this->getUser(['subscriber' => true], 0);

        $aUserReturn = [$AdminUser, $EditorUser, $AuthorUser, $ContributorUser, $SubscriberUser, $NoneUser];

        $Wordpress = parent::getWordpress();
        $Wordpress->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->onConsecutiveCalls(
                $UnkownRoleUser,
                $MultiRoleUser,
                ...$aUserReturn,
                ...$aUserReturn,
                ...$aUserReturn,
                ...$aUserReturn,
                ...$aUserReturn,
                ...$aUserReturn
            ));

        $Wordpress->expects($this->any())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $Config = $this->getConfig();
        $Config->expects($this->any())
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

        $AccessHandler = new AccessHandler(
            $Wordpress,
            $Config,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );


        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());

        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());

        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());

        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());

        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertFalse($AccessHandler->checkUserAccess());

        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
        self::assertTrue($AccessHandler->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::checkObjectAccess()
     */
    public function testCheckObjectAccess()
    {
        $aObjectUserGroups = [
            'postType' => [
                -1 => [3 => $this->getUserGroup(11)],
                1 => [3 => $this->getUserGroup(3)],
                2 => [0 => $this->getUserGroup(0)],
                3 => [],
                4 => [10 => $this->getUserGroup(10)]
            ]
        ];

        $AccessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(0),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::setValue($AccessHandler, 'aObjectUserGroups', $aObjectUserGroups);
        self::setValue($AccessHandler, 'aUserGroupsForUser', []);

        self::assertTrue($AccessHandler->checkObjectAccess('invalid', 1));
        self::assertTrue($AccessHandler->checkObjectAccess('postType', 2));

        $Config = $this->getConfig();

        $Config->expects($this->exactly(5))
            ->method('authorsHasAccessToOwn')
            ->will($this->onConsecutiveCalls(true, true, false, false, true));

        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $Config,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(3),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::setValue($AccessHandler, 'aObjectUserGroups', $aObjectUserGroups);

        $aUserUserGroups = [0 => $this->getUserGroup(0)];
        self::setValue($AccessHandler, 'aUserGroupsForUser', $aUserUserGroups);

        self::assertTrue($AccessHandler->checkObjectAccess('postType', 1));
        self::assertTrue($AccessHandler->checkObjectAccess('postType', 2));
        self::assertTrue($AccessHandler->checkObjectAccess('postType', 3));
        self::assertFalse($AccessHandler->checkObjectAccess('postType', 4));
        self::assertFalse($AccessHandler->checkObjectAccess('postType', -1));

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
            'aObjectAccess',
            $AccessHandler
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getExcludedTerms()
     */
    public function testGetExcludedTerms()
    {
        $AccessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $AccessHandler->getExcludedTerms());

        $AccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $aUserGroups = [
            0 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [1 => 'term', 2 => 'term', 5 => 'term']),
            1 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [3 => 'term', 2 => 'term', 4 => 'term'])
        ];

        self::setValue($AccessHandler, 'aUserGroups', $aUserGroups);

        $aUserGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [1 => 'term', 3 => 'term']),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [5 => 'term', 3 => 'term'])
        ];

        self::setValue($AccessHandler, 'aUserGroupsForUser', $aUserGroupsForUser);

        self::assertEquals([2 => 2, 4 => 4], $AccessHandler->getExcludedTerms());
        self::assertAttributeEquals([2 => 2, 4 => 4], 'aExcludedTerms', $AccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getExcludedPosts()
     */
    public function testGetExcludedPosts()
    {
        $AccessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $AccessHandler->getExcludedPosts());

        $ObjectHandler = $this->getObjectHandler();

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(2))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['page'])
            ->will($this->onConsecutiveCalls(true, false));

        $ObjectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post', 'page']));

        $AccessHandler = new AccessHandler(
            $Wordpress,
            $Config,
            $this->getCache(),
            $this->getDatabase(),
            $ObjectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );


        $aUserGroups = [
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

        self::setValue($AccessHandler, 'aUserGroups', $aUserGroups);

        $aUserGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [1 => 'post', 3 => 'post']),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [5 => 'post', 3 => 'post'])
        ];

        self::setValue($AccessHandler, 'aUserGroupsForUser', $aUserGroupsForUser);

        self::assertEquals([4 => 4, 6 => 6], $AccessHandler->getExcludedPosts());
        self::assertAttributeEquals([4 => 4, 6 => 6], 'aExcludedPosts', $AccessHandler);

        $this->setValue($AccessHandler, 'aExcludedPosts', null);
        self::assertEquals([2 => 2, 4 => 4, 6 => 6], $AccessHandler->getExcludedPosts());
        self::assertAttributeEquals([2 => 2, 4 => 4, 6 => 6], 'aExcludedPosts', $AccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::userIsAdmin()
     */
    public function testUserIsAdmin()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser();
            }));

        $AccessHandler = new AccessHandler(
            $Wordpress,
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $ObjectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertFalse($AccessHandler->userIsAdmin(1));
        self::assertTrue($AccessHandler->userIsAdmin(1));

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->never())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser(['administrator' => 1]);
            }));

        $AccessHandler = new AccessHandler(
            $Wordpress,
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $ObjectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($AccessHandler->userIsAdmin(1));
    }
}
