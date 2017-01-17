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

use UserAccessManager\UserAccessManager;
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
     * @var UserAccessManager
     */
    protected $_oUserAccessManager;

    /**
     * @var string
     */
    protected $_sTemplate = 'AdminSetup.php';

    /**
     * AdminSetupController constructor.
     *
     * @param Wordpress         $oWrapper
     * @param UserAccessManager $oUserAccessManager
     */
    public function __construct(Wordpress $oWrapper, UserAccessManager $oUserAccessManager)
    {
        parent::__construct($oWrapper);
        $this->_oUserAccessManager = $oUserAccessManager;
    }

    /**
     * Returns if a database update is necessary.
     *
     * @return bool
     */
    public function isDatabaseUpdateNecessary()
    {
        return $this->_oUserAccessManager->isDatabaseUpdateNecessary();
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

        if ($sUpdate === 'blog' || $sUpdate === 'network' ) {
            $blNetwork = ($sUpdate == 'network') ? true : false;
            $this->_oUserAccessManager->update($blNetwork);
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
            $this->_oUserAccessManager->uninstall();
            $this->_oUserAccessManager->install();
            $this->_setUpdateMessage(TXT_UAM_UAM_RESET_SUCCSESS);
        }
    }
}