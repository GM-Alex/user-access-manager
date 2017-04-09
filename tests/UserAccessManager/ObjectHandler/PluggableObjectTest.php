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
 * @version   SVN: $id$
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
     * @param $name
     * @param $reference
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PluggableObject
     */
    private function getStub($name, $reference)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\ObjectHandler\PluggableObject',
            [$name, $reference]
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\ObjectHandler\PluggableObject::__construct()
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub('nameValue', 'referenceValue');
        self::assertInstanceOf('\UserAccessManager\ObjectHandler\PluggableObject', $stub);
        self::assertAttributeEquals('nameValue', 'name', $stub);
        self::assertAttributeEquals('referenceValue', 'reference', $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\ObjectHandler\PluggableObject::getName()
     *
     * @param PluggableObject $stub
     */
    public function testGetName(PluggableObject $stub)
    {
        self::assertEquals('nameValue', $stub->getName());
    }
}
