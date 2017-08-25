<?php
/**
 * AdminOutputControllerTraitTest.php
 *
 * The AdminOutputControllerTraitTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller\Frontend;

use UserAccessManager\Controller\Frontend\AdminOutputControllerTrait;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class AdminOutputControllerTraitTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\AdminOutputControllerTrait
 */
class AdminOutputControllerTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AdminOutputControllerTrait
     */
    private function getStub()
    {
        return $this->getMockForTrait(AdminOutputControllerTrait::class);
    }

    /**
     * @group  unit
     * @covers ::adminOutput()
     */
    public function testAdminOutput()
    {
        $wordpress = $this->getWordpress();

        /**
         * @var \WP_User|\stdClass $adminUser
         */
        $adminUser = $this->getMockBuilder('\WP_User')->getMock();
        $adminUser->ID = 1;

        /**
         * @var \WP_User|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = 2;

        $wordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->onConsecutiveCalls(
                $user,
                $adminUser,
                $adminUser
            ));

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(6))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(5))
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

        $mainConfig->expects($this->exactly(4))
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('hintText'));

        $util = $this->getUtil();

        $util->expects($this->once())
            ->method('endsWith')
            ->withConsecutive(
                ['text hintText', 'hintText']
            )
            ->will($this->onConsecutiveCalls(true));

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(3))
            ->method('userIsAdmin')
            ->withConsecutive([2], [1], [1])
            ->will($this->returnCallback(function ($id) {
                return ($id === 1);
            }));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('getUserGroupsForObject')
            ->withConsecutive(
                ['objectType', 'objectId'],
                ['secondObjectType', 'secondObjectId']
            )
            ->will($this->onConsecutiveCalls(
                [],
                [$this->getUserGroup(1)]
            ));

        $stub = $this->getStub();
        self::setValue($stub, 'wordpress', $wordpress);
        self::setValue($stub, 'wordpressConfig', $wordpressConfig);
        self::setValue($stub, 'mainConfig', $mainConfig);
        self::setValue($stub, 'util', $util);
        self::setValue($stub, 'userHandler', $userHandler);
        self::setValue($stub, 'accessHandler', $accessHandler);

        self::assertEquals('', $stub->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $stub->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $stub->adminOutput('objectType', 'objectId'));
        self::assertEquals('', $stub->adminOutput('objectType', 'objectId'));
        self::assertEquals('hintText', $stub->adminOutput('secondObjectType', 'secondObjectId'));
        self::assertEquals('', $stub->adminOutput(
            'secondObjectType',
            'secondObjectId',
            'text hintText'
        ));
    }
}
