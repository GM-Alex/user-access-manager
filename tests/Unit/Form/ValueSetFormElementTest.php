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

namespace UserAccessManager\Tests\Unit\Form;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\FormElement;
use UserAccessManager\Form\ValueSetFormElement;
use UserAccessManager\Form\ValueSetFormElementValue;

/**
 * Class ValueSetFormElementTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\ValueSetFormElement
 */
class ValueSetFormElementTest extends TestCase
{
    /**
     * @param string $id
     * @param array $possibleValues
     * @param mixed|null $value
     * @param null $label
     * @param null $description
     * @return MockObject|ValueSetFormElement
     */
    private function getStub(string $id, array $possibleValues, mixed $value = null, $label = null, $description = null): ValueSetFormElement|MockObject
    {
        return $this->getMockForAbstractClass(
            ValueSetFormElement::class,
            [$id, $possibleValues, $value, $label, $description]
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     * @return FormElement
     */
    public function testCanCreateInstance()
    {
        $possibleValue = $this->createMock(ValueSetFormElementValue::class);

        $possibleValue->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('value'));

        $stub = $this->getStub('id', [$possibleValue], 'value', 'label', 'description');
        self::assertInstanceOf(ValueSetFormElement::class, $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @covers  ::getPossibleValues()
     * @depends testCanCreateInstance
     * @param ValueSetFormElement $valueSetFormElement
     */
    public function testGetPossibleValues(ValueSetFormElement $valueSetFormElement)
    {
        self::assertEquals(
            ['value' => $this->createMock(ValueSetFormElementValue::class)],
            $valueSetFormElement->getPossibleValues()
        );
    }
}
