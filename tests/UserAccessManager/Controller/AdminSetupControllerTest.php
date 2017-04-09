<?php
/**
 * AdminSetupControllerTest.php
 *
 * The AdminSetupControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class AdminSetupControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminSetupControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::__construct()
     */
    public function testCanCreateInstance()
    {
        $adminSetupController = new AdminSetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminSetupController', $adminSetupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(true, false));

        $adminSetupController = new AdminSetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        self::assertTrue($adminSetupController->isDatabaseUpdateNecessary());
        self::assertFalse($adminSetupController->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::showNetworkUpdate()
     */
    public function testShowNetworkUpdate()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, false, true));

        $adminSetupController = new AdminSetupController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertFalse($adminSetupController->showNetworkUpdate());

        define('MULTISITE', true);
        self::assertFalse($adminSetupController->showNetworkUpdate());

        define('WP_ALLOW_MULTISITE', true);
        self::assertFalse($adminSetupController->showNetworkUpdate());

        self::assertTrue($adminSetupController->showNetworkUpdate());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::updateDatabaseAction()
     */
    public function testUpdateDatabaseAction()
    {
        $_GET[AdminSetupController::SETUP_UPDATE_NONCE.'Nonce'] = 'updateNonce';

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(5))
            ->method('verifyNonce')
            ->with('updateNonce')
            ->will($this->returnValue(true));

        $wordpress->expects($this->exactly(6))
            ->method('switchToBlog')
            ->withConsecutive([1], [1], [1], [2], [3], [1]);

        $setupHandler = $this->getSetupHandler();

        $setupHandler->expects($this->exactly(5))
            ->method('update');

        $setupHandler->expects($this->exactly(3))
            ->method('getBlogIds')
            ->will($this->onConsecutiveCalls([], [1], [1, 2, 3]));

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $adminSetupController = new AdminSetupController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $database,
            $setupHandler
        );

        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(null, 'updateMessage', $adminSetupController);

        $_GET['uam_update_db'] = AdminSetupController::UPDATE_BLOG;
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'updateMessage', $adminSetupController);

        $_GET['uam_update_db'] = AdminSetupController::UPDATE_NETWORK;
        self::setValue($adminSetupController, 'updateMessage', null);
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'updateMessage', $adminSetupController);

        self::setValue($adminSetupController, 'updateMessage', null);
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'updateMessage', $adminSetupController);

        self::setValue($adminSetupController, 'updateMessage', null);
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'updateMessage', $adminSetupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::resetUamAction()
     */
    public function testResetUamAction()
    {
        $_GET[AdminSetupController::SETUP_RESET_NONCE.'Nonce'] = 'resetNonce';
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('verifyNonce')
            ->with('resetNonce')
            ->will($this->returnValue(true));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->once())
            ->method('uninstall');
        $setupHandler->expects($this->once())
            ->method('install');

        $adminSetupController = new AdminSetupController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        $_GET['uam_reset'] = 'something';
        $adminSetupController->resetUamAction();
        self::assertAttributeEquals(null, 'updateMessage', $adminSetupController);

        $_GET['uam_reset'] = 'reset';
        $adminSetupController->resetUamAction();
        self::assertAttributeEquals(TXT_UAM_UAM_RESET_SUCCESS, 'updateMessage', $adminSetupController);
    }
}
