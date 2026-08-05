<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\ValueSetFormElementValue;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\ValueSetFormElementValue
 */
class ValueSetFormElementValueTest extends TestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance(): ValueSetFormElementValue
    {
        $valueSetFormElementValue = new ValueSetFormElementValue('value', 'label');
        self::assertInstanceOf(ValueSetFormElementValue::class, $valueSetFormElementValue);

        return $valueSetFormElementValue;
    }

    /**
     * @group   unit
     * @covers  ::getValue()
     * @depends testCanCreateInstance
     */
    public function testGetValue(ValueSetFormElementValue $valueSetFormElementValue)
    {
        self::assertEquals('value', $valueSetFormElementValue->getValue());
    }

    /**
     * @group   unit
     * @covers  ::getLabel()
     * @depends testCanCreateInstance
     */
    public function testGetLabel(ValueSetFormElementValue $valueSetFormElementValue)
    {
        self::assertEquals('label', $valueSetFormElementValue->getLabel());
    }

    /**
     * @group   unit
     * @covers  ::isDisabled()
     * @covers  ::markDisabled()
     * @depends testCanCreateInstance
     */
    public function testDisabled(ValueSetFormElementValue $valueSetFormElementValue)
    {
        self::assertFalse($valueSetFormElementValue->isDisabled());
        $valueSetFormElementValue->markDisabled();
        self::assertTrue($valueSetFormElementValue->isDisabled());
    }
}
