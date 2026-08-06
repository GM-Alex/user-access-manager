<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\Textarea;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\Textarea
 */
class TextareaTest extends TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $textarea = new Textarea('id', 'value', 'label', 'description');
        self::assertInstanceOf(Textarea::class, $textarea);
    }
}
