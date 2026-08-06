<?php
/**
 * UserAccessManagerTest.php
 *
 * The UserAccessManagerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit;

use UserAccessManager\Controller\Backend\BackendController;
use UserAccessManager\Controller\Backend\ObjectMembership\DynamicGroupsController;
use UserAccessManager\Controller\Backend\ObjectMembership\ObjectController;
use UserAccessManager\Controller\Backend\ObjectMembership\PostObjectController;
use UserAccessManager\Controller\Backend\ObjectMembership\TermObjectController;
use UserAccessManager\Controller\Backend\ObjectMembership\UserObjectController;
use UserAccessManager\Controller\Frontend\FrontendController;
use UserAccessManager\Controller\Frontend\Content\PostController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroupTypeException;

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
    public function testCanCreateInstance(): UserAccessManager
    {
        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $this->getCache(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getObjectMembershipHandlerFactory(),
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
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getObjectMembershipHandlerFactory(),
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
     * @covers  ::getUserHandler()
     * @covers  ::getUserGroupHandler()
     * @covers  ::getAccessHandler()
     * @covers  ::getFileHandler()
     * @covers  ::getSetupHandler()
     * @covers  ::getUserGroupFactory()
     * @covers  ::getObjectMembershipHandlerFactory()
     * @covers  ::getControllerFactory()
     * @covers  ::getWidgetFactory()
     * @covers  ::getCacheProviderFactory()
     * @covers  ::getConfigFactory()
     * @covers  ::getConfigParameterFactory()
     * @covers  ::getFileProtectionFactory()
     * @covers  ::getFileObjectFactory()
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
        self::assertEquals($this->getUserHandler(), $userAccessManager->getUserHandler());
        self::assertEquals($this->getUserGroupHandler(), $userAccessManager->getUserGroupHandler());
        self::assertEquals($this->getAccessHandler(), $userAccessManager->getAccessHandler());
        self::assertEquals($this->getFileHandler(), $userAccessManager->getFileHandler());
        self::assertEquals($this->getSetupHandler(), $userAccessManager->getSetupHandler());
        self::assertEquals($this->getUserGroupFactory(), $userAccessManager->getUserGroupFactory());
        self::assertEquals(
            $this->getObjectMembershipHandlerFactory(),
            $userAccessManager->getObjectMembershipHandlerFactory()
        );
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

        $userHandler = $this->getUserHandler();
        $userHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $controllerFactory = $this->getControllerFactory();
        $controllerFactory->expects($this->once())
            ->method('createBackendUserGroupController');

        $controllerFactory->expects($this->once())
            ->method('createBackendSettingsController');

        $controllerFactory->expects($this->once())
            ->method('createBackendSetupController');

        $controllerFactory->expects($this->once())
            ->method('createBackendAboutController');

        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getUtil(),
            $this->getCache(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $userHandler,
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getObjectMembershipHandlerFactory(),
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
     * @covers ::addAdminActions()
     * @covers ::addAdminFilters()
     * @covers ::addAdminMetaBoxes()
     * @throws UserGroupTypeException
     */
    public function testRegisterAdminActionsAndFilters()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(70))
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
            ->will($this->returnValue(['a' => 'a', 'b' => 'b']));

        $objectHandler->expects($this->exactly(2))
            ->method('getPostTypes')
            ->will($this->returnValue([
                'a' => 'a',
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => ObjectHandler::ATTACHMENT_OBJECT_TYPE
            ]));


        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(4))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(true, false, false, false));

        $backendController = $this->createMock(BackendController::class);

        $backendController->expects($this->exactly(4))
            ->method('getRequestParameter')
            ->withConsecutive(
                ['taxonomy'],
                ['taxonomy'],
                ['taxonomy'],
                ['taxonomy']
            )
            ->will($this->onConsecutiveCalls(
                null,
                'c',
                'c',
                'c'
            ));

        $controllerFactory = $this->getControllerFactory();

        $controllerFactory->expects($this->exactly(4))
            ->method('createBackendController')
            ->will($this->returnValue($backendController));

        $objectController = $this->createMock(ObjectController::class);
        $objectController->expects($this->any())
            ->method('checkRightsToEditContent');

        $objectController->expects($this->any())
            ->method('getRequestParameter')
            ->will($this->returnValue('c'));

        $controllerFactory->expects($this->exactly(4))
            ->method('createBackendObjectController')
            ->will($this->returnValue($objectController));

        $postObjectController = $this->createMock(PostObjectController::class);

        $controllerFactory->expects($this->exactly(4))
            ->method('createBackendPostObjectController')
            ->will($this->returnValue($postObjectController));

        $termObjectController = $this->createMock(TermObjectController::class);

        $controllerFactory->expects($this->exactly(4))
            ->method('createBackendTermObjectController')
            ->will($this->returnValue($termObjectController));

        $userObjectController = $this->createMock(UserObjectController::class);

        $controllerFactory->expects($this->exactly(4))
            ->method('createBackendUserObjectController')
            ->will($this->returnValue($userObjectController));

        $dynamicGroupsController = $this->createMock(DynamicGroupsController::class);

        $controllerFactory->expects($this->exactly(4))
            ->method('createBackendDynamicGroupsController')
            ->will($this->returnValue($dynamicGroupsController));

        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getUtil(),
            $this->getCache(),
            $config,
            $this->getDatabase(),
            $objectHandler,
            $userHandler,
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getObjectMembershipHandlerFactory(),
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
        $frontendController->expects($this->exactly(6))
            ->method('getRequestParameter')
            ->withConsecutive(
                ['uamgetfile'],
                ['testXSendFile'],
                ['uamgetfile'],
                ['testXSendFile'],
                ['uamgetfile'],
                ['testXSendFile']
            )
            ->will($this->onConsecutiveCalls(
                null,
                null,
                true,
                null,
                true,
                true
            ));

        $controllerFactory = $this->getControllerFactory();
        $controllerFactory->expects($this->exactly(3))
            ->method('createFrontendController')
            ->will($this->returnValue($frontendController));

        $controllerFactory->expects($this->exactly(3))
            ->method('createBackendCacheController');

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(27))
            ->method('addAction');

        $wordpress->expects($this->exactly(85))
            ->method('addFilter');

        $wordpress->expects($this->exactly(12))
            ->method('addShortCode');

        $config = $this->getMainConfig();
        $config->expects($this->exactly(3))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls('', '', 'redirect'));

        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getUtil(),
            $this->getCache(),
            $config,
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getObjectMembershipHandlerFactory(),
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

    /**
     * @group  unit
     * @covers ::addActionsAndFilters()
     */
    public function testAddActionsAndFiltersRegistersRestFiltersOnRestApiInit()
    {
        $frontendPostController = $this->createMock(PostController::class);

        $controllerFactory = $this->getControllerFactory();
        $controllerFactory->expects($this->once())
            ->method('createFrontendPostController')
            ->will($this->returnValue($frontendPostController));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->once())
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'page' => 'page']));

        $restApiInitCallback = null;
        $registeredFilters = [];

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->any())
            ->method('addAction')
            ->will($this->returnCallback(
                function (string $tag, callable $callback) use (&$restApiInitCallback) {
                    if ($tag === 'rest_api_init') {
                        $restApiInitCallback = $callback;
                    }

                    return true;
                }
            ));
        $wordpress->expects($this->any())
            ->method('addFilter')
            ->will($this->returnCallback(
                function (
                    string $tag,
                    callable $callback,
                    int $priority = 10,
                    int $acceptedArguments = 1
                ) use (&$registeredFilters) {
                    $registeredFilters[] = [$tag, $callback, $priority, $acceptedArguments];

                    return true;
                }
            ));

        $userAccessManager = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getUtil(),
            $this->getCache(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $objectHandler,
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler(),
            $this->getSetupHandler(),
            $this->getUserGroupFactory(),
            $this->getObjectMembershipHandlerFactory(),
            $controllerFactory,
            $this->getWidgetFactory(),
            $this->getCacheProviderFactory(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory(),
            $this->getFileProtectionFactory(),
            $this->getFileObjectFactory()
        );

        $userAccessManager->addActionsAndFilters();

        self::assertIsCallable($restApiInitCallback);

        $registeredFilters = [];
        $restApiInitCallback();

        self::assertEquals(
            [
                ['rest_pre_dispatch', [$frontendPostController, 'restrictRestRequest'], 1, 3],
                ['rest_prepare_post', [$frontendPostController, 'restrictRestResponse'], 10, 3],
                ['rest_post_query', [$frontendPostController, 'excludeRestrictedPostsFromRestQuery'], 10, 1],
                ['rest_prepare_page', [$frontendPostController, 'restrictRestResponse'], 10, 3],
                ['rest_page_query', [$frontendPostController, 'excludeRestrictedPostsFromRestQuery'], 10, 1]
            ],
            $registeredFilters
        );
    }
}
