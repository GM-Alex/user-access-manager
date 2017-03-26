<?php
/**
 * FrontendControllerTest.php
 *
 * The FrontendControllerTest unit test class file.
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
 * Class FrontendControllerTest
 *
 * @package UserAccessManager\Controller
 */
class FrontendControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::__construct()
     */
    public function testCanCreateInstance()
    {
        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\FrontendController', $oFrontendController);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::enqueueStylesAndScripts()
     * @covers \UserAccessManager\Controller\FrontendController::_registerStylesAndScripts()
     */
    public function testEnqueueStylesAndScripts()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('registerStyle')
            ->with(
                FrontendController::HANDLE_STYLE_LOGIN_FORM,
                'http://url/assets/css/uamLoginForm.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            );

        $oWordpress->expects($this->once())
            ->method('enqueueStyle')
            ->with(FrontendController::HANDLE_STYLE_LOGIN_FORM);

        $oConfig = $this->getConfig();
        $oConfig->expects($this->once())
            ->method('getUrlPath')
            ->will($this->returnValue('http://url/'));

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getAccessHandler(),
            $this->getFileHandler()
        );

        $oFrontendController->enqueueStylesAndScripts();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\FrontendController::parseQuery()
     */
    public function testParseQuery()
    {
        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->once())
            ->method('getExcludedPosts')
            ->willReturn([2, 3, 5]);

        $oFrontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getUtil(),
            $this->getCache(),
            $this->getObjectHandler(),
            $oAccessHandler,
            $this->getFileHandler()
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\WP_Query $oWpQuery
         */
        $oWpQuery = $this->getMockBuilder('\WP_Query')->getMock();
        $oWpQuery->query_vars = [
            'post__not_in' => [1, 2, 4]
        ];

        $oFrontendController->parseQuery($oWpQuery);
        self::assertEquals([1, 2, 3, 4, 5], $oWpQuery->query_vars['post__not_in'], '', 0.0, 10, true);
    }
}
