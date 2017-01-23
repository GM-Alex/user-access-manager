<?php
/**
 * AdminController.php
 *
 * The AdminController class file.
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

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\Config;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AdminController
 *
 * @package UserAccessManager\Controller
 */
class AdminController extends Controller
{
    const HANDLE_STYLE_ADMIN = 'UserAccessManagerAdmin';
    const HANDLE_SCRIPT_ADMIN = 'UserAccessManagerFunctions';

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * @var FileHandler
     */
    protected $_oFileHandler;

    /**
     * @var string
     */
    protected $_sNotice = '';

    /**
     * AdminController constructor.
     *
     * @param Wordpress     $oWrapper
     * @param Config        $oConfig
     * @param AccessHandler $oAccessHandler
     * @param FileHandler   $oFileHandler
     */
    public function __construct(
        Wordpress $oWrapper,
        Config $oConfig,
        AccessHandler $oAccessHandler,
        FileHandler $oFileHandler
    )
    {
        parent::__construct($oWrapper, $oConfig);
        $this->_oConfig = $oConfig;
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oFileHandler = $oFileHandler;
    }

    /**
     * Returns the set notice.
     *
     * @return string
     */
    public function getNotice()
    {
        return $this->_sNotice;
    }

    /**
     * Shows the fopen notice.
     */
    public function showFOpenNotice()
    {
        $this->_sNotice = TXT_UAM_FOPEN_WITHOUT_SAVE_MODE_OFF;
        echo $this->_getIncludeContents('AdminNotice.php');
    }

    /**
     * Shows the database notice.
     */
    public function showDatabaseNotice()
    {
        $this->_sNotice = sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup');
        echo $this->_getIncludeContents('AdminNotice.php');
    }

    /**
     * Register styles and scripts with handle for admin panel.
     */
    protected function registerStylesAndScripts()
    {
        $sUrlPath = $this->_oConfig->getUrlPath();

        $this->_oWrapper->registerStyle(
            self::HANDLE_STYLE_ADMIN,
            $sUrlPath.'assets/css/uamAdmin.css',
            array(),
            UserAccessManager::VERSION,
            'screen'
        );

        $this->_oWrapper->registerScript(
            self::HANDLE_SCRIPT_ADMIN,
            $sUrlPath.'assets/js/functions.js',
            array('jquery'),
            UserAccessManager::VERSION
        );
    }

    /**
     * The function for the admin_enqueue_scripts action for styles and scripts.
     *
     * @param string $sHook
     */
    public function enqueueStylesAndScripts($sHook)
    {
        $this->registerStylesAndScripts();
        $this->_oWrapper->enqueueStyle(self::HANDLE_STYLE_ADMIN);

        if ($sHook === 'uam_page_uam_settings' || $sHook === 'uam_page_uam_setup') {
            $this->_oWrapper->enqueueScript(self::HANDLE_SCRIPT_ADMIN);
        }
    }


    /**
     * The function for the wp_dashboard_setup action.
     * Removes widgets to which a user should not have access.
     */
    public function setupAdminDashboard()
    {
        if (!$this->_oAccessHandler->checkUserAccess('manage_user_groups')) {
            $aMetaBoxes = $this->_oWrapper->getMetaBoxes();
            unset($aMetaBoxes['dashboard']['normal']['core']['dashboard_recent_comments']);
        }
    }

    /**
     * The function for the update_option_permalink_structure action.
     */
    public function updatePermalink()
    {
        $this->_oFileHandler->createFileProtection();
    }

}