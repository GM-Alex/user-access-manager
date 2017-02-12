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

    /**
     * @var SetupHandler
     */
    protected $_oSetupHandler;

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var string
     */
    protected $_sTemplate = 'AdminSetup.php';

    /**
     * AdminSetupController constructor.
     *
     * @param Wordpress    $oWrapper
     * @param Config       $oConfig
     * @param Database     $oDatabase
     * @param SetupHandler $oSetupHandler
     */
    public function __construct(Wordpress $oWrapper, Config $oConfig, Database $oDatabase, SetupHandler $oSetupHandler)
    {
        parent::__construct($oWrapper, $oConfig);
        $this->_oDatabase = $oDatabase;
        $this->_oSetupHandler = $oSetupHandler;
    }

    /**
     * Returns if a database update is necessary.
     *
     * @return bool
     */
    public function isDatabaseUpdateNecessary()
    {
        return $this->_oSetupHandler->isDatabaseUpdateNecessary();
    }

    /**
     * Checks if a network update is nessary.
     *
     * @return bool
     */
    public function showNetworkUpdate()
    {
        return $this->_oWrapper->isSuperAdmin() === true
            && defined('MULTISITE') === true && MULTISITE === true
            && defined('WP_ALLOW_MULTISITE') === true && WP_ALLOW_MULTISITE === true;
    }

    /**
     * The database update action.
     */
    public function updateDatabaseAction()
    {
        $this->_verifyNonce(self::SETUP_UPDATE_NONCE);
        $sUpdate = $this->getRequestParameter('uam_update_db');

        if ($sUpdate === 'blog' || $sUpdate === 'network') {
            if ($sUpdate === 'network') {
                $aBlogIds = $this->_oSetupHandler->getBlogIds();

                if (count($aBlogIds) > 0) {
                    $iCurrentBlogId = $this->_oDatabase->getCurrentBlogId();

                    foreach ($aBlogIds as $iBlogId) {
                        $this->_oWrapper->switchToBlog($iBlogId);
                        $this->_oSetupHandler->update();
                    }

                    $this->_oWrapper->switchToBlog($iCurrentBlogId);
                }
            } else {
                $this->_oSetupHandler->update();
            }

            $this->_setUpdateMessage(TXT_UAM_UAM_DB_UPDATE_SUC);
        }
    }

    /**
     * The reset action.
     */
    public function resetUamAction()
    {
        $this->_verifyNonce(self::SETUP_RESET_NONCE);
        $sReset = $this->getRequestParameter('uam_reset');

        if ($sReset === 'reset') {
            $this->_oSetupHandler->uninstall();
            $this->_oSetupHandler->install();
            $this->_setUpdateMessage(TXT_UAM_UAM_RESET_SUCCESS);
        }
    }
}