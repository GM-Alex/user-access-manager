<?php

namespace UserAccessManager\Tests\Unit\Form;

use Exception;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\Element\Input;
use UserAccessManager\Form\Element\MultipleFormElementValue;
use UserAccessManager\Form\Element\Radio;
use UserAccessManager\Form\Element\Select;
use UserAccessManager\Form\Element\Textarea;
use UserAccessManager\Form\Element\ValueSetFormElementValue;

/**
 * @coversDefaultClass \UserAccessManager\Form\FormFactory
 */
class FormFactoryTest extends TestCase
{
    /**
     * @group  unit
     */
    public function testCanCreateInstance(): FormFactory
    {
        $formFactory = new FormFactory();

        self::assertInstanceOf(FormFactory::class, $formFactory);

        return $formFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createForm()
     */
    public function testCreateFrom(FormFactory $formFactory)
    {
        self::assertInstanceOf(Form::class, $formFactory->createForm());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createValueSetFormElementValue()
     */
    public function testCreateValueSetFromElementValue(FormFactory $formFactory)
    {
        self::assertInstanceOf(
            ValueSetFormElementValue::class,
            $formFactory->createValueSetFormElementValue('value', 'label')
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createMultipleFormElementValue()
     */
    public function testCreateMultipleFormElementValue(FormFactory $formFactory)
    {
        self::assertInstanceOf(
            MultipleFormElementValue::class,
            $formFactory->createMultipleFormElementValue('value', 'label')
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createInput()
     */
    public function testCreateInput(FormFactory $formFactory)
    {
        self::assertInstanceOf(Input::class, $formFactory->createInput('id'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createTextarea()
     */
    public function testCreateTextarea(FormFactory $formFactory)
    {
        self::assertInstanceOf(Textarea::class, $formFactory->createTextarea('id'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createSelect()
     */
    public function testCreateSelect(FormFactory $formFactory)
    {
        self::assertInstanceOf(Select::class, $formFactory->createSelect('id', []));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createRadio()
     * @throws Exception
     */
    public function testCreateRadio(FormFactory $formFactory)
    {
        self::assertInstanceOf(Radio::class, $formFactory->createRadio('id', []));
    }
}
