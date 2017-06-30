<?php
/**
 * RadioTest.php
 *
 * The RadioTest unit test class file.
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
 * Class RadioTest
 *
 * @package UserAccessManager\Form
 */
class RadioTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $radio = new Radio('id', [], 'value', 'label', 'description');
        self::assertInstanceOf(Radio::class, $radio);
    }
}
