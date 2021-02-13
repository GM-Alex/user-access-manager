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

namespace UserAccessManager\Tests\Unit\UserGroup;

use Exception;
use ReflectionException;
use stdClass;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupTypeException;

/**
 * Class UserGroupTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\UserGroup
 */
class UserGroupTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @throws UserGroupTypeException
     */
    public function testCanCreateInstance()
    {
        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getAssignmentInformationFactory()
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
            $this->getAssignmentInformationFactory(),
            1
        );

        self::assertInstanceOf(UserGroup::class, $userGroup);
    }

    /**
     * @group  unit
     * @covers ::load()
     * @return UserGroup
     * @throws UserGroupTypeException
     */
    public function testLoad(): UserGroup
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

        $dbUserGroup = new stdClass();
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
            $this->getObjectHandler(),
            $this->getAssignmentInformationFactory()
        );

        self::assertFalse($userGroup->load(1));
        self::assertEquals(null, $userGroup->getId());
        self::assertEquals(null, $userGroup->getName());
        self::assertEquals(null, $userGroup->getDescription());
        self::assertEquals('group', $userGroup->getReadAccess());
        self::assertEquals('group', $userGroup->getWriteAccess());
        self::assertEquals(null, $userGroup->getIpRange());

        self::assertTrue($userGroup->load(2));
        self::assertEquals(2, $userGroup->getId());
        self::assertEquals('groupName', $userGroup->getName());
        self::assertEquals('groupDesc', $userGroup->getDescription());
        self::assertEquals('readAccess', $userGroup->getReadAccess());
        self::assertEquals('writeAccess', $userGroup->getWriteAccess());
        self::assertEquals('ipRange;ipRange2', $userGroup->getIpRange());

        return $userGroup;
    }

    /**
     * @group   unit
     * @depends testLoad
     * @covers  ::getId()
     * @covers  ::getName()
     * @covers  ::getDescription()
     * @covers  ::getReadAccess()
     * @covers  ::getWriteAccess()
     * @covers  ::getIpRange()
     * @covers  ::getIpRangeArray()
     * @covers  ::setName()
     * @covers  ::setDescription()
     * @covers  ::setReadAccess()
     * @covers  ::setWriteAccess()
     * @covers  ::setIpRange()
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
        self::assertEquals('groupNameNew', $userGroup->getName());

        $userGroup->setDescription('groupDescNew');
        self::assertEquals('groupDescNew', $userGroup->getDescription());

        $userGroup->setReadAccess('readAccessNew');
        self::assertEquals('readAccessNew', $userGroup->getReadAccess());

        $userGroup->setWriteAccess('writeAccessNew');
        self::assertEquals('writeAccessNew', $userGroup->getWriteAccess());

        $userGroup->setIpRange(['ipRangeNew', 'ipRangeNew2']);
        self::assertEquals('ipRangeNew;ipRangeNew2', $userGroup->getIpRange());
    }

    /**
     * @group  unit
     * @covers ::save()
     * @throws UserGroupTypeException
     * @throws ReflectionException
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
            $this->getObjectHandler(),
            $this->getAssignmentInformationFactory()
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
     * @covers ::delete()
     * @covers ::removeObject()
     * @covers ::resetObjects()
     * @throws Exception
     * @throws UserGroupTypeException
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
            ->will($this->returnValue(null));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $objectHandler,
            $this->getAssignmentInformationFactory()
        );

        self::assertFalse($userGroup->delete());
        self::setValue($userGroup, 'id', 123);
        self::setValue($userGroup, 'type', 'type');

        self::assertFalse($userGroup->delete());
        self::assertTrue($userGroup->delete());
    }
}
