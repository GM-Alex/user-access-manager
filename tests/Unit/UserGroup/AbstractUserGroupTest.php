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

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\Exception\MissingObjectMembershipHandlerException;
use UserAccessManager\ObjectMembership\ObjectMembershipHandler;
use UserAccessManager\ObjectMembership\Type\PostMembershipHandler;
use UserAccessManager\ObjectMembership\Type\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\Type\TermMembershipHandler;
use UserAccessManager\ObjectMembership\Type\UserMembershipHandler;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignedObjectsLoader;
use UserAccessManager\UserGroup\UserGroupTypeException;
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
     * @return MockObject|AbstractUserGroup
     * @throws ReflectionException
     */
    private function getStub(
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        ObjectHandler $objectHandler,
        AssignedObjectsLoader $assignedObjectsLoader,
        ?string $id = null
    ): MockObject|AbstractUserGroup {
        $stub = $this->getMockForAbstractClass(
            AbstractUserGroup::class,
            [],
            '',
            false
        );

        self::setValue($stub, 'wordpress', $wordpress);
        self::setValue($stub, 'database', $database);
        self::setValue($stub, 'config', $config);
        self::setValue($stub, 'objectHandler', $objectHandler);
        self::setValue($stub, 'assignedObjectsLoader', $assignedObjectsLoader);
        self::setValue($stub, 'id', $id);

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testCanCreateInstance()
    {
        $abstractUserGroup = $this->getStub(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getObjectHandler(),
            $this->getAssignedObjectsLoader()
        );

        self::setValue($abstractUserGroup, 'type', 'type');
        $abstractUserGroup->__construct(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getObjectHandler(),
            $this->getAssignedObjectsLoader()
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
                $this->getWordpress(),
                $this->getDatabase(),
                $this->getMainConfig(),
                $this->getObjectHandler(),
                $this->getAssignedObjectsLoader()
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
     * @throws ReflectionException
     */
    public function testSimpleGetterSetter()
    {
        $abstractUserGroup = $this->getStub(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getObjectHandler(),
            $this->getAssignedObjectsLoader(),
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
        self::assertEquals('groupNameNew', $abstractUserGroup->getName());

        $abstractUserGroup->setDescription('groupDescNew');
        self::assertEquals('groupDescNew', $abstractUserGroup->getDescription());

        $abstractUserGroup->setReadAccess('readAccessNew');
        self::assertEquals('readAccessNew', $abstractUserGroup->getReadAccess());

        $abstractUserGroup->setWriteAccess('writeAccessNew');
        self::assertEquals('writeAccessNew', $abstractUserGroup->getWriteAccess());
    }

    /**
     * @group  unit
     * @covers ::addObject()
     * @covers ::resetObjects()
     * @covers ::resetObjectsAfterAssignmentChange()
     * @covers ::addDefaultType()
     * @throws Exception
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
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true));

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
            ->will($this->onConsecutiveCalls(false, true, true, true, true, true, true));

        $assignedObjectsLoader = $this->getAssignedObjectsLoader();

        // Every successful assignment must invalidate the objects loaded for all user groups.
        $assignedObjectsLoader->expects($this->exactly(5))
            ->method('flush');

        $abstractUserGroup = $this->getStub(
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $objectHandler,
            $assignedObjectsLoader
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
        self::assertEquals([], self::getValue($abstractUserGroup, 'assignedObjects'));
        self::assertEquals([], self::getValue($abstractUserGroup, 'objectMembership'));
        self::assertEquals([], self::getValue($abstractUserGroup, 'fullObjectMembership'));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType'));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType', 1, 2));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType', 1, 0));
        self::assertTrue($abstractUserGroup->addDefaultType('defaultObjectType', 1, 1));
    }

    /**
     * @group  unit
     * @covers ::delete()
     * @covers ::removeObject()
     * @covers ::resetObjects()
     * @covers ::resetObjectsAfterAssignmentChange()
     * @covers ::removeDefaultType()
     * @throws Exception
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
                          AND (object_type = \'%s\' OR general_object_type = \'%s\')'
                    ),
                    [123, 'type', 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                        WHERE group_id = %d
                          AND group_type = \'%s\'
                          AND (object_type = \'%s\' OR general_object_type = \'%s\')'
                    ),
                    [123, 'type', 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                            WHERE group_id = %d
                              AND group_type = \'%s\'
                              AND (object_type = \'%s\' OR general_object_type = \'%s\')'
                    ),
                    [123, 'type', 'objectType', 'objectType']
                ],
                [
                    new MatchIgnoreWhitespace(
                        'DELETE FROM userGroupToObjectTable
                            WHERE group_id = %d
                              AND group_type = \'%s\'
                              AND (object_type = \'%s\' OR general_object_type = \'%s\')
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
                    [123, 'type', 'defaultObjectType', '']
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

        $assignedObjectsLoader = $this->getAssignedObjectsLoader();

        // Every successful removal must invalidate the objects loaded for all user groups.
        $assignedObjectsLoader->expects($this->exactly(4))
            ->method('flush');

        $abstractUserGroup = $this->getStub(
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $objectHandler,
            $assignedObjectsLoader,
            123
        );

        self::setValue($abstractUserGroup, 'type', 'type');
        self::setValue($abstractUserGroup, 'assignedObjects', [1 => 1]);
        self::setValue($abstractUserGroup, 'objectMembership', [2 => 2]);
        self::setValue($abstractUserGroup, 'fullObjectMembership', [3 => 3]);

        self::assertTrue($abstractUserGroup->delete());

        self::setValue($abstractUserGroup, 'assignedObjects', [1 => 1]);
        self::setValue($abstractUserGroup, 'objectMembership', [2 => 2]);
        self::setValue($abstractUserGroup, 'fullObjectMembership', [3 => 3]);

        self::assertFalse($abstractUserGroup->removeObject('invalid'));
        self::assertFalse($abstractUserGroup->removeObject('invalidObjectType'));

        self::assertFalse($abstractUserGroup->removeObject('objectType'));
        // A failed removal must not reset the cached objects.
        self::assertEquals([1 => 1], self::getValue($abstractUserGroup, 'assignedObjects'));

        self::assertTrue($abstractUserGroup->removeObject('objectType'));
        // A successful removal resets the cached objects.
        self::assertEquals([], self::getValue($abstractUserGroup, 'assignedObjects'));

        self::assertTrue($abstractUserGroup->removeObject('objectType', 1));
        self::assertTrue($abstractUserGroup->removeDefaultType('defaultObjectType'));
    }

    /**
     * @group  unit
     * @covers ::setIgnoreDates()
     * @covers ::getIgnoreDates()
     * @throws ReflectionException
     */
    public function testSetIgnoreDates()
    {
        $abstractUserGroup = $this->getStub(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getObjectHandler(),
            $this->getAssignedObjectsLoader()
        );

        self::setValue($abstractUserGroup, 'assignedObjects', ['someValue']);
        $abstractUserGroup->setIgnoreDates(false);
        self::assertFalse($abstractUserGroup->getIgnoreDates());

        $abstractUserGroup->setIgnoreDates(true);
        self::assertTrue($abstractUserGroup->getIgnoreDates());

        $abstractUserGroup->setIgnoreDates(false);
        self::assertFalse($abstractUserGroup->getIgnoreDates());
    }

    /**
     * Generates return values.
     * @param int $number
     * @param string $type
     * @param null $fromDate
     * @param null $toDate
     * @return array
     */
    private function generateReturn(int $number, string $type, $fromDate = null, $toDate = null): array
    {
        $returns = [];

        for ($counter = 1; $counter <= $number; $counter++) {
            $return = new stdClass();
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
     * @throws ReflectionException
     */
    public function testAssignedObject()
    {
        $assignedObjects = [
            1 => $this->getAssignmentInformation('objectType'),
            2 => $this->getAssignmentInformation('objectType'),
            3 => $this->getAssignmentInformation('objectType')
        ];

        $assignedObjectsLoader = $this->getAssignedObjectsLoader();

        $assignedObjectsLoader->expects($this->exactly(4))
            ->method('getAssignedObjects')
            ->withConsecutive(
                ['type', 123, 'noResultObjectType', false],
                ['type', 123, 'objectType', false],
                ['type', 123, 'something', false],
                ['type', 123, 'objectType', true]
            )
            ->will($this->onConsecutiveCalls([], $assignedObjects, [], []));

        $abstractUserGroup = $this->getStub(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getObjectHandler(),
            $assignedObjectsLoader
        );

        self::setValue($abstractUserGroup, 'id', 123);
        self::setValue($abstractUserGroup, 'type', 'type');

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['noResultObjectType']);
        self::assertEquals([], $result);

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals($assignedObjects, $result);

        // The already loaded objects must not be requested from the loader again.
        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals($assignedObjects, $result);

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
     * @return AbstractUserGroup
     * @throws ReflectionException
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
                $this->generateReturn(1, 'typeFour', '01-01-1970 00:02:00')
            )));

        $abstractUserGroup = $this->getStub(
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getObjectHandler(),
            $this->getAssignedObjectsLoader()
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
     * @return MockObject|ObjectHandler
     */
    private function getMembershipObjectHandler(): ObjectHandler|MockObject
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
     * @throws Exception
     */
    private function memberFunctionAssertions(
        AbstractUserGroup $abstractUserGroup,
        ?string $extraFunction,
        string $objectType,
        string $objectId,
        bool $expectedReturn,
        string $object = '',
        ?string $fromDate = null,
        ?string $toDate = null,
        array $expectedRecursiveMembership = []
    ): void {
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
     * @throws Exception
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
            $this->getWordpress(),
            $this->getDatabase(),
            $config,
            $this->getMembershipObjectHandler(),
            $this->getAssignedObjectsLoader()
        );

        $this->memberFunctionAssertions($userGroup, 'isRoleMember', 'role', 1, true, 'role', 'fromDate', 'toDate');
        $this->memberFunctionAssertions($userGroup, 'isRoleMember', 'role', 3, true, 'role', 'fromDate', 'toDate');

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
     * @throws Exception
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
            $this->getWordpress(),
            $this->getDatabase(),
            $config,
            $this->getMembershipObjectHandler(),
            $this->getAssignedObjectsLoader()
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
            $otherType = $generalObjectType . 'Other';

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
