<?php
/**
 * StringConfigParameterTest.php
 *
 * The StringConfigParameterTest unit test class file.
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

/**
 * Class StringConfigParameterTest
 *
 * @package UserAccessManager\Config
 */
class StringConfigParameterTest extends \UserAccessManagerTestCase
{
    /**
     * @group unit
     * @covers  \UserAccessManager\Config\StringConfigParameter::__construct()
     *
     * @return StringConfigParameter
     */
    public function testCanCreateInstance()
    {
        $oStringConfigParameter = new StringConfigParameter('testId');

        self::assertInstanceOf('\UserAccessManager\Config\StringConfigParameter', $oStringConfigParameter);
        self::assertAttributeEquals('testId', '_sId', $oStringConfigParameter);
        self::assertAttributeEquals('', '_mDefaultValue', $oStringConfigParameter);

        $oStringConfigParameter = new StringConfigParameter('otherId', 'value');

        self::assertInstanceOf('\UserAccessManager\Config\StringConfigParameter', $oStringConfigParameter);
        self::assertAttributeEquals('otherId', '_sId', $oStringConfigParameter);
        self::assertAttributeEquals('value', '_mDefaultValue', $oStringConfigParameter);

        return $oStringConfigParameter;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\StringConfigParameter::isValidValue()
     * @depends testCanCreateInstance
     *
     * @param StringConfigParameter $oStringConfigParameter
     */
    public function testIsValidValue($oStringConfigParameter)
    {
        self::assertEquals(true, $oStringConfigParameter->isValidValue('string'));
        self::assertEquals(false, $oStringConfigParameter->isValidValue(true));
        self::assertEquals(false, $oStringConfigParameter->isValidValue(array()));
    }
}
