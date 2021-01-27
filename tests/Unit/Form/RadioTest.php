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

namespace UserAccessManager\Tests\Unit\Form;

use Exception;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Radio;

/**
 * Class RadioTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\Radio
 */
class RadioTest extends TestCase
{
    /**
     * @group unit
     * @throws Exception
     */
    public function testCanCreateInstance()
    {
        $radio = new Radio('id', [], 'value', 'label', 'description');
        self::assertInstanceOf(Radio::class, $radio);
    }
}
