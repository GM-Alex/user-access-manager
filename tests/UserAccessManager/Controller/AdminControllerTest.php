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
 * @version   SVN: $id$
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
    private $root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('src', new Directory([
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
        $this->root->unmount();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::__construct()
     */
    public function testCanCreateInstance()
    {
        $adminController = new AdminController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminController', $adminController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::showFOpenNotice()
     *
     * @return AdminController
     */
    public function testShowFOpenNotice()
    {
        $php = $this->getPhp();


        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $adminController = new AdminController(
            $php,
            $this->getWordpress(),
            $config,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $php->expects($this->once())
            ->method('includeFile')
            ->with($adminController, 'vfs://src/UserAccessManager/View/AdminNotice.php')
            ->will($this->returnCallback(function () {
                echo 'FOpenNotice';
            }));

        $adminController->showFOpenNotice();
        self::assertAttributeEquals(TXT_UAM_FOPEN_WITHOUT_SAVE_MODE_OFF, 'notice', $adminController);
        self::expectOutputString('FOpenNotice');

        return $adminController;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::showDatabaseNotice()
     *
     * @return AdminController
     */
    public function testShowDatabaseNotice()
    {
        $php = $this->getPhp();

        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $adminController = new AdminController(
            $php,
            $this->getWordpress(),
            $config,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $php->expects($this->once())
            ->method('includeFile')
            ->with($adminController, 'vfs://src/UserAccessManager/View/AdminNotice.php')
            ->will($this->returnCallback(function () {
                echo 'DatabaseNotice';
            }));

        $adminController->showDatabaseNotice();

        self::assertAttributeEquals(
            sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup'),
            'notice',
            $adminController
        );
        self::expectOutputString('DatabaseNotice');

        return $adminController;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminController::getNotice()
     * @depends testShowFOpenNotice
     * @depends testShowDatabaseNotice
     *
     * @param AdminController $fOpenNoticeAdminController
     * @param AdminController $databaseNoticeAdminController
     */
    public function testGetNotice(
        AdminController $fOpenNoticeAdminController,
        AdminController $databaseNoticeAdminController
    ) {
        self::assertEquals(TXT_UAM_FOPEN_WITHOUT_SAVE_MODE_OFF, $fOpenNoticeAdminController->getNotice());
        self::assertEquals(
            sprintf(TXT_UAM_NEED_DATABASE_UPDATE, 'admin.php?page=uam_setup'),
            $databaseNoticeAdminController->getNotice()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::registerStylesAndScripts()
     * @covers \UserAccessManager\Controller\AdminController::enqueueStylesAndScripts()
     */
    public function testStylesAndScripts()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('registerStyle')
            ->with(
                AdminController::HANDLE_STYLE_ADMIN,
                'url/assets/css/uamAdmin.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            )
            ->will($this->returnValue('a'));

        $wordpress->expects($this->exactly(4))
            ->method('registerScript')
            ->with(
                AdminController::HANDLE_SCRIPT_ADMIN,
                'url/assets/js/functions.js',
                ['jquery'],
                UserAccessManager::VERSION
            );

        $wordpress->expects($this->exactly(4))
            ->method('enqueueStyle')
            ->with(AdminController::HANDLE_STYLE_ADMIN);

        $wordpress->expects($this->exactly(2))
            ->method('enqueueScript')
            ->with(AdminController::HANDLE_SCRIPT_ADMIN);

        $config = $this->getConfig();
        $config->expects($this->exactly(4))
            ->method('getUrlPath')
            ->will($this->returnValue('url/'));

        $adminController = new AdminController(
            $this->getPhp(),
            $wordpress,
            $config,
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $adminController->enqueueStylesAndScripts('someHook');
        $adminController->enqueueStylesAndScripts('someHook');
        $adminController->enqueueStylesAndScripts('uam_page_uam_settings');
        $adminController->enqueueStylesAndScripts('uam_page_uam_setup');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::setupAdminDashboard()
     */
    public function testSetupAdminDashboard()
    {
        global $metaBoxes;
        $metaBoxes = [
            'dashboard' => [
                'normal' => [
                    'core' => [
                        'dashboard_recent_comments' => true
                    ]
                ]
            ]
        ];
        $originalMetaBoxes = $metaBoxes;

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getMetaBoxes')
            ->will($this->returnCallback(function () {
                global $metaBoxes;
                return $metaBoxes;
            }));

        $wordpress->expects($this->once())
            ->method('setMetaBoxes')
            ->will($this->returnCallback(function ($newMetaBoxes) {
                global $metaBoxes;
                $metaBoxes = $newMetaBoxes;
            }));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(3))
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->onConsecutiveCalls(true, true, false));

        $adminController = new AdminController(
            $this->getPhp(),
            $wordpress,
            $this->getConfig(),
            $accessHandler,
            $this->getFileHandler()
        );

        $adminController->setupAdminDashboard();
        self::assertEquals($originalMetaBoxes, $metaBoxes);

        $adminController->setupAdminDashboard();
        self::assertEquals($originalMetaBoxes, $metaBoxes);

        $adminController->setupAdminDashboard();
        unset($originalMetaBoxes['dashboard']['normal']['core']['dashboard_recent_comments']);
        self::assertEquals($originalMetaBoxes, $metaBoxes);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminController::updatePermalink()
     */
    public function testUpdatePermalink()
    {
        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->once())
            ->method('createFileProtection');

        $adminController = new AdminController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getAccessHandler(),
            $fileHandler
        );

        $adminController->updatePermalink();
    }
}
