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
use UserAccessManager\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class AdminControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminControllerTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $Root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->oRoot = FileSystem::factory('vfs://');
        $this->oRoot->mount();

        /**
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('src', new Directory([
            'UserAccessManager'  => new Directory([
                'View'  => new Directory([
                    'AdminNotice.php' => new File('<?php echo \'AdminNotice\';')
                ])
            ])
        ]));
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->oRoot->unmount();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::__construct()
     */
    public function testCanCreateInstance()
    {
        $AdminController = new AdminController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminController', $AdminController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::showFOpenNotice()
     *
     * @return AdminController
     */
    public function testShowFOpenNotice()
    {
        $Php = $this->getPhp();


        $Config = $this->getConfig();
        $Config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $AdminController = new AdminController(
            $Php,
            $this->getWordpress(),
            $Config,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $Php->expects($this->once())
            ->method('includeFile')
            ->with($AdminController, 'vfs://src/UserAccessManager/View/AdminNotice.php')
            ->will($this->returnCallback(function () {
                echo 'FOpenNotice';
            }));

        $AdminController->showFOpenNotice();
        self::assertAttributeEquals(TXT_UAM_FOPEN_WITHOUT_SAVE_MODE_OFF, 'sNotice', $AdminController);
        self::expectOutputString('FOpenNotice');

        return $AdminController;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::showDatabaseNotice()
     *
     * @return AdminController
     */
    public function testShowDatabaseNotice()
    {
        $Php = $this->getPhp();

        $Config = $this->getConfig();
        $Config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $AdminController = new AdminController(
            $Php,
            $this->getWordpress(),
            $Config,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $Php->expects($this->once())
            ->method('includeFile')
            ->with($AdminController, 'vfs://src/UserAccessManager/View/AdminNotice.php')
            ->will($this->returnCallback(function () {
                echo 'DatabaseNotice';
            }));

        $AdminController->showDatabaseNotice();

        self::assertAttributeEquals(
            sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup'),
            'sNotice',
            $AdminController
        );
        self::expectOutputString('DatabaseNotice');

        return $AdminController;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminController::getNotice()
     * @depends testShowFOpenNotice
     * @depends testShowDatabaseNotice
     *
     * @param AdminController $FOpenNoticeAdminController
     * @param AdminController $DatabaseNoticeAdminController
     */
    public function testGetNotice(
        AdminController $FOpenNoticeAdminController,
        AdminController $DatabaseNoticeAdminController
    ) {
        self::assertEquals(TXT_UAM_FOPEN_WITHOUT_SAVE_MODE_OFF, $FOpenNoticeAdminController->getNotice());
        self::assertEquals(
            sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup'),
            $DatabaseNoticeAdminController->getNotice()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::registerStylesAndScripts()
     * @covers \UserAccessManager\Controller\AdminController::enqueueStylesAndScripts()
     */
    public function testStylesAndScripts()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(4))
            ->method('registerStyle')
            ->with(
                AdminController::HANDLE_STYLE_ADMIN,
                'url/assets/css/uamAdmin.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            )
            ->will($this->returnValue('a'));

        $Wordpress->expects($this->exactly(4))
            ->method('registerScript')
            ->with(
                AdminController::HANDLE_SCRIPT_ADMIN,
                'url/assets/js/functions.js',
                ['jquery'],
                UserAccessManager::VERSION
            );

        $Wordpress->expects($this->exactly(4))
            ->method('enqueueStyle')
            ->with(AdminController::HANDLE_STYLE_ADMIN);

        $Wordpress->expects($this->exactly(2))
            ->method('enqueueScript')
            ->with(AdminController::HANDLE_SCRIPT_ADMIN);

        $Config = $this->getConfig();
        $Config->expects($this->exactly(4))
            ->method('getUrlPath')
            ->will($this->returnValue('url/'));

        $AdminController = new AdminController(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $AdminController->enqueueStylesAndScripts('someHook');
        $AdminController->enqueueStylesAndScripts('someHook');
        $AdminController->enqueueStylesAndScripts('uam_page_uam_settings');
        $AdminController->enqueueStylesAndScripts('uam_page_uam_setup');
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

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getMetaBoxes')
            ->will($this->returnCallback(function () {
                global $aMetaBoxes;
                return $aMetaBoxes;
            }));

        $Wordpress->expects($this->once())
            ->method('setMetaBoxes')
            ->will($this->returnCallback(function ($aNewMetaBoxes) {
                global $aMetaBoxes;
                $aMetaBoxes = $aNewMetaBoxes;
            }));

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(3))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(true, true, false));

        $AdminController = new AdminController(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig(),
            $AccessHandler,
            $this->getFileHandler()
        );

        $AdminController->setupAdminDashboard();
        self::assertEquals($aOriginalMetaBoxes, $aMetaBoxes);

        $AdminController->setupAdminDashboard();
        self::assertEquals($aOriginalMetaBoxes, $aMetaBoxes);

        $AdminController->setupAdminDashboard();
        unset($aOriginalMetaBoxes['dashboard']['normal']['core']['dashboard_recent_comments']);
        self::assertEquals($aOriginalMetaBoxes, $aMetaBoxes);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::updatePermalink()
     */
    public function testUpdatePermalink()
    {
        $FileHandler = $this->getFileHandler();
        $FileHandler->expects($this->once())
            ->method('createFileProtection');

        $AdminController = new AdminController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $FileHandler
        );

        $AdminController->updatePermalink();
    }
}
