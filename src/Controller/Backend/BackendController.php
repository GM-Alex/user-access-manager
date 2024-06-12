<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class BackendController extends Controller
{
    public const HANDLE_STYLE_ADMIN = 'UserAccessManagerAdmin';
    public const HANDLE_SCRIPT_GROUP_SUGGEST = 'UserAccessManagerGroupSuggest';
    public const HANDLE_SCRIPT_TIME_INPUT = 'UserAccessManagerTimeInput';
    public const HANDLE_SCRIPT_ADMIN = 'UserAccessManagerFunctions';
    public const UAM_ERRORS = 'UAM_ERRORS';

    private string $notice = '';

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        private UserHandler $userHandler,
        private SetupHandler $setupHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
    }

    /**
     * @throws MissingColumnsException
     */
    public function showAdminNotice(): void
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

    public function getNotice(): string
    {
        return $this->notice;
    }

    private function registerStylesAndScripts(): void
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

    public function enqueueStylesAndScripts(): void
    {
        $this->registerStylesAndScripts();
        $this->wordpress->enqueueStyle(self::HANDLE_STYLE_ADMIN);
        $this->wordpress->enqueueScript(self::HANDLE_SCRIPT_GROUP_SUGGEST);
        $this->wordpress->enqueueScript(self::HANDLE_SCRIPT_TIME_INPUT);
        $this->wordpress->enqueueScript(self::HANDLE_SCRIPT_ADMIN);
    }

    public function setupAdminDashboard(): void
    {
        if ($this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY) === false) {
            $metaBoxes = $this->wordpress->getMetaBoxes();
            unset($metaBoxes['dashboard']['normal']['core']['dashboard_recent_comments']);
            $this->wordpress->setMetaBoxes($metaBoxes);
        }
    }
}
