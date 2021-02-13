<?php
/**
 * DynamicGroupsControllerTest.php
 *
 * The DynamicGroupsControllerTest class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Controller\Backend;

use UserAccessManager\Controller\Backend\DynamicGroupsController;
use UserAccessManager\User\UserHandler;
use WP_Roles;

/**
 * Class DynamicGroupsControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\DynamicGroupsController
 */
class DynamicGroupsControllerTest extends ObjectControllerTestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $termObjectController = new DynamicGroupsController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertInstanceOf(DynamicGroupsController::class, $termObjectController);
    }

    /**
     * @group  unit
     * @covers ::getDynamicGroupsForAjax()
     */
    public function testGetDynamicGroupsForAjax()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(2))
            ->method('callExit');

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getUsers')
            ->with([
                'search' => '*Sea*',
                'fields' => ['ID', 'display_name', 'user_login', 'user_email']
            ])
            ->will($this->returnValue([
                $this->getUser(1, 'firstUser', 'firstUserLogin'),
                $this->getUser(2, 'secondUser', 'secondUserLogin')
            ]));

        $roles = $this->getMockBuilder(WP_Roles::class)->allowMockingUnknownTypes()->getMock();
        $roles->roles = [
            'admin' => ['name' => 'Administrator'],
            'editor' => ['name' => 'Editor'],
            'search' => ['name' => 'Search']
        ];

        $wordpress->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue($roles));

        $_GET['q'] = 'firstSearch, Sea';

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->with(UserHandler::MANAGE_USER_GROUPS_CAPABILITY)
            ->will($this->onConsecutiveCalls(true, false));

        $objectController = new DynamicGroupsController(
            $php,
            $wordpress,
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getExtendedObjectHandler(),
            $userHandler,
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $objectController->getDynamicGroupsForAjax();
        $objectController->getDynamicGroupsForAjax();

        self::expectOutputString(
            '['
            . '{"id":1,"name":"User|user-access-manager: firstUser (firstUserLogin)","type":"user"},'
            . '{"id":2,"name":"User|user-access-manager: secondUser (secondUserLogin)","type":"user"},'
            . '{"id":"search","name":"Role|user-access-manager: Search","type":"role"}'
            . '][]'
        );
    }
}
