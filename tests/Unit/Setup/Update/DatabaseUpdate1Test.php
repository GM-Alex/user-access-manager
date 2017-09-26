<?php
/**
 * DatabaseUpdate1Test.php
 *
 * The DatabaseUpdate1Test unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Setup\Update;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Setup\Update\DatabaseUpdate1;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class DatabaseUpdate1Test
 *
 * @package UserAccessManager\Tests\Unit\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate1
 */
class DatabaseUpdate1Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new DatabaseUpdate1(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(DatabaseUpdate1::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new DatabaseUpdate1(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.0', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers ::update()
     * @covers ::updateToUserGroupTableUpdate()
     * @covers ::updateToUserGroupToObjectTableUpdate()
     * @covers ::getObjectSelectQuery()
     */
    public function testUpdate()
    {
        $database = $this->getDatabase();
        $database->expects($this->exactly(4))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(3))
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        $database->expects($this->exactly(3))
            ->method('getCharset')
            ->will($this->returnValue('CHARSET testCharset'));

        $database->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postsTable'));

        $database->expects($this->exactly(6))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW columns FROM userGroupTable LIKE \'ip_range\''],
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW columns FROM userGroupTable LIKE \'ip_range\''],
                ['SHOW TABLES LIKE \'userGroupTable\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable',
                'not_ip_range',
                'someUserGroupTable',
                'userGroupTable',
                'ip_range',
                'someUserGroupTable'
            ));

        $database->expects($this->exactly(2))
            ->method('insert')
            ->with(
                'prefix_uam_accessgroup_to_object',
                [
                    'group_id' => 123,
                    'object_id' => 321,
                    'object_type' => 'post'
                ],
                ['%d', '%d', '%s']
            )
            ->will($this->onConsecutiveCalls(true, false));

        $firstDbObject = new \stdClass();
        $firstDbObject->groupId = 123;
        $firstDbObject->id = 321;

        $database->expects($this->exactly(5))
            ->method('getResults')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'SELECT post_id AS id, group_id AS groupId
                    FROM prefix_uam_accessgroup_to_post, postsTable WHERE post_id = ID
                    AND post_type = \'post\''
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT category_id AS id, group_id AS groupId
                    FROM prefix_uam_accessgroup_to_category'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT user_id AS id, group_id AS groupId FROM prefix_uam_accessgroup_to_user'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT role_name AS id, group_id AS groupId FROM prefix_uam_accessgroup_to_role'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT post_id AS id, group_id AS groupId
                    FROM prefix_uam_accessgroup_to_post, postsTable WHERE post_id = ID
                    AND post_type = \'post\''
                )]
            )
            ->will($this->onConsecutiveCalls([$firstDbObject], [], [], [], [$firstDbObject]));

        $database->expects($this->exactly(9))
            ->method('query')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable
                    ADD read_access TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD write_access TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD ip_range MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupTable SET read_access = \'group\', write_access = \'group\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable ADD ip_range MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE \'prefix_uam_accessgroup_to_object\'
                    CHANGE \'object_id\' \'object_id\' VARCHAR(64) CHARSET testCharset'
                )],
                [new MatchIgnoreWhitespace(
                    'DROP TABLE prefix_uam_accessgroup_to_post,
                    prefix_uam_accessgroup_to_user,
                    prefix_uam_accessgroup_to_category,
                    prefix_uam_accessgroup_to_role'
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE \'prefix_uam_accessgroup_to_object\'
                    CHANGE \'object_id\' \'object_id\' VARCHAR(64) CHARSET testCharset'
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable
                    ADD read_access TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD write_access TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD ip_range MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupTable SET read_access = \'group\', write_access = \'group\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE \'prefix_uam_accessgroup_to_object\'
                    CHANGE \'object_id\' \'object_id\' VARCHAR(64) CHARSET testCharset'
                )]
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable',
                'ip_range',
                true,
                true,
                true,
                false,
                true,
                false,
                true,
                true
            ));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(2))
            ->method('getObjectTypes')
            ->will($this->onConsecutiveCalls(
                ['post', 'nothing', 'category', 'nothing', 'user', 'nothing', 'role', 'nothing'],
                ['post']
            ));

        $objectHandler->expects($this->exactly(9))
            ->method('isPostType')
            ->withConsecutive(
                ['post'],
                ['nothing'],
                ['category'],
                ['nothing'],
                ['user'],
                ['nothing'],
                ['role'],
                ['nothing'],
                ['post']
            )
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false, false, true));

        $update = new DatabaseUpdate1($database, $objectHandler);
        self::assertTrue($update->update());
        self::assertFalse($update->update());
        self::assertFalse($update->update());
        self::assertFalse($update->update());
    }
}
