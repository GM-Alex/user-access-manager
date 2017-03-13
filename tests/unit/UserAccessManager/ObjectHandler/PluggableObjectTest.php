<?php
/**
 * PluggableObjectTest.php
 *
 * The PluggableObjectTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\ObjectHandler;

/**
 * Class PluggableObjectTest
 *
 * @package UserAccessManager\ObjectHandler
 */
class PluggableObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $sName
     * @param $sReference
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PluggableObject
     */
    private function getStub($sName, $sReference)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\ObjectHandler\PluggableObject',
            [$sName, $sReference]
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\ObjectHandler\PluggableObject::__construct()
     */
    public function testCanCreateInstance()
    {
        $oStub = $this->getStub('nameValue', 'referenceValue');
        self::assertInstanceOf('\UserAccessManager\ObjectHandler\PluggableObject', $oStub);
        self::assertAttributeEquals('nameValue', '_sName', $oStub);
        self::assertAttributeEquals('referenceValue', '_sReference', $oStub);

        return $oStub;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\ObjectHandler\PluggableObject::getName()
     *
     * @param PluggableObject $oStub
     */
    public function testGetName(PluggableObject $oStub)
    {
        self::assertEquals('nameValue', $oStub->getName());
    }
}
