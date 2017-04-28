<?php
/**
 * AdminSetupController.php
 *
 * The AdminSetupController class file.
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

use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AdminSetupController
 *
 * @package UserAccessManager\Controller
 */
class AdminSetupController extends Controller
{
    const SETUP_UPDATE_NONCE = 'uamSetupUpdate';
    const SETUP_REVERT_NONCE = 'uamSetupRevert';
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
     * AdminSetupController constructor.
     *
     * @param Php          $php
     * @param Wordpress    $wordpress
     * @param Config       $config
     * @param Database     $database
     * @param SetupHandler $setupHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Config $config,
        Database $database,
        SetupHandler $setupHandler
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->database = $database;
        $this->setupHandler = $setupHandler;
    }

    /**
     * Returns if a database update is necessary.
     *
     * @return bool
     */
    public function isDatabaseUpdateNecessary()
    {
        return $this->setupHandler->isDatabaseUpdateNecessary();
    }

    /**
     * Checks if a network update is nessary.
     *
     * @return bool
     */
    public function showNetworkUpdate()
    {
        return $this->wordpress->isSuperAdmin() === true
            && defined('MULTISITE') === true && MULTISITE === true
            && defined('WP_ALLOW_MULTISITE') === true && WP_ALLOW_MULTISITE === true;
    }

    /**
     * Returns the existing backups
     *
     * @return array
     */
    public function getBackups()
    {
        return $this->setupHandler->getBackups();
    }

    /**
     * The database update action.
     */
    public function updateDatabaseAction()
    {
        $this->verifyNonce(self::SETUP_UPDATE_NONCE);
        $update = $this->getRequestParameter('uam_update_db');
        $backup = (bool)$this->getRequestParameter('uam_backup_db', false);

        if ($update === self::UPDATE_BLOG || $update === self::UPDATE_NETWORK) {
            if ($update === self::UPDATE_NETWORK) {
                $blogIds = $this->setupHandler->getBlogIds();

                if (count($blogIds) > 0) {
                    $currentBlogId = $this->database->getCurrentBlogId();

                    foreach ($blogIds as $blogId) {
                        $this->wordpress->switchToBlog($blogId);

                        if ($backup === true) {
                            $this->setupHandler->backupDatabase();
                        }

                        $this->setupHandler->update();
                    }

                    $this->wordpress->switchToBlog($currentBlogId);
                }
            } else {
                if ($backup === true) {
                    $this->setupHandler->backupDatabase();
                }

                $this->setupHandler->update();
            }

            $this->setUpdateMessage(TXT_UAM_UAM_DB_UPDATE_SUCCESS);
        }
    }

    /**
     * Reverts the database to the given version.
     */
    public function revertDatabaseAction()
    {
        $this->verifyNonce(self::SETUP_REVERT_NONCE);
        $version = $this->getRequestParameter('uam_revert_database');

        if ($this->setupHandler->revertDatabase($version) === true) {
            $this->setUpdateMessage(TXT_UAM_REVERT_DATABASE_SUCCESS);
        }
    }

    /**
     * Deletes the given database backup.
     */
    public function deleteDatabaseBackupAction()
    {
        $this->verifyNonce(self::SETUP_DELETE_BACKUP_NONCE);
        $version = $this->getRequestParameter('uam_delete_backup');

        if ($this->setupHandler->deleteBackup($version) === true) {
            $this->setUpdateMessage(TXT_UAM_DELETE_DATABASE_BACKUP_SUCCESS);
        }
    }

    /**
     * The reset action.
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
