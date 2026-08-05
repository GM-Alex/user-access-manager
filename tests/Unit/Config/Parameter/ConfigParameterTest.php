<?php

namespace UserAccessManager\Tests\Unit\Config\Parameter;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\Parameter\ConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Config\Parameter\ConfigParameter
 */
class ConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @throws ReflectionException
     */
    private function getStub(): ConfigParameter|MockObject
    {
        $stub = $this->getMockForAbstractClass(
            ConfigParameter::class,
            [],
            '',
            false,
            true,
            true
        );

        $this->setValue($stub, 'id', 'id');
        return $stub;
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     * @throws Exception
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub();
        $stub->expects($this->exactly(2))->method('isValidValue')->will($this->returnValue(true));
        $stub->__construct('testId');

        self::assertEquals('testId', $stub->getId());
        self::assertEquals(null, $stub->getValue());

        $stub->__construct('otherId', 'defaultValue');

        self::assertEquals('otherId', $stub->getId());
        self::assertEquals('defaultValue', $stub->getValue());

        $stub = $this->getStub();
        $stub->expects($this->once())->method('isValidValue')->will($this->returnValue(false));

        self::expectException('\Exception');
        $stub->__construct('otherId', 'defaultValue');
    }

    /**
     * @group   unit
     * @covers  ::getId()
     * @throws Exception
     */
    public function testGetId()
    {
        $stub = $this->getStub();
        $stub->expects($this->once())->method('isValidValue')->will($this->returnValue(true));
        $stub->__construct('testId');

        self::assertEquals('testId', $stub->getId());
    }

    /**
     * @group   unit
     * @covers  ::validateValue()
     * @throws ReflectionException
     */
    public function testValidateValue()
    {
        $stub = $this->getStub();
        $stub->expects($this->exactly(2))
            ->method('isValidValue')
            ->will($this->onConsecutiveCalls(true, false));

        self::assertNull(self::callMethod($stub, 'validateValue', ['value']));

        self::expectException('\Exception');
        self::callMethod($stub, 'validateValue', ['value']);
    }

    /**
     * @group   unit
     * @covers  ::setValue()
     */
    public function testSetValue(): ConfigParameter
    {
        $stub = $this->getStub();
        $stub->expects($this->never())
            ->method('isValidValue');

        $stub->setValue('testValue');

        self::assertEquals('testValue', $stub->getValue());

        // A value outside the parameter's valid set is still stored, so a settings save round-trips it.
        $stub->setValue('unrecognisedValue');

        self::assertEquals('unrecognisedValue', $stub->getValue());

        $stub->setValue('testValue');

        return $stub;
    }

    /**
     * @group   unit
     * @depends testSetValue
     * @covers  ::getValue()
     */
    public function testGetValue(ConfigParameter $stub)
    {
        self::assertEquals('testValue', $stub->getValue());
    }
}
