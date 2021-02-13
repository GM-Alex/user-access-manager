<?php
/**
 * BackendController.php
 *
 * The BackendController class file.
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
use UserAccessManager\File\FileHandler;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class BackendController
 *
 * @package UserAccessManager\Controller
 */
class BackendController extends Controller
{
    public const HANDLE_STYLE_ADMIN = 'UserAccessManagerAdmin';
    public const HANDLE_SCRIPT_GROUP_SUGGEST = 'UserAccessManagerGroupSuggest';
    public const HANDLE_SCRIPT_TIME_INPUT = 'UserAccessManagerTimeInput';
    public const HANDLE_SCRIPT_ADMIN = 'UserAccessManagerFunctions';
    public const UAM_ERRORS = 'UAM_ERRORS';

    /**
     * @var UserHandler
     */
    private $userHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var SetupHandler
     */
    private $setupHandler;

    /**
     * @var string
     */
    private $notice = '';

    /**
     * BackendController constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param UserHandler $userHandler
     * @param FileHandler $fileHandler
     * @param SetupHandler $setupHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        UserHandler $userHandler,
        FileHandler $fileHandler,
        SetupHandler $setupHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->userHandler = $userHandler;
        $this->fileHandler = $fileHandler;
        $this->setupHandler = $setupHandler;
    }

    /**
     * Shows the admin notices.
     */
    public function showAdminNotice()
    {
        $messages = isset($_SESSION[self::UAM_ERRORS]) === true ? $_SESSION[self::UAM_ERRORS] : [];
        $updateAction = $this->getRequestParameter('uam_update_db');

        if ($this->setupHandler->getDatabaseHandler()->isDatabaseUpdateNecessary() === true
            && $updateAction !== SetupController::UPDATE_BLOG
            && $updateAction !== SetupController::UPDATE_NETWORK
        ) {
            $messages[] = sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup');
        }

        if ($messages !== []) {
            $this->notice = implode('<br>', $messages);
            echo $this->getIncludeContents('AdminNotice.php');
        }
    }

    /**
     * Returns the set notice.
     * @return string
     */
    public function getNotice(): string
    {
        return $this->notice;
    }

    /**
     * Register styles and scripts with handle for admin panel.
     */
    private function registerStylesAndScripts()
    {
        $urlPath = $this->wordpressConfig->getUrlPath();

        $this->wordpress->registerStyle(
            self::HANDLE_STYLE_ADMIN,
            $urlPath . 'assets/css/uamAdmin.css',
            [],
            UserAccessManager::VERSION,
            'screen'
        );

        $this->wordpress->registerScript(
            self::HANDLE_SCRIPT_GROUP_SUGGEST,
            $urlPath . 'assets/js/jquery.uam-group-suggest.js',
            ['jquery'],
            UserAccessManager::VERSION
        );

        $this->wordpress->registerScript(
            self::HANDLE_SCRIPT_TIME_INPUT,
            $urlPath . 'assets/js/jquery.uam-time-input.js',
            ['jquery'],
            UserAccessManager::VERSION
        );

        $this->wordpress->registerScript(
            self::HANDLE_SCRIPT_ADMIN,
            $urlPath . 'assets/js/functions.js',
            ['jquery'],
            UserAccessManager::VERSION
        );
    }

    /**
     * The function for the admin_enqueue_scripts action for styles and scripts.
     */
    public function enqueueStylesAndScripts()
    {
        $this->registerStylesAndScripts();
        $this->wordpress->enqueueStyle(self::HANDLE_STYLE_ADMIN);
        $this->wordpress->enqueueScript(self::HANDLE_SCRIPT_GROUP_SUGGEST);
        $this->wordpress->enqueueScript(self::HANDLE_SCRIPT_TIME_INPUT);
        $this->wordpress->enqueueScript(self::HANDLE_SCRIPT_ADMIN);
    }


    /**
     * The function for the wp_dashboard_setup action.
     * Removes widgets to which a user should not have access.
     */
    public function setupAdminDashboard()
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === false) {
            $metaBoxes = $this->wordpress->getMetaBoxes();
            unset($metaBoxes['dashboard']['normal']['core']['dashboard_recent_comments']);
            $this->wordpress->setMetaBoxes($metaBoxes);
        }
    }
}
