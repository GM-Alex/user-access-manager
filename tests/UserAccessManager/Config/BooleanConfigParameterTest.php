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
        $oBooleanConfigParameter = new BooleanConfigParameter('testId');

        self::assertInstanceOf('\UserAccessManager\Config\BooleanConfigParameter', $oBooleanConfigParameter);
        self::assertAttributeEquals('testId', '_sId', $oBooleanConfigParameter);
        self::assertAttributeEquals(false, '_mDefaultValue', $oBooleanConfigParameter);

        $oBooleanConfigParameter = new BooleanConfigParameter('otherId', true);

        self::assertInstanceOf('\UserAccessManager\Config\BooleanConfigParameter', $oBooleanConfigParameter);
        self::assertAttributeEquals('otherId', '_sId', $oBooleanConfigParameter);
        self::assertAttributeEquals(true, '_mDefaultValue', $oBooleanConfigParameter);

        return $oBooleanConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\BooleanConfigParameter::_stringToBoolConverter()
     *
     * @param BooleanConfigParameter $oBooleanConfigParameter
     */
    public function testStringToBoolConverter($oBooleanConfigParameter)
    {
        self::assertEquals(
            true,
            self::callMethod($oBooleanConfigParameter, '_stringToBoolConverter', ['true'])
        );

        self::assertEquals(
            false,
            self::callMethod($oBooleanConfigParameter, '_stringToBoolConverter', ['false'])
        );

        self::assertEquals(
            'Test',
            self::callMethod($oBooleanConfigParameter, '_stringToBoolConverter', ['Test'])
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\BooleanConfigParameter::setValue()
     *
     * @param BooleanConfigParameter $oBooleanConfigParameter
     */
    public function testSetValue($oBooleanConfigParameter)
    {
        $oBooleanConfigParameter->setValue(true);
        self::assertAttributeEquals(true, '_mValue', $oBooleanConfigParameter);

        $oBooleanConfigParameter->setValue(false);
        self::assertAttributeEquals(false, '_mValue', $oBooleanConfigParameter);

        $oBooleanConfigParameter->setValue('true');
        self::assertAttributeEquals(true, '_mValue', $oBooleanConfigParameter);

        $oBooleanConfigParameter->setValue('false');
        self::assertAttributeEquals(false, '_mValue', $oBooleanConfigParameter);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\BooleanConfigParameter::isValidValue()
     *
     * @param BooleanConfigParameter $oBooleanConfigParameter
     */
    public function testIsValidValue($oBooleanConfigParameter)
    {
        self::assertTrue($oBooleanConfigParameter->isValidValue(true));
        self::assertTrue($oBooleanConfigParameter->isValidValue(false));
        self::assertFalse($oBooleanConfigParameter->isValidValue('string'));
    }
}
