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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Config;

use UserAccessManager\Config\StringConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class StringConfigParameterTest
 *
 * @package UserAccessManager\Tests\Unit\Config
 * @coversDefaultClass \UserAccessManager\Config\StringConfigParameter
 */
class StringConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     *
     * @return StringConfigParameter
     */
    public function testCanCreateInstance()
    {
        $stringConfigParameter = new StringConfigParameter('testId');

        self::assertInstanceOf(StringConfigParameter::class, $stringConfigParameter);
        self::assertAttributeEquals('testId', 'id', $stringConfigParameter);
        self::assertAttributeEquals('', 'defaultValue', $stringConfigParameter);

        $stringConfigParameter = new StringConfigParameter('otherId', 'value');

        self::assertInstanceOf(StringConfigParameter::class, $stringConfigParameter);
        self::assertAttributeEquals('otherId', 'id', $stringConfigParameter);
        self::assertAttributeEquals('value', 'defaultValue', $stringConfigParameter);

        return $stringConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::isValidValue()
     *
     * @param StringConfigParameter $stringConfigParameter
     */
    public function testIsValidValue($stringConfigParameter)
    {
        self::assertTrue($stringConfigParameter->isValidValue('string'));
        self::assertFalse($stringConfigParameter->isValidValue(true));
        self::assertFalse($stringConfigParameter->isValidValue([]));
    }
}
