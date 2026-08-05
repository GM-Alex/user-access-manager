<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\FormElement;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\FormElement
 */
class FormElementTest extends TestCase
{
    private function getStub(
        string $id,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ): FormElement|MockObject {
        return $this->getMockForAbstractClass(
            FormElement::class,
            [$id, $value, $label, $description]
        );
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance(): FormElement
    {
        $stub = $this->getStub('id', 'value', 'label', 'description');
        self::assertInstanceOf(FormElement::class, $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @covers  ::getId()
     * @depends testCanCreateInstance
     */
    public function testGetId(FormElement $formElement)
    {
        self::assertEquals('id', $formElement->getId());
    }

    /**
     * @group   unit
     * @covers  ::getValue()
     * @depends testCanCreateInstance
     */
    public function testGetValue(FormElement $formElement)
    {
        self::assertEquals('value', $formElement->getValue());
    }

    /**
     * @group   unit
     * @covers  ::getLabel()
     * @depends testCanCreateInstance
     */
    public function testGetLabel(FormElement $formElement)
    {
        self::assertEquals('label', $formElement->getLabel());
    }

    /**
     * @group   unit
     * @covers  ::getDescription()
     * @depends testCanCreateInstance
     */
    public function testGetDescription(FormElement $formElement)
    {
        self::assertEquals('description', $formElement->getDescription());
    }
}
