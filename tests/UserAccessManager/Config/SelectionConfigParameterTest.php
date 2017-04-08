<?php
/**
 * SelectionConfigParameterTest.php
 *
 * The SelectionConfigParameterTest unit test class file.
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
 * Class SelectionConfigParameterTest
 *
 * @package UserAccessManager\Config
 */
class SelectionConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\SelectionConfigParameter::__construct()
     *
     * @return SelectionConfigParameter
     */
    public function testCanCreateInstance()
    {
        $SelectionConfigParameter = new SelectionConfigParameter('testId', 'default', ['default', 'second']);

        self::assertInstanceOf('\UserAccessManager\Config\SelectionConfigParameter', $SelectionConfigParameter);
        self::assertAttributeEquals('testId', 'sId', $SelectionConfigParameter);
        self::assertAttributeEquals('default', 'mDefaultValue', $SelectionConfigParameter);
        self::assertAttributeEquals(['default', 'second'], 'aSelections', $SelectionConfigParameter);

        return $SelectionConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\SelectionConfigParameter::isValidValue()
     *
     * @param SelectionConfigParameter $SelectionConfigParameter
     */
    public function testIsValidValue($SelectionConfigParameter)
    {
        self::assertTrue($SelectionConfigParameter->isValidValue('default'));
        self::assertTrue($SelectionConfigParameter->isValidValue('second'));
        self::assertFalse($SelectionConfigParameter->isValidValue('aaa'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\SelectionConfigParameter::getSelections()
     *
     * @param SelectionConfigParameter $SelectionConfigParameter
     */
    public function testGetSelections($SelectionConfigParameter)
    {
        self::assertEquals(['default', 'second'], $SelectionConfigParameter->getSelections());
    }
}
