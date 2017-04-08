<?php
/**
 * ObjectCommandTest.php
 *
 * The ObjectCommandTest unit test class file.
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

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class ObjectCommandTest
 *
 * @package UserAccessManager\Command
 */
class ObjectCommandTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Command\ObjectCommand::__construct()
     */
    public function testCanCreateInstance()
    {
        $ObjectCommand = new ObjectCommand(
            $this->getWordpressCli(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Command\ObjectCommand', $ObjectCommand);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\ObjectCommand::__invoke()
     */
    public function testInvoke()
    {
        $WordpressCli = $this->getWordpressCli();
        $WordpressCli->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                ['<operation>, <object_type>, <object_id> and <user_groups> are required'],
                ['Operation is not valid: invalid'],
                ['There is no group with the id: 3']
            );

        $WordpressCli->expects($this->exactly(3))
            ->method('success')
            ->withConsecutive(
                ['Groups 1,2 successfully added to post 1'],
                ['Successfully updated user 2 with groups 1,2'],
                ['Successfully removed groups: firstGroupName,secondGroupName from category 3']
            );

        $FirstUserGroup = $this->getUserGroup(1, true, false, [''], 'none', 'none', [], [], 'firstGroupName');

        $FirstUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                ['post', 1],
                ['user', 2]
            );

        $FirstUserGroup->expects($this->exactly(2))
            ->method('removeObject')
            ->withConsecutive(
                ['user', 2],
                ['category', 3]
            );

        $SecondUserGroup = $this->getUserGroup(2, true, false, [''], 'none', 'none', [], [], 'secondGroupName');

        $SecondUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                ['post', 1],
                ['user', 2]
            );

        $SecondUserGroup->expects($this->once())
            ->method('removeObject')
            ->withConsecutive(
                ['category', 3]
            );


        $AccessHandler = $this->getAccessHandler();
        $AccessHandler->expects($this->exactly(4))
            ->method('getUserGroups')
            ->will($this->returnValue([1 => $FirstUserGroup, 2 => $SecondUserGroup]));

        $AccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('user', 2)
            ->will($this->returnValue([1 => $FirstUserGroup]));

        $ObjectCommand = new ObjectCommand(
            $WordpressCli,
            $AccessHandler
        );

        $ObjectCommand->__invoke([], []);
        $ObjectCommand->__invoke(['invalid', 'post', 1, '1,2'], []);
        $ObjectCommand->__invoke(['add', 'post', 1, '1,2,3'], []);
        $ObjectCommand->__invoke(['add', 'post', 1, '1,2'], []);
        $ObjectCommand->__invoke(['update', 'user', 2, '1,2'], []);
        $ObjectCommand->__invoke(['remove', 'category', 3, 'firstGroupName,secondGroupName'], []);
    }
}
