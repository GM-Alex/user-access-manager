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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class UserGroupFactoryTest
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroupFactory::__construct()
     *
     * @return UserGroupFactory
     */
    public function testCanCreateInstance()
    {
        $UserGroupFactory = new UserGroupFactory(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroupFactory', $UserGroupFactory);

        return $UserGroupFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\UserGroup\UserGroupFactory::createUserGroup()
     *
     * @param UserGroupFactory $UserGroupFactory
     */
    public function testCreateUserGroup(UserGroupFactory $UserGroupFactory)
    {
        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $UserGroupFactory->createUserGroup());
    }
}
