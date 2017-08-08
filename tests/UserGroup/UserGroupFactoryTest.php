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
namespace UserAccessManager\Tests\UserGroup;

use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupFactory;

/**
 * Class UserGroupFactoryTest
 *
 * @package UserAccessManager\UserGroup
 * @coversDefaultClass \UserAccessManager\UserGroup\UserGroupFactory
 */
class UserGroupFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     *
     * @return UserGroupFactory
     */
    public function testCanCreateInstance()
    {
        $userGroupFactory = new UserGroupFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getObjectMembershipHandlerFactory(),
            $this->getAssignmentInformationFactory()
        );

        self::assertInstanceOf(UserGroupFactory::class, $userGroupFactory);

        return $userGroupFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createUserGroup()
     *
     * @param UserGroupFactory $userGroupFactory
     */
    public function testCreateUserGroup(UserGroupFactory $userGroupFactory)
    {
        self::assertInstanceOf(UserGroup::class, $userGroupFactory->createUserGroup());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createDynamicUserGroup()
     *
     * @param UserGroupFactory $userGroupFactory
     */
    public function testCreateDynamicUserGroup(UserGroupFactory $userGroupFactory)
    {
        $dynamicUserGroup = $userGroupFactory->createDynamicUserGroup('user', 'id');
        self::assertInstanceOf(DynamicUserGroup::class, $dynamicUserGroup);

        self::assertAttributeEquals('id', 'id', $dynamicUserGroup);
        self::assertAttributeEquals('user', 'type', $dynamicUserGroup);
    }
}
