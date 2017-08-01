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
namespace UserAccessManager\Tests\Config;

use UserAccessManager\Config\BooleanConfigParameter;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class BooleanConfigParameterTest
 *
 * @package UserAccessManager\Config
 * @coversDefaultClass \UserAccessManager\Config\BooleanConfigParameter
 */
class BooleanConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  ::__construct()
     *
     * @return BooleanConfigParameter
     */
    public function testCanCreateInstance()
    {
        $booleanConfigParameter = new BooleanConfigParameter('testId');

        self::assertInstanceOf(BooleanConfigParameter::class, $booleanConfigParameter);
        self::assertAttributeEquals('testId', 'id', $booleanConfigParameter);
        self::assertAttributeEquals(false, 'defaultValue', $booleanConfigParameter);

        $booleanConfigParameter = new BooleanConfigParameter('otherId', true);

        self::assertInstanceOf(BooleanConfigParameter::class, $booleanConfigParameter);
        self::assertAttributeEquals('otherId', 'id', $booleanConfigParameter);
        self::assertAttributeEquals(true, 'defaultValue', $booleanConfigParameter);

        return $booleanConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::stringToBoolConverter()
     *
     * @param BooleanConfigParameter $booleanConfigParameter
     */
    public function testStringToBoolConverter($booleanConfigParameter)
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
     *
     * @param BooleanConfigParameter $booleanConfigParameter
     */
    public function testSetValue($booleanConfigParameter)
    {
        $booleanConfigParameter->setValue(true);
        self::assertAttributeEquals(true, 'value', $booleanConfigParameter);

        $booleanConfigParameter->setValue(false);
        self::assertAttributeEquals(false, 'value', $booleanConfigParameter);

        $booleanConfigParameter->setValue('true');
        self::assertAttributeEquals(true, 'value', $booleanConfigParameter);

        $booleanConfigParameter->setValue('false');
        self::assertAttributeEquals(false, 'value', $booleanConfigParameter);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::isValidValue()
     *
     * @param BooleanConfigParameter $booleanConfigParameter
     */
    public function testIsValidValue($booleanConfigParameter)
    {
        self::assertTrue($booleanConfigParameter->isValidValue(true));
        self::assertTrue($booleanConfigParameter->isValidValue(false));
        self::assertFalse($booleanConfigParameter->isValidValue('string'));
    }
}
