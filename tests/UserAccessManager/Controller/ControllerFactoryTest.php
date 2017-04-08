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
        $ControllerFactory = new ControllerFactory(
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
            $this->getSetupHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\ControllerFactory', $ControllerFactory);

        return $ControllerFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminController()
     *
     * @param ControllerFactory $ControllerFactory
     */
    public function testCreateAdminController(ControllerFactory $ControllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminController',
            $ControllerFactory->createAdminController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminAboutController()
     *
     * @param ControllerFactory $ControllerFactory
     */
    public function testCreateAdminAboutController(ControllerFactory $ControllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminAboutController',
            $ControllerFactory->createAdminAboutController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminObjectController()
     *
     * @param ControllerFactory $ControllerFactory
     */
    public function testCreateAdminObjectController(ControllerFactory $ControllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminObjectController',
            $ControllerFactory->createAdminObjectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminSettingsController()
     *
     * @param ControllerFactory $ControllerFactory
     */
    public function testCreateAdminSettingController(ControllerFactory $ControllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminSettingsController',
            $ControllerFactory->createAdminSettingsController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminSetupController()
     *
     * @param ControllerFactory $ControllerFactory
     */
    public function testCreateAdminSetupController(ControllerFactory $ControllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminSetupController',
            $ControllerFactory->createAdminSetupController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createAdminUserGroupController()
     *
     * @param ControllerFactory $ControllerFactory
     */
    public function testCreateAdminUserGroupController(ControllerFactory $ControllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\AdminUserGroupController',
            $ControllerFactory->createAdminUserGroupController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\ControllerFactory::createFrontendController()
     *
     * @param ControllerFactory $ControllerFactory
     */
    public function testCreateFrontendController(ControllerFactory $ControllerFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Controller\FrontendController',
            $ControllerFactory->createFrontendController()
        );
    }
}
