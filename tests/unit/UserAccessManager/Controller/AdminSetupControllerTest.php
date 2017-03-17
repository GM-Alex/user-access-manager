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

/**
 * Class AdminSetupControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminSetupControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::__construct()
     */
    public function testCanCreateInstance()
    {
        $oAdminSetupController = new AdminSetupController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminSetupController', $oAdminSetupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $oSetupHandler = $this->getSetupHandler();
        $oSetupHandler->expects($this->exactly(2))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(true, false));

        $oAdminSetupController = new AdminSetupController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $oSetupHandler
        );

        self::assertTrue($oAdminSetupController->isDatabaseUpdateNecessary());
        self::assertFalse($oAdminSetupController->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::showNetworkUpdate()
     */
    public function testShowNetworkUpdate()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(4))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, false, true));

        $oAdminSetupController = new AdminSetupController(
            $oWrapper,
            $this->getConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertFalse($oAdminSetupController->showNetworkUpdate());

        define('MULTISITE', true);
        self::assertFalse($oAdminSetupController->showNetworkUpdate());

        define('WP_ALLOW_MULTISITE', true);
        self::assertFalse($oAdminSetupController->showNetworkUpdate());

        self::assertTrue($oAdminSetupController->showNetworkUpdate());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::updateDatabaseAction()
     */
    public function testUpdateDatabaseAction()
    {
        $_GET[AdminSetupController::SETUP_UPDATE_NONCE.'Nonce'] = 'updateNonce';
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(3))
            ->method('verifyNonce')
            ->with('updateNonce')
            ->will($this->returnValue(true));

        $oWrapper->expects($this->exactly(4))
            ->method('switchToBlog')
            ->withConsecutive([1], [2], [3], [1]);

        $oSetupHandler = $this->getSetupHandler();
        $oSetupHandler->expects($this->exactly(4))
            ->method('update');
        $oSetupHandler->expects($this->once())
            ->method('getBlogIds')
            ->will($this->returnValue([1, 2, 3]));

        $oDatabase = $this->getDatabase();
        $oDatabase->expects($this->once())
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $oAdminSetupController = new AdminSetupController(
            $oWrapper,
            $this->getConfig(),
            $oDatabase,
            $oSetupHandler
        );

        $oAdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(null, '_sUpdateMessage', $oAdminSetupController);

        $_GET['uam_update_db'] = AdminSetupController::UPDATE_BLOG;
        $oAdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, '_sUpdateMessage', $oAdminSetupController);

        $_GET['uam_update_db'] = AdminSetupController::UPDATE_NETWORK;
        self::setValue($oAdminSetupController, '_sUpdateMessage', null);
        $oAdminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS, '_sUpdateMessage', $oAdminSetupController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSetupController::resetUamAction()
     */
    public function testResetUamAction()
    {
        $_GET[AdminSetupController::SETUP_RESET_NONCE.'Nonce'] = 'resetNonce';
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(2))
            ->method('verifyNonce')
            ->with('resetNonce')
            ->will($this->returnValue(true));

        $oSetupHandler = $this->getSetupHandler();
        $oSetupHandler->expects($this->once())
            ->method('uninstall');
        $oSetupHandler->expects($this->once())
            ->method('install');

        $oAdminSetupController = new AdminSetupController(
            $oWrapper,
            $this->getConfig(),
            $this->getDatabase(),
            $oSetupHandler
        );

        $_GET['uam_reset'] = 'something';
        $oAdminSetupController->resetUamAction();
        self::assertAttributeEquals(null, '_sUpdateMessage', $oAdminSetupController);

        $_GET['uam_reset'] = 'reset';
        $oAdminSetupController->resetUamAction();
        self::assertAttributeEquals(TXT_UAM_UAM_RESET_SUCCESS, '_sUpdateMessage', $oAdminSetupController);
    }
}
