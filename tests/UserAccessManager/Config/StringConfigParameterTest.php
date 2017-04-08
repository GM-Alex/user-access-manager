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

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class StringConfigParameterTest
 *
 * @package UserAccessManager\Config
 */
class StringConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\StringConfigParameter::__construct()
     *
     * @return StringConfigParameter
     */
    public function testCanCreateInstance()
    {
        $StringConfigParameter = new StringConfigParameter('testId');

        self::assertInstanceOf('\UserAccessManager\Config\StringConfigParameter', $StringConfigParameter);
        self::assertAttributeEquals('testId', 'sId', $StringConfigParameter);
        self::assertAttributeEquals('', 'mDefaultValue', $StringConfigParameter);

        $StringConfigParameter = new StringConfigParameter('otherId', 'value');

        self::assertInstanceOf('\UserAccessManager\Config\StringConfigParameter', $StringConfigParameter);
        self::assertAttributeEquals('otherId', 'sId', $StringConfigParameter);
        self::assertAttributeEquals('value', 'mDefaultValue', $StringConfigParameter);

        return $StringConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\StringConfigParameter::isValidValue()
     *
     * @param StringConfigParameter $StringConfigParameter
     */
    public function testIsValidValue($StringConfigParameter)
    {
        self::assertTrue($StringConfigParameter->isValidValue('string'));
        self::assertFalse($StringConfigParameter->isValidValue(true));
        self::assertFalse($StringConfigParameter->isValidValue([]));
    }
}
