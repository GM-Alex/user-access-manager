<?php
/**
 * SelectTest.php
 *
 * The SelectTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Form;

/**
 * Class SelectTest
 *
 * @package UserAccessManager\Form
 */
class SelectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $select = new Select('id', [], 'value', 'label', 'description');
        self::assertInstanceOf(Select::class, $select);
    }
}
