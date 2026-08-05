<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\Select;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\Select
 */
class SelectTest extends TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $select = new Select('id', [], 'value', 'label', 'description');
        self::assertInstanceOf(Select::class, $select);
    }
}
