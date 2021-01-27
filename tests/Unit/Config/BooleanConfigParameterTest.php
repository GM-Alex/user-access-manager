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
        self::assertEquals(false, $booleanConfigParameter->getValue());

        $booleanConfigParameter = new BooleanConfigParameter('otherId', true);

        self::assertInstanceOf(BooleanConfigParameter::class, $booleanConfigParameter);
        self::assertEquals('otherId', $booleanConfigParameter->getId());
        self::assertEquals(true, $booleanConfigParameter->getValue());

        return $booleanConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::stringToBoolConverter()
     * @param BooleanConfigParameter $booleanConfigParameter
     * @throws ReflectionException
     */
    public function testStringToBoolConverter(BooleanConfigParameter $booleanConfigParameter)
    {
        self::assertEquals(
            true,
            self::callMethod($booleanConfigParameter, 'stringToBoolConverter', ['true'])
        );

        self::assertEquals(
            false,
            self::callMethod($booleanConfigParameter, 'stringToBoolConverter', ['false'])
        );

        self::assertEquals(
            'Test',
            self::callMethod($booleanConfigParameter, 'stringToBoolConverter', ['Test'])
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
        self::assertEquals(true, $booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue(true);
        self::assertEquals(true, $booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue(false);
        self::assertEquals(false, $booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue('true');
        self::assertEquals(true, $booleanConfigParameter->getValue());

        $booleanConfigParameter->setValue('false');
        self::assertEquals(false, $booleanConfigParameter->getValue());
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
