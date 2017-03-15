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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

/**
 * Class ControllerFactoryTest
 *
 * @package UserAccessManager\Controller
 */
class ControllerFactoryTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\ControllerFactory::__construct()
     *
     * @return ControllerFactory
     */
    public function testCanCreateInstance()
    {
        $oControllerFactory = new ControllerFactory(
            $this->getWrapper(),
            $this->getDatabase(),
            $this->getConfig(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory(),
            $this->getFileHandler(),
            $this->getSetupHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\ControllerFactory', $oControllerFactory);

        return $oControllerFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminController()
     *
     * @param ControllerFactory $oControllerFactory
     */
    public function testCreateAdminController(ControllerFactory $oControllerFactory)
    {
        self::assertInstanceOf('\UserAccessManager\Controller\AdminController', $oControllerFactory->createAdminController());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminAboutController()
     *
     * @param ControllerFactory $oControllerFactory
     */
    public function testCreateAdminAboutController(ControllerFactory $oControllerFactory)
    {
        self::assertInstanceOf('\UserAccessManager\Controller\AdminAboutController', $oControllerFactory->createAdminAboutController());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminObjectController()
     *
     * @param ControllerFactory $oControllerFactory
     */
    public function testCreateAdminObjectController(ControllerFactory $oControllerFactory)
    {
        self::assertInstanceOf('\UserAccessManager\Controller\AdminObjectController', $oControllerFactory->createAdminObjectController());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminSettingsController()
     *
     * @param ControllerFactory $oControllerFactory
     */
    public function testCreateAdminSettingController(ControllerFactory $oControllerFactory)
    {
        self::assertInstanceOf('\UserAccessManager\Controller\AdminSettingsController', $oControllerFactory->createAdminSettingsController());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminSetupController()
     *
     * @param ControllerFactory $oControllerFactory
     */
    public function testCreateAdminSetupController(ControllerFactory $oControllerFactory)
    {
        self::assertInstanceOf('\UserAccessManager\Controller\AdminSetupController', $oControllerFactory->createAdminSetupController());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminUserGroupController()
     *
     * @param ControllerFactory $oControllerFactory
     */
    public function testCreateAdminUserGroupController(ControllerFactory $oControllerFactory)
    {
        self::assertInstanceOf('\UserAccessManager\Controller\AdminUserGroupController', $oControllerFactory->createAdminUserGroupController());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createFrontendController()
     *
     * @param ControllerFactory $oControllerFactory
     */
    public function testCreateFrontendController(ControllerFactory $oControllerFactory)
    {
        self::assertInstanceOf('\UserAccessManager\Controller\FrontendController', $oControllerFactory->createFrontendController());
    }
}
