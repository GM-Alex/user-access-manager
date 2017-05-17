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

use UserAccessManager\Controller\AdminSetupController;
use UserAccessManager\ObjectHandler\ObjectHandler;

/**
 * Class UserAccessManagerTest
 *
 * @package UserAccessManager
 */
class UserAccessManagerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\UserAccessManager::__construct()
     */
    public function testCanCreateInstance()
    {
        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $this->getCache(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getControllerFactory(),
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        self::assertInstanceOf('\UserAccessManager\UserAccessManager', $userAccessManager);

        return $userAccessManager;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\UserAccessManager::getPhp()
     * @covers  \UserAccessManager\UserAccessManager::getWordpress()
     * @covers  \UserAccessManager\UserAccessManager::getUtil()
     * @covers  \UserAccessManager\UserAccessManager::getCache()
     * @covers  \UserAccessManager\UserAccessManager::getConfig()
     * @covers  \UserAccessManager\UserAccessManager::getDatabase()
     * @covers  \UserAccessManager\UserAccessManager::getObjectHandler()
     * @covers  \UserAccessManager\UserAccessManager::getAccessHandler()
     * @covers  \UserAccessManager\UserAccessManager::getFileHandler()
     * @covers  \UserAccessManager\UserAccessManager::getSetupHandler()
     * @covers  \UserAccessManager\UserAccessManager::getUserGroupFactory()
     * @covers  \UserAccessManager\UserAccessManager::getControllerFactory()
     * @covers  \UserAccessManager\UserAccessManager::getConfigParameterFactory()
     * @covers  \UserAccessManager\UserAccessManager::getFileProtectionFactory()
     * @covers  \UserAccessManager\UserAccessManager::getFileObjectFactory()
     *
     * @param UserAccessManager $userAccessManager
     */
    public function testSimpleGetters(UserAccessManager $userAccessManager)
    {
        self::assertEquals($this->getPhp(), $userAccessManager->getPhp());
        self::assertEquals($this->getWordpress(), $userAccessManager->getWordpress());
        self::assertEquals($this->getUtil(), $userAccessManager->getUtil());
        self::assertEquals($this->getCache(), $userAccessManager->getCache());
        self::assertEquals($this->getConfig(), $userAccessManager->getConfig());
        self::assertEquals($this->getDatabase(), $userAccessManager->getDatabase());
        self::assertEquals($this->getObjectHandler(), $userAccessManager->getObjectHandler());
        self::assertEquals($this->getAccessHandler(), $userAccessManager->getAccessHandler());
        self::assertEquals($this->getFileHandler(), $userAccessManager->getFileHandler());
        self::assertEquals($this->getSetupHandler(), $userAccessManager->getSetupHandler());
        self::assertEquals($this->getUserGroupFactory(), $userAccessManager->getUserGroupFactory());
        self::assertEquals($this->getControllerFactory(), $userAccessManager->getControllerFactory());
        self::assertEquals($this->getConfigParameterFactory(), $userAccessManager->getConfigParameterFactory());
        self::assertEquals($this->getFileProtectionFactory(), $userAccessManager->getFileProtectionFactory());
        self::assertEquals($this->getFileObjectFactory(), $userAccessManager->getFileObjectFactory());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserAccessManager::registerAdminMenu()
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
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $controllerFactory,
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        $userAccessManager->registerAdminMenu();
        $userAccessManager->registerAdminMenu();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserAccessManager::registerAdminActionsAndFilters()
     */
    public function testRegisterAdminActionsAndFilters()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(63))
            ->method('addAction');

        $wordpress->expects($this->exactly(16))
            ->method('addFilter');

        $wordpress->expects($this->exactly(3))
            ->method('addMetaBox');


        $config = $this->getConfig();

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


        $adminController = $this->createMock('UserAccessManager\Controller\AdminController');

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
                $adminObjectController = $this->createMock('UserAccessManager\Controller\AdminObjectController');
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
     * @covers \UserAccessManager\UserAccessManager::addActionsAndFilters()
     */
    public function testAddActionsAndFilters()
    {
        $frontendController = $this->createMock('UserAccessManager\Controller\FrontendController');
        $frontendController->expects($this->exactly(3))
            ->method('getRequestParameter')
            ->will($this->onConsecutiveCalls(null, true, true));

        $controllerFactory = $this->getControllerFactory();
        $controllerFactory->expects($this->exactly(3))
            ->method('createFrontendController')
            ->will($this->returnValue($frontendController));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(21))
            ->method('addAction');

        $wordpress->expects($this->exactly(71))
            ->method('addFilter');

        $config = $this->getConfig();
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
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        $userAccessManager->addActionsAndFilters();
        $userAccessManager->addActionsAndFilters();
        $userAccessManager->addActionsAndFilters();
    }
}
