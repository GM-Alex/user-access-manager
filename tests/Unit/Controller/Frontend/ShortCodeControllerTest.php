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

namespace UserAccessManager\Tests\Unit\Controller\Frontend;

use ReflectionException;
use UserAccessManager\Controller\Frontend\ShortCodeController;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroupTypeException;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ShortCodeControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\ShortCodeController
 */
class ShortCodeControllerTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private FileSystem $root;

    /**
     * Setup virtual file system.
     */
    protected function setUp(): void
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    protected function tearDown(): void
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
            $this->getWordpressConfig(),
            $this->getUserGroupHandler()
        );

        self::assertInstanceOf(ShortCodeController::class, $frontendShortCodeController);
    }

    /**
     * @group  unit
     * @covers ::getWordpress()
     * @throws ReflectionException
     */
    public function testSimpleGetters()
    {
        $frontendShortCodeController = new ShortCodeController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getUserGroupHandler()
        );

        self::assertEquals($this->getWordpress(), self::callMethod($frontendShortCodeController, 'getWordpress'));
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
        $rootDir->add('root', new Directory([
            'src' => new Directory([
                'View' => new Directory([
                    'LoginForm.php' => new File('<?php echo \'LoginForm\';')
                ])
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


        $config = $this->getWordpressConfig();

        $config->expects($this->exactly(2))
            ->method('getRealPath')
            ->will($this->returnValue('vfs://root/'));

        $frontendShortCodeController = new ShortCodeController(
            $php,
            $wordpress,
            $config,
            $this->getUserGroupHandler()
        );

        $php->expects($this->exactly(2))
            ->method('includeFile')
            ->with($frontendShortCodeController, 'vfs://root/src/View/LoginForm.php')
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
            $this->getWordpressConfig(),
            $this->getUserGroupHandler()
        );

        self::assertEquals('', $frontendShortCodeController->publicShortCode([], 'content'));
        self::assertEquals('contentShortCode', $frontendShortCodeController->publicShortCode([], 'content'));
    }

    /**
     * @group  unit
     * @covers ::privateShortCode()
     * @covers ::getUserGroupsMapFromAttributes()
     * @throws UserGroupTypeException
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

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->exactly(3))
            ->method('getUserGroupsForUser')
            ->will($this->returnValue([
                $this->getUserGroup(1),
                $this->getUserGroup(2)
            ]));

        $frontendShortCodeController = new ShortCodeController(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $userGroupHandler
        );

        self::assertEquals('', $frontendShortCodeController->privateShortCode([], 'content'));
        self::assertEquals('contentShortCode', $frontendShortCodeController->privateShortCode([], 'content'));
        self::assertEquals(
            'contentShortCode',
            $frontendShortCodeController->privateShortCode(['group' => 'name3,1 '], 'content')
        );
        self::assertEquals(
            'contentShortCode',
            $frontendShortCodeController->privateShortCode(['group' => '3,name2'], 'content')
        );
        self::assertEquals('', $frontendShortCodeController->privateShortCode(['group' => '10,9'], 'content'));
    }
}
