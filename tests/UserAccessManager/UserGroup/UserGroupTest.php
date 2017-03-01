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
        $oDatabase->expects($this->exactly(1))
            ->method('prepare');

        $oDatabase->expects($this->exactly(1))
            ->method('getUserGroupTable');

        $oDatabase->expects($this->exactly(1))
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

        $oDatabase->expects($this->exactly(1))
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

        $oObjectHandler->expects($this->exactly(1))
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
        self::assertAttributeEquals([], '_aObjectMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aFullObjects', $oUserGroup);
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
        self::setValue($oUserGroup, '_aObjectMembership', [1 => 1, 2 => 2]);
        self::setValue($oUserGroup, '_aFullObjects', [1 => 1, 2 => 2]);

        self::assertFalse($oUserGroup->addObject('invalid', 321));
        self::assertFalse($oUserGroup->addObject('generalObjectType', 321));
        self::assertFalse($oUserGroup->addObject('notValidObjectType', 321));
        self::assertFalse($oUserGroup->addObject('objectType', 321));
        self::assertTrue($oUserGroup->addObject('objectType', 321));

        self::assertAttributeEquals([], '_aAssignedObjects', $oUserGroup);
        self::assertAttributeEquals([], '_aObjectMembership', $oUserGroup);
        self::assertAttributeEquals([], '_aFullObjects', $oUserGroup);
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
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::_isObjectRecursiveMember()
     * @covers  \UserAccessManager\UserGroup\UserGroup::_isUserMember()
     * @covers  \UserAccessManager\UserGroup\UserGroup::_isTermMember()
     * @covers  \UserAccessManager\UserGroup\UserGroup::_isPostMember()
     * @covers  \UserAccessManager\UserGroup\UserGroup::_isPluggableObjectMember()
     * @covers  \UserAccessManager\UserGroup\UserGroup::isObjectMember()
     */
    public function testIsMember()
    {
        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $oDatabase->expects($this->exactly(3))
            ->method('getCapabilitiesTable')
            ->will($this->returnValue('capabilitiesTable'));

        $sQuery = 'SELECT object_id AS id
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $oDatabase->expects($this->exactly(3))
            ->method('prepare')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($sQuery), [123, '_role_', '_role_']],
                [new MatchIgnoreWhitespace($sQuery), [123, '_user_', '_user_']],
                [new MatchIgnoreWhitespace($sQuery), [123, '_term_', '_term_']],
                [new MatchIgnoreWhitespace($sQuery), [123, '_post_', '_post_']]
            )
            ->will($this->onConsecutiveCalls(
                'rolePreparedQuery',
                'userPreparedQuery',
                'termPreparedQuery',
                'postPreparedQuery'
            ));

        $oDatabase->expects($this->exactly(3))
            ->method('getResults')
            ->withConsecutive(
                ['rolePreparedQuery'],
                ['userPreparedQuery'],
                ['termPreparedQuery'],
                ['postPreparedQuery']
            )
            ->will($this->onConsecutiveCalls(
                $this->generateReturn(3),
                $this->generateReturn(2),
                $this->generateReturn(3),
                $this->generateReturn(3)
            ));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(5))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $oObjectHandler = $this->getObjectHandler();

        /**
         * @var \stdClass $oFirstUser
         */
        $oFirstUser = $this->getMockBuilder('\WP_User')->getMock();
        $oFirstUser->capabilitiesTable = [1 => 1, 2 => 2];

        /**
         * @var \stdClass $oSecondUser
         */
        $oSecondUser = $this->getMockBuilder('\WP_User')->getMock();
        $oSecondUser->capabilitiesTable = 'invalide';

        $oObjectHandler->expects($this->exactly(3))
            ->method('getUser')
            ->withConsecutive([1], [2], [3])
            ->will($this->onConsecutiveCalls($oFirstUser, $oSecondUser, null));

        $oObjectHandler->expects($this->exactly(1))
            ->method('isTaxonomy')
            ->withConsecutive(['termObjectType'])
            ->will($this->onConsecutiveCalls(true));

        $oObjectHandler->expects($this->exactly(4))
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 3],
                        2 => [3 => 3],
                        4 => [1 => 1]
                    ]
                ]
            ]));

        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $oDatabase,
            $oConfig,
            $this->getUtil(),
            $oObjectHandler
        );

        self::setValue($oUserGroup, '_iId', 123);
        $aRecursiveMembership = [];

        // role tests
        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 4, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        // user tests
        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [1 => 1, 2 => 2]
            ],
            $aRecursiveMembership
        );

        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 3, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        // term tests
        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([],
            $aRecursiveMembership
        );

        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 2, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 3]
        ], $aRecursiveMembership);

        $blReturn = $oUserGroup->isObjectMember('termObjectType', 3, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([], $aRecursiveMembership);

        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 4, $aRecursiveMembership);
        self::assertTrue($blReturn);
        self::assertEquals([
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 1]
        ], $aRecursiveMembership);

        $blReturn = $oUserGroup->isObjectMember(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5, $aRecursiveMembership);
        self::assertFalse($blReturn);
        self::assertEquals([], $aRecursiveMembership);
    }
}
