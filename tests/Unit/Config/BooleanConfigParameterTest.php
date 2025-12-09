<?php
/**
 * BooleanConfigParameterTest.php
 *
 * The BooleanConfigParameterTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Config;

use ReflectionException;
use UserAccessManager\Config\BooleanConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class BooleanConfigParameterTest
 *
 * @package UserAccessManager\Tests\Unit\Config
 * @coversDefaultClass \UserAccessManager\Config\BooleanConfigParameter
 */
class BooleanConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  ::__construct()
     * @return BooleanConfigParameter
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
     * @covers ::valueToBoolConverter()
     * @param BooleanConfigParameter $booleanConfigParameter
     * @throws ReflectionException
     */
    public function testStringToBoolConverter(BooleanConfigParameter $booleanConfigParameter)
    {
        self::assertTrue(
            self::callMethod($booleanConfigParameter, 'valueToBoolConverter', [1])
        );

        self::assertTrue(
            self::callMethod($booleanConfigParameter, 'valueToBoolConverter', ['true'])
        );

        self::assertFalse(
            self::callMethod($booleanConfigParameter, 'valueToBoolConverter', ['false'])
        );

        self::assertEquals(
            'Test',
            self::callMethod($booleanConfigParameter, 'valueToBoolConverter', ['Test'])
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::setValue()
     * @param BooleanConfigParameter $booleanConfigParameter
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
     * @param BooleanConfigParameter $booleanConfigParameter
     */
    public function testIsValidValue(BooleanConfigParameter $booleanConfigParameter)
    {
        self::assertTrue($booleanConfigParameter->isValidValue(true));
        self::assertTrue($booleanConfigParameter->isValidValue(false));
        self::assertFalse($booleanConfigParameter->isValidValue('string'));
    }
}
