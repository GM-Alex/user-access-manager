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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

/**
 * Class ConfigParameterFactoryTest
 *
 * @package UserAccessManager\Config
 */
class ConfigParameterFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     */
    public function testCanCreateInstance()
    {
        $ConfigParameterFactory = new ConfigParameterFactory();
        self::assertInstanceOf('\UserAccessManager\Config\ConfigParameterFactory', $ConfigParameterFactory);

        return $ConfigParameterFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\ConfigParameterFactory::createBooleanConfigParameter()
     *
     * @param ConfigParameterFactory $ConfigParameterFactory
     */
    public function testCreateBooleanConfigParameter(ConfigParameterFactory $ConfigParameterFactory)
    {
        $Parameter = $ConfigParameterFactory->createBooleanConfigParameter('parameterId');
        self::assertInstanceOf('\UserAccessManager\Config\BooleanConfigParameter', $Parameter);
        self::assertEquals('parameterId', $Parameter->getId());
        self::assertFalse($Parameter->getValue());

        $Parameter = $ConfigParameterFactory->createBooleanConfigParameter('parameterId', true);
        self::assertTrue($Parameter->getValue());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\ConfigParameterFactory::createStringConfigParameter()
     *
     * @param ConfigParameterFactory $ConfigParameterFactory
     */
    public function testCreateStringConfigParameter(ConfigParameterFactory $ConfigParameterFactory)
    {
        $Parameter = $ConfigParameterFactory->createStringConfigParameter('parameterId');
        self::assertInstanceOf('\UserAccessManager\Config\StringConfigParameter', $Parameter);
        self::assertEquals('parameterId', $Parameter->getId());
        self::assertEquals('', $Parameter->getValue());

        $Parameter = $ConfigParameterFactory->createStringConfigParameter('parameterId', 'test');
        self::assertEquals('test', $Parameter->getValue());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Config\ConfigParameterFactory::createSelectionConfigParameter()
     *
     * @param ConfigParameterFactory $ConfigParameterFactory
     */
    public function testCreateSelectionConfigParameter(ConfigParameterFactory $ConfigParameterFactory)
    {
        $Parameter = $ConfigParameterFactory->createSelectionConfigParameter(
            'parameterId',
            'a',
            ['a', 'b', 'c']
        );

        self::assertInstanceOf('\UserAccessManager\Config\SelectionConfigParameter', $Parameter);
        self::assertEquals('parameterId', $Parameter->getId());
        self::assertEquals('a', $Parameter->getValue());
        self::assertEquals(['a', 'b', 'c'], $Parameter->getSelections());
    }
}
