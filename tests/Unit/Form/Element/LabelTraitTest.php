<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\LabelTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\LabelTrait
 */
class LabelTraitTest extends UserAccessManagerTestCase
{
    private function getStub(): MockObject|LabelTrait
    {
        return $this->getMockForTrait(LabelTrait::class);
    }

    /**
     * @group  unit
     * @covers ::getLabel()
     * @throws ReflectionException
     */
    public function testGetGetLabel()
    {
        $labelTrait = $this->getStub();
        self::setValue($labelTrait, 'label', 'labelValue');
        self::assertEquals('labelValue', $labelTrait->getLabel());
    }
}
