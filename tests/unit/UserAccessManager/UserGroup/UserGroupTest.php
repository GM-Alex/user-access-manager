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

/**
 * Class UserGroupTest
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::__construct()
     */
    public function testCanCreateInstance()
    {
        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $this->getDatabase(),
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $oUserGroup);

        $oDatabase = $this->getDatabase();
        $oDatabase->expects($this->once())
            ->method('prepare');

        $oDatabase->expects($this->once())
            ->method('getUserGroupTable');

        $oDatabase->expects($this->once())
            ->method('getRow');

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            1
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $oUserGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::load()
     *
     * @return UserGroup
     */
    public function testLoad()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $oDatabase->expects($this->exactly(2))
            ->method('prepare')
            ->withConsecutive([
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

        $oDbUserGroup = new \stdClass();
        $oDbUserGroup->groupname = 'groupName';
        $oDbUserGroup->groupdesc = 'groupDesc';
        $oDbUserGroup->read_access = 'readAccess';
        $oDbUserGroup->write_access = 'writeAccess';
        $oDbUserGroup->ip_range = 'ipRange;ipRange2';

        $oDatabase->expects($this->exactly(2))
            ->method('getRow')
            ->with('queryString')
            ->will($this->onConsecutiveCalls(null, $oDbUserGroup));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertFalse($oUserGroup->load(1));
        self::assertAttributeEquals(null, '_iId', $oUserGroup);
        self::assertAttributeEquals(null, '_sGroupName', $oUserGroup);
        self::assertAttributeEquals(null, '_sGroupDesc', $oUserGroup);
        self::assertAttributeEquals(null, '_sReadAccess', $oUserGroup);
        self::assertAttributeEquals(null, '_sWriteAccess', $oUserGroup);
        self::assertAttributeEquals(null, '_sIpRange', $oUserGroup);

        self::assertTrue($oUserGroup->load(2));
        self::assertAttributeEquals(2, '_iId', $oUserGroup);
        self::assertAttributeEquals('groupName', '_sGroupName', $oUserGroup);
        self::assertAttributeEquals('groupDesc', '_sGroupDesc', $oUserGroup);
        self::assertAttributeEquals('readAccess', '_sReadAccess', $oUserGroup);
        self::assertAttributeEquals('writeAccess', '_sWriteAccess', $oUserGroup);
        self::assertAttributeEquals('ipRange;ipRange2', '_sIpRange', $oUserGroup);

        return $oUserGroup;
    }

    /**
     * @group   unit
     * @depends testLoad
     * @covers  \UserAccessManager\UserGroup\UserGroup::getId()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getGroupName()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getGroupDesc()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getReadAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getWriteAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::getIpRange()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setGroupName()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setGroupDesc()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setReadAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setWriteAccess()
     * @covers  \UserAccessManager\UserGroup\UserGroup::setIpRange()
     *
     * @param UserGroup $oUserGroup
     */
    public function testSimpleGetterSetter(UserGroup $oUserGroup)
    {
        self::assertEquals(2, $oUserGroup->getId());
        self::assertEquals('groupName', $oUserGroup->getGroupName());
        self::assertEquals('groupDesc', $oUserGroup->getGroupDesc());
        self::assertEquals('readAccess', $oUserGroup->getReadAccess());
        self::assertEquals('writeAccess', $oUserGroup->getWriteAccess());
        self::assertEquals(['ipRange', 'ipRange2'], $oUserGroup->getIpRange());
        self::assertEquals('ipRange;ipRange2', $oUserGroup->getIpRange('string'));

        $oUserGroup->setGroupName('groupNameNew');
        self::assertAttributeEquals('groupNameNew', '_sGroupName', $oUserGroup);

        $oUserGroup->setGroupDesc('groupDescNew');
        self::assertAttributeEquals('groupDescNew', '_sGroupDesc', $oUserGroup);

        $oUserGroup->setReadAccess('readAccessNew');
        self::assertAttributeEquals('readAccessNew', '_sReadAccess', $oUserGroup);

        $oUserGroup->setWriteAccess('writeAccessNew');
        self::assertAttributeEquals('writeAccessNew', '_sWriteAccess', $oUserGroup);

        $oUserGroup->setIpRange(['ipRangeNew', 'ipRangeNew2']);
        self::assertAttributeEquals('ipRangeNew;ipRangeNew2', '_sIpRange', $oUserGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::save()
     */
    public function testSave()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(4))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $oDatabase->expects($this->exactly(2))
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

        $oDatabase->expects($this->once())
            ->method('getLastInsertId')
            ->will($this->returnValue(123));

        $oDatabase->expects($this->exactly(2))
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

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        $oUserGroup->setGroupName('groupName');
        $oUserGroup->setGroupDesc('groupDesc');
        $oUserGroup->setReadAccess('readAccess');
        $oUserGroup->setWriteAccess('writeAccess');
        $oUserGroup->setIpRange(['ipRange', 'ipRange2']);

        self::assertFalse($oUserGroup->save());
        self::assertNull($oUserGroup->getId());
        self::assertTrue($oUserGroup->save());
        self::assertEquals(123, $oUserGroup->getId());

        self::setValue($oUserGroup, '_iId', 2);
        self::assertFalse($oUserGroup->save());
        self::assertTrue($oUserGroup->save());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::delete()
     * @covers \UserAccessManager\UserGroup\UserGroup::removeObject()
     */
    public function testDelete()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $oDatabase->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $oDatabase->expects($this->exactly(2))
            ->method('delete')
            ->with(
                'userGroupTable',
                ['ID' => 123]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $oDatabase->expects($this->exactly(4))
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

        $oDatabase->expects($this->exactly(4))
            ->method('query')
            ->with('preparedQuery')
            ->will($this->onConsecutiveCalls(true, false, true, true));

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue(['objectType']));

        $oObjectHandler->expects($this->exactly(5))
            ->method('isValidObjectType')
            ->withConsecutive(['objectType'], ['invalid'], ['objectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(true, false, true));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $oObjectHandler
        );

        self::assertFalse($oUserGroup->delete());
        self::setValue($oUserGroup, '_iId', 123);
        self::assertFalse($oUserGroup->delete());
        self::assertTrue($oUserGroup->delete());
        self::assertFalse($oUserGroup->removeObject('invalid'));
        self::assertFalse($oUserGroup->removeObject('objectType'));
        self::assertTrue($oUserGroup->removeObject('objectType'));
        self::assertTrue($oUserGroup->removeObject('objectType', 1));

        self::assertAttributeEquals([], '_aAssignedObjects', $oUserGroup);
        self::assertAttributeEquals([], '_aRoleMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aUserMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aTermMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aPostMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aFullObjectMembership', $oUserGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::addObject()
     */
    public function testAddObject()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $oDatabase->expects($this->exactly(2))
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

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(5))
            ->method('getGeneralObjectType')
            ->withConsecutive(['invalid'], ['generalObjectType'], ['notValidObjectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(null, null, 'generalNotValidObjectType', 'generalObjectType', 'generalObjectType'));

        $oObjectHandler->expects($this->exactly(3))
            ->method('isValidObjectType')
            ->withConsecutive(['notValidObjectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $oObjectHandler
        );

        self::setValue($oUserGroup, '_iId', 123);
        self::setValue($oUserGroup, '_aAssignedObjects', [1 => 1, 2 => 2]);
        self::setValue($oUserGroup, '_aRoleMembership', [1 => 1, 2 => 2]);
        self::setValue($oUserGroup, '_aUserMembership', [1 => 1, 2 => 2]);
        self::setValue($oUserGroup, '_aTermMembership', [1 => 1, 2 => 2]);
        self::setValue($oUserGroup, '_aPostMembership', [1 => 1, 2 => 2]);
        self::setValue($oUserGroup, '_aFullObjectMembership', [1 => 1, 2 => 2]);

        self::assertFalse($oUserGroup->addObject('invalid', 321));
        self::assertFalse($oUserGroup->addObject('generalObjectType', 321));
        self::assertFalse($oUserGroup->addObject('notValidObjectType', 321));
        self::assertFalse($oUserGroup->addObject('objectType', 321));
        self::assertTrue($oUserGroup->addObject('objectType', 321));

        self::assertAttributeEquals([], '_aAssignedObjects', $oUserGroup);
        self::assertAttributeEquals([], '_aRoleMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aUserMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aTermMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aPostMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aFullObjectMembership', $oUserGroup);
    }

    /**
     * Generates return values.
     *
     * @param int $iNumber
     *
     * @return array
     */
    private function generateReturn($iNumber)
    {
        $aReturn = [];

        for ($iCounter = 1; $iCounter <= $iNumber; $iCounter++) {
            $oReturn = new \stdClass();
            $oReturn->id = $iCounter;
            $aReturn[] = $oReturn;
        }

        return $aReturn;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::_getAssignedObjects()
     * @covers  \UserAccessManager\UserGroup\UserGroup::_isObjectAssignedToGroup()
     */
    public function testAssignedObject()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $sQuery = 'SELECT object_id AS id
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $oDatabase->expects($this->exactly(3))
            ->method('prepare')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($sQuery), [123, 'noResultObjectType', 'noResultObjectType']],
                [new MatchIgnoreWhitespace($sQuery), [123, 'objectType', 'objectType']],
                [new MatchIgnoreWhitespace($sQuery), [123, 'something', 'something']]
            )
            ->will($this->onConsecutiveCalls('nonResultPreparedQuery', 'preparedQuery', 'nonResultSomethingPreparedQuery'));

        $oDatabase->expects($this->exactly(3))
            ->method('getResults')
            ->withConsecutive(
                ['nonResultPreparedQuery'],
                ['preparedQuery'],
                ['nonResultSomethingPreparedQuery']
            )
            ->will($this->onConsecutiveCalls(null, $this->generateReturn(3), null));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($oUserGroup, '_iId', 123);

        $aResult = self::callMethod($oUserGroup, '_getAssignedObjects', ['noResultObjectType']);
        self::assertEquals([], $aResult);
        self::assertAttributeEquals(['noResultObjectType' => []], '_aAssignedObjects', $oUserGroup);

        $aResult = self::callMethod($oUserGroup, '_getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 1, 2 => 2, 3 => 3], $aResult);
        self::assertAttributeEquals(
            ['noResultObjectType' => [], 'objectType' => [1 => 1, 2 => 2, 3 => 3]],
            '_aAssignedObjects',
            $oUserGroup
        );

        $aResult = self::callMethod($oUserGroup, '_getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 1, 2 => 2, 3 => 3], $aResult);

        $blResult = self::callMethod($oUserGroup, '_isObjectAssignedToGroup', ['objectType', 1]);
        self::assertTrue($blResult);
        $blResult = self::callMethod($oUserGroup, '_isObjectAssignedToGroup', ['objectType', 2]);
        self::assertTrue($blResult);
        $blResult = self::callMethod($oUserGroup, '_isObjectAssignedToGroup', ['objectType', 3]);
        self::assertTrue($blResult);

        $blResult = self::callMethod($oUserGroup, '_isObjectAssignedToGroup', ['objectType', 4]);
        self::assertFalse($blResult);
        $blResult = self::callMethod($oUserGroup, '_isObjectAssignedToGroup', ['noResultObjectType', 1]);
        self::assertFalse($blResult);
        $blResult = self::callMethod($oUserGroup, '_isObjectAssignedToGroup', ['something', 1]);
        self::assertFalse($blResult);
    }

    /**
     * Returns the database mock for the member tests
     *
     * @param array $aTypes
     * @param array $aNumberOfReturns
     * @param array $aGetResultsWith
     * @param array $aGetResultsWill
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    private function getDatabaseMockForMemberTests(
        array $aTypes,
        array $aNumberOfReturns,
        array $aGetResultsWith = [],
        array $aGetResultsWill = []
    )
    {
        $sQuery = 'SELECT object_id AS id
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $aPrepareWith = [];
        $aPrepareWill = [];

        foreach ($aTypes as $sType) {
            $aPrepareWith[] = [new MatchIgnoreWhitespace($sQuery), [123, "_{$sType}_", "_{$sType}_"]];
            $aPrepareWill[] = "{$sType}PreparedQuery";
            $aGetResultsWith[] = ["{$sType}PreparedQuery"];
        }

        foreach ($aNumberOfReturns as $iNumberOfReturn) {
            $aGetResultsWill[] = $this->generateReturn($iNumberOfReturn);
        }

        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->any())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $oDatabase->expects($this->exactly(count($aPrepareWith)))
            ->method('prepare')
            ->withConsecutive(...$aPrepareWith)
            ->will($this->onConsecutiveCalls(...$aPrepareWill));

        $oDatabase->expects($this->exactly(count($aGetResultsWith)))
            ->method('getResults')
            ->withConsecutive(...$aGetResultsWith)
            ->will($this->onConsecutiveCalls(...$aGetResultsWill));

        return $oDatabase;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::_isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isRoleMember()
     *
     * @return UserGroup
     */
    public function testIsRoleMember()
    {
        $oDatabase = $this->getDatabaseMockForMemberTests(['role'], [3]);

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($oUserGroup, '_iId', 123);
        $aRecursiveMembership = [];

        $blReturn = $oUserGroup->isRoleMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isRoleMember(4, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $oUserGroup;
    }

    /**
     * Prototype function for the testIsUserMember
     *
     * @param array $aGetResultsWith
     * @param array $aGetResultsWill
     *
     * @return UserGroup
     */
    private function getTestIsUserMemberPrototype(array $aGetResultsWith = [], array $aGetResultsWill = [])
    {
        $oDatabase = $this->getDatabaseMockForMemberTests(
            ['role', 'user'],
            [3, 2],
            $aGetResultsWith,
            $aGetResultsWill
        );

        $oDatabase->expects($this->any())
            ->method('getUsersTable')
            ->will($this->returnValue('usersTable'));

        $oDatabase->expects($this->any())
            ->method('getCapabilitiesTable')
            ->will($this->returnValue('capabilitiesTable'));

        /**
         * @var \stdClass $oFirstUser
         */
        $oFirstUser = $this->getMockBuilder('\WP_User')->getMock();
        $oFirstUser->capabilitiesTable = [1 => 1, 2 => 2];

        /**
         * @var \stdClass $oSecondUser
         */
        $oSecondUser = $this->getMockBuilder('\WP_User')->getMock();
        $oSecondUser->capabilitiesTable = 'invalid';

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->any())
            ->method('getUser')
            ->will($this->returnCallback(function ($sUserId) use ($oFirstUser, $oSecondUser) {
                if ($sUserId === 1) {
                    return $oFirstUser;
                } elseif ($sUserId === 2) {
                    return $oSecondUser;
                }

                return null;
            }));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $this->getConfig(),
            $this->getUtil(),
            $oObjectHandler
        );

        self::setValue($oUserGroup, '_iId', 123);

        return $oUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::_isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isUserMember()
     *
     * @return UserGroup
     */
    public function testIsUserMember()
    {
        $oUserGroup = $this->getTestIsUserMemberPrototype();
        $aRecursiveMembership = [];

        $blReturn = $oUserGroup->isUserMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [1 => 1, 2 => 2]
        ],
            $aRecursiveMembership
        );

        $blReturn = $oUserGroup->isUserMember(2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isUserMember(3, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $oUserGroup;
    }

    /**
     * Prototype function for the testIsTermMember
     *
     * @return UserGroup
     */
    private function getTestIsTermMemberPrototype()
    {
        $oDatabase = $this->getDatabaseMockForMemberTests(['term'], [3]);

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->exactly(4))
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 3],
                        2 => [3 => 3],
                        4 => [1 => 1]
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        3 => [1 => 1, 2 => 2],
                        1 => [4 => 4]
                    ]
                ]
            ]));

        $oObjectHandler->expects($this->any())
            ->method('isTaxonomy')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'termObjectType');
            }));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(5))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $oConfig,
            $this->getUtil(),
            $oObjectHandler
        );

        self::setValue($oUserGroup, '_iId', 123);

        return $oUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::_isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isTermMember()
     *
     * @return UserGroup
     */
    public function testIsTermMember()
    {
        $oUserGroup = $this->getTestIsTermMemberPrototype();
        $aRecursiveMembership = [];

        // term tests
        $blReturn = $oUserGroup->isTermMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([],$aRecursiveMembership);

        $blReturn = $oUserGroup->isTermMember(2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 3]], $aRecursiveMembership);

        $blReturn = $oUserGroup->isTermMember(3, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isTermMember(4, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 1]], $aRecursiveMembership);

        $blReturn = $oUserGroup->isTermMember(5, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $oUserGroup;
    }

    /**
     * Prototype function for the testIsPostMember
     *
     * @return UserGroup
     */
    private function getTestIsPostMemberPrototype()
    {
        $oDatabase = $this->getDatabaseMockForMemberTests(['post', 'term'], [3, 3]);
        $oConfig = $this->getConfig();

        $aLockRecursiveReturns = [false, true, true, true, true, false];

        $oConfig->expects($this->any())
            ->method('lockRecursive')
            ->will($this->returnCallback(function () use (&$aLockRecursiveReturns) {
                if (count($aLockRecursiveReturns) > 0) {
                    return array_shift($aLockRecursiveReturns);
                }

                return true;
            }));

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->any())
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 3],
                        2 => [3 => 3],
                        4 => [1 => 1]
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        3 => [1 => 1, 2 => 2],
                        1 => [4 => 4]
                    ]
                ]
            ]));

        $oObjectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === 'postObjectType');
            }));

        $oObjectHandler->expects($this->any())
            ->method('getPostTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        1 => [3 => 3],
                        2 => [3 => 3],
                        4 => [1 => 1]
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        3 => [1 => 1, 2 => 2],
                        1 => [4 => 4]
                    ]
                ]
            ]));

        $oObjectHandler->expects($this->any())
            ->method('getPostTermMap')
            ->will($this->returnValue([
                2 => [3 => 3, 9 => 9]
            ]));

        $oObjectHandler->expects($this->any())
            ->method('getTermPostMap')
            ->will($this->returnValue([
                2 => [9 => 9]
            ]));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $oConfig,
            $this->getUtil(),
            $oObjectHandler
        );

        self::setValue($oUserGroup, '_iId', 123);

        return $oUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::_isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isPostMember()
     *
     * @return UserGroup
     */
    public function testIsPostMember()
    {
        $oUserGroup = $this->getTestIsPostMemberPrototype();
        $aRecursiveMembership = [];

        // post tests
        $blReturn = $oUserGroup->isPostMember(1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isPostMember(2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(
            [ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 3], ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 3]],
            $aRecursiveMembership
        );

        $blReturn = $oUserGroup->isPostMember(3, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isPostMember( 4, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 1]], $aRecursiveMembership);

        $blReturn = $oUserGroup->isPostMember(5, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $oUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::_isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\UserGroup::isPluggableObjectMember()
     *
     * @return UserGroup
     */
    public function testIsPluggableObjectMember()
    {
        $oDatabase = $this->getDatabaseMockForMemberTests(['pluggableObject'], [2]);

        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->any())
            ->method('getPluggableObject')
            ->will($this->returnCallback(
                function ($sObjectType) {
                    if ($sObjectType === '_pluggableObject_') {
                        $oPluggableObject = $this->getMockForAbstractClass(
                            '\UserAccessManager\ObjectHandler\PluggableObject',
                            [],
                            '',
                            false
                        );

                        $oPluggableObject->expects($this->any())
                            ->method('getRecursiveMembership')
                            ->will($this->returnCallback(
                                function ($oUserGroup, $iObjectId) {
                                    return ($iObjectId === 1) ? ['pluggableObject' => [1 => 1]] : [];
                                }
                            ));

                        $oPluggableObject->expects($this->any())
                            ->method('getFullObjects')
                            ->will($this->returnValue([1 => 1, 6 => 6]));

                        return $oPluggableObject;
                    }

                    return null;
                }
            ));

        $oObjectHandler->expects($this->any())
            ->method('isPluggableObject')
            ->will($this->returnCallback(function ($sObjectType) {
                return ($sObjectType === '_pluggableObject_');
            }));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $oConfig = $this->getConfig(),
            $this->getUtil(),
            $oObjectHandler
        );

        self::setValue($oUserGroup, '_iId', 123);
        $aRecursiveMembership = [];

        // pluggable object tests
        $blReturn = $oUserGroup->isPluggableObjectMember('noPluggableObject', 1, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isPluggableObjectMember('_pluggableObject_', 1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals(
            [
                'pluggableObject' => [1 => 1]
            ],
            $aRecursiveMembership
        );

        $blReturn = $oUserGroup->isPluggableObjectMember('_pluggableObject_', 3, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        return $oUserGroup;
    }

    /**
     * Assertion helper for testIsMemberFunctions
     *
     * @param UserGroup $oUserGroup
     * @param bool      $blExpectedReturn
     * @param array     $aExpectedRecursiveMembership
     * @param string    $sObjectType
     * @param string    $sObjectId
     */
    private function memberFunctionAssertions(
        UserGroup $oUserGroup,
        $blExpectedReturn,
        array $aExpectedRecursiveMembership,
        $sObjectType,
        $sObjectId
    )
    {
        $aRecursiveMembership = [];
        $blReturn = $oUserGroup->isObjectMember($sObjectType, $sObjectId, $aRecursiveMembership);

        self::assertEquals($blExpectedReturn, $blReturn);
        self::assertEquals($aExpectedRecursiveMembership, $aRecursiveMembership);

        self::assertEquals(
            $aExpectedRecursiveMembership,
            $oUserGroup->getRecursiveMembershipForObject(
                $sObjectType,
                $sObjectId
            )
        );

        self::assertEquals(
            count($aExpectedRecursiveMembership) > 0,
            $oUserGroup->isLockedRecursive($sObjectType, $sObjectId)
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
     * @param UserGroup $oRoleUserGroup
     * @param UserGroup $oUserUserGroup
     * @param UserGroup $oTermUserGroup
     * @param UserGroup $oPostUserGroup
     * @param UserGroup $oPluggableObjectUserGroup
     */
    public function testIsMemberFunctions(
        UserGroup $oRoleUserGroup,
        UserGroup $oUserUserGroup,
        UserGroup $oTermUserGroup,
        UserGroup $oPostUserGroup,
        UserGroup $oPluggableObjectUserGroup
    )
    {
        // role tests
        $this->memberFunctionAssertions($oRoleUserGroup, true, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions($oRoleUserGroup, false, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 4);

        // user tests
        $this->memberFunctionAssertions(
            $oUserUserGroup, true, [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [1 => 1, 2 => 2]],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1
        );
        $this->memberFunctionAssertions($oUserUserGroup, true, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 2);
        $this->memberFunctionAssertions($oUserUserGroup, false, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 3);

        // term tests
        $this->memberFunctionAssertions($oTermUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $oTermUserGroup, true, [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 3]],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 2
        );
        $this->memberFunctionAssertions($oTermUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $oTermUserGroup, true, [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 1]],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4
        );
        $this->memberFunctionAssertions($oTermUserGroup, false, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5);

        // post tests
        $this->memberFunctionAssertions($oPostUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $oPostUserGroup, true, [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 3],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 3]
            ],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE, 2
        );
        $this->memberFunctionAssertions(
            $oPostUserGroup, true, [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 3],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 3]
            ],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE, 2
        );
        $this->memberFunctionAssertions($oPostUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $oPostUserGroup, true, [ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 1]],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE, 4
        );
        $this->memberFunctionAssertions($oPostUserGroup, false, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 5);

        // pluggable object tests
        $this->memberFunctionAssertions($oPluggableObjectUserGroup, false, [], 'noPluggableObject', 1);
        $this->memberFunctionAssertions(
            $oPluggableObjectUserGroup, true, ['pluggableObject' => [1 => 1]],
            '_pluggableObject_', 1
        );
        $this->memberFunctionAssertions($oPluggableObjectUserGroup, false, [], '_pluggableObject_', 3);
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
            $oReturn = new \stdClass();
            $oReturn->ID = $iNumber;
            $aReturn[] = $oReturn;
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

        $oUserGroup = $this->getTestIsUserMemberPrototype(
            [[new MatchIgnoreWhitespace($sQuery)]],
            [$this->generateUserReturn([10 => 10, 1, 2, 3])]
        );

        self::assertEquals([1 => 1, 2 => 2], $oUserGroup->getFullUsers());

        return $oUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullTerms()
     * @covers \UserAccessManager\UserGroup\UserGroup::_getFullObjects()
     *
     * @return UserGroup
     */
    public function testGetFullTerms()
    {
        $oUserGroup = $this->getTestIsTermMemberPrototype();
        self::assertEquals([1 => 1, 2 => 2, 3 => 3], $oUserGroup->getFullTerms());

        self::setValue($oUserGroup, '_aFullObjectMembership', []);
        self::assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4], $oUserGroup->getFullTerms());

        return $oUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::getFullPosts()
     * @covers \UserAccessManager\UserGroup\UserGroup::_getFullObjects()
     *
     * @return UserGroup
     */
    public function testGetFullPosts()
    {
        $oUserGroup = $this->getTestIsPostMemberPrototype();
        self::assertEquals([1 => 1, 2 => 2, 3 => 3, 9 => 9], $oUserGroup->getFullPosts());

        self::setValue($oUserGroup, '_aFullObjectMembership', []);
        self::assertEquals([1 => 1, 2 => 2, 3 => 3, 4 => 4, 9 => 9], $oUserGroup->getFullPosts());

        return $oUserGroup;
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
     * @param UserGroup $oRoleUserGroup
     * @param UserGroup $oUserUserGroup
     * @param UserGroup $oTermUserGroup
     * @param UserGroup $oPostUserGroup
     * @param UserGroup $oPluggableObjectUserGroup
     */
    public function testGetAssignedObjectsByType(
        UserGroup $oRoleUserGroup,
        UserGroup $oUserUserGroup,
        UserGroup $oTermUserGroup,
        UserGroup $oPostUserGroup,
        UserGroup $oPluggableObjectUserGroup
    )
    {
        self::assertEquals(
            [1 => 1, 2 => 2, 3 => 3],
            $oRoleUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
        );
        self::assertEquals(
            [1 => 1, 2 => 2],
            $oUserUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );
        self::assertEquals(
            [1 => 1, 2 => 2, 3 => 3, 4 => 4],
            $oTermUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
        );
        self::assertEquals(
            [1 => 1, 2 => 2, 3 => 3, 4 => 4, 9 => 9],
            $oPostUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_POST_OBJECT_TYPE)
        );
        self::assertEquals(
            [1 => 1, 6 => 6],
            $oPluggableObjectUserGroup->getAssignedObjectsByType('_pluggableObject_')
        );
        self::assertEquals(
            [],
            $oPluggableObjectUserGroup->getAssignedObjectsByType('nothing')
        );
    }
}
