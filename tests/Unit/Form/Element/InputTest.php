<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\Input;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\Input
 */
class InputTest extends TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $input = new Input('id', 'value', 'label', 'description');
        self::assertInstanceOf(Input::class, $input);
    }
}
