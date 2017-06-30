<?php
/**
 * ValueSetFormElementTest.php
 *
 * The ValueSetFormElementTest unit test class file.
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
 * Class ValueSetFormElementTest
 *
 * @package UserAccessManager\Form
 */
class ValueSetFormElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $id
     * @param array  $possibleValues
     * @param mixed  $value
     * @param string $label
     * @param string $description
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ValueSetFormElement
     */
    private function getStub($id, array $possibleValues, $value = null, $label = null, $description = null)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\Form\ValueSetFormElement',
            [$id, $possibleValues, $value, $label, $description]
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\ValueSetFormElement::__construct()
     *
     * @return FormElement
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub('id', ['possibleValue'], 'value', 'label', 'description');
        self::assertInstanceOf(ValueSetFormElement::class, $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\ValueSetFormElement::getPossibleValues()
     * @depends testCanCreateInstance
     *
     * @param ValueSetFormElement $valueSetFormElement
     */
    public function testGetPossibleValues(ValueSetFormElement $valueSetFormElement)
    {
        self::assertEquals(['possibleValue'], $valueSetFormElement->getPossibleValues());
    }
}
