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
namespace UserAccessManager\Tests\Command;

use UserAccessManager\Command\GroupCommand;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class GroupCommandTest
 *
 * @package UserAccessManager\Command
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
     * @param array  $roles
     * @param array  $ipRanges
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroup
     */
    private function getExtendedUserGroup(
        $id,
        $name,
        $description,
        $readAccess,
        $writeAccess,
        array $roles,
        $ipRanges
    ) {
        $userGroup = $this->getUserGroup($id, true, false, $ipRanges, $readAccess, $writeAccess, [], [], $name);

        $userGroup->expects($this->any())
            ->method('getDescription')
            ->will($this->returnValue($description));

        $userGroup->expects($this->any())
            ->method('getAssignedObjectsByType')
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
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf(GroupCommand::class, $groupCommand);
    }

    /**
     * @group  unit
     * @covers ::ls()
     * @covers ::getFormatter()
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
                ['a' => 'b'],
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
            ['roleOne' => 'roleOne', 'roleTwo' => 'roleTwo'],
            [1, 2]
        );

        $secondUserGroup = $this->getExtendedUserGroup(
            2,
            'secondGroupName',
            'secondGroupDescription',
            'none',
            'all',
            ['roleThree' => 'roleThree', 'roleFour' => 'roleFour'],
            [3, 4]
        );

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(3))
            ->method('getUserGroups')
            ->will($this->onConsecutiveCalls(
                [],
                [1 => $firstUserGroup],
                [1 => $firstUserGroup, 2 => $secondUserGroup]
            ));

        $groupCommand = new GroupCommand(
            $wordpressCli,
            $accessHandler,
            $this->getUserGroupFactory()
        );

        $groupCommand->ls(['arguments'], ['a' => 'b']);
        $groupCommand->ls([], ['a' => 'b']);
        $groupCommand->ls([], ['a' => 'b']);
        $groupCommand->ls([], ['a' => 'b']);
    }

    /**
     * @group  unit
     * @covers ::del()
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

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(4))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [1], [2], [3])
            ->will($this->onConsecutiveCalls(true, true, true, false));

        $groupCommand = new GroupCommand(
            $wordpressCli,
            $accessHandler,
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
     * @covers ::getAccessValue()
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
            ['roleOne' => 'roleOne', 'roleTwo' => 'roleTwo'],
            [1, 2]
        );

        $secondUserGroup = $this->getExtendedUserGroup(
            2,
            'secondGroupName',
            'secondGroupDescription',
            'none',
            'all',
            ['roleThree' => 'roleThree', 'roleFour' => 'roleFour'],
            [3, 4]
        );

        $accessHandler = $this->getAccessHandler();
        $accessHandler->expects($this->exactly(3))
            ->method('getUserGroups')
            ->will($this->returnValue([1 => $firstUserGroup, 2 => $secondUserGroup]));

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

        $userGroupFactory = $this->getUserGroupFactory();
        $userGroupFactory->expects($this->exactly(2))
            ->method('createUserGroup')
            ->will($this->returnValue($createdUserGroup));

        $groupCommand = new GroupCommand(
            $wordpressCli,
            $accessHandler,
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
