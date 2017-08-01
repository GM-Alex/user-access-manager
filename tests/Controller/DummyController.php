<?php
/**
 * DummyController.php
 *
 * The DummyController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller;

use UserAccessManager\Controller\Controller;

/**
 * Class DummyController
 *
 * @package UserAccessManager\Controller
 */
class DummyController extends Controller
{
    /**
     * Dummy action function.
     */
    public function testAction()
    {
        echo 'testAction';
    }
}
