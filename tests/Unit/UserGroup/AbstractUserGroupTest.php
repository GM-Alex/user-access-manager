<?php
/**
 * AbstractUserGroupTest.php
 *
 * The AbstractUserGroupTest unit test class file.
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

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\ObjectMembership\PostMembershipHandler;
use UserAccessManager\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\TermMembershipHandler;
use UserAccessManager\ObjectMembership\UserMembershipHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AbstractUserGroupTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\AbstractUserGroup
 */
class AbstractUserGroupTest extends UserAccessManagerTestCase
{
    /**
     * @param Php                            $php
     * @param Wordpress                      $wordpress
     * @param Database                       $database
     * @param MainConfig                     $config
     * @param Util                           $util
     * @param ObjectHandler                  $objectHandler
     * @param AssignmentInformationFactory   $assignmentInformationFactory
     * @param null|string                    $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractUserGroup
     */
    private function getStub(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory,
        $id = null
    ) {
        $stub = $this->getMockForAbstractClass(
            AbstractUserGroup::class,
            [],
            '',
            false
        );

        self::setValue($stub, 'php', $php);
        self::setValue($stub, 'wordpress', $wordpress);
        self::setValue($stub, 'database', $database);
        self::setValue($stub, 'config', $config);
        self::setValue($stub, 'util', $util);
        self::setValue($stub, 'objectHandler', $objectHandler);
        self::setValue($stub, 'assignmentInformationFactory', $assignmentInformationFactory);
        self::setValue($stub, 'id', $id);

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );

        self::setValue($abstractUserGroup, 'type', 'type');
        $abstractUserGroup->__construct(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );

        self::assertInstanceOf(AbstractUserGroup::class, $abstractUserGroup);
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testUserGroupTypeException()
    {
        self::expectException(UserGroupTypeException::class);
        $this->getMockForAbstractClass(
            AbstractUserGroup::class,
            [
                $this->getPhp(),
                $this->getWordpress(),
                $this->getDatabase(),
                $this->getMainConfig(),
                $this->getUtil(),
                $this->getObjectHandler(),
                $this->getExtendedAssignmentInformationFactory()
            ]
        );
    }

    /**
     * @group   unit
     * @covers  ::getId()
     * @covers  ::getType()
     * @covers  ::getName()
     * @covers  ::getDescription()
     * @covers  ::getReadAccess()
     * @covers  ::getWriteAccess()
     * @covers  ::setName()
     * @covers  ::setDescription()
     * @covers  ::setReadAccess()
     * @covers  ::setWriteAccess()
     */
    public function testSimpleGetterSetter()
    {
        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory(),
            2
        );

        self::setValue($abstractUserGroup, 'type', 'type');
        self::setValue($abstractUserGroup, 'name', 'groupName');
        self::setValue($abstractUserGroup, 'description', 'groupDesc');
        self::setValue($abstractUserGroup, 'readAccess', 'readAccess');
        self::setValue($abstractUserGroup, 'writeAccess', 'writeAccess');

        self::assertEquals(2, $abstractUserGroup->getId());
        self::assertEquals('type', $abstractUserGroup->getType());
        self::assertEquals('groupName', $abstractUserGroup->getName());
        self::assertEquals('groupDesc', $abstractUserGroup->getDescription());
        self::assertEquals('readAccess', $abstractUserGroup->getReadAccess());
        self::assertEquals('writeAccess', $abstractUserGroup->getWriteAccess());

        $abstractUserGroup->setName('groupNameNew');
        self::assertAttributeEquals('groupNameNew', 'name', $abstractUserGroup);

        $abstractUserGroup->setDescription('groupDescNew');
        self::assertAttributeEquals('groupDescNew', 'description', $abstractUserGroup);

        $abstractUserGroup->setReadAccess('readAccessNew');
        self::assertAttributeEquals('readAccessNew', 'readAccess', $abstractUserGroup);

