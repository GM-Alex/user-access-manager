<?php

namespace UserAccessManager\Tests\Unit\Config\Parameter;

use ReflectionException;
use UserAccessManager\Config\Parameter\BooleanConfigParameter;
use UserAccessManager\Config\Config;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Config\Parameter\BooleanConfigParameter
 */
class BooleanConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  ::__construct()
     */
    public function testCanCreateInstance(): BooleanConfigParameter
    {
        $booleanConfigParameter = new BooleanConfigParameter('testId');

        self::assertInstanceOf(BooleanConfigParameter::class, $booleanConfigParameter);
        self::assertEquals('testId', $booleanConfigParameter->getId());
        self::assertFalse($booleanConfigParameter->getValue());

        $booleanConfigParameter = new BooleanConfigParameter('otherId', true);

        self::assertInstanceOf(BooleanConfigParameter::class, $booleanConfigParameter);
        self::assertEquals('otherId', $booleanConfigParameter->getId());
        self::assertTrue($booleanConfigParameter->getValue());

        return $booleanConfigParameter;
    }

    /**
     * @group unit
     * @depends testCanCreateInstance
     * @covers ::convertToBoolean()
     * @throws ReflectionException
     */
    public function testStringToBoolConverter(BooleanConfigParameter $booleanConfigParameter)
    {
        // Each accepted literal must be converted individually (strict comparison),
        // so no entry of the conversion tables can be dropped without breaking a case.
        self::assertSame(true, self::callMethod($booleanConfigParameter, 'convertToBoolean', [1]));
        self::assertSame(true, self::callMethod($booleanConfigParameter, 'convertToBoolean', ['1']));
        self::assertSame(true, self::callMethod($booleanConfigParameter, 'convertToBoolean', ['true']));

        self::assertSame(false, self::callMethod($booleanConfigParameter, 'convertToBoolean', [0]));
        self::assertSame(false, self::callMethod($booleanConfigParameter, 'convertToBoolean', ['0']));
        self::assertSame(false, self::callMethod($booleanConfigParameter, 'convertToBoolean', ['false']));

        self::assertSame(
            'Test',
            self::callMethod($booleanConfigParameter, 'convertToBoolean', ['Test'])
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::setValue()
     */
    public function testSetValue(BooleanConfigParameter $booleanConfigParameter)
    {
        $booleanConfigParameter->setValue(1);
        self::assertTrue($booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue(true);
        self::assertTrue($booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue(false);
        self::assertFalse($booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue('true');
        self::assertTrue($booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue('false');
        self::assertFalse($booleanConfigParameter->getValue());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::isValidValue()
     */
    public function testIsValidValue(BooleanConfigParameter $booleanConfigParameter)
    {
        self::assertTrue($booleanConfigParameter->isValidValue(true));
        self::assertTrue($booleanConfigParameter->isValidValue(false));
        self::assertFalse($booleanConfigParameter->isValidValue('string'));
    }
}
