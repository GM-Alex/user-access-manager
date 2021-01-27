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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Config;

use Exception;
use UserAccessManager\Config\SelectionConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class SelectionConfigParameterTest
 *
 * @package UserAccessManager\Tests\Unit\Config
 * @coversDefaultClass \UserAccessManager\Config\SelectionConfigParameter
 */
class SelectionConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @group   unit
     * @covers  ::__construct()
     * @return SelectionConfigParameter
     * @throws Exception
     */
    public function testCanCreateInstance(): SelectionConfigParameter
    {
        $selectionConfigParameter = new SelectionConfigParameter('testId', 'default', ['default', 'second']);

        self::assertInstanceOf(SelectionConfigParameter::class, $selectionConfigParameter);
        self::assertEquals('testId', $selectionConfigParameter->getId());
        self::assertEquals('default', $selectionConfigParameter->getValue());
        self::assertEquals(['default', 'second'], $selectionConfigParameter->getSelections());

        return $selectionConfigParameter;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::isValidValue()
     * @param SelectionConfigParameter $selectionConfigParameter
     */
    public function testIsValidValue(SelectionConfigParameter $selectionConfigParameter)
    {
        self::assertTrue($selectionConfigParameter->isValidValue('default'));
        self::assertTrue($selectionConfigParameter->isValidValue('second'));
        self::assertFalse($selectionConfigParameter->isValidValue('aaa'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getSelections()
     * @param SelectionConfigParameter $selectionConfigParameter
     */
    public function testGetSelections(SelectionConfigParameter $selectionConfigParameter)
    {
        self::assertEquals(['default', 'second'], $selectionConfigParameter->getSelections());
    }
}
