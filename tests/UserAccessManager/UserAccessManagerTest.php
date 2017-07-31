<?php
/**
 * PluggableObjectTest.php
 *
 * The PluggableObjectTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager;

use UserAccessManager\Controller\AdminController;
use UserAccessManager\Controller\AdminObjectController;
use UserAccessManager\Controller\AdminSetupController;
use UserAccessManager\Controller\FrontendController;
use UserAccessManager\ObjectHandler\ObjectHandler;

/**
 * Class UserAccessManagerTest
 *
 * @package UserAccessManager
 * @coversDefaultClass \UserAccessManager\UserAccessManager
 */
class UserAccessManagerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $this->getCache(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getControllerFactory(),
            $this->getWidgetFactory(),
            $this->getCacheProviderFactory(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        self::assertInstanceOf(UserAccessManager::class, $userAccessManager);

        return $userAccessManager;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testConstructor()
    {
        $cache = $this->getCache();
        $cache->expects($this->once())
            ->method('setActiveCacheProvider')
            ->with('activeCacheProvider');

        $config = $this->getMainConfig();
        $config->expects($this->once())
            ->method('getActiveCacheProvider')
            ->will($this->returnValue('activeCacheProvider'));

        new UserAccessManager(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $cache,
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getControllerFactory(),
            $this->getWidgetFactory(),
            $this->getCacheProviderFactory(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getPhp()
     * @covers  ::getWordpress()
     * @covers  ::getUtil()
     * @covers  ::getCache()
     * @covers  ::getConfig()
     * @covers  ::getDatabase()
     * @covers  ::getObjectHandler()
     * @covers  ::getAccessHandler()
     * @covers  ::getFileHandler()
     * @covers  ::getSetupHandler()
     * @covers  ::getUserGroupFactory()
     * @covers  ::getControllerFactory()
     * @covers  ::getWidgetFactory()
     * @covers  ::getCacheProviderFactory()
     * @covers  ::getConfigFactory()
     * @covers  ::getConfigParameterFactory()
     * @covers  ::getFileProtectionFactory()
     * @covers  ::getFileObjectFactory()
     *
     * @param UserAccessManager $userAccessManager
     */
    public function testSimpleGetters(UserAccessManager $userAccessManager)
    {
        self::assertEquals($this->getPhp(), $userAccessManager->getPhp());
        self::assertEquals($this->getWordpress(), $userAccessManager->getWordpress());
        self::assertEquals($this->getUtil(), $userAccessManager->getUtil());
        self::assertEquals($this->getCache(), $userAccessManager->getCache());
        self::assertEquals($this->getMainConfig(), $userAccessManager->getConfig());
        self::assertEquals($this->getDatabase(), $userAccessManager->getDatabase());
        self::assertEquals($this->getObjectHandler(), $userAccessManager->getObjectHandler());
        self::assertEquals($this->getAccessHandler(), $userAccessManager->getAccessHandler());
        self::assertEquals($this->getFileHandler(), $userAccessManager->getFileHandler());
        self::assertEquals($this->getSetupHandler(), $userAccessManager->getSetupHandler());
        self::assertEquals($this->getUserGroupFactory(), $userAccessManager->getUserGroupFactory());
        self::assertEquals($this->getControllerFactory(), $userAccessManager->getControllerFactory());
        self::assertEquals($this->getWidgetFactory(), $userAccessManager->getWidgetFactory());
        self::assertEquals($this->getCacheProviderFactory(), $userAccessManager->getCacheProviderFactory());
        self::assertEquals($this->getConfigFactory(), $userAccessManager->getConfigFactory());
        self::assertEquals($this->getConfigParameterFactory(), $userAccessManager->getConfigParameterFactory());
        self::assertEquals($this->getFileProtectionFactory(), $userAccessManager->getFileProtectionFactory());
        self::assertEquals($this->getFileObjectFactory(), $userAccessManager->getFileObjectFactory());
    }

    /**
     * @group  unit
     * @covers ::registerAdminMenu()
     */
    public function testRegisterAdminMenu()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('addMenuPage');
        $wordpress->expects($this->exactly(4))
            ->method('addSubmenuPage');
        $wordpress->expects($this->once())
            ->method('doAction');

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $controllerFactory = $this->getControllerFactory();
        $controllerFactory->expects($this->once())
            ->method('createAdminUserGroupController');

        $controllerFactory->expects($this->once())
            ->method('createAdminSettingsController');

        $controllerFactory->expects($this->once())
            ->method('createAdminSetupController');

        $controllerFactory->expects($this->once())
            ->method('createAdminAboutController');

        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getUtil(),
            $this->getCache(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $controllerFactory,
            $this->getWidgetFactory(),
            $this->getCacheProviderFactory(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        $userAccessManager->registerAdminMenu();
        $userAccessManager->registerAdminMenu();
    }

    /**
     * @group  unit
     * @covers ::registerAdminActionsAndFilters()
     */
    public function testRegisterAdminActionsAndFilters()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(67))
            ->method('addAction');

        $wordpress->expects($this->exactly(16))
            ->method('addFilter');

        $wordpress->expects($this->exactly(3))
            ->method('addMetaBox');


        $config = $this->getMainConfig();

        $config->expects($this->exactly(3))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(true, false, false));

        $config->expects($this->exactly(6))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true));


        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(4))
            ->method('getTaxonomies')
            ->will($this->returnValue(['a', 'b']));

        $objectHandler->expects($this->exactly(2))
            ->method('getPostTypes')
            ->will($this->returnValue(['a', ObjectHandler::ATTACHMENT_OBJECT_TYPE]));


        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(4))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(true, false, false, false));


        $setupHandler = $this->getSetupHandler();

        $setupHandler->expects($this->exactly(4))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(false, true, true, true));


        $adminController = $this->createMock(AdminController::class);

        $adminController->expects($this->exactly(8))
            ->method('getRequestParameter')
            ->withConsecutive(
                ['uam_update_db'],
                ['taxonomy'],
                ['uam_update_db'],
                ['taxonomy'],
                ['uam_update_db'],
                ['taxonomy'],
                ['uam_update_db'],
                ['taxonomy']
            )
            ->will($this->onConsecutiveCalls(
                AdminSetupController::UPDATE_BLOG,
                null,
                AdminSetupController::UPDATE_BLOG,
                'c',
                AdminSetupController::UPDATE_NETWORK,
                'c',
                null,
                'c'
            ));

        $controllerFactory = $this->getControllerFactory();

        $controllerFactory->expects($this->exactly(4))
            ->method('createAdminController')
            ->will($this->returnValue($adminController));

        $controllerFactory->expects($this->exactly(4))
            ->method('createAdminObjectController')
            ->will($this->returnCallback(function () {
                $adminObjectController = $this->createMock(AdminObjectController::class);
                $adminObjectController->expects($this->any())
                    ->method('checkRightsToEditContent');

                $adminObjectController->expects($this->any())
                    ->method('getRequestParameter')
                    ->will($this->returnValue('c'));

                return $adminObjectController;
            }));

        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getUtil(),
            $this->getCache(),
            $config,
            $this->getDatabase(),
            $objectHandler,
            $accessHandler,
            $this->getFileHandler(),
            $setupHandler,
            $this->getUserGroupFactory(),
            $controllerFactory,
            $this->getWidgetFactory(),
            $this->getCacheProviderFactory(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        $userAccessManager->registerAdminActionsAndFilters();
        $userAccessManager->registerAdminActionsAndFilters();
        $userAccessManager->registerAdminActionsAndFilters();
        $userAccessManager->registerAdminActionsAndFilters();
    }

    /**
     * @group  unit
     * @covers ::addActionsAndFilters()
     */
    public function testAddActionsAndFilters()
    {
        $frontendController = $this->createMock(FrontendController::class);
        $frontendController->expects($this->exactly(3))
            ->method('getRequestParameter')
            ->will($this->onConsecutiveCalls(null, true, true));

        $controllerFactory = $this->getControllerFactory();
        $controllerFactory->expects($this->exactly(3))
            ->method('createFrontendController')
            ->will($this->returnValue($frontendController));

        $controllerFactory->expects($this->exactly(3))
            ->method('createAdminObjectController');

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(24))
            ->method('addAction');

        $wordpress->expects($this->exactly(80))
            ->method('addFilter');

        $wordpress->expects($this->exactly(12))
            ->method('addShortCode');

        $config = $this->getMainConfig();
        $config->expects($this->exactly(3))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls(false, false, true));

        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getUtil(),
            $this->getCache(),
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $controllerFactory,
            $this->getWidgetFactory(),
            $this->getCacheProviderFactory(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        $userAccessManager->addActionsAndFilters();
        $userAccessManager->addActionsAndFilters();
        $userAccessManager->addActionsAndFilters();
    }
}
