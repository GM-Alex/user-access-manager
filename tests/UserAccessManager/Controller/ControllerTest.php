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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ControllerTest
 *
 * @package UserAccessManager\Controller
 */
class ControllerTest extends UserAccessManagerTestCase
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
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->oRoot->unmount();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Controller
     */
    private function getStub()
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\Controller\Controller',
            [],
            '',
            false,
            true,
            true
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\Controller::__construct()
     *
     * @return Controller
     */
    public function testCanCreateInstance()
    {
        $Stub = $this->getStub();
        $Stub->__construct(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\Controller', $Stub);

        return $Stub;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::getRequestParameter()
     *
     * @param Controller $Stub
     */
    public function testGetRequestParameter(Controller $Stub)
    {
        $_POST['postParam'] = 'postValue';
        $_GET['postParam'] = 'getValue';
        $_GET['getParam'] = 'getValue';

        self::assertEquals('postValue', $Stub->getRequestParameter('postParam', 'default'));
        self::assertEquals('getValue', $Stub->getRequestParameter('getParam', 'default'));
        self::assertEquals('default', $Stub->getRequestParameter('invalid', 'default'));
        self::assertNull($Stub->getRequestParameter('invalid'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::getRequestUrl()
     *
     * @param Controller $Stub
     */
    public function testGetRequestUrl(Controller $Stub)
    {
        $_SERVER['REQUEST_URI'] = 'https://test.domain?id=<a href=\'evil\'>evil</a>';

        self::assertEquals('https://test.domain?id=&lt;a href=\'evil\'&gt;evil&lt;/a&gt;', $Stub->getRequestUrl());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::createNonceField()
     */
    public function testCreateNonceField()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getNonceField')
            ->with('test', 'testNonce')
            ->will($this->returnValue('return'));

        $Stub = $this->getStub();
        $Stub->__construct(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig()
        );

        self::assertEquals('return', $Stub->createNonceField('test'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::getNonce()
     */
    public function testGetNonce()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('createNonce')
            ->with('test')
            ->will($this->returnValue('return'));

        $Stub = $this->getStub();
        $Stub->__construct(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig()
        );

        self::assertEquals('return', $Stub->getNonce('test'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::verifyNonce()
     */
    public function testVerifyNonce()
    {
        $_GET['testNonce'] = 'testNonceValue';

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(3))
            ->method('verifyNonce')
            ->withConsecutive(['testNonceValue', 'test'], ['testNonceValue', 'test'], ['testNonceValue', 'test'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $Wordpress->expects($this->once())
            ->method('wpDie');

        $Stub = $this->getStub();
        $Stub->__construct(
            $this->getPhp(),
            $Wordpress,
            $this->getConfig()
        );

        self::callMethod($Stub, 'verifyNonce', ['test']);
        self::callMethod($Stub, 'verifyNonce', ['test']);
        self::callMethod($Stub, 'verifyNonce', ['test']);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::setUpdateMessage()
     *
     * @param Controller $Stub
     *
     * @return Controller
     */
    public function testSetUpdateMessage(Controller $Stub)
    {
        self::assertAttributeEquals(null, 'sUpdateMessage', $Stub);
        self::callMethod($Stub, 'setUpdateMessage', ['updateMessage']);
        self::assertAttributeEquals('updateMessage', 'sUpdateMessage', $Stub);

        return $Stub;
    }

    /**
     * @group   unit
     * @depends testSetUpdateMessage
     * @covers  \UserAccessManager\Controller\Controller::getUpdateMessage()
     *
     * @param Controller $Stub
     */
    public function testGetUpdateMessage(Controller $Stub)
    {
        self::assertEquals('updateMessage', $Stub->getUpdateMessage());
    }

    /**
     * @group   unit
     * @depends testSetUpdateMessage
     * @covers  \UserAccessManager\Controller\Controller::hasUpdateMessage()
     *
     * @param Controller $Stub
     */
    public function testHasUpdateMessage(Controller $Stub)
    {
        self::assertTrue($Stub->hasUpdateMessage());

        $Stub = $this->getStub();
        $Stub->__construct(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig()
        );

        self::assertFalse($Stub->hasUpdateMessage());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\Controller::render()
     * @covers  \UserAccessManager\Controller\Controller::processAction()
     * @covers  \UserAccessManager\Controller\Controller::getIncludeContents()
     */
    public function testRender()
    {
        /**
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('src', new Directory([
            'UserAccessManager'  => new Directory([
                'View'  => new Directory([
                    'TestView.php' => new File('<?php echo \'testContent\';')
                ])
            ])
        ]));

        $Php = $this->getPhp();

        $Config = $this->getConfig();
        $Config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $DummyController = new DummyController(
            $Php,
            $this->getWordpress(),
            $Config
        );

        $Php->expects($this->once())
            ->method('includeFile')
            ->with($DummyController, 'vfs://src/UserAccessManager/View/TestView.php')
            ->will($this->returnCallback(function () {
                echo 'testContent';
            }));

        $_GET['uam_action'] = 'test';
        self::setValue($DummyController, 'sTemplate', 'TestView.php');
        $DummyController->render();
        self::expectOutputString('testAction'.'testContent');
    }
}
