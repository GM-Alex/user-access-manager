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
 * @version   SVN: $Id$
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
    const SETUP_RESET_NONCE = 'uamSetupReset';
    const UPDATE_BLOG = 'blog';
    const UPDATE_NETWORK = 'network';

    /**
     * @var SetupHandler
     */
    protected $oSetupHandler;

    /**
     * @var Database
     */
    protected $oDatabase;

    /**
     * @var string
     */
    protected $sTemplate = 'AdminSetup.php';

    /**
     * AdminSetupController constructor.
     *
     * @param Php          $oPhp
     * @param Wordpress    $oWordpress
     * @param Config       $oConfig
     * @param Database     $oDatabase
     * @param SetupHandler $oSetupHandler
     */
    public function __construct(
        Php $oPhp,
        Wordpress $oWordpress,
        Config $oConfig,
        Database $oDatabase,
        SetupHandler $oSetupHandler
    ) {
        parent::__construct($oPhp, $oWordpress, $oConfig);
        $this->oDatabase = $oDatabase;
        $this->oSetupHandler = $oSetupHandler;
    }

    /**
     * Returns if a database update is necessary.
     *
     * @return bool
     */
    public function isDatabaseUpdateNecessary()
    {
        return $this->oSetupHandler->isDatabaseUpdateNecessary();
    }

    /**
     * Checks if a network update is nessary.
     *
     * @return bool
     */
    public function showNetworkUpdate()
    {
        return $this->oWordpress->isSuperAdmin() === true
            && defined('MULTISITE') === true && MULTISITE === true
            && defined('WP_ALLOW_MULTISITE') === true && WP_ALLOW_MULTISITE === true;
    }

    /**
     * The database update action.
     */
    public function updateDatabaseAction()
    {
        $this->verifyNonce(self::SETUP_UPDATE_NONCE);
        $sUpdate = $this->getRequestParameter('uam_update_db');

        if ($sUpdate === self::UPDATE_BLOG || $sUpdate === self::UPDATE_NETWORK) {
            if ($sUpdate === self::UPDATE_NETWORK) {
                $aBlogIds = $this->oSetupHandler->getBlogIds();

                if (count($aBlogIds) > 0) {
                    $iCurrentBlogId = $this->oDatabase->getCurrentBlogId();

                    foreach ($aBlogIds as $iBlogId) {
                        $this->oWordpress->switchToBlog($iBlogId);
                        $this->oSetupHandler->update();
                    }

                    $this->oWordpress->switchToBlog($iCurrentBlogId);
                }
            } else {
                $this->oSetupHandler->update();
            }

            $this->setUpdateMessage(TXT_UAM_UAM_DB_UPDATE_SUCSUCCESS);
        }
    }

    /**
     * The reset action.
     */
    public function resetUamAction()
    {
        $this->verifyNonce(self::SETUP_RESET_NONCE);
        $sReset = $this->getRequestParameter('uam_reset');

        if ($sReset === 'reset') {
            $this->oSetupHandler->uninstall();
            $this->oSetupHandler->install();
            $this->setUpdateMessage(TXT_UAM_UAM_RESET_SUCCESS);
        }
    }
}
