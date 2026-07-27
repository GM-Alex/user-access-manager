<?php
/**
 * UserGroupFactoryTest.php
 *
 * The UserGroupFactoryTest unit test class file.
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

use stdClass;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\UserGroup\UserGroupTypeException;

/**
 * Class UserGroupFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\UserGroupFactory
 */
class UserGroupFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @return UserGroupFactory
     */
    public function testCanCreateInstance(): UserGroupFactory
    {
        $userGroupFactory = new UserGroupFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getAssignedObjectsLoader()
        );

        self::assertInstanceOf(UserGroupFactory::class, $userGroupFactory);

        return $userGroupFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createUserGroup()
     * @param UserGroupFactory $userGroupFactory
     * @throws UserGroupTypeException
     */
    public function testCreateUserGroup(UserGroupFactory $userGroupFactory)
    {
        self::assertInstanceOf(UserGroup::class, $userGroupFactory->createUserGroup());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createUserGroupFromDatabaseRow()
     * @param UserGroupFactory $userGroupFactory
     * @throws UserGroupTypeException
     */
    public function testCreateUserGroupFromDatabaseRow(UserGroupFactory $userGroupFactory)
    {
        $databaseUserGroup = new stdClass();
        $databaseUserGroup->ID = 3;
        $databaseUserGroup->groupname = 'groupName';
        $databaseUserGroup->groupdesc = 'groupDesc';
        $databaseUserGroup->read_access = 'readAccess';
        $databaseUserGroup->write_access = 'writeAccess';
        $databaseUserGroup->ip_range = 'ipRange';

        $userGroup = $userGroupFactory->createUserGroupFromDatabaseRow($databaseUserGroup);

        self::assertInstanceOf(UserGroup::class, $userGroup);
        self::assertEquals(3, $userGroup->getId());
        self::assertEquals('groupName', $userGroup->getName());
        self::assertEquals('groupDesc', $userGroup->getDescription());
        self::assertEquals('readAccess', $userGroup->getReadAccess());
        self::assertEquals('writeAccess', $userGroup->getWriteAccess());
        self::assertEquals('ipRange', $userGroup->getIpRange());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createDynamicUserGroup()
     * @param UserGroupFactory $userGroupFactory
     * @throws UserGroupTypeException
     */
    public function testCreateDynamicUserGroup(UserGroupFactory $userGroupFactory)
    {
        $dynamicUserGroup = $userGroupFactory->createDynamicUserGroup('user', 'id');
        self::assertInstanceOf(DynamicUserGroup::class, $dynamicUserGroup);

        self::assertEquals('user|id', $dynamicUserGroup->getId());
        self::assertEquals('user', $dynamicUserGroup->getType());
    }
}
