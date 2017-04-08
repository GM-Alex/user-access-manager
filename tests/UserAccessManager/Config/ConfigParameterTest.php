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
        $Stub = $this->getStub();
        $Stub->expects($this->exactly(2))->method('isValidValue')->will($this->returnValue(true));
        $Stub->__construct('testId');

        self::assertAttributeEquals('testId', 'sId', $Stub);
        self::assertAttributeEquals(null, 'mDefaultValue', $Stub);

        $Stub->__construct('otherId', 'defaultValue');

        self::assertAttributeEquals('otherId', 'sId', $Stub);
        self::assertAttributeEquals('defaultValue', 'mDefaultValue', $Stub);

        $Stub = $this->getStub();
        $Stub->expects($this->once())->method('isValidValue')->will($this->returnValue(false));

        self::expectException('\Exception');
        $Stub->__construct('otherId', 'defaultValue');
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::getId()
     */
    public function testGetId()
    {
        $Stub = $this->getStub();
        $Stub->expects($this->once())->method('isValidValue')->will($this->returnValue(true));
        $Stub->__construct('testId');

        self::assertEquals('testId', $Stub->getId());
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::validateValue()
     */
    public function testValidateValue()
    {
        $Stub = $this->getStub();
        $Stub->expects($this->exactly(2))
            ->method('isValidValue')
            ->will($this->onConsecutiveCalls(true, false));

        self::assertNull(self::callMethod($Stub, 'validateValue', ['value']));

        self::expectException('\Exception');
        self::callMethod($Stub, 'validateValue', ['value']);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Config\ConfigParameter::setValue()
     *
     * @return ConfigParameter
     */
    public function testSetValue()
    {
        $Stub = $this->getStub();
        $Stub->expects($this->once())
            ->method('isValidValue')
            ->will($this->returnValue(true));

        $Stub->setValue('testValue');

        self::assertAttributeEquals('testValue', 'mValue', $Stub);

        return $Stub;
    }

    /**
     * @group   unit
     * @depends testSetValue
     * @covers  \UserAccessManager\Config\ConfigParameter::getValue()
     *
     * @param ConfigParameter $Stub
     */
    public function testGetValue($Stub)
    {
        self::assertEquals('testValue', $Stub->getValue());
    }
}
