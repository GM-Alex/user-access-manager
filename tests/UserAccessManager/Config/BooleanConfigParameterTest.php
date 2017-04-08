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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class BooleanConfigParameterTest
 *
 * @package UserAccessManager\Config
 */
class BooleanConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\BooleanConfigParameter::__construct()
     *
     * @return BooleanConfigParameter
     */
    public function testCanCreateInstance()
    {
        $BooleanConfigParameter = new BooleanConfigParameter('testId');

        self::assertInstanceOf('\UserAccessManager\Config\BooleanConfigParameter', $BooleanConfigParameter);
        self::assertAttributeEquals('testId', 'sId', $BooleanConfigParameter);
        self::assertAttributeEquals(false, 'mDefaultValue', $BooleanConfigParameter);

        $BooleanConfigParameter = new BooleanConfigParameter('otherId', true);

        self::assertInstanceOf('\UserAccessManager\Config\BooleanConfigParameter', $BooleanConfigParameter);
        self::assertAttributeEquals('otherId', 'sId', $BooleanConfigParameter);
        self::assertAttributeEquals(true, 'mDefaultValue', $BooleanConfigParameter);

        return $BooleanConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\BooleanConfigParameter::stringToBoolConverter()
     *
     * @param BooleanConfigParameter $BooleanConfigParameter
     */
    public function testStringToBoolConverter($BooleanConfigParameter)
    {
        self::assertEquals(
            true,
            self::callMethod($BooleanConfigParameter, 'stringToBoolConverter', ['true'])
        );

        self::assertEquals(
            false,
            self::callMethod($BooleanConfigParameter, 'stringToBoolConverter', ['false'])
        );

        self::assertEquals(
            'Test',
            self::callMethod($BooleanConfigParameter, 'stringToBoolConverter', ['Test'])
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\BooleanConfigParameter::setValue()
     *
     * @param BooleanConfigParameter $BooleanConfigParameter
     */
    public function testSetValue($BooleanConfigParameter)
    {
        $BooleanConfigParameter->setValue(true);
        self::assertAttributeEquals(true, 'mValue', $BooleanConfigParameter);

        $BooleanConfigParameter->setValue(false);
        self::assertAttributeEquals(false, 'mValue', $BooleanConfigParameter);

        $BooleanConfigParameter->setValue('true');
        self::assertAttributeEquals(true, 'mValue', $BooleanConfigParameter);

        $BooleanConfigParameter->setValue('false');
        self::assertAttributeEquals(false, 'mValue', $BooleanConfigParameter);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\BooleanConfigParameter::isValidValue()
     *
     * @param BooleanConfigParameter $BooleanConfigParameter
     */
    public function testIsValidValue($BooleanConfigParameter)
    {
        self::assertTrue($BooleanConfigParameter->isValidValue(true));
        self::assertTrue($BooleanConfigParameter->isValidValue(false));
        self::assertFalse($BooleanConfigParameter->isValidValue('string'));
    }
}
