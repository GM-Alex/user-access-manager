<?php

namespace UserAccessManager\Tests\Unit\Form;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\FormElement;

/**
 * @coversDefaultClass \UserAccessManager\Form\Form
 */
class FormTest extends TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance(): Form
    {
        $form = new Form();

        self::assertInstanceOf(Form::class, $form);

        return $form;
    }

    /**
     * @group   unit
     * @covers  ::addElement()
     * @depends testCanCreateInstance
     */
    public function testAddElement(Form $form): Form
    {
        /**
         * @var MockObject|FormElement $firstFormElement
         */
        $firstFormElement = $this->createMock(FormElement::class);
        $firstFormElement->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('firstId'));

        /**
         * @var MockObject|FormElement $secondFormElement
         */
        $secondFormElement = $this->createMock(FormElement::class);
        $secondFormElement->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('secondId'));

        $form->addElement($firstFormElement);
        $form->addElement($secondFormElement);

        self::assertEquals(
            ['firstId' => $firstFormElement, 'secondId' => $secondFormElement],
            $form->getElements()
        );

        return $form;
    }

    /**
     * @group   unit
     * @covers  ::getElements()
     * @depends testAddElement
     */
    public function testGetElements(Form $form)
    {
        $elements = $form->getElements();
        self::assertCount(2, $elements);
    }
}
