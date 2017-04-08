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

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class ConfigParameterTest
 *
 * @package UserAccessManager\Config
 */
class ConfigParameterTest extends UserAccessManagerTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigParameter
     */
    private function getStub()
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\Config\ConfigParameter',
            [],
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
        $oStub = $this->getStub();
        $oStub->expects($this->exactly(2))->method('isValidValue')->will($this->returnValue(true));
        $oStub->__construct('testId');

        self::assertAttributeEquals('testId', '_sId', $oStub);
        self::assertAttributeEquals(null, '_mDefaultValue', $oStub);

        $oStub->__construct('otherId', 'defaultValue');

        self::assertAttributeEquals('otherId', '_sId', $oStub);
        self::assertAttributeEquals('defaultValue', '_mDefaultValue', $oStub);

        $oStub = $this->getStub();
        $oStub->expects($this->once())->method('isValidValue')->will($this->returnValue(false));

        self::expectException('\Exception');
        $oStub->__construct('otherId', 'defaultValue');
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::getId()
     */
    public function testGetId()
    {
        $oStub = $this->getStub();
        $oStub->expects($this->once())->method('isValidValue')->will($this->returnValue(true));
        $oStub->__construct('testId');

        self::assertEquals('testId', $oStub->getId());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::_validateValue()
     */
    public function testValidateValue()
    {
        $oStub = $this->getStub();
        $oStub->expects($this->exactly(2))
            ->method('isValidValue')
            ->will($this->onConsecutiveCalls(true, false));

        self::assertNull(self::callMethod($oStub, '_validateValue', ['value']));

        self::expectException('\Exception');
        self::callMethod($oStub, '_validateValue', ['value']);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::setValue()
     *
     * @return ConfigParameter
     */
    public function testSetValue()
    {
        $oStub = $this->getStub();
        $oStub->expects($this->once())
            ->method('isValidValue')
            ->will($this->returnValue(true));

        $oStub->setValue('testValue');

        self::assertAttributeEquals('testValue', '_mValue', $oStub);

        return $oStub;
    }

    /**
     * @group   unit
     * @depends testSetValue
     * @covers  \UserAccessManager\Config\ConfigParameter::getValue()
     *
     * @param ConfigParameter $oStub
     */
    public function testGetValue($oStub)
    {
        self::assertEquals('testValue', $oStub->getValue());
    }
}
