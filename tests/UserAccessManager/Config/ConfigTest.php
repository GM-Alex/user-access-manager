<?php
/**
 * ConfigTest.php
 *
 * The ConfigTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class ConfigTest
 *
 * @package UserAccessManager\Config
 */
class ConfigTest extends UserAccessManagerTestCase
{
    /**
     * @param string $type
     * @param string $postFix
     * @param mixed  $expectedSetValue
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigParameter
     */
    protected function getConfigParameter($type, $postFix = '', $expectedSetValue = null)
    {
        $configParameter = parent::getConfigParameter($type, $postFix);

        if ($expectedSetValue !== null) {
            $configParameter->expects($this->atLeastOnce())
                ->method('setValue')
                ->with($expectedSetValue);
        }

        return $configParameter;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::__construct()
     */
    public function testCanCreateInstance()
    {
        $config = new Config(
            $this->getWordpress(),
            'key'
        );

        self::assertInstanceOf(Config::class, $config);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getWpOption()
     */
    public function testGetWpOption()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('optionValueOne', 'optionValueTwo'));

        $config = new Config(
            $wordpress,
            $this->getConfigParameterFactory(),
            'key'
        );

        $optionOne = $config->getWpOption('optionOne');
        $optionOneAgain = $config->getWpOption('optionOne');

        self::assertEquals('optionValueOne', $optionOne);
        self::assertEquals('optionValueOne', $optionOneAgain);

        $optionTwo = $config->getWpOption('optionTwo');
        self::assertEquals('optionValueTwo', $optionTwo);

        $optionTwo = $config->getWpOption('optionNotExisting');
        self::assertEquals(null, $optionTwo);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::setDefaultConfigParameters()
     */
    public function testSetDefaultConfigParameters()
    {
        $config = new Config(
            $this->getWordpress(),
            $this->getConfigParameterFactory(),
            'key'
        );

        self::assertAttributeEquals([], 'defaultConfigParameters', $config);
        $config->setDefaultConfigParameters(['newOne', 'newTwo']);
        self::assertAttributeEquals(['newOne', 'newTwo'], 'defaultConfigParameters', $config);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getDefaultConfigParameters()
     * @covers \UserAccessManager\Config\Config::getConfigParameters()
     *
     * @return Config
     */
    public function testGetConfigParameters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->with('key')
            ->will($this->returnValue(null));

        $config = new Config(
            $wordpress,
            'key'
        );

        self::assertEquals([], $config->getConfigParameters());

        $optionKeys = array_keys([]);
        $testValues = array_map(function ($element) {
            return $element.'|value';
        }, $optionKeys);

        $options = array_combine($optionKeys, $testValues);
        $options['bool_false'] = 'true';
        $options['invalid'] = 'invalid';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->with('key')
            ->will($this->returnValue($options));

        $config = new Config(
            $wordpress,
            'key'
        );

        $defaultValues = [
            'bool_false' => $this->getConfigParameter('boolean', 'false', 'true'),
            'bool_true' => $this->getConfigParameter('boolean', 'true'),
            'string' => $this->getConfigParameter('string'),
            'selection' => $this->getConfigParameter('selection')
        ];

        $config->setDefaultConfigParameters($defaultValues);
        $parameters = $config->getConfigParameters();

        self::assertEquals($parameters['bool_false']->getValue(), 'booleanfalseValue');
        self::assertEquals($parameters['bool_true']->getValue(), 'booleantrueValue');
        self::assertEquals($parameters['string']->getValue(), 'stringValue');
        self::assertEquals($parameters['selection']->getValue(), 'selectionValue');

        return $config;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::setConfigParameters()
     */
    public function testSetConfigParameters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $wordpress->expects($this->once())
            ->method('updateOption')
            ->with(
                'key',
                [
                    'booleanId' => 'booleanValue',
                    'stringId' => 'stringValue'
                ]
            );

        $config = new Config(
            $wordpress,
            'key'
        );

        $defaultValues = [
            'bool' => $this->getConfigParameter('boolean'),
            'string' => $this->getConfigParameter('string', '', 'newStringValue'),
        ];

        $config->setDefaultConfigParameters($defaultValues);
        $config->setConfigParameters(['string' => 'newStringValue']);
    }

    /**
     * @group   unit
     * @depends testGetConfigParameters
     * @covers  \UserAccessManager\Config\Config::flushConfigParameters()
     *
     * @param Config $config
     */
    public function testFlushConfigParameters(Config $config)
    {
        self::assertAttributeNotEmpty('configParameters', $config);
        $config->flushConfigParameters();
        self::assertAttributeEquals(null, 'configParameters', $config);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Config\Config::getParameterValue()
     */
    public function testGetParameterValue()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->returnValue(null));

        $config = new Config(
            $wordpress,
            'key'
        );

        $defaultValues = [
            'bool' => $this->getConfigParameter('boolean')
        ];

        $config->setDefaultConfigParameters($defaultValues);

        self::assertEquals('booleanValue', $config->getParameterValue('bool'));

        self::expectException('\Exception');
        $config->getParameterValue('undefined');
    }
}
