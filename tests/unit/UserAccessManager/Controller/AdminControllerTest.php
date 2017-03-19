<?php
/**
 * AdminControllerTest.php
 *
 * The AdminControllerTest unit test class file.
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

use UserAccessManager\UserAccessManager;

/**
 * Class AdminControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::__construct()
     */
    public function testCanCreateInstance()
    {
        $oAdminController = new AdminController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminController', $oAdminController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::showFOpenNotice()
     *
     * @return AdminController
     */
    public function testShowFOpenNotice()
    {
        $sPath = realpath(__DIR__.'/../../../fixtures');

        $oConfig = $this->getConfig();
        $oConfig->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue($sPath));

        $oAdminController = new AdminController(
            $this->getWrapper(),
            $oConfig,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $oAdminController->showFOpenNotice();
        self::assertAttributeEquals(TXT_UAM_FOPEN_WITHOUT_SAVE_MODE_OFF, '_sNotice', $oAdminController);
        self::expectOutputString('adminNotice');

        return $oAdminController;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::showDatabaseNotice()
     *
     * @return AdminController
     */
    public function testShowDatabaseNotice()
    {
        $sPath = realpath(__DIR__.'/../../../fixtures');

        $oConfig = $this->getConfig();
        $oConfig->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue($sPath));

        $oAdminController = new AdminController(
            $this->getWrapper(),
            $oConfig,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $oAdminController->showDatabaseNotice();
        self::assertAttributeEquals(
            sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup'),
            '_sNotice',
            $oAdminController
        );
        self::expectOutputString('adminNotice');

        return $oAdminController;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminController::getNotice()
     * @depends testShowFOpenNotice
     * @depends testShowDatabaseNotice
     *
     * @param AdminController $oFOpenNoticeAdminController
     * @param AdminController $oDatabaseNoticeAdminController
     */
    public function testGetNotice(
        AdminController $oFOpenNoticeAdminController,
        AdminController $oDatabaseNoticeAdminController
    )
    {
        self::assertEquals(TXT_UAM_FOPEN_WITHOUT_SAVE_MODE_OFF, $oFOpenNoticeAdminController->getNotice());
        self::assertEquals(
            sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup'),
            $oDatabaseNoticeAdminController->getNotice()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::_registerStylesAndScripts()
     * @covers \UserAccessManager\Controller\AdminController::enqueueStylesAndScripts()
     */
    public function testStylesAndScripts()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(3))
            ->method('registerStyle')
            ->with(
                AdminController::HANDLE_STYLE_ADMIN,
                'url/assets/css/uamAdmin.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            )
            ->will($this->returnValue('a'));

        $oWrapper->expects($this->exactly(3))
            ->method('registerScript')
            ->with(
                AdminController::HANDLE_SCRIPT_ADMIN,
                'url/assets/js/functions.js',
                ['jquery'],
                UserAccessManager::VERSION
            );

        $oWrapper->expects($this->exactly(3))
            ->method('enqueueStyle')
            ->with(AdminController::HANDLE_STYLE_ADMIN);

        $oWrapper->expects($this->exactly(2))
            ->method('enqueueScript')
            ->with(AdminController::HANDLE_SCRIPT_ADMIN);

        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(3))
            ->method('getUrlPath')
            ->will($this->returnValue('url/'));

        $oAdminController = new AdminController(
            $oWrapper,
            $oConfig,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $oAdminController->enqueueStylesAndScripts('someHook');
        $oAdminController->enqueueStylesAndScripts('uam_page_uam_settings');
        $oAdminController->enqueueStylesAndScripts('uam_page_uam_setup');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::setupAdminDashboard()
     */
    public function testSetupAdminDashboard()
    {
        global $aMetaBoxes;
        $aMetaBoxes = [
            'dashboard' => [
                'normal' => [
                    'core' => [
                        'dashboard_recent_comments' => true
                    ]
                ]
            ]
        ];
        $aOriginalMetaBoxes = $aMetaBoxes;

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('getMetaBoxes')
            ->will($this->returnCallback(function () {
                global $aMetaBoxes;
                return $aMetaBoxes;
            }));

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(2))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(true, false));

        $oAdminController = new AdminController(
            $oWrapper,
            $this->getConfig(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        $oAdminController->setupAdminDashboard();
        self::assertEquals($aOriginalMetaBoxes, $aMetaBoxes);
        $oAdminController->setupAdminDashboard();
        self::assertEquals($aOriginalMetaBoxes, $aMetaBoxes);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::updatePermalink()
     */
    public function testUpdatePermalink()
    {
        $oFileHandler = $this->getFileHandler();
        $oFileHandler->expects($this->once())
            ->method('createFileProtection');

        $oAdminController = new AdminController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $oFileHandler
        );

        $oAdminController->updatePermalink();
    }
}
