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

/**
 * Class ObjectCommandTest
 *
 * @package UserAccessManager\Command
 */
class ObjectCommandTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Command\ObjectCommand::__construct()
     */
    public function testCanCreateInstance()
    {
        $oObjectCommand = new ObjectCommand(
            $this->getWordpressCli(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf('\UserAccessManager\Command\ObjectCommand', $oObjectCommand);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Command\ObjectCommand::__invoke()
     */
    public function testInvoke()
    {
        $oWordpressCli = $this->getWordpressCli();
        $oWordpressCli->expects($this->exactly(3))
            ->method('error')
            ->withConsecutive(
                ['<operation>, <object_type>, <object_id> and <user_groups> are required'],
                ['Operation is not valid: invalid'],
                ['There is no group with the id: 3']
            );

        $oWordpressCli->expects($this->exactly(3))
            ->method('success')
            ->withConsecutive(
                ['Groups 1,2 successfully added to post 1'],
                ['Successfully updated user 2 with groups 1,2'],
                ['Successfully removed groups: firstGroupName,secondGroupName from category 3']
            );

        $oFirstUserGroup = $this->getUserGroup(1, true, false, [''], 'none', 'none', [], [], 'firstGroupName');

        $oFirstUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                ['post', 1],
                ['user', 2]
            );

        $oFirstUserGroup->expects($this->exactly(2))
            ->method('removeObject')
            ->withConsecutive(
                ['user', 2],
                ['category', 3]
            );

        $oSecondUserGroup = $this->getUserGroup(2, true, false, [''], 'none', 'none', [], [], 'secondGroupName');

        $oSecondUserGroup->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                ['post', 1],
                ['user', 2]
            );

        $oSecondUserGroup->expects($this->once())
            ->method('removeObject')
            ->withConsecutive(
                ['category', 3]
            );


        $oAccessHandler = $this->getAccessHandler();
        $oAccessHandler->expects($this->exactly(4))
            ->method('getUserGroups')
            ->will($this->returnValue([1 => $oFirstUserGroup, 2 => $oSecondUserGroup]));

        $oAccessHandler->expects($this->once())
            ->method('getUserGroupsForObject')
            ->with('user', 2)
            ->will($this->returnValue([1 => $oFirstUserGroup]));

        $oObjectCommand = new ObjectCommand(
            $oWordpressCli,
            $oAccessHandler
        );

        $oObjectCommand->__invoke([], []);
        $oObjectCommand->__invoke(['invalid', 'post', 1, '1,2'], []);
        $oObjectCommand->__invoke(['add', 'post', 1, '1,2,3'], []);
        $oObjectCommand->__invoke(['add', 'post', 1, '1,2'], []);
        $oObjectCommand->__invoke(['update', 'user', 2, '1,2'], []);
        $oObjectCommand->__invoke(['remove', 'category', 3, 'firstGroupName,secondGroupName'], []);
    }
}
