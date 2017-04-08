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
 * @version   SVN: $Id$
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
        $ObjectHandler = new UserAccessManager(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getSetupHandler(),
            $this->getControllerFactory()
        );

        self::assertInstanceOf('\UserAccessManager\UserAccessManager', $ObjectHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserAccessManager::registerAdminMenu()
     */
    public function testRegisterAdminMenu()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('addMenuPage');
        $Wordpress->expects($this->exactly(4))
            ->method('addSubmenuPage');
        $Wordpress->expects($this->once())
            ->method('doAction');

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(false, true));

        $ControllerFactory = $this->getControllerFactory();
        $ControllerFactory->expects($this->once())
            ->method('createAdminUserGroupController');

        $ControllerFactory->expects($this->once())
            ->method('createAdminSettingsController');

        $ControllerFactory->expects($this->once())
            ->method('createAdminSetupController');

        $ControllerFactory->expects($this->once())
            ->method('createAdminAboutController');

        $ObjectHandler = new UserAccessManager(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $this->getObjectHandler(),
            $AccessHandler,
            $this->getSetupHandler(),
            $ControllerFactory
        );

        $ObjectHandler->registerAdminMenu();
        $ObjectHandler->registerAdminMenu();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserAccessManager::registerAdminActionsAndFilters()
     */
    public function testRegisterAdminActionsAndFilters()
    {
        $Php = $this->getPhp();
        $Php->expects($this->exactly(3))
            ->method('iniGet')
            ->will($this->returnValue(true));

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(57))
            ->method('addAction');

        $Wordpress->expects($this->exactly(16))
            ->method('addFilter');

        $Wordpress->expects($this->exactly(3))
            ->method('addMetaBox');

        $Config = $this->getConfig();
        $Config->expects($this->exactly(3))
            ->method('getDownloadType')
            ->will($this->onConsecutiveCalls(null, 'fopen', 'fopen'));

        $Config->expects($this->exactly(2))
            ->method('authorsCanAddPostsToGroups')
            ->will($this->onConsecutiveCalls(true, false));

        $Config->expects($this->exactly(6))
            ->method('lockFile')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true));


        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->exactly(3))
            ->method('getTaxonomies')
            ->will($this->returnValue(['a', 'b']));

        $ObjectHandler->expects($this->exactly(2))
            ->method('getPostTypes')
            ->will($this->returnValue(['a', ObjectHandler::ATTACHMENT_OBJECT_TYPE]));

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(3))
            ->method('checkUserAccess')
            ->will($this->onConsecutiveCalls(true, false, false));

        $SetupHandler = $this->getSetupHandler();
        $SetupHandler->expects($this->exactly(3))
            ->method('isDatabaseUpdateNecessary')
            ->will($this->onConsecutiveCalls(false, true, false));

        $AdminController = $this->createMock('UserAccessManager\Controller\AdminController');
        $AdminController->expects($this->exactly(3))
            ->method('getRequestParameter')
            ->will($this->onConsecutiveCalls(null, 'c', 'c'));

        $ControllerFactory = $this->getControllerFactory();
        $ControllerFactory->expects($this->exactly(3))
            ->method('createAdminController')
            ->will($this->returnValue($AdminController));

        $ControllerFactory->expects($this->exactly(3))
            ->method('createAdminObjectController')
            ->will($this->returnCallback(function () {
                $AdminObjectController = $this->createMock('UserAccessManager\Controller\AdminObjectController');
                $AdminObjectController->expects($this->any())
                    ->method('checkRightsToEditContent');

                $AdminObjectController->expects($this->any())
                    ->method('getRequestParameter')
                    ->will($this->returnValue('c'));

                return $AdminObjectController;
            }));

        $ObjectHandler = new UserAccessManager(
            $Php,
            $Wordpress,
            $Config,
            $ObjectHandler,
            $AccessHandler,
            $SetupHandler,
            $ControllerFactory
        );

        $ObjectHandler->registerAdminActionsAndFilters();
        $ObjectHandler->registerAdminActionsAndFilters();
        $ObjectHandler->registerAdminActionsAndFilters();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserAccessManager::addActionsAndFilters()
     */
    public function testAddActionsAndFilters()
    {
        $FrontendController = $this->createMock('UserAccessManager\Controller\FrontendController');
        $FrontendController->expects($this->exactly(3))
            ->method('getRequestParameter')
            ->will($this->onConsecutiveCalls(null, true, true));

        $ControllerFactory = $this->getControllerFactory();
        $ControllerFactory->expects($this->exactly(3))
            ->method('createFrontendController')
            ->will($this->returnValue($FrontendController));

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(21))
            ->method('addAction');

        $Wordpress->expects($this->exactly(62))
            ->method('addFilter');

        $Config = $this->getConfig();
        $Config->expects($this->exactly(3))
            ->method('getRedirect')
            ->will($this->onConsecutiveCalls(false, false, true));

        $ObjectHandler = new UserAccessManager(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getSetupHandler(),
            $ControllerFactory
        );

        $ObjectHandler->addActionsAndFilters();
        $ObjectHandler->addActionsAndFilters();
        $ObjectHandler->addActionsAndFilters();
    }
}
