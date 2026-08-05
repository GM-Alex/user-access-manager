<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\Input;
use UserAccessManager\Form\Element\MultipleFormElement;
use UserAccessManager\Form\Element\MultipleFormElementValue;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\MultipleFormElementValue
 */
class MultipleFormElementValueTest extends TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance(): MultipleFormElementValue
    {
        $multipleFormElementValue = new MultipleFormElementValue('value', 'label');
        self::assertInstanceOf(MultipleFormElementValue::class, $multipleFormElementValue);

        return $multipleFormElementValue;
    }

    /**
     * @group             unit
     * @covers            ::setSubElement()
     * @depends           testCanCreateInstance
     * @throws Exception
     */
    public function testSetSubElementException(MultipleFormElementValue $multipleFormElementValue)
    {
        $this->expectException(Exception::class);
        /**
         * @var MockObject|MultipleFormElement $subElement
         */
        $subElement = $this->createMock(MultipleFormElement::class);
        $multipleFormElementValue->setSubElement($subElement);
    }

    /**
     * @group   unit
     * @covers  ::setSubElement()
     * @depends testCanCreateInstance
     * @throws Exception
     */
    public function testSetSubElement(MultipleFormElementValue $multipleFormElementValue): MultipleFormElementValue
    {
        /**
         * @var MockObject|Input $subElement
         */
        $subElement = $this->createMock(Input::class);
        $multipleFormElementValue->setSubElement($subElement);

        self::assertEquals($subElement, $multipleFormElementValue->getSubElement());

        return $multipleFormElementValue;
    }

    /**
     * @group   unit
     * @covers  ::getSubElement()
     * @depends testSetSubElement
     */
    public function testGetSubElement(MultipleFormElementValue $multipleFormElementValue)
    {
        /**
         * @var MockObject|Input $subElement
         */
        $subElement = $this->createMock(Input::class);
        self::assertEquals($subElement, $multipleFormElementValue->getSubElement());
    }
}
