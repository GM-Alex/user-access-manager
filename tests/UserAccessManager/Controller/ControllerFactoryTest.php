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
namespace UserAccessManager\Controller;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class ControllerFactoryTest
 *
 * @package UserAccessManager\Controller
 */
class ControllerFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\ControllerFactory::__construct()
     *
     * @return ControllerFactory
     */
    public function testCanCreateInstance()
    {
        $controllerFactory = new ControllerFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getConfig(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $this->getFileHandler(),
            $this->getFileObjectFactory(),
            $this->getSetupHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\ControllerFactory', $controllerFactory);

        return $controllerFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminController',
            $controllerFactory->createAdminController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminAboutController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminAboutController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminAboutController',
            $controllerFactory->createAdminAboutController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminObjectController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminObjectController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminObjectController',
            $controllerFactory->createAdminObjectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminSettingsController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminSettingController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminSettingsController',
            $controllerFactory->createAdminSettingsController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminSetupController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminSetupController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminSetupController',
            $controllerFactory->createAdminSetupController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminUserGroupController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateAdminUserGroupController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminUserGroupController',
            $controllerFactory->createAdminUserGroupController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createFrontendController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateFrontendController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\FrontendController',
            $controllerFactory->createFrontendController()
        );
    }
}
