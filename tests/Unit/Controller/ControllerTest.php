<?php
/**
 * ControllerTest.php
 *
 * The ControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Controller\Backend\BackendController;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller
 * @coversDefaultClass \UserAccessManager\Controller\Controller
 */
class ControllerTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

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
     * @return MockObject|Controller
     */
    private function getStub()
    {
        return $this->getMockForAbstractClass(
            Controller::class,
            [],
            '',
            false,
            true,
            true
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     * @return Controller
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub();
        $stub->__construct(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertInstanceOf(Controller::class, $stub);

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::getPhp()
     * @covers ::getWordpressConfig()
     * @throws ReflectionException
     */
    public function testSimpleGetters()
    {
        $stub = $this->getStub();
        $stub->__construct(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertEquals($this->getPhp(), self::callMethod($stub, 'getPhp'));
        self::assertEquals($this->getWordpressConfig(), self::callMethod($stub, 'getWordpressConfig'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createNonceField()
     */
    public function testCreateNonceField()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getNonceField')
            ->with('test', 'testNonce')
            ->will($this->returnValue('return'));

        $stub = $this->getStub();
        $stub->__construct(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig()
        );

        self::assertEquals('return', $stub->createNonceField('test'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getNonce()
     */
    public function testGetNonce()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('createNonce')
            ->with('test')
            ->will($this->returnValue('return'));

        $stub = $this->getStub();
        $stub->__construct(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig()
        );

        self::assertEquals('return', $stub->getNonce('test'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::verifyNonce()
     * @throws ReflectionException
     */
    public function testVerifyNonce()
    {
        $_GET['testNonce'] = 'testNonceValue';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('verifyNonce')
            ->withConsecutive(['testNonceValue', 'test'], ['testNonceValue', 'test'], ['testNonceValue', 'test'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_NONCE_FAILURE_MESSAGE, TXT_UAM_NONCE_FAILURE_TITLE, ['response' => 401]);

        $stub = $this->getStub();
        $stub->__construct(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig()
        );

        self::callMethod($stub, 'verifyNonce', ['test']);
        self::callMethod($stub, 'verifyNonce', ['test']);
        self::callMethod($stub, 'verifyNonce', ['test']);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::setUpdateMessage()
     * @param Controller $stub
     * @return Controller
     * @throws ReflectionException
     */
    public function testSetUpdateMessage(Controller $stub): Controller
    {
        self::assertEquals(null, $stub->getUpdateMessage());
        self::callMethod($stub, 'setUpdateMessage', ['updateMessage']);
        self::assertEquals('updateMessage', $stub->getUpdateMessage());

        return $stub;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::addErrorMessage()
     * @param Controller $stub
     * @throws ReflectionException
     */
    public function testAddErrorMessage(Controller $stub)
    {
        if (defined('_SESSION')) {
            unset($_SESSION[BackendController::UAM_ERRORS]);
        }

        self::callMethod($stub, 'addErrorMessage', ['errorMessageOne']);
        self::callMethod($stub, 'addErrorMessage', ['errorMessageTwo']);
        self::assertEquals(['errorMessageOne', 'errorMessageTwo'], $_SESSION[BackendController::UAM_ERRORS]);
    }

    /**
     * @group   unit
     * @depends testSetUpdateMessage
     * @covers  ::getUpdateMessage()
     * @param Controller $stub
     */
    public function testGetUpdateMessage(Controller $stub)
    {
        self::assertEquals('updateMessage', $stub->getUpdateMessage());
    }

    /**
     * @group   unit
     * @depends testSetUpdateMessage
     * @covers  ::hasUpdateMessage()
     * @param Controller $stub
     */
    public function testHasUpdateMessage(Controller $stub)
    {
        self::assertTrue($stub->hasUpdateMessage());

        $stub = $this->getStub();
        $stub->__construct(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertFalse($stub->hasUpdateMessage());
    }

    /**
     * @group   unit
     * @covers  ::render()
     * @covers  ::processAction()
     * @covers  ::getIncludeContents()
     * @throws ReflectionException
     */
    public function testRender()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('root', new Directory([
            'src' => new Directory([
                'View' => new Directory([
                    'TestView.php' => new File('<?php echo \'testContent\';')
                ])
            ])
        ]));

        $php = $this->getPhp();

        $config = $this->getWordpressConfig();
        $config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs://root/'));

        $dummyController = new DummyController(
            $php,
            $this->getWordpress(),
            $config
        );

        $php->expects($this->once())
            ->method('includeFile')
            ->with($dummyController, 'vfs://root/src/View/TestView.php')
            ->will($this->returnCallback(function () {
                echo 'testContent';
            }));

        $_GET['uam_action'] = 'test';
        self::setValue($dummyController, 'template', 'TestView.php');
        $dummyController->render();
        self::expectOutputString('testAction' . 'testContent');
    }
}
