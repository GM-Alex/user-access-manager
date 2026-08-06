<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\FormElement;
use UserAccessManager\Form\Element\ValueSetFormElement;
use UserAccessManager\Form\Element\ValueSetFormElementValue;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\ValueSetFormElement
 */
class ValueSetFormElementTest extends TestCase
{
    private function getStub(
        string $id,
        array $possibleValues,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ): ValueSetFormElement|MockObject {
        return $this->getMockForAbstractClass(
            ValueSetFormElement::class,
            [$id, $possibleValues, $value, $label, $description]
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     */
    public function testCanCreateInstance(): FormElement
    {
        $possibleValue = $this->createMock(ValueSetFormElementValue::class);

        $possibleValue->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue('value'));

        $stub = $this->getStub('id', [$possibleValue], 'value', 'label', 'description');
        self::assertInstanceOf(ValueSetFormElement::class, $stub);
        self::assertEquals('id', $stub->getId());
        self::assertEquals('value', $stub->getValue());
        self::assertEquals('label', $stub->getLabel());
        self::assertEquals('description', $stub->getDescription());

        return $stub;
    }

    /**
     * @group   unit
     * @covers  ::getPossibleValues()
     * @depends testCanCreateInstance
     */
    public function testGetPossibleValues(ValueSetFormElement $valueSetFormElement)
    {
        self::assertEquals(
            ['value' => $this->createMock(ValueSetFormElementValue::class)],
            $valueSetFormElement->getPossibleValues()
        );
    }
}
