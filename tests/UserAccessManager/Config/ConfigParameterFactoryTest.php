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
        $oConfigParameterFactory = new ConfigParameterFactory();
        self::assertInstanceOf('\UserAccessManager\Config\ConfigParameterFactory', $oConfigParameterFactory);

        return $oConfigParameterFactory;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameterFactory::createBooleanConfigParameter()
     * @depends testCanCreateInstance
     *
     * @param ConfigParameterFactory $oConfigParameterFactory
     */
    public function testCreateBooleanConfigParameter(ConfigParameterFactory $oConfigParameterFactory)
    {
        $oParameter = $oConfigParameterFactory->createBooleanConfigParameter('parameterId');
        self::assertInstanceOf('\UserAccessManager\Config\BooleanConfigParameter', $oParameter);
        self::assertEquals('parameterId', $oParameter->getId());
        self::assertEquals(false, $oParameter->getValue());

        $oParameter = $oConfigParameterFactory->createBooleanConfigParameter('parameterId', true);
        self::assertEquals(true, $oParameter->getValue());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameterFactory::createStringConfigParameter()
     * @depends testCanCreateInstance
     *
     * @param ConfigParameterFactory $oConfigParameterFactory
     */
    public function testCreateStringConfigParameter(ConfigParameterFactory $oConfigParameterFactory)
    {
        $oParameter = $oConfigParameterFactory->createStringConfigParameter('parameterId');
        self::assertInstanceOf('\UserAccessManager\Config\StringConfigParameter', $oParameter);
        self::assertEquals('parameterId', $oParameter->getId());
        self::assertEquals('', $oParameter->getValue());

        $oParameter = $oConfigParameterFactory->createStringConfigParameter('parameterId', 'test');
        self::assertEquals('test', $oParameter->getValue());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameterFactory::createSelectionConfigParameter()
     * @depends testCanCreateInstance
     *
     * @param ConfigParameterFactory $oConfigParameterFactory
     */
    public function testCreateSelectionConfigParameter(ConfigParameterFactory $oConfigParameterFactory)
    {
        $oParameter = $oConfigParameterFactory->createSelectionConfigParameter(
            'parameterId',
            'a',
            array('a', 'b', 'c')
        );

        self::assertInstanceOf('\UserAccessManager\Config\SelectionConfigParameter', $oParameter);
        self::assertEquals('parameterId', $oParameter->getId());
        self::assertEquals('a', $oParameter->getValue());
        self::assertEquals(array('a', 'b', 'c'), $oParameter->getSelections());
    }
}
