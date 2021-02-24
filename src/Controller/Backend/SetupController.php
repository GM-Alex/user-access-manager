<?php
/**
 * SetupController.php
 *
 * The SetupController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Database\Database;
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class SetupController
 *
 * @package UserAccessManager\Controller
 */
class SetupController extends Controller
{
    const SETUP_UPDATE_NONCE = 'uamSetupUpdate';
    const SETUP_REVERT_NONCE = 'uamSetupRevert';
    const SETUP_REPAIR_NONCE = 'uamSetupRepair';
    const SETUP_DELETE_BACKUP_NONCE = 'uamSetupDeleteBackup';
    const SETUP_RESET_NONCE = 'uamSetupReset';
    const UPDATE_BLOG = 'blog';
    const UPDATE_NETWORK = 'network';

    /**
     * @var string
     */
    protected $template = 'AdminSetup.php';

    /**
     * @var SetupHandler
     */
    private $setupHandler;

    /**
     * @var Database
     */
    private $database;

    /**
     * SetupController constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param Database $database
     * @param SetupHandler $setupHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        Database $database,
        SetupHandler $setupHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->database = $database;
        $this->setupHandler = $setupHandler;
    }

    /**
     * Returns if a database update is necessary.
     * @return bool
     */
    public function isDatabaseUpdateNecessary(): bool
    {
        return $this->setupHandler->getDatabaseHandler()->isDatabaseUpdateNecessary();
    }

    /**
     * Checks if a network update is nessary.
     * @return bool
     */
    public function showNetworkUpdate(): bool
    {
        return $this->wordpress->isSuperAdmin() === true
            && defined('MULTISITE') === true && MULTISITE === true
            && defined('WP_ALLOW_MULTISITE') === true && WP_ALLOW_MULTISITE === true;
    }

    /**
     * Returns the existing backups
     * @return array
     */
    public function getBackups(): array
    {
        return $this->setupHandler->getDatabaseHandler()->getBackups();
    }

    /**
     * The database update action.
     */
    public function updateDatabaseAction()
    {
        $success = true;
        $this->verifyNonce(self::SETUP_UPDATE_NONCE);
        $update = $this->getRequestParameter('uam_update_db');
        $backup = (bool) $this->getRequestParameter('uam_backup_db', false);

        if ($update === self::UPDATE_BLOG || $update === self::UPDATE_NETWORK) {
            $currentBlogId = $this->database->getCurrentBlogId();
            $blogIds = ($update === self::UPDATE_NETWORK) ? $this->setupHandler->getBlogIds() : [$currentBlogId];

            foreach ($blogIds as $blogId) {
                $this->wordpress->switchToBlog($blogId);

                if ($backup === true) {
                    $this->setupHandler->getDatabaseHandler()->backupDatabase();
                }

                $success = $success && $this->setupHandler->update();
                $this->wordpress->restoreCurrentBlog();
            }

            $message = $success === true ? TXT_UAM_UAM_DB_UPDATE_SUCCESS : TXT_UAM_UAM_DB_UPDATE_FAILURE;
            $this->setUpdateMessage($message);
        }
    }

    /**
     * Reverts the database to the given version.
     */
    public function revertDatabaseAction()
    {
        $this->verifyNonce(self::SETUP_REVERT_NONCE);
        $version = $this->getRequestParameter('uam_revert_database');

        if ($this->setupHandler->getDatabaseHandler()->revertDatabase($version) === true) {
            $this->setUpdateMessage(TXT_UAM_REVERT_DATABASE_SUCCESS);
        }
    }

    /**
     * Checks if the database is broken.
     * @return bool
     * @throws MissingColumnsException
     */
    public function isDatabaseBroken(): bool
    {
        $information = $this->setupHandler->getDatabaseHandler()->getCorruptedDatabaseInformation();

        $numberOfIssues = array_reduce(
            $information,
            function ($carry, $item) {
                $carry += count($item);
                return $carry;
            }
        );

        return $numberOfIssues > 0;
    }

    /**
     * Repairs the database.
     * @throws MissingColumnsException
     */
    public function repairDatabaseAction()
    {
        $this->verifyNonce(self::SETUP_REPAIR_NONCE);

        $databaseHandler = $this->setupHandler->getDatabaseHandler();
        $backups = $databaseHandler->getBackups();

        if (isset($backups[UserAccessManager::DB_VERSION]) === false) {
            $databaseHandler->backupDatabase();
        }

        if ($databaseHandler->repairDatabase() === true) {
            $this->setUpdateMessage(TXT_UAM_REPAIR_DATABASE_SUCCESS);
            $this->wordpress->updateOption('uam_db_version', UserAccessManager::DB_VERSION);
        }
    }

    /**
     * Deletes the given database backup.
     */
    public function deleteDatabaseBackupAction()
    {
        $this->verifyNonce(self::SETUP_DELETE_BACKUP_NONCE);
        $version = (string) $this->getRequestParameter('uam_delete_backup');

        if ($this->setupHandler->getDatabaseHandler()->deleteBackup($version) === true) {
            $this->setUpdateMessage(TXT_UAM_DELETE_DATABASE_BACKUP_SUCCESS);
        }
    }

    /**
     * The reset action.
     * @throws MissingColumnsException
     */
    public function resetUamAction()
    {
        $this->verifyNonce(self::SETUP_RESET_NONCE);
        $reset = $this->getRequestParameter('uam_reset');

        if ($reset === 'reset') {
            $this->setupHandler->uninstall();
            $this->setupHandler->install(true);
            $this->setUpdateMessage(TXT_UAM_UAM_RESET_SUCCESS);
        }
    }
}
