<?php
/**
 * ConfigParameterFactoryTest.php
 *
 * The ConfigParameterFactoryTest unit test class file.
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
use PHPUnit\Framework\TestCase;
use UserAccessManager\Config\BooleanConfigParameter;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Config\SelectionConfigParameter;
use UserAccessManager\Config\StringConfigParameter;

/**
 * Class ConfigParameterFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Config
 * @coversDefaultClass \UserAccessManager\Config\ConfigParameterFactory
 */
class ConfigParameterFactoryTest extends TestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance(): ConfigParameterFactory
    {
        $configParameterFactory = new ConfigParameterFactory();
        self::assertInstanceOf(ConfigParameterFactory::class, $configParameterFactory);

        return $configParameterFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createBooleanConfigParameter()
     * @param ConfigParameterFactory $configParameterFactory
     * @throws Exception
     */
    public function testCreateBooleanConfigParameter(ConfigParameterFactory $configParameterFactory)
    {
        $parameter = $configParameterFactory->createBooleanConfigParameter('parameterId');
        self::assertInstanceOf(BooleanConfigParameter::class, $parameter);
        self::assertEquals('parameterId', $parameter->getId());
        self::assertFalse($parameter->getValue());

        $parameter = $configParameterFactory->createBooleanConfigParameter('parameterId', true);
        self::assertTrue($parameter->getValue());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createStringConfigParameter()
     * @param ConfigParameterFactory $configParameterFactory
     * @throws Exception
     */
    public function testCreateStringConfigParameter(ConfigParameterFactory $configParameterFactory)
    {
        $parameter = $configParameterFactory->createStringConfigParameter('parameterId');
        self::assertInstanceOf(StringConfigParameter::class, $parameter);
        self::assertEquals('parameterId', $parameter->getId());
        self::assertEquals('', $parameter->getValue());

        $parameter = $configParameterFactory->createStringConfigParameter('parameterId', 'test');
        self::assertEquals('test', $parameter->getValue());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createSelectionConfigParameter()
     * @param ConfigParameterFactory $configParameterFactory
     * @throws Exception
     */
    public function testCreateSelectionConfigParameter(ConfigParameterFactory $configParameterFactory)
    {
        $parameter = $configParameterFactory->createSelectionConfigParameter(
            'parameterId',
            'a',
            ['a', 'b', 'c']
        );

        self::assertInstanceOf(SelectionConfigParameter::class, $parameter);
        self::assertEquals('parameterId', $parameter->getId());
        self::assertEquals('a', $parameter->getValue());
        self::assertEquals(['a', 'b', 'c'], $parameter->getSelections());
    }
}
