<?php
/**
 * AdminAboutControllerTest.php
 *
 * The AdminAboutControllerTest unit test class file.
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

/**
 * Class AdminAboutControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminAboutControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWrapper()
    {
        return $this->createMock('\UserAccessManager\Wrapper\Wordpress');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\Config
     */
    private function getConfig()
    {
        return $this->createMock('\UserAccessManager\Config\Config');
    }

    /**
     * @group  unit
     */
    public function testCanCreateInstance()
    {
        $oAdminAboutController = new AdminAboutController($this->getWrapper(), $this->getConfig());

        self::assertInstanceOf('\UserAccessManager\Controller\AdminAboutController', $oAdminAboutController);
    }
}
