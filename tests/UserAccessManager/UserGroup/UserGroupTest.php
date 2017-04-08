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
 * @version   SVN: $Id$
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
        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $UserGroup);

        $Database = $this->getDatabase();
        $Database->expects($this->once())
            ->method('prepare');

        $Database->expects($this->once())
            ->method('getUserGroupTable');

        $Database->expects($this->once())
            ->method('getRow');

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            1
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $UserGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::load()
     *
     * @return UserGroup
     */
    public function testLoad()
    {
        $Database = $this->getDatabase();

        $Database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $Database->expects($this->exactly(2))
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

        $DbUserGroup = new \stdClass();
        $DbUserGroup->groupname = 'groupName';
        $DbUserGroup->groupdesc = 'groupDesc';
        $DbUserGroup->read_access = 'readAccess';
        $DbUserGroup->write_access = 'writeAccess';
        $DbUserGroup->ip_range = 'ipRange;ipRange2';

        $Database->expects($this->exactly(2))
            ->method('getRow')
            ->with('queryString')
            ->will($this->onConsecutiveCalls(null, $DbUserGroup));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertFalse($UserGroup->load(1));
        self::assertAttributeEquals(null, 'iId', $UserGroup);
        self::assertAttributeEquals(null, 'sName', $UserGroup);
        self::assertAttributeEquals(null, 'sDescription', $UserGroup);
        self::assertAttributeEquals(null, 'sReadAccess', $UserGroup);
        self::assertAttributeEquals(null, 'sWriteAccess', $UserGroup);
        self::assertAttributeEquals(null, 'sIpRange', $UserGroup);

        self::assertTrue($UserGroup->load(2));
        self::assertAttributeEquals(2, 'iId', $UserGroup);
        self::assertAttributeEquals('groupName', 'sName', $UserGroup);
        self::assertAttributeEquals('groupDesc', 'sDescription', $UserGroup);
        self::assertAttributeEquals('readAccess', 'sReadAccess', $UserGroup);
        self::assertAttributeEquals('writeAccess', 'sWriteAccess', $UserGroup);
        self::assertAttributeEquals('ipRange;ipRange2', 'sIpRange', $UserGroup);

        return $UserGroup;
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
     * @covers  \UserAccessManager\UserGroup\UserGroup::setName()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setDescription()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setReadAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setWriteAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setIpRange()
     *
     * @param UserGroup $UserGroup
     */
    public function testSimpleGetterSetter(UserGroup $UserGroup)
    {
        self::assertEquals(2, $UserGroup->getId());
        self::assertEquals('groupName', $UserGroup->getName());
        self::assertEquals('groupDesc', $UserGroup->getDescription());
        self::assertEquals('readAccess', $UserGroup->getReadAccess());
        self::assertEquals('writeAccess', $UserGroup->getWriteAccess());
        self::assertEquals(['ipRange', 'ipRange2'], $UserGroup->getIpRange());
        self::assertEquals('ipRange;ipRange2', $UserGroup->getIpRange(true));

        $UserGroup->setName('groupNameNew');
        self::assertAttributeEquals('groupNameNew', 'sName', $UserGroup);

        $UserGroup->setDescription('groupDescNew');
        self::assertAttributeEquals('groupDescNew', 'sDescription', $UserGroup);

        $UserGroup->setReadAccess('readAccessNew');
        self::assertAttributeEquals('readAccessNew', 'sReadAccess', $UserGroup);

        $UserGroup->setWriteAccess('writeAccessNew');
        self::assertAttributeEquals('writeAccessNew', 'sWriteAccess', $UserGroup);

        $UserGroup->setIpRange(['ipRangeNew', 'ipRangeNew2']);
        self::assertAttributeEquals('ipRangeNew;ipRangeNew2', 'sIpRange', $UserGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::save()
     */
    public function testSave()
    {
        $Database = $this->getDatabase();

        $Database->expects($this->exactly(4))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $Database->expects($this->exactly(2))
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

        $Database->expects($this->once())
            ->method('getLastInsertId')
            ->will($this->returnValue(123));

        $Database->expects($this->exactly(2))
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

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        $UserGroup->setName('groupName');
        $UserGroup->setDescription('groupDesc');
        $UserGroup->setReadAccess('readAccess');
        $UserGroup->setWriteAccess('writeAccess');
        $UserGroup->setIpRange(['ipRange', 'ipRange2']);

        self::assertFalse($UserGroup->save());
        self::assertNull($UserGroup->getId());
        self::assertTrue($UserGroup->save());
        self::assertEquals(123, $UserGroup->getId());

        self::setValue($UserGroup, 'iId', 2);
        self::assertFalse($UserGroup->save());
        self::assertTrue($UserGroup->save());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::delete()
     * @covers \UserAccessManager\UserGroup\UserGroup::removeObject()
     */
    public function testDelete()
    {
        $Database = $this->getDatabase();

        $Database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $Database->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $Database->expects($this->exactly(2))
            ->method('delete')
            ->with(
                'userGroupTable',
                ['ID' => 123]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $Database->expects($this->exactly(4))
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

        $Database->expects($this->exactly(4))
            ->method('query')
            ->with('preparedQuery')
            ->will($this->onConsecutiveCalls(true, false, true, true));

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue(['objectType']));

        $ObjectHandler->expects($this->exactly(5))
            ->method('isValidObjectType')
            ->withConsecutive(['objectType'], ['invalid'], ['objectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(true, false, true));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $ObjectHandler
        );

        self::assertFalse($UserGroup->delete());
        self::setValue($UserGroup, 'iId', 123);

        self::setValue($UserGroup, 'aAssignedObjects', [1 => 1]);
        self::setValue($UserGroup, 'aRoleMembership', [2 => 2]);
        self::setValue($UserGroup, 'aUserMembership', [3 => 3]);
        self::setValue($UserGroup, 'aTermMembership', [4 => 4]);
        self::setValue($UserGroup, 'aPostMembership', [5 => 5]);
        self::setValue($UserGroup, 'aFullObjectMembership', [6 => 6]);

        self::assertFalse($UserGroup->delete());

        self::assertAttributeEquals([1 => 1], 'aAssignedObjects', $UserGroup);
        self::assertAttributeEquals([2 => 2], 'aRoleMembership', $UserGroup);
        self::assertAttributeEquals([3 => 3], 'aUserMembership', $UserGroup);
        self::assertAttributeEquals([4 => 4], 'aTermMembership', $UserGroup);
        self::assertAttributeEquals([5 => 5], 'aPostMembership', $UserGroup);
        self::assertAttributeEquals([6 => 6], 'aFullObjectMembership', $UserGroup);

        self::assertTrue($UserGroup->delete());


        self::setValue($UserGroup, 'aAssignedObjects', [1 => 1]);
        self::setValue($UserGroup, 'aRoleMembership', [2 => 2]);
        self::setValue($UserGroup, 'aUserMembership', [3 => 3]);
        self::setValue($UserGroup, 'aTermMembership', [4 => 4]);
        self::setValue($UserGroup, 'aPostMembership', [5 => 5]);
        self::setValue($UserGroup, 'aFullObjectMembership', [6 => 6]);

        self::assertFalse($UserGroup->removeObject('invalid'));
        self::assertFalse($UserGroup->removeObject('objectType'));

        self::assertAttributeEquals([1 => 1], 'aAssignedObjects', $UserGroup);
        self::assertAttributeEquals([2 => 2], 'aRoleMembership', $UserGroup);
        self::assertAttributeEquals([3 => 3], 'aUserMembership', $UserGroup);
        self::assertAttributeEquals([4 => 4], 'aTermMembership', $UserGroup);
        self::assertAttributeEquals([5 => 5], 'aPostMembership', $UserGroup);
        self::assertAttributeEquals([6 => 6], 'aFullObjectMembership', $UserGroup);

        self::assertTrue($UserGroup->removeObject('objectType'));
        self::assertTrue($UserGroup->removeObject('objectType', 1));

        self::assertAttributeEquals([], 'aAssignedObjects', $UserGroup);
        self::assertAttributeEquals([], 'aRoleMembership', $UserGroup);
        self::assertAttributeEquals([], 'aUserMembership', $UserGroup);
        self::assertAttributeEquals([], 'aTermMembership', $UserGroup);
        self::assertAttributeEquals([], 'aPostMembership', $UserGroup);
        self::assertAttributeEquals([], 'aFullObjectMembership', $UserGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::addObject()
     */
    public function testAddObject()
    {
        $Database = $this->getDatabase();

        $Database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $Database->expects($this->exactly(2))
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

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->exactly(5))
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

        $ObjectHandler->expects($this->exactly(3))
            ->method('isValidObjectType')
            ->withConsecutive(['notValidObjectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $ObjectHandler
        );

        self::setValue($UserGroup, 'iId', 123);
        self::setValue($UserGroup, 'aAssignedObjects', [1 => 'post', 2 => 'post']);
        self::setValue($UserGroup, 'aRoleMembership', [1 => 'role', 2 => 'role']);
        self::setValue($UserGroup, 'aUserMembership', [1 => 'user', 2 => 'user']);
        self::setValue($UserGroup, 'aTermMembership', [1 => 'term', 2 => 'term']);
        self::setValue($UserGroup, 'aPostMembership', [1 => 'post', 2 => 'post']);
        self::setValue($UserGroup, 'aFullObjectMembership', [1 => 'post', 2 => 'post']);

        self::assertFalse($UserGroup->addObject('invalid', 321));
        self::assertFalse($UserGroup->addObject('generalObjectType', 321));
        self::assertFalse($UserGroup->addObject('notValidObjectType', 321));
        self::assertFalse($UserGroup->addObject('objectType', 321));
        self::assertTrue($UserGroup->addObject('objectType', 321));

        self::assertAttributeEquals([], 'aAssignedObjects', $UserGroup);
        self::assertAttributeEquals([], 'aRoleMembership', $UserGroup);
        self::assertAttributeEquals([], 'aUserMembership', $UserGroup);
        self::assertAttributeEquals([], 'aTermMembership', $UserGroup);
        self::assertAttributeEquals([], 'aPostMembership', $UserGroup);
        self::assertAttributeEquals([], 'aFullObjectMembership', $UserGroup);
    }

    /**
     * Generates return values.
     *
     * @param int    $iNumber
     * @param string $sType
     *
     * @return array
     */
    private function generateReturn($iNumber, $sType)
    {
        $aReturn = [];

        for ($iCounter = 1; $iCounter <= $iNumber; $iCounter++) {
            $Return = new \stdClass();
            $Return->id = $iCounter;
            $Return->objectType = $sType;
            $aReturn[] = $Return;
        }

        return $aReturn;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getAssignedObjects()
     * @covers  \UserAccessManager\UserGroup\UserGroup::isObjectAssignedToGroup()
     */
    public function testAssignedObject()
    {
        $Database = $this->getDatabase();

        $Database->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $sQuery = 'SELECT object_id AS id, object_type AS objectType
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $Database->expects($this->exactly(3))
            ->method('prepare')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($sQuery), [123, 'noResultObjectType', 'noResultObjectType']],
                [new MatchIgnoreWhitespace($sQuery), [123, 'objectType', 'objectType']],
                [new MatchIgnoreWhitespace($sQuery), [123, 'something', 'something']]
            )
            ->will($this->onConsecutiveCalls(
                'nonResultPreparedQuery',
                'preparedQuery',
                'nonResultSomethingPreparedQuery'
            ));

        $Database->expects($this->exactly(3))
            ->method('getResults')
            ->withConsecutive(
                ['nonResultPreparedQuery'],
                ['preparedQuery'],
                ['nonResultSomethingPreparedQuery']
            )
            ->will($this->onConsecutiveCalls(null, $this->generateReturn(3, 'objectType'), null));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($UserGroup, 'iId', 123);

        $aResult = self::callMethod($UserGroup, 'getAssignedObjects', ['noResultObjectType']);
        self::assertEquals([], $aResult);
        self::assertAttributeEquals(['noResultObjectType' => []], 'aAssignedObjects', $UserGroup);

        $aResult = self::callMethod($UserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 'objectType', 2 => 'objectType', 3 => 'objectType'], $aResult);
        self::assertAttributeEquals(
            ['noResultObjectType' => [], 'objectType' => [1 => 'objectType', 2 => 'objectType', 3 => 'objectType']],
            'aAssignedObjects',
            $UserGroup
        );

        $aResult = self::callMethod($UserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 'objectType', 2 => 'objectType', 3 => 'objectType'], $aResult);

        $blResult = self::callMethod($UserGroup, 'isObjectAssignedToGroup', ['objectType', 1]);
        self::assertTrue($blResult);
        $blResult = self::callMethod($UserGroup, 'isObjectAssignedToGroup', ['objectType', 2]);
        self::assertTrue($blResult);
        $blResult = self::callMethod($UserGroup, 'isObjectAssignedToGroup', ['objectType', 3]);
        self::assertTrue($blResult);

        $blResult = self::callMethod($UserGroup, 'isObjectAssignedToGroup', ['objectType', 4]);
        self::assertFalse($blResult);
        $blResult = self::callMethod($UserGroup, 'isObjectAssignedToGroup', ['noResultObjectType', 1]);
        self::assertFalse($blResult);
        $blResult = self::callMethod($UserGroup, 'isObjectAssignedToGroup', ['something', 1]);
        self::assertFalse($blResult);
    }

    /**
     * Returns the database mock for the member tests
     *
     * @param array $aTypes
     * @param array $aGetResultsWith
     * @param array $aGetResultsWill
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    private function getDatabaseMockForMemberTests(
        array $aTypes,
        array $aGetResultsWith = [],
        array $aGetResultsWill = []
    ) {
        $sQuery = 'SELECT object_id AS id, object_type AS objectType
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $aPrepareWith = [];
        $aPrepareWill = [];

        foreach ($aTypes as $sType => $iNumberOfReturn) {
            $aPrepareWith[] = [new MatchIgnoreWhitespace($sQuery), [123, "_{$sType}_", "_{$sType}_"]];
            $aPrepareWill[] = "{$sType}PreparedQuery";
            $aGetResultsWith[] = ["{$sType}PreparedQuery"];
            $aGetResultsWill[] = $this->generateReturn($iNumberOfReturn, $sType);
        }

        $Database = $this->getDatabase();

        $Database->expects($this->any())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $Database->expects($this->exactly(count($aPrepareWith)))
            ->method('prepare')
            ->withConsecutive(...$aPrepareWith)
            ->will($this->onConsecutiveCalls(...$aPrepareWill));

        $Database->expects($this->exactly(count($aGetResultsWith)))
            ->method('getResults')
            ->withConsecutive(...$aGetResultsWith)
            ->will($this->onConsecutiveCalls(...$aGetResultsWill));

        return $Database;
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
        $Database = $this->getDatabaseMockForMemberTests(['role' => 3]);

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($UserGroup, 'iId', 123);
        $aRecursiveMembership = [];

        $blReturn = $UserGroup->isRoleMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isRoleMember(4, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $UserGroup;
    }

    /**
     * Prototype function for the testIsUserMember
     *
     * @param array $aTypes
     * @param array $aGetResultsWith
     * @param array $aGetResultsWill
     * @param int   $iExpectGetUsersTable
     * @param int   $iExpectGetCapabilitiesTable
     * @param int   $iExpectGetUser
     *
     * @return UserGroup
     */
    private function getTestIsUserMemberPrototype(
        array $aTypes,
        array $aGetResultsWith,
        array $aGetResultsWill,
        $iExpectGetUsersTable,
        $iExpectGetCapabilitiesTable,
        $iExpectGetUser
    ) {
        $Database = $this->getDatabaseMockForMemberTests(
            $aTypes,
            $aGetResultsWith,
            $aGetResultsWill
        );

        $Database->expects($this->exactly($iExpectGetUsersTable))
            ->method('getUsersTable')
            ->will($this->returnValue('usersTable'));

        $Database->expects($this->exactly($iExpectGetCapabilitiesTable))
            ->method('getCapabilitiesTable')
            ->will($this->returnValue('capabilitiesTable'));

        /**
         * @var \stdClass $FirstUser
         */
        $FirstUser = $this->getMockBuilder('\WP_User')->getMock();
        $FirstUser->capabilitiesTable = [1 => 1, 2 => 2];

        /**
         * @var \stdClass $SecondUser
         */
        $SecondUser = $this->getMockBuilder('\WP_User')->getMock();
        $SecondUser->capabilitiesTable = 'invalid';

        /**
         * @var \stdClass $ThirdUser
         */
        $ThirdUser = $this->getMockBuilder('\WP_User')->getMock();
        $ThirdUser->capabilitiesTable = [1 => 1];

        /**
         * @var \stdClass $FourthUser
         */
        $FourthUser = $this->getMockBuilder('\WP_User')->getMock();
        $FourthUser->capabilitiesTable = [];

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->exactly($iExpectGetUser))
            ->method('getUser')
            ->will($this->returnCallback(
                function ($sUserId) use (
                    $FirstUser,
                    $SecondUser,
                    $ThirdUser,
                    $FourthUser
                ) {
                    if ($sUserId === 1) {
                        return $FirstUser;
                    } elseif ($sUserId === 2) {
                        return $SecondUser;
                    } elseif ($sUserId === 3) {
                        return $ThirdUser;
                    } elseif ($sUserId === 4) {
                        return $FourthUser;
                    }

                    return false;
                }
            ));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $this->getConfig(),
            $this->getUtil(),
            $ObjectHandler
        );

        self::setValue($UserGroup, 'iId', 123);

        return $UserGroup;
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
        $UserGroup = $this->getTestIsUserMemberPrototype(['role' => 3, 'user' => 2], [], [], 0, 5, 6);
        $aRecursiveMembership = [];

        self::setValue($UserGroup, 'aAssignedObjects', [ObjectHandler::GENERAL_USER_OBJECT_TYPE => []]);
        $blReturn = $UserGroup->isUserMember(4, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);
        self::setValue($UserGroup, 'aAssignedObjects', [
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [],
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => []
        ]);
        $blReturn = $UserGroup->isUserMember(3, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);
        self::setValue($UserGroup, 'aUserMembership', []);
        self::setValue($UserGroup, 'aAssignedObjects', []);

        $blReturn = $UserGroup->isUserMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                    2 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            $aRecursiveMembership
        );

        $blReturn = $UserGroup->isUserMember(2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isUserMember(3, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
            ]
        ], $aRecursiveMembership);

        $blReturn = $UserGroup->isUserMember(5, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $UserGroup;
    }

    /**
     * Prototype function for the testIsTermMember
     *
     * @return UserGroup
     */
    private function getTestIsTermMemberPrototype()
    {
        $Database = $this->getDatabaseMockForMemberTests(['term' => 3]);

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->exactly(4))
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

        $ObjectHandler->expects($this->any())
            ->method('isTaxonomy')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'termObjectType');
            }));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(5))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $Config,
            $this->getUtil(),
            $ObjectHandler
        );

        self::setValue($UserGroup, 'iId', 123);

        return $UserGroup;
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
        $UserGroup = $this->getTestIsTermMemberPrototype();
        $aRecursiveMembership = [];

        // term tests
        $blReturn = $UserGroup->isTermMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isTermMember(2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            $aRecursiveMembership
        );

        $blReturn = $UserGroup->isTermMember(3, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isTermMember(4, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 'term']],
            $aRecursiveMembership
        );

        $blReturn = $UserGroup->isTermMember(5, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $UserGroup;
    }

    /**
     * Prototype function for the testIsPostMember
     *
     * @return UserGroup
     */
    private function getTestIsPostMemberPrototype()
    {
        $Database = $this->getDatabaseMockForMemberTests(['post' => 3, 'term' => 3]);
        $Config = $this->getConfig();

        $aLockRecursiveReturns = [false, true, true, true, true, false];

        $Config->expects($this->any())
            ->method('lockRecursive')
            ->will($this->returnCallback(function () use (&$aLockRecursiveReturns) {
                if (count($aLockRecursiveReturns) > 0) {
                    return array_shift($aLockRecursiveReturns);
                }

                return true;
            }));

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->any())
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

        $ObjectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'postObjectType');
            }));

        $ObjectHandler->expects($this->any())
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

        $ObjectHandler->expects($this->any())
            ->method('getPostTermMap')
            ->will($this->returnValue([
                2 => [3 => 'term', 9 => 'term'],
                10 => [3 => 'term']
            ]));

        $ObjectHandler->expects($this->any())
            ->method('getTermPostMap')
            ->will($this->returnValue([
                2 => [9 => 'post']
            ]));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $Config,
            $this->getUtil(),
            $ObjectHandler
        );

        self::setValue($UserGroup, 'iId', 123);

        return $UserGroup;
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
        $UserGroup = $this->getTestIsPostMemberPrototype();
        $aRecursiveMembership = [];

        // post tests
        $blReturn = $UserGroup->isPostMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isPostMember(2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            $aRecursiveMembership
        );

        $blReturn = $UserGroup->isPostMember(3, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isPostMember(4, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 'post']], $aRecursiveMembership);

        $blReturn = $UserGroup->isPostMember(5, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isPostMember(10, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            $aRecursiveMembership
        );

        return $UserGroup;
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
        $Database = $this->getDatabaseMockForMemberTests(['pluggableObject' => 2]);

        $ObjectHandler = $this->getObjectHandler();
        $ObjectHandler->expects($this->any())
            ->method('getPluggableObject')
            ->will($this->returnCallback(
                function ($sObjectType) {
                    if ($sObjectType === '_pluggableObject_') {
                        $PluggableObject = $this->getMockForAbstractClass(
                            '\UserAccessManager\ObjectHandler\PluggableObject',
                            [],
                            '',
                            false
                        );

                        $PluggableObject->expects($this->any())
                            ->method('getRecursiveMembership')
                            ->will($this->returnCallback(
                                function ($UserGroup, $iObjectId) {
                                    return ($iObjectId === 1 || $iObjectId === 4) ?
                                        ['pluggableObject' => [1 => 'pluggableObject']] : [];
                                }
                            ));

                        $PluggableObject->expects($this->any())
                            ->method('getFullObjects')
                            ->will($this->returnValue([1 => 'pluggableObject', 6 => 'pluggableObject']));

                        return $PluggableObject;
                    }

                    return null;
                }
            ));

        $ObjectHandler->expects($this->any())
            ->method('isPluggableObject')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === '_pluggableObject_');
            }));

        $UserGroup = new UserGroup(
            $this->getWordpress(),
            $Database,
            $Config = $this->getConfig(),
            $this->getUtil(),
            $ObjectHandler
        );

        self::setValue($UserGroup, 'iId', 123);
        $aRecursiveMembership = [];

        // pluggable object tests
        $blReturn = $UserGroup->isPluggableObjectMember('noPluggableObject', 1, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isPluggableObjectMember('_pluggableObject_', 1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(['pluggableObject' => [1 => 'pluggableObject']], $aRecursiveMembership);

        $blReturn = $UserGroup->isPluggableObjectMember('_pluggableObject_', 2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        self::assertAttributeEquals(
            [
                'noPluggableObject' => [1 => false],
                '_pluggableObject_' => [
                    1 => ['pluggableObject' => [1 => 'pluggableObject']],
                    2 => []
                ]
            ],
            'aPluggableObjectMembership',
            $UserGroup
        );

        $blReturn = $UserGroup->isPluggableObjectMember('_pluggableObject_', 3, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $UserGroup->isPluggableObjectMember('_pluggableObject_', 4, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(['pluggableObject' => [1 => 'pluggableObject']], $aRecursiveMembership);

        return $UserGroup;
    }

    /**
     * Assertion helper for testIsMemberFunctions
     *
     * @param UserGroup $UserGroup
     * @param bool      $blExpectedReturn
     * @param array     $aExpectedRecursiveMembership
     * @param string    $sObjectType
     * @param string    $sObjectId
     */
    private function memberFunctionAssertions(
        UserGroup $UserGroup,
        $blExpectedReturn,
        array $aExpectedRecursiveMembership,
        $sObjectType,
        $sObjectId
    ) {
        $aRecursiveMembership = [];
        $blReturn = $UserGroup->isObjectMember($sObjectType, $sObjectId, $aRecursiveMembership);

        self::assertEquals($blExpectedReturn, $blReturn);
        self::assertEquals($aExpectedRecursiveMembership, $aRecursiveMembership);

        self::assertEquals(
            $aExpectedRecursiveMembership,
            $UserGroup->getRecursiveMembershipForObject(
                $sObjectType,
                $sObjectId
            )
        );

        self::assertEquals(
            count($aExpectedRecursiveMembership) > 0,
            $UserGroup->isLockedRecursive($sObjectType, $sObjectId)
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
     * @param UserGroup $RoleUserGroup
     * @param UserGroup $UserUserGroup
     * @param UserGroup $TermUserGroup
     * @param UserGroup $PostUserGroup
     * @param UserGroup $PluggableObjectUserGroup
     */
    public function testIsMemberFunctions(
        UserGroup $RoleUserGroup,
        UserGroup $UserUserGroup,
        UserGroup $TermUserGroup,
        UserGroup $PostUserGroup,
        UserGroup $PluggableObjectUserGroup
    ) {
        // role tests
        $this->memberFunctionAssertions($RoleUserGroup, true, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions($RoleUserGroup, false, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 4);

        // user tests
        $this->memberFunctionAssertions(
            $UserUserGroup,
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
        $this->memberFunctionAssertions($UserUserGroup, true, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 2);
        $this->memberFunctionAssertions(
            $UserUserGroup,
            true,
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            3
        );
        $this->memberFunctionAssertions($UserUserGroup, false, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 5);

        // term tests
        $this->memberFunctionAssertions($TermUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $TermUserGroup,
            true,
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            'termObjectType',
            2
        );
        $this->memberFunctionAssertions($TermUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $TermUserGroup,
            true,
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 'term']],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            4
        );
        $this->memberFunctionAssertions($TermUserGroup, false, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5);

        // post tests
        $this->memberFunctionAssertions($PostUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $PostUserGroup,
            true,
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            'postObjectType',
            2
        );
        $this->memberFunctionAssertions(
            $PostUserGroup,
            true,
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            2
        );
        $this->memberFunctionAssertions($PostUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $PostUserGroup,
            true,
            [ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 'post']],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            4
        );
        $this->memberFunctionAssertions($PostUserGroup, false, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 5);

        // pluggable object tests
        $this->memberFunctionAssertions($PluggableObjectUserGroup, false, [], 'noPluggableObject', 1);
        $this->memberFunctionAssertions(
            $PluggableObjectUserGroup,
            true,
            ['pluggableObject' => [1 => 'pluggableObject']],
            '_pluggableObject_',
            1
        );
        $this->memberFunctionAssertions($PluggableObjectUserGroup, false, [], '_pluggableObject_', 3);
    }

    /**
     * Generates return values.
     *
     * @param array $aNumbers
     *
     * @return array
     */
    private function generateUserReturn(array $aNumbers)
    {
        $aReturn = [];

        foreach ($aNumbers as $iNumber) {
            $Return = new \stdClass();
            $Return->ID = $iNumber;
            $aReturn[] = $Return;
        }

        return $aReturn;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullUsers()
     *
     * @return UserGroup
     */
    public function testGetFullUser()
    {
        $sQuery = "SELECT ID, user_nicename FROM usersTable";

        $UserGroup = $this->getTestIsUserMemberPrototype(
            ['user' => 2, 'role' => 3],
            [[new MatchIgnoreWhitespace($sQuery)]],
            [$this->generateUserReturn([10 => 10, 1, 2, 3])],
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
            $UserGroup->getFullUsers()
        );

        return $UserGroup;
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
        $UserGroup = $this->getTestIsTermMemberPrototype();
        self::assertEquals([1 => 'term', 2 => 'term', 3 => 'term'], $UserGroup->getFullTerms());

        self::setValue($UserGroup, 'aFullObjectMembership', []);
        self::assertEquals([1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term'], $UserGroup->getFullTerms());

        return $UserGroup;
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
        $UserGroup = $this->getTestIsPostMemberPrototype();
        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 9 => 'post'],
            $UserGroup->getFullPosts()
        );

        self::setValue($UserGroup, 'aFullObjectMembership', []);
        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 4 => 'post', 9 => 'post'],
            $UserGroup->getFullPosts()
        );

        return $UserGroup;
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
     * @param UserGroup $RoleUserGroup
     * @param UserGroup $UserUserGroup
     * @param UserGroup $TermUserGroup
     * @param UserGroup $PostUserGroup
     * @param UserGroup $PluggableObjectUserGroup
     */
    public function testGetAssignedObjectsByType(
        UserGroup $RoleUserGroup,
        UserGroup $UserUserGroup,
        UserGroup $TermUserGroup,
        UserGroup $PostUserGroup,
        UserGroup $PluggableObjectUserGroup
    ) {
        self::assertEquals(
            [1 => 'role', 2 => 'role', 3 => 'role'],
            $RoleUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                2 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                3 => ObjectHandler::GENERAL_USER_OBJECT_TYPE
            ],
            $UserUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );

        self::assertEquals(
            [1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term'],
            $TermUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
        );
        self::setValue($TermUserGroup, 'aFullObjectMembership', ['termObjectType' => [1 => 'term', 2 => 'term']]);
        self::assertEquals(
            [1 => 'term', 2 => 'term'],
            $TermUserGroup->getAssignedObjectsByType('termObjectType')
        );

        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 4 => 'post', 9 => 'post'],
            $PostUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_POST_OBJECT_TYPE)
        );
        self::setValue($PostUserGroup, 'aFullObjectMembership', ['postObjectType' => [3 => 'post', 4 => 'post']]);
        self::assertEquals(
            [3 => 'post', 4 => 'post'],
            $PostUserGroup->getAssignedObjectsByType('postObjectType')
        );

        self::assertEquals(
            [1 => 'pluggableObject', 6 => 'pluggableObject'],
            $PluggableObjectUserGroup->getAssignedObjectsByType('_pluggableObject_')
        );

        self::assertEquals(
            [],
            $PluggableObjectUserGroup->getAssignedObjectsByType('nothing')
        );
    }
}
