<?php
/**
 * ObjectMembershipHandlerFactoryTest.php
 *
 * The ObjectMembershipHandlerFactoryTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\UserGroup\ObjectMembership;

use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\UserGroup\ObjectMembership\PostMembershipHandler;
use UserAccessManager\UserGroup\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\UserGroup\ObjectMembership\TermMembershipHandler;
use UserAccessManager\UserGroup\ObjectMembership\UserMembershipHandler;

/**
 * Class ObjectMembershipHandlerFactoryTest
 *
 * @package UserAccessManager\Tests\UserGroup\ObjectMembership
 * @coversDefaultClass \UserAccessManager\UserGroup\ObjectMembership\ObjectMembershipHandlerFactory
 */
class ObjectMembershipHandlerFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     *
     * @return ObjectMembershipHandlerFactory
     */
    public function testCanCreateInstance()
    {
        $objectMembershipHandlerFactory = new ObjectMembershipHandlerFactory(
            $this->getPhp(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );
        self::assertInstanceOf(ObjectMembershipHandlerFactory::class, $objectMembershipHandlerFactory);

        return $objectMembershipHandlerFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createRoleMembershipHandler()
     *
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     */
    public function testCreateRoleMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            RoleMembershipHandler::class,
            $objectMembershipHandlerFactory->createRoleMembershipHandler($this->getUserGroup(1))
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createPostMembershipHandler()
     *
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     */
    public function testCreatePostMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            PostMembershipHandler::class,
            $objectMembershipHandlerFactory->createPostMembershipHandler($this->getUserGroup(1))
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createTermMembershipHandler()
     *
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     */
    public function testCreateTermMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            TermMembershipHandler::class,
            $objectMembershipHandlerFactory->createTermMembershipHandler($this->getUserGroup(1))
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createUserMembershipHandler()
     *
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     */
    public function testCreateUserMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            UserMembershipHandler::class,
            $objectMembershipHandlerFactory->createUserMembershipHandler($this->getUserGroup(1))
        );
    }
}
