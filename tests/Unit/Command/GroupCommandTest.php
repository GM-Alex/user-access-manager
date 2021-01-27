<?php
/**
 * GroupCommandTest.php
 *
 * The GroupCommandTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Command;

use PHPUnit\Framework\MockObject\MockObject;
use UserAccessManager\Command\GroupCommand;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_CLI\ExitException;

/**
 * Class GroupCommandTest
 *
 * @package UserAccessManager\Tests\Unit\Command
 * @coversDefaultClass \UserAccessManager\Command\GroupCommand
 */
class GroupCommandTest extends UserAccessManagerTestCase
{
    /**
     * @param string $id
     * @param string $name
     * @param string $description
     * @param string $readAccess
     * @param string $writeAccess
     * @param array $roles
     * @param array $ipRanges
     * @param int|null $saveExpected
     * @return MockObject|UserGroup
     */
    private function getExtendedUserGroup(
        string $id,
        string $name,
        string $description,
        string $readAccess,
        string $writeAccess,
        array $roles,
        array $ipRanges,
        ?int $saveExpected = null
    )
    {
        $userGroup = $this->getUserGroup(
            $id,
            true,
            false,
            $ipRanges,
            $readAccess,
            $writeAccess,
            [],
            [],
            $name)
        ;

        $userGroup->expects($this->any())
            ->method('getDescription')
            ->will($this->returnValue($description));

        $userGroup->expects($this->any())
            ->method('getAssignedObjectsByType')
            ->will($this->returnValue($roles));

        $userGroup->expects($saveExpected === null ? $this->any() : $this->exactly($saveExpected))
            ->method('save')
            ->will($this->returnValue($roles));

        return $userGroup;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $groupCommand = new GroupCommand(
            $this->getWordpressCli(),
            $this->getUserGroupHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf(GroupCommand::class, $groupCommand);
    }

    /**
     * @group  unit
     * @covers ::ls()
     * @covers ::getFormatter()
     * @throws ExitException
     * @throws UserGroupTypeException
     */
    public function testLs()
    {
        $wordpressCli = $this->getWordpressCli();
        $wordpressCli->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['No arguments excepted. Please use the format option.'],
                ['No groups defined yet!']
            );

        $formatter = $this->createMock('\WP_CLI\Formatter');
        $formatter->expects($this->exactly(2))
            ->method('display_items')
            ->withConsecutive(
                [[
                    1 => [
                        'ID' => 1,
                        'group_name' => 'firstGroupName',
                        'group_desc' => 'firstGroupDescription',
                        'read_access' => 'all',
                        'write_access' => 'none',
                        'roles' => 'roleOne,roleTwo',
                        'ip_range' => '1;2'
                    ]
                ]],
                [[
                    1 => [
                        'ID' => 1,
                        'group_name' => 'firstGroupName',
                        'group_desc' => 'firstGroupDescription',
                        'read_access' => 'all',
                        'write_access' => 'none',
                        'roles' => 'roleOne,roleTwo',
                        'ip_range' => '1;2'
                    ],
                    2 => [
                        'ID' => 2,
                        'group_name' => 'secondGroupName',
                        'group_desc' => 'secondGroupDescription',
                        'read_access' => 'none',
                        'write_access' => 'all',
                        'roles' => 'roleThree,roleFour',
                        'ip_range' => '3;4'
                    ]
                ]]
            );

        $wordpressCli->expects($this->exactly(2))
            ->method('createFormatter')
            ->with(
                ['a' => '1'],
                [
                    'ID',
                    'group_name',
                    'group_desc',
                    'read_access',
                    'write_access',
                    'roles',
                    'ip_range',
                ],
                GroupCommand::FORMATTER_PREFIX
            )
            ->will($this->returnValue($formatter));

        $firstUserGroup = $this->getExtendedUserGroup(
            1,
            'firstGroupName',
            'firstGroupDescription',
            'all',
            'none',
            ['roleOne' => 'roleOneName', 'roleTwo' => 'roleTwoName'],
            [1, 2],
            0
        );

        $secondUserGroup = $this->getExtendedUserGroup(
            2,
            'secondGroupName',
            'secondGroupDescription',
            'none',
            'all',
            ['roleThree' => 'roleThreeName', 'roleFour' => 'roleFourName'],
            [3, 4],
            0
        );

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->exactly(3))
            ->method('getUserGroups')
            ->will($this->onConsecutiveCalls(
                [],
                [1 => $firstUserGroup],
                [1 => $firstUserGroup, 2 => $secondUserGroup]
            ));

        $groupCommand = new GroupCommand(
            $wordpressCli,
            $userGroupHandler,
            $this->getUserGroupFactory()
        );

