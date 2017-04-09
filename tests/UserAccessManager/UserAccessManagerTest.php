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
        $objectHandler = new UserAccessManager(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getSetupHandler(),
            $this->getControllerFactory()
        );

        self::assertInstanceOf('\UserAccessManager\UserAccessManager', $objectHandler);
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

        $objectHandler = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $this->getObjectHandler(),
            $accessHandler,
            $this->getSetupHandler(),
            $controllerFactory
        );

        $objectHandler->registerAdminMenu();
        $objectHandler->registerAdminMenu();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserAccessManager::registerAdminActionsAndFilters()
     */
    public function testRegisterAdminActionsAndFilters()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(3))
            ->method('iniGet')
            ->will($this->returnValue(true));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(57))
            ->method('addAction');

        $wordpress->expects($this->exactly(16))
            ->method('addFilter');

        $wordpress->expects($this->exactly(3))
            ->method('addMetaBox');

        $config = $this->getConfig();
        $config->expects($this->exactly(3))
            ->method('getDownloadType')
            ->will($this->onConsecutiveCalls(null, 'fopen', 'fopen'));

        $config->expects($this->exactly(2))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(true, false));

        $config->expects($this->exactly(6))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true));


        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(3))
            ->method('getTaxonomies')
            ->will($this->returnValue(['a', 'b']));

        $objectHandler->expects($this->exactly(2))
            ->method('getPostTypes')
            ->will($this->returnValue(['a', ObjectHandler::ATTACHMENT_OBJECT_TYPE]));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(3))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(true, false, false));

        $setupHandler = $this->getSetupHandler();
        $setupHandler->expects($this->exactly(3))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(false, true, false));

        $adminController = $this->createMock('UserAccessManager\Controller\AdminController');
        $adminController->expects($this->exactly(3))
            ->method('getRequestParameter')
            ->will($this->onConsecutiveCalls(null, 'c', 'c'));

        $controllerFactory = $this->getControllerFactory();
        $controllerFactory->expects($this->exactly(3))
            ->method('createAdminController')
            ->will($this->returnValue($adminController));

        $controllerFactory->expects($this->exactly(3))
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

        $objectHandler = new UserAccessManager(
            $php,
            $wordpress,
            $config,
            $objectHandler,
            $accessHandler,
            $setupHandler,
            $controllerFactory
        );

        $objectHandler->registerAdminActionsAndFilters();
        $objectHandler->registerAdminActionsAndFilters();
        $objectHandler->registerAdminActionsAndFilters();
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

        $wordpress->expects($this->exactly(62))
            ->method('addFilter');

        $config = $this->getConfig();
        $config->expects($this->exactly(3))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls(false, false, true));

        $objectHandler = new UserAccessManager(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getSetupHandler(),
            $controllerFactory
        );

        $objectHandler->addActionsAndFilters();
        $objectHandler->addActionsAndFilters();
        $objectHandler->addActionsAndFilters();
    }
}
