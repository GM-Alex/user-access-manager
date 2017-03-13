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
     */
    public function testCanCreateInstance()
    {
        $oStub = $this->getStub();
        $oStub->__construct(
            $this->getWrapper(),
            $this->getConfig()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\Controller', $oStub);
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
        $oConfig->expects($this->exactly(1))
            ->method('getRealPath')
            ->will($this->returnValue($sPath));

        $oDummyController = new DummyController(
            $this->getWrapper(),
            $oConfig
        );

        $_GET['uam_action'] = 'test';
        self::setValue($oDummyController, '_sTemplate', 'testView.php');
        $oDummyController->render();
        self::expectOutputString('testAction'.'testContent');
    }
}
