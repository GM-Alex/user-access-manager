<?php

namespace UserAccessManager\Tests\Unit\Setup\Update;

use stdClass;
use UserAccessManager\Setup\Update\DatabaseUpdate1;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
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
     * @covers ::getMigratableObjectTypes()
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

        // The real value carries no DEFAULT, that would make it a table option.
        $database->expects($this->exactly(3))
            ->method('getColumnCharset')
            ->will($this->returnValue('CHARACTER SET testCharset COLLATE testCollate'));

        $database->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postsTable'));

        $database->expects($this->exactly(6))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW COLUMNS FROM `userGroupTable` LIKE \'ip_range\''],
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW COLUMNS FROM `userGroupTable` LIKE \'ip_range\''],
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
                ['%s', '%s', '%s']
            )
            ->will($this->onConsecutiveCalls(true, false));

        $firstDbObject = new stdClass();
        $firstDbObject->groupId = 123;
        $firstDbObject->id = 321;

        $database->expects($this->exactly(7))
            ->method('getResults')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'SELECT `post_id` AS `id`, `group_id` AS `groupId`
                    FROM `prefix_uam_accessgroup_to_post`, `postsTable` WHERE `post_id` = `ID`
                    AND `post_type` = \'post\''
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT `category_id` AS `id`, `group_id` AS `groupId`
                    FROM `prefix_uam_accessgroup_to_category`'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT `user_id` AS `id`, `group_id` AS `groupId` FROM `prefix_uam_accessgroup_to_user`'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT `role_name` AS `id`, `group_id` AS `groupId` FROM `prefix_uam_accessgroup_to_role`'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT `post_id` AS `id`, `group_id` AS `groupId`
                    FROM `prefix_uam_accessgroup_to_post`, `postsTable` WHERE `post_id` = `ID`
                    AND `post_type` = \'post\''
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT `user_id` AS `id`, `group_id` AS `groupId` FROM `prefix_uam_accessgroup_to_user`'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT `role_name` AS `id`, `group_id` AS `groupId` FROM `prefix_uam_accessgroup_to_role`'
                )]
            )
            ->will($this->onConsecutiveCalls([$firstDbObject], [], [], [], [$firstDbObject], [], []));

        $database->expects($this->exactly(9))
            ->method('query')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE `userGroupTable`
                    ADD `read_access` TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD `write_access` TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD `ip_range` MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE `userGroupTable` SET `read_access` = \'group\', `write_access` = \'group\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE `userGroupTable` ADD `ip_range` MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE `prefix_uam_accessgroup_to_object`
                    CHANGE `object_id` `object_id` VARCHAR(64) CHARACTER SET testCharset COLLATE testCollate'
                )],
                [new MatchIgnoreWhitespace(
                    'DROP TABLE IF EXISTS `prefix_uam_accessgroup_to_post`,
                    `prefix_uam_accessgroup_to_user`,
                    `prefix_uam_accessgroup_to_category`,
                    `prefix_uam_accessgroup_to_role`'
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE `prefix_uam_accessgroup_to_object`
                    CHANGE `object_id` `object_id` VARCHAR(64) CHARACTER SET testCharset COLLATE testCollate'
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE `userGroupTable`
                    ADD `read_access` TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD `write_access` TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD `ip_range` MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE `userGroupTable` SET `read_access` = \'group\', `write_access` = \'group\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE `prefix_uam_accessgroup_to_object`
                    CHANGE `object_id` `object_id` VARCHAR(64) CHARACTER SET testCharset COLLATE testCollate'
                )]
            )
            ->will($this->onConsecutiveCalls(
                1,
                2,
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
                ['post', 'nothing', 'category', 'nothing'],
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
                ['role'],
                ['post'],
                ['user'],
                ['role']
            )
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, true, false, false));

        $update = new DatabaseUpdate1($database, $objectHandler);
        self::assertTrue($update->update());
        self::assertFalse($update->update());
        self::assertFalse($update->update());
        self::assertFalse($update->update());
    }
}
