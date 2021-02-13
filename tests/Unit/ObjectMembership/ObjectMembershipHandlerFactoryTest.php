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

namespace UserAccessManager\Tests\Unit\ObjectMembership;

use Exception;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\ObjectMembership\PostMembershipHandler;
use UserAccessManager\ObjectMembership\RoleMembershipHandler;
use UserAccessManager\ObjectMembership\TermMembershipHandler;
use UserAccessManager\ObjectMembership\UserMembershipHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class ObjectMembershipHandlerFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\ObjectMembership
 * @coversDefaultClass \UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory
 */
class ObjectMembershipHandlerFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @return ObjectMembershipHandlerFactory
     */
    public function testCanCreateInstance(): ObjectMembershipHandlerFactory
    {
        $objectMembershipHandlerFactory = new ObjectMembershipHandlerFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getObjectMapHandler(),
            $this->getExtendedAssignmentInformationFactory()
        );
        self::assertInstanceOf(ObjectMembershipHandlerFactory::class, $objectMembershipHandlerFactory);

        return $objectMembershipHandlerFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createRoleMembershipHandler()
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     * @throws Exception
     */
    public function testCreateRoleMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            RoleMembershipHandler::class,
            $objectMembershipHandlerFactory->createRoleMembershipHandler()
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createPostMembershipHandler()
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     * @throws Exception
     */
    public function testCreatePostMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            PostMembershipHandler::class,
            $objectMembershipHandlerFactory->createPostMembershipHandler($this->getObjectHandler())
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createTermMembershipHandler()
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     * @throws Exception
     */
    public function testCreateTermMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            TermMembershipHandler::class,
            $objectMembershipHandlerFactory->createTermMembershipHandler($this->getObjectHandler())
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createUserMembershipHandler()
     * @param ObjectMembershipHandlerFactory $objectMembershipHandlerFactory
     * @throws Exception
     */
    public function testCreateUserMembershipHandler(ObjectMembershipHandlerFactory $objectMembershipHandlerFactory)
    {
        self::assertInstanceOf(
            UserMembershipHandler::class,
            $objectMembershipHandlerFactory->createUserMembershipHandler($this->getObjectHandler())
        );
    }
}
