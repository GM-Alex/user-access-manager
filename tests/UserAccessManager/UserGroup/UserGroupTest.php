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
            $this->getConfig(),
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
            $this->getConfig(),
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
                            WHERE ID = %s
                            LIMIT 1'
                    ),
                    1
                ],
                [
                    new MatchIgnoreWhitespace(
                        'SELECT *
                        FROM userGroupTable
                        WHERE ID = %s
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
            $this->getConfig(),
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
            $this->getConfig(),
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
                          AND (general_object_type = \'%s\' OR object_type = \'%s\')'
                    ),
                    [123, 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                        WHERE group_id = %d
                          AND (general_object_type = \'%s\' OR object_type = \'%s\')'
                    ),
                    [123, 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                            WHERE group_id = %d
                              AND (general_object_type = \'%s\' OR object_type = \'%s\')'
                    ),
                    [123, 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                            WHERE group_id = %d
                              AND (general_object_type = \'%s\' OR object_type = \'%s\')
                              AND object_id = %d'
                    ),
                    [123, 'objectType', 'objectType', 1]
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
            $this->getConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::assertFalse($userGroup->delete());
        self::setValue($userGroup, 'id', 123);

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

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::addObject()
     * @covers \UserAccessManager\UserGroup\UserGroup::resetObjects()
     */
    public function testAddObject()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('insert')
            ->with(
                'userGroupToObjectTable',
                [
                    'group_id' => 123,
                    'object_id' => 321,
                    'general_object_type' => 'generalObjectType',
                    'object_type' => 'objectType'
                ],
                ['%d', '%s', '%s', '%s']
            )
            ->will($this->onConsecutiveCalls(false, true));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(5))
            ->method('getGeneralObjectType')
            ->withConsecutive(
                ['invalid'],
                ['generalObjectType'],
                ['notValidObjectType'],
                ['objectType'],
                ['objectType']
            )
            ->will($this->onConsecutiveCalls(
                null,
                null,
                'generalNotValidObjectType',
                'generalObjectType',
                'generalObjectType'
            ));

        $objectHandler->expects($this->exactly(3))
            ->method('isValidObjectType')
            ->withConsecutive(['notValidObjectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($userGroup, 'id', 123);
        self::setValue($userGroup, 'assignedObjects', [1 => 'post', 2 => 'post']);
        self::setValue($userGroup, 'roleMembership', [1 => 'role', 2 => 'role']);
        self::setValue($userGroup, 'userMembership', [1 => 'user', 2 => 'user']);
        self::setValue($userGroup, 'termMembership', [1 => 'term', 2 => 'term']);
        self::setValue($userGroup, 'postMembership', [1 => 'post', 2 => 'post']);
        self::setValue($userGroup, 'fullObjectMembership', [1 => 'post', 2 => 'post']);

        self::assertFalse($userGroup->addObject('invalid', 321));
        self::assertFalse($userGroup->addObject('generalObjectType', 321));
        self::assertFalse($userGroup->addObject('notValidObjectType', 321));
        self::assertFalse($userGroup->addObject('objectType', 321));
        self::assertTrue($userGroup->addObject('objectType', 321));

        self::assertAttributeEquals([], 'assignedObjects', $userGroup);
        self::assertAttributeEquals([], 'roleMembership', $userGroup);
        self::assertAttributeEquals([], 'userMembership', $userGroup);
        self::assertAttributeEquals([], 'termMembership', $userGroup);
        self::assertAttributeEquals([], 'postMembership', $userGroup);
        self::assertAttributeEquals([], 'fullObjectMembership', $userGroup);
    }

    /**
     * Generates return values.
     *
     * @param int    $number
     * @param string $type
     *
     * @return array
     */
    private function generateReturn($number, $type)
    {
        $returns = [];

        for ($counter = 1; $counter <= $number; $counter++) {
            $return = new \stdClass();
            $return->id = $counter;
            $return->objectType = $type;
            $returns[] = $return;
        }

        return $returns;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getAssignedObjects()
     * @covers  \UserAccessManager\UserGroup\UserGroup::isObjectAssignedToGroup()
     */
    public function testAssignedObject()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $query = 'SELECT object_id AS id, object_type AS objectType
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $database->expects($this->exactly(3))
            ->method('prepare')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($query), [123, 'noResultObjectType', 'noResultObjectType']],
                [new MatchIgnoreWhitespace($query), [123, 'objectType', 'objectType']],
                [new MatchIgnoreWhitespace($query), [123, 'something', 'something']]
            )
            ->will($this->onConsecutiveCalls(
                'nonResultPreparedQuery',
                'preparedQuery',
                'nonResultSomethingPreparedQuery'
            ));

        $database->expects($this->exactly(3))
            ->method('getResults')
            ->withConsecutive(
                ['nonResultPreparedQuery'],
                ['preparedQuery'],
                ['nonResultSomethingPreparedQuery']
            )
            ->will($this->onConsecutiveCalls(null, $this->generateReturn(3, 'objectType'), null));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($userGroup, 'id', 123);

        $result = self::callMethod($userGroup, 'getAssignedObjects', ['noResultObjectType']);
        self::assertEquals([], $result);
        self::assertAttributeEquals(['noResultObjectType' => []], 'assignedObjects', $userGroup);

        $result = self::callMethod($userGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 'objectType', 2 => 'objectType', 3 => 'objectType'], $result);
        self::assertAttributeEquals(
            ['noResultObjectType' => [], 'objectType' => [1 => 'objectType', 2 => 'objectType', 3 => 'objectType']],
            'assignedObjects',
            $userGroup
        );

        $result = self::callMethod($userGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 'objectType', 2 => 'objectType', 3 => 'objectType'], $result);

        $result = self::callMethod($userGroup, 'isObjectAssignedToGroup', ['objectType', 1]);
        self::assertTrue($result);
        $result = self::callMethod($userGroup, 'isObjectAssignedToGroup', ['objectType', 2]);
        self::assertTrue($result);
        $result = self::callMethod($userGroup, 'isObjectAssignedToGroup', ['objectType', 3]);
        self::assertTrue($result);

        $result = self::callMethod($userGroup, 'isObjectAssignedToGroup', ['objectType', 4]);
        self::assertFalse($result);
        $result = self::callMethod($userGroup, 'isObjectAssignedToGroup', ['noResultObjectType', 1]);
        self::assertFalse($result);
        $result = self::callMethod($userGroup, 'isObjectAssignedToGroup', ['something', 1]);
        self::assertFalse($result);
    }

    /**
     * Returns the database mock for the member tests
     *
     * @param array $types
     * @param array $getResultsWith
     * @param array $getResultsWill
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    private function getDatabaseMockForMemberTests(
        array $types,
        array $getResultsWith = [],
        array $getResultsWill = []
    ) {
        $query = 'SELECT object_id AS id, object_type AS objectType
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $prepareWith = [];
        $prepareWill = [];

        foreach ($types as $type => $numberOfReturn) {
            $prepareWith[] = [new MatchIgnoreWhitespace($query), [123, "_{$type}_", "_{$type}_"]];
            $prepareWill[] = "{$type}PreparedQuery";
            $getResultsWith[] = ["{$type}PreparedQuery"];
            $getResultsWill[] = $this->generateReturn($numberOfReturn, $type);
        }

        $database = $this->getDatabase();

        $database->expects($this->any())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(count($prepareWith)))
            ->method('prepare')
            ->withConsecutive(...$prepareWith)
            ->will($this->onConsecutiveCalls(...$prepareWill));

        $database->expects($this->exactly(count($getResultsWith)))
            ->method('getResults')
            ->withConsecutive(...$getResultsWith)
            ->will($this->onConsecutiveCalls(...$getResultsWill));

        return $database;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isRoleMember()
     *
     * @return UserGroup
     */
    public function testIsRoleMember()
    {
        $database = $this->getDatabaseMockForMemberTests(['role' => 3]);

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($userGroup, 'id', 123);
        $recursiveMembership = [];

        $return = $userGroup->isRoleMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isRoleMember(4, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        return $userGroup;
    }

    /**
     * Prototype function for the testIsUserMember
     *
     * @param array $types
     * @param array $getResultsWith
     * @param array $getResultsWill
     * @param array $arrayFillWith
     * @param int   $expectGetUsersTable
     * @param int   $expectGetCapabilitiesTable
     * @param int   $expectGetUser
     *
     * @return UserGroup
     */
    private function getTestIsUserMemberPrototype(
        array $types,
        array $getResultsWith,
        array $getResultsWill,
        array $arrayFillWith,
        $expectGetUsersTable,
        $expectGetCapabilitiesTable,
        $expectGetUser
    ) {
        $php = $this->getPhp();

        $php->expects($this->exactly(count($arrayFillWith)))
            ->method('arrayFill')
            ->withConsecutive(...$arrayFillWith)
            ->will($this->returnCallback(function ($startIndex, $numberOfElements, $value) {
                return array_fill($startIndex, $numberOfElements, $value);
            }));

        $database = $this->getDatabaseMockForMemberTests(
            $types,
            $getResultsWith,
            $getResultsWill
        );

        $database->expects($this->exactly($expectGetUsersTable))
            ->method('getUsersTable')
            ->will($this->returnValue('usersTable'));

        $database->expects($this->exactly($expectGetCapabilitiesTable))
            ->method('getCapabilitiesTable')
            ->will($this->returnValue('capabilitiesTable'));

        /**
         * @var \stdClass $firstUser
         */
        $firstUser = $this->getMockBuilder('\WP_User')->getMock();
        $firstUser->capabilitiesTable = [1 => 1, 2 => 2];

        /**
         * @var \stdClass $secondUser
         */
        $secondUser = $this->getMockBuilder('\WP_User')->getMock();
        $secondUser->capabilitiesTable = 'invalid';

        /**
         * @var \stdClass $thirdUser
         */
        $thirdUser = $this->getMockBuilder('\WP_User')->getMock();
        $thirdUser->capabilitiesTable = [1 => 1];

        /**
         * @var \stdClass $fourthUser
         */
        $fourthUser = $this->getMockBuilder('\WP_User')->getMock();
        $fourthUser->capabilitiesTable = [];

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly($expectGetUser))
            ->method('getUser')
            ->will($this->returnCallback(
                function ($userId) use (
                    $firstUser,
                    $secondUser,
                    $thirdUser,
                    $fourthUser
                ) {
                    if ($userId === 1) {
                        return $firstUser;
                    } elseif ($userId === 2) {
                        return $secondUser;
                    } elseif ($userId === 3) {
                        return $thirdUser;
                    } elseif ($userId === 4) {
                        return $fourthUser;
                    }

                    return false;
                }
            ));

        $userGroup = new UserGroup(
            $php,
            $this->getWordpress(),
            $database,
            $this->getConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($userGroup, 'id', 123);

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isUserMember()
     *
     * @return UserGroup
     */
    public function testIsUserMember()
    {
        $userGroup = $this->getTestIsUserMemberPrototype(
            ['role' => 3, 'user' => 2],
            [],
            [],
            [
                [0, 2, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE],
                [0, 1, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE]
            ],
            0,
            5,
            6
        );
        $recursiveMembership = [];

        self::setValue($userGroup, 'assignedObjects', [ObjectHandler::GENERAL_USER_OBJECT_TYPE => []]);
        $return = $userGroup->isUserMember(4, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);
        self::setValue($userGroup, 'assignedObjects', [
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [],
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => []
        ]);
        $return = $userGroup->isUserMember(3, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);
        self::setValue($userGroup, 'userMembership', []);
        self::setValue($userGroup, 'assignedObjects', []);

        $return = $userGroup->isUserMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                    2 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            $recursiveMembership
        );

        $return = $userGroup->isUserMember(2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isUserMember(3, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
            ]
        ], $recursiveMembership);

        $return = $userGroup->isUserMember(5, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        return $userGroup;
    }

    /**
     * Prototype function for the testIsTermMember
     *
     * @return UserGroup
     */
    private function getTestIsTermMemberPrototype()
    {
        $database = $this->getDatabaseMockForMemberTests(['term' => 3]);

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(4))
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 'term'],
                        2 => [3 => 'term'],
                        4 => [1 => 'term']
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        3 => [1 => 'term', 2 => 'term'],
                        1 => [4 => 'term']
                    ]
                ]
            ]));

        $objectHandler->expects($this->any())
            ->method('isTaxonomy')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'termObjectType');
            }));

        $config = $this->getConfig();
        $config->expects($this->exactly(5))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $config,
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($userGroup, 'id', 123);

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isTermMember()
     *
     * @return UserGroup
     */
    public function testIsTermMember()
    {
        $userGroup = $this->getTestIsTermMemberPrototype();
        $recursiveMembership = [];

        // term tests
        $return = $userGroup->isTermMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isTermMember(2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            $recursiveMembership
        );

        $return = $userGroup->isTermMember(3, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isTermMember(4, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 'term']],
            $recursiveMembership
        );

        $return = $userGroup->isTermMember(5, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        return $userGroup;
    }

    /**
     * Prototype function for the testIsPostMember
     *
     * @return UserGroup
     */
    private function getTestIsPostMemberPrototype()
    {
        $database = $this->getDatabaseMockForMemberTests(['post' => 3, 'term' => 3]);
        $config = $this->getConfig();

        $lockRecursiveReturns = [false, true, true, true, true, false];

        $config->expects($this->any())
            ->method('lockRecursive')
            ->will($this->returnCallback(function () use (&$lockRecursiveReturns) {
                if (count($lockRecursiveReturns) > 0) {
                    return array_shift($lockRecursiveReturns);
                }

                return true;
            }));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 'term'],
                        2 => [3 => 'term'],
                        4 => [1 => 'term']
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        3 => [1 => 'term', 2 => 'term'],
                        1 => [4 => 'term']
                    ]
                ]
            ]));

        $objectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'postObjectType');
            }));

        $objectHandler->expects($this->any())
            ->method('getPostTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        1 => [3 => 'post'],
                        2 => [3 => 'post'],
                        4 => [1 => 'post']
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        3 => [1 => 'post', 2 => 'post'],
                        1 => [4 => 'post']
                    ]
                ]
            ]));

        $objectHandler->expects($this->any())
            ->method('getPostTermMap')
            ->will($this->returnValue([
                2 => [3 => 'term', 9 => 'term'],
                10 => [3 => 'term']
            ]));

        $objectHandler->expects($this->any())
            ->method('getTermPostMap')
            ->will($this->returnValue([
                2 => [9 => 'post']
            ]));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $config,
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($userGroup, 'id', 123);

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isPostMember()
     *
     * @return UserGroup
     */
    public function testIsPostMember()
    {
        $userGroup = $this->getTestIsPostMemberPrototype();
        $recursiveMembership = [];

        // post tests
        $return = $userGroup->isPostMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isPostMember(2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            $recursiveMembership
        );

        $return = $userGroup->isPostMember(3, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isPostMember(4, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 'post']], $recursiveMembership);

        $return = $userGroup->isPostMember(5, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isPostMember(10, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            $recursiveMembership
        );

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isPluggableObjectMember()
     *
     * @return UserGroup
     */
    public function testIsPluggableObjectMember()
    {
        $database = $this->getDatabaseMockForMemberTests(['pluggableObject' => 2]);

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getPluggableObject')
            ->will($this->returnCallback(
                function ($objectType) {
                    if ($objectType === '_pluggableObject_') {
                        $pluggableObject = $this->getMockForAbstractClass(
                            '\UserAccessManager\ObjectHandler\PluggableObject',
                            [],
                            '',
                            false
                        );

                        $pluggableObject->expects($this->any())
                            ->method('getRecursiveMembership')
                            ->will($this->returnCallback(
                                function ($userGroup, $objectId) {
                                    return ($objectId === 1 || $objectId === 4) ?
                                        ['pluggableObject' => [1 => 'pluggableObject']] : [];
                                }
                            ));

                        $pluggableObject->expects($this->any())
                            ->method('getFullObjects')
                            ->will($this->returnValue([1 => 'pluggableObject', 6 => 'pluggableObject']));

                        return $pluggableObject;
                    }

                    return null;
                }
            ));

        $objectHandler->expects($this->any())
            ->method('isPluggableObject')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === '_pluggableObject_');
            }));

        $userGroup = new UserGroup(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $config = $this->getConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($userGroup, 'id', 123);
        $recursiveMembership = [];

        // pluggable object tests
        $return = $userGroup->isPluggableObjectMember('noPluggableObject', 1, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isPluggableObjectMember('_pluggableObject_', 1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(['pluggableObject' => [1 => 'pluggableObject']], $recursiveMembership);

        $return = $userGroup->isPluggableObjectMember('_pluggableObject_', 2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        self::assertAttributeEquals(
            [
                'noPluggableObject' => [1 => false],
                '_pluggableObject_' => [
                    1 => ['pluggableObject' => [1 => 'pluggableObject']],
                    2 => []
                ]
            ],
            'pluggableObjectMembership',
            $userGroup
        );

        $return = $userGroup->isPluggableObjectMember('_pluggableObject_', 3, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        $return = $userGroup->isPluggableObjectMember('_pluggableObject_', 4, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(['pluggableObject' => [1 => 'pluggableObject']], $recursiveMembership);

        return $userGroup;
    }

    /**
     * Assertion helper for testIsMemberFunctions
     *
     * @param UserGroup $userGroup
     * @param bool      $expectedReturn
     * @param array     $expectedRecursiveMembership
     * @param string    $objectType
     * @param string    $objectId
     */
    private function memberFunctionAssertions(
        UserGroup $userGroup,
        $expectedReturn,
        array $expectedRecursiveMembership,
        $objectType,
        $objectId
    ) {
        $recursiveMembership = [];
        $return = $userGroup->isObjectMember($objectType, $objectId, $recursiveMembership);

        self::assertEquals($expectedReturn, $return);
        self::assertEquals($expectedRecursiveMembership, $recursiveMembership);

        self::assertEquals(
            $expectedRecursiveMembership,
            $userGroup->getRecursiveMembershipForObject(
                $objectType,
                $objectId
            )
        );

        self::assertEquals(
            count($expectedRecursiveMembership) > 0,
            $userGroup->isLockedRecursive($objectType, $objectId)
        );
    }

    /**
     * @group   unit
     * @depends testIsRoleMember
     * @depends testIsUserMember
     * @depends testIsTermMember
     * @depends testIsPostMember
     * @depends testIsPluggableObjectMember
     * @covers  \UserAccessManager\UserGroup\UserGroup::isObjectMember()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getRecursiveMembershipForObject()
     * @covers  \UserAccessManager\UserGroup\UserGroup::isLockedRecursive()
     *
     * @param UserGroup $roleUserGroup
     * @param UserGroup $userUserGroup
     * @param UserGroup $termUserGroup
     * @param UserGroup $postUserGroup
     * @param UserGroup $pluggableObjectUserGroup
     */
    public function testIsMemberFunctions(
        UserGroup $roleUserGroup,
        UserGroup $userUserGroup,
        UserGroup $termUserGroup,
        UserGroup $postUserGroup,
        UserGroup $pluggableObjectUserGroup
    ) {
        // role tests
        $this->memberFunctionAssertions($roleUserGroup, true, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions($roleUserGroup, false, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 4);

        // user tests
        $this->memberFunctionAssertions(
            $userUserGroup,
            true,
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                    2 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            1
        );
        $this->memberFunctionAssertions($userUserGroup, true, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 2);
        $this->memberFunctionAssertions(
            $userUserGroup,
            true,
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            3
        );
        $this->memberFunctionAssertions($userUserGroup, false, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 5);

        // term tests
        $this->memberFunctionAssertions($termUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $termUserGroup,
            true,
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            'termObjectType',
            2
        );
        $this->memberFunctionAssertions($termUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $termUserGroup,
            true,
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 'term']],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            4
        );
        $this->memberFunctionAssertions($termUserGroup, false, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5);

        // post tests
        $this->memberFunctionAssertions($postUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $postUserGroup,
            true,
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            'postObjectType',
            2
        );
        $this->memberFunctionAssertions(
            $postUserGroup,
            true,
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            2
        );
        $this->memberFunctionAssertions($postUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $postUserGroup,
            true,
            [ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 'post']],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            4
        );
        $this->memberFunctionAssertions($postUserGroup, false, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 5);

        // pluggable object tests
        $this->memberFunctionAssertions($pluggableObjectUserGroup, false, [], 'noPluggableObject', 1);
        $this->memberFunctionAssertions(
            $pluggableObjectUserGroup,
            true,
            ['pluggableObject' => [1 => 'pluggableObject']],
            '_pluggableObject_',
            1
        );
        $this->memberFunctionAssertions($pluggableObjectUserGroup, false, [], '_pluggableObject_', 3);
    }

    /**
     * Generates return values.
     *
     * @param array $numbers
     *
     * @return array
     */
    private function generateUserReturn(array $numbers)
    {
        $returns = [];

        foreach ($numbers as $number) {
            $return = new \stdClass();
            $return->ID = $number;
            $returns[] = $return;
        }

        return $returns;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullUsers()
     *
     * @return UserGroup
     */
    public function testGetFullUser()
    {
        $query = "SELECT ID, user_nicename FROM usersTable";

        $userGroup = $this->getTestIsUserMemberPrototype(
            ['user' => 2, 'role' => 3],
            [[new MatchIgnoreWhitespace($query)]],
            [$this->generateUserReturn([10 => 10, 1, 2, 3])],
            [
                [0, 2, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE],
                [0, 1, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE]
            ],
            1,
            3,
            4
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                2 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                3 => ObjectHandler::GENERAL_USER_OBJECT_TYPE
            ],
            $userGroup->getFullUsers()
        );

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullTerms()
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullObjects()
     *
     * @return UserGroup
     */
    public function testGetFullTerms()
    {
        $userGroup = $this->getTestIsTermMemberPrototype();
        self::assertEquals([1 => 'term', 2 => 'term', 3 => 'term'], $userGroup->getFullTerms());

        self::setValue($userGroup, 'fullObjectMembership', []);
        self::assertEquals([1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term'], $userGroup->getFullTerms());

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullPosts()
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullObjects()
     *
     * @return UserGroup
     */
    public function testGetFullPosts()
    {
        $userGroup = $this->getTestIsPostMemberPrototype();
        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 9 => 'post'],
            $userGroup->getFullPosts()
        );

        self::setValue($userGroup, 'fullObjectMembership', []);
        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 4 => 'post', 9 => 'post'],
            $userGroup->getFullPosts()
        );

        return $userGroup;
    }

    /**
     * @group  unit
     * @depends testIsRoleMember
     * @depends testGetFullUser
     * @depends testGetFullTerms
     * @depends testGetFullPosts
     * @depends testIsPluggableObjectMember
     * @covers \UserAccessManager\UserGroup\UserGroup::getAssignedObjectsByType()
     *
     * @param UserGroup $roleUserGroup
     * @param UserGroup $userUserGroup
     * @param UserGroup $termUserGroup
     * @param UserGroup $postUserGroup
     * @param UserGroup $pluggableObjectUserGroup
     */
    public function testGetAssignedObjectsByType(
        UserGroup $roleUserGroup,
        UserGroup $userUserGroup,
        UserGroup $termUserGroup,
        UserGroup $postUserGroup,
        UserGroup $pluggableObjectUserGroup
    ) {
        self::assertEquals(
            [1 => 'role', 2 => 'role', 3 => 'role'],
            $roleUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                2 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                3 => ObjectHandler::GENERAL_USER_OBJECT_TYPE
            ],
            $userUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );

        self::assertEquals(
            [1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term'],
            $termUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
        );
        self::setValue($termUserGroup, 'fullObjectMembership', ['termObjectType' => [1 => 'term', 2 => 'term']]);
        self::assertEquals(
            [1 => 'term', 2 => 'term'],
            $termUserGroup->getAssignedObjectsByType('termObjectType')
        );

        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 4 => 'post', 9 => 'post'],
            $postUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_POST_OBJECT_TYPE)
        );
        self::setValue($postUserGroup, 'fullObjectMembership', ['postObjectType' => [3 => 'post', 4 => 'post']]);
        self::assertEquals(
            [3 => 'post', 4 => 'post'],
            $postUserGroup->getAssignedObjectsByType('postObjectType')
        );

        self::assertEquals(
            [1 => 'pluggableObject', 6 => 'pluggableObject'],
            $pluggableObjectUserGroup->getAssignedObjectsByType('_pluggableObject_')
        );

        self::assertEquals(
            [],
            $pluggableObjectUserGroup->getAssignedObjectsByType('nothing')
        );
    }
}
