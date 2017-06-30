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
namespace UserAccessManager\Form;

/**
 * Class ValueSetFormElementValueTest
 *
 * @package UserAccessManager\Form
 */
class ValueSetFormElementValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Form\ValueSetFormElementValue::__construct()
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
     * @covers  \UserAccessManager\Form\ValueSetFormElementValue::getValue()
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
     * @covers  \UserAccessManager\Form\ValueSetFormElementValue::getLabel()
     * @depends testCanCreateInstance
     *
     * @param ValueSetFormElementValue $valueSetFormElementValue
     */
    public function testGetLabel(ValueSetFormElementValue $valueSetFormElementValue)
    {
        self::assertEquals('label', $valueSetFormElementValue->getLabel());
    }
}
