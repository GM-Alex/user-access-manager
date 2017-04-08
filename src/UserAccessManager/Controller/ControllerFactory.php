<?php
/**
 * ControllerFactory.php
 *
 * The ControllerFactory class file.
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
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ControllerFactory
 *
 * @package UserAccessManager\Controller
 */
class ControllerFactory
{
    /**
     * @var Php
     */
    protected $Php;

    /**
     * @var Wordpress
     */
    protected $Wordpress;

    /**
     * @var Database
     */
    protected $Database;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var Util
     */
    protected $Util;

    /**
     * @var Cache
     */
    protected $Cache;

    /**
     * @var ObjectHandler
     */
    protected $ObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $AccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $UserGroupFactory;

    /**
     * @var FileHandler
     */
    protected $FileHandler;

    /**
     * @var SetupHandler
     */
    protected $SetupHandler;

    /**
     * ControllerFactory constructor.
     *
     * @param Php              $Php
     * @param Wordpress        $Wordpress
     * @param Database         $Database
     * @param Config           $Config
     * @param Util             $Util
     * @param Cache            $Cache
     * @param ObjectHandler    $ObjectHandler
     * @param AccessHandler    $AccessHandler
     * @param UserGroupFactory $UserGroupFactory
     * @param FileHandler      $FileHandler
     * @param SetupHandler     $SetupHandler
     */
    public function __construct(
        Php $Php,
        Wordpress $Wordpress,
        Database $Database,
        Config $Config,
        Util $Util,
        Cache $Cache,
        ObjectHandler $ObjectHandler,
        AccessHandler $AccessHandler,
        UserGroupFactory $UserGroupFactory,
        FileHandler $FileHandler,
        SetupHandler $SetupHandler
    ) {
        $this->Php = $Php;
        $this->Wordpress = $Wordpress;
        $this->Database = $Database;
        $this->Config = $Config;
        $this->Util = $Util;
        $this->Cache = $Cache;
        $this->ObjectHandler = $ObjectHandler;
        $this->AccessHandler = $AccessHandler;
        $this->UserGroupFactory = $UserGroupFactory;
        $this->FileHandler = $FileHandler;
        $this->SetupHandler = $SetupHandler;
    }

    /**
     * Creates and returns a new admin controller.
     *
     * @return AdminController
     */
    public function createAdminController()
    {
        return new AdminController(
            $this->Php,
            $this->Wordpress,
            $this->Config,
            $this->AccessHandler,
            $this->FileHandler
        );
    }

    /**
     * Creates and returns a new admin about controller.
     *
     * @return AdminAboutController
     */
    public function createAdminAboutController()
    {
        return new AdminAboutController(
            $this->Php,
            $this->Wordpress,
            $this->Config
        );
    }

    /**
     * Creates and returns a new admin about controller.
     *
     * @return AdminObjectController
     */
    public function createAdminObjectController()
    {
        return new AdminObjectController(
            $this->Php,
            $this->Wordpress,
            $this->Config,
            $this->Database,
            $this->ObjectHandler,
            $this->AccessHandler
        );
    }

    /**
     * Creates and returns a new admin setup controller.
     *
     * @return AdminSettingsController
     */
    public function createAdminSettingsController()
    {
        return new AdminSettingsController(
            $this->Php,
            $this->Wordpress,
            $this->Config,
            $this->ObjectHandler,
            $this->FileHandler
        );
    }

    /**
     * Creates and returns a new admin setup controller.
     *
     * @return AdminSetupController
     */
    public function createAdminSetupController()
    {
        return new AdminSetupController(
            $this->Php,
            $this->Wordpress,
            $this->Config,
            $this->Database,
            $this->SetupHandler
        );
    }

    /**
     * Creates and returns a new admin user group controller.
     *
     * @return AdminUserGroupController
     */
    public function createAdminUserGroupController()
    {
        return new AdminUserGroupController(
            $this->Php,
            $this->Wordpress,
            $this->Config,
            $this->AccessHandler,
            $this->UserGroupFactory
        );
    }

    /**
     * Creates and returns a new frontend controller.
     *
     * @return FrontendController
     */
    public function createFrontendController()
    {
        return new FrontendController(
            $this->Php,
            $this->Wordpress,
            $this->Config,
            $this->Database,
            $this->Util,
            $this->Cache,
            $this->ObjectHandler,
            $this->AccessHandler,
            $this->FileHandler
        );
    }
}
