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
 * @version   SVN: $Id$
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
        $AdminSetupController = new AdminSetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminSetupController', $AdminSetupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $SetupHandler = $this->getSetupHandler();
        $SetupHandler->expects($this->exactly(2))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(true, false));

        $AdminSetupController = new AdminSetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $SetupHandler
        );

        self::assertTrue($AdminSetupController->isDatabaseUpdateNecessary());
        self::assertFalse($AdminSetupController->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::showNetworkUpdate()
     */
    public function testShowNetworkUpdate()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(4))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, false, true));

        $AdminSetupController = new AdminSetupController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertFalse($AdminSetupController->showNetworkUpdate());

        define('MULTISITE', true);
        self::assertFalse($AdminSetupController->showNetworkUpdate());

        define('WP_ALLOW_MULTISITE', true);
        self::assertFalse($AdminSetupController->showNetworkUpdate());

        self::assertTrue($AdminSetupController->showNetworkUpdate());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::updateDatabaseAction()
     */
    public function testUpdateDatabaseAction()
    {
        $_GET[AdminSetupController::SETUP_UPDATE_NONCE.'Nonce'] = 'updateNonce';

        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(5))
            ->method('verifyNonce')
            ->with('updateNonce')
            ->will($this->returnValue(true));

        $Wordpress->expects($this->exactly(6))
            ->method('switchToBlog')
            ->withConsecutive([1], [1], [1], [2], [3], [1]);

        $SetupHandler = $this->getSetupHandler();

        $SetupHandler->expects($this->exactly(5))
            ->method('update');

        $SetupHandler->expects($this->exactly(3))
            ->method('getBlogIds')
            ->will($this->onConsecutiveCalls([], [1], [1, 2, 3]));

        $Database = $this->getDatabase();

        $Database->expects($this->exactly(2))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $AdminSetupController = new AdminSetupController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $Database,
            $SetupHandler
        );

        $AdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(null, 'sUpdateMessage', $AdminSetupController);

        $_GET['uam_update_db'] = AdminSetupController::UPDATE_BLOG;
        $AdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'sUpdateMessage', $AdminSetupController);

        $_GET['uam_update_db'] = AdminSetupController::UPDATE_NETWORK;
        self::setValue($AdminSetupController, 'sUpdateMessage', null);
        $AdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'sUpdateMessage', $AdminSetupController);

        self::setValue($AdminSetupController, 'sUpdateMessage', null);
        $AdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'sUpdateMessage', $AdminSetupController);

        self::setValue($AdminSetupController, 'sUpdateMessage', null);
        $AdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, 'sUpdateMessage', $AdminSetupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::resetUamAction()
     */
    public function testResetUamAction()
    {
        $_GET[AdminSetupController::SETUP_RESET_NONCE.'Nonce'] = 'resetNonce';
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(2))
            ->method('verifyNonce')
            ->with('resetNonce')
            ->will($this->returnValue(true));

        $SetupHandler = $this->getSetupHandler();
        $SetupHandler->expects($this->once())
            ->method('uninstall');
        $SetupHandler->expects($this->once())
            ->method('install');

        $AdminSetupController = new AdminSetupController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getDatabase(),
            $SetupHandler
        );

        $_GET['uam_reset'] = 'something';
        $AdminSetupController->resetUamAction();
        self::assertAttributeEquals(null, 'sUpdateMessage', $AdminSetupController);

        $_GET['uam_reset'] = 'reset';
        $AdminSetupController->resetUamAction();
        self::assertAttributeEquals(TXT_UAM_UAM_RESET_SUCCESS, 'sUpdateMessage', $AdminSetupController);
    }
}
