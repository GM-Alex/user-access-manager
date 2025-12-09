<?php

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

class SetupController extends Controller
{
    public const SETUP_UPDATE_NONCE = 'uamSetupUpdate';
    public const SETUP_REVERT_NONCE = 'uamSetupRevert';
    public const SETUP_REPAIR_NONCE = 'uamSetupRepair';
    public const SETUP_DELETE_BACKUP_NONCE = 'uamSetupDeleteBackup';
    public const SETUP_RESET_NONCE = 'uamSetupReset';
    public const UPDATE_BLOG = 'blog';
    public const UPDATE_NETWORK = 'network';

    protected ?string $template = 'AdminSetup.php';

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        private Database $database,
        private SetupHandler $setupHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
    }

    /**
     * @throws MissingColumnsException
     */
    public function isDatabaseUpdateNecessary(): bool
    {
        return $this->setupHandler->getDatabaseHandler()->isDatabaseUpdateNecessary();
    }

    public function showNetworkUpdate(): bool
    {
        return $this->wordpress->isSuperAdmin() === true
            && defined('MULTISITE') === true && MULTISITE === true
            && defined('WP_ALLOW_MULTISITE') === true && WP_ALLOW_MULTISITE === true;
    }

    public function getBackups(): array
    {
        return $this->setupHandler->getDatabaseHandler()->getBackups();
    }

    public function updateDatabaseAction(): void
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

    public function revertDatabaseAction(): void
    {
        $this->verifyNonce(self::SETUP_REVERT_NONCE);
        $version = $this->getRequestParameter('uam_revert_database');

        if ($this->setupHandler->getDatabaseHandler()->revertDatabase($version) === true) {
            $this->setUpdateMessage(TXT_UAM_REVERT_DATABASE_SUCCESS);
        }
    }

    /**
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
     * @throws MissingColumnsException
     */
    public function repairDatabaseAction(): void
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

    public function deleteDatabaseBackupAction(): void
    {
        $this->verifyNonce(self::SETUP_DELETE_BACKUP_NONCE);
        $version = (string) $this->getRequestParameter('uam_delete_backup');

        if ($this->setupHandler->getDatabaseHandler()->deleteBackup($version) === true) {
            $this->setUpdateMessage(TXT_UAM_DELETE_DATABASE_BACKUP_SUCCESS);
        }
    }

    /**
     * @throws MissingColumnsException
     */
    public function resetUamAction(): void
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
