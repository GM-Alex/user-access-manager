<?php
/**
 * SetupControllerTest.php
 *
 * The SetupControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller\Backend;

use UserAccessManager\Controller\Backend\SetupController;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class SetupControllerTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\Backend\SetupController
 */
class SetupControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $adminSetupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertInstanceOf(SetupController::class, $adminSetupController);
    }

    /**
     * @group  unit
     * @covers ::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(true, false));

        $adminSetupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        self::assertTrue($adminSetupController->isDatabaseUpdateNecessary());
        self::assertFalse($adminSetupController->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers ::showNetworkUpdate()
     */
    public function testShowNetworkUpdate()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, false, true));

        $adminSetupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
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
     * @covers ::getBackups()
     */
    public function testGetBackups()
    {
        $setupHandler = $this->getSetupHandler();

        $setupHandler->expects($this->once())
            ->method('getBackups')
            ->will($this->returnValue([1, 123, 4]));

        $adminSetupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        self::assertEquals([1, 123, 4], $adminSetupController->getBackups());
    }

    /**
     * @group  unit
     * @covers ::updateDatabaseAction()
     */
    public function testUpdateDatabaseAction()
    {
        $_GET[SetupController::SETUP_UPDATE_NONCE.'Nonce'] = 'updateNonce';

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

        $setupHandler->expects($this->exactly(2))
            ->method('backupDatabase');

        $setupHandler->expects($this->exactly(3))
            ->method('getBlogIds')
            ->will($this->onConsecutiveCalls([], [1], [1, 2, 3]));

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $adminSetupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $database,
            $setupHandler
        );

        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(null, 'updateMessage', $adminSetupController);

        $_GET['uam_backup_db'] = true;
        $_GET['uam_update_db'] = SetupController::UPDATE_BLOG;
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $adminSetupController);

        $_GET['uam_update_db'] = SetupController::UPDATE_NETWORK;
        self::setValue($adminSetupController, 'updateMessage', null);
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $adminSetupController);

        self::setValue($adminSetupController, 'updateMessage', null);
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $adminSetupController);

        unset($_GET['uam_backup_db']);
        self::setValue($adminSetupController, 'updateMessage', null);
        $adminSetupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $adminSetupController);
    }

    /**
     * @group  unit
     * @covers ::revertDatabaseAction()
     */
    public function testRevertDatabaseAction()
    {
        $_GET[SetupController::SETUP_REVERT_NONCE.'Nonce'] = 'revertNonce';
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('verifyNonce')
            ->with('revertNonce')
            ->will($this->returnValue(true));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('revertDatabase')
            ->withConsecutive(['1.2'], ['1.3'])
            ->will($this->onConsecutiveCalls(false, true));

        $adminSetupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        $_GET['uam_revert_database'] = '1.2';
        $adminSetupController->revertDatabaseAction();
        self::assertAttributeEquals(null, 'updateMessage', $adminSetupController);

        $_GET['uam_revert_database'] = '1.3';
        $adminSetupController->revertDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_REVERT_DATABASE_SUCCESS, 'updateMessage', $adminSetupController);
    }

    /**
     * @group  unit
     * @covers ::deleteDatabaseBackupAction()
     */
    public function testDeleteDatabaseBackupAction()
    {
        $_GET[SetupController::SETUP_DELETE_BACKUP_NONCE.'Nonce'] = 'deleteBackupNonce';
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('verifyNonce')
            ->with('deleteBackupNonce')
            ->will($this->returnValue(true));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('deleteBackup')
            ->withConsecutive(['1.2'], ['1.3'])
            ->will($this->onConsecutiveCalls(false, true));

        $adminSetupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        $_GET['uam_delete_backup'] = '1.2';
        $adminSetupController->deleteDatabaseBackupAction();
        self::assertAttributeEquals(null, 'updateMessage', $adminSetupController);

        $_GET['uam_delete_backup'] = '1.3';
        $adminSetupController->deleteDatabaseBackupAction();
        self::assertAttributeEquals(TXT_UAM_DELETE_DATABASE_BACKUP_SUCCESS, 'updateMessage', $adminSetupController);
    }

    /**
     * @group  unit
     * @covers ::resetUamAction()
     */
    public function testResetUamAction()
    {
        $_GET[SetupController::SETUP_RESET_NONCE.'Nonce'] = 'resetNonce';
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('verifyNonce')
            ->with('resetNonce')
            ->will($this->returnValue(true));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->once())
            ->method('uninstall');
        $setupHandler->expects($this->once())
            ->method('install')
            ->with(true);

        $adminSetupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
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
