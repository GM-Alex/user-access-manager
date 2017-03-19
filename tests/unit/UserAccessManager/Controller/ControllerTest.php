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

require_once __DIR__.'/../../../fixtures/src/UserAccessManager/Controller/DummyController.php';

/**
 * Class ControllerTest
 *
 * @package UserAccessManager\Controller
 */
class ControllerTest extends \UserAccessManagerTestCase
{
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
        $oStub = $this->getStub();
        $oStub->__construct(
            $this->getWrapper(),
            $this->getConfig()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\Controller', $oStub);

        return $oStub;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::getRequestParameter()
     *
     * @param Controller $oStub
     */
    public function testGetRequestParameter(Controller $oStub)
    {
        $_POST['postParam'] = 'postValue';
        $_GET['postParam'] = 'getValue';
        $_GET['getParam'] = 'getValue';

        self::assertEquals('postValue', $oStub->getRequestParameter('postParam', 'default'));
        self::assertEquals('getValue', $oStub->getRequestParameter('getParam', 'default'));
        self::assertEquals('default', $oStub->getRequestParameter('invalid', 'default'));
        self::assertNull($oStub->getRequestParameter('invalid'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::getRequestUrl()
     *
     * @param Controller $oStub
     */
    public function testGetRequestUrl(Controller $oStub)
    {
        $_SERVER['REQUEST_URI'] = 'https://test.domain?id=<a href=\'evil\'>evil</a>';

        self::assertEquals('https://test.domain?id=&lt;a href=\'evil\'&gt;evil&lt;/a&gt;', $oStub->getRequestUrl());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::createNonceField()
     */
    public function testCreateNonceField()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('getNonceField')
            ->with('test', 'testNonce')
            ->will($this->returnValue('return'));

        $oStub = $this->getStub();
        $oStub->__construct(
            $oWrapper,
            $this->getConfig()
        );

        self::assertEquals('return', $oStub->createNonceField('test'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::getNonce()
     */
    public function testGetNonce()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->once())
            ->method('createNonce')
            ->with('test')
            ->will($this->returnValue('return'));

        $oStub = $this->getStub();
        $oStub->__construct(
            $oWrapper,
            $this->getConfig()
        );

        self::assertEquals('return', $oStub->getNonce('test'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::_verifyNonce()
     */
    public function testVerifyNonce()
    {
        $_GET['testNonce'] = 'testNonceValue';

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(2))
            ->method('verifyNonce')
            ->withConsecutive(['testNonceValue', 'test'] ,['testNonceValue', 'test'])
            ->will($this->onConsecutiveCalls(false, true));

        $oWrapper->expects($this->once())
            ->method('wpDie');

        $oStub = $this->getStub();
        $oStub->__construct(
            $oWrapper,
            $this->getConfig()
        );

        self::callMethod($oStub, '_verifyNonce', ['test']);
        self::callMethod($oStub, '_verifyNonce', ['test']);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Controller\Controller::_setUpdateMessage()
     *
     * @param Controller $oStub
     *
     * @return Controller
     */
    public function testSetUpdateMessage(Controller $oStub)
    {
        self::assertAttributeEquals(null, '_sUpdateMessage', $oStub);
        self::callMethod($oStub, '_setUpdateMessage', ['updateMessage']);
        self::assertAttributeEquals('updateMessage', '_sUpdateMessage', $oStub);

        return $oStub;
    }

    /**
     * @group   unit
     * @depends testSetUpdateMessage
     * @covers  \UserAccessManager\Controller\Controller::getUpdateMessage()
     *
     * @param Controller $oStub
     */
    public function testGetUpdateMessage(Controller $oStub)
    {
        self::assertEquals('updateMessage', $oStub->getUpdateMessage());
    }

    /**
     * @group   unit
     * @depends testSetUpdateMessage
     * @covers  \UserAccessManager\Controller\Controller::hasUpdateMessage()
     *
     * @param Controller $oStub
     */
    public function testHasUpdateMessage(Controller $oStub)
    {
        self::assertTrue($oStub->hasUpdateMessage());

        $oStub = $this->getStub();
        $oStub->__construct(
            $this->getWrapper(),
            $this->getConfig()
        );

        self::assertFalse($oStub->hasUpdateMessage());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\Controller::render()
     * @covers  \UserAccessManager\Controller\Controller::_processAction()
     * @covers  \UserAccessManager\Controller\Controller::_getIncludeContents()
     */
    public function testRender()
    {
        $sPath = realpath(__DIR__.'/../../../fixtures');

        $oConfig = $this->getConfig();
        $oConfig->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue($sPath));

        $oDummyController = new DummyController(
            $this->getWrapper(),
            $oConfig
        );

        $_GET['uam_action'] = 'test';
        self::setValue($oDummyController, '_sTemplate', 'TestView.php');
        $oDummyController->render();
        self::expectOutputString('testAction'.'testContent');
    }
}
