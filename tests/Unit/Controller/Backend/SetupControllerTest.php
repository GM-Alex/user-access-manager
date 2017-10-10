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
namespace UserAccessManager\Tests\Unit\Controller\Backend;

use UserAccessManager\Controller\Backend\SetupController;
use UserAccessManager\Setup\Database\DatabaseHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserAccessManager;

/**
 * Class SetupControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
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
        $setupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertInstanceOf(SetupController::class, $setupController);
    }

    /**
     * @group  unit
     * @covers ::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $databaseHandler->expects($this->exactly(2))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(true, false));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('getDatabaseHandler')
            ->will($this->returnValue($databaseHandler));

        $setupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        self::assertTrue($setupController->isDatabaseUpdateNecessary());
        self::assertFalse($setupController->isDatabaseUpdateNecessary());
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

        $setupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $this->getSetupHandler()
        );

        self::assertFalse($setupController->showNetworkUpdate());

        define('MULTISITE', true);
        self::assertFalse($setupController->showNetworkUpdate());

        define('WP_ALLOW_MULTISITE', true);
        self::assertFalse($setupController->showNetworkUpdate());

        self::assertTrue($setupController->showNetworkUpdate());
    }

    /**
     * @group  unit
     * @covers ::getBackups()
     */
    public function testGetBackups()
    {
        $databaseHandler = $this->getDatabaseHandler();
        $databaseHandler->expects($this->once())
            ->method('getBackups')
            ->will($this->returnValue([1, 123, 4]));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->once())
            ->method('getDatabaseHandler')
            ->will($this->returnValue($databaseHandler));

        $setupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        self::assertEquals([1, 123, 4], $setupController->getBackups());
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

        $wordpress->expects($this->exactly(9))
            ->method('switchToBlog')
            ->withConsecutive([1], [1], [1], [1], [1], [1], [2], [3], [1]);

        $databaseHandler = $this->getDatabaseHandler();

        $databaseHandler->expects($this->exactly(2))
            ->method('backupDatabase');

        $setupHandler = $this->getSetupHandler();

        $setupHandler->expects($this->exactly(2))
            ->method('getDatabaseHandler')
            ->will($this->returnValue($databaseHandler));

        $setupHandler->expects($this->exactly(5))
            ->method('update');

        $setupHandler->expects($this->exactly(3))
            ->method('getBlogIds')
            ->will($this->onConsecutiveCalls([], [1], [1, 2, 3]));

        $database = $this->getDatabase();

        $database->expects($this->exactly(8))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $setupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $database,
            $setupHandler
        );

        $setupController->updateDatabaseAction();
        self::assertAttributeEquals(null, 'updateMessage', $setupController);

        $_GET['uam_backup_db'] = true;
        $_GET['uam_update_db'] = SetupController::UPDATE_BLOG;
        $setupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $setupController);

        $_GET['uam_update_db'] = SetupController::UPDATE_NETWORK;
        self::setValue($setupController, 'updateMessage', null);
        $setupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $setupController);

        self::setValue($setupController, 'updateMessage', null);
        $setupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $setupController);

        unset($_GET['uam_backup_db']);
        self::setValue($setupController, 'updateMessage', null);
        $setupController->updateDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_UAM_DB_UPDATE_SUCCESS, 'updateMessage', $setupController);
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

        $databaseHandler = $this->getDatabaseHandler();
        $databaseHandler->expects($this->exactly(2))
            ->method('revertDatabase')
            ->withConsecutive(['1.2'], ['1.3'])
            ->will($this->onConsecutiveCalls(false, true));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('getDatabaseHandler')
            ->will($this->returnValue($databaseHandler));

        $setupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        $_GET['uam_revert_database'] = '1.2';
        $setupController->revertDatabaseAction();
        self::assertAttributeEquals(null, 'updateMessage', $setupController);

        $_GET['uam_revert_database'] = '1.3';
        $setupController->revertDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_REVERT_DATABASE_SUCCESS, 'updateMessage', $setupController);
    }

    /**
     * @group  unit
     * @covers ::isDatabaseBroken()
     */
    public function testIsDatabaseBroken()
    {
        $information = [
            DatabaseHandler::MISSING_TABLES => [],
            DatabaseHandler::MISSING_COLUMNS => [],
            DatabaseHandler::MODIFIED_COLUMNS => [],
            DatabaseHandler::EXTRA_COLUMNS => []
        ];

        $brokenInformation = $information;
        $brokenInformation[DatabaseHandler::MISSING_TABLES] = ['table'];

        $databaseHandler = $this->getDatabaseHandler();
        $databaseHandler->expects($this->exactly(2))
            ->method('getCorruptedDatabaseInformation')
            ->will($this->onConsecutiveCalls($information, $brokenInformation));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('getDatabaseHandler')
            ->will($this->returnValue($databaseHandler));

        $setupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        self::assertFalse($setupController->isDatabaseBroken());
        self::assertTrue($setupController->isDatabaseBroken());
    }

    /**
     * @group  unit
     * @covers ::repairDatabaseAction()
     */
    public function testRepairDatabaseAction()
    {
        $information = [
            DatabaseHandler::MISSING_TABLES => [],
            DatabaseHandler::MISSING_COLUMNS => [],
            DatabaseHandler::MODIFIED_COLUMNS => [],
            DatabaseHandler::EXTRA_COLUMNS => []
        ];

        $brokenInformation = $information;
        $brokenInformation[DatabaseHandler::MISSING_TABLES] = ['table'];

        $databaseHandler = $this->getDatabaseHandler();

        $databaseHandler->expects($this->exactly(3))
            ->method('getBackups')
            ->will($this->onConsecutiveCalls(
                [UserAccessManager::DB_VERSION => UserAccessManager::DB_VERSION],
                [UserAccessManager::DB_VERSION => UserAccessManager::DB_VERSION],
                []
            ));

        $databaseHandler->expects($this->once())
            ->method('backupDatabase');

        $databaseHandler->expects($this->exactly(3))
            ->method('repairDatabase')
            ->will($this->onConsecutiveCalls(false, false, true));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(3))
            ->method('getDatabaseHandler')
            ->will($this->returnValue($databaseHandler));

        $setupController = new SetupController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        $setupController->repairDatabaseAction();
        $setupController->repairDatabaseAction();
        self::assertAttributeEquals(null, 'updateMessage', $setupController);
        $setupController->repairDatabaseAction();
        self::assertAttributeEquals(TXT_UAM_REPAIR_DATABASE_SUCCESS, 'updateMessage', $setupController);
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

        $databaseHandler = $this->getDatabaseHandler();
        $databaseHandler->expects($this->exactly(2))
            ->method('deleteBackup')
            ->withConsecutive(['1.2'], ['1.3'])
            ->will($this->onConsecutiveCalls(false, true));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(2))
            ->method('getDatabaseHandler')
            ->will($this->returnValue($databaseHandler));

        $setupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        $_GET['uam_delete_backup'] = '1.2';
        $setupController->deleteDatabaseBackupAction();
        self::assertAttributeEquals(null, 'updateMessage', $setupController);

        $_GET['uam_delete_backup'] = '1.3';
        $setupController->deleteDatabaseBackupAction();
        self::assertAttributeEquals(TXT_UAM_DELETE_DATABASE_BACKUP_SUCCESS, 'updateMessage', $setupController);
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

        $setupController = new SetupController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $this->getDatabase(),
            $setupHandler
        );

        $_GET['uam_reset'] = 'something';
        $setupController->resetUamAction();
        self::assertAttributeEquals(null, 'updateMessage', $setupController);

        $_GET['uam_reset'] = 'reset';
        $setupController->resetUamAction();
        self::assertAttributeEquals(TXT_UAM_UAM_RESET_SUCCESS, 'updateMessage', $setupController);
    }
}
