<?php
/**
 * ShortCodeControllerTest.php
 *
 * The ShortCodeControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller\Frontend;

use UserAccessManager\Controller\Frontend\ShortCodeController;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ShortCodeControllerTest
 *
 * @package UserAccessManager\Tests\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\ShortCodeController
 */
class ShortCodeControllerTest extends UserAccessManagerTestCase
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
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $frontendShortCodeController = new ShortCodeController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf(ShortCodeController::class, $frontendShortCodeController);
    }

    /**
     * @group  unit
     * @covers ::getLoginFormHtml()
     * @covers ::loginFormShortCode()
     */
    public function testGetLoginFormHtml()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('src', new Directory([
            'View' => new Directory([
                'LoginForm.php' => new File('<?php echo \'LoginForm\';')
            ])
        ]));

        $php = $this->getPhp();

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(true, true, false, false));

        $wordpress->expects($this->exactly(4))
            ->method('applyFilters')
            ->withConsecutive(
                ['uam_login_form', ''],
                ['uam_login_form', ''],
                ['uam_login_form', 'LoginForm'],
                ['uam_login_form', 'LoginForm']
            )
            ->will($this->onConsecutiveCalls('filter', 'filter', 'LoginFormWithFilter', 'LoginFormWithFilter'));


        $config = $this->getMainConfig();

        $config->expects($this->exactly(2))
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $frontendShortCodeController = new ShortCodeController(
            $php,
            $wordpress,
            $config,
            $this->getAccessHandler()
        );

        $php->expects($this->exactly(2))
            ->method('includeFile')
            ->with($frontendShortCodeController, 'vfs://src/View/LoginForm.php')
            ->will($this->returnCallback(function () {
                echo 'LoginForm';
            }));

        self::assertEquals('filter', $frontendShortCodeController->getLoginFormHtml());
        self::assertEquals('filter', $frontendShortCodeController->loginFormShortCode());
        self::assertEquals('LoginFormWithFilter', $frontendShortCodeController->getLoginFormHtml());
        self::assertEquals('LoginFormWithFilter', $frontendShortCodeController->loginFormShortCode());
    }

    /**
     * @group  unit
     * @covers ::publicShortCode()
     */
    public function testPublicShortCode()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(true, false));

        $wordpress->expects($this->once())
            ->method('doShortCode')
            ->with('content')
            ->will($this->returnValue('contentShortCode'));

        $frontendShortCodeController = new ShortCodeController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $this->getAccessHandler()
        );

        self::assertEquals('', $frontendShortCodeController->publicShortCode([], 'content'));
        self::assertEquals('contentShortCode', $frontendShortCodeController->publicShortCode([], 'content'));
    }

    /**
     * @group  unit
     * @covers ::privateShortCode()
     * @covers ::getUserGroupsMapFromAttributes()
     */
    public function testPrivateShortCode()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(5))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $wordpress->expects($this->exactly(3))
            ->method('doShortCode')
            ->with('content')
            ->will($this->returnValue('contentShortCode'));

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(3))
            ->method('getUserGroupsForUser')
            ->will($this->returnValue([
                $this->getUserGroup(1),
                $this->getUserGroup(2)
            ]));

        $frontendShortCodeController = new ShortCodeController(
            $this->getPhp(),
            $wordpress,
            $this->getMainConfig(),
            $accessHandler
        );

        self::assertEquals('', $frontendShortCodeController->privateShortCode([], 'content'));
        self::assertEquals('contentShortCode', $frontendShortCodeController->privateShortCode([], 'content'));
        self::assertEquals(
            'contentShortCode',
            $frontendShortCodeController->privateShortCode(['group' => 'name3,1'], 'content')
        );
        self::assertEquals(
            'contentShortCode',
            $frontendShortCodeController->privateShortCode(['group' => '3,name2'], 'content')
        );
        self::assertEquals('', $frontendShortCodeController->privateShortCode(['group' => '10'], 'content'));
    }
}
