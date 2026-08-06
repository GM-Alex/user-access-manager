<?php

namespace UserAccessManager\Tests\Unit\Config\Parameter;

use Exception;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Config\Parameter\BooleanConfigParameter;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\Parameter\ConfigParameterFactory;
use UserAccessManager\Config\Parameter\SelectionConfigParameter;
use UserAccessManager\Config\Parameter\StringConfigParameter;

/**
 * @coversDefaultClass \UserAccessManager\Config\Parameter\ConfigParameterFactory
 */
class ConfigParameterFactoryTest extends TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance(): ConfigParameterFactory
    {
        $configParameterFactory = new ConfigParameterFactory();
        self::assertInstanceOf(ConfigParameterFactory::class, $configParameterFactory);

        return $configParameterFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBooleanConfigParameter()
     * @throws Exception
     */
    public function testCreateBooleanConfigParameter(ConfigParameterFactory $configParameterFactory)
    {
        $parameter = $configParameterFactory->createBooleanConfigParameter('parameterId');
        self::assertInstanceOf(BooleanConfigParameter::class, $parameter);
        self::assertEquals('parameterId', $parameter->getId());
        self::assertFalse($parameter->getValue());

        $parameter = $configParameterFactory->createBooleanConfigParameter('parameterId', true);
        self::assertTrue($parameter->getValue());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createStringConfigParameter()
     * @throws Exception
     */
    public function testCreateStringConfigParameter(ConfigParameterFactory $configParameterFactory)
    {
        $parameter = $configParameterFactory->createStringConfigParameter('parameterId');
        self::assertInstanceOf(StringConfigParameter::class, $parameter);
        self::assertEquals('parameterId', $parameter->getId());
        self::assertEquals('', $parameter->getValue());

        $parameter = $configParameterFactory->createStringConfigParameter('parameterId', 'test');
        self::assertEquals('test', $parameter->getValue());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createSelectionConfigParameter()
     * @throws Exception
     */
    public function testCreateSelectionConfigParameter(ConfigParameterFactory $configParameterFactory)
    {
        $parameter = $configParameterFactory->createSelectionConfigParameter(
            'parameterId',
            'a',
            ['a', 'b', 'c']
        );

        self::assertInstanceOf(SelectionConfigParameter::class, $parameter);
        self::assertEquals('parameterId', $parameter->getId());
        self::assertEquals('a', $parameter->getValue());
        self::assertEquals(['a', 'b', 'c'], $parameter->getSelections());
    }
}
