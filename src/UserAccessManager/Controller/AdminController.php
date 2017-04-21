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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\Config;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Php;
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
    private $accessHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var string
     */
    private $notice = '';

    /**
     * AdminController constructor.
     *
     *
     * @param Php           $php
     * @param Wordpress     $wordpress
     * @param Config        $config
     * @param AccessHandler $accessHandler
     * @param FileHandler   $fileHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Config $config,
        AccessHandler $accessHandler,
        FileHandler $fileHandler
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->accessHandler = $accessHandler;
        $this->fileHandler = $fileHandler;
    }

    /**
     * Shows the database notice.
     */
    public function showDatabaseNotice()
    {
        $this->notice = sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup');
        echo $this->getIncludeContents('AdminNotice.php');
    }

    /**
     * Returns the set notice.
     *
     * @return string
     */
    public function getNotice()
    {
        return $this->notice;
    }

    /**
     * Register styles and scripts with handle for admin panel.
     */
    private function registerStylesAndScripts()
    {
        $urlPath = $this->config->getUrlPath();

        $this->wordpress->registerStyle(
            self::HANDLE_STYLE_ADMIN,
            $urlPath.'assets/css/uamAdmin.css',
            [],
            UserAccessManager::VERSION,
            'screen'
        );

        $this->wordpress->registerScript(
            self::HANDLE_SCRIPT_ADMIN,
            $urlPath.'assets/js/functions.js',
            ['jquery'],
            UserAccessManager::VERSION
        );
    }

    /**
     * The function for the admin_enqueue_scripts action for styles and scripts.
     *
     * @param string $hook
     */
    public function enqueueStylesAndScripts($hook)
    {
        $this->registerStylesAndScripts();
        $this->wordpress->enqueueStyle(self::HANDLE_STYLE_ADMIN);

        if ($hook === 'uam_page_uam_settings' || $hook === 'uam_page_uam_setup') {
            $this->wordpress->enqueueScript(self::HANDLE_SCRIPT_ADMIN);
        }
    }


    /**
     * The function for the wp_dashboard_setup action.
     * Removes widgets to which a user should not have access.
     */
    public function setupAdminDashboard()
    {
        if ($this->accessHandler->checkUserAccess('manage_user_groups') === false) {
            $metaBoxes = $this->wordpress->getMetaBoxes();
            unset($metaBoxes['dashboard']['normal']['core']['dashboard_recent_comments']);
            $this->wordpress->setMetaBoxes($metaBoxes);
        }
    }

    /**
     * The function for the update_option_permalink_structure action.
     */
    public function updatePermalink()
    {
        $this->fileHandler->createFileProtection();
    }
}
