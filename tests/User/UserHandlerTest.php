<?php
/**
 * UserHandlerTest.php
 *
 * The UserHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\User;

use UserAccessManager\Tests\HandlerTestCase;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\User\UserHandler;

/**
 * Class UserHandlerTest
 *
 * @package UserAccessManager\Tests\UserHandler
 * @coversDefaultClass \UserAccessManager\User\UserHandler
 */
class UserHandlerTest extends HandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $userHandler = new UserHandler(
            $this->getWordpressWithUser(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(UserHandler::class, $userHandler);
    }
    
    /**
     * @group  unit
     * @covers ::isIpInRange()
     * @covers ::calculateIp()
     * @covers ::getCalculatedRange()
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

        $userHandler = new UserHandler(
            $this->getWordpressWithUser(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertFalse(self::callMethod($userHandler, 'calculateIp', ['0.0.0']));
        self::assertFalse(self::callMethod($userHandler, 'calculateIp', ['255.255.255255']));
        self::assertEquals('1', self::callMethod($userHandler, 'calculateIp', ['0.0.0.1']));
        self::assertEquals('100000000', self::callMethod($userHandler, 'calculateIp', ['0.0.1.0']));
        self::assertFalse(self::callMethod($userHandler, 'calculateIp', ['0:0:0:0:0:FFFF:0000']));
        self::assertEquals(
            '0000000000000000000000000000000000000000000000000000000000000000'
            .'0000000000000000111111111111111100000000000000000000000100000000',
            self::callMethod($userHandler, 'calculateIp', ['0:0:0:0:0:FFFF:0000:0100'])
        );

        self::assertFalse($userHandler->isIpInRange('0.0.0', ['0.0.0.0']));
        self::assertFalse($userHandler->isIpInRange('1.1.1', $ranges));
        self::assertFalse($userHandler->isIpInRange('0.0.0.0', $ranges));
        self::assertTrue($userHandler->isIpInRange('1.1.1.1', $ranges));
        self::assertTrue($userHandler->isIpInRange('1.1.1.100', $ranges));
        self::assertTrue($userHandler->isIpInRange('1.1.2.1', $ranges));
        self::assertFalse($userHandler->isIpInRange('1.1.2.2', $ranges));
        self::assertTrue($userHandler->isIpInRange('2.2.2.2', $ranges));
        self::assertFalse($userHandler->isIpInRange('3.2.2.2', $ranges));
        self::assertFalse($userHandler->isIpInRange('5.5.5.5', $ranges));
        self::assertFalse($userHandler->isIpInRange('8.8.8.8', $ranges));

        self::assertTrue($userHandler->isIpInRange('0:0:0:0:0:ffff:101:101', $ranges));
        self::assertTrue($userHandler->isIpInRange('0:0:0:0:0:ffff:101:164', $ranges));
        self::assertTrue($userHandler->isIpInRange('0:0:0:0:0:ffff:101:201', $ranges));
        self::assertFalse($userHandler->isIpInRange('0:0:0:0:0:ffff:101:202', $ranges));
        self::assertTrue($userHandler->isIpInRange('0:0:0:0:0:ffff:202:202', $ranges));
        self::assertFalse($userHandler->isIpInRange('0:0:0:0:0:ffff:302:202', $ranges));
        self::assertFalse($userHandler->isIpInRange('0:0:0:0:0:ffff:505:505', $ranges));
        self::assertFalse($userHandler->isIpInRange('0:0:0:0:0:ffff:808:808', $ranges));
    }
    
    /**
     * @group  unit
     * @covers ::checkUserAccess()
     * @covers ::getUserRole()
     */
    public function testCheckUserAccess()
    {
        $wordpress = $this->getWordpressWithUser(null, null);
        $wordpress->expects($this->exactly(3))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(true, false, false));

        $config = $this->getMainConfig();
        $config->expects($this->any())
            ->method('getFullAccessRole')
            ->will($this->returnValue('administrator'));

        $userHandler = new UserHandler(
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess('user_cap'));
        self::assertFalse($userHandler->checkUserAccess());

        $noneUser = $this->getUser(null, 0);
        $unkownRoleUser = $this->getUser(['unkown' => true], 0);
        $multiRoleUser = $this->getUser(['subscriber' => true, 'contributor' => true, 'administrator' => true], 0);
        $adminUser = $this->getUser(['administrator' => true], 0);
        $editorUser = $this->getUser(['editor' => true], 0);
        $authorUser = $this->getUser(['author' => true], 0);
        $contributorUser = $this->getUser(['contributor' => true], 0);
        $subscriberUser = $this->getUser(['subscriber' => true], 0);

        $userReturn = [$adminUser, $editorUser, $authorUser, $contributorUser, $subscriberUser, $noneUser];

        $wordpress = $this->getWordpress();
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

        $userHandler = new UserHandler(
            $wordpress,
            $config,
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertFalse($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());

        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());

        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());

        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());

        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertFalse($userHandler->checkUserAccess());

        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
        self::assertTrue($userHandler->checkUserAccess());
    }

    /**
     * @group  unit
     * @covers ::userIsAdmin()
     * @covers ::getUserRole()
     */
    public function testUserIsAdmin()
    {
        $wordpress = $this->getWordpressWithUser();
        $wordpress->expects($this->once())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(2))
            ->method('getUser')
            ->will($this->onConsecutiveCalls(false, $this->getUser(['administrator' => 1])));

        $userHandler = new UserHandler(
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $objectHandler
        );

        self::assertFalse($userHandler->userIsAdmin(1));
        self::assertTrue($userHandler->userIsAdmin(1));

        $wordpress = $this->getWordpressWithUser();
        $wordpress->expects($this->exactly(2))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, true));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser();
            }));

        $userHandler = new UserHandler(
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $objectHandler
        );

        self::assertFalse($userHandler->userIsAdmin(1));
        self::assertTrue($userHandler->userIsAdmin(1));

        $wordpress = $this->getWordpressWithUser();
        $wordpress->expects($this->never())
            ->method('isSuperAdmin')
            ->will($this->returnValue(false));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function () {
                return $this->getUser(['administrator' => 1]);
            }));

        $userHandler = new UserHandler(
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $objectHandler
        );

        self::assertTrue($userHandler->userIsAdmin(1));
    }
}
