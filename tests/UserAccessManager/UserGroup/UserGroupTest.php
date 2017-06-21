<?php
/**
 * UserGroupTest.php
 *
 * The UserGroupTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class UserGroupTest
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::__construct()
     */
    public function testCanCreateInstance()
    {
        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $userGroup);

        $database = $this->getDatabase();
        $database->expects($this->once())
            ->method('prepare');

        $database->expects($this->once())
            ->method('getUserGroupTable');

        $database->expects($this->once())
            ->method('getRow');

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            1
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $userGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::load()
     *
     * @return UserGroup
     */
    public function testLoad()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('prepare')
            ->withConsecutive(
                [
                    new MatchIgnoreWhitespace(
                        'SELECT *
                            FROM userGroupTable
                            WHERE ID = %d
                            LIMIT 1'
                    ),
                    1
                ],
                [
                    new MatchIgnoreWhitespace(
                        'SELECT *
                        FROM userGroupTable
                        WHERE ID = %d
                        LIMIT 1'
                    ),
                    2
                ]
            )
            ->will($this->returnValue('queryString'));

        $dbUserGroup = new \stdClass();
        $dbUserGroup->groupname = 'groupName';
        $dbUserGroup->groupdesc = 'groupDesc';
        $dbUserGroup->read_access = 'readAccess';
        $dbUserGroup->write_access = 'writeAccess';
        $dbUserGroup->ip_range = 'ipRange;ipRange2';

        $database->expects($this->exactly(2))
            ->method('getRow')
            ->with('queryString')
            ->will($this->onConsecutiveCalls(null, $dbUserGroup));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertFalse($userGroup->load(1));
        self::assertAttributeEquals(null, 'id', $userGroup);
        self::assertAttributeEquals(null, 'name', $userGroup);
        self::assertAttributeEquals(null, 'description', $userGroup);
        self::assertAttributeEquals(null, 'readAccess', $userGroup);
        self::assertAttributeEquals(null, 'writeAccess', $userGroup);
        self::assertAttributeEquals(null, 'ipRange', $userGroup);

        self::assertTrue($userGroup->load(2));
        self::assertAttributeEquals(2, 'id', $userGroup);
        self::assertAttributeEquals('groupName', 'name', $userGroup);
        self::assertAttributeEquals('groupDesc', 'description', $userGroup);
        self::assertAttributeEquals('readAccess', 'readAccess', $userGroup);
        self::assertAttributeEquals('writeAccess', 'writeAccess', $userGroup);
        self::assertAttributeEquals('ipRange;ipRange2', 'ipRange', $userGroup);

        return $userGroup;
    }

    /**
     * @group   unit
     * @depends testLoad
     * @covers  \UserAccessManager\UserGroup\UserGroup::getId()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getName()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getDescription()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getReadAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getWriteAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getIpRange()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getIpRangeArray()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setName()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setDescription()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setReadAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setWriteAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setIpRange()
     *
     * @param UserGroup $userGroup
     */
    public function testSimpleGetterSetter(UserGroup $userGroup)
    {
        self::assertEquals(2, $userGroup->getId());
        self::assertEquals('groupName', $userGroup->getName());
        self::assertEquals('groupDesc', $userGroup->getDescription());
        self::assertEquals('readAccess', $userGroup->getReadAccess());
        self::assertEquals('writeAccess', $userGroup->getWriteAccess());
        self::assertEquals(['ipRange', 'ipRange2'], $userGroup->getIpRangeArray());
        self::assertEquals('ipRange;ipRange2', $userGroup->getIpRange());

        $userGroup->setName('groupNameNew');
        self::assertAttributeEquals('groupNameNew', 'name', $userGroup);

        $userGroup->setDescription('groupDescNew');
        self::assertAttributeEquals('groupDescNew', 'description', $userGroup);

        $userGroup->setReadAccess('readAccessNew');
        self::assertAttributeEquals('readAccessNew', 'readAccess', $userGroup);

        $userGroup->setWriteAccess('writeAccessNew');
        self::assertAttributeEquals('writeAccessNew', 'writeAccess', $userGroup);

        $userGroup->setIpRange(['ipRangeNew', 'ipRangeNew2']);
        self::assertAttributeEquals('ipRangeNew;ipRangeNew2', 'ipRange', $userGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::save()
     */
    public function testSave()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(4))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('insert')
            ->with(
                'userGroupTable',
                [
                    'groupname' => 'groupName',
                    'groupdesc' => 'groupDesc',
                    'read_access' => 'readAccess',
                    'write_access' => 'writeAccess',
                    'ip_range' => 'ipRange;ipRange2'
                ]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $database->expects($this->once())
            ->method('getLastInsertId')
            ->will($this->returnValue(123));

        $database->expects($this->exactly(2))
            ->method('update')
            ->with(
                'userGroupTable',
                [
                    'groupname' => 'groupName',
                    'groupdesc' => 'groupDesc',
                    'read_access' => 'readAccess',
                    'write_access' => 'writeAccess',
                    'ip_range' => 'ipRange;ipRange2'
                ],
                ['ID' => 2]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        $userGroup->setName('groupName');
        $userGroup->setDescription('groupDesc');
        $userGroup->setReadAccess('readAccess');
        $userGroup->setWriteAccess('writeAccess');
        $userGroup->setIpRange(['ipRange', 'ipRange2']);

        self::assertFalse($userGroup->save());
        self::assertNull($userGroup->getId());
        self::assertTrue($userGroup->save());
        self::assertEquals(123, $userGroup->getId());

        self::setValue($userGroup, 'id', 2);
        self::assertFalse($userGroup->save());
        self::assertTrue($userGroup->save());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::delete()
     * @covers \UserAccessManager\UserGroup\UserGroup::removeObject()
     * @covers \UserAccessManager\UserGroup\UserGroup::resetObjects()
     */
    public function testDelete()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('delete')
            ->with(
                'userGroupTable',
                ['ID' => 123]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $database->expects($this->exactly(4))
            ->method('prepare')
            ->withConsecutive(
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                        WHERE group_id = %d
                          AND group_type = \'%s\'
                          AND (general_object_type = \'%s\' OR object_type = \'%s\')'
                    ),
                    [123, 'type', 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                        WHERE group_id = %d
                          AND group_type = \'%s\'
                          AND (general_object_type = \'%s\' OR object_type = \'%s\')'
                    ),
                    [123, 'type', 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                            WHERE group_id = %d
                              AND group_type = \'%s\'
                              AND (general_object_type = \'%s\' OR object_type = \'%s\')'
                    ),
                    [123, 'type', 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                            WHERE group_id = %d
                              AND group_type = \'%s\'
                              AND (general_object_type = \'%s\' OR object_type = \'%s\')
                              AND object_id = %d'
                    ),
                    [123, 'type', 'objectType', 'objectType', 1]
                ]
            )
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->exactly(4))
            ->method('query')
            ->with('preparedQuery')
            ->will($this->onConsecutiveCalls(true, false, true, true));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue(['objectType']));

        $objectHandler->expects($this->exactly(5))
            ->method('isValidObjectType')
            ->withConsecutive(['objectType'], ['invalid'], ['objectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(true, false, true));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::assertFalse($userGroup->delete());
        self::setValue($userGroup, 'id', 123);
        self::setValue($userGroup, 'type', 'type');

        self::setValue($userGroup, 'assignedObjects', [1 => 1]);
        self::setValue($userGroup, 'roleMembership', [2 => 2]);
        self::setValue($userGroup, 'userMembership', [3 => 3]);
        self::setValue($userGroup, 'termMembership', [4 => 4]);
        self::setValue($userGroup, 'postMembership', [5 => 5]);
        self::setValue($userGroup, 'fullObjectMembership', [6 => 6]);

        self::assertFalse($userGroup->delete());

        self::assertAttributeEquals([1 => 1], 'assignedObjects', $userGroup);
        self::assertAttributeEquals([2 => 2], 'roleMembership', $userGroup);
        self::assertAttributeEquals([3 => 3], 'userMembership', $userGroup);
        self::assertAttributeEquals([4 => 4], 'termMembership', $userGroup);
        self::assertAttributeEquals([5 => 5], 'postMembership', $userGroup);
        self::assertAttributeEquals([6 => 6], 'fullObjectMembership', $userGroup);

        self::assertTrue($userGroup->delete());


        self::setValue($userGroup, 'assignedObjects', [1 => 1]);
        self::setValue($userGroup, 'roleMembership', [2 => 2]);
        self::setValue($userGroup, 'userMembership', [3 => 3]);
        self::setValue($userGroup, 'termMembership', [4 => 4]);
        self::setValue($userGroup, 'postMembership', [5 => 5]);
        self::setValue($userGroup, 'fullObjectMembership', [6 => 6]);

        self::assertFalse($userGroup->removeObject('invalid'));
        self::assertFalse($userGroup->removeObject('objectType'));

        self::assertAttributeEquals([1 => 1], 'assignedObjects', $userGroup);
        self::assertAttributeEquals([2 => 2], 'roleMembership', $userGroup);
        self::assertAttributeEquals([3 => 3], 'userMembership', $userGroup);
        self::assertAttributeEquals([4 => 4], 'termMembership', $userGroup);
        self::assertAttributeEquals([5 => 5], 'postMembership', $userGroup);
        self::assertAttributeEquals([6 => 6], 'fullObjectMembership', $userGroup);

        self::assertTrue($userGroup->removeObject('objectType'));
        self::assertTrue($userGroup->removeObject('objectType', 1));

        self::assertAttributeEquals([], 'assignedObjects', $userGroup);
        self::assertAttributeEquals([], 'roleMembership', $userGroup);
        self::assertAttributeEquals([], 'userMembership', $userGroup);
        self::assertAttributeEquals([], 'termMembership', $userGroup);
        self::assertAttributeEquals([], 'postMembership', $userGroup);
        self::assertAttributeEquals([], 'fullObjectMembership', $userGroup);
    }
}
