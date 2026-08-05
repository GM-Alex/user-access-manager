<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\FormElement;
use UserAccessManager\Form\Element\MultipleFormElement;
use UserAccessManager\Form\Element\MultipleFormElementValue;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\MultipleFormElement
 */
class MultipleFormElementTest extends TestCase
{
    private function getStub(
        string $id,
        array $possibleValues,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ): MultipleFormElement|MockObject {
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
     */
    public function testCanCreateInstance(): FormElement
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
     */
    public function testGetPossibleValues(MultipleFormElement $multipleFormElement)
    {
        $valueMock = $this->createMock(MultipleFormElementValue::class);
        self::assertEquals(['value' => $valueMock], $multipleFormElement->getPossibleValues());
    }
}
