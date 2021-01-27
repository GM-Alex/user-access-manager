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

namespace UserAccessManager\Tests\Unit\Controller\Frontend;

use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use UserAccessManager\Controller\Frontend\AdminOutputControllerTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_User;

/**
 * Class AdminOutputControllerTraitTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\AdminOutputControllerTrait
 */
class AdminOutputControllerTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return MockObject|AdminOutputControllerTrait
     */
    private function getStub()
    {
        return $this->getMockForTrait(AdminOutputControllerTrait::class);
    }

    /**
     * @group  unit
     * @covers ::adminOutput()
     * @covers ::showAdminHint()
     * @throws UserGroupTypeException
     */
    public function testAdminOutput()
    {
        $wordpress = $this->getWordpress();

        /**
         * @var WP_User|stdClass $adminUser
         */
        $adminUser = $this->getMockBuilder('\WP_User')->getMock();
        $adminUser->ID = 1;

        /**
         * @var WP_User|stdClass $user
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

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroupHandler->expects($this->exactly(2))
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

        $stub->expects($this->any())
            ->method('getWordpress')
            ->will($this->returnValue($wordpress));

        $stub->expects($this->any())
            ->method('getWordpressConfig')
            ->will($this->returnValue($wordpressConfig));

        $stub->expects($this->any())
            ->method('getMainConfig')
            ->will($this->returnValue($mainConfig));

        $stub->expects($this->any())
            ->method('getUtil')
            ->will($this->returnValue($util));

        $stub->expects($this->any())
            ->method('getUserHandler')
            ->will($this->returnValue($userHandler));

        $stub->expects($this->any())
            ->method('getUserGroupHandler')
            ->will($this->returnValue($userGroupHandler));

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
