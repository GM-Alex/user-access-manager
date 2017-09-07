<?php
/**
 * ValueSetFormElementValueTest.php
 *
 * The ValueSetFormElementValueTest unit test class file.
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

use UserAccessManager\Form\ValueSetFormElementValue;

/**
 * Class ValueSetFormElementValueTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\ValueSetFormElementValue
 */
class ValueSetFormElementValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     *
     * @return ValueSetFormElementValue
     */
    public function testCanCreateInstance()
    {
        $valueSetFormElementValue = new ValueSetFormElementValue('value', 'label');
        self::assertInstanceOf(ValueSetFormElementValue::class, $valueSetFormElementValue);

        return $valueSetFormElementValue;
    }

    /**
     * @group   unit
     * @covers  ::getValue()
     * @depends testCanCreateInstance
     *
     * @param ValueSetFormElementValue $valueSetFormElementValue
     */
    public function testGetValue(ValueSetFormElementValue $valueSetFormElementValue)
    {
        self::assertEquals('value', $valueSetFormElementValue->getValue());
    }

    /**
     * @group   unit
     * @covers  ::getLabel()
     * @depends testCanCreateInstance
     *
     * @param ValueSetFormElementValue $valueSetFormElementValue
     */
    public function testGetLabel(ValueSetFormElementValue $valueSetFormElementValue)
    {
        self::assertEquals('label', $valueSetFormElementValue->getLabel());
    }
}
