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
namespace UserAccessManager\Tests\Unit\Controller;

use UserAccessManager\Controller\Backend\AboutController;
use UserAccessManager\Controller\Backend\BackendController;
use UserAccessManager\Controller\Backend\CacheController;
use UserAccessManager\Controller\Backend\DynamicGroupsController;
use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Controller\Backend\PostObjectController;
use UserAccessManager\Controller\Backend\SettingsController;
use UserAccessManager\Controller\Backend\SetupController;
use UserAccessManager\Controller\Backend\TermObjectController;
use UserAccessManager\Controller\Backend\UserGroupController;
use UserAccessManager\Controller\Backend\UserObjectController;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Controller\Frontend\FrontendController;
use UserAccessManager\Controller\Frontend\PostController;
use UserAccessManager\Controller\Frontend\RedirectController;
use UserAccessManager\Controller\Frontend\ShortCodeController;
use UserAccessManager\Controller\Frontend\TermController;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class ControllerFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Controller
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
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getDateUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getObjectMapHandler(),
            $this->getUserHandler(),
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
     * @covers  ::createBackendController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            BackendController::class,
            $controllerFactory->createBackendController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendAboutController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendAboutController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            AboutController::class,
            $controllerFactory->createBackendAboutController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendObjectController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendObjectController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            ObjectController::class,
            $controllerFactory->createBackendObjectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendPostObjectController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendPostObjectController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            PostObjectController::class,
            $controllerFactory->createBackendPostObjectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendTermObjectController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendTermObjectController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            TermObjectController::class,
            $controllerFactory->createBackendTermObjectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendUserObjectController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendUserObjectController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            UserObjectController::class,
            $controllerFactory->createBackendUserObjectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendCacheController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendCacheController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            CacheController::class,
            $controllerFactory->createBackendCacheController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendDynamicGroupsController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateDynamicGroupController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            DynamicGroupsController::class,
            $controllerFactory->createBackendDynamicGroupsController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendSettingsController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendSettingController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            SettingsController::class,
            $controllerFactory->createBackendSettingsController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendSetupController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendSetupController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            SetupController::class,
            $controllerFactory->createBackendSetupController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBackendUserGroupController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateBackendUserGroupController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            UserGroupController::class,
            $controllerFactory->createBackendUserGroupController()
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

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFrontendPostController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateFrontendPostController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            PostController::class,
            $controllerFactory->createFrontendPostController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFrontendRedirectController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateFrontendRedirectController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            RedirectController::class,
            $controllerFactory->createFrontendRedirectController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFrontendShortCodeController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateFrontendShortCodeController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            ShortCodeController::class,
            $controllerFactory->createFrontendShortCodeController()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFrontendTermController()
     *
     * @param ControllerFactory $controllerFactory
     */
    public function testCreateFrontendTermController(ControllerFactory $controllerFactory)
    {
        self::assertInstanceOf(
            TermController::class,
            $controllerFactory->createFrontendTermController()
        );
    }
}
