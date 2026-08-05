<?php

namespace UserAccessManager\Tests\Unit\Config\Parameter;

use UserAccessManager\Config\Config;
use UserAccessManager\Config\Parameter\StringConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Config\Parameter\StringConfigParameter
 */
class StringConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance(): StringConfigParameter
    {
        $stringConfigParameter = new StringConfigParameter('testId');

        self::assertInstanceOf(StringConfigParameter::class, $stringConfigParameter);
        self::assertEquals('testId', $stringConfigParameter->getId());
        self::assertEquals('', $stringConfigParameter->getValue());

        $stringConfigParameter = new StringConfigParameter('otherId', 'value');

        self::assertInstanceOf(StringConfigParameter::class, $stringConfigParameter);
        self::assertEquals('otherId', $stringConfigParameter->getId());
        self::assertEquals('value', $stringConfigParameter->getValue());

        return $stringConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::isValidValue()
     */
    public function testIsValidValue(StringConfigParameter $stringConfigParameter)
    {
        self::assertTrue($stringConfigParameter->isValidValue('string'));
        self::assertFalse($stringConfigParameter->isValidValue(true));
        self::assertFalse($stringConfigParameter->isValidValue([]));
    }
}
