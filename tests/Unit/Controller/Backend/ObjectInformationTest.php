<?php
/**
 * ObjectInformationTest.php
 *
 * The ObjectInformationTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Controller\Backend;

use UserAccessManager\Controller\Backend\ObjectInformation;

/**
 * Class ObjectInformationTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\ObjectInformation
 */
class ObjectInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     */
    public function testCreateInstance()
    {
        $objectInformation = new ObjectInformation();
        self::assertInstanceOf(ObjectInformation::class, $objectInformation);
    }

    /**
     * @group unit
     * @covers ::getObjectType
     * @covers ::getObjectId
     * @covers ::getUserGroupDiff
     * @covers ::getObjectUserGroups
     * @covers ::setObjectType
     * @covers ::setObjectId
     * @covers ::setUserGroupDiff
     * @covers ::setObjectUserGroups
     */
    public function testGettersAndSetters()
    {
        $objectInformation = new ObjectInformation();

        self::assertNull($objectInformation->getObjectType());
        self::assertNull($objectInformation->getObjectId());
        self::assertEquals(0, $objectInformation->getUserGroupDiff());
        self::assertEquals([], $objectInformation->getObjectUserGroups());

        $objectInformation->setObjectType('objectType')
            ->setObjectId('objectId')
            ->setUserGroupDiff(10)
            ->setObjectUserGroups(['userGroups']);

        self::assertEquals('objectType', $objectInformation->getObjectType());
        self::assertEquals('objectId', $objectInformation->getObjectId());
        self::assertEquals(10, $objectInformation->getUserGroupDiff());
        self::assertEquals(['userGroups'], $objectInformation->getObjectUserGroups());
    }
}
