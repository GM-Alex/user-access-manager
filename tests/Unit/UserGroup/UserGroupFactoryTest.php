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
            $this->getAssignmentInformationFactory()
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
