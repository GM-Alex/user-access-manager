<?php

namespace UserAccessManager\Tests\Unit\Form\Element;

use Exception;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\Element\Radio;

/**
 * @coversDefaultClass \UserAccessManager\Form\Element\Radio
 */
class RadioTest extends TestCase
{
    /**
     * @group unit
     * @throws Exception
     */
    public function testCanCreateInstance()
    {
        $radio = new Radio('id', [], 'value', 'label', 'description');
        self::assertInstanceOf(Radio::class, $radio);
    }
}
