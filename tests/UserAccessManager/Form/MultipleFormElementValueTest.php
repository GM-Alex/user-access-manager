<?php
/**
 * MultipleFormElementValueTest.php
 *
 * The MultipleFormElementValueTest unit test class file.
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
 * Class MultipleFormElementValueTest
 *
 * @package UserAccessManager\Form
 */
class MultipleFormElementValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     *
     * @return MultipleFormElementValue
     */
    public function testCanCreateInstance()
    {
        $multipleFormElementValue = new MultipleFormElementValue('value', 'label');
        self::assertInstanceOf('\UserAccessManager\Form\MultipleFormElementValue', $multipleFormElementValue);

        return $multipleFormElementValue;
    }

    /**
     * @group             unit
     * @covers            \UserAccessManager\Form\MultipleFormElementValue::setSubElement()
     * @depends           testCanCreateInstance
     * @expectedException \Exception
     *
     * @param MultipleFormElementValue $multipleFormElementValue
     */
    public function testSetSubElementException(MultipleFormElementValue $multipleFormElementValue)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|MultipleFormElement $subElement
         */
        $subElement = $this->createMock('\UserAccessManager\Form\MultipleFormElement');
        $multipleFormElementValue->setSubElement($subElement);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\MultipleFormElementValue::setSubElement()
     * @depends testCanCreateInstance
     *
     * @param MultipleFormElementValue $multipleFormElementValue
     *
     * @return MultipleFormElementValue
     */
    public function testSetSubElement(MultipleFormElementValue $multipleFormElementValue)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|Input $subElement
         */
        $subElement = $this->createMock('\UserAccessManager\Form\Input');
        $multipleFormElementValue->setSubElement($subElement);

        self::assertAttributeEquals($subElement, 'subElement', $multipleFormElementValue);

        return $multipleFormElementValue;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\MultipleFormElementValue::getSubElement()
     * @depends testSetSubElement
     *
     * @param MultipleFormElementValue $multipleFormElementValue
     */
    public function testGetSubElement(MultipleFormElementValue $multipleFormElementValue)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|Input $subElement
         */
        $subElement = $this->createMock('\UserAccessManager\Form\Input');
        self::assertEquals($subElement, $multipleFormElementValue->getSubElement());
    }
}
