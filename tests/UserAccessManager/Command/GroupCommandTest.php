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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Command;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class GroupCommandTest
 *
 * @package UserAccessManager\Command
 */
class GroupCommandTest extends UserAccessManagerTestCase
{
    /**
     * @param string $iId
     * @param string $sName
     * @param string $sDescription
     * @param string $sReadAccess
     * @param string $sWriteAccess
     * @param array  $aRoles
     * @param array  $aIpRanges
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroup
     */
    private function getExtendedUserGroup(
        $iId,
        $sName,
        $sDescription,
        $sReadAccess,
        $sWriteAccess,
        array $aRoles,
        $aIpRanges
    ) {
        $UserGroup = $this->getUserGroup($iId, true, false, $aIpRanges, $sReadAccess, $sWriteAccess, [], [], $sName);

        $UserGroup->expects($this->any())
            ->method('getDescription')
            ->will($this->returnValue($sDescription));

        $UserGroup->expects($this->any())
            ->method('getAssignedObjectsByType')
            ->will($this->returnValue($aRoles));

        return $UserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\GroupCommand::__construct()
     */
    public function testCanCreateInstance()
    {
        $GroupCommand = new GroupCommand(
            $this->getWordpressCli(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Command\GroupCommand', $GroupCommand);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\GroupCommand::ls()
     * @covers \UserAccessManager\Command\GroupCommand::getFormatter()
     */
    public function testLs()
    {
        $WordpressCli = $this->getWordpressCli();
        $WordpressCli->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['No arguments excepted. Please use the format option.'],
                ['No groups defined yet!']
            );

        $Formatter = $this->createMock('\WP_CLI\Formatter');
        $Formatter->expects($this->exactly(2))
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

        $WordpressCli->expects($this->exactly(2))
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
            ->will($this->returnValue($Formatter));

        $FirstUserGroup = $this->getExtendedUserGroup(
            1,
            'firstGroupName',
            'firstGroupDescription',
            'all',
            'none',
            ['roleOne' => 'roleOne', 'roleTwo' => 'roleTwo'],
            [1, 2]
        );

        $SecondUserGroup = $this->getExtendedUserGroup(
            2,
            'secondGroupName',
            'secondGroupDescription',
            'none',
            'all',
            ['roleThree' => 'roleThree', 'roleFour' => 'roleFour'],
            [3, 4]
        );

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(3))
            ->method('getUserGroups')
            ->will($this->onConsecutiveCalls(
                [],
                [1 => $FirstUserGroup],
                [1 => $FirstUserGroup, 2 => $SecondUserGroup]
            ));

        $GroupCommand = new GroupCommand(
            $WordpressCli,
            $AccessHandler,
            $this->getUserGroupFactory()
        );

        $GroupCommand->ls(['arguments'], ['a' => 'b']);
        $GroupCommand->ls([], ['a' => 'b']);
        $GroupCommand->ls([], ['a' => 'b']);
        $GroupCommand->ls([], ['a' => 'b']);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\GroupCommand::del()
     */
    public function testDel()
    {
        $WordpressCli = $this->getWordpressCli();
        $WordpressCli->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Expected: wp uam groups del \<id\> ...'],
                ['Group id \'3\' doesn\'t exists.']
            );

        $WordpressCli->expects($this->exactly(3))
            ->method('success')
            ->withConsecutive(
                ['Successfully deleted group with id \'1\'.'],
                ['Successfully deleted group with id \'1\'.'],
                ['Successfully deleted group with id \'2\'.']
            );

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(4))
            ->method('deleteUserGroup')
            ->withConsecutive([1], [1], [2], [3])
            ->will($this->onConsecutiveCalls(true, true, true, false));

        $GroupCommand = new GroupCommand(
            $WordpressCli,
            $AccessHandler,
            $this->getUserGroupFactory()
        );

        $GroupCommand->del([]);
        $GroupCommand->del([1]);
        $GroupCommand->del([1, 2, 3]);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\GroupCommand::add()
     */
    public function testAdd()
    {
        $WordpressCli = $this->getWordpressCli();
        $WordpressCli->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Please provide a group name.'],
                ['Group with the same name \'firstGroupName\' already exists: 1']
            );

        $WordpressCli->expects($this->once())
            ->method('success')
            ->with('Added new group \'otherNewGroupName\' with id 3.');

        $WordpressCli->expects($this->exactly(3))
            ->method('line')
            ->withConsecutive(
                ['setting read_access to group'],
                ['setting write_access to group'],
                [3]
            );

        $FirstUserGroup = $this->getExtendedUserGroup(
            1,
            'firstGroupName',
            'firstGroupDescription',
            'all',
            'none',
            ['roleOne' => 'roleOne', 'roleTwo' => 'roleTwo'],
            [1, 2]
        );

        $SecondUserGroup = $this->getExtendedUserGroup(
            2,
            'secondGroupName',
            'secondGroupDescription',
            'none',
            'all',
            ['roleThree' => 'roleThree', 'roleFour' => 'roleFour'],
            [3, 4]
        );

        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(3))
            ->method('getUserGroups')
            ->will($this->returnValue([1 => $FirstUserGroup, 2 => $SecondUserGroup]));

        $CreatedUserGroup = $this->getUserGroup(3);
        $CreatedUserGroup->expects($this->exactly(2))
            ->method('setName')
            ->withConsecutive(['newGroupName'], ['otherNewGroupName']);

        $CreatedUserGroup->expects($this->exactly(2))
            ->method('setDescription')
            ->withConsecutive([''], ['newGroupDesc']);

        $CreatedUserGroup->expects($this->exactly(2))
            ->method('setIpRange')
            ->withConsecutive([''], ['ipRange']);

        $CreatedUserGroup->expects($this->exactly(2))
            ->method('setReadAccess')
            ->withConsecutive(['group'], ['all']);

        $CreatedUserGroup->expects($this->exactly(2))
            ->method('setWriteAccess')
            ->withConsecutive(['group'], ['all']);

        $CreatedUserGroup->expects($this->once())
            ->method('removeObject')
            ->with(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        $CreatedUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleOne'],
                [ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 'roleTwo']
            );

        $UserGroupFactory = $this->getUserGroupFactory();
        $UserGroupFactory->expects($this->exactly(2))
            ->method('createUserGroup')
            ->will($this->returnValue($CreatedUserGroup));

        $GroupCommand = new GroupCommand(
            $WordpressCli,
            $AccessHandler,
            $UserGroupFactory
        );

        $GroupCommand->add([], []);
        $GroupCommand->add(['firstGroupName'], []);
        $GroupCommand->add(['newGroupName'], ['porcelain' => 1]);
        $GroupCommand->add(['otherNewGroupName'], [
            'desc' => 'newGroupDesc',
            'ip_range' => 'ipRange',
            'read_access' => 'all',
            'write_access' => 'all',
            'roles' => 'roleOne, roleTwo',
        ]);
    }
}
