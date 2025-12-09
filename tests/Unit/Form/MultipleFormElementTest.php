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

namespace UserAccessManager\Tests\Unit\Form;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\FormElement;
use UserAccessManager\Form\MultipleFormElement;
use UserAccessManager\Form\MultipleFormElementValue;

/**
 * Class MultipleFormElementTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\MultipleFormElement
 */
class MultipleFormElementTest extends TestCase
{
    /**
     * @param string $id
     * @param array $possibleValues
     * @param mixed|null $value
     * @param null $label
     * @param null $description
     * @return MockObject|MultipleFormElement
     */
    private function getStub(string $id, array $possibleValues, mixed $value = null, $label = null, $description = null): MultipleFormElement|MockObject
    {
        return $this->getMockForAbstractClass(
            MultipleFormElement::class,
            [$id, $possibleValues, $value, $label, $description]
        );
    }

    /**
     * @group             unit
     * @covers            ::__construct()
     */
    public function testCreateInstanceException()
    {
        $this->expectException(Exception::class);
        $stub = $this->getStub('id', ['possibleValue'], 'value', 'label', 'description');
        self::assertInstanceOf(MultipleFormElement::class, $stub);
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     * @return FormElement
     */
    public function testCanCreateInstance()
    {
        $valueMock = $this->createMock(MultipleFormElementValue::class);
        $valueMock->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('value'));

        $stub = $this->getStub('id', [$valueMock], 'value', 'label', 'description');
        self::assertInstanceOf(MultipleFormElement::class, $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @covers  ::getPossibleValues()
     * @depends testCanCreateInstance
     * @param MultipleFormElement $multipleFormElement
     */
    public function testGetPossibleValues(MultipleFormElement $multipleFormElement)
    {
        $valueMock = $this->createMock(MultipleFormElementValue::class);
        self::assertEquals(['value' => $valueMock], $multipleFormElement->getPossibleValues());
    }
}
