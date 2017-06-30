<?php
/**
 * MultipleFormElementTest.php
 *
 * The MultipleFormElementTest unit test class file.
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
 * Class MultipleFormElementTest
 *
 * @package UserAccessManager\Form
 */
class MultipleFormElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $id
     * @param array  $possibleValues
     * @param mixed  $value
     * @param string $label
     * @param string $description
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|MultipleFormElement
     */
    private function getStub($id, array $possibleValues, $value = null, $label = null, $description = null)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\Form\MultipleFormElement',
            [$id, $possibleValues, $value, $label, $description]
        );
    }

    /**
     * @group             unit
     * @covers            \UserAccessManager\Form\MultipleFormElement::__construct()
     * @expectedException \Exception
     */
    public function testCreateInstanceException()
    {
        $stub = $this->getStub('id', ['possibleValue'], 'value', 'label', 'description');
        self::assertInstanceOf(MultipleFormElement::class, $stub);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\MultipleFormElement::__construct()
     *
     * @return FormElement
     */
    public function testCanCreateInstance()
    {
        $valueMock = $this->createMock('\UserAccessManager\Form\MultipleFormElementValue');
        $stub = $this->getStub('id', [$valueMock], 'value', 'label', 'description');
        self::assertInstanceOf(MultipleFormElement::class, $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\MultipleFormElement::getPossibleValues()
     * @depends testCanCreateInstance
     *
     * @param MultipleFormElement $multipleFormElement
     */
    public function testGetPossibleValues(MultipleFormElement $multipleFormElement)
    {
        $valueMock = $this->createMock('\UserAccessManager\Form\MultipleFormElementValue');
        self::assertEquals([$valueMock], $multipleFormElement->getPossibleValues());
    }
}
