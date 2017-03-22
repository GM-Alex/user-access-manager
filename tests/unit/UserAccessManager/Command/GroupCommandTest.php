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

/**
 * Class GroupCommandTest
 *
 * @package UserAccessManager\Command
 */
class GroupCommandTest extends \UserAccessManagerTestCase
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
    )
    {
        $oUserGroup = $this->getUserGroup($iId, true, false, $aIpRanges, $sReadAccess, $sWriteAccess);

        $oUserGroup->expects($this->any())
            ->method('getGroupName')
            ->will($this->returnValue($sName));

        $oUserGroup->expects($this->any())
            ->method('getGroupDescription')
            ->will($this->returnValue($sDescription));

        $oUserGroup->expects($this->any())
            ->method('getAssignedObjectsByType')
            ->will($this->returnValue($aRoles));

        return $oUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\GroupCommand::__construct()
     */
    public function testCanCreateInstance()
    {
        $oGroupCommand = new GroupCommand(
            $this->getWordpressCli(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Command\GroupCommand', $oGroupCommand);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\GroupCommand::ls()
     * @covers \UserAccessManager\Command\GroupCommand::_getFormatter()
     */
    public function testLs()
    {
        $oWordpressCli = $this->getWordpressCli();
        $oWordpressCli->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['No arguments excepted. Please use the format option.'],
                ['No groups defined yet!']
            );

        $oFormatter = $this->createMock('\WP_CLI\Formatter');
        $oFormatter->expects($this->once())
            ->method('display_items')
            ->with([
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
            ]);

        $oWordpressCli->expects($this->once())
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
            ->will($this->returnValue($oFormatter));

        $oFirstUserGroup = $this->getExtendedUserGroup(
            1,
            'firstGroupName',
            'firstGroupDescription',
            'all',
            'none',
            ['roleOne' => 'roleOne', 'roleTwo' => 'roleTwo'],
            [1, 2]
        );
        $oSecondUserGroup = $this->getExtendedUserGroup(
            2,
            'secondGroupName',
            'secondGroupDescription',
            'none',
            'all',
            ['roleThree' => 'roleThree', 'roleFour' => 'roleFour'],
            [3, 4]
        );

        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(2))
            ->method('getUserGroups')
            ->will($this->onConsecutiveCalls([], [1 => $oFirstUserGroup, 2 => $oSecondUserGroup]));

        $oGroupCommand = new GroupCommand(
            $oWordpressCli,
            $oAccessHandler,
            $this->getUserGroupFactory()
        );

        $oGroupCommand->ls(['arguments'], ['a' => 'b']);
        $oGroupCommand->ls([], ['a' => 'b']);
        $oGroupCommand->ls([], ['a' => 'b']);
    }
}
