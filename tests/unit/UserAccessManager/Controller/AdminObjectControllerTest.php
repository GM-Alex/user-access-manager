<?php
/**
 * AdminObjectControllerTest.php
 *
 * The AdminObjectControllerTest unit test class file.
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
 * Class AdminObjectControllerTest
 *
 * @package UserAccessManager\Controller
 */
class AdminObjectControllerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Controller\AdminObjectController::__construct()
     */
    public function testCanCreateInstance()
    {
        $oAdminObjectController = new AdminObjectController(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Controller\AdminObjectController', $oAdminObjectController);
    }
}
