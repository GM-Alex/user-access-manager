<?php
/**
 * FrontendAdminOutputControllerTraitTest.php
 *
 * The FrontendAdminOutputControllerTraitTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller;

use UserAccessManager\Controller\FrontendAdminOutputControllerTrait;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class FrontendAdminOutputControllerTraitTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\FrontendAdminOutputControllerTrait
 */
class FrontendAdminOutputControllerTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FrontendAdminOutputControllerTrait
     */
    private function getStub()
    {
        return $this->getMockForTrait(FrontendAdminOutputControllerTrait::class);
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

        $config = $this->getMainConfig();

        $config->expects($this->exactly(6))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false));

        $config->expects($this->exactly(5))
            ->method('blogAdminHint')
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

        $config->expects($this->exactly(4))
            ->method('getBlogAdminHintText')
            ->will($this->returnValue('hintText'));

        $util = $this->getUtil();

        $util->expects($this->once())
            ->method('endsWith')
            ->withConsecutive(
                ['text hintText', 'hintText']
            )
            ->will($this->onConsecutiveCalls(true));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(3))
            ->method('userIsAdmin')
            ->withConsecutive([2], [1], [1])
            ->will($this->returnCallback(function ($id) {
                return ($id === 1);
            }));

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
        self::setValue($stub, 'config', $config);
        self::setValue($stub, 'util', $util);
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
