<?php
/**
 * ControllerFactoryTest.php
 *
 * The ControllerFactoryTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller;

use UserAccessManager\Controller\AdminAboutController;
use UserAccessManager\Controller\AdminController;
use UserAccessManager\Controller\AdminObjectController;
use UserAccessManager\Controller\AdminSettingsController;
use UserAccessManager\Controller\AdminSetupController;
use UserAccessManager\Controller\AdminUserGroupController;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Controller\FrontendController;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class ControllerFactoryTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\ControllerFactory
 */
class ControllerFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     *
     * @return ControllerFactory
     */
    public function testCanCreateInstance()
    {
        $controllerFactory = new ControllerFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $this->getFileHandler(),
            $this->getFileObjectFactory(),
            $this->getSetupHandler(),
            $this->getFormFactory(),
            $this->getFormHelper()
        );

        self::assertInstanceOf(ControllerFactory::class, $controllerFactory);

        return $controllerFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createAdminController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            AdminController::class,
            $controllerFactory->createAdminController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createAdminAboutController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminAboutController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            AdminAboutController::class,
            $controllerFactory->createAdminAboutController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createAdminObjectController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminObjectController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            AdminObjectController::class,
            $controllerFactory->createAdminObjectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createAdminSettingsController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminSettingController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            AdminSettingsController::class,
            $controllerFactory->createAdminSettingsController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createAdminSetupController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminSetupController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            AdminSetupController::class,
            $controllerFactory->createAdminSetupController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createAdminUserGroupController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminUserGroupController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            AdminUserGroupController::class,
            $controllerFactory->createAdminUserGroupController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFrontendController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateFrontendController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            FrontendController::class,
            $controllerFactory->createFrontendController()
        );
    }
}
