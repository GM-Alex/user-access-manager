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
        $oSelectionConfigParameter = new SelectionConfigParameter('testId', 'default', ['default', 'second']);

        self::assertInstanceOf('\UserAccessManager\Config\SelectionConfigParameter', $oSelectionConfigParameter);
        self::assertAttributeEquals('testId', 'sId', $oSelectionConfigParameter);
        self::assertAttributeEquals('default', 'mDefaultValue', $oSelectionConfigParameter);
        self::assertAttributeEquals(['default', 'second'], 'aSelections', $oSelectionConfigParameter);

        return $oSelectionConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\SelectionConfigParameter::isValidValue()
     *
     * @param SelectionConfigParameter $oSelectionConfigParameter
     */
    public function testIsValidValue($oSelectionConfigParameter)
    {
        self::assertTrue($oSelectionConfigParameter->isValidValue('default'));
        self::assertTrue($oSelectionConfigParameter->isValidValue('second'));
        self::assertFalse($oSelectionConfigParameter->isValidValue('aaa'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\SelectionConfigParameter::getSelections()
     *
     * @param SelectionConfigParameter $oSelectionConfigParameter
     */
    public function testGetSelections($oSelectionConfigParameter)
    {
        self::assertEquals(['default', 'second'], $oSelectionConfigParameter->getSelections());
    }
}
