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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class AdminAboutControllerTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\AdminAboutController
 */
class AdminAboutControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $adminAboutController = new AdminAboutController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig()
        );

        self::assertInstanceOf(AdminAboutController::class, $adminAboutController);
    }
}
