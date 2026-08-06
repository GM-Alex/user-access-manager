<?php

namespace UserAccessManager\Tests\Unit\Config\Parameter;

use Exception;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\Parameter\SelectionConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Config\Parameter\SelectionConfigParameter
 */
class SelectionConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  ::__construct()
     * @throws Exception
     */
    public function testCanCreateInstance(): SelectionConfigParameter
    {
        $selectionConfigParameter = new SelectionConfigParameter('testId', 'default', ['default', 'second']);

        self::assertInstanceOf(SelectionConfigParameter::class, $selectionConfigParameter);
        self::assertEquals('testId', $selectionConfigParameter->getId());
        self::assertEquals('default', $selectionConfigParameter->getValue());
        self::assertEquals(['default', 'second'], $selectionConfigParameter->getSelections());

        return $selectionConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::isValidValue()
     */
    public function testIsValidValue(SelectionConfigParameter $selectionConfigParameter)
    {
        self::assertTrue($selectionConfigParameter->isValidValue('default'));
        self::assertTrue($selectionConfigParameter->isValidValue('second'));
        self::assertFalse($selectionConfigParameter->isValidValue('aaa'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getSelections()
     */
    public function testGetSelections(SelectionConfigParameter $selectionConfigParameter)
    {
        self::assertEquals(['default', 'second'], $selectionConfigParameter->getSelections());
    }
}
