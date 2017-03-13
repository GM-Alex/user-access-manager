<?php
/**
 * AdminSettingsControllerTest.php
 *
 * The AdminSettingsControllerTest unit test class file.
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
 * Class AdminSettingsControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminSettingsControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminSettingsController::__construct()
     */
    public function testCanCreateInstance()
    {
        $oAdminSettingController = new AdminSettingsController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminSettingsController', $oAdminSettingController);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Controller\AdminSettingsController::isNginx()
     */
    public function testIsNginx()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(2))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, true));

        $oAdminSettingController = new AdminSettingsController(
            $oWrapper,
            $this->getConfig(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertFalse($oAdminSettingController->isNginx());
        self::assertTrue($oAdminSettingController->isNginx());
    }
}
