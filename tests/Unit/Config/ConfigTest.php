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

namespace UserAccessManager\Tests\Unit\Config;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\Config\StringConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class ConfigTest
 *
 * @package UserAccessManager\Tests\Unit\Config
 * @coversDefaultClass \UserAccessManager\Config\Config
 */
class ConfigTest extends UserAccessManagerTestCase
{
    /**
     * @param string $type
     * @param string $postFix
     * @param null $expectedSetValue
     * @return MockObject|ConfigParameter
     */
    protected function getConfigParameter(string $type, $postFix = '', $expectedSetValue = null)
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
     * @covers ::__construct()
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
     * @covers ::getWpOption()
     */
    public function testGetWpOption()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('optionValueOne', 'optionValueTwo'));

        $config = new Config(
            $wordpress,
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
     * @covers ::setDefaultConfigParameters()
     */
    public function testSetDefaultConfigParameters()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getOption')
            ->will($this->onConsecutiveCalls(
                ['newOne' => 'newOne']
            ));

        $config = new Config(
            $wordpress,
            'key'
        );

        $stringParameter = $this->createMock(StringConfigParameter::class);
        self::assertEquals([], $config->getConfigParameters());
        $config->flushConfigParameters();
        $config->setDefaultConfigParameters(['newOne' => $stringParameter]);
        self::assertEquals(['newOne' => $stringParameter], $config->getConfigParameters());
    }

    /**
     * @group  unit
     * @covers ::getDefaultConfigParameters()
     * @covers ::getConfigParameters()
     * @return Config
     */
    public function testGetConfigParameters(): Config
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
            return $element . '|value';
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

        self::assertEquals('booleanfalseValue', $parameters['bool_false']->getValue());
        self::assertEquals('booleantrueValue', $parameters['bool_true']->getValue());
        self::assertEquals('stringValue', $parameters['string']->getValue());
        self::assertEquals('selectionValue', $parameters['selection']->getValue());

        return $config;
    }

    /**
     * @group  unit
     * @covers ::setConfigParameters()
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
     * @covers  ::flushConfigParameters()
     * @param Config $config
     */
    public function testFlushConfigParameters(Config $config)
    {
        self::assertNotEquals([], $config->getConfigParameters());
        $config->flushConfigParameters();
        self::assertEquals([], $config->getConfigParameters());
    }

    /**
     * @group  unit
     * @covers ::getParameterValue()
     * @covers ::getParameterValueRaw()
     * @throws Exception
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
        self::assertNull($config->getParameterValue('undefined'));

        self::expectException('\Exception');
        $config->getParameterValueRaw('undefined');
    }
}
