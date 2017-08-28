<?php
/**
 * UserObjectMembershipHandlerTest.php
 *
 * The UserObjectMembershipHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\ObjectMembership;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\ObjectMembership\UserMembershipHandler;

/**
 * Class UserObjectMembershipHandlerTest
 *
 * @package UserAccessManager\Tests\ObjectMembership
 * @coversDefaultClass \UserAccessManager\ObjectMembership\UserMembershipHandler
 */
class UserObjectMembershipHandlerTest extends ObjectMembershipHandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $userMembershipHandler = new UserMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $this->getPhp(),
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(UserMembershipHandler::class, $userMembershipHandler);
    }

    /**
     * @group  unit
     * @covers ::getObjectName()
     */
    public function testGetObjectName()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->display_name = 'userTwo';

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(2))
            ->method('getUser')
            ->withConsecutive([-1], [2])
            ->will($this->onConsecutiveCalls(false, $user));

        $userMembershipHandler = new UserMembershipHandler(
            $this->getAssignmentInformationFactory(),
            $this->getPhp(),
            $this->getDatabase(),
            $objectHandler
        );

        $typeName = 'someType';
        self::assertEquals(-1, $userMembershipHandler->getObjectName(-1, $typeName));
        self::assertEquals('_user_', $typeName);

        $typeName = 'someType';
        self::assertEquals('userTwo', $userMembershipHandler->getObjectName(2, $typeName));
        self::assertEquals('_user_', $typeName);
    }

    /**
     * @param array             $arrayFillWith
     * @param int               $expectGetUsersTable
     * @param int               $expectGetCapabilitiesTable
     * @param int               $expectGetUser
     * @param AbstractUserGroup $userGroup
     *
     * @return UserMembershipHandler
     */
    private function getUserObjectMembershipHandler(
        array $arrayFillWith,
        $expectGetUsersTable,
        $expectGetCapabilitiesTable,
        $expectGetUser,
        AbstractUserGroup $userGroup
    ) {
        $php = $this->getPhp();

        $php->expects($this->exactly(count($arrayFillWith)))
            ->method('arrayFill')
            ->withConsecutive(...$arrayFillWith)
            ->will($this->returnCallback(function ($startIndex, $numberOfElements, $value) {
                return array_fill($startIndex, $numberOfElements, $value);
            }));

        $database = $this->getDatabase();

        $database->expects($this->exactly($expectGetUsersTable))
            ->method('getUsersTable')
            ->will($this->returnValue('usersTable'));

        $database->expects($this->exactly($expectGetCapabilitiesTable))
            ->method('getCapabilitiesTable')
            ->will($this->returnValue('capabilitiesTable'));

        /**
         * @var \stdClass $firstUser
         */
        $firstUser = $this->getMockBuilder('\WP_User')->getMock();
        $firstUser->capabilitiesTable = [1 => 1, 2 => 2];

        /**
         * @var \stdClass $secondUser
         */
        $secondUser = $this->getMockBuilder('\WP_User')->getMock();
        $secondUser->capabilitiesTable = 'invalid';

        /**
         * @var \stdClass $thirdUser
         */
        $thirdUser = $this->getMockBuilder('\WP_User')->getMock();
        $thirdUser->capabilitiesTable = [1 => 1];

        /**
         * @var \stdClass $fourthUser
         */
        $fourthUser = $this->getMockBuilder('\WP_User')->getMock();
        $fourthUser->capabilitiesTable = [];

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly($expectGetUser))
            ->method('getUser')
            ->will($this->returnCallback(
                function ($userId) use (
                    $firstUser,
                    $secondUser,
                    $thirdUser,
                    $fourthUser
                ) {
                    if ($userId === 1) {
                        return $firstUser;
                    } elseif ($userId === 2) {
                        return $secondUser;
                    } elseif ($userId === 3) {
                        return $thirdUser;
                    } elseif ($userId === 4) {
                        return $fourthUser;
                    }

                    return false;
                }
            ));

        return new UserMembershipHandler(
            $this->getExtendedAssignmentInformationFactory(),
            $php,
            $database,
            $objectHandler
        );
    }

    /**
     * @group  unit
     * @covers ::isMember()
     */
    public function testIsMember()
    {
        $userGroup = $this->getMembershipUserGroup(
            [
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 4],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 2],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 3],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 5]
            ],
            [4, 3, 5],
            'fromDate',
            'toDate'
        );

        $userGroup->expects($this->exactly(3))
            ->method('getAssignedObjects')
            ->with(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
            ->will($this->onConsecutiveCalls(
                [],
                [
                    1 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'fromDate', 'toDate'),
                    2 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'fromDate', 'toDate')
                ],
                [
                    1 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'fromDate', 'toDate'),
                    2 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'fromDate', 'toDate')
                ]
            ));

        $userObjectMembershipHandler = $this->getUserObjectMembershipHandler(
            [
                [0, 2, $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)],
                [0, 1, $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)]
            ],
            0,
            5,
            6,
            $userGroup
        );

        $return = $userObjectMembershipHandler->isMember($userGroup, false, 4, $assignmentInformation);
        self::assertFalse($return);
        self::assertNull($assignmentInformation);

        $return = $userObjectMembershipHandler->isMember($userGroup, false, 3, $assignmentInformation);
        self::assertFalse($return);
        self::assertNull($assignmentInformation);

        $return = $userObjectMembershipHandler->isMember($userGroup, false, 1, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                'user',
                'fromDate',
                'toDate',
                [
                    ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                        1 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE),
                        2 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
                    ]
                ]
            ),
            $assignmentInformation
        );

        $return = $userObjectMembershipHandler->isMember($userGroup, false, 2, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals($this->getAssignmentInformation('user', 'fromDate', 'toDate'), $assignmentInformation);

        $return = $userObjectMembershipHandler->isMember($userGroup, false, 3, $assignmentInformation);
        self::assertTrue($return);
        self::assertEquals(
            $this->getAssignmentInformation(
                null,
                null,
                null,
                [
                    ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                        1 => $this->getAssignmentInformation(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
                    ]
                ]
            ),
            $assignmentInformation
        );

        $return = $userObjectMembershipHandler->isMember($userGroup, false, 5, $assignmentInformation);
        self::assertFalse($return);
        self::assertNull($assignmentInformation);
    }

    /**
     * @param int[] $numbers
     *
     * @return array
     */
    private function generateUserReturn(array $numbers)
    {
        $returns = [];

        foreach ($numbers as $number) {
            $return = new \stdClass();
            $return->ID = $number;
            $returns[] = $return;
        }

        return $returns;
    }

    /**
     * @group  unit
     * @covers ::getFullObjects()
     */
    public function testGetFullObjects()
    {
        $query = "SELECT ID, user_nicename FROM usersTable";

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getUsersTable')
            ->will($this->returnValue('usersTable'));

        $database->expects($this->once())
            ->method('getResults')
            ->with(new MatchIgnoreWhitespace($query))
            ->will($this->returnValue($this->generateUserReturn([10 => 10, 1, 2, 3])));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|AbstractUserGroup $userGroup
         */
        $userGroup = $this->createMock(AbstractUserGroup::class);
        $userGroup->expects($this->exactly(4))
            ->method('isObjectMember')
            ->withConsecutive(
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 10],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 1],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 2],
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE, 3]
            )
            ->will($this->onConsecutiveCalls(true, true, true, false));

        $userObjectMembershipHandler = new UserMembershipHandler(
            $this->getExtendedAssignmentInformationFactory(),
            $this->getPhp(),
            $database,
            $this->getObjectHandler()
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                2 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                10 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            ],
            $userObjectMembershipHandler->getFullObjects($userGroup, false, 'someType')
        );
    }
}
