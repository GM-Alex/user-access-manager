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
 * Class ConfigParameterTest
 *
 * @package UserAccessManager\Config
 */
class ConfigParameterTest extends \UserAccessManagerTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_oStub;

    /**
     * Create default mocked objects.
     */
    public function setUp()
    {
        $this->_oStub = $this->getMockForAbstractClass(
            '\UserAccessManager\Config\ConfigParameter',
            array(),
            '',
            false,
            true,
            true
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::__construct()
     */
    public function testCanCreateInstance()
    {
        $oStub = clone $this->_oStub;
        $oStub->expects($this->exactly(2))->method('isValidValue')->willReturn(true);

        /**
         * @var ConfigParameter $oStub
         */
        $oStub->__construct('testId');

        self::assertAttributeEquals('testId', '_sId', $oStub);
        self::assertAttributeEquals(null, '_mDefaultValue', $oStub);

        $oStub->__construct('otherId', 'defaultValue');

        self::assertAttributeEquals('otherId', '_sId', $oStub);
        self::assertAttributeEquals('defaultValue', '_mDefaultValue', $oStub);

        $oStub = clone $this->_oStub;
        $oStub->expects($this->exactly(1))->method('isValidValue')->willReturn(false);

        self::expectException('\Exception');
        $oStub->__construct('otherId', 'defaultValue');
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::getId()
     */
    public function testGetId()
    {
        $oStub = clone $this->_oStub;
        $oStub->expects($this->exactly(1))->method('isValidValue')->willReturn(true);

        /**
         * @var ConfigParameter $oStub
         */
        $oStub->__construct('testId');

        self::assertEquals('testId', $oStub->getId());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::_validateValue()
     */
    public function testValidateValue()
    {
        $oStub = clone $this->_oStub;
        $oStub->expects($this->exactly(2))
            ->method('isValidValue')
            ->will($this->onConsecutiveCalls(true, false));

        self::assertNull(self::callMethod($oStub, '_validateValue', array('value')));

        self::expectException('\Exception');
        self::callMethod($oStub, '_validateValue', array('value'));
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::setValue()
     *
     * @return ConfigParameter
     */
    public function testSetValue()
    {
        $oStub = clone $this->_oStub;
        $oStub->expects($this->once())
            ->method('isValidValue')
            ->will($this->returnValue(true));

        /**
         * @var ConfigParameter $oStub
         */
        $oStub->setValue('testValue');

        self::assertAttributeEquals('testValue', '_mValue', $oStub);

        return $oStub;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::getValue()
     * @depends testSetValue
     *
     * @param ConfigParameter $oStub
     */
    public function testGetValue($oStub)
    {

        self::assertEquals('testValue', $oStub->getValue());
    }
}