        $groupCommand->ls(['arguments'], ['a' => 1]);
        $groupCommand->ls([], ['a' => 1]);
        $groupCommand->ls([], ['a' => 1]);
        $groupCommand->ls([], ['a' => 1]);
    }

    /**
     * @group  unit
     * @covers ::del()
     * @throws ExitException
     * @throws UserGroupTypeException
     */
    public function testDel()
    {
        $wordpressCli = $this->getWordpressCli();
        $wordpressCli->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Expected: wp uam groups del \<id\> ...'],
                ['Group id \'3\' doesn\'t exists.']
            );

        $wordpressCli->expects($this->exactly(3))
            ->method('success')
            ->withConsecutive(
                ['Successfully deleted group with id \'1\'.'],
                ['Successfully deleted group with id \'1\'.'],
                ['Successfully deleted group with id \'2\'.']
            );

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->exactly(4))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [1], [2], [3])
            ->will($this->onConsecutiveCalls(true, true, true, false));

        $groupCommand = new GroupCommand(
            $wordpressCli,
            $userGroupHandler,
            $this->getUserGroupFactory()
        );

        $groupCommand->del([]);
        $groupCommand->del([1]);
        $groupCommand->del([1, 2, 3]);
    }

    /**
     * @group  unit
     * @covers ::add()
     * @covers ::doesUserGroupExists()
     * @covers ::createUserGroup()
     * @covers ::getArgumentValue()
     * @covers ::getAccessValue()
     * @throws ExitException
     * @throws UserGroupTypeException
     */
    public function testAdd()
    {
        $wordpressCli = $this->getWordpressCli();
        $wordpressCli->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Please provide a group name.'],
                ['Group with the same name \'firstGroupName\' already exists: 1']
            );

        $wordpressCli->expects($this->once())
            ->method('success')
            ->with('Added new group \'otherNewGroupName\' with id 3.');

        $wordpressCli->expects($this->exactly(3))
            ->method('line')
            ->withConsecutive(
                ['setting read_access to group'],
                ['setting write_access to group'],
                [3]
            );

        $firstUserGroup = $this->getExtendedUserGroup(
            1,
            'firstGroupName',
            'firstGroupDescription',
            'all',
            'none',
            ['roleOne' => 'roleOneName', 'roleTwo' => 'roleTwoName'],
            [1, 2],
            0
        );

        $secondUserGroup = $this->getExtendedUserGroup(
            2,
            'secondGroupName',
            'secondGroupDescription',
            'none',
            'all',
            ['roleThree' => 'roleThreeName', 'roleFour' => 'roleFourName'],
            [3, 4],
            0
        );

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->exactly(3))
            ->method('getUserGroups')
            ->will($this->returnValue([1 => $firstUserGroup, 2 => $secondUserGroup]));

        $userGroupHandler->expects($this->exactly(2))
            ->method('addUserGroup');

        $createdUserGroup = $this->getUserGroup(3);
        $createdUserGroup->expects($this->exactly(2))
            ->method('setName')
            ->withConsecutive(['newGroupName'], ['otherNewGroupName']);

        $createdUserGroup->expects($this->exactly(2))
            ->method('setDescription')
            ->withConsecutive([''], ['newGroupDesc']);

        $createdUserGroup->expects($this->exactly(2))
            ->method('setIpRange')
            ->withConsecutive([''], ['ipRange']);

        $createdUserGroup->expects($this->exactly(2))
            ->method('setReadAccess')
            ->withConsecutive(['group'], ['all']);

        $createdUserGroup->expects($this->exactly(2))
            ->method('setWriteAccess')
            ->withConsecutive(['group'], ['all']);

        $createdUserGroup->expects($this->once())
            ->method('removeObject')
            ->with(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        $createdUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo']
            );

        $createdUserGroup->expects($this->exactly(2))
            ->method('save');

        $userGroupFactory = $this->getUserGroupFactory();
        $userGroupFactory->expects($this->exactly(2))
            ->method('createUserGroup')
            ->will($this->returnValue($createdUserGroup));

        $groupCommand = new GroupCommand(
            $wordpressCli,
            $userGroupHandler,
            $userGroupFactory
        );

        $groupCommand->add([], []);
        $groupCommand->add(['firstGroupName'], []);
        $groupCommand->add(['newGroupName'], ['porcelain' => 1]);
        $groupCommand->add(['otherNewGroupName'], [
            'desc' => 'newGroupDesc',
            'ip_range' => 'ipRange',
            'read_access' => 'all',
            'write_access' => 'all',
            'roles' => 'roleOne, roleTwo',
        ]);
    }
}
