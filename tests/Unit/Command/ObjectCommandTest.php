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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Command;

use UserAccessManager\Command\ObjectCommand;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_CLI\ExitException;

/**
 * Class ObjectCommandTest
 *
 * @package UserAccessManager\Tests\Unit\Command
 * @coversDefaultClass \UserAccessManager\Command\ObjectCommand
 */
class ObjectCommandTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $objectCommand = new ObjectCommand(
            $this->getWordpressCli(),
            $this->getUserGroupHandler()
        );

        self::assertInstanceOf(ObjectCommand::class, $objectCommand);
    }

    /**
     * @group  unit
     * @covers ::__invoke()
     * @covers ::getUserGroupNameMap()
     * @covers ::getAddRemoveUserGroups()
     * @covers ::getUserGroupIdAndType()
     * @throws ExitException
     * @throws UserGroupTypeException
     */
    public function testInvoke()
    {
        $wordpressCli = $this->getWordpressCli();
        $wordpressCli->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                ['<operation>, <object_type>, <object_id> and <user_groups> are required'],
                ['Operation is not valid: invalid'],
                ['There is no group with the id: 3']
            );

        $wordpressCli->expects($this->exactly(3))
            ->method('success')
            ->withConsecutive(
                ['Groups 1, 2 successfully added to post 1'],
                ['Successfully updated user 2 with groups 1, 2'],
                ['Successfully removed groups: firstGroupName, secondGroupName from category 3']
            );

        $firstUserGroup = $this->getUserGroup(1, true, false, [''], 'none', 'none', [], [], 'firstGroupName');
        $firstUserGroup->expects($this->exactly(3))
            ->method('save');

        $firstUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                ['post', 1],
                ['user', 2]
            );

        $firstUserGroup->expects($this->exactly(2))
            ->method('removeObject')
            ->withConsecutive(
                ['user', 2],
                ['category', 3]
            );

        $secondUserGroup = $this->getUserGroup(2, true, false, [''], 'none', 'none', [], [], 'secondGroupName');

        $secondUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                ['post', 1],
                ['user', 2]
            );

        $secondUserGroup->expects($this->once())
            ->method('removeObject')
            ->withConsecutive(
                ['category', 3]
            );

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->exactly(4))
            ->method('getUserGroups')
            ->will($this->returnValue([1 => $firstUserGroup, 2 => $secondUserGroup]));

        $userGroupHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('user', 2)
            ->will($this->returnValue([1 => $firstUserGroup]));

        $objectCommand = new ObjectCommand(
            $wordpressCli,
            $userGroupHandler
        );

        $objectCommand->__invoke([], []);
        $objectCommand->__invoke(['invalid', 'post', 1, '1,2,2'], []);
        $objectCommand->__invoke(['add', 'post', 1, '1,2,3'], []);
        $objectCommand->__invoke(['add', 'post', 1, '1,2'], []);
        $objectCommand->__invoke(['update', 'user', 2, '1,2'], []);
        $objectCommand->__invoke(['remove', 'category', 3, 'firstGroupName,secondGroupName,secondGroupName'], []);
    }
}
