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
namespace UserAccessManager\Tests\ObjectHandler;

use UserAccessManager\ObjectHandler\PluggableObject;

/**
 * Class PluggableObjectTest
 *
 * @package UserAccessManager\ObjectHandler
 * @coversDefaultClass \UserAccessManager\ObjectHandler\PluggableObject
 */
class PluggableObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $objectType
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PluggableObject
     */
    private function getStub($objectType)
    {
        return $this->getMockForAbstractClass(
            PluggableObject::class,
            [$objectType]
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub('objectTypeValue');
        self::assertInstanceOf(PluggableObject::class, $stub);
        self::assertAttributeEquals('objectTypeValue', 'objectType', $stub);

        return $stub;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getObjectType()
     *
     * @param PluggableObject $stub
     */
    public function testGetObjectType(PluggableObject $stub)
    {
        self::assertEquals('objectTypeValue', $stub->getObjectType());
    }
}
