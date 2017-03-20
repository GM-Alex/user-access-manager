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


class AccessHandlerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $oAccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\AccessHandler\AccessHandler', $oAccessHandler);
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
            $oReturn = new \stdClass();
            $oReturn->ID = $iCounter;
            $aReturn[] = $oReturn;
        }

        return $aReturn;
    }

    /**
     * @param array $aCapabilities
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_User
     */
    private function getUser(array $aCapabilities = null)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')
            ->setMethods(['has_cap'])
            ->getMock();
        $oUser->ID = 1;

        $oUser->expects($this->any())
            ->method('has_cap')
            ->will($this->returnCallback(function ($sCap) use ($aCapabilities) {
                return ($sCap === 'user_cap' || in_array($sCap, (array)$aCapabilities));
            }));

        if ($aCapabilities !== null) {
            $oUser->prefix_capabilities = $aCapabilities;
        }

        return $oUser;
    }

    /**
     * @param array $aCapabilities
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    protected function getWordpress(array $aCapabilities = null)
    {
        $oWordpress = parent::getWordpress();

        $oWordpress->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->returnCallback(function () use ($aCapabilities) {
                return $this->getUser($aCapabilities);
            }));

        return $oWordpress;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    protected function getObjectHandler()
    {
        $oObjectHandler = parent::getObjectHandler();

        $oObjectHandler->expects($this->any())
            ->method('isValidObjectType')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'objectType'
                    || $sObjectType === 'postType'
                    || $sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE);
            }));

        $oObjectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'postType');
            }));

        $oObjectHandler->expects($this->any())
            ->method('getPost')
            ->will($this->returnCallback(function ($iId) {
                if ($iId === -1) {
                    return false;
                }

                /**
                 * @var \stdClass $oPost
                 */
                $oPost = $this->getMockBuilder('\WP_Post')->getMock();
                $oPost->ID = $iId;
                $oPost->post_author = $iId;
                return $oPost;
            }));

        return $oObjectHandler;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    protected function getDatabase()
    {
        $oDatabase = parent::getDatabase();

        $oDatabase->expects($this->any())
            ->method('getUserGroupTable')
            ->will($this->returnValue('getUserGroupTable'));

        $oDatabase->expects($this->any())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        return $oDatabase;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroups()
     *
     * @return AccessHandler
     */
    public function testGetUserGroups()
    {
        $oDatabase = $this->getDatabase();

        $sQuery = 'SELECT ID FROM getUserGroupTable';

        $oDatabase->expects($this->once())
            ->method('getResults')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($sQuery)]
            )
            ->will($this->returnValue($this->generateReturn(3)));

        $oUserGroupFactory = $this->getUserGroupFactory();

        $oUserGroupFactory->expects($this->exactly(3))
            ->method('createUserGroup')
            ->withConsecutive([1], [2], [3])
            ->will($this->returnCallback(function ($iId) {
                return $this->getUserGroup($iId, !($iId === 3));
            }));

        $oAccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $oDatabase,
            $this->getObjectHandler(),
            $this->getUtil(),
            $oUserGroupFactory
        );

        $aExpected = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3, false)
        ];

        self::assertEquals($aExpected, $oAccessHandler->getUserGroups());
        self::assertAttributeEquals($aExpected, '_aUserGroups', $oAccessHandler);
        self::assertEquals($aExpected, $oAccessHandler->getUserGroups());

        return $oAccessHandler;
    }


    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getFilteredUserGroups()
     */
    public function testGetFilteredUserGroups()
    {
        $oAccessHandler = new AccessHandler(
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

        self::setValue($oAccessHandler, '_aUserGroups', $aUserGroups);

        $aUserUserGroups = $aUserGroups;
        unset($aUserUserGroups[4]);
        unset($aUserUserGroups[6]);

        self::setValue($oAccessHandler, '_aUserGroupsForUser', $aUserUserGroups);
        self::assertEquals($aUserUserGroups, $oAccessHandler->getFilteredUserGroups());
    }

    /**
     * @group   unit
     * @depends testGetUserGroups
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::addUserGroup()
     *
     * @param AccessHandler $oAccessHandler
     *
     * @return AccessHandler
     */
    public function testAddUserGroups(AccessHandler $oAccessHandler)
    {
        $aExpected = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($oAccessHandler, '_aFilteredUserGroups', []);
        $oAccessHandler->addUserGroup($this->getUserGroup(4));
        self::assertAttributeEquals($aExpected, '_aUserGroups', $oAccessHandler);
        self::assertAttributeEquals(null, '_aFilteredUserGroups', $oAccessHandler);

        return $oAccessHandler;
    }

    /**
     * @group   unit
     * @depends testAddUserGroups
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::deleteUserGroup()
     *
     * @param AccessHandler $oAccessHandler
     */
    public function testDeleteUserGroups(AccessHandler $oAccessHandler)
    {
        $aExpected = [
            1 => $this->getUserGroup(1),
            3 => $this->getUserGroup(3, false),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($oAccessHandler, '_aFilteredUserGroups', []);
        self::assertFalse($oAccessHandler->deleteUserGroup(10));
        self::assertFalse($oAccessHandler->deleteUserGroup(3));
        self::assertAttributeEquals([], '_aFilteredUserGroups', $oAccessHandler);

        self::assertTrue($oAccessHandler->deleteUserGroup(2));
        self::assertAttributeEquals($aExpected, '_aUserGroups', $oAccessHandler);
        self::assertAttributeEquals(null, '_aFilteredUserGroups', $oAccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroupsForObject()
     *
     * @return AccessHandler
     */
    public function testGetUserGroupsForObject()
    {
        $oCache = $this->getCache();

        $oCache->expects($this->exactly(3))
            ->method('generateCacheKey')
            ->withConsecutive(
                ['getUserGroupsForObject', 'objectType', 0],
                ['getUserGroupsForObject', 'objectType', 1],
                ['getUserGroupsForObject', 'objectType', 2]
            )
            ->will($this->returnValue('cacheKey'));

        $oCache->expects($this->exactly(3))
            ->method('getFromCache')
            ->with('cacheKey')
            ->will($this->onConsecutiveCalls(
                [4 => $this->getUserGroup(4)],
                null,
                null
            ));

        $oAccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $oCache,
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

        self::setValue($oAccessHandler, '_aUserGroups', $aUserGroups);

        self::assertEquals([], $oAccessHandler->getUserGroupsForObject('invalid', 1));

        self::assertEquals(
            [4 => $this->getUserGroup(4)],
            $oAccessHandler->getUserGroupsForObject('objectType', 0)
        );

        self::assertEquals(
            [1 => $this->getUserGroup(1, true, true)],
            $oAccessHandler->getUserGroupsForObject('objectType', 1)
        );

        $aUserGroups = [
            1 => $this->getUserGroup(1),
            2 => $this->getUserGroup(2, true, true),
            3 => $this->getUserGroup(3, true, true),
            4 => $this->getUserGroup(4)
        ];

        self::setValue($oAccessHandler, '_aUserGroups', $aUserGroups);

        self::assertEquals(
            [
                2 => $this->getUserGroup(2, true, true),
                3 => $this->getUserGroup(3, true, true)
            ],
            $oAccessHandler->getUserGroupsForObject('objectType', 2)
        );

        return $oAccessHandler;
    }

    /**
     * @group   unit
     * @depends testGetUserGroupsForObject
     * @covers  \UserAccessManager\AccessHandler\AccessHandler::unsetUserGroupsForObject()
     *
     * @param AccessHandler $oAccessHandler
     */
    public function testUnsetUserGroupsForObject(AccessHandler $oAccessHandler)
    {
        self::assertAttributeNotEquals([], '_aObjectUserGroups', $oAccessHandler);
        $oAccessHandler->unsetUserGroupsForObject();
        self::assertAttributeEquals([], '_aObjectUserGroups', $oAccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::isIpInRange()
     * @covers \UserAccessManager\AccessHandler\AccessHandler::_calculateIp()
     */
    public function testIsIpInRange()
    {
        $aRanges = [
            '1.1.1.1-1.1.2.1',
            '2.2.2.2'
        ];

        $oAccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($oAccessHandler->isIpInRange('1.1.1.1', $aRanges));
        self::assertTrue($oAccessHandler->isIpInRange('1.1.1.100', $aRanges));
        self::assertTrue($oAccessHandler->isIpInRange('1.1.2.1', $aRanges));
        self::assertFalse($oAccessHandler->isIpInRange('1.1.2.2', $aRanges));
        self::assertTrue($oAccessHandler->isIpInRange('2.2.2.2', $aRanges));
        self::assertFalse($oAccessHandler->isIpInRange('3.2.2.2', $aRanges));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getUserGroupsForUser()
     */
    public function testGetUserGroupsForUser()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(7))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(false, true, true, false, false, false, false));

        $oAccessHandler = new AccessHandler(
            $oWordpress,
            $oConfig,
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
            6 => $this->getUserGroup(6)
        ];

        self::setValue($oAccessHandler, '_aUserGroups', $aUserGroups);

        $aObjectUserGroups = [
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [
                1 => [
                    0 => $this->getUserGroup(0),
                    5 => $this->getUserGroup(5)
                ]
            ]
        ];

        self::setValue($oAccessHandler, '_aObjectUserGroups', $aObjectUserGroups);

        $aExpected = $aUserGroups;
        unset($aExpected[4]);
        unset($aExpected[6]);
        self::assertEquals($aExpected, $oAccessHandler->getUserGroupsForUser());
        self::assertEquals($aUserGroups, $oAccessHandler->getUserGroupsForUser());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getFilteredUserGroupsForObject()
     */
    public function testGetFilteredUserGroupsForObject()
    {
        $oAccessHandler = new AccessHandler(
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

        self::setValue($oAccessHandler, '_aUserGroups', $aUserGroups);

        $aUserUserGroups = $aUserGroups;
        unset($aUserUserGroups[4]);
        unset($aUserUserGroups[6]);

        self::setValue($oAccessHandler, '_aUserGroupsForUser', $aUserUserGroups);

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

        self::setValue($oAccessHandler, '_aObjectUserGroups', $aObjectUserGroups);

        $aExpected = [
            0 => $this->getUserGroup(0),
            2 => $this->getUserGroup(2, true, false, [''], 'all'),
            5 => $this->getUserGroup(5)
        ];

        self::assertEquals($aExpected, $oAccessHandler->getFilteredUserGroupsForObject('objectType', 1));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::checkUserAccess()
     * @covers \UserAccessManager\AccessHandler\AccessHandler::_getUserRole()
     */
    public function testCheckUserAccess()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->exactly(3))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(true, false, false));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->any())
            ->method('getFullAccessRole')
            ->will($this->returnValue('administrator'));

        $oAccessHandler = new AccessHandler(
            $oWordpress,
            $oConfig,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($oAccessHandler->checkUserAccess());
        self::assertTrue($oAccessHandler->checkUserAccess('user_cap'));
        self::assertFalse($oAccessHandler->checkUserAccess());

        $oWordpress = $this->getWordpress(['author' => true]);
        $oWordpress->expects($this->any())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->any())
            ->method('getFullAccessRole')
            ->will($this->onConsecutiveCalls('administrator', 'author'));

        $oAccessHandler = new AccessHandler(
            $oWordpress,
            $oConfig,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertFalse($oAccessHandler->checkUserAccess());
        self::assertTrue($oAccessHandler->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::checkObjectAccess()
     */
    public function testCheckObjectAccess()
    {
        $oAccessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($oAccessHandler->checkObjectAccess('invalid', 1));
        self::assertTrue($oAccessHandler->checkObjectAccess('objectType', 1));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(4))
            ->method('authorsHasAccessToOwn')
            ->will($this->onConsecutiveCalls(true, true, false, false));

        $oAccessHandler = new AccessHandler(
            $this->getWordpress(),
            $oConfig,
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $aObjectUserGroups = [
            'postType' => [
                2 => [
                    0 => $this->getUserGroup(0)
                ],
                3 => [],
                4 => [10 => $this->getUserGroup(10)]
            ]
        ];
        self::setValue($oAccessHandler, '_aObjectUserGroups', $aObjectUserGroups);

        $aUserUserGroups = [0 => $this->getUserGroup(0)];
        self::setValue($oAccessHandler, '_aUserGroupsForUser', $aUserUserGroups);

        self::assertTrue($oAccessHandler->checkObjectAccess('postType', 1));
        self::assertTrue($oAccessHandler->checkObjectAccess('postType', 2));
        self::assertTrue($oAccessHandler->checkObjectAccess('postType', 3));
        self::assertFalse($oAccessHandler->checkObjectAccess('postType', 4));

        self::assertAttributeEquals(
            [
                'postType' => [
                    1 => true,
                    2 => true,
                    3 => true,
                    4 => false
                ]
            ],
            '_aObjectAccess',
            $oAccessHandler
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getExcludedTerms()
     */
    public function testGetExcludedTerms()
    {
        $oAccessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $oAccessHandler->getExcludedTerms());

        $oAccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        $aUserGroups = [
            0 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [1 => 1, 2 => 2, 5 => 5]),
            1 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [3 => 3, 2=> 2, 4 => 4])
        ];

        self::setValue($oAccessHandler, '_aUserGroups', $aUserGroups);

        $aUserGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [1 => 1, 3 => 3]),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [5 => 5, 3 => 3])
        ];

        self::setValue($oAccessHandler, '_aUserGroupsForUser', $aUserGroupsForUser);

        self::assertEquals([2 => 2, 4 => 4], $oAccessHandler->getExcludedTerms());
        self::assertAttributeEquals([2 => 2, 4 => 4], '_aExcludedTerms', $oAccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::getExcludedPosts()
     */
    public function testGetExcludedPosts()
    {
        $oAccessHandler = new AccessHandler(
            $this->getWordpress(['manage_user_groups']),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertEquals([], $oAccessHandler->getExcludedPosts());

        $oAccessHandler = new AccessHandler(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUtil(),
            $this->getUserGroupFactory()
        );


        $aUserGroups = [
            0 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [1 => 1, 2 => 2, 5 => 5]),
            1 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [3 => 3, 2=> 2, 4 => 4])
        ];

        self::setValue($oAccessHandler, '_aUserGroups', $aUserGroups);

        $aUserGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [1 => 1, 3 => 3]),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [5 => 5, 3 => 3])
        ];

        self::setValue($oAccessHandler, '_aUserGroupsForUser', $aUserGroupsForUser);

        self::assertEquals([2 => 2, 4 => 4], $oAccessHandler->getExcludedPosts());
        self::assertAttributeEquals([2 => 2, 4 => 4], '_aExcludedPosts', $oAccessHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\AccessHandler\AccessHandler::userIsAdmin()
     */
    public function testUserIsAdmin()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser();
            }));

        $oAccessHandler = new AccessHandler(
            $oWordpress,
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $oObjectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertFalse($oAccessHandler->userIsAdmin(1));
        self::assertTrue($oAccessHandler->userIsAdmin(1));

        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->never())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser(['administrator' => 1]);
            }));

        $oAccessHandler = new AccessHandler(
            $oWordpress,
            $this->getConfig(),
            $this->getCache(),
            $this->getDatabase(),
            $oObjectHandler,
            $this->getUtil(),
            $this->getUserGroupFactory()
        );

        self::assertTrue($oAccessHandler->userIsAdmin(1));
    }
}
