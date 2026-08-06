<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\ValueTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\ValueTrait
 */
class ValueTraitTest extends UserAccessManagerTestCase
{
    private function getStub(): ValueTrait|MockObject
    {
        return $this->getMockForTrait(ValueTrait::class);
    }

    /**
     * @group  unit
     * @covers ::getValue()
     * @throws ReflectionException
     */
    public function testGetGetValue()
    {
        $valueTrait = $this->getStub();
        self::setValue($valueTrait, 'value', 'valueValue');
        self::assertEquals('valueValue', $valueTrait->getValue());
    }
}
