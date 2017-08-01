<?php
/**
 * SetupHandlerTest.php
 *
 * The SetupHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\SetupHandler;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class SetupHandlerTest
 *
 * @package UserAccessManager\SetupHandler
 * @coversDefaultClass \UserAccessManager\SetupHandler\SetupHandler
 */
class SetupHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $setupHandler = new SetupHandler(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf(SetupHandler::class, $setupHandler);
    }

    /**
     * @param int $numberOfSites
     *
     * @return array
     */
    private function getSites($numberOfSites = 3)
    {
        $sites = [];

        for ($count = 1; $count <= $numberOfSites; $count++) {
            /**
             * @var \stdClass $site
             */
            $site = $this->getMockBuilder('\WP_Site')->getMock();
            $site->blog_id = $count;
            $sites[] = $site;
        }

        return $sites;
    }

    /**
     * @group  unit
     * @covers ::getBlogIds()
     */
    public function testGetBlogIds()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites()));

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getCurrentBlogId')
            ->will($this->returnValue(123));

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        $blogIds = $setupHandler->getBlogIds();
        self::assertEquals([123 => 123, 1 => 1, 2 => 2, 3 => 3], $blogIds);
    }

    /**
     * @group  unit
     * @covers ::install()
     * @covers ::runInstall()
     */
    public function testInstall()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites(1)));

        $wordpress->expects(($this->exactly(3)))
            ->method('addOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $wordpress->expects(($this->exactly(2)))
            ->method('switchToBlog')
            ->withConsecutive([1], [1]);

        $database = $this->getDatabase();
        $database->expects($this->exactly(3))
            ->method('getCharset')
            ->will($this->returnValue('CHARSET test'));

        $database->expects($this->exactly(3))
            ->method('getUserGroupTable')
            ->will($this->returnValue('user_group_table'));

        $database->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('user_group_to_object_table'));

        $database->expects($this->exactly(6))
            ->method('getVariable')
            ->will($this->onConsecutiveCalls(
                'invalid_table',
                'invalid_table',
                'user_group_table',
                'user_group_to_object_table',
                'invalid_table',
                'invalid_table'
            ));

        $database->expects($this->exactly(2))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $objectHandler = $this->getObjectHandler();
        $fileHandler = $this->getFileHandler();

        $database->expects($this->exactly(4))
            ->method('dbDelta')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_table (
                        ID INT(11) NOT NULL AUTO_INCREMENT,
                        groupname TINYTEXT NOT NULL,
                        groupdesc TEXT NOT NULL,
                        read_access TINYTEXT NOT NULL,
                        write_access TINYTEXT NOT NULL,
                        ip_range MEDIUMTEXT NULL,
                        PRIMARY KEY (ID)
                    ) CHARSET test;'
                )],
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_to_object_table (
                        object_id VARCHAR(64) NOT NULL,
                        general_object_type VARCHAR(64) NOT NULL,
                        object_type VARCHAR(64) NOT NULL,
                        group_id VARCHAR(64) NOT NULL,
                        group_type VARCHAR(64) NOT NULL,
                        from_date DATETIME NULL DEFAULT NULL,
                        to_date DATETIME NULL DEFAULT NULL,
                        PRIMARY KEY (object_id, object_type, group_id, group_type)
                    ) CHARSET test;'
                )],
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_table (
                        ID INT(11) NOT NULL AUTO_INCREMENT,
                        groupname TINYTEXT NOT NULL,
                        groupdesc TEXT NOT NULL,
                        read_access TINYTEXT NOT NULL,
                        write_access TINYTEXT NOT NULL,
                        ip_range MEDIUMTEXT NULL,
                        PRIMARY KEY (ID)
                    ) CHARSET test;'
                )],
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_to_object_table (
                        object_id VARCHAR(64) NOT NULL,
                        general_object_type VARCHAR(64) NOT NULL,
                        object_type VARCHAR(64) NOT NULL,
                        group_id VARCHAR(64) NOT NULL,
                        group_type VARCHAR(64) NOT NULL,
                        from_date DATETIME NULL DEFAULT NULL,
                        to_date DATETIME NULL DEFAULT NULL,
                        PRIMARY KEY (object_id, object_type, group_id, group_type)
                    ) CHARSET test;'
                )]
            );

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $objectHandler,
            $fileHandler
        );

        $setupHandler->install();
        $setupHandler->install();
        $setupHandler->install(true);
    }

    /**
     * @group  unit
     * @covers ::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(5))
            ->method('getSites')
            ->will($this->onConsecutiveCalls(
                $this->getSites(),
                $this->getSites(),
                $this->getSites(),
                [],
                $this->getSites(1)
            ));

        $wordpress->expects($this->exactly(5))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, true, true, true));


        $wordpress->expects($this->exactly(3))
            ->method('getOption')
            ->with('uam_db_version')
            ->will($this->onConsecutiveCalls('1000.0.0', '0.0', '1000.0'));

        $database = $this->getDatabase();
        $database->expects($this->exactly(5))
            ->method('getBlogPrefix')
            ->will($this->returnValue('prefix_'));

        $database->expects($this->exactly(5))
            ->method('prepare')
            ->with('SELECT option_value FROM prefix_options WHERE option_name = \'%s\' LIMIT 1', 'uam_db_version')
            ->will($this->returnValue('preparedStatement'));

        $database->expects($this->exactly(5))
            ->method('getVariable')
            ->with('preparedStatement')
            ->will($this->onConsecutiveCalls('1000.0.0', '0.0', '0.0', null, null));

        $objectHandler = $this->getObjectHandler();
        $fileHandler = $this->getFileHandler();

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $objectHandler,
            $fileHandler
        );

        self::assertFalse($setupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($setupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($setupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($setupHandler->isDatabaseUpdateNecessary());
        self::assertFalse($setupHandler->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers ::backupDatabase()
     */
    public function testBackup()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('getOption')
            ->with('uam_db_version')
            ->will($this->onConsecutiveCalls(null, '1.1', '1.2', '1.3.0'));

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(5))
            ->method('query')
            ->withConsecutive(
                ['CREATE TABLE `userGroupTable_1-2` LIKE `userGroupTable`'],
                ['INSERT `userGroupTable_1-2` SELECT * FROM `userGroupTable`'],
                ['CREATE TABLE `userGroupToObjectTable_1-2` LIKE `userGroupToObjectTable`'],
                ['INSERT `userGroupToObjectTable_1-2` SELECT * FROM `userGroupToObjectTable`'],
                ['CREATE TABLE `userGroupTable_1-3-0` LIKE `userGroupTable`']
            )
            ->will($this->onConsecutiveCalls(true, true, true, true, false));

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertFalse($setupHandler->backupDatabase());
        self::assertFalse($setupHandler->backupDatabase());
        self::assertTrue($setupHandler->backupDatabase());
        self::assertFalse($setupHandler->backupDatabase());
    }

    /**
     * @group  unit
     * @covers ::getBackups()
     */
    public function testGetBackups()
    {
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        $database->expects($this->once())
            ->method('getColumn')
            ->with('SHOW TABLES LIKE \'prefix_uam_%\'')
            ->will($this->returnValue([
                'prefix_uam_one_1-2',
                'prefix_uam_two_1-2',
                'prefix_uam_one_1-5-6',
                'something_1-2-3',
                'invalid1-4'
            ]));

        $setupHandler = new SetupHandler(
            $this->getWordpress(),
            $database,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertEquals(
            ['1.2' => '1.2', '1.5.6' => '1.5.6', '1.2.3' => '1.2.3'],
            $setupHandler->getBackups()
        );
    }

    /**
     * @group  unit
     * @covers ::revertDatabase()
     * @covers ::getBackupTables()
     */
    public function testRevertBackup()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('updateOption')
            ->with('uam_db_version', '1.2');

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(4))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupTable_1-3-1\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-3-1\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable_1-2',
                'userGroupToObjectTable_1-2',
                '',
                'userGroupToObjectTable_1-3-0'
            ));

        $database->expects($this->exactly(5))
            ->method('query')
            ->withConsecutive(
                ['DROP TABLE IF EXISTS `userGroupTable`'],
                ['RENAME TABLE `userGroupTable_1-2` TO `userGroupTable`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable`'],
                ['RENAME TABLE `userGroupToObjectTable_1-2` TO `userGroupToObjectTable`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable`']
            )
            ->will($this->onConsecutiveCalls(true, true, true, true, false));

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertTrue($setupHandler->revertDatabase('1.2'));
        self::assertFalse($setupHandler->revertDatabase('1.3.1'));
    }

    /**
     * @group  unit
     * @covers ::deleteBackup()
     * @covers ::getBackupTables()
     */
    public function testDeleteBackup()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(4))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-2\''],
                ['SHOW TABLES LIKE \'userGroupTable_1-3-1\''],
                ['SHOW TABLES LIKE \'userGroupToObjectTable_1-3-1\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable_1-2',
                'userGroupToObjectTable_1-2',
                '',
                'userGroupToObjectTable_1-3-1'
            ));

        $database->expects($this->exactly(3))
            ->method('query')
            ->withConsecutive(
                ['DROP TABLE IF EXISTS `userGroupTable_1-2`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable_1-2`'],
                ['DROP TABLE IF EXISTS `userGroupToObjectTable_1-3-1`']
            )
            ->will($this->onConsecutiveCalls(true, true, false));

        $setupHandler = new SetupHandler(
            $this->getWordpress(),
            $database,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertTrue($setupHandler->deleteBackup('1.2'));
        self::assertFalse($setupHandler->deleteBackup('1.3.1'));
    }

    /**
     * @group  unit
     * @covers ::update()
     * @covers ::updateTo10()
     * @covers ::updateTo12()
     * @covers ::updateTo13()
     * @covers ::updateTo14()
     * @covers ::updateTo151()
     * @covers ::updateTo16()
     */
    public function testUpdate()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(3))
            ->method('getOption')
            ->withConsecutive(
                ['uam_db_version', false],
                ['uam_db_version', false],
                ['uam_version', '0']
            )
            ->will($this->onConsecutiveCalls('0', '0.0', '0.0'));

        $wordpress->expects($this->once())
            ->method('deleteOption')
            ->with('allow_comments_locked');

        $wordpress->expects($this->once())
            ->method('updateOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $database = $this->getDatabase();
        $database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(5))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        $database->expects($this->once())
            ->method('getCharset')
            ->will($this->returnValue('CHARSET testCharset'));

        $database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postsTable'));

        $database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $database->expects($this->exactly(2))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW columns FROM userGroupTable LIKE \'ip_range\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable',
                'not_ip_range'
            ));

        $firstDbObject = new \stdClass();
        $firstDbObject->groupId = 123;
        $firstDbObject->id = 321;

        $secondDbObject = new \stdClass();
        $secondDbObject->objectId = 123;
        $secondDbObject->groupId = 321;
        $secondDbObject->objectType = 'customPostType';

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
                    'SELECT object_id AS objectId, object_type AS objectType, group_id AS groupId
                    FROM userGroupToObjectTable
                    WHERE general_object_type = \'\''
                )]
            )
            ->will($this->onConsecutiveCalls([$firstDbObject], [], [], [], [$secondDbObject]));

        $database->expects($this->exactly(13))
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
                    'ALTER TABLE `userGroupToObjectTable`
                    CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
                    CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL'
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupToObjectTable
                    ADD general_object_type VARCHAR(64) NOT NULL AFTER object_id'
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_post_\'
                    WHERE object_type IN (\'post\', \'page\', \'attachment\')'
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_role_\'
                    WHERE object_type = \'role\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_user_\'
                    WHERE object_type = \'user\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_term_\'
                    WHERE object_type = \'term\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable AS gto
                    LEFT JOIN termTaxonomyTable AS tt 
                      ON gto.object_id = tt.term_id
                    SET gto.object_type = tt.taxonomy
                    WHERE gto.general_object_type = \'_term_\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupToObjectTable
                    ADD group_type VARCHAR(64) NOT NULL AFTER group_id,
                    ADD from_date DATETIME NULL DEFAULT NULL,
                    ADD to_date DATETIME NULL DEFAULT NULL,
                    MODIFY group_id VARCHAR(64) NOT NULL,
                    MODIFY object_id VARCHAR(64) NOT NULL,
                    DROP PRIMARY KEY,
                    ADD PRIMARY KEY (object_id, object_type, group_id, group_type)'
                )]
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable',
                'ip_range'
            ));

        $database->expects($this->exactly(3))
            ->method('update')
            ->withConsecutive(
                [
                    'userGroupToObjectTable',
                    ['object_type' => ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                    ['object_type' => 'category']
                ],
                [
                    'userGroupToObjectTable',
                    ['general_object_type' => 'generalCustomPostType'],
                    ['object_id' => 123, 'group_id' => 321, 'object_type' => 'customPostType']
                ],
                [
                    'userGroupToObjectTable',
                    ['group_type' => UserGroup::USER_GROUP_TYPE],
                    ['group_type' => '']
                ]
            );

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->once())
            ->method('getObjectTypes')
            ->will($this->returnValue(
                ['post', 'nothing', 'category', 'nothing', 'user', 'nothing', 'role', 'nothing']
            ));

        $objectHandler->expects($this->exactly(8))
            ->method('isPostType')
            ->withConsecutive(
                ['post'],
                ['nothing'],
                ['category'],
                ['nothing'],
                ['user'],
                ['nothing'],
                ['role'],
                ['nothing']
            )
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false, false));

        $objectHandler->expects($this->once())
            ->method('getGeneralObjectType')
            ->with('customPostType')
            ->will($this->returnValue('generalCustomPostType'));

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $objectHandler,
            $this->getFileHandler()
        );

        self::assertFalse($setupHandler->update());
        self::assertTrue($setupHandler->update());
    }

    /**
     * @group  unit
     * @covers ::uninstall()
     */
    public function testUninstall()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites(2)));

        $wordpress->expects(($this->exactly(6)))
            ->method('deleteOption')
            ->withConsecutive(
                [MainConfig::MAIN_CONFIG_KEY],
                ['uam_version'],
                ['uam_db_version'],
                [MainConfig::MAIN_CONFIG_KEY],
                ['uam_version'],
                ['uam_db_version']
            );

        $wordpress->expects(($this->exactly(3)))
            ->method('switchToBlog')
            ->withConsecutive([1], [2], [1]);

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $database->expects($this->exactly(2))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('query')
            ->with(new MatchIgnoreWhitespace(
                'DROP TABLE IF EXISTS userGroupTable, userGroupToObjectTable'
            ));

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->once())
            ->method('deleteFileProtection');

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $this->getObjectHandler(),
            $fileHandler
        );

        $setupHandler->uninstall();
    }

    /**
     * @group  unit
     * @covers ::deactivate()
     */
    public function testDeactivate()
    {
        $wordpress = $this->getWordpress();
        $database = $this->getDatabase();
        $objectHandler = $this->getObjectHandler();
        $fileHandler = $this->getFileHandler();

        $fileHandler->expects($this->exactly(2))
            ->method('deleteFileProtection')
            ->will($this->onConsecutiveCalls(false, true));

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $objectHandler,
            $fileHandler
        );

        self::assertFalse($setupHandler->deactivate());
        self::assertTrue($setupHandler->deactivate());
    }
}
