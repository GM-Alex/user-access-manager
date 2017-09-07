<?php
/**
 * Update5Test.php
 *
 * The Update5Test unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Setup\Update;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Setup\Update\Update5;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class Update5Test
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\Update5
 */
class Update5Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new Update5(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(Update5::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new Update5(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.5.1', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers ::update()
     */
    public function testUpdate()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $dbObject = new \stdClass();
        $dbObject->objectId = 123;
        $dbObject->groupId = 321;
        $dbObject->objectType = 'customPostType';

        $database->expects($this->exactly(2))
            ->method('getResults')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT object_id AS objectId, object_type AS objectType, group_id AS groupId
                    FROM userGroupToObjectTable
                    WHERE general_object_type = \'\''
                )
            )
            ->will($this->returnValue([$dbObject, $dbObject]));

        $database->expects($this->exactly(4))
            ->method('update')
            ->with(
                'userGroupToObjectTable',
                ['general_object_type' => 'generalCustomPostType'],
                ['object_id' => 123, 'group_id' => 321, 'object_type' => 'customPostType']
            )
            ->will($this->onConsecutiveCalls(true, false, true, true));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(4))
            ->method('getGeneralObjectType')
            ->with('customPostType')
            ->will($this->returnValue('generalCustomPostType'));

        $update = new Update5($database, $objectHandler);
        self::assertFalse($update->update());
        self::assertTrue($update->update());
    }
}
