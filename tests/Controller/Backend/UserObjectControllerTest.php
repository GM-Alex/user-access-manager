<?php
/**
 * UserObjectControllerTest.php
 *
 * The UserObjectControllerTest class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Controller\Backend;

use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Controller\Backend\UserObjectController;
use UserAccessManager\ObjectHandler\ObjectHandler;

/**
 * Class UserObjectControllerTest
 *
 * @package UserAccessManager\Tests\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\UserObjectController
 */
class UserObjectControllerTest extends ObjectControllerTestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $userObjectController = new UserObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf(UserObjectController::class, $userObjectController);
    }

    /**
     * @group  unit
     * @covers ::addUserColumnsHeader()
     */
    public function testAddUserColumnsHeader()
    {
        $userObjectController = new UserObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertEquals(
            ['b' => 'b', ObjectController::COLUMN_NAME => TXT_UAM_COLUMN_USER_GROUPS],
            $userObjectController->addUserColumnsHeader(['b' => 'b'])
        );
    }

    /**
     * @group  unit
     * @covers ::addUserColumn()
     */
    public function testAddUserColumn()
    {
        $userObjectController = new UserObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        $userObjectController->addUserColumn('return', ObjectController::COLUMN_NAME, 1);
    }

    /**
     * @group  unit
     * @covers ::addUserColumn()
     * @covers ::showUserProfile()
     */
    public function testEditForm()
    {
        /**
         * @var UserObjectController $userObjectController
         */
        $userObjectController = $this->getTestEditFormPrototype(
            UserObjectController::class,
            [
                'vfs://src/View/UserColumn.php',
                'vfs://src/View/UserProfileEditForm.php',
                'vfs://src/View/UserProfileEditForm.php'
            ],
            [
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4]
            ]
        );

        self::assertEquals('return', $userObjectController->addUserColumn('return', 'invalid', 1));
        $this->resetControllerObjectInformation($userObjectController);

        self::assertEquals('return', $userObjectController->addUserColumn('return', 'invalid', 1));
        $this->resetControllerObjectInformation($userObjectController);

        $expected = 'return!UserAccessManager\Controller\Backend\UserObjectController|'
            .'vfs://src/View/UserColumn.php|uam_user_groups!';
        self::assertEquals(
            $expected,
            $userObjectController->addUserColumn('return', ObjectController::COLUMN_NAME, 1)
        );
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $userObjectController);
        self::assertAttributeEquals(1, 'objectId', $userObjectController);
        $this->resetControllerObjectInformation($userObjectController);

        $userObjectController->showUserProfile();
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $userObjectController);
        self::assertAttributeEquals(null, 'objectId', $userObjectController);
        $expectedOutput = '!UserAccessManager\Controller\Backend\UserObjectController|'
            .'vfs://src/View/UserProfileEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($userObjectController);

        $_GET['user_id'] = 4;
        $userObjectController->showUserProfile();
        self::assertAttributeEquals(ObjectHandler::GENERAL_USER_OBJECT_TYPE, 'objectType', $userObjectController);
        self::assertAttributeEquals(4, 'objectId', $userObjectController);
        $expectedOutput .= '!UserAccessManager\Controller\Backend\UserObjectController|'
            .'vfs://src/View/UserProfileEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($userObjectController);

        self::expectOutputString($expectedOutput);
    }

    /**
     * @group  unit
     * @covers ::saveUserData()
     */
    public function testSaveUserData()
    {
        /**
         * @var UserObjectController $userObjectController
         */
        $userObjectController = $this->getTestSaveObjectDataPrototype(
            UserObjectController::class,
            [
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1]
            ]
        );

        $userObjectController->saveUserData(1);
    }

    /**
     * @group  unit
     * @covers ::removeUserData()
     */
    public function testRemoveUserData()
    {
        /**
         * @var UserObjectController $userObjectController
         */
        $userObjectController = $this->getTestRemoveObjectDataPrototype(
            UserObjectController::class,
            2,
            ObjectHandler::GENERAL_USER_OBJECT_TYPE
        );
        $userObjectController->removeUserData(2);
    }
}
