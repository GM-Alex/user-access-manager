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

        self::assertInstanceOf(UserGroup::class, $userGroup);

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

        self::assertInstanceOf(UserGroup::class, $userGroup);
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
        self::assertAttributeEquals('group', 'readAccess', $userGroup);
        self::assertAttributeEquals('group', 'writeAccess', $userGroup);
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

        $database->expects($this->exactly(2))
            ->method('delete')
            ->with(
                'userGroupTable',
                ['ID' => 123]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue(['objectType']));

        $objectHandler->expects($this->once())
            ->method('getGeneralObjectType')
            ->with('objectType')
            ->will($this->returnValue(false));

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

        self::assertFalse($userGroup->delete());
        self::assertTrue($userGroup->delete());
    }
}