        $abstractUserGroup->setWriteAccess('writeAccessNew');
        self::assertAttributeEquals('writeAccessNew', 'writeAccess', $abstractUserGroup);
    }

    /**
     * @group  unit
     * @covers ::addObject()
     * @covers ::resetObjects()
     * @covers ::addDefaultType()
     */
    public function testAddObject()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(6))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(6))
            ->method('replace')
            ->withConsecutive(
                [
                    'userGroupToObjectTable',
                    [
                        'group_id' => 123,
                        'group_type' => 'type',
                        'object_id' => 321,
                        'general_object_type' => 'generalObjectType',
                        'object_type' => 'objectType',
                        'from_date' => null,
                        'to_date' => null
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    [
                        'group_id' => 123,
                        'group_type' => 'type',
                        'object_id' => 321,
                        'general_object_type' => 'generalObjectType',
                        'object_type' => 'objectType',
                        'from_date' => 'fromDate',
                        'to_date' => 'toDate'
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    [
                        'group_id' => 123,
                        'group_type' => 'type',
                        'object_id' => '',
                        'general_object_type' => 'generalDefaultObjectType',
                        'object_type' => 'defaultObjectType',
                        'from_date' => null,
                        'to_date' => null
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    [
                        'group_id' => 123,
                        'group_type' => 'type',
                        'object_id' => '',
                        'general_object_type' => 'generalDefaultObjectType',
                        'object_type' => 'defaultObjectType',
                        'from_date' => '1970-01-01 00:00:01',
                        'to_date' => '1970-01-01 00:00:02'
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    [
                        'group_id' => 123,
                        'group_type' => 'type',
                        'object_id' => '',
                        'general_object_type' => 'generalDefaultObjectType',
                        'object_type' => 'defaultObjectType',
                        'from_date' => '1970-01-01 00:00:01',
                        'to_date' => '1970-01-01 00:00:02'
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                ],
                [
                    'userGroupToObjectTable',
                    [
                        'group_id' => 123,
                        'group_type' => 'type',
                        'object_id' => '',
                        'general_object_type' => 'generalDefaultObjectType',
                        'object_type' => 'defaultObjectType',
                        'from_date' => '1970-01-01 00:00:01',
                        'to_date' => '1970-01-01 00:00:02'
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                ]
            )
            ->will($this->onConsecutiveCalls(false, true));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(9))
            ->method('getGeneralObjectType')
            ->withConsecutive(
                ['invalid'],
                ['generalObjectType'],
                ['notValidObjectType'],
                ['objectType'],
                ['objectType'],
                ['defaultObjectType'],
                ['defaultObjectType'],
                ['defaultObjectType'],
                ['defaultObjectType']
            )
            ->will($this->onConsecutiveCalls(
                null,
                null,
                'generalNotValidObjectType',
                'generalObjectType',
                'generalObjectType',
                'generalDefaultObjectType',
                'generalDefaultObjectType',
                'generalDefaultObjectType',
                'generalDefaultObjectType'
            ));

        $objectHandler->expects($this->exactly(7))
            ->method('isValidObjectType')
            ->withConsecutive(
                ['notValidObjectType'],
                ['objectType'],
                ['objectType'],
                ['defaultObjectType'],
                ['defaultObjectType'],
                ['defaultObjectType'],
                ['defaultObjectType']
            )
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $objectHandler,
            $this->getExtendedAssignmentInformationFactory()
        );

        self::setValue($abstractUserGroup, 'id', 123);
        self::setValue($abstractUserGroup, 'type', 'type');
        self::setValue($abstractUserGroup, 'assignedObjects', [1 => 'post', 2 => 'post']);
        self::setValue($abstractUserGroup, 'objectMembership', [1 => 'role', 2 => 'role']);
        self::setValue($abstractUserGroup, 'fullObjectMembership', [1 => 'post', 2 => 'post']);

        self::assertFalse($abstractUserGroup->addObject('invalid', 321));
        self::assertFalse($abstractUserGroup->addObject('generalObjectType', 321));
        self::assertFalse($abstractUserGroup->addObject('notValidObjectType', 321));
        self::assertFalse($abstractUserGroup->addObject('objectType', 321));
        self::assertTrue($abstractUserGroup->addObject('objectType', 321, 'fromDate', 'toDate'));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType'));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType', 1, 2));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType', 1, 0));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType', 1, 1));

        self::assertAttributeEquals([], 'assignedObjects', $abstractUserGroup);
        self::assertAttributeEquals([], 'objectMembership', $abstractUserGroup);
        self::assertAttributeEquals([], 'fullObjectMembership', $abstractUserGroup);
    }

    /**
     * @group  unit
     * @covers ::delete()
     * @covers ::removeObject()
     * @covers ::resetObjects()
     * @covers ::removeDefaultType()
     */
    public function testDelete()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(5))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(5))
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
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                            WHERE group_id = %d
                              AND group_type = \'%s\'
                              AND object_type = \'%s\'
                              AND object_id = %d'
                    ),
                    [123, 'type', 'defaultObjectType', 'defaultObjectType', '']
                ]
            )
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->exactly(5))
            ->method('query')
            ->with('preparedQuery')
            ->will($this->onConsecutiveCalls(true, false, true, true, true));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->once())
            ->method('getAllObjectTypes')
            ->will($this->returnValue(['objectType']));

        $objectHandler->expects($this->exactly(7))
            ->method('getGeneralObjectType')
            ->withConsecutive(
                ['objectType'],
                ['invalid'],
                ['invalidObjectType'],
                ['objectType'],
                ['objectType'],
                ['objectType'],
                ['defaultObjectType']
            )
            ->will($this->returnCallback(function ($type) {
                return ($type !== 'invalid') ? $type : null;
            }));

        $objectHandler->expects($this->exactly(6))
            ->method('isValidObjectType')
            ->withConsecutive(
                ['objectType'],
                ['invalidObjectType'],
                ['objectType'],
                ['objectType'],
                ['objectType'],
                ['defaultObjectType']
            )
            ->will($this->returnCallback(function ($type) {
                return ($type === 'objectType' || $type === 'defaultObjectType');
            }));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $objectHandler,
            $this->getExtendedAssignmentInformationFactory(),
            123
        );

        self::setValue($abstractUserGroup, 'type', 'type');
        self::setValue($abstractUserGroup, 'assignedObjects', [1 => 1]);
        self::setValue($abstractUserGroup, 'objectMembership', [2 => 2]);
        self::setValue($abstractUserGroup, 'fullObjectMembership', [3 => 3]);

        self::assertTrue($abstractUserGroup->delete());

        self::assertAttributeEquals([], 'assignedObjects', $abstractUserGroup);
        self::assertAttributeEquals([], 'objectMembership', $abstractUserGroup);
        self::assertAttributeEquals([], 'fullObjectMembership', $abstractUserGroup);

        self::setValue($abstractUserGroup, 'assignedObjects', [1 => 1]);
        self::setValue($abstractUserGroup, 'objectMembership', [2 => 2]);
        self::setValue($abstractUserGroup, 'fullObjectMembership', [3 => 3]);

        self::assertFalse($abstractUserGroup->removeObject('invalid'));
        self::assertFalse($abstractUserGroup->removeObject('invalidObjectType'));

        self::assertAttributeEquals([1 => 1], 'assignedObjects', $abstractUserGroup);
        self::assertAttributeEquals([2 => 2], 'objectMembership', $abstractUserGroup);
        self::assertAttributeEquals([3 => 3], 'fullObjectMembership', $abstractUserGroup);

        self::assertFalse($abstractUserGroup->removeObject('objectType'));

        self::assertAttributeEquals([1 => 1], 'assignedObjects', $abstractUserGroup);
        self::assertAttributeEquals([2 => 2], 'objectMembership', $abstractUserGroup);
        self::assertAttributeEquals([3 => 3], 'fullObjectMembership', $abstractUserGroup);

        self::assertTrue($abstractUserGroup->removeObject('objectType'));

        self::assertAttributeEquals([], 'assignedObjects', $abstractUserGroup);
        self::assertAttributeEquals([], 'objectMembership', $abstractUserGroup);
        self::assertAttributeEquals([], 'fullObjectMembership', $abstractUserGroup);

        self::assertTrue($abstractUserGroup->removeObject('objectType', 1));
        self::assertTrue($abstractUserGroup->removeDefaultType('defaultObjectType'));
    }

    /**
     * @group  unit
     * @covers ::setIgnoreDates()
     */
    public function testSetIgnoreDates()
    {
        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );

        self::setValue($abstractUserGroup, 'assignedObjects', ['someValue']);
        $abstractUserGroup->setIgnoreDates(false);
        self::assertAttributeEquals(false, 'ignoreDates', $abstractUserGroup);
        self::assertAttributeEquals(['someValue'], 'assignedObjects', $abstractUserGroup);

        $abstractUserGroup->setIgnoreDates(true);
        self::assertAttributeEquals(true, 'ignoreDates', $abstractUserGroup);
        self::assertAttributeEquals([], 'assignedObjects', $abstractUserGroup);

        $abstractUserGroup->setIgnoreDates(false);
        self::assertAttributeEquals(false, 'ignoreDates', $abstractUserGroup);
        self::assertAttributeEquals([], 'assignedObjects', $abstractUserGroup);
    }

    /**
     * Generates return values.
     *
     * @param int    $number
     * @param string $type
     * @param string $fromDate
     * @param string $toDate
     *
     * @return array
     */
    private function generateReturn($number, $type, $fromDate = null, $toDate = null)
    {
        $returns = [];

        for ($counter = 1; $counter <= $number; $counter++) {
            $return = new \stdClass();
            $return->id = $counter;
            $return->objectType = $type;
            $return->fromDate = $fromDate;
            $return->toDate = $toDate;
            $returns[] = $return;
        }

        return $returns;
    }

    /**
     * @group  unit
     * @covers ::getAssignedObjects()
     * @covers  ::isObjectAssignedToGroup()
     */
    public function testAssignedObject()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(3))
            ->method('currentTime')
            ->with('mysql')
            ->will($this->returnValue('time'));

        $database = $this->getDatabase();

        $database->expects($this->exactly(4))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $queryWithoutDates = 'SELECT object_id AS id,
              object_type AS objectType,
              from_date AS fromDate,
              to_date AS toDate
            FROM userGroupToObjectTable
            WHERE group_id = \'%s\'
              AND group_type = \'%s\'
              AND object_id != \'\'
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $query = $queryWithoutDates.' AND (from_date IS NULL OR from_date <= \'%s\')
              AND (to_date IS NULL OR to_date >= \'%s\')';

        $database->expects($this->exactly(4))
            ->method('prepare')
            ->withConsecutive(
                [
                    new MatchIgnoreWhitespace($query),
                    [123, null, 'noResultObjectType', 'noResultObjectType', 'time', 'time']
                ],
                [new MatchIgnoreWhitespace($query), [123, null, 'objectType', 'objectType', 'time', 'time']],
                [new MatchIgnoreWhitespace($query), [123, null, 'something', 'something', 'time', 'time']],
                [new MatchIgnoreWhitespace($queryWithoutDates), [123, null, 'objectType', 'objectType']]
            )
            ->will($this->onConsecutiveCalls(
                'nonResultPreparedQuery',
                'preparedQuery',
                'nonResultSomethingPreparedQuery',
                'nonResultPreparedQuery'
            ));

        $database->expects($this->exactly(4))
            ->method('getResults')
            ->withConsecutive(
                ['nonResultPreparedQuery'],
                ['preparedQuery'],
                ['nonResultSomethingPreparedQuery'],
                ['nonResultPreparedQuery']
            )
            ->will($this->onConsecutiveCalls(null, $this->generateReturn(3, 'objectType'), null));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $wordpress,
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );

        self::setValue($abstractUserGroup, 'id', 123);

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['noResultObjectType']);
        self::assertEquals([], $result);
        self::assertAttributeEquals(['noResultObjectType' => []], 'assignedObjects', $abstractUserGroup);

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals(
            [
                1 => $this->getAssignmentInformation('objectType'),
                2 => $this->getAssignmentInformation('objectType'),
                3 => $this->getAssignmentInformation('objectType')
            ],
            $result
        );
        self::assertAttributeEquals(
            [
                'noResultObjectType' => [],
                'objectType' => [
                    1 => $this->getAssignmentInformation('objectType'),
                    2 => $this->getAssignmentInformation('objectType'),
                    3 => $this->getAssignmentInformation('objectType')
                ]
            ],
            'assignedObjects',
            $abstractUserGroup
        );

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals(
            [
                1 => $this->getAssignmentInformation('objectType'),
                2 => $this->getAssignmentInformation('objectType'),
                3 => $this->getAssignmentInformation('objectType')
            ],
            $result
        );

        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 1]);
        self::assertTrue($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 2]);
        self::assertTrue($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 3]);
        self::assertTrue($result);

        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 4]);
        self::assertFalse($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['noResultObjectType', 1]);
        self::assertFalse($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['something', 1]);
        self::assertFalse($result);

        $abstractUserGroup->setIgnoreDates(true);
        $result = $abstractUserGroup->getAssignedObjects('objectType');
        self::assertEquals([], $result);
    }

    /**
     * @group  unit
     * @covers ::getDefaultGroupForObjectTypes()
     *
     * @return AbstractUserGroup
     */
    public function testGetDefaultGroupForObjectTypes()
    {
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('prepare')
            ->with(
                new MatchIgnoreWhitespace(
                    'SELECT object_type AS objectType, from_date AS fromDate, to_date AS toDate
                    FROM userGroupToObjectTable
                    WHERE group_id = \'%s\'
                      AND group_type = \'%s\'
                      AND object_id = \'\''
                ),
                ['groupId', 'groupType']
            )
            ->will($this->returnValue('preparedQuery'));

        $database->expects($this->once())
            ->method('getResults')
            ->with('preparedQuery')
            ->will($this->returnValue(array_merge(
                $this->generateReturn(2, 'typeOne'),
                $this->generateReturn(1, 'typeTwo', '01-01-1970 00:00:10', '01-01-1970 00:00:20'),
                $this->generateReturn(1, 'typeThree', null, '01-01-1970 00:01:00'),
                $this->generateReturn(1, 'typeFour', '01-01-1970 00:02:00', null)
            )));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );

        self::setValue($abstractUserGroup, 'id', 'groupId');
        self::setValue($abstractUserGroup, 'type', 'groupType');

        $expected = [
            'typeOne' => [null, null],
            'typeTwo' => [10, 20],
            'typeThree' => [null, 60],
            'typeFour' => [120, null]
        ];

        self::assertEquals($expected, $abstractUserGroup->getDefaultGroupForObjectTypes());
        self::assertEquals($expected, $abstractUserGroup->getDefaultGroupForObjectTypes());

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @depends testGetDefaultGroupForObjectTypes
     * @covers ::isDefaultGroupForObjectType()
     *
     * @param AbstractUserGroup $abstractUserGroup
     */
    public function testIsDefaultGroupForObjectType(AbstractUserGroup $abstractUserGroup)
    {
        self::assertTrue($abstractUserGroup->isDefaultGroupForObjectType('typeOne', $fromTime, $toTime));
        self::assertEmpty($fromTime);
        self::assertEmpty($toTime);

        self::assertTrue($abstractUserGroup->isDefaultGroupForObjectType('typeTwo', $fromTime, $toTime));
        self::assertEquals(10, $fromTime);
        self::assertEquals(20, $toTime);

        self::assertTrue($abstractUserGroup->isDefaultGroupForObjectType('typeThree', $fromTime, $toTime));
        self::assertEquals(null, $fromTime);
        self::assertEquals(60, $toTime);

        self::assertTrue($abstractUserGroup->isDefaultGroupForObjectType('typeFour', $fromTime, $toTime));
        self::assertEquals(120, $fromTime);
        self::assertEquals(null, $toTime);

        self::assertFalse($abstractUserGroup->isDefaultGroupForObjectType('someType', $fromTime, $toTime));
        self::assertEmpty($fromTime);
        self::assertEmpty($toTime);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    private function getMembershipObjectHandler()
    {
        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getGeneralObjectType')
            ->will($this->returnCallback(function ($objectType) {
                if ($objectType === 'role' || $objectType === 'roleOther') {
                    return ObjectHandler::GENERAL_ROLE_OBJECT_TYPE;
                } elseif ($objectType === 'user' || $objectType === 'userOther') {
                    return ObjectHandler::GENERAL_USER_OBJECT_TYPE;
                } elseif ($objectType === 'term' || $objectType === 'termOther') {
                    return ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
                } elseif ($objectType === 'post' || $objectType === 'postOther') {
                    return ObjectHandler::GENERAL_POST_OBJECT_TYPE;
                }

                return $objectType;
            }));

        $roleMembershipHandler = $this->getMembershipHandler(RoleMembershipHandler::class, 'role', [2]);
        $userMembershipHandler = $this->getMembershipHandler(UserMembershipHandler::class, 'user', [2]);
        $postMembershipHandler = $this->getMembershipHandler(PostMembershipHandler::class, 'post', [2]);
        $termMembershipHandler = $this->getMembershipHandler(TermMembershipHandler::class, 'term', [2]);
        $someObjectHandler = $this->getMembershipHandler(ObjectMembershipHandler::class, 'someObject', [2]);

        $objectHandler->expects($this->any())
            ->method('getObjectMembershipHandler')
            ->will($this->returnCallback(
                function ($objectType) use (
                    $roleMembershipHandler,
                    $userMembershipHandler,
                    $postMembershipHandler,
                    $termMembershipHandler,
                    $someObjectHandler
                ) {
                    if ($objectType === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                        || $objectType === 'role'
                        || $objectType === 'roleOther'
                    ) {
                        return $roleMembershipHandler;
                    } elseif ($objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE
                        || $objectType === 'user'
                        || $objectType === 'userOther'
                    ) {
                        return $userMembershipHandler;
                    } elseif ($objectType === ObjectHandler::GENERAL_TERM_OBJECT_TYPE
                        || $objectType === 'term'
                        || $objectType === 'termOther'
                    ) {
                        return $termMembershipHandler;
                    } elseif ($objectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE
                        || $objectType === 'post'
                        || $objectType === 'postOther'
                    ) {
                        return $postMembershipHandler;
                    } elseif ($objectType === 'someObject') {
                        return $someObjectHandler;
                    }

                    throw new MissingObjectMembershipHandlerException('MissingObjectMembershipHandler');
                }
            ));

        return $objectHandler;
    }

    /**
     * Assertion helper for testIsMemberFunctions
     *
     * @param AbstractUserGroup $abstractUserGroup
     * @param string            $extraFunction
     * @param string            $objectType
     * @param string            $objectId
     * @param bool              $expectedReturn
     * @param string            $object
     * @param string            $fromDate
     * @param string            $toDate
     * @param array             $expectedRecursiveMembership
     */
    private function memberFunctionAssertions(
        AbstractUserGroup $abstractUserGroup,
        $extraFunction,
        $objectType,
        $objectId,
        $expectedReturn,
        $object = '',
        $fromDate = null,
        $toDate = null,
        array $expectedRecursiveMembership = []
    ) {
        if ($expectedReturn === true || count($expectedRecursiveMembership)) {
            $expectedAssignmentInformation = $this->getAssignmentInformation(
                $object,
                $fromDate,
                $toDate,
                $expectedRecursiveMembership
            );
        } else {
            $expectedAssignmentInformation = null;
        }

        $return = $abstractUserGroup->isObjectMember($objectType, $objectId, $assignmentInformation);

        self::assertEquals($expectedReturn, $return);
        self::assertEquals($expectedAssignmentInformation, $assignmentInformation);

        self::assertEquals(
            $expectedRecursiveMembership,
            $abstractUserGroup->getRecursiveMembershipForObject(
                $objectType,
                $objectId
            )
        );

        self::assertEquals(
            count($expectedRecursiveMembership) > 0,
            $abstractUserGroup->isLockedRecursive($objectType, $objectId)
        );

        if ($extraFunction !== null) {
            $abstractUserGroup->{$extraFunction}($objectId, $assignmentInformation);

            self::assertEquals($expectedReturn, $return);
            self::assertEquals($expectedAssignmentInformation, $assignmentInformation);

            self::assertEquals(
                $expectedRecursiveMembership,
                $abstractUserGroup->getRecursiveMembershipForObject(
                    $objectType,
                    $objectId
                )
            );

            self::assertEquals(
                count($expectedRecursiveMembership) > 0,
                $abstractUserGroup->isLockedRecursive($objectType, $objectId)
            );
        }
    }

    /**
     * @group   unit
     * @covers  ::isObjectMember()
     * @covers  ::isRoleMember()
     * @covers  ::isUserMember()
     * @covers  ::isTermMember()
     * @covers  ::isPostMember()
     * @covers  ::getRecursiveMembershipForObject()
     * @covers  ::isLockedRecursive()
     */
    public function testIsMemberFunctions()
    {
        $lockRecursive = false;

        $config = $this->getMainConfig();
        $config->expects($this->any())
            ->method('lockRecursive')
            ->will($this->returnCallback(function () use (&$lockRecursive) {
                return $lockRecursive;
            }));

        $userGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $config,
            $this->getUtil(),
            $this->getMembershipObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );

        $this->memberFunctionAssertions($userGroup, 'isRoleMember', 'role', 1, true, 'role', 'fromDate', 'toDate');
        $this->memberFunctionAssertions($userGroup, 'isRoleMember', 'role', 3, true, 'role', 'fromDate', 'toDate');

        self::assertAttributeEquals(
            [
                'role' => [
                    1 => $this->getAssignmentInformation('role', 'fromDate', 'toDate'),
                    3 => $this->getAssignmentInformation('role', 'fromDate', 'toDate')
                ],
                '_role_' => [
                    1 => $this->getAssignmentInformation('role', 'fromDate', 'toDate'),
                    3 => $this->getAssignmentInformation('role', 'fromDate', 'toDate')
                ]
            ],
            'objectMembership',
            $userGroup
        );

        $objectTypes = [
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => 'isRoleMember',
            'role' => 'isRoleMember',
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => 'isUserMember',
            'user' => 'isUserMember',
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => 'isTermMember',
            'term' => 'isTermMember',
            ObjectHandler::GENERAL_POST_OBJECT_TYPE => 'isPostMember',
            'post' => 'isPostMember',
            'someObject' => null
        ];

        foreach ($objectTypes as $objectType => $extraFunction) {
            $generalObjectType = str_replace('_', '', $objectType);

            /** @noinspection PhpUnusedLocalVariableInspection */
            $lockRecursive = false;
            self::setValue($userGroup, 'objectMembership', []);

            $this->memberFunctionAssertions(
                $userGroup,
                $extraFunction,
                $objectType,
                1,
                true,
                $generalObjectType,
                'fromDate',
                'toDate'
            );
            $this->memberFunctionAssertions($userGroup, $extraFunction, $objectType, 2, false);

            /** @noinspection PhpUnusedLocalVariableInspection */
            $lockRecursive = true;
            self::setValue($userGroup, 'objectMembership', []);
            
            $this->memberFunctionAssertions(
                $userGroup,
                $extraFunction,
                $objectType,
                1,
                true,
                $generalObjectType,
                'fromDate',
                'toDate',
                [
                    $this->getAssignmentInformation($generalObjectType, 'recursiveFromDate', 'recursiveToDate')
                ]
            );
            $this->memberFunctionAssertions($userGroup, $extraFunction, $objectType, 2, false);
        }

        self::assertFalse($userGroup->isObjectMember('someInvalidType', 'someId', $assignmentInformation));
        self::assertNull($assignmentInformation);
    }

    /**
     * @group  unit
     * @covers ::getAssignedObjectsByType()
     * @covers ::getFullRoles()
     * @covers ::getFullUsers()
     * @covers ::getFullTerms()
     * @covers ::getFullPosts()
     */
    public function testGetAssignedObjectsByType()
    {
        $lockRecursive = false;

        $config = $this->getMainConfig();
        $config->expects($this->any())
            ->method('lockRecursive')
            ->will($this->returnCallback(function () use (&$lockRecursive) {
                return $lockRecursive;
            }));

        $userGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $config,
            $this->getUtil(),
            $this->getMembershipObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );

        $objectTypes = [
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => 'getFullRoles',
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => 'getFullUsers',
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE => 'getFullTerms',
            ObjectHandler::GENERAL_POST_OBJECT_TYPE => 'getFullPosts',
            'someObject' => null
        ];

        foreach ($objectTypes as $objectType => $extraFunction) {
            $generalObjectType = str_replace('_', '', $objectType);
            $otherType = $generalObjectType.'Other';

            /** @noinspection PhpUnusedLocalVariableInspection */
            $lockRecursive = false;
            self::setValue($userGroup, 'fullObjectMembership', []);

            self::assertEquals(
                [1 => $generalObjectType, 100 => $otherType],
                $userGroup->getAssignedObjectsByType($objectType)
            );

            if ($extraFunction !== null) {
                self::assertEquals(
                    [1 => $generalObjectType, 100 => $otherType],
                    $userGroup->{$extraFunction}()
                );
            }

            /** @noinspection PhpUnusedLocalVariableInspection */
            $lockRecursive = true;
            self::setValue($userGroup, 'fullObjectMembership', []);

            self::assertEquals(
                [1 => $generalObjectType, 3 => $generalObjectType, 100 => $otherType, 101 => $otherType],
                $userGroup->getAssignedObjectsByType($objectType)
            );

            if ($extraFunction !== null) {
                self::assertEquals(
                    [1 => $generalObjectType, 3 => $generalObjectType, 100 => $otherType, 101 => $otherType],
                    $userGroup->{$extraFunction}()
                );
            }
        }

        self::assertEquals([], $userGroup->getAssignedObjectsByType('someInvalidType'));
    }
}
