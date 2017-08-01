<?php
/**
 * FormElementTest.php
 *
 * The FormElementTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Form;

use UserAccessManager\Form\FormElement;

/**
 * Class FormElementTest
 *
 * @package UserAccessManager\Form
 * @coversDefaultClass \UserAccessManager\Form\FormElement
 */
class FormElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $id
     * @param mixed  $value
     * @param string $label
     * @param string $description
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FormElement
     */
    private function getStub($id, $value = null, $label = null, $description = null)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\Form\FormElement',
            [$id, $value, $label, $description]
        );
    }

    /**
     * @group  unit
     * @covers ::__construct()
     *
     * @return FormElement
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub('id', 'value', 'label', 'description');
        self::assertInstanceOf(FormElement::class, $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @covers  ::getId()
     * @depends testCanCreateInstance
     *
     * @param FormElement $formElement
     */
    public function testGetId(FormElement $formElement)
    {
        self::assertEquals('id', $formElement->getId());
    }

    /**
     * @group   unit
     * @covers  ::getValue()
     * @depends testCanCreateInstance
     *
     * @param FormElement $formElement
     */
    public function testGetValue(FormElement $formElement)
    {
        self::assertEquals('value', $formElement->getValue());
    }

    /**
     * @group   unit
     * @covers  ::getLabel()
     * @depends testCanCreateInstance
     *
     * @param FormElement $formElement
     */
    public function testGetLabel(FormElement $formElement)
    {
        self::assertEquals('label', $formElement->getLabel());
    }

    /**
     * @group   unit
     * @covers  ::getDescription()
     * @depends testCanCreateInstance
     *
     * @param FormElement $formElement
     */
    public function testGetDescription(FormElement $formElement)
    {
        self::assertEquals('description', $formElement->getDescription());
    }
}
